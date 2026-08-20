<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    20th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Component;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\InstallScripts;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UninstallScriptContext;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UninstallScriptFields;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Component\MoveFolderMethodInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Component\MoveFolderScriptInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Component\PostInstallScriptInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Component\UninstallScriptInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The install, update and uninstall scripts of the component.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\Component')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class InstallScriptsTest extends ArchitectureTestCase
{
	/**
	 * What was written once for the whole component.
	 *
	 * @var    ContentOne|null
	 * @since  6.1.7
	 */
	private ?ContentOne $one = null;

	/**
	 * Every script the installer file is built from is written.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryScriptTheInstallerFileIsBuiltFromIsWritten(): void
	{
		$this->set();

		$this->assertSame(
			[
				'###PREINSTALLSCRIPT###',
				'###PREUPDATESCRIPT###',
				'###POSTINSTALLSCRIPT###',
				'###POSTUPDATESCRIPT###',
				'###UNINSTALLSCRIPT###',
				'###INSTALLERMETHODS###',
				'###MOVEFOLDERSSCRIPT###',
				'###HELPER_UIKIT###'
			],
			array_keys($this->one->allActive())
		);
	}

	/**
	 * The post install script is the one the component was given.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testThePostInstallScriptIsTheOneTheComponentWasGiven(): void
	{
		$this->set();

		$this->assertSame(
			'post-install', $this->one->allActive()['###POSTINSTALLSCRIPT###']
		);
	}

	/**
	 * The uninstall script is told what the compiler collected to remove.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheUninstallScriptIsToldWhatWasCollectedToRemove(): void
	{
		$context = new UninstallScriptContext();
		$context->set('looker', 'com_demo.looker');
		$fields = new UninstallScriptFields();
		$fields->set('looker', true);

		$uninstall = $this->createMock(UninstallScriptInterface::class);
		$uninstall->expects($this->once())
			->method('get')
			->with(
				['looker' => 'com_demo.looker'],
				['looker' => true]
			)
			->willReturn('uninstall');

		$this->set([
			'uninstallscriptcontext' => $context,
			'uninstallscriptfields' => $fields,
			'uninstallscript' => $uninstall
		]);

		$this->assertSame(
			'uninstall', $this->one->allActive()['###UNINSTALLSCRIPT###']
		);
	}

	/**
	 * The folder moving method is added after the installer methods.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheFolderMovingMethodIsAddedAfterTheInstallerMethods(): void
	{
		$this->set();

		$this->assertSame(
			'installer-methodsmove-folder-method',
			$this->one->allActive()['###INSTALLERMETHODS###']
		);
	}

	/**
	 * Set the install scripts of one component.
	 *
	 * @param   array  $overrides  Collaborators the test names itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function set(array $overrides = []): void
	{
		$this->one = new ContentOne();

		$dispenser = $this->createStub(
			\VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser::class
		);
		$dispenser->method('get')->willReturnCallback(
			static fn(string $key, string $target): string
				=> $key === 'php_method' ? 'installer-methods' : ''
		);

		$postinstall = $this->createStub(PostInstallScriptInterface::class);
		$postinstall->method('get')->willReturn('post-install');

		$movescript = $this->createStub(MoveFolderScriptInterface::class);
		$movescript->method('get')->willReturn('move-folder-script');

		$movemethod = $this->createStub(MoveFolderMethodInterface::class);
		$movemethod->method('get')->willReturn('move-folder-method');

		$subject = $this->renderer(InstallScripts::class, $overrides + [
			'contentone' => $this->one,
			'dispenser' => $dispenser,
			'postinstallscript' => $postinstall,
			'movefolderscript' => $movescript,
			'movefoldermethod' => $movemethod
		]);

		$subject->set();
	}
}
