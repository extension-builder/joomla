<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    18th August, 2026
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


/**
 * Generated custom admin menu contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class MenuCustomMainMenuTest extends ArchitectureTestCase
{
	/**
	 * The subject built for the current test.
	 *
	 * @var    CustomMainMenu
	 * @since  6.1.7
	 */
	private CustomMainMenu $subject;

	/**
	 * A custom admin view that names the view it sits before is placed there.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomAdminViewIsPlacedBeforeTheViewItNames(): void
	{
		$menu = $this->build([
			'custom_admin_views' => [
				['mainmenu' => 1, 'before' => 'articles', 'settings' => $this->settings('dash')],
			],
		]);

		$this->assertSame(
			PHP_EOL . "\t\t\t" . '<menu option="com_demo" view="dash">COM_DEMO_MENU_DASH</menu>',
			$menu
		);
		$this->assertSame([], $this->subject->takeDeferred());
	}

	/**
	 * A custom admin view that names nothing is held back for the end.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomAdminViewThatNamesNothingIsHeldBack(): void
	{
		$menu = $this->build([
			'custom_admin_views' => [
				0 => ['mainmenu' => 1, 'before' => 'articles', 'settings' => $this->settings('dash')],
				1 => ['mainmenu' => 1, 'before' => '', 'settings' => $this->settings('tail')],
			],
		]);

		$this->assertStringNotContainsString('view="tail"', $menu);
		$this->assertSame(
			[1 => PHP_EOL . "\t\t\t" . '<menu option="com_demo" view="tail">COM_DEMO_MENU_TAIL</menu>'],
			$this->subject->takeDeferred()
		);
	}

	/**
	 * What has been held back is handed over once, not twice.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWhatIsHeldBackIsHandedOverOnce(): void
	{
		$this->build([
			'custom_admin_views' => [
				['mainmenu' => 1, 'before' => '', 'settings' => $this->settings('tail')],
			],
		]);

		$this->assertNotSame([], $this->subject->takeDeferred());
		$this->assertSame([], $this->subject->takeDeferred());
	}

	/**
	 * A custom admin view already placed elsewhere is left alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomAdminViewAlreadyPlacedIsLeftAlone(): void
	{
		$menu = $this->build([
			'custom_admin_views' => [
				['mainmenu' => 1, 'before' => 'articles', 'settings' => $this->settings('dash')],
			],
		], ['dash' => true]);

		$this->assertSame('', $menu);
		$this->assertSame([], $this->subject->takeDeferred());
	}

	/**
	 * A view that asks for no main menu entry gets none.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatAsksForNoEntryGetsNone(): void
	{
		$menu = $this->build([
			'custom_admin_views' => [
				['mainmenu' => 0, 'before' => 'articles', 'settings' => $this->settings('off')],
				['before' => 'articles', 'settings' => $this->settings('none')],
			],
		]);

		$this->assertSame('', $menu);
		$this->assertSame([], $this->subject->takeDeferred());
	}

	/**
	 * A custom menu with a link is placed as a link, one without as a view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomMenuIsPlacedAsALinkOrAsAView(): void
	{
		$menu = $this->build([
			'custommenus' => [
				['mainmenu' => 1, 'before' => 'articles', 'name' => 'My Link',
					'name_code' => 'my_link', 'link' => 'index.php?option=com_x'],
				['mainmenu' => 1, 'before' => 'articles', 'name' => 'No Link',
					'name_code' => 'no_link'],
			],
		]);

		$this->assertStringContainsString(
			'<menu link="option=com_x">COM_DEMO_MENU_MY_LINK</menu>', $menu
		);
		$this->assertStringContainsString(
			'<menu option="com_demo" view="no_link">COM_DEMO_MENU_NO_LINK</menu>', $menu
		);
	}

	/**
	 * A custom menu that names nothing is held back, under its own key space.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomMenuHeldBackKeepsItsOwnKeySpace(): void
	{
		$this->build([
			'custommenus' => [
				0 => ['mainmenu' => 1, 'before' => '', 'name' => 'Tail Link',
					'name_code' => 'tail_link', 'link' => 'index.php?option=com_z'],
				1 => ['mainmenu' => 1, 'before' => '', 'name' => 'Tail Plain',
					'name_code' => 'tail_plain'],
			],
		]);
		$deferred = $this->subject->takeDeferred();

		$this->assertSame([100, 101], array_keys($deferred));
		$this->assertStringContainsString(
			'<menu link="option=com_z">COM_DEMO_MENU_TAIL_LINK</menu>', $deferred[100]
		);
		$this->assertStringContainsString(
			'<menu option="com_demo" view="tail_plain">COM_DEMO_MENU_TAIL_PLAIN</menu>',
			$deferred[101]
		);
	}

	/**
	 * The administrator entry point is stripped out of a local link.
	 *
	 * @return  array<string, array{string,string}>
	 * @since   6.1.7
	 */
	public static function links(): array
	{
		return [
			'administrator prefix' => ['administrator/index.php?option=com_x', 'option=com_x'],
			'rooted administrator prefix' => ['/administrator/index.php?option=com_x', 'option=com_x'],
			'index only' => ['index.php?option=com_x', 'option=com_x'],
			'rooted index only' => ['/index.php?option=com_x', 'option=com_x'],
			// an off-site link is left as it was written
			'external' => ['https://vdm.dev/a?b=c', 'https://vdm.dev/a?b=c'],
		];
	}

	/**
	 * A local link loses its entry point; an external one is left alone.
	 *
	 * @param   string  $link      The declared link.
	 * @param   string  $expected  What the menu must carry.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('links')]
	public function testALocalLinkLosesItsEntryPoint(string $link, string $expected): void
	{
		$menu = $this->build([
			'custommenus' => [
				['mainmenu' => 1, 'before' => 'articles', 'name' => 'A Link',
					'name_code' => 'a_link', 'link' => $link],
			],
		]);

		$this->assertStringContainsString('<menu link="' . $expected . '">', $menu);
	}

	/**
	 * The ampersands a link carries are escaped for the XML manifest.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheAmpersandsInALinkAreEscapedForTheManifest(): void
	{
		$menu = $this->build([
			'custommenus' => [
				['mainmenu' => 1, 'before' => 'articles', 'name' => 'A Link',
					'name_code' => 'a_link', 'link' => 'index.php?option=com_x&view=y'],
			],
		]);

		$this->assertStringContainsString('link="option=com_x&amp;view=y"', $menu);
		$this->assertStringNotContainsString('link="option=com_x&view=y"', $menu);
	}

	/**
	 * Every entry placed registers its label for translation.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryEntryRegistersItsLabelForTranslation(): void
	{
		$this->build([
			'custom_admin_views' => [
				['mainmenu' => 1, 'before' => 'articles', 'settings' => $this->settings('dash')],
			],
			'custommenus' => [
				['mainmenu' => 1, 'before' => 'articles', 'name' => 'No Link',
					'name_code' => 'no_link'],
			],
		]);

		$this->assertSame(
			'Dash View', $this->language()->get('adminsys', 'COM_DEMO_MENU_DASH')
		);
		$this->assertSame(
			'No Link', $this->language()->get('adminsys', 'COM_DEMO_MENU_NO_LINK')
		);
	}

	/**
	 * A component that declares no custom menus produces nothing, quietly.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentThatDeclaresNothingProducesNothing(): void
	{
		$this->assertSame('', $this->build([]));
		$this->assertSame([], $this->subject->takeDeferred());
	}

	/**
	 * Build the settings object a custom admin view carries.
	 *
	 * @param   string  $code  The view code name.
	 *
	 * @return  stdClass
	 * @since   6.1.7
	 */
	private function settings(string $code): stdClass
	{
		$settings = new stdClass();
		$settings->code = $code;
		$settings->CODE = strtoupper($code);
		$settings->name = ucfirst($code) . ' View';

		return $settings;
	}

	/**
	 * Build the menu for one component declaration.
	 *
	 * @param   array  $component  The component data to seed.
	 * @param   array  $added      The custom admin views already placed.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function build(array $component, array $added = []): string
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$seeded = new Component($data, $this->createStub(EventInterface::class));

		foreach ($component as $key => $value)
		{
			$seeded->set($key, $value);
		}

		$this->subject = $this->renderer(CustomMainMenu::class, [
			'component' => $seeded,
		]);

		return $this->subject->get(
			['adminview' => 'articles'], 'demo', 'COM_DEMO_MENU', $added
		);
	}
}
