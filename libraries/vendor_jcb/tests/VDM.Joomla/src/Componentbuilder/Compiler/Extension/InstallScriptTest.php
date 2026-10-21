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
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Installer\LegacyInstallerScript;
use Joomla\CMS\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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
	 * A hook-free Joomla 6 script returns a native instance with successful no-op methods.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testJoomlaSixReturnsNativeInstallerWithEveryLifecycleMethod(): void
	{
		define('JVERSION', (new Version())->getShortVersion());
		$this->setJoomlaApplication($this->createStub(CMSApplicationInterface::class));
		$script = (new JoomlaSixInstallScript())->get((object) [
			'official_name' => 'Native Module',
			'class_name' => 'NativeModule',
			'installer_class_name' => 'ModNativeLifecycleInstallerScript',
		]);

		$installer = eval($script);
		$adapter = $this->createStub(InstallerAdapter::class);

		$this->assertInstanceOf(InstallerScriptInterface::class, $installer);
		$this->assertNotInstanceOf(LegacyInstallerScript::class, $installer);
		$this->assertTrue($installer->install($adapter));
		$this->assertTrue($installer->update($adapter));
		$this->assertTrue($installer->uninstall($adapter));

		foreach (['install', 'update', 'uninstall', 'discover_install'] as $route)
		{
			$this->assertTrue($installer->preflight($route, $adapter));
			$this->assertTrue($installer->postflight($route, $adapter));
		}
	}

	/**
	 * Disabled and empty custom hooks do not suppress the native Joomla 6 contract.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testDisabledAndEmptyHooksStillProduceNativeInstaller(): void
	{
		define('JVERSION', (new Version())->getShortVersion());
		$this->setJoomlaApplication($this->createStub(CMSApplicationInterface::class));
		$script = (new JoomlaSixInstallScript())->get((object) [
			'official_name' => 'Native Plugin',
			'class_name' => 'NativePlugin',
			'installer_class_name' => 'PlgNativeEmptyHooksInstallerScript',
			'add_php_script_construct' => 0,
			'php_script_construct' => 'throw new \\RuntimeException("Disabled constructor");',
			'add_php_script_install' => 0,
			'php_script_install' => 'return false;',
			'add_php_preflight_update' => 1,
			'php_preflight_update' => '',
			'add_php_postflight_uninstall' => 1,
			'php_postflight_uninstall' => '',
		]);

		$installer = eval($script);
		$adapter = $this->createStub(InstallerAdapter::class);

		$this->assertInstanceOf(InstallerScriptInterface::class, $installer);
		$this->assertTrue($installer->install($adapter));
		$this->assertTrue($installer->preflight('update', $adapter));
		$this->assertTrue($installer->postflight('uninstall', $adapter));
	}

	/**
	 * Native loading preserves the existing minimum-version failure and message.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testNativePreflightPreservesTheVersionAbort(): void
	{
		define('JVERSION', '4.4.0');
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('enqueueMessage')->with(
			'Please upgrade to at least Joomla! 5.0.0 before continuing!',
			'error'
		);
		$this->setJoomlaApplication($app);
		$script = (new JoomlaSixInstallScript())->get((object) [
			'official_name' => 'Native Abort',
			'class_name' => 'NativeAbort',
			'installer_class_name' => 'NativeAbortInstallerScript',
		]);
		$installer = eval($script);
		$adapter = $this->createStub(InstallerAdapter::class);

		$this->assertInstanceOf(InstallerScriptInterface::class, $installer);
		$this->assertFalse($installer->preflight('install', $adapter));
		$this->assertTrue($installer->postflight('install', $adapter));
	}

	/**
	 * Every enabled custom hook retains its entire previous script contract.
	 *
	 * Joomla 5 and the previous Joomla 6 generator have identical output with
	 * debug line suffixes disabled by this fixture.
	 *
	 * @param   string  $hook  Configured custom hook field.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('customHookProvider')]
	public function testCustomHooksRetainLegacyOutput(string $hook): void
	{
		$extension = (object) [
			'official_name' => 'Custom Extension',
			'class_name' => 'CustomExtension',
			'installer_class_name' => 'CustomOutputInstallerScript',
			'add_' . $hook => 1,
			$hook => "\t\t// custom hook remains verbatim",
			'remove_file_paths' => ['/administrator/obsolete.php'],
			'remove_folder_paths' => ['/administrator/obsolete'],
		];

		$this->assertSame(
			(new JoomlaFiveInstallScript())->get($extension),
			(new JoomlaSixInstallScript())->get($extension)
		);
	}

	/**
	 * Legacy constructor context, null success and explicit aborts remain executable.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testCustomLifecycleReturnSemanticsAndConstructorContextArePreserved(): void
	{
		define('JVERSION', (new Version())->getShortVersion());
		$messages = [];
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(2))->method('enqueueMessage')
			->willReturnCallback(static function (string $message) use (&$messages): void
			{
				$messages[] = $message;
			});
		$this->setJoomlaApplication($app);
		$script = (new JoomlaSixInstallScript())->get((object) [
			'official_name' => 'Custom Lifecycle',
			'class_name' => 'CustomLifecycle',
			'installer_class_name' => 'CustomRuntimeInstallerScript',
			'add_php_script_construct' => 1,
			'php_script_construct' => "\t\t" . '$this->app->enqueueMessage($adapter instanceof InstallerAdapter ? "constructed" : "missing adapter");',
			'add_php_script_install' => 1,
			'php_script_install' => "\t\treturn false;",
			'add_php_script_update' => 1,
			'php_script_update' => "\t\treturn;",
			'add_php_script_uninstall' => 1,
			'php_script_uninstall' => "\t\t" . '$this->app->enqueueMessage("uninstalled");',
			'add_php_preflight_update' => 1,
			'php_preflight_update' => "\t\treturn false;",
			'add_php_postflight_install' => 1,
			'php_postflight_install' => "\t\treturn null;",
		]);

		$this->assertNull(eval($script));
		$this->assertSame([], $messages);
		$adapter = $this->createStub(InstallerAdapter::class);
		$class = 'CustomRuntimeInstallerScript';
		$installer = new LegacyInstallerScript(new $class($adapter));

		$this->assertSame(['constructed'], $messages);
		$this->assertFalse($installer->install($adapter));
		$this->assertTrue($installer->update($adapter));
		$this->assertTrue($installer->uninstall($adapter));
		$this->assertFalse($installer->preflight('update', $adapter));
		$this->assertTrue($installer->preflight('install', $adapter));
		$this->assertTrue($installer->postflight('install', $adapter));
		$this->assertSame(['constructed', 'uninstalled'], $messages);
	}

	/**
	 * Reusing the generator restores native eligibility and drops earlier hooks.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testJoomlaSixResetsNativeEligibilityBetweenExtensions(): void
	{
		$subject = new JoomlaSixInstallScript();
		$extension = (object) [
			'official_name' => 'Repeated Extension',
			'class_name' => 'Repeated',
			'installer_class_name' => 'RepeatedInstallerScript',
			'add_php_script_install' => 1,
			'php_script_install' => '// first extension only',
		];

		$this->assertStringNotContainsString('implements InstallerScriptInterface', $subject->get($extension));
		unset($extension->add_php_script_install, $extension->php_script_install);
		$script = $subject->get($extension);

		$this->assertStringContainsString('implements InstallerScriptInterface', $script);
		$this->assertStringContainsString('return new RepeatedInstallerScript();', $script);
		$this->assertStringNotContainsString('// first extension only', $script);
	}

	/**
	 * Custom hook families requiring a separately reviewed definition migration.
	 *
	 * @return  Generator<string, array{string}>
	 * @since   6.2.0
	 */
	public static function customHookProvider(): Generator
	{
		foreach (['php_script', 'php_method'] as $method)
		{
			foreach (['construct', 'install', 'update', 'uninstall'] as $route)
			{
				$hook = $method . '_' . $route;
				yield $hook => [$hook];
			}
		}

		foreach (['php_preflight', 'php_postflight'] as $method)
		{
			foreach (['install', 'update', 'uninstall', 'discover_install'] as $route)
			{
				$hook = $method . '_' . $route;
				yield $hook => [$hook];
			}
		}
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
