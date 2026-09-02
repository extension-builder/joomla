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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\AllowView;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The read permission of a dynamic get API controller.
 *
 * @since 6.1.7
 */
#[CoversClass(AllowView::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class AllowViewTest extends ArchitectureTestCase
{
	/**
	 * The body of a site view whose link asks for its access permission.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SITE_ACCESS = <<<'GEN'

		// Get the calling user.
		$user = Factory::getApplication()->getIdentity();

		// The site.truck.access permission the truck view link asks for.
		return $user->authorise('site.truck.access', 'com_demo');
GEN;

	/**
	 * The body of a custom admin view whose link asks for its access permission.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ADMIN_ACCESS = <<<'GEN'

		// Get the calling user.
		$user = Factory::getApplication()->getIdentity();

		// The administrator area asks for core.manage.
		if (!$user->authorise('core.manage', 'com_demo'))
		{
			return false;
		}

		// The report.access permission the report view link asks for.
		return $user->authorise('report.access', 'com_demo');
GEN;

	/**
	 * A site view without an access flag asks for nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteViewWithoutAnAccessFlagAsksForNothing(): void
	{
		$this->assertSame(
			PHP_EOL . "\t\t// The truck view asks for no permission of its own." . PHP_EOL . "\t\treturn true;",
			$this->renderer(AllowView::class)->get(['area' => 'site', 'code' => 'truck', 'access' => false])
		);
	}

	/**
	 * A site view with an access flag asks for its site permission.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteViewWithAnAccessFlagAsksForItsSitePermission(): void
	{
		$this->assertSame(
			self::EXPECTED_SITE_ACCESS,
			$this->renderer(AllowView::class)->get(['area' => 'site', 'code' => 'truck', 'access' => true])
		);
	}

	/**
	 * A custom admin view asks for core.manage and its own permission when flagged.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomAdminViewAsksForCoreManageAndItsOwnPermission(): void
	{
		$subject = $this->renderer(AllowView::class);

		$this->assertSame(
			self::EXPECTED_ADMIN_ACCESS,
			$subject->get(['area' => 'custom_admin', 'code' => 'report', 'access' => true])
		);

		$bare = $subject->get(['area' => 'custom_admin', 'code' => 'report', 'access' => false]);

		$this->assertStringContainsString("if (!\$user->authorise('core.manage', 'com_demo'))", $bare);
		$this->assertStringContainsString('// The report view asks for no permission of its own.', $bare);
		$this->assertStringEndsWith("\t\treturn true;", $bare);
		$this->assertStringNotContainsString('report.access', $bare);
	}
}
