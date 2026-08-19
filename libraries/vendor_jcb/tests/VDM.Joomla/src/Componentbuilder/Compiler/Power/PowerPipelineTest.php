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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Power;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Power\ExtractorInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor as JoomlaPowerExtractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector as JoomlaPowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Power;
use VDM\Joomla\Componentbuilder\Compiler\Power\Autoloader;
use VDM\Joomla\Componentbuilder\Compiler\Power\Extractor as PowerExtractor;
use VDM\Joomla\Componentbuilder\Compiler\Power\Infusion;
use VDM\Joomla\Componentbuilder\Compiler\Power\Injector as PowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Power\Structure as PowerStructure;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Files;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Folder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Joomla\Interfaces\Readme\ItemInterface as ItemReadme;
use VDM\Joomla\Interfaces\Readme\MainInterface as MainReadme;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Super Power and Joomla Power discovery, injection, infusion, and output contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Power::class)]
#[CoversClass(JoomlaPower::class)]
#[CoversClass(PowerExtractor::class)]
#[CoversClass(JoomlaPowerExtractor::class)]
#[CoversClass(PowerInjector::class)]
#[CoversClass(JoomlaPowerInjector::class)]
#[CoversClass(Autoloader::class)]
#[CoversClass(Infusion::class)]
#[CoversClass(PowerStructure::class)]
final class PowerPipelineTest extends CompilerDomainTestCase
{
	/**
	 * Both power catalogues return already resolved entries when a build forces use.
	 *
	 * @param   class-string  $class  Power catalogue class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('powerCatalogueProvider')]
	public function testPowerCataloguesReturnCachedEntriesWithoutReloading(string $class): void
	{
		$guid = '123e4567-e89b-12d3-a456-426614174000';
		$power = (object) ['guid' => $guid, 'class_name' => 'Service'];
		$subject = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		$this->setCompilerProperty($subject, 'config', $this->compilerConfig(['add_power' => false]));
		$this->setCompilerProperty($subject, 'active', [$guid => $power]);
		$this->setCompilerProperty($subject, 'state', [$guid => true]);

		$this->assertNull($subject->get($guid));
		$this->assertSame($power, $subject->get($guid, 1));
		$subject->load([$guid => 1]);
		$this->assertSame($power, $subject->active[$guid]);
	}

	/**
	 * Token discovery maps exact generated identifiers for both catalogues.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExtractorsMapOnlyTheirOwnGeneratedPowerTokens(): void
	{
		$db = $this->createStub(DatabaseInterface::class);
		$guid = '123e4567-e89b-12d3-a456-426614174000';
		$superToken = 'Super___123e4567_e89b_12d3_a456_426614174000___Power';
		$joomlaToken = 'Joomla___123e4567_e89b_12d3_a456_426614174000___Power';
		$super = new PowerExtractor($db);
		$joomla = new JoomlaPowerExtractor($db, 6);

		$this->assertSame([$superToken => $guid], $super->get('return ' . $superToken . '::get();'));
		$this->assertNull($super->get('return ' . $joomlaToken . '::get();'));
		$this->assertSame([$joomlaToken => $guid], $joomla->get('return ' . $joomlaToken . '::get();'));
		$this->assertNull($joomla->get('return ' . $superToken . '::get();'));
	}

	/**
	 * Document the malformed token stripping in the stateful search pathway.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testPowerSearchCollectsDiscoveredGuidsForLaterLoading(): void
	{
		$subject = new PowerExtractor($this->createStub(DatabaseInterface::class));
		$guid = '123e4567-e89b-12d3-a456-426614174000';

		$subject->search('Super___123e4567_e89b_12d3_a456_426614174000___Power');

		$this->assertSame([$guid => 1], $subject->get_());
	}

	/**
	 * Injectors add a reviewed namespace import and replace the generated token.
	 *
	 * @param   class-string  $class  Injector implementation.
	 * @param   string        $token  Generated token.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('injectorProvider')]
	public function testInjectorsReplaceTokensAndInsertImports(string $class, string $token): void
	{
		$guid = '123e4567-e89b-12d3-a456-426614174000';
		$extractor = $this->createMock(ExtractorInterface::class);
		$extractor->expects($this->once())
			->method('get')
			->willReturn([$token => $guid]);
		$power = $this->createMock(PowerInterface::class);
		$power->expects($this->once())
			->method('get')
			->with($guid)
			->willReturn((object) [
				'_namespace' => 'Acme\Domain',
				'class_name' => 'Service',
				'type' => 'class',
			]);
		$config = $this->compilerConfig();
		$subject = new $class($power, $extractor, new Parser(), new Placeholder($config));
		$code = "<?php\n\nclass Demo\n{\n\tpublic function run()\n\t{\n\t\treturn {$token}::get();\n\t}\n}\n";

		$output = $subject->power($code);

		$this->assertStringContainsString('use Acme\Domain\Service;', $output);
		$this->assertStringContainsString('return Service::get();', $output);
		$this->assertStringNotContainsString($token, $output);
	}

	/**
	 * Joomla Power's override builds the namespace used by generated imports.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJoomlaPowerInjectorBuildsRuntimeNamespaceStatement(): void
	{
		$config = $this->compilerConfig();
		$subject = new JoomlaPowerInjector(
			$this->createStub(PowerInterface::class),
			$this->createStub(ExtractorInterface::class),
			new Parser(),
			new Placeholder($config)
		);

		$this->assertSame(
			'Joomla\CMS\Factory',
			(new ReflectionMethod(JoomlaPowerInjector::class, 'buildNamespaceStatment'))
				->invoke($subject, (object) ['_namespace' => 'Joomla\CMS', 'class_name' => 'Factory'])
		);
	}

	/**
	 * Autoloader output resets stale placeholders and emits admin/site file loaders.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAutoloaderBuildsTargetedFileLoaderPlaceholders(): void
	{
		$power = (new ReflectionClass(Power::class))->newInstanceWithoutConstructor();
		$power->namespace = [];
		$power->composer = [];
		$content = new ContentOne();
		$content->set('POWER_AUTOLOADER', 'stale');
		$config = $this->compilerConfig([
			'component_code_name' => 'demo',
			'component_autoloader_path' => 'src/Helper/PowerloaderHelper.php',
			'component_installer_autoloader_path' => 'DemoInstallerPowerloader.php',
		]);
		$subject = new Autoloader($power, $config, $content);

		$this->assertSame('', $content->get('POWER_AUTOLOADER'));
		$subject->setFiles();

		$this->assertStringContainsString(
			"JPATH_ADMINISTRATOR . '/components/com_demo/src/Helper/PowerloaderHelper.php'",
			$content->get('ONE_POWER_AUTOLOADER')
		);
		$this->assertStringContainsString(
			"JPATH_SITE . '/components/com_demo/src/Helper/PowerloaderHelper.php'",
			$content->get('SITE_ONE_POWER_AUTOLOADER')
		);
		$this->assertStringContainsString('INSTALLER_POWER_AUTOLOADER_ARRAY', implode(' ', array_keys($content->allActive())));
	}

	/**
	 * Infusion emits source, raw code, linker JSON, README, and ordered events once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInfusionWritesCompletePowerArtifactsOnlyOnce(): void
	{
		$guid = '123e4567-e89b-12d3-a456-426614174000';
		$power = (new ReflectionClass(Power::class))->newInstanceWithoutConstructor();
		$power->active = [
			$guid => (object) [
				'key' => '17_P0m3R',
				'guid' => $guid,
				'_namespace' => 'Acme\Power',
				'class_name' => 'Widget',
				'type' => 'class',
				'description' => 'A reusable widget.',
				'main_class_code' => "\tpublic function value(): string\n\t{\n\t\treturn '###TOKEN###';\n\t}",
				'unchanged_main_class_code' => 'RAW-CODE',
			],
		];
		$power->superpowers = [];
		$power->old_superpowers = [];
		$config = $this->compilerConfig(['add_super_powers' => false]);
		$content = new ContentOne();
		$content->set('TOKEN', 'resolved');
		$contents = new ContentMulti();
		$itemReadme = $this->createMock(ItemReadme::class);
		$itemReadme->expects($this->once())->method('get')->willReturn('# Widget');
		$events = [];
		$event = $this->createMock(EventInterface::class);
		$event->expects($this->exactly(2))
			->method('trigger')
			->willReturnCallback(function (string $name) use (&$events): void
			{
				$events[] = $name;
			});
		$subject = new Infusion(
			$config,
			$power,
			$content,
			$contents,
			new Parser(),
			$itemReadme,
			$this->createStub(MainReadme::class),
			new Placeholder($config),
			$event
		);

		$subject->set();
		$subject->set();

		$this->assertSame([
			'jcb_ce_onBeforeInfusePowerData',
			'jcb_ce_onAfterInfusePowerData',
		], $events);
		$this->assertStringContainsString('namespace Acme\Power;', $contents->get('17_P0m3R|POWERCODE'));
		$this->assertStringContainsString("return 'resolved';", $contents->get('17_P0m3R|POWERCODE'));
		$this->assertSame('RAW-CODE', $contents->get('17_P0m3R|CODEPOWER'));
		$this->assertStringContainsString($guid, $contents->get('17_P0m3R|POWERLINKER'));
		$this->assertSame('# Widget', $contents->get('17_P0m3R|POWERREADME'));
	}

	/**
	 * Structure creates the generated power file and deny-access companions once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPowerStructureTracksGeneratedFilesPathsAndCounters(): void
	{
		$root = sys_get_temp_dir() . '/jcb-power-' . bin2hex(random_bytes(5));
		$rootCreated = false;

		try
		{
			if (!mkdir($root, 0700))
			{
				throw new RuntimeException('Unable to create the power-structure fixture: ' . $root);
			}

			$rootCreated = true;
			$power = (new ReflectionClass(Power::class))->newInstanceWithoutConstructor();
			$power->active = [
				'guid' => (object) [
					'path' => 'src/Acme/Power',
					'path_jcb' => 'src/Acme',
					'path_parent' => 'src/Acme',
					'key' => '17_P0m3R',
					'file_name' => 'Widget',
					'add_licensing_template' => 0,
				],
			];
			$power->superpowers = [];
			$power->old_superpowers = [];
			$config = $this->compilerConfig(['add_super_powers' => false, 'joomla_version' => 6]);
			$registry = new Registry();
			$event = $this->createMock(EventInterface::class);
			$event->expects($this->once())->method('trigger')->with('jcb_ce_onBeforeBuildPowers');
			$counter = (new ReflectionClass(Counter::class))->newInstanceWithoutConstructor();
			$folder = $this->createMock(Folder::class);
			$folder->expects($this->exactly(3))->method('create')->willReturn(true);
			$written = [];
			$file = $this->createMock(File::class);
			$file->expects($this->exactly(4))
				->method('write')
				->willReturnCallback(
					static function (string $path, string $data) use (&$written): bool
					{
						$written[basename($path)] = $data;

						return true;
					}
				);
			$files = new Files();
			$app = $this->createMock(CMSApplicationInterface::class);
			$app->expects($this->exactly(2))->method('enqueueMessage');
			$paths = new class($root) extends Paths
			{
				/**
				 * Component output root.
				 *
				 * @var    string
				 * @since  6.1.6
				 */
				private string $root;

				/**
				 * Create a path fixture for the isolated build root.
				 *
				 * @param   string  $root  Component output root.
				 *
				 * @since   6.1.6
				 */
				public function __construct(string $root)
				{
					$this->root = $root;
				}

				/**
				 * Resolve the component path used by the power structure.
				 *
				 * @param   string  $key  Requested path key.
				 *
				 * @return  string  Component output root.
				 *
				 * @throws  \InvalidArgumentException  If the path key is unsupported.
				 * @since   6.1.6
				 */
				public function __get($key)
				{
					if ($key === 'component_path')
					{
						return $this->root;
					}

					throw new \InvalidArgumentException((string) $key);
				}
			};
			$subject = new PowerStructure(
				$power,
				$config,
				$registry,
				$event,
				$counter,
				$paths,
				$folder,
				$file,
				$files,
				$app
			);

			$subject->build();

			$this->assertSame($root . '/src/Acme', $registry->get('dynamic_paths.17_P0m3R'));
			$this->assertTrue($registry->get('set_move_folders_install_script'));
			$this->assertCount(4, $files->get('17_P0m3R'));
			$this->assertSame(4, $counter->file);
			$this->assertSame(1, $counter->power);

			// the deny only reaches IIS if the document parses, and it was
			// written without its configuration root element
			$this->assertArrayHasKey('web.config', $written);
			$previous = libxml_use_internal_errors(true);
			$document = simplexml_load_string($written['web.config']);
			libxml_clear_errors();
			libxml_use_internal_errors($previous);
			$this->assertNotFalse($document, 'The generated web.config must be well formed XML');
			$this->assertSame('configuration', $document->getName());
			$this->assertSame('*', (string) $document->{'system.web'}->authorization->deny['users']);

			// the Apache side denies every request in both module eras
			$this->assertArrayHasKey('.htaccess', $written);
			$this->assertStringContainsString('Require all denied', $written['.htaccess']);
			$this->assertStringContainsString('Deny from all', $written['.htaccess']);
		}
		finally
		{
			if ($rootCreated && is_link($root) && !unlink($root))
			{
				throw new RuntimeException('Unable to remove the power-structure fixture link: ' . $root);
			}

			if ($rootCreated && is_dir($root) && !rmdir($root))
			{
				throw new RuntimeException('Unable to remove the power-structure fixture: ' . $root);
			}

			if ($rootCreated && file_exists($root))
			{
				throw new RuntimeException('The power-structure fixture is not a directory: ' . $root);
			}
		}
	}

	/**
	 * Power catalogue implementations.
	 *
	 * @return  array<string, array{class-string}>
	 * @since   6.1.6
	 */
	public static function powerCatalogueProvider(): array
	{
		return [
			'Super Powers' => [Power::class],
			'Joomla Powers' => [JoomlaPower::class],
		];
	}

	/**
	 * Power injector implementations and their token prefixes.
	 *
	 * @return  array<string, array{class-string,string}>
	 * @since   6.1.6
	 */
	public static function injectorProvider(): array
	{
		return [
			'Super Power' => [PowerInjector::class, 'Super___123e4567_e89b_12d3_a456_426614174000___Power'],
			'Joomla Power' => [JoomlaPowerInjector::class, 'Joomla___123e4567_e89b_12d3_a456_426614174000___Power'],
		];
	}
}
