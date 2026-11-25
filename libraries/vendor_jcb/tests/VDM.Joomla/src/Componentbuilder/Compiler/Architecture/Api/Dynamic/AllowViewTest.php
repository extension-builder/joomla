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


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

		// Joomla 5.4.11 and 6.2.1 introduced the active route and the core.api dispatcher check.
		if (!method_exists(\Joomla\CMS\Application\ApiApplication::class, 'getActiveRoute')
			&& !$user->authorise('core.manage', 'com_demo'))
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
	 * A custom admin view retains its configured permission and the legacy management check.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomAdminViewEmitsTheLegacyGuardAndItsConfiguredPermission(): void
	{
		$subject = $this->renderer(AllowView::class);

		$this->assertSame(
			self::EXPECTED_ADMIN_ACCESS,
			$subject->get(['area' => 'custom_admin', 'code' => 'report', 'access' => true])
		);

		$bare = $subject->get(['area' => 'custom_admin', 'code' => 'report', 'access' => false]);

		$this->assertStringContainsString("&& !\$user->authorise('core.manage', 'com_demo'))", $bare);
		$this->assertStringContainsString('// The report view asks for no permission of its own.', $bare);
		$this->assertStringEndsWith("\t\treturn true;", $bare);
		$this->assertStringNotContainsString('report.access', $bare);
	}

	/**
	 * Run the emitted guard with representative legacy and patched API applications.
	 *
	 * @param   bool   $manage    Component management permission.
	 * @param   bool   $access    The link requires the view permission.
	 * @param   bool   $view      The user holds the view permission.
	 * @param   bool   $legacy    Expected result on the legacy dispatcher.
	 * @param   bool   $patched   Expected result after the component API gate.
	 *
	 * @return  void
	 * @since   6.3.0
	 */
	#[DataProvider('customAdminPermissions')]
	public function testCustomAdminViewPermissionResults(bool $manage, bool $access, bool $view, bool $legacy, bool $patched): void
	{
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturnMap([
			['core.manage', 'com_demo', $manage],
			['report.access', 'com_demo', $view],
		]);
		$application = $this->createStub(CMSApplication::class);
		$application->method('getIdentity')->willReturn($user);
		$this->setJoomlaApplication($application);

		$body = $this->renderer(AllowView::class)->get(['area' => 'custom_admin', 'code' => 'report', 'access' => $access]);
		// Bind the installed core class at the boundary so one run covers both API contracts.
		$body = str_replace('\\Joomla\\CMS\\Application\\ApiApplication::class', '$core', $body, $replacements);
		$this->assertSame(1, $replacements);
		$check = eval('use Joomla\\CMS\\Factory; return static function (object $core): bool {' . $body . '};');
		$this->assertSame($legacy, $check(new \stdClass()));
		$this->assertSame($patched, $check(new class
		{
			/**
			 * The route accessor provided by Joomla's patched API application.
			 *
			 * @return  null
			 * @since   6.3.0
			 */
			public function getActiveRoute()
			{
				return null;
			}
		}));
	}

	/**
	 * Custom admin links with and without view-level access requirements.
	 *
	 * @return  array<string, array{bool, bool, bool, bool, bool}>
	 * @since   6.3.0
	 */
	public static function customAdminPermissions(): array
	{
		return [
			'no manage, no view ACL' => [false, false, false, false, true],
			'no manage, view granted' => [false, true, true, false, true],
			'no manage, view denied' => [false, true, false, false, false],
			'manage, view denied' => [true, true, false, false, false],
			'manage, view granted' => [true, true, true, true, true],
		];
	}
}
