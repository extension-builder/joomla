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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Component;


use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Component\JoomlaFive\Settings as JoomlaFiveSettings;
use VDM\Joomla\Componentbuilder\Compiler\Component\JoomlaFour\Settings as JoomlaFourSettings;
use VDM\Joomla\Componentbuilder\Compiler\Component\JoomlaSix\Settings as JoomlaSixSettings;
use VDM\Joomla\Componentbuilder\Compiler\Component\JoomlaThree\Settings as JoomlaThreeSettings;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Component\SettingsInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Dynamicpath;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Pathfix;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Joomla-version component template-settings contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeSettings::class)]
#[CoversClass(JoomlaFourSettings::class)]
#[CoversClass(JoomlaFiveSettings::class)]
#[CoversClass(JoomlaSixSettings::class)]
#[UsesClass(Component::class)]
#[UsesClass(Registry::class)]
final class SettingsTest extends CompilerDomainTestCase
{
	/**
	 * Every target loads its template manifest, caches it, and exposes the same settings contract.
	 *
	 * @param   class-string<SettingsInterface>  $class  Target settings class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('targetProvider')]
	public function testTargetLoadsValidTemplateSettingsOnce(string $class): void
	{
		$directory = sys_get_temp_dir() . '/jcb-settings-' . bin2hex(random_bytes(6));
		$directoryCreated = false;

		try
		{
			if (!mkdir($directory, 0700))
			{
				throw new RuntimeException('Unable to create the settings fixture: ' . $directory);
			}

			$directoryCreated = true;
			$settings = json_encode([
				'create' => ['administrator' => ['src' => new \stdClass()]],
				'move' => [
					'static' => ['license' => ['naam' => 'LICENSE.txt']],
					'dynamic' => ['admin' => ['single' => new \stdClass()]]
				]
			]);

			if (!is_string($settings)
				|| file_put_contents($directory . '/settings.json', $settings) === false)
			{
				throw new RuntimeException('Unable to write the settings fixture: ' . $directory);
			}

			$config = $this->compilerConfig(['component_code_name' => 'example']);
			$registry = new Registry();
			$event = $this->createMock(EventInterface::class);
			$event->expects($this->once())
				->method('trigger')
				->with('jcb_ce_onAfterSetJoomlaVersionData', $this->isArray());
			$component = $this->component();
			$component->set('name_code', 'example');
			$paths = $this->createStub(Paths::class);
			$paths->method('__get')->willReturnCallback(
				static fn(string $name): string => $name === 'template_path' ? $directory : ''
			);
			$subject = new $class(
				$config,
				$registry,
				$event,
				new Placeholder($config),
				$component,
				$paths,
				$this->createStub(Dynamicpath::class),
				$this->createStub(Pathfix::class)
			);

			$this->assertTrue($subject->exists());
			$this->assertTrue($subject->exists());
			$this->assertObjectHasProperty('administrator', $subject->structure());
			$this->assertObjectHasProperty('license', $subject->single());
			$this->assertObjectHasProperty('admin', $subject->multiple());
			$this->assertTrue($subject->standardFolder('admin'));
			$this->assertFalse($subject->standardFolder('api'));
			$this->assertTrue($subject->standardRootFile('access.xml'));
			$this->assertTrue($subject->standardRootFile('example.php'));
			$this->assertFalse($subject->standardRootFile('unknown.php'));
		}
		finally
		{
			if ($directoryCreated)
			{
				$this->removeSettingsFixture($directory);
			}
		}
	}

	/**
	 * Invalid template JSON cannot activate settings or trigger the loaded event.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInvalidTemplateSettingsRemainUnavailable(): void
	{
		$directory = sys_get_temp_dir() . '/jcb-settings-' . bin2hex(random_bytes(6));
		$directoryCreated = false;

		try
		{
			if (!mkdir($directory, 0700))
			{
				throw new RuntimeException('Unable to create the invalid-settings fixture: ' . $directory);
			}

			$directoryCreated = true;

			if (file_put_contents($directory . '/settings.json', '{invalid-json') === false)
			{
				throw new RuntimeException('Unable to write the invalid-settings fixture: ' . $directory);
			}

			$config = $this->compilerConfig(['component_code_name' => 'example']);
			$event = $this->createMock(EventInterface::class);
			$event->expects($this->never())->method('trigger');
			$component = $this->component();
			$component->set('name_code', 'example');
			$paths = $this->createStub(Paths::class);
			$paths->method('__get')->willReturn($directory);
			$subject = new JoomlaSixSettings(
				$config,
				new Registry(),
				$event,
				new Placeholder($config),
				$component,
				$paths,
				$this->createStub(Dynamicpath::class),
				$this->createStub(Pathfix::class)
			);

			$this->assertFalse($subject->exists());
		}
		finally
		{
			if ($directoryCreated)
			{
				$this->removeSettingsFixture($directory);
			}
		}
	}

	/**
	 * Remove one exact settings fixture owned by the current test.
	 *
	 * @param   string  $directory  Fixture directory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function removeSettingsFixture(string $directory): void
	{
		$file = $directory . '/settings.json';

		if ((is_link($file) || is_file($file)) && !unlink($file))
		{
			throw new RuntimeException('Unable to remove the settings fixture file: ' . $file);
		}

		if (file_exists($file))
		{
			throw new RuntimeException('The settings fixture is not a file: ' . $file);
		}

		if (is_link($directory))
		{
			if (!unlink($directory))
			{
				throw new RuntimeException('Unable to remove the settings fixture path: ' . $directory);
			}

			return;
		}

		if (is_dir($directory) && !rmdir($directory))
		{
			throw new RuntimeException('Unable to remove the settings fixture directory: ' . $directory);
		}

		if (file_exists($directory))
		{
			throw new RuntimeException('The settings fixture is not a directory: ' . $directory);
		}
	}

	/**
	 * Settings implementation matrix.
	 *
	 * @return  Generator<string, array{class-string<SettingsInterface>}>
	 * @since   6.1.6
	 */
	public static function targetProvider(): Generator
	{
		yield 'Joomla 3' => [JoomlaThreeSettings::class];
		yield 'Joomla 4' => [JoomlaFourSettings::class];
		yield 'Joomla 5' => [JoomlaFiveSettings::class];
		yield 'Joomla 6' => [JoomlaSixSettings::class];
	}

	/**
	 * Build a mutable component registry without database modelling.
	 *
	 * @return  Component
	 * @since   6.1.6
	 */
	private function component(): Component
	{
		return new Component(
			$this->inertCompilerCollaborator(Data::class),
			$this->createStub(EventInterface::class)
		);
	}
}
