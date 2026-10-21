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
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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
	 * Evaluate the complete script against Joomla's actual installer interface.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testScriptReturnsItsNativeInstallerInstance(): void
	{
		define('JOOMLA_MINIMUM_PHP', '8.3.0');
		$this->setJoomlaApplication($this->createStub(CMSApplicationInterface::class));
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
		$adapter = $this->createStub(InstallerAdapter::class);
		$this->assertTrue($installer->install($adapter));
		$this->assertTrue($installer->update($adapter));
	}
}
