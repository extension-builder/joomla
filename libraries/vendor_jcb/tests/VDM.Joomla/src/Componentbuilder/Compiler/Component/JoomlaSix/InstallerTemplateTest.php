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
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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
		$template = file_get_contents($root . '/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/Component/JoomlaSix/Templates/INSTALLER_SCRIPT.php.tpl');
		$this->assertIsString($template);
		$code = substr($template, strpos($template, '###BOM###') + strlen('###BOM###'));
		$code = str_replace(
			[
				'###Component###',
				'###component###',
				'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power',
				'Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power',
			],
			['NativeFixture', 'nativefixture', 'Factory', 'Text'],
			$code
		);
		$code = preg_replace('/###[A-Z_]+###/', '', $code);
		$this->assertIsString($code);
		$installer = eval($code);
		$this->assertInstanceOf(InstallerScriptInterface::class, $installer);
		$adapter = $this->createStub(InstallerAdapter::class);
		$this->assertTrue($installer->install($adapter));
		$this->assertTrue($installer->update($adapter));
	}
}
