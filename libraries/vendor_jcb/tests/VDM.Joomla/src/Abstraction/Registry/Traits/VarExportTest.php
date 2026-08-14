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

namespace VDM\Joomla\Tests\Abstraction\Registry\Traits;


use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Abstraction\Registry\Traits\VarExport;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Tests\Support\CompilerUtilityTestCase;
use VDM\Tests\Support\RegistryTraitsFixture;


/**
 * Registry PHP-array export trait tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(VarExport::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(Indent::class)]
final class VarExportTest extends CompilerUtilityTestCase
{
	/**
	 * Export selected data with short-array syntax and configured indentation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testVarExportProducesDeterministicShortArrayCode(): void
	{
		$subject = new RegistryTraitsFixture([
			'permissions' => [
				'view' => 'core.manage',
				'edit' => 'core.edit'
			]
		]);

		$this->assertSame(
			'[' . PHP_EOL
				. "\t\t'view' => 'core.manage'," . PHP_EOL
				. "\t\t'edit' => 'core.edit'," . PHP_EOL
				. "\t]",
			$subject->varExport('permissions', 1)
		);
	}

	/**
	 * Return null when a selected path is absent.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testVarExportReturnsNullForMissingPath(): void
	{
		$subject = new RegistryTraitsFixture(['value' => 'present']);

		$this->assertNull($subject->varExport('missing'));
	}

	/**
	 * Return null when no path is selected and the registry is empty.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testVarExportReturnsNullForAnEmptyRegistry(): void
	{
		$subject = new RegistryTraitsFixture();

		$this->assertNull($subject->varExport());
	}
}
