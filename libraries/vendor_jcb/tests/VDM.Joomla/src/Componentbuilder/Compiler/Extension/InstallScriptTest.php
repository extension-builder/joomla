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
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaFive\InstallScript as JoomlaFiveInstallScript;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaFour\InstallScript as JoomlaFourInstallScript;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaSix\InstallScript as JoomlaSixInstallScript;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaThree\InstallScript as JoomlaThreeInstallScript;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Extension\InstallInterface;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
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
		$this->assertStringContainsString(
			$class === JoomlaSixInstallScript::class
				? 'public function install(InstallerAdapter $adapter): bool'
				: 'public function install($adapter)',
			$script
		);
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
	 * Joomla 6 output rejects an otherwise supported Joomla 5 installation target.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testNativePreflightPreservesTheVersionAbort(): void
	{
		define('JVERSION', '5.4.0');
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('enqueueMessage')->with(
			'Please upgrade to at least Joomla! 6.0.0 before continuing!',
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
	 * Every enabled custom hook retains its body inside the native Joomla 6 contract.
	 *
	 * @param   string  $hook  Configured custom hook field.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('customHookProvider')]
	public function testCustomHooksUseNativeOutputWithoutRewritingBodies(string $hook): void
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

		$script = (new JoomlaSixInstallScript())->get($extension);

		$this->assertStringContainsString('implements InstallerScriptInterface', $script);
		$this->assertStringContainsString('return new CustomOutputInstallerScript();', $script);
		$this->assertStringContainsString($extension->{$hook}, $script);
		$this->assertStringNotContainsString('LegacyInstallerScript', $script);
		$this->assertStringNotContainsString('implements InstallerScriptInterface', (new JoomlaFiveInstallScript())->get($extension));
	}

	/**
	 * Native hooks retain adapter context, null success and explicit aborts.
	 *
	 * @param string $indentation The configured indentation unit.
	 * @param bool   $debug       Whether generated comments include their compiler origin.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	#[DataProvider('formattingProvider')]
	public function testCustomLifecycleReturnSemanticsAndConstructorContextArePreserved(string $indentation, bool $debug): void
	{
		(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, $indentation);
		(new ReflectionProperty(Indent::class, 'bucket'))->setValue(null, []);
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, $debug);
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

		$this->assertStringContainsString(PHP_EOL . $indentation . 'private function initializeInstaller(InstallerAdapter $adapter): void', $script);
		$this->assertStringContainsString(PHP_EOL . str_repeat($indentation, 2) . '$result = $this->installHook($adapter);', $script);
		$this->assertStringContainsString(PHP_EOL . str_repeat($indentation, 3) . '$this->initializeInstallerHook($adapter);', $script);
		$this->assertStringContainsString("\t\treturn false;", $script);
		$comment = $debug ? ' \\[' . preg_quote(JoomlaSixInstallScript::class, '~') . ' [0-9]+\\]' : '';

		foreach ([
			'Whether adapter-dependent initialization is running or completed.',
			'Initialize custom installer state once the adapter is available.',
			'Run the configured constructor body without rewriting its control flow.',
			'Run the install hook and preserve its explicit return value.',
		] as $description)
		{
			$this->assertMatchesRegularExpression('~^' . preg_quote($indentation, '~') . ' \\*' . $comment . ' ' . preg_quote($description, '~') . '$~m', $script);
		}

		$this->assertMatchesRegularExpression('~^' . preg_quote(str_repeat($indentation, 2), '~') . '//' . $comment
			. ' A hook with no return value completed successfully; explicit failures remain failures\\.$~m', $script);
		$installer = eval($script);
		$this->assertInstanceOf(InstallerScriptInterface::class, $installer);
		$this->assertNotInstanceOf(LegacyInstallerScript::class, $installer);
		$this->assertSame([], $messages);
		$adapter = $this->createStub(InstallerAdapter::class);

		$this->assertFalse($installer->install($adapter));
		$this->assertSame(['constructed'], $messages);
		$this->assertTrue($installer->update($adapter));
		$this->assertTrue($installer->uninstall($adapter));
		$this->assertFalse($installer->preflight('update', $adapter));
		$this->assertTrue($installer->preflight('install', $adapter));
		$this->assertTrue($installer->postflight('install', $adapter));
		$this->assertSame(['constructed', 'uninstalled'], $messages);
	}

	/**
	 * Compiler formatting options must not change the native installer contract.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function formattingProvider(): iterable
	{
		yield 'tabs' => ["\t", false];
		yield 'spaces' => ['    ', false];
		yield 'tabs with origins' => ["\t", true];
		yield 'spaces with origins' => ['    ', true];
	}

	/**
	 * Reusing the generator drops all earlier hooks while retaining the native contract.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testJoomlaSixResetsCustomHooksBetweenExtensions(): void
	{
		$subject = new JoomlaSixInstallScript();
		$extension = (object) [
			'official_name' => 'Repeated Extension',
			'class_name' => 'Repeated',
			'installer_class_name' => 'RepeatedInstallerScript',
			'add_php_script_install' => 1,
			'php_script_install' => '// first extension only',
		];

		$this->assertStringContainsString('// first extension only', $subject->get($extension));
		unset($extension->add_php_script_install, $extension->php_script_install);
		$script = $subject->get($extension);

		$this->assertStringContainsString('implements InstallerScriptInterface', $script);
		$this->assertStringContainsString('return new RepeatedInstallerScript();', $script);
		$this->assertStringNotContainsString('// first extension only', $script);
	}

	/**
	 * A constructor hook initializes before any first lifecycle and only once.
	 *
	 * @param   string  $method  First lifecycle called by the installer.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('lifecycleProvider')]
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testCustomInitializationPrecedesEveryLifecycle(string $method): void
	{
		define('JVERSION', (new Version())->getShortVersion());
		$messages = [];
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(3))->method('enqueueMessage')->willReturnCallback(static function (string $message) use (&$messages): void
		{
			$messages[] = $message;
		});
		$this->setJoomlaApplication($app);
		$adapter = $this->createStub(InstallerAdapter::class);
		$extension = (object) [
			'official_name' => 'Ordered Native Installer',
			'class_name' => 'OrderedNative',
			'installer_class_name' => 'OrderedNativeInstallerScript',
			'add_php_script_construct' => 1,
			'php_script_construct' => '$this->app->enqueueMessage("initialize:" . spl_object_id($adapter)); return;',
			'add_php_script_install' => 1,
			'php_script_install' => '$this->app->enqueueMessage("install");',
			'add_php_script_update' => 1,
			'php_script_update' => '$this->app->enqueueMessage("update");',
			'add_php_script_uninstall' => 1,
			'php_script_uninstall' => '$this->app->enqueueMessage("uninstall");',
			'add_php_preflight_install' => 1,
			'php_preflight_install' => '$app->enqueueMessage("preflight:" . $route);',
			'add_php_postflight_install' => 1,
			'php_postflight_install' => '$app->enqueueMessage("postflight:" . $route);',
		];
		$installer = eval((new JoomlaSixInstallScript())->get($extension));
		$arguments = in_array($method, ['preflight', 'postflight'], true) ? ['install', $adapter] : [$adapter];

		$this->assertSame([], $messages);
		$this->assertTrue($installer->{$method}(...$arguments));
		$this->assertTrue($installer->{$method}(...$arguments));
		$event = in_array($method, ['preflight', 'postflight'], true) ? $method . ':install' : $method;
		$this->assertSame(['initialize:' . spl_object_id($adapter), $event, $event], $messages);
	}

	/**
	 * PHP ignored constructor return values; the native initializer must do the same.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testConstructorReturnValueDoesNotBecomeLifecycleFailure(): void
	{
		$this->setJoomlaApplication($this->createStub(CMSApplicationInterface::class));
		$extension = (object) [
			'official_name' => 'Returning Constructor',
			'class_name' => 'ReturningConstructor',
			'installer_class_name' => 'ReturningConstructorInstallerScript',
			'add_php_script_construct' => 1,
			'php_script_construct' => 'return false; throw new \\RuntimeException("unreachable");',
		];
		$installer = eval((new JoomlaSixInstallScript())->get($extension));
		$this->assertInstanceOf(InstallerScriptInterface::class, $installer);
		$this->assertTrue($installer->install($this->createStub(InstallerAdapter::class)));
	}

	/**
	 * Lifecycle return coercion preserves the established installer success contract.
	 *
	 * @param   string  $body      Configured lifecycle body.
	 * @param   bool    $expected  Expected interface result.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('returnProvider')]
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testCustomLifecycleResultsRemainExact(string $body, bool $expected): void
	{
		define('JVERSION', (new Version())->getShortVersion());
		$this->setJoomlaApplication($this->createStub(CMSApplicationInterface::class));
		$extension = (object) [
			'official_name' => 'Native Results',
			'class_name' => 'NativeResults',
			'installer_class_name' => 'NativeResultsInstallerScript',
		];

		foreach (['php_script_install', 'php_script_update', 'php_script_uninstall', 'php_preflight_install', 'php_postflight_install'] as $hook)
		{
			$extension->{'add_' . $hook} = 1;
			$extension->{$hook} = $body;
		}

		$installer = eval((new JoomlaSixInstallScript())->get($extension));
		$adapter = $this->createStub(InstallerAdapter::class);

		$this->assertSame($expected, $installer->install($adapter));
		$this->assertSame($expected, $installer->update($adapter));
		$this->assertSame($expected, $installer->uninstall($adapter));
		$this->assertSame($expected, $installer->preflight('install', $adapter));
		$this->assertSame($expected, $installer->postflight('install', $adapter));
	}

	/**
	 * Initialization exceptions propagate without marking a failed attempt complete.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testFailedInitializationCanRetryAndLifecycleErrorsPropagate(): void
	{
		$this->setJoomlaApplication($this->createStub(CMSApplicationInterface::class));
		$script = (new JoomlaSixInstallScript())->get((object) [
			'official_name' => 'Native Exceptions',
			'class_name' => 'NativeExceptions',
			'installer_class_name' => 'NativeExceptionsInstallerScript',
			'add_php_script_construct' => 1,
			'php_script_construct' => 'static $attempts = 0; if (++$attempts === 1) { throw new \\RuntimeException("initialization failed"); }',
			'add_php_script_install' => 1,
			'php_script_install' => 'throw new \\RuntimeException("install failed");',
		]);
		$installer = eval($script);
		$adapter = $this->createStub(InstallerAdapter::class);

		try
		{
			$installer->update($adapter);
			$this->fail('Initialization failure must reach the caller.');
		}
		catch (\RuntimeException $error)
		{
			$this->assertSame('initialization failed', $error->getMessage());
		}

		$this->assertTrue($installer->update($adapter));
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('install failed');
		$installer->install($adapter);
	}

	/**
	 * Constructor hooks may invoke a lifecycle without recursively initializing.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testConstructorHookCanInvokeAnotherLifecycle(): void
	{
		$messages = [];
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(3))->method('enqueueMessage')
			->willReturnCallback(static function (string $message) use (&$messages): void
			{
				$messages[] = $message;
			});
		$this->setJoomlaApplication($app);
		$script = (new JoomlaSixInstallScript())->get((object) [
			'official_name' => 'Reentrant Initializer',
			'class_name' => 'ReentrantInitializer',
			'installer_class_name' => 'ReentrantInitializerInstallerScript',
			'add_php_script_construct' => 1,
			'php_script_construct' => 'static $attempts = 0; if (++$attempts > 1) { throw new \\RuntimeException("recursive initialization"); } $this->app->enqueueMessage("initialize"); $this->update($adapter);',
			'add_php_script_update' => 1,
			'php_script_update' => '$this->app->enqueueMessage("update");',
			'add_php_script_install' => 1,
			'php_script_install' => '$this->app->enqueueMessage("install");',
		]);
		$installer = eval($script);

		$this->assertTrue($installer->install($this->createStub(InstallerAdapter::class)));
		$this->assertSame(['initialize', 'update', 'install'], $messages);
	}

	/**
	 * An early preflight return retains its cleanup boundary and result.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testPreflightAbortsAndEarlySuccessDoNotRunCleanup(): void
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
			'official_name' => 'Cleanup Contract',
			'class_name' => 'CleanupContract',
			'installer_class_name' => 'CleanupContractInstallerScript',
			'add_php_script_construct' => 1,
			'php_script_construct' => '$this->app->enqueueMessage("initialize");',
			'add_php_preflight_update' => 1,
			'php_preflight_update' => 'return false;',
			'add_php_preflight_install' => 1,
			'php_preflight_install' => 'return;',
		]);
		eval($script);
		$installer = new class extends \CleanupContractInstallerScript
		{
			/**
			 * Observe cleanup without changing files in the pinned Joomla source tree.
			 *
			 * @return  void
			 * @since   6.2.0
			 */
			protected function removeFiles()
			{
				$this->app->enqueueMessage('cleanup');
			}
		};
		$adapter = $this->createStub(InstallerAdapter::class);

		$this->assertFalse($installer->preflight('update', $adapter));
		$this->assertTrue($installer->preflight('install', $adapter));
		$this->assertSame(['initialize'], $messages);
		$this->assertTrue($installer->preflight('uninstall', $adapter));
		$this->assertSame(['initialize', 'cleanup'], $messages);
	}

	/**
	 * Every native lifecycle can be the first entry point.
	 *
	 * @return  Generator<string, array{string}>
	 * @since   6.2.0
	 */
	public static function lifecycleProvider(): Generator
	{
		foreach (['install', 'update', 'uninstall', 'preflight', 'postflight'] as $method)
		{
			yield $method => [$method];
		}
	}

	/**
	 * Values accepted by Joomla's existing installer success contract.
	 *
	 * @return  Generator<string, array{string, bool}>
	 * @since   6.2.0
	 */
	public static function returnProvider(): Generator
	{
		yield 'fall through' => ['// completed', true];
		yield 'bare return' => ['return;', true];
		yield 'null return' => ['return null;', true];
		yield 'explicit success' => ['return true;', true];
		yield 'explicit abort' => ['return false;', false];
		yield 'zero abort' => ['return 0;', false];
		yield 'empty string abort' => ['return "";', false];
		yield 'nonempty result' => ['return "completed";', true];
	}

	/**
	 * Custom hook families supported by the native Joomla 6 installer.
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
		yield 'Joomla 6' => [JoomlaSixInstallScript::class, '6.0.0', true];
	}
}
