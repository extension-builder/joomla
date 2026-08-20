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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\AdminViews;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\Loop;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Every admin view the component was given.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class LoopTest extends ArchitectureTestCase
{
	/**
	 * A component given no admin views builds nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentGivenNoAdminViewsBuildsNothing(): void
	{
		[$viewarray, $siteEdit] = $this->build([]);

		$this->assertSame([], $viewarray);
		$this->assertSame([], $siteEdit);
	}

	/**
	 * Every admin view is named into the view map.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryAdminViewIsNamedIntoTheViewMap(): void
	{
		[$viewarray] = $this->build([
			$this->view('looker', 'lookers'),
			$this->view('seeker', 'seekers')
		]);

		$this->assertSame(
			["\t\t\t\t'looker' => 'lookers'", "\t\t\t\t'seeker' => 'seekers'"],
			$viewarray
		);
	}

	/**
	 * The admin target is the one the views are built under.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheAdminTargetIsTheOneTheViewsAreBuiltUnder(): void
	{
		$this->build([$this->view('looker', 'lookers')]);

		$this->assertSame('admin', $this->config()->build_target);
		$this->assertSame('admin', $this->config()->lang_target);
	}

	/**
	 * A view given a site edit view is kept for the site half of the build.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewGivenASiteEditViewIsKeptForTheSiteBuild(): void
	{
		$view = $this->view('looker', 'lookers');
		$view['edit_create_site_view'] = 3;

		[, $siteEdit] = $this->build([$view]);

		$this->assertSame(['looker' => 'lookers'], $siteEdit);
		$this->assertSame('both', $this->config()->lang_target);
		$this->assertFalse($this->config()->remove_site_edit_folder);
	}

	/**
	 * A view given no site edit view leaves the site edit folder alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewGivenNoSiteEditViewLeavesTheSiteEditFolderAlone(): void
	{
		$this->config()->remove_site_edit_folder = true;

		[, $siteEdit] = $this->build([$this->view('looker', 'lookers')]);

		$this->assertSame([], $siteEdit);
		$this->assertTrue($this->config()->remove_site_edit_folder);
	}

	/**
	 * Build every admin view of one component.
	 *
	 * @param   array  $views  The views the component was given.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function build(array $views): array
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));
		$component->set('admin_views', $views);

		$subject = $this->renderer(Loop::class, [
			'config' => $this->config(),
			'component' => $component
		]);

		return $subject->build();
	}

	/**
	 * An admin view the compiler collected.
	 *
	 * @param   string  $single  Its single code name.
	 * @param   string  $list    Its list code name.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(string $single, string $list): array
	{
		$settings = new stdClass();
		$settings->name_single_code = $single;
		$settings->name_list_code = $list;
		$settings->code = $single;
		$settings->Code = ucfirst($single);
		$settings->CODE = strtoupper($single);

		return ['settings' => $settings];
	}
}
