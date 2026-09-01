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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\AllowDelete;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The delete permission of the item API controller.
 *
 * @since 6.1.7
 */
#[CoversClass(AllowDelete::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class AllowDeleteTest extends ArchitectureTestCase
{
	private const EXPECTED_CORE = <<<'GEN'

		// Get user object.
		$user = $this->app->getIdentity();
		// In the absence of better information, revert to the component permissions.
		return $user->authorise('core.delete', $this->option);
GEN;

	private const EXPECTED_VIEW = <<<'GEN'

		// Get user object.
		$user = $this->app->getIdentity();
		// Access check.
		$access = $user->authorise('demo.access', 'com_demo');
		if (!$access)
		{
			return false;
		}
		// In the absence of better information, revert to the component permissions.
		return $user->authorise('demo.delete', $this->option);
GEN;

	public function testAViewWithoutItsOwnPermissionsRevertsToTheCoreDeleteAction(): void
	{
		$subject = $this->renderer(AllowDelete::class);

		$this->assertSame(self::EXPECTED_CORE, $subject->get('demo'));
	}

	public function testAViewWithItsOwnPermissionsIsGatedByAccessAndUsesItsDeleteAction(): void
	{
		$subject = $this->renderer(AllowDelete::class, [
			'permission' => $this->permissionWith(
				['demo|core.access' => 'demo.access', 'demo|core.delete' => 'demo.delete'],
				[],
				['demo.access|demo' => 'demo', 'demo.delete|demo' => 'demo']
			)
		]);

		$this->assertSame(self::EXPECTED_VIEW, $subject->get('demo'));
	}
}
