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
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaFive\InstallScript as JoomlaFiveInstallScript;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaFour\InstallScript as JoomlaFourInstallScript;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaSix\InstallScript as JoomlaSixInstallScript;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaThree\InstallScript as JoomlaThreeInstallScript;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Extension\InstallInterface;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Joomla-version installation-script generation contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeInstallScript::class)]
#[CoversClass(JoomlaFourInstallScript::class)]
#[CoversClass(JoomlaFiveInstallScript::class)]
#[CoversClass(JoomlaSixInstallScript::class)]
final class InstallScriptTest extends CompilerDomainTestCase
{
	/**
	 * Every target emits its minimum-version guard and routes custom lifecycle code.
	 *
	 * @param   class-string<InstallInterface>  $class          Generator class.
	 * @param   string                          $minimumVersion Minimum supported Joomla version.
	 * @param   bool                            $modernCleanup  Whether modern removal support is expected.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versionProvider')]
	public function testLifecycleCodeIsRenderedForEachTarget(
		string $class,
		string $minimumVersion,
		bool $modernCleanup
	): void
	{
		$extension = (object) [
			'official_name' => 'Example Extension',
			'class_name' => 'Example',
			'installer_class_name' => 'Com_ExampleInstallerScript',
			'add_php_script_install' => 1,
			'php_script_install' => "\t\t// install-body",
			'add_php_preflight_update' => 1,
			'php_preflight_update' => "\t\t// update-preflight",
			'add_php_postflight_install' => 1,
			'php_postflight_install' => "\t\t// install-postflight",
			'remove_file_paths' => ['/administrator/obsolete.php'],
			'remove_folder_paths' => ['/administrator/obsolete']
		];
		$subject = new $class();

		$script = $subject->get($extension);

		$this->assertStringContainsString('class Com_ExampleInstallerScript', $script);
		$this->assertStringContainsString('public function install($adapter)', $script);
		$this->assertStringContainsString('// install-body', $script);
		$this->assertStringContainsString("isCompatible('{$minimumVersion}')", $script);
		$this->assertStringContainsString("if ('update' === \$route)", $script);
		$this->assertStringContainsString('// update-preflight', $script);
		$this->assertStringContainsString("if ('install' === \$route)", $script);
		$this->assertStringContainsString('// install-postflight', $script);
		$this->assertSame(
			$modernCleanup,
			str_contains($script, "\$this->deleteFiles[] = '/administrator/obsolete.php';")
		);
		$this->assertSame(
			$modernCleanup,
			str_contains($script, 'protected function removeFiles()')
		);
	}

	/**
	 * A generator instance must not leak lifecycle buckets between extensions.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepeatedBuildResetsPreviouslyCollectedCode(): void
	{
		$subject = new JoomlaFourInstallScript();
		$first = (object) [
			'official_name' => 'First',
			'class_name' => 'First',
			'installer_class_name' => 'FirstInstallerScript',
			'add_php_script_install' => 1,
			'php_script_install' => "\t\t// first-only"
		];
		$second = (object) [
			'official_name' => 'Second',
			'class_name' => 'Second',
			'installer_class_name' => 'SecondInstallerScript'
		];

		$this->assertStringContainsString('// first-only', $subject->get($first));
		$script = $subject->get($second);

		$this->assertStringNotContainsString('// first-only', $script);
		$this->assertStringNotContainsString('public function install($adapter)', $script);
		$this->assertStringContainsString('class SecondInstallerScript', $script);
	}

	/**
	 * Joomla target matrix.
	 *
	 * @return  Generator<string, array{class-string<InstallInterface>, string, bool}>
	 * @since   6.1.6
	 */
	public static function versionProvider(): Generator
	{
		yield 'Joomla 3' => [JoomlaThreeInstallScript::class, '3.8.0', false];
		yield 'Joomla 4' => [JoomlaFourInstallScript::class, '4.0.0', false];
		yield 'Joomla 5' => [JoomlaFiveInstallScript::class, '5.0.0', true];
		yield 'Joomla 6' => [JoomlaSixInstallScript::class, '5.0.0', true];
	}
}
