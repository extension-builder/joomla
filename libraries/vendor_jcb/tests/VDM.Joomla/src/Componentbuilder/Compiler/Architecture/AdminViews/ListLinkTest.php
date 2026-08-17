<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    16th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\AdminViews;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListLink;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomAdminAdded;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomAdminViewListId;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomAdminViewListLink;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DynamicButtons;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Admin List View Custom Admin Link Test.
 *
 * @since  6.1.7
 */
#[CoversClass(ListLink::class)]
#[CoversClass(CustomAdminViewListLink::class)]
#[CoversClass(CustomAdminViewListId::class)]
#[CoversClass(CustomAdminAdded::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ListLinkTest extends ArchitectureTestCase
{
	/**
	 * The list-link registry keeps its rows in discovery order.
	 *
	 * @var    CustomAdminViewListLink
	 * @since  6.1.7
	 */
	private CustomAdminViewListLink $listlink;

	/**
	 * The id-filter registry of custom admin views.
	 *
	 * @var    CustomAdminViewListId
	 * @since  6.1.7
	 */
	private CustomAdminViewListId $listid;

	/**
	 * The already-added registry of custom admin views.
	 *
	 * @var    CustomAdminAdded
	 * @since  6.1.7
	 */
	private CustomAdminAdded $added;

	/**
	 * The list toolbar button registry.
	 *
	 * @var    DynamicButtons
	 * @since  6.1.7
	 */
	private DynamicButtons $buttons;

	/**
	 * Create the focused registries shared by one test.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->listlink = new CustomAdminViewListLink();
		$this->listid = new CustomAdminViewListId();
		$this->added = new CustomAdminAdded();
		$this->buttons = new DynamicButtons();
	}

	/**
	 * An id-filtered custom admin view becomes a per-row list link.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testIdFilteredViewBecomesAListLink(): void
	{
		$subject = $this->listLink($this->component(true));
		$subject->set(['adminview' => 5], 'articles');

		$this->assertSame(
			[[
				'icon' => 'pencil',
				'link' => 'viewcost',
				'NAME' => 'VIEWCOST',
				'name' => 'View Cost',
			]],
			$this->listlink->get('articles')
		);
		$this->assertTrue($this->listid->get('viewcost'));
		$this->assertSame(5, $this->added->get('viewcost'));
		$this->assertNull($this->buttons->get('articles'));
	}

	/**
	 * A view without an id filter becomes a list toolbar button instead.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testViewWithoutIdFilterBecomesAToolbarButton(): void
	{
		$subject = $this->listLink($this->component(false));
		$subject->set(['adminview' => 5], 'articles');

		$this->assertNull($this->listlink->get('articles'));
		$this->assertNull($this->listid->get('viewcost'));
		$this->assertSame(5, $this->added->get('viewcost'));
		// the toolbar registry accumulates its buttons as a list
		$this->assertSame(
			[[
				'icon' => 'pencil',
				'link' => 'viewcost',
				'NAME' => 'VIEWCOST',
				'name' => 'View Cost',
			]],
			$this->buttons->get('articles')
		);
	}

	/**
	 * A custom admin view linked to another admin view is ignored.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testUnrelatedAdminViewIsIgnored(): void
	{
		$subject = $this->listLink($this->component(true));
		$subject->set(['adminview' => 9], 'articles');

		$this->assertNull($this->listlink->get('articles'));
		$this->assertSame([], $this->added->allActive());
	}

	/**
	 * A component without custom admin views records nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testComponentWithoutCustomAdminViewsRecordsNothing(): void
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));

		$subject = $this->listLink($component);
		$subject->set(['adminview' => 5], 'articles');

		$this->assertSame([], $this->listlink->allActive());
		$this->assertSame([], $this->added->allActive());
	}

	/**
	 * Recorded list links render an authorised and a disabled button.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testRecordedLinksRenderBothButtonStates(): void
	{
		$contentone = new ContentOne();
		$contentone->set('COMPONENT', 'DEMO');

		$subject = $this->listLink($this->component(true), $contentone);
		$subject->set(['adminview' => 5], 'articles');

		$buttons = $subject->getButtons('articles', '&ref=articles');

		$this->assertStringContainsString(
			"<?php if (\$canDo->get('viewcost.access')): ?>",
			$buttons
		);
		$this->assertStringContainsString(
			'href="index.php?option=com_demo&view=viewcost&id=<?php echo $item->id; ?>&ref=articles"',
			$buttons
		);
		$this->assertStringContainsString(
			'Joomla__' . '_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_(\'COM_DEMO_VIEWCOST\')',
			$buttons
		);
		$this->assertStringContainsString(
			'<a class="hasTooltip btn btn-mini disabled" href="#"',
			$buttons
		);
		$this->assertStringContainsString('<span class="icon-pencil"></span>', $buttons);
		$this->assertStringStartsWith(PHP_EOL . "\t\t\t" . '<div class="btn-group">', $buttons);
		$this->assertStringEndsWith(PHP_EOL . "\t\t\t" . '</div>', $buttons);
	}

	/**
	 * A list view without recorded links renders no buttons.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testListViewWithoutLinksRendersNoButtons(): void
	{
		$subject = $this->listLink($this->component(true));

		$this->assertSame('', $subject->getButtons('articles'));
	}

	/**
	 * Create a component registry holding one custom admin view.
	 *
	 * @param   bool  $withIdFilter  Give the view's main get an id filter.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function component(bool $withIdFilter): Component
	{
		$settings = new \stdClass();
		$settings->code = 'viewcost';
		$settings->CODE = 'VIEWCOST';
		$settings->name = 'View Cost';

		$mainGet = new \stdClass();
		$mainGet->filter = $withIdFilter
			? [['filter_type' => 1, 'state_key' => '$id']]
			: [['filter_type' => 2, 'state_key' => 'catid']];
		$settings->main_get = $mainGet;

		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));
		$component->set('custom_admin_views', [[
			'adminviews' => [5],
			'icomoon' => 'pencil',
			'settings' => $settings,
		]]);

		return $component;
	}

	/**
	 * Create the list-link service with the shared focused registries.
	 *
	 * @param   Component        $component    The component registry.
	 * @param   ContentOne|null  $contentone   The global content registry.
	 *
	 * @return  ListLink
	 * @since   6.1.7
	 */
	private function listLink(Component $component, ?ContentOne $contentone = null): ListLink
	{
		return new ListLink(
			$this->config(),
			$component,
			$contentone ?? new ContentOne(),
			$this->listlink,
			$this->listid,
			$this->added,
			$this->buttons
		);
	}
}
