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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Utilities;


use FilesystemIterator;
use Joomla\CMS\Application\CMSApplicationInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data as ComponentData;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Component\SettingsInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Power\ExtractorInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector as JoomlaPowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Power\Injector as PowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\ComplexityEngine;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\FileInjector;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Files;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Valuation;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Compiler filesystem, path, counter, complexity, and valuation contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(ComplexityEngine::class)]
#[CoversClass(Counter::class)]
#[CoversClass(FileInjector::class)]
#[CoversClass(Files::class)]
#[CoversClass(Paths::class)]
#[CoversClass(Structure::class)]
#[CoversClass(Valuation::class)]
final class UtilitiesPipelineTest extends CompilerDomainTestCase
{
	/**
	 * Paths derive the complete build identity once from Config and Component state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPathsInitializeCompleteBuildIdentityOnlyOnce(): void
	{
		$root = '/tmp/jcb-compiler-contract';
		$config = $this->compilerConfig([
			'compiler_path' => $root,
			'custom_folder_path' => $root . '/custom',
			'joomla_version' => 5,
		]);
		$component = $this->componentRegistry();
		$component->set('sales_name', 'demo-sales');
		$component->set('name_code', 'demo');
		$component->set('component_version', '2.3.4');
		$subject = new Paths($config, $component);

		$this->assertSame($root . '/joomla_4', $subject->template_path);
		$this->assertSame('com_demo-sales__J5', $subject->component_sales_name);
		$this->assertSame('com_demo-sales_v2_3_4__J5', $subject->component_backup_name);
		$this->assertSame('com_demo_v2_3_4__J5', $subject->component_folder_name);
		$this->assertSame($root . '/com_demo_v2_3_4__J5', $subject->component_path);
		$this->assertSame($root . '/custom', $subject->template_path_custom);

		$component->set('component_version', '9.9.9');
		$this->assertSame('com_demo_v2_3_4__J5', $subject->component_folder_name);

		$this->expectException(\InvalidArgumentException::class);
		$subject->missing_path;
	}

	/**
	 * Complexity caps at the configured revolutionary tier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testComplexityMapsBoundaryWorkloadsToConfiguredTiers(): void
	{
		$config = $this->compilerConfig([
			'market_multiplier_low' => 0.8,
			'market_multiplier_medium' => 1.1,
			'market_multiplier_high' => 2.2,
			'market_multiplier_revolutionary' => 5.0,
		]);
		$subject = new ComplexityEngine($config);
		$counter = $this->counterWithoutConstructor();
		$counter->projectStart = time() + 86400;

		foreach ([
			'adminView' => 10,
			'field' => 500,
			'customAdminView' => 5,
			'siteView' => 10,
			'layout' => 20,
			'template' => 25,
			'module' => 1,
			'plugin' => 2,
			'dynamicGet' => 15,
			'power' => 100,
			'customCodeBlock' => 50,
			'accessSize' => 2500,
		] as $property => $value)
		{
			$counter->{$property} = $value;
		}

		$this->assertSame(
			['complexity_index' => 0.98, 'complexity_multiplier' => 4.86],
			$subject->get($counter)
		);
	}

	/**
	 * Zero complexity must resolve to the configured low multiplier.
	 *
	 * The production comparison casts BCMath's result to float before using a
	 * strict integer comparison, which selects the revolutionary interpolation
	 * branch and produces a negative multiplier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testZeroComplexityUsesConfiguredLowMultiplier(): void
	{
		$config = $this->compilerConfig([
			'market_multiplier_low' => 0.8,
			'market_multiplier_medium' => 1.1,
			'market_multiplier_high' => 2.2,
			'market_multiplier_revolutionary' => 5.0,
		]);
		$counter = $this->counterWithoutConstructor();
		$counter->projectStart = time() + 86400;

		$this->assertSame(
			['complexity_index' => 0.0, 'complexity_multiplier' => 0.8],
			(new ComplexityEngine($config))->get($counter)
		);
	}

	/**
	 * Counter timing is idempotent and unknown names return the caller default.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCounterProtectsTimerStateAndTypedCounters(): void
	{
		$config = $this->compilerConfig();
		$content = new ContentOne();
		$valuation = new Valuation($config, $content, new ComplexityEngine($config));
		$counter = new Counter($valuation);
		$start = (new ReflectionProperty(Counter::class, 'start'))->getValue($counter);
		$counter->start();

		$this->assertSame($start, (new ReflectionProperty(Counter::class, 'start'))->getValue($counter));
		$counter->field = 12;
		$this->assertSame(12, $counter->get('field'));
		$this->assertSame('fallback', $counter->get('unknown', 'fallback'));

		$counter->end();
		$this->assertSame(2, (new ReflectionProperty(Counter::class, 'started'))->getValue($counter));
		$this->assertGreaterThanOrEqual($start, (new ReflectionProperty(Counter::class, 'end'))->getValue($counter));
	}

	/**
	 * Valuation writes legacy counters and the extended financial model together.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValuationStoresLegacyAndExtendedResultsInOnePass(): void
	{
		$config = $this->compilerConfig([
			'avg_seconds_per_folder' => 2,
			'avg_seconds_per_file' => 3,
			'avg_seconds_per_line' => 4,
			'avg_lines_per_page' => 50,
			'avg_hourly_rate_usd' => 100,
		]);
		$content = new ContentOne();
		$valuation = new Valuation($config, $content, new ComplexityEngine($config));
		$counter = new Counter($valuation);
		$counter->folder = 10;
		$counter->file = 20;
		$counter->line = 500;
		$counter->field = 25;
		$counter->adminView = 2;
		$counter->customAdminView = 5;
		$counter->siteView = 10;
		$counter->layout = 20;
		$counter->template = 25;
		$counter->module = 1;
		$counter->plugin = 2;
		$counter->dynamicGet = 15;
		$counter->power = 100;
		$counter->customCodeBlock = 50;
		$counter->accessSize = 2500;
		$counter->projectStart = time() + 86400;
		$counter->end();

		$valuation->set($counter);

		$this->assertSame(500, $content->get('LINE_COUNT'));
		$this->assertSame(25, $content->get('FIELD_COUNT'));
		$this->assertSame(10, $content->get('PAGE_COUNT'));
		$this->assertSame($content->get('seconds'), $content->get('actualSeconds'));
		$this->assertIsFloat($content->get('COMPLEXITY_INDEX'));
		$this->assertMatchesRegularExpression('/^\$ [0-9,.]+ \(USD\)$/', $content->get('PROJECT_VALUE_USD'));
		$this->assertMatchesRegularExpression('/^\$ [0-9,.]+ \(USD\)$/', $content->get('BLUEPRINT_VALUE'));
		$this->assertSame(100, $content->get('SUBSCRIPTION_PER_MONTH'));
	}

	/**
	 * File injection supports insertion, byte replacement, and negative-offset rejection.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFileInjectorPreservesUnreplacedBytesAndValidatesOffsets(): void
	{
		$file = null;

		try
		{
			$temporaryFile = tempnam(sys_get_temp_dir(), 'jcb-inject-');

			if ($temporaryFile === false)
			{
				throw new RuntimeException('Unable to create the file-injector fixture.');
			}

			$file = $temporaryFile;

			if (file_put_contents($file, 'abcdef') === false)
			{
				throw new RuntimeException('Unable to write the file-injector fixture: ' . $file);
			}

			$config = $this->compilerConfig();
			$extractor = $this->createStub(ExtractorInterface::class);
			$power = $this->createStub(PowerInterface::class);
			$parser = new Parser();
			$placeholder = new Placeholder($config);
			$subject = new FileInjector(
				new PowerInjector($power, $extractor, $parser, $placeholder),
				new JoomlaPowerInjector($power, $extractor, $parser, $placeholder)
			);

			$subject->add($file, 'XY', 2);
			$this->assertSame('abXYcdef', file_get_contents($file));

			if (file_put_contents($file, 'abcdef') === false)
			{
				throw new RuntimeException('Unable to reset the file-injector fixture: ' . $file);
			}

			$subject->add($file, 'XY', 2, 2);
			$this->assertSame('abXYef', file_get_contents($file));

			try
			{
				$subject->add($file, 'bad', -1);
				$this->fail('A negative offset must be rejected.');
			}
			catch (\InvalidArgumentException $error)
			{
				$this->assertSame('Position cannot be negative.', $error->getMessage());
			}
		}
		finally
		{
			if ($file !== null && (is_link($file) || is_file($file)) && !unlink($file))
			{
				throw new RuntimeException('Unable to remove the file-injector fixture: ' . $file);
			}

			if ($file !== null && file_exists($file))
			{
				throw new RuntimeException('The file-injector fixture is not a file: ' . $file);
			}
		}
	}

	/**
	 * Structure copies matching templates and records the generated file contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStructureBuildsMatchingTemplateAndClearsTemporaryPlaceholders(): void
	{
		$root = sys_get_temp_dir() . '/jcb-structure-' . bin2hex(random_bytes(5));
		$template = $root . '/templates';
		$rootCreated = false;

		try
		{
			if (!mkdir($root, 0700))
			{
				throw new RuntimeException('Unable to create the structure fixture: ' . $root);
			}

			$rootCreated = true;

			if (!mkdir($template, 0700))
			{
				throw new RuntimeException('Unable to create the structure template fixture: ' . $template);
			}

			if (file_put_contents($template . '/template.php', 'template-content') === false)
			{
				throw new RuntimeException('Unable to write the structure template fixture.');
			}

			$config = $this->compilerConfig();
			$placeholder = new Placeholder($config);
			$settings = $this->createMock(SettingsInterface::class);
			$settings->expects($this->once())->method('multiple')->willReturn((object) [
				'main' => (object) [
					'template.php' => (object) [
						'type' => 'view',
						'path' => 'c0mp0n3nt/admin/VIEW',
						'rename' => 'template',
					],
				],
			]);
			$paths = new class($root, $template) extends Paths
			{
				/**
				 * Component output root.
				 *
				 * @var    string
				 * @since  6.1.6
				 */
				private string $root;

				/**
				 * Template source root.
				 *
				 * @var    string
				 * @since  6.1.6
				 */
				private string $template;

				/**
				 * Create an isolated component/template path fixture.
				 *
				 * @param   string  $root      Component output root.
				 * @param   string  $template  Template source root.
				 *
				 * @since   6.1.6
				 */
				public function __construct(string $root, string $template)
				{
					$this->root = $root;
					$this->template = $template;
				}

				/**
				 * Resolve the requested compiler path.
				 *
				 * @param   string  $key  Requested path key.
				 *
				 * @return  string  Resolved path.
				 *
				 * @throws  \InvalidArgumentException  If the path key is unsupported.
				 * @since   6.1.6
				 */
				public function __get($key)
				{
					return match ($key)
					{
						'component_path' => $this->root,
						'template_path' => $this->template,
						default => throw new \InvalidArgumentException((string) $key),
					};
				}
			};
			$counter = $this->counterWithoutConstructor();
			$file = $this->createMock(File::class);
			$file->expects($this->once())->method('html')->with('admin/article');
			$files = new Files();
			$subject = new Structure(
				$placeholder,
				$settings,
				$paths,
				$counter,
				$file,
				$files,
				$this->createStub(CMSApplicationInterface::class)
			);

			$this->assertTrue($subject->build(['main' => 'Article'], 'view', null, ['client' => 'admin']));
			$this->assertSame('template-content', file_get_contents($root . '/admin/article/article.php'));
			$this->assertSame('article.php', $files->get('dynamic.article.0.name'));
			$this->assertSame(['client' => 'admin'], $files->get('dynamic.article.0.config'));
			$this->assertFalse($placeholder->exist('Name'));
			$this->assertFalse($placeholder->exist('Key'));
			$this->assertSame(1, $counter->folder);
			$this->assertSame(1, $counter->file);
		}
		finally
		{
			if ($rootCreated)
			{
				$this->removeTemporaryDirectory($root);
			}
		}
	}

	/**
	 * Recursively remove one test-owned directory without following links.
	 *
	 * @param   string  $directory  Test-owned directory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function removeTemporaryDirectory(string $directory): void
	{
		if (is_link($directory) || is_file($directory))
		{
			if (!unlink($directory))
			{
				throw new RuntimeException('Unable to remove the structure fixture path: ' . $directory);
			}

			return;
		}

		if (!file_exists($directory))
		{
			return;
		}

		if (!is_dir($directory))
		{
			throw new RuntimeException('The structure fixture is not a directory: ' . $directory);
		}

		$iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

		foreach ($iterator as $item)
		{
			$path = $item->getPathname();

			if ($item->isLink() || !$item->isDir())
			{
				if (!unlink($path))
				{
					throw new RuntimeException('Unable to remove the structure fixture file: ' . $path);
				}

				continue;
			}

			$this->removeTemporaryDirectory($path);
		}

		unset($iterator);

		if (!rmdir($directory))
		{
			throw new RuntimeException('Unable to remove the structure fixture directory: ' . $directory);
		}
	}

	/**
	 * Create a usable Component registry without querying definitions.
	 *
	 * @return  Component
	 * @since   6.1.6
	 */
	private function componentRegistry(): Component
	{
		$data = (new ReflectionClass(ComponentData::class))->newInstanceWithoutConstructor();

		return new Component($data, $this->createStub(EventInterface::class));
	}

	/**
	 * Create Counter state without triggering its timer/valuation collaboration.
	 *
	 * @return  Counter
	 * @since   6.1.6
	 */
	private function counterWithoutConstructor(): Counter
	{
		return (new ReflectionClass(Counter::class))->newInstanceWithoutConstructor();
	}
}
