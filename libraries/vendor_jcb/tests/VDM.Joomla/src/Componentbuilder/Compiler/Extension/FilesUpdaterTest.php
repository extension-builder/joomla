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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Extension;


use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Extension\FileContent;
use VDM\Joomla\Componentbuilder\Compiler\Extension\Files\Dynamic;
use VDM\Joomla\Componentbuilder\Compiler\Extension\Files\Module;
use VDM\Joomla\Componentbuilder\Compiler\Extension\Files\Plugin;
use VDM\Joomla\Componentbuilder\Compiler\Extension\Files\Power as ExtensionPower;
use VDM\Joomla\Componentbuilder\Compiler\Extension\Files\StaticFiles;
use VDM\Joomla\Componentbuilder\Compiler\Extension\Files\Updater;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\ModuleDataInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\MoveFieldsRulesInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PluginDataInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Power\ExtractorInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector as JoomlaPowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Power as CompilerPower;
use VDM\Joomla\Componentbuilder\Compiler\Power\Autoloader;
use VDM\Joomla\Componentbuilder\Compiler\Power\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\Power\Infusion;
use VDM\Joomla\Componentbuilder\Compiler\Power\Injector as PowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Power\Structure as PowerStructure;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Files;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Interfaces\Readme\ItemInterface;
use VDM\Joomla\Interfaces\Readme\MainInterface;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Extension file-bucket updater contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Dynamic::class)]
#[CoversClass(Module::class)]
#[CoversClass(Plugin::class)]
#[CoversClass(ExtensionPower::class)]
#[CoversClass(StaticFiles::class)]
#[CoversClass(Updater::class)]
#[UsesClass(FileContent::class)]
#[UsesClass(Files::class)]
#[UsesClass(ContentOne::class)]
#[UsesClass(ContentMulti::class)]
#[UsesClass(Placeholder::class)]
#[UsesClass(Infusion::class)]
final class FilesUpdaterTest extends CompilerDomainTestCase
{
	/**
	 * Static/autoloader partitioning and dynamic view replacement select the correct files.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStaticAndDynamicUpdatersSelectTheirOwnedFiles(): void
	{
		$regular = null;
		$loader = null;
		$dynamic = null;
		$writes = [];

		try
		{
			$regular = $this->temporaryFile('static ###FILENAME###');
			$loader = $this->temporaryFile('loader ###FILENAME###');
			$dynamic = $this->temporaryFile('dynamic ###TITLE###');

			$files = new Files();
			$files->set('static', [
				['name' => 'Regular.php', 'path' => $regular],
				['name' => 'PowerLoader.php', 'path' => $loader]
			]);
			$files->set('dynamic', [
				'article' => [
					['name' => 'Article.php', 'path' => $dynamic, 'view' => 'article'],
					['name' => 'Wrong.php', 'path' => $dynamic, 'view' => 'other']
				]
			]);
			$contents = new ContentMulti();
			$contents->set('article|TITLE', 'Article title');
			$fileContent = $this->recordingFileContent($contents, $writes);
			$static = new StaticFiles($files, $fileContent);
			$dynamicUpdater = new Dynamic($files, $contents, $fileContent);

			$static->update('// header');
			$this->assertCount(1, $writes);
			$this->assertSame($regular, $writes[0][0]);
			$this->assertStringContainsString('static Regular.php', $writes[0][1]);

			$dynamicUpdater->update('// header');
			$this->assertCount(2, $writes);
			$this->assertSame($dynamic, $writes[1][0]);
			$this->assertStringContainsString('dynamic Article title', $writes[1][1]);

			$static->autoloader('// header');
			$this->assertCount(3, $writes);
			$this->assertSame($loader, $writes[2][0]);
			$this->assertStringContainsString('loader PowerLoader.php', $writes[2][1]);
		}
		finally
		{
			$this->removeTemporaryFiles([$regular, $loader, $dynamic]);
		}
	}

	/**
	 * Module and plugin updaters relocate nested fields and consume their isolated buckets.
	 *
	 * @param   class-string<Module|Plugin>                     $class          Updater class.
	 * @param   class-string<ModuleDataInterface|PluginDataInterface>  $dataInterface  Data contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('extensionUpdaterProvider')]
	public function testExtensionUpdaterMovesFieldsAndConsumesBucket(
		string $class,
		string $dataInterface
	): void
	{
		$key = 'extension_key';
		$extension = (object) [
			'key' => $key,
			'folder_path' => '/generated/extension',
			'fields_rules_paths' => 2,
			'config_fields' => [
				'params' => ['basic' => [['id' => 7, 'type_name' => 'text']]]
			],
			'form_files' => [
				'config.xml' => ['params' => ['advanced' => [['id' => 8, 'type_name' => 'list']]]]
			]
		];
		$data = $this->createMock($dataInterface);
		$data->expects($this->once())->method('exists')->willReturn(true);
		$data->expects($this->once())->method('get')->willReturn([$extension]);
		$moved = [];
		$move = $this->createMock(MoveFieldsRulesInterface::class);
		$move->expects($this->exactly(2))
			->method('move')
			->willReturnCallback(static function (array $field, string $path) use (&$moved): void
			{
				$moved[] = [$field['id'], $path];
			});
		$files = new Files();
		$files->set($key, [['name' => 'missing.php', 'path' => '/not/a/file']]);
		$contents = new ContentMulti();
		$contents->set($key . '|TOKEN', 'value');
		$subject = new $class(
			$data,
			$move,
			$this->inertCompilerCollaborator(FileContent::class),
			$contents,
			$files
		);

		$subject->update('// header');

		$this->assertSame([
			[7, '/generated/extension'],
			[8, '/generated/extension']
		], $moved);
		$this->assertFalse($files->exists($key));
		$this->assertFalse($contents->exists($key));
	}

	/**
	 * The power updater reloads extracted powers and always rebuilds/infuses before file walking.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPowerUpdaterReloadsAndPreparesPowerState(): void
	{
		$config = $this->compilerConfig(['add_super_powers' => false]);
		$compilerPower = $this->createMock(CompilerPower::class);
		$compilerPower->expects($this->once())->method('load')->with(['power-guid']);
		$extractor = $this->createMock(Extractor::class);
		$extractor->expects($this->once())->method('get_')->willReturn(['power-guid']);
		$structure = $this->createMock(PowerStructure::class);
		$structure->expects($this->once())->method('build');
		$contents = new ContentMulti();
		$subject = new ExtensionPower(
			$compilerPower,
			$extractor,
			$structure,
			$this->powerInfusion($config, $compilerPower, $contents),
			$this->inertCompilerCollaborator(FileContent::class),
			new Files(),
			$contents
		);

		$subject->update('// header');

		$this->assertSame([], $compilerPower->active);
		$this->assertSame([], $compilerPower->superpowers);
	}

	/**
	 * The aggregate updater performs one complete no-file-I/O pass and consumes dynamic state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAggregateUpdaterRunsCompletePassWhenCoreBucketsExist(): void
	{
		$bomPath = null;

		try
		{
			$bomPath = $this->temporaryFile('// shared header');
			$config = $this->compilerConfig([
				'bom_path' => $bomPath,
				'add_super_powers' => false
			]);
			$compilerPower = $this->createMock(CompilerPower::class);
			$compilerPower->expects($this->never())->method('load');
			$extractor = $this->createMock(Extractor::class);
			$extractor->expects($this->exactly(2))->method('get_')->willReturn(null);
			$autoloader = $this->createMock(Autoloader::class);
			$autoloader->expects($this->once())->method('set');
			$files = new Files();
			$files->set('static', [['name' => 'missing.php', 'path' => '/not/a/file']]);
			$files->set('dynamic', [
				'missing' => [['name' => 'missing.php', 'path' => '/not/a/file', 'view' => 'missing']]
			]);
			$contents = new ContentMulti();
			$fileContent = $this->inertCompilerCollaborator(FileContent::class);
			$static = new StaticFiles($files, $fileContent);
			$dynamic = new Dynamic($files, $contents, $fileContent);
			$move = $this->createStub(MoveFieldsRulesInterface::class);
			$moduleData = $this->createStub(ModuleDataInterface::class);
			$moduleData->method('exists')->willReturn(false);
			$pluginData = $this->createStub(PluginDataInterface::class);
			$pluginData->method('exists')->willReturn(false);
			$module = new Module($moduleData, $move, $fileContent, $contents, $files);
			$plugin = new Plugin($pluginData, $move, $fileContent, $contents, $files);
			$structure = $this->createMock(PowerStructure::class);
			$structure->expects($this->once())->method('build');
			$power = new ExtensionPower(
				$compilerPower,
				$extractor,
				$structure,
				$this->powerInfusion($config, $compilerPower, $contents),
				$fileContent,
				$files,
				$contents
			);
			$subject = new Updater(
				$config,
				$compilerPower,
				$extractor,
				$autoloader,
				$files,
				$static,
				$dynamic,
				$module,
				$plugin,
				$power
			);

			$this->assertTrue($subject->update());
			$this->assertFalse($files->exists('dynamic'));
			$this->assertTrue($files->exists('static'));
		}
		finally
		{
			$this->removeTemporaryFiles([$bomPath]);
		}
	}

	/**
	 * Missing either core bucket aborts before extraction and returns false.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAggregateUpdaterRejectsIncompleteCoreBuckets(): void
	{
		$files = new Files();
		$files->set('static', [['name' => 'one.php', 'path' => '/not/a/file']]);
		$extractor = $this->createMock(Extractor::class);
		$extractor->expects($this->never())->method('get_');
		$subject = new Updater(
			$this->compilerConfig(),
			$this->createStub(CompilerPower::class),
			$extractor,
			$this->createStub(Autoloader::class),
			$files,
			$this->inertCompilerCollaborator(StaticFiles::class),
			$this->inertCompilerCollaborator(Dynamic::class),
			$this->inertCompilerCollaborator(Module::class),
			$this->inertCompilerCollaborator(Plugin::class),
			$this->inertCompilerCollaborator(ExtensionPower::class)
		);

		$this->assertFalse($subject->update());
	}

	/**
	 * Module/plugin updater matrix.
	 *
	 * @return  Generator<string, array{class-string<Module|Plugin>, class-string<ModuleDataInterface|PluginDataInterface>}>
	 * @since   6.1.6
	 */
	public static function extensionUpdaterProvider(): Generator
	{
		yield 'module' => [Module::class, ModuleDataInterface::class];
		yield 'plugin' => [Plugin::class, PluginDataInterface::class];
	}

	/**
	 * Build a file transformer whose write boundary records output without changing disk.
	 *
	 * @param   ContentMulti                         $contents  View placeholders.
	 * @param   array<int, array{string, string}>   &$writes   Recorded writes.
	 *
	 * @return  FileContent
	 * @since   6.1.6
	 */
	private function recordingFileContent(ContentMulti $contents, array &$writes): FileContent
	{
		$placeholder = new Placeholder($this->compilerConfig());
		$extractor = $this->createStub(ExtractorInterface::class);
		$extractor->method('get')->willReturn(null);
		$powerInjector = $this->createStub(PowerInjector::class);
		$powerInjector->method('power')->willReturnArgument(0);
		$file = $this->createStub(File::class);
		$file->method('write')
			->willReturnCallback(static function (string $path, string $data) use (&$writes): bool
			{
				$writes[] = [$path, $data];
				return true;
			});

		return new FileContent(
			new Registry(),
			$placeholder,
			$this->createStub(Customcode::class),
			$this->createStub(EventInterface::class),
			$powerInjector,
			new JoomlaPowerInjector(
				$this->createStub(PowerInterface::class),
				$extractor,
				$this->inertCompilerCollaborator(Parser::class),
				$placeholder
			),
			new ContentOne(),
			$contents,
			$file,
			$this->inertCompilerCollaborator(Counter::class)
		);
	}

	/**
	 * Construct an infusion collaborator which is observably inert for disabled empty power state.
	 *
	 * @param   \VDM\Joomla\Componentbuilder\Compiler\Config  $config         Compiler configuration.
	 * @param   CompilerPower                                    $compilerPower  Power state.
	 * @param   ContentMulti                                     $contents       Power placeholders.
	 *
	 * @return  Infusion
	 * @since   6.1.6
	 */
	private function powerInfusion(
		\VDM\Joomla\Componentbuilder\Compiler\Config $config,
		CompilerPower $compilerPower,
		ContentMulti $contents
	): Infusion
	{
		return new Infusion(
			$config,
			$compilerPower,
			new ContentOne(),
			$contents,
			$this->inertCompilerCollaborator(Parser::class),
			$this->createStub(ItemInterface::class),
			$this->createStub(MainInterface::class),
			new Placeholder($config),
			$this->createStub(EventInterface::class)
		);
	}

	/**
	 * Create a temporary source file.
	 *
	 * @param   string  $content  Source content.
	 *
	 * @return  string
	 * @since   6.1.6
	 */
	private function temporaryFile(string $content): string
	{
		$path = null;
		$complete = false;

		try
		{
			$temporaryFile = tempnam(sys_get_temp_dir(), 'jcb-updater-');

			if ($temporaryFile === false)
			{
				throw new RuntimeException('Unable to create the updater fixture.');
			}

			$path = $temporaryFile;

			if (file_put_contents($path, $content) === false)
			{
				throw new RuntimeException('Unable to write the updater fixture: ' . $path);
			}

			$complete = true;

			return $path;
		}
		finally
		{
			if (!$complete)
			{
				$this->removeTemporaryFiles([$path]);
			}
		}
	}

	/**
	 * Remove exact temporary files owned by the current test.
	 *
	 * Every candidate is attempted before a cleanup failure is reported.
	 *
	 * @param   array<int, string|null>  $paths  Temporary file paths.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function removeTemporaryFiles(array $paths): void
	{
		$failures = [];

		foreach ($paths as $path)
		{
			if ($path !== null && (is_link($path) || is_file($path)) && !unlink($path))
			{
				$failures[] = $path;
			}
			elseif ($path !== null && file_exists($path))
			{
				$failures[] = $path;
			}
		}

		if ($failures !== [])
		{
			throw new RuntimeException(
				'Unable to remove updater fixtures: ' . implode(', ', $failures)
			);
		}
	}
}
