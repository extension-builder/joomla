<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Controller;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\AllowView;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The view permission of the item API controller.
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
	 * The view check of a view without an access permission.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_OPEN = <<<'GEN'

		// In the absence of an access permission, every authenticated user may view.
		return true;
GEN;

	/**
	 * The view check of a view with an access permission.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_GUARDED = <<<'GEN'

		// Get user object.
		$user = $this->app->getIdentity();

		// Access check.
		return ($user->authorise('demo.access', 'com_demo.demo.' . $id) && $user->authorise('demo.access', 'com_demo'));
GEN;

	/**
	 * A view without an access permission lets every user view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutAnAccessPermissionLetsEveryUserView(): void
	{
		$subject = $this->renderer(AllowView::class);

		$this->assertSame(self::EXPECTED_OPEN, $subject->get('demo'));
	}

	/**
	 * A view with an access permission checks the record and the component.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAnAccessPermissionChecksTheRecordAndTheComponent(): void
	{
		$subject = $this->renderer(AllowView::class, [
			'permission' => $this->permissionWith(['demo|core.access' => 'demo.access'], ['demo.access|demo' => 'demo'])
		]);

		$this->assertSame(self::EXPECTED_GUARDED, $subject->get('demo'));
	}

	/**
	 * An access permission of another view is not applied.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAccessPermissionOfAnotherViewIsNotApplied(): void
	{
		$subject = $this->renderer(AllowView::class, [
			'permission' => $this->permissionWith(['other|core.access' => 'other.access'], ['other.access|other' => 'other'])
		]);

		$this->assertSame(self::EXPECTED_OPEN, $subject->get('demo'));
	}
}
