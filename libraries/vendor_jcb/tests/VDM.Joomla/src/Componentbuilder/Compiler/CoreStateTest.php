<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler;


use Joomla\CMS\Application\CMSApplicationInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler as FinalCompiler;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data as ComponentData;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\FilePaths;
use VDM\Joomla\Componentbuilder\Compiler\Initializer;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Joomla\Path;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder\Reverse;
use VDM\Joomla\Componentbuilder\Compiler\Power;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Files;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Compiler configuration, placeholder, cache, and initialization contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(FinalCompiler::class)]
#[CoversClass(Config::class)]
#[CoversClass(FilePaths::class)]
#[CoversClass(Initializer::class)]
#[CoversClass(Path::class)]
#[CoversClass(Placeholder::class)]
#[CoversClass(Reverse::class)]
#[CoversClass(Registry::class)]
#[CoversClass(Files::class)]
#[UsesClass(Component::class)]
#[UsesClass(ContentOne::class)]
final class CoreStateTest extends CompilerDomainTestCase
{
	/**
	 * The target catalogue remains complete and mutable overrides stay cached.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigProtectsTargetCatalogueAndMutableBuildState(): void
	{
		$config = $this->compilerConfig();

		$this->assertSame(
			[
				3 => ['folder_key' => 3, 'xml_version' => '3.10'],
				4 => ['folder_key' => 4, 'xml_version' => '4.0'],
				5 => ['folder_key' => 4, 'xml_version' => '5.0'],
				6 => ['folder_key' => 4, 'xml_version' => '6.0'],
			],
			$config->joomla_versions
		);
		$this->assertSame('admin', $config->build_target);
		$this->assertSame('admin', $config->lang_target);
		$this->assertSame(['basic', 'medium', 'whmcs', 'expert'], $config->cryption_types);

		$config->build_target = 'site';
		$config->joomla_version = 4;

		$this->assertSame('site', $config->build_target);
		$this->assertSame(4, $config->joomla_version);
		$this->assertSame('src/Helper/PowerloaderHelper.php', $config->component_autoloader_path);
	}

	/**
	 * Compiler registries retain their deliberately different path semantics.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCompilerRegistriesPreserveJoomlaAndVdmStorageContracts(): void
	{
		$registry = new Registry();
		$files = new Files();
		$filePaths = new FilePaths();

		$registry->set('build.target', 'administrator');
		$registry->appendArray('build.files', 'one.php');
		$registry->appendArray('build.files', 'two.php');
		$files->appendArray('component', ['name' => 'manifest.xml']);
		$filePaths->set('component.manifest', '/tmp/manifest.xml');

		$this->assertSame('administrator', $registry->get('build.target'));
		$this->assertSame(['one.php', 'two.php'], $registry->get('build.files'));
		$this->assertTrue($registry->isArray('build.files'));
		$this->assertSame([['name' => 'manifest.xml']], $files->get('component'));
		$this->assertSame('/tmp/manifest.xml', $filePaths->get('component.manifest'));
		$missing = $registry->_('missing');
		$this->assertInstanceOf(\ArrayIterator::class, $missing);
		$this->assertCount(0, $missing);
	}

	/**
	 * Placeholder operations keep plain and both hashed spellings synchronized.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPlaceholderProtectsHashVariantsTypesAndReplacementModes(): void
	{
		$config = $this->compilerConfig(['add_placeholders' => true]);
		$subject = new Placeholder($config);

		$subject->set('Name', 'Article');
		$this->assertSame('Article', $subject->get('Name'));
		$this->assertSame('Article', $subject->get_('Name'));
		$this->assertSame('Article', $subject->get_h('Name'));

		$subject->add('Name', ' List');
		$this->assertSame('Article List', $subject->get('Name'));
		$this->assertSame('x Article List y', $subject->update_('x ' . Placefix::_('Name') . ' y'));

		$subject->setType('Field', ['title', 'alias']);
		$this->assertSame('title', $subject->get('Field0'));
		$this->assertSame('alias', $subject->get('Field1'));
		$subject->setType('Field', ['state']);
		$this->assertSame('state', $subject->get('Field0'));
		$this->assertFalse($subject->exist('Field1'));

		$this->assertSame('A one B', $subject->update('A TOKEN B', ['TOKEN' => 'one'], 2));
		$this->assertSame('A  B', $subject->update('A TOKEN B', ['TOKEN' => ''], 3));
		$this->assertSame(
			['start' => '/***[INSERTED$$$$]***//**7**/', 'end' => '/***[/INSERTED$$$$]***/'],
			$subject->keys(12, 7)
		);

		$subject->remove('Name');
		$this->assertFalse($subject->exist('Name'));
	}

	/**
	 * Joomla namespace resolution distinguishes component, module, and plugin roots.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJoomlaPathResolvesCoreNamespacesAndCachesResolvedPrefixes(): void
	{
		$placeholder = new Placeholder($this->compilerConfig());
		$placeholder->set('NamespacePrefix', 'Acme');
		$placeholder->set('ComponentNamespace', 'Demo');
		$subject = new Path($placeholder);

		$this->assertSame('admin', $subject->core('Acme\Component\Demo\Administrator\Model\ArticleModel'));
		$this->assertSame('site', $subject->core('Acme\Component\Demo\Site\Controller\DisplayController'));
		$this->assertSame('mod_blog', $subject->core('Acme\Module\Blog\Site\Dispatcher'));
		$this->assertSame('plg_content_example', $subject->core('Acme\Plugin\Content\Example\Extension\Example'));
		$this->assertNull($subject->core('Other\Component\Demo\Administrator'));
		$this->assertNull($subject->core(''));
		$this->assertNull($subject->get('unknown'));

		$resolved = $subject->get('admin');
		$placeholder->set('NamespacePrefix', 'Changed');
		$this->assertSame($resolved, $subject->get('admin'));
	}

	/**
	 * Reverse mapping honors imported aliases and then applies caller placeholders.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testReverseMapsPowerImportsAndAppliesProvidedPlaceholders(): void
	{
		$subject = (new ReflectionClass(Reverse::class))->newInstanceWithoutConstructor();
		$method = new ReflectionMethod(Reverse::class, 'getReversePower');
		$guid = '123e4567-e89b-12d3-a456-426614174000';

		$this->assertSame(
			['Alias' => 'Super___123e4567_e89b_12d3_a456_426614174000___Power'],
			$method->invoke(
				$subject,
				[$guid => 'Acme\Domain\Service'],
				['use Acme\Domain\Service as Alias;'],
				'Super'
			)
		);

		$config = $this->compilerConfig();
		$placeholder = new Placeholder($config);
		$this->setCompilerProperty($subject, 'config', $config);
		$this->setCompilerProperty($subject, 'placeholder', $placeholder);
		$placeholders = ['TOKEN' => 'resolved'];

		$this->assertSame(
			'before resolved after',
			$subject->engine('before TOKEN after', $placeholders, 'component')
		);
		$this->assertSame(['TOKEN' => 'resolved'], $placeholders);
	}

	/**
	 * Initializer field selection falls back without Tidy and utility powers are forced.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInitializerProtectsFallbackAndRequiredPowerCatalogue(): void
	{
		$subject = (new ReflectionClass(Initializer::class))->newInstanceWithoutConstructor();
		$config = $this->compilerConfig(['field_builder_type' => 2, 'tidy' => false]);
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(2))->method('enqueueMessage');
		$this->setCompilerProperty($subject, 'config', $config);
		$this->setCompilerProperty($subject, 'app', $app);

		(new ReflectionMethod(Initializer::class, 'initializeFieldBuilderType'))->invoke($subject);
		$this->assertSame(1, $config->get('field_builder_type'));

		$expected = [
			'1f28cb53-60d9-4db1-b517-3c7dc6b429ef',
			'0a59c65c-9daf-4bc9-baf4-e063ff9e6a8a',
			'640b5352-fb09-425f-a26e-cd44eda03f15',
			'91004529-94a9-4590-b842-e7c6b624ecf5',
			'db87c339-5bb6-4291-a7ef-2c48ea1b06bc',
			'4b225c51-d293-48e4-b3f6-5136cf5c3f18',
			'1198aecf-84c6-45d2-aea8-d531aa4afdfa',
		];
		$power = $this->createMock(Power::class);
		$power->expects($this->exactly(7))
			->method('get')
			->willReturnCallback(function (string $guid, int $build) use (&$expected): ?object
			{
				$this->assertSame(array_shift($expected), $guid);
				$this->assertSame(1, $build);

				return null;
			});
		$this->setCompilerProperty($subject, 'power', $power);

		(new ReflectionMethod(Initializer::class, 'loadUtilityPowers'))->invoke($subject);
		$this->assertSame([], $expected);
	}

	/**
	 * Initializer normalizes and increments component versions only once per SQL change.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInitializerNormalizesAndTracksSqlDrivenVersionIncrement(): void
	{
		$subject = (new ReflectionClass(Initializer::class))->newInstanceWithoutConstructor();
		$component = $this->componentRegistry();
		$registry = new Registry();
		$component->set('component_version', '7');
		$registry->set('builder.update_sql', true);
		$this->setCompilerProperty($subject, 'component', $component);
		$this->setCompilerProperty($subject, 'registry', $registry);

		(new ReflectionMethod(Initializer::class, 'ensureComponentVersion'))->invoke($subject);
		(new ReflectionMethod(Initializer::class, 'updateComponentVersionIfNeeded'))->invoke($subject);

		$this->assertSame('1.0.1', $component->get('component_version'));
		$this->assertSame('1.0.0', $component->get('old_component_version'));

		(new ReflectionMethod(Initializer::class, 'updateComponentVersionIfNeeded'))->invoke($subject);
		$this->assertSame('1.0.1', $component->get('component_version'));
	}

	/**
	 * Finalization validates update-server files and preserves license placeholder resets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFinalCompilerProtectsUpdateServerAndLicensePlaceholderContracts(): void
	{
		$subject = (new ReflectionClass(FinalCompiler::class))->newInstanceWithoutConstructor();
		$validMethod = new ReflectionMethod(FinalCompiler::class, 'isValidUpdateServerObject');
		$file = null;

		try
		{
			$temporaryFile = tempnam(sys_get_temp_dir(), 'jcb-update-');

			if ($temporaryFile === false)
			{
				throw new RuntimeException('Unable to create the update-server fixture.');
			}

			$file = $temporaryFile;
			$item = (object) [
				'add_update_server' => 1,
				'update_server_target' => 1,
				'update_server' => 8,
				'update_server_xml_path' => $file,
				'update_server_xml_file_name' => 'updates.xml',
			];

			$this->assertTrue($validMethod->invoke($subject, $item));
			$item->update_server = 0;
			$this->assertFalse($validMethod->invoke($subject, $item));
		}
		finally
		{
			if ($file !== null && (is_link($file) || is_file($file)) && !unlink($file))
			{
				throw new RuntimeException('Unable to remove the update-server fixture: ' . $file);
			}

			if ($file !== null && file_exists($file))
			{
				throw new RuntimeException('The update-server fixture is not a file: ' . $file);
			}
		}

		$component = $this->componentRegistry();
		$content = new ContentOne();
		$component->set('mvc_versiondate', 1);
		$content->set('GLOBALCREATIONDATE', 'global-created');
		$content->set('GLOBALBUILDDATE', 'global-built');
		$content->set('GLOBALVERSION', '9.0.0');
		$this->setCompilerProperty($subject, 'component', $component);
		$this->setCompilerProperty($subject, 'contentone', $content);
		$fix = new ReflectionMethod(FinalCompiler::class, 'fixLicenseValues');

		$this->assertTrue($fix->invoke($subject, [
			'config' => [Placefix::_h('VERSION') => 2, 'BUILDDATE' => 'view-built'],
		]));
		$this->assertSame('@update number 2 of this MVC', $content->get(Placefix::_h('VERSION')));
		$this->assertSame('view-built', $content->get('BUILDDATE'));

		$component->set('mvc_versiondate', 0);
		$this->assertNull($fix->invoke($subject, []));
		$this->assertSame('global-created', $content->get('CREATIONDATE'));
		$this->assertSame('global-built', $content->get('BUILDDATE'));
		$this->assertSame('9.0.0', $content->get('VERSION'));
	}

	/**
	 * Create a usable component registry without loading component data.
	 *
	 * @return  Component
	 * @since   6.1.6
	 */
	private function componentRegistry(): Component
	{
		$data = (new ReflectionClass(ComponentData::class))->newInstanceWithoutConstructor();

		return new Component($data, $this->createStub(EventInterface::class));
	}
}
