<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    21st October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Component\JoomlaSix;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor as JoomlaPowerExtractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector as JoomlaPowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * The rendered component script returns a native installer to Joomla's loader.
 *
 * @since  6.2.0
 */
#[CoversNothing]
final class InstallerTemplateTest extends JoomlaTestCase
{
	/**
	 * Evaluate the target requirements and actual native installer interface.
	 *
	 * @param string $version   The destination Joomla version.
	 * @param bool   $supported Whether installation may continue.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('destinationVersions')]
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testScriptEnforcesTargetMinimumsAndReturnsItsNativeInstance(string $version, bool $supported): void
	{
		// A Joomla 4 compiler host must not lower the generated target requirement.
		define('JOOMLA_MINIMUM_PHP', '8.1.0');
		define('JVERSION', $version);
		$language = $this->createStub(Language::class);
		$language->method('_')->willReturnArgument(0);
		$this->setJoomlaFactoryProperty('language', $language);
		$app = $this->createStub(CMSApplicationInterface::class);
		$app->method('getLanguage')->willReturn($language);
		$this->setJoomlaApplication($app);
		$messages = [];
		Log::setInstance(null);
		Log::addLogger(['logger' => 'callback', 'callback' => static function ($entry) use (&$messages): void
		{
			$messages[] = [$entry->message, $entry->priority, $entry->category];
		}], Log::ALL, ['jerror']);
		$container = new Container();
		$container->set(DatabaseInterface::class, $this->createStub(DatabaseInterface::class));
		$this->setJoomlaContainer($container);
		$root = dirname(JPATH_COMPONENT_ADMINISTRATOR);
		$template = file_get_contents($root . '/admin/compiler/joomla_6/INSTALLER_SCRIPT.php');
		$this->assertIsString($template);
		$code = substr($template, strpos($template, '###BOM###') + strlen('###BOM###'));
		$code = str_replace(['###Component###', '###component###'], ['NativeFixture', 'nativefixture'], $code);
		$code = preg_replace('/###[A-Z_]+###/', '', $code);
		$this->assertIsString($code);
		$this->assertStringContainsString('Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power', $code);
		$this->assertStringContainsString('Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power', $code);
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['39403062-84fb-46e0-bac4-0023f766e827', 0, (object) ['_namespace' => 'Joomla\CMS', 'class_name' => 'Factory', 'type' => 'class']],
			['ba6326ef-cb79-4348-80f4-ab086082e3c5', 0, (object) ['_namespace' => 'Joomla\CMS\Language', 'class_name' => 'Text', 'type' => 'class']],
		]);
		$injector = new JoomlaPowerInjector(
			$power,
			new JoomlaPowerExtractor($this->createStub(DatabaseInterface::class), 6),
			new Parser(),
			new Placeholder(new Config(new Input(), new Registry(), new Registry()))
		);
		$code = $injector->power('<?php' . $code);
		$this->assertStringNotContainsString('Joomla___', $code);
		$installer = eval(substr($code, strlen('<?php')));
		$this->assertInstanceOf(InstallerScriptInterface::class, $installer);
		$this->assertSame('6.0.0', (new ReflectionProperty($installer, 'minimumJoomla'))->getValue($installer));
		$this->assertSame('8.3.0', (new ReflectionProperty($installer, 'minimumPhp'))->getValue($installer));
		$adapter = $this->createMock(InstallerAdapter::class);
		$adapter->expects($supported ? $this->once() : $this->never())->method('getName')->willReturn('nativefixture');
		$adapter->expects($supported ? $this->once() : $this->never())->method('getManifest')->willReturn((object) ['version' => '1.0.0']);
		$this->assertSame($supported, $installer->preflight('install', $adapter));
		$this->assertSame($supported ? [] : [['JLIB_INSTALLER_MINIMUM_JOOMLA', Log::WARNING, 'jerror']], $messages);
		$this->assertTrue($installer->install($adapter));
		$this->assertTrue($installer->update($adapter));
	}

	/**
	 * Native Joomla 6 code requires the target major, regardless of compiler host.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function destinationVersions(): iterable
	{
		yield 'Joomla 5 rejected' => ['5.4.0', false];
		yield 'Joomla 6 minimum accepted' => ['6.0.0', true];
		yield 'Joomla 6 current accepted' => ['6.1.2', true];
	}
}
