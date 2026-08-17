<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Dashboard\Icons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryOtherName;
use VDM\Joomla\Componentbuilder\Compiler\Component;


/**
 * Generated component dashboard icon contracts.
 *
 * The icons read the same on every Joomla target, so this is one class with
 * no target variants at all.
 *
 * @since  6.1.7
 */
#[CoversClass(Icons::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class DashboardIconsTest extends ArchitectureTestCase
{
	/**
	 * A component with no views renders no icons.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutViewsRendersNoIcons(): void
	{
		$this->assertSame('', $this->icons([]));
	}

	/**
	 * An admin view contributes an add icon and a list icon.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAdminViewContributesAnAddAndAListIcon(): void
	{
		$code = $this->icons([$this->view('article', 'articles')]);

		$this->assertSame("'png.article.add', 'png.articles'", $code);
	}

	/**
	 * Every view contributes its own pair, in the order they are declared.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryViewContributesItsOwnPairInOrder(): void
	{
		$code = $this->icons([
			$this->view('article', 'articles'),
			$this->view('comment', 'comments'),
		]);

		$this->assertSame(
			"'png.article.add', 'png.articles', 'png.comment.add', 'png.comments'",
			$code
		);
	}

	/**
	 * A view that opts out of the add icon contributes only its list icon.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutAnAddIconContributesOnlyItsList(): void
	{
		$view = $this->view('article', 'articles');
		$view['dashboard_add'] = 0;

		$this->assertSame("'png.articles'", $this->icons([$view]));
	}

	/**
	 * A view that opts out of the list icon contributes only its add icon.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutAListIconContributesOnlyItsAdd(): void
	{
		$view = $this->view('article', 'articles');
		$view['dashboard_list'] = 0;

		$this->assertSame("'png.article.add'", $this->icons([$view]));
	}

	/**
	 * Build the dashboard icons of one component shape.
	 *
	 * @param   array  $adminViews  The admin view definitions.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function icons(array $adminViews): string
	{
		$component = $this->renderer(Component::class);
		$component->set('admin_views', $adminViews);
		$component->set('dashboard_type', 0);

		$subject = new Icons(
			$this->config(),
			$component,
			$this->language(),
			new Category(),
			new CategoryOtherName(),
			$this->createStub(\VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths::class)
		);

		return $subject->get([]);
	}

	/**
	 * Build one admin view definition, as the component registry stores it.
	 *
	 * @param   string  $single  The single view code name.
	 * @param   string  $list    The list view code name.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(string $single, string $list): array
	{
		return [
			'adminview' => $single . '-guid',
			'settings' => (object) [
				'name_single' => ucfirst($single),
				'name_list' => ucfirst($list),
				'name_single_code' => $single,
				'name_list_code' => $list,
				'icon' => 'stack',
				'icon_add' => 'stack',
				'icon_category' => 'stack',
			],
			'dashboard_add' => 1,
			'dashboard_list' => 1,
		];
	}
}
