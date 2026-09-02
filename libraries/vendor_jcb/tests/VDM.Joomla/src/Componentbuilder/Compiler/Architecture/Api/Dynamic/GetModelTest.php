<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Dynamic;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\GetModel;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The model resolution of a dynamic get API controller.
 *
 * @since 6.1.7
 */
#[CoversClass(GetModel::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class GetModelTest extends ArchitectureTestCase
{
	/**
	 * The body serving a site view from its site model.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SITE = <<<'GEN'

		// The site model of the truck view, its request state ignored.
		return parent::getModel('Truck', 'Site', array_merge(['ignore_request' => true], $config));
GEN;

	/**
	 * A site view is served from the Site namespace.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteViewIsServedFromTheSiteNamespace(): void
	{
		$this->assertSame(self::EXPECTED_SITE, $this->renderer(GetModel::class)->get([
			'area' => 'site', 'code' => 'truck', 'settings' => (object) ['Code' => 'Truck'],
		]));
	}

	/**
	 * A custom admin view is served from the Administrator namespace by its class name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomAdminViewIsServedFromTheAdministratorNamespace(): void
	{
		$code = $this->renderer(GetModel::class)->get([
			'area' => 'custom_admin', 'code' => 'truck_report', 'settings' => (object) ['Code' => 'Truck_report'],
		]);

		$this->assertStringContainsString('// The administrator model of the truck_report view, its request state ignored.', $code);
		$this->assertStringContainsString("return parent::getModel('Truck_report', 'Administrator', array_merge(['ignore_request' => true], \$config));", $code);
	}

	/**
	 * Without the class case in the settings the code is capitalised.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithoutTheClassCaseTheCodeIsCapitalised(): void
	{
		$code = $this->renderer(GetModel::class)->get([
			'area' => 'site', 'code' => 'page', 'settings' => (object) [],
		]);

		$this->assertStringContainsString("parent::getModel('Page', 'Site'", $code);
	}
}
