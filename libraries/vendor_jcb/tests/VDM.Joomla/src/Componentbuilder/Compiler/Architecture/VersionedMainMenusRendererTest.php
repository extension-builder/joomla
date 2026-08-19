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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Menu\CustomMainMenu;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Registry;


/**
 * Generated administrator main menu contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedMainMenusRendererTest extends ArchitectureTestCase
{
	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.7
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree', 3],
			'Joomla 4' => ['JoomlaFour', 4],
			'Joomla 5' => ['JoomlaFive', 5],
			'Joomla 6' => ['JoomlaSix', 6],
		];
	}

	/**
	 * The targets that have a default dashboard for a component to reach.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		$versions = self::versions();
		unset($versions['Joomla 3']);

		return $versions;
	}

	/**
	 * Build the main menu renderer of one target.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   int     $major      Joomla target major.
	 * @param   array   $views      The admin views the component declares.
	 * @param   string  $dashboard  The dashboard the component builds, if it builds one.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function subject(string $version, int $major, array $views,
		?string $dashboard = null): object
	{
		$this->config()->set('joomla_version', $major);

		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));
		$component->set('admin_views', $views);
		$component->set('name', 'Demo');

		$registry = new Registry();

		if ($dashboard !== null)
		{
			$registry->set('build.dashboard', $dashboard);
		}

		return $this->renderer(
			$this->targetClass($version, 'Menu\\MainMenus', ['JoomlaThree']),
			[
				'component' => $component,
				'registry' => $registry,
				'custommainmenu' => new CustomMainMenu($component, $this->language()),
			]
		);
	}

	/**
	 * Build one admin view of the demo component.
	 *
	 * @param   string  $single  The single view code name.
	 * @param   string  $list    The list view code name.
	 * @param   int     $menu    Whether the view asks for a main menu entry.
	 *
	 * @return  array  The view, as the component data carries it.
	 * @since   6.1.7
	 */
	private static function view(string $single, string $list, int $menu = 1): array
	{
		$settings = new stdClass();
		$settings->name_single = $single;
		$settings->name_list = $list;
		$settings->name_single_code = $single;
		$settings->name_list_code = $list;

		return ['settings' => $settings, 'adminview' => $single, 'mainmenu' => $menu];
	}

	/**
	 * A component with no admin views declares no main menu.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAComponentWithoutAdminViewsDeclaresNoMainMenu(string $version, int $major): void
	{
		$this->assertSame('', $this->subject($version, $major, [])->get());
	}

	/**
	 * Every target gives a view that asks for one its own entry.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testEveryTargetGivesAViewThatAsksItsOwnEntry(string $version, int $major): void
	{
		$this->assertStringContainsString(
			'<menu option="com_demo" view="looks">COM_DEMO_MENU_LOOKS</menu>',
			$this->subject($version, $major, [self::view('look', 'looks')])->get()
		);
	}

	/**
	 * A view that asks for no entry is given none.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewThatAsksForNoEntryIsGivenNone(string $version, int $major): void
	{
		$this->assertStringNotContainsString(
			'COM_DEMO_MENU_LOOKS',
			$this->subject($version, $major, [self::view('look', 'looks', 0)])->get()
		);
	}

	/**
	 * Joomla 3 has no default dashboard, so it reaches none.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeReachesNoDefaultDashboard(): void
	{
		$this->assertSame(
			self::EXPECTED_J3,
			$this->subject('JoomlaThree', 3, [self::view('look', 'looks')])->get()
		);
	}

	/**
	 * Later targets reach the default dashboard before anything else.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernTargetsReachTheDefaultDashboardFirst(string $version, int $major): void
	{
		$this->assertSame(
			self::EXPECTED_MODERN,
			$this->subject($version, $major, [self::view('look', 'looks')])->get()
		);
	}

	/**
	 * A component that builds its own dashboard is given no entry to the default.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAComponentWithItsOwnDashboardReachesNoDefault(string $version, int $major): void
	{
		$this->assertSame(
			self::EXPECTED_J3,
			$this->subject($version, $major, [self::view('look', 'looks')], 'look')->get()
		);
	}

	/**
	 * The dashboard entry brings its own language string, and only when it is built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDashboardEntryBringsItsOwnLanguageString(): void
	{
		$this->subject('JoomlaSix', 6, [self::view('look', 'looks')])->get();

		$this->assertSame(
			[
				'COM_DEMO_MENU' => '&#187; Demo',
				'COM_DEMO_MENU_DASHBOARD' => 'Dashboard',
				'COM_DEMO_MENU_LOOKS' => 'looks',
			],
			$this->language()->getTarget('adminsys')
		);
	}

	/**
	 * Joomla 3 registers no language string for a dashboard it never reaches.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeRegistersNoDashboardLanguageString(): void
	{
		$this->subject('JoomlaThree', 3, [self::view('look', 'looks')])->get();

		$this->assertSame(
			[
				'COM_DEMO_MENU' => '&#187; Demo',
				'COM_DEMO_MENU_LOOKS' => 'looks',
			],
			$this->language()->getTarget('adminsys')
		);
	}

	/**
	 * The generated main menu this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3 = <<<'GEN'

					<menu option="com_demo" view="looks">COM_DEMO_MENU_LOOKS</menu>
		GEN;

	/**
	 * The generated main menu this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN = <<<'GEN'

					<menu option="com_demo" view="demo">COM_DEMO_MENU_DASHBOARD</menu>
					<menu option="com_demo" view="looks">COM_DEMO_MENU_LOOKS</menu>
		GEN;
}
