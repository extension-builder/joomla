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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\Assembly;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Extension\VersionUpdate;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * What the component needs once its views are built.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\Component')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class AssemblyTest extends ArchitectureTestCase
{
	/**
	 * What was written for the component as a whole.
	 *
	 * @var    ContentOne|null
	 * @since  6.1.7
	 */
	private ?ContentOne $one = null;

	/**
	 * What was written for each view.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $multi = null;

	/**
	 * Every admin view is named to the component in one array.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryAdminViewIsNamedToTheComponent(): void
	{
		$this->build(["'demo' => 'demos'", "'looker' => 'lookers'"], []);

		$written = $this->one->get('VIEWARRAY');

		$this->assertStringContainsString("'demo' => 'demos'", $written);
		$this->assertStringContainsString("'looker' => 'lookers'", $written);
	}

	/**
	 * Every view the site may edit is named to the component in one array.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryViewTheSiteMayEditIsNamed(): void
	{
		$this->build([], ['demo' => 'demos']);

		$this->assertNotNull($this->one->get('SITE_EDIT_VIEW_ARRAY'));
	}

	/**
	 * Each generated file is given the header it opens with.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachGeneratedFileIsGivenItsHeader(): void
	{
		$this->build([], []);

		foreach ([
			'ADMIN_HELPER_CLASS_HEADER',
			'ADMIN_COMPONENT_HEADER',
			'SITE_HELPER_CLASS_HEADER',
			'SITE_COMPONENT_HEADER',
			'SITE_ROUTER_HEADER'
		] as $key)
		{
			$this->assertNotNull($this->one->get($key));
		}
	}

	/**
	 * The component is given its menus, its keys and its sql.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheComponentIsGivenItsMenusKeysAndSql(): void
	{
		$this->build([], []);

		foreach ([
			'MAINMENUS', 'SUBMENU', 'GET_CRYPT_KEY', 'CONTRIBUTORS',
			'INSTALL', 'UNINSTALL', 'HELPER_EXEL'
		] as $key)
		{
			$this->assertNotNull($this->one->get($key));
		}
	}

	/**
	 * A component using the default dashboard is given the dashboard it needs.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentOnTheDefaultDashboardIsGivenIt(): void
	{
		$this->build([], []);

		$written = $this->multi->get('demo');

		$this->assertIsArray($written);
		$this->assertArrayHasKey('###DASHBOARDICONS###', $written);
	}

	/**
	 * A component with a dashboard of its own is given that one instead.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithItsOwnDashboardIsGivenThatOne(): void
	{
		$registry = new Registry();
		$registry->set('build.dashboard', 'looker');

		$this->build([], [], ['registry' => $registry]);

		$written = (array) $this->multi->get('demo');

		$this->assertArrayNotHasKey('###DASHBOARDICONS###', $written);
	}

	/**
	 * Fill in what one component needs once its views are built.
	 *
	 * @param   array  $viewarray   Every admin view, by its two names.
	 * @param   array  $siteEdit    Every view the site may edit.
	 * @param   array  $overrides   What the compiler collected.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function build(array $viewarray, array $siteEdit, array $overrides = []): void
	{
		$this->one = new ContentOne();
		$this->multi = new ContentMulti();
		$this->config()->set('component_code_name', 'demo');

		$subject = $this->renderer(Assembly::class, $overrides + [
			'contentone' => $this->one,
			'contentmulti' => $this->multi,
			'versionupdate' => $this->renderer(VersionUpdate::class, [
				'contentone' => $this->one,
				'contentmulti' => $this->multi
			])
		]);

		$subject->build($viewarray, $siteEdit);
	}
}
