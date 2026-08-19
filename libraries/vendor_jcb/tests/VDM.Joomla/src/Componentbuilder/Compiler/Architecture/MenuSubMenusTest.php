<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Menu\CustomSubMenu;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Menu\SubMenus;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryOtherName;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UninstallScriptContext;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UninstallScriptFields;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Registry;


/**
 * Generated administrator sub menu contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class MenuSubMenusTest extends ArchitectureTestCase
{
	/**
	 * What the run registered for removal, by view code name.
	 *
	 * @var    UninstallScriptContext
	 * @since  6.1.7
	 */
	private UninstallScriptContext $context;

	/**
	 * The views whose custom fields the run registered.
	 *
	 * @var    UninstallScriptFields
	 * @since  6.1.7
	 */
	private UninstallScriptFields $fields;

	/**
	 * Build one admin view of the demo component.
	 *
	 * @param   string  $single  The single view code name.
	 * @param   string  $list    The list view code name.
	 * @param   array   $over    What this case changes about the view.
	 *
	 * @return  array  The view, as the component data carries it.
	 * @since   6.1.7
	 */
	private static function view(string $single, string $list, array $over = []): array
	{
		$settings = new stdClass();
		$settings->name_single = $single;
		$settings->name_list = $list;
		$settings->name_single_code = $single;
		$settings->name_list_code = $list;
		// a real admin view always declares this; the renderer reads it unguarded
		$settings->add_category_submenu = 0;

		return $over + [
			'settings' => $settings,
			'adminview' => $single,
			'submenu' => 1,
			'mainmenu' => 1,
		];
	}

	/**
	 * Build the sub menu renderer over a component carrying these views.
	 *
	 * @param   array  $views  The admin views the component declares.
	 * @param   array  $menus  The custom menus the component declares.
	 *
	 * @return  SubMenus
	 * @since   6.1.7
	 */
	private function subject(array $views, array $menus = []): SubMenus
	{
		$this->context = new UninstallScriptContext();
		$this->fields = new UninstallScriptFields();

		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));
		$component->set('admin_views', $views);
		$component->set('name', 'Demo');

		if ($menus !== [])
		{
			$component->set('custommenus', $menus);
		}

		$custom = $this->renderer(CustomSubMenu::class, [
			'component' => $component,
			'permission' => $this->permission(),
		]);

		return $this->renderer(SubMenus::class, [
			'component' => $component,
			'registry' => new Registry(),
			'permission' => $this->permission(),
			'category' => new Category(),
			'categoryothername' => new CategoryOtherName(),
			'uninstallcontext' => $this->context,
			'uninstallfields' => $this->fields,
			'customsubmenu' => $custom,
		]);
	}

	/**
	 * A component with no admin views declares no sub menu.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutAdminViewsDeclaresNoSubMenu(): void
	{
		$this->assertSame('', $this->subject([])->get());
	}

	/**
	 * The dashboard is reached first, and then every view that asks to be.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDashboardComesFirstAndThenEveryViewThatAsks(): void
	{
		$this->assertSame(
			self::EXPECTED_PLAIN,
			$this->subject([self::view('look', 'looks')])->get()
		);
	}

	/**
	 * A view that asks for no sub menu entry is given none.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatAsksForNoEntryIsGivenNone(): void
	{
		$menu = $this->subject([self::view('look', 'looks', ['submenu' => 0])])->get();

		$this->assertStringContainsString('COM_DEMO_SUBMENU_DASHBOARD', $menu);
		$this->assertStringNotContainsString('COM_DEMO_SUBMENU_LOOKS', $menu);
	}

	/**
	 * A view carrying Joomla custom fields reaches its fields and their groups.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithJoomlaFieldsReachesThemAndTheirGroups(): void
	{
		$this->assertSame(
			self::EXPECTED_JOOMLA_FIELDS,
			$this->subject([self::view('look', 'looks', ['joomla_fields' => 1])])->get()
		);
	}

	/**
	 * The fields a view registers are recorded for the uninstall that removes them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheFieldsAViewRegistersAreRecordedForRemoval(): void
	{
		$this->subject([self::view('look', 'looks', ['joomla_fields' => 1])])->get();

		$this->assertSame(['look' => 'com_demo.look'], $this->context->allActive());
		$this->assertSame(['look' => 'look'], $this->fields->allActive());
	}

	/**
	 * A view without Joomla custom fields registers nothing to remove.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutJoomlaFieldsRegistersNothingToRemove(): void
	{
		$this->subject([self::view('look', 'looks')])->get();

		$this->assertSame([], $this->context->allActive());
		$this->assertSame([], $this->fields->allActive());
	}

	/**
	 * A custom menu that names no view to sit before is added after them all.
	 *
	 * This is the hand over: the entry is held back while the views are walked
	 * and taken once they are done, which is what the caller's unset used to do.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomMenuWithNoPlaceIsAddedAfterEveryView(): void
	{
		$this->assertSame(
			self::EXPECTED_CUSTOM_MENU,
			$this->subject(
				[self::view('look', 'looks')],
				[[
					'name' => 'Docs',
					'link' => 'index.php?option=com_demo&view=docs',
					'target' => 1,
					'submenu' => 1,
					'before' => '',
				]]
			)->get()
		);
	}

	/**
	 * The generated sub menu this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_PLAIN = <<<'GEN'
		Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_SUBMENU_DASHBOARD'), 'index.php?option=com_demo&view=demo', $submenu === 'demo');
				Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_SUBMENU_LOOKS'), 'index.php?option=com_demo&view=looks', $submenu === 'looks');
		GEN;

	/**
	 * The generated sub menu this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_JOOMLA_FIELDS = <<<'GEN'
		Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_SUBMENU_DASHBOARD'), 'index.php?option=com_demo&view=demo', $submenu === 'demo');
				Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_SUBMENU_LOOKS'), 'index.php?option=com_demo&view=looks', $submenu === 'looks');
				if (ComponentHelper::isEnabled('com_fields'))
				{
					Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_SUBMENU_LOOKS_FIELDS'), 'index.php?option=com_fields&context=com_demo.look', $submenu === 'fields.fields');
					Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_SUBMENU_LOOKS_FIELDS_GROUPS'), 'index.php?option=com_fields&view=groups&context=com_demo.look', $submenu === 'fields.groups');
				}
		GEN;

	/**
	 * The generated sub menu this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CUSTOM_MENU = <<<'GEN'
		Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_SUBMENU_DASHBOARD'), 'index.php?option=com_demo&view=demo', $submenu === 'demo');
				Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_SUBMENU_LOOKS'), 'index.php?option=com_demo&view=looks', $submenu === 'looks');
				if ($user->authorise('docs.submenu', 'com_demo'))
				{
					Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addEntry(Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_SUBMENU_DOCS'), 'index.php?option=com_demo&view=docs', $submenu === 'docs');
				}
		GEN;
}
