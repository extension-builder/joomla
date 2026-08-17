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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LibraryManager;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Module\LibraryInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Customcode\DispenserInterface;
use VDM\Joomla\Componentbuilder\Compiler\Registry;


/**
 * Module and plugin generated-artifact contracts across Joomla targets.
 *
 * @since  6.1.6
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Power')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedModulePluginRendererTest extends ArchitectureTestCase
{
	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.6
	 */
	public static function versions(): array
	{
		return VersionedPermissionRendererTest::versions();
	}

	/**
	 * Joomla targets whose database-aware module helper currently renders.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.6
	 */
	public static function workingDatabaseHelperVersions(): array
	{
		return self::versions();
	}

	/**
	 * Protect basic helper wrapping for every target, including Joomla 4.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModuleHelperWrapsPlainBodiesForEveryTarget(string $version, int $major): void
	{
		$content = new ContentOne();
		$content->set('TOKEN', 'resolved');
		$subject = $this->renderer(
			$this->rendererClass($version, 'Module/Helper'),
			['contentone' => $content]
		);
		$module = (object) [
			'class_helper_type' => 'final class',
			'class_helper_name' => 'ArticleHelper',
			'class_helper_code' => "\tpublic function value(): string\n\t{\n\t\treturn '###TOKEN###';\n\t}",
			'class_helper_header' => 'use Acme\\Article;',
		];

		$this->assertSame(
			PHP_EOL . "final class ArticleHelper\n{\n\tpublic function value(): string\n\t{\n\t\treturn 'resolved';\n\t}\n}\n",
			$subject->get($module)
		);
		$this->assertSame(PHP_EOL . 'use Acme\\Article;', $subject->header($module));
	}

	/**
	 * Protect helper class wrapping, placeholder replacement, and database traits.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('workingDatabaseHelperVersions')]
	public function testModuleHelperPreservesDatabaseAwareTargetDifference(string $version, int $major): void
	{
		$content = new ContentOne();
		$content->set('TOKEN', 'resolved');
		$subject = $this->renderer(
			$this->rendererClass($version, 'Module/Helper'),
			['contentone' => $content]
		);
		$module = (object) [
			'class_helper_type' => 'final class',
			'class_helper_name' => 'ArticleHelper',
			'class_helper_code' => "\tpublic function load(): void\n\t{\n\t\t\$this->getDatabase();\n\t\t// ###TOKEN###\n\t}",
			'class_helper_header' => '',
		];

		$code = $subject->get($module);

		$this->assertStringStartsWith(PHP_EOL . 'final class ArticleHelper', $code);
		$this->assertStringContainsString('// resolved', $code);
		$this->assertStringEndsWith(PHP_EOL . '}' . PHP_EOL, $code);

		if ($major === 3)
		{
			$this->assertStringNotContainsString('implements DatabaseAwareInterface', $code);
			$this->assertSame('', $subject->header($module));
			return;
		}

		$this->assertStringContainsString(' implements DatabaseAwareInterface', $code);
		$this->assertSame(1, substr_count($code, 'use DatabaseAwareTrait;'));
		$this->assertSame(
			PHP_EOL . 'use Joomla\\Database\\DatabaseAwareInterface;'
				. PHP_EOL . 'use Joomla\\Database\\DatabaseAwareTrait;',
			$subject->header($module)
		);
	}

	/**
	 * Joomla 4 indents the database-aware trait it prepends.
	 *
	 * The target class called the indentation utility without importing it,
	 * which resolved a nonexistent class in its own namespace and fatalled on
	 * every database-aware Joomla 4 module.
	 *
	 * @return  void
	 * @since   6.1.6
	 * @since   6.1.7  The missing import was added, so this now guards it.
	 */
	public function testJoomlaFourModuleHelperCanAddDatabaseAwareTrait(): void
	{
		$subject = $this->renderer(
			$this->rendererClass('JoomlaFour', 'Module/Helper')
		);
		$module = (object) [
			'class_helper_type' => 'class',
			'class_helper_name' => 'ArticleHelper',
			'class_helper_code' => "\tpublic function load(): void\n\t{\n\t\t\$this->getDatabase();\n\t}",
			'class_helper_header' => '',
		];

		$this->assertStringContainsString(
			"\tuse DatabaseAwareTrait;",
			$subject->get($module)
		);
	}

	/**
	 * Protect dispatcher collaboration, placeholders, and modern fast path.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModuleDispatcherUsesInjectedLibraryAndTargetShape(string $version, int $major): void
	{
		$library = $this->createMock(LibraryInterface::class);
		$library->expects($this->once())
			->method('get')
			->willReturn($major === 3 ? 'LIBRARY_CODE;' : '');

		$builder = new ContentOne();
		$builder->set('Component', 'Demo');
		$builder->set('component', 'demo');

		$subject = $this->renderer(
			$this->rendererClass($version, 'Module/Dispatcher'),
			[
				'builder' => $builder,
				'library' => $library,
			]
		);
		$module = (object) [
			'official_name' => 'Articles',
			'mod_code' => '[[[MOD_LIBRARIES]]]' . PHP_EOL . "echo 'body';",
			'layout_data' => '',
			'add_class_helper' => 0,
			'custom_get' => false,
			'namespace' => 'Articles',
			'target_client_namespace' => 'Site',
			'class_helper_name' => 'ArticleHelper',
		];

		$code = $subject->get($module);

		if ($major === 3)
		{
			$this->assertSame(
				PHP_EOL . 'LIBRARY_CODE;' . PHP_EOL . "echo 'body';" . PHP_EOL,
				$code
			);
			return;
		}

		$this->assertStringContainsString('Dispatcher class for Articles', $code);
		$this->assertStringContainsString(
			'class Dispatcher extends AbstractModuleDispatcher {}' . PHP_EOL,
			$code
		);
		$this->assertStringNotContainsString("echo 'body';", $code);
	}

	/**
	 * Protect library-manager paths, document normalization, and host API changes.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModuleLibraryUsesBuilderRegistryAndNormalizesDocumentCode(string $version, int $major): void
	{
		$manager = new LibraryManager();
		$manager->set('module.article', [7 => ['enabled' => true]]);
		$registry = new Registry();
		$registry->set('builder.libraries.7', (object) [
			'document' => "\t\$this->document->addScript('asset.js');  ",
		]);

		$subject = $this->renderer(
			$this->rendererClass($version, 'Module/Library'),
			[
				'librarymanager' => $manager,
				'registry' => $registry,
			]
		);
		$code = $subject->get((object) ['key' => 'module', 'code_name' => 'article']);

		$this->assertStringContainsString("\$document->addScript('asset.js');", $code);
		$this->assertStringNotContainsString('$this->document->', $code);
		$this->assertStringContainsString(
			$major === 3
				? '$document = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getDocument();'
				: '$document = $this->app->getDocument();',
			$code
		);
		$this->assertSame('', $subject->get((object) ['key' => 'missing', 'code_name' => 'article']));
	}

	/**
	 * Protect module provider availability and generated DI registrations.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModuleProviderIsAbsentOnlyFromJoomlaThree(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'Module/Provider'));
		$module = (object) [
			'official_name' => 'Articles',
			'namespace' => 'Articles',
			'target_client_namespace' => 'Site',
			'add_class_helper' => 1,
			'custom_get' => false,
		];
		$code = $subject->get($module);

		if ($major === 3)
		{
			$this->assertSame('', $code);
			return;
		}

		$this->assertStringContainsString('return new class () implements ServiceProviderInterface {', $code);
		$this->assertStringContainsString('ModuleDispatcherFactory(', $code);
		$this->assertStringContainsString('Acme', $code);
		$this->assertStringContainsString('Articles', $code);
		$this->assertStringContainsString('HelperFactory(', $code);
		$this->assertStringEndsWith('};' . PHP_EOL, $code);
	}

	/**
	 * Protect module template boundary arguments and exact concatenation order.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModuleTemplateCombinesCssBodyAndJavascriptInOrder(string $version, int $major): void
	{
		$dispenser = $this->createMock(DispenserInterface::class);
		$dispenser->expects($this->exactly(2))
			->method('get')
			->willReturnCallback(
				function (string $first, string $second, string $prefix, ?string $note,
					bool $unset, mixed $default, string $suffix): string
				{
					$this->assertSame('article', $second);
					$this->assertSame('', $note);
					$this->assertTrue($unset);
					$this->assertNull($default);

					if ($first === 'css_views')
					{
						$this->assertSame(PHP_EOL . '<style>', $prefix);
						$this->assertSame(PHP_EOL . '</style>' . PHP_EOL, $suffix);
						return PHP_EOL . '<style>CSS</style>' . PHP_EOL;
					}

					$this->assertSame('views_footer', $first);
					$this->assertSame(PHP_EOL . '<script type="text/javascript">', $prefix);
					$this->assertSame(PHP_EOL . '</script>' . PHP_EOL, $suffix);

					return PHP_EOL . '<script type="text/javascript">JS</script>' . PHP_EOL;
				}
			);

		$subject = $this->renderer(
			$this->rendererClass($version, 'Module/Template'),
			['dispenser' => $dispenser]
		);
		$module = (object) [
			'default' => '<section>Body</section>',
			'add_default_header' => 1,
			'default_header' => 'use Acme\\ModuleHeader;',
		];

		$expected = PHP_EOL . '<style>CSS</style>' . PHP_EOL
			. PHP_EOL . '<section>Body</section>' . PHP_EOL
			. PHP_EOL . '<script type="text/javascript">JS</script>' . PHP_EOL;

		if ($major === 3)
		{
			$expected = PHP_EOL . 'use Acme\\ModuleHeader;' . PHP_EOL . '?>' . $expected;
		}

		$this->assertSame($expected, $subject->default($module, 'article'));

		if ($major === 3)
		{
			$this->assertFalse(method_exists($subject, 'header'));
		}
		else
		{
			$this->assertSame(
				PHP_EOL . PHP_EOL . 'use Acme\\ModuleHeader;',
				$subject->header($module)
			);
		}
	}

	/**
	 * Protect manifest file discovery, SQL paths, config shape, and update server.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModuleMainXmlBuildsReviewedManifestSections(string $version, int $major): void
	{
		$folder = $this->createTemporaryDirectory('module');
		$this->writeTemporaryFile('module/helper.php', '<?php');
		$this->createTemporaryDirectory('module/media');
		$this->createTemporaryDirectory('module/sql');

		$subject = $this->renderer($this->rendererClass($version, 'Module/MainXML'));
		$module = (object) [
			'add_install_script' => true,
			'add_sql' => true,
			'add_sql_uninstall' => true,
			'add_update_server' => true,
			'config_fields' => [],
			'fieldsets_paths' => [],
			'fieldsets_label' => [],
			'folder_path' => $folder,
			'file_name' => 'mod_articles',
			'key' => 'module.article',
			'guid' => 'module-guid',
			'official_name' => 'Articles Module',
			'update_server_url' => 'https://example.test/module.xml',
			'add_scripts_field' => false,
			'moduleclass_sfx_label' => 'MOD_ARTICLES_CLASS_SUFFIX',
			'caching_label' => 'MOD_ARTICLES_CACHING',
			'value_nocaching' => 'MOD_ARTICLES_NO_CACHING',
			'cache_time_label' => 'MOD_ARTICLES_CACHE_TIME',
		];
		$xml = $subject->get($module);

		$this->assertStringContainsString('<scriptfile>script.php</scriptfile>', $xml);
		$this->assertStringContainsString('sql/mysql/install.sql', $xml);
		$this->assertStringContainsString('sql/mysql/uninstall.sql', $xml);
		$this->assertStringContainsString('<filename>helper.php</filename>', $xml);
		$this->assertStringContainsString('<folder>media</folder>', $xml);
		$this->assertStringContainsString('<folder>sql</folder>', $xml);

		if ($major >= 5)
		{
			$this->assertStringContainsString('label="MOD_ARTICLES_CLASS_SUFFIX"', $xml);
			$this->assertSame(1, substr_count($xml, '<config>'));
			$this->assertSame(1, substr_count($xml, '</config>'));
		}
		else
		{
			$this->assertStringNotContainsString('<config>', $xml);
		}
		$this->assertStringContainsString(
			'<server type="extension" priority="1" name="Articles Module">https://example.test/module.xml</server>',
			$xml
		);
	}

	/**
	 * Protect plugin class shape and modern subscriber synthesis.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testPluginExtensionSynthesizesNonLegacySubscriberContract(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'Plugin/Extension'));
		$plugin = (object) [
			'comment' => '/** Articles plugin. */',
			'class_name' => 'ArticlesPlugin',
			'extends' => 'CMSPlugin',
			'main_class_code' => "\tpublic function collectArticles(): void\n\t{\n\t}",
		];
		$code = $subject->get($plugin);

		$this->assertStringContainsString('/** Articles plugin. */', $code);
		$this->assertStringContainsString('ArticlesPlugin extends CMSPlugin', $code);
		$this->assertStringEndsWith(PHP_EOL . '}' . PHP_EOL, $code);

		if ($major === 3)
		{
			$this->assertStringContainsString('class ArticlesPlugin extends CMSPlugin', $code);
			$this->assertStringNotContainsString('getSubscribedEvents', $code);
			return;
		}

		$this->assertStringContainsString('final class ArticlesPlugin extends CMSPlugin', $code);
		$this->assertStringContainsString(' implements Joomla___c06c5116_6b9d_487c_9b09_5094ec4506a3___Power', $code);
		$this->assertStringContainsString('public static function getSubscribedEvents(): array', $code);
		$this->assertStringContainsString("'collectArticles' => 'collectArticles'", $code);
	}

	/**
	 * Protect plugin provider availability and exact plugin lookup identity.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testPluginProviderIsAbsentOnlyFromJoomlaThree(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'Plugin/Provider'));
		$plugin = (object) [
			'group' => 'SYSTEM',
			'context_name' => 'articles',
			'class_name' => 'ArticlesPlugin',
			'service_provider' => "\t\t\t\t\$plugin->boot();",
		];
		$code = $subject->get($plugin);

		if ($major === 3)
		{
			$this->assertSame('', $code);
			return;
		}

		$this->assertStringContainsString("PluginHelper::getPlugin('system', 'articles')", $code);
		$this->assertStringContainsString('$plugin = new ArticlesPlugin(', $code);

		if ($major === 4)
		{
			$this->assertStringContainsString('$app = Factory::getApplication();', $code);
			$this->assertStringContainsString('$plugin->setApplication($app);', $code);
		}
		else
		{
			$this->assertStringContainsString('$plugin->setApplication(Factory::getApplication());', $code);
		}
		$this->assertStringContainsString('$plugin->boot();', $code);
		$this->assertStringEndsWith('};' . PHP_EOL, $code);
	}

	/**
	 * Protect plugin manifest SQL, file inventory, and update-server sections.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testPluginMainXmlBuildsReviewedManifestSections(string $version, int $major): void
	{
		$folder = $this->createTemporaryDirectory('plugin');
		$this->writeTemporaryFile('plugin/articles.php', '<?php');
		$this->createTemporaryDirectory('plugin/services');
		$this->createTemporaryDirectory('plugin/sql');

		$subject = $this->renderer($this->rendererClass($version, 'Plugin/MainXML'));
		$plugin = (object) [
			'add_install_script' => true,
			'add_sql' => true,
			'add_sql_uninstall' => true,
			'add_update_server' => true,
			'config_fields' => [],
			'fieldsets_paths' => [],
			'fieldsets_label' => [],
			'folder_path' => $folder,
			'file_name' => 'articles',
			'context_name' => 'articles',
			'group' => 'SYSTEM',
			'key' => 'plugin.article',
			'guid' => 'plugin-guid',
			'official_name' => 'Articles Plugin',
			'update_server_url' => 'https://example.test/plugin.xml',
		];
		$xml = $subject->get($plugin);

		$this->assertStringContainsString('<scriptfile>script.php</scriptfile>', $xml);
		$this->assertStringContainsString('sql/mysql/install.sql', $xml);
		$this->assertStringContainsString('sql/mysql/uninstall.sql', $xml);

		if ($major === 3)
		{
			$this->assertStringContainsString('<filename plugin="articles">articles.php</filename>', $xml);
		}
		else
		{
			$this->assertStringContainsString('<filename>articles.php</filename>', $xml);
		}
		$this->assertStringContainsString('<folder>services</folder>', $xml);
		$this->assertStringContainsString('<folder>sql</folder>', $xml);
		$this->assertStringContainsString(
			'<server type="extension" priority="1" name="Articles Plugin">https://example.test/plugin.xml</server>',
			$xml
		);
		$this->assertStringNotContainsString('<config>', $xml);
	}

	/**
	 * Build a versioned renderer class name.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   string  $family   Slash-delimited renderer family.
	 *
	 * @return  class-string
	 * @since   6.1.6
	 */
	private function rendererClass(string $version, string $family): string
	{
		return 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\'
			. $version . '\\' . str_replace('/', '\\', $family);
	}
}
