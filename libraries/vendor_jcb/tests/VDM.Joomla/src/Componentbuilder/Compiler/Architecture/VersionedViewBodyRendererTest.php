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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Templatelayout\Data as TemplatelayoutData;


/**
 * Generated admin list-view body contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedViewBodyRendererTest extends ArchitectureTestCase
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
	 * The default body wraps the list table in the admin form for every target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testDefaultBodyWrapsTheListTableInTheAdminForm(string $version, int $major): void
	{
		$subject = $this->viewBody($version);
		$body = $subject->getDefault('article', 'articles');

		$this->assertStringContainsString(
			'<form action="<?php echo Joomla__' . '_d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('
			. "'index.php?option=com_demo&view=articles'); ?>\" method=\"post\" "
			. 'name="adminForm" id="adminForm">',
			$body
		);
		$this->assertStringContainsString('<table class="table table-striped" id="articleList">', $body);
		$this->assertStringContainsString("<thead><?php echo \$this->loadTemplate('head');?></thead>", $body);
		$this->assertStringContainsString("<tfoot><?php echo \$this->loadTemplate('foot');?></tfoot>", $body);
		$this->assertStringContainsString("<tbody><?php echo \$this->loadTemplate('body');?></tbody>", $body);
		$this->assertStringContainsString(
			'<?php echo Joomla__' . "_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>",
			$body
		);
		$this->assertStringEndsWith('</form>', $body);
	}

	/**
	 * Only Joomla 3 renders the sidebar container and the batch modal.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testDefaultBodyContainerAndBatchFollowTheTarget(string $version, int $major): void
	{
		$subject = $this->viewBody($version);
		$body = $subject->getDefault('article', 'articles');

		if ($major === 3)
		{
			$this->assertStringContainsString('<?php if(!empty( $this->sidebar)): ?>', $body);
			$this->assertStringContainsString('<div id="j-sidebar-container" class="span2">', $body);
			$this->assertStringContainsString('<div id="j-main-container" class="span10">', $body);
			$this->assertStringContainsString("'bootstrap.renderModal',", $body);
			$this->assertStringContainsString('COM_DEMO_ARTICLES_BATCH_OPTIONS', $body);
			$this->assertStringContainsString("\$this->loadTemplate('batch_body')", $body);

			return;
		}

		$this->assertStringNotContainsString('j-sidebar-container', $body);
		$this->assertStringNotContainsString('bootstrap.renderModal', $body);
		$this->assertStringNotContainsString('BATCH_OPTIONS', $body);
		$this->assertStringContainsString("\t" . '<div id="j-main-container">', $body);
	}

	/**
	 * The sidebar filter type adds the ordering script and hidden inputs.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSidebarFilterAddsOrderingScriptAndInputs(): void
	{
		$subject = $this->viewBody('JoomlaSix');
		$body = $subject->getDefault('article', 'articles');

		$this->assertStringStartsWith('<script type="text/javascript">', $body);
		$this->assertStringContainsString('Joomla.orderTable = function()', $body);
		$this->assertStringContainsString("Joomla.tableOrdering(order, dirn, '');", $body);
		$this->assertStringContainsString("<?php echo \$this->loadTemplate('toolbar');?>", $body);
		$this->assertStringContainsString('name="filter_order"', $body);
		$this->assertStringContainsString('name="filter_order_Dir"', $body);
		$this->assertStringNotContainsString('searchtools', $body);
	}

	/**
	 * The top-bar filter type renders the search tools instead.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTopBarFilterRendersTheSearchTools(): void
	{
		$filterType = new AdminFilterType();
		$filterType->set('articles', 2);

		$subject = $this->viewBody('JoomlaSix', $filterType);
		$body = $subject->getDefault('article', 'articles');

		$this->assertStringContainsString(
			"echo Joomla__" . "_7ab82272_0b3d_4bb1_af35_e63a096cfe0b___Power::render('joomla.searchtools.default', "
			. "array('view' => \$this));",
			$body
		);
		$this->assertStringNotContainsString('Joomla.orderTable', $body);
		$this->assertStringNotContainsString("loadTemplate('toolbar')", $body);
		$this->assertStringNotContainsString('filter_order_Dir', $body);
	}

	/**
	 * A known template layout adds the trash helper before the search tools.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testKnownTemplateLayoutAddsTheTrashHelper(): void
	{
		$filterType = new AdminFilterType();
		$filterType->set('articles', 2);

		$layout = $this->getMockBuilder(TemplatelayoutData::class)
			->disableOriginalConstructor()
			->onlyMethods(['set'])
			->getMock();
		$layout->expects($this->once())
			->method('set')
			->with($this->stringContains('trashhelper'), 'articles')
			->willReturn(true);

		$subject = $this->viewBody('JoomlaSix', $filterType, $layout);
		$body = $subject->getDefault('article', 'articles');

		$this->assertStringContainsString('// Add the trash helper layout', $body);
		$this->assertStringContainsString(
			"echo Joomla__" . "_7ab82272_0b3d_4bb1_af35_e63a096cfe0b___Power::render('trashhelper', \$this);",
			$body
		);
	}

	/**
	 * The modal body targets the modal layout and omits the batch modal.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testModalBodyTargetsTheModalLayout(string $version, int $major): void
	{
		$subject = $this->viewBody($version);
		$body = $subject->getModal('article', 'articles');

		$this->assertStringContainsString(
			"&layout=modal&tmpl=component&titleKey=' . \$this->getModalTitleKey()); ?>\"",
			$body
		);
		$this->assertStringContainsString("\t" . '<div id="j-main-container">', $body);
		$this->assertStringContainsString('<table class="table table-striped" id="articleList">', $body);
		// the modal body never renders the sidebar, toolbar or batch modal
		$this->assertStringNotContainsString('j-sidebar-container', $body);
		$this->assertStringNotContainsString("loadTemplate('toolbar')", $body);
		$this->assertStringNotContainsString('bootstrap.renderModal', $body);
		$this->assertStringNotContainsString('filter_order', $body);
	}

	/**
	 * The default body triggers its four extension events in source order.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testDefaultBodyTriggersItsEventsInOrder(): void
	{
		$seen = [];
		$subject = $this->viewBody('JoomlaSix', null, null, $this->recordingEvent($seen));
		$subject->getDefault('article', 'articles');

		$this->assertSame(
			[
				'jcb_ce_onSetDefaultViewsBodyTop',
				'jcb_ce_onSetDefaultViewsFormTop',
				'jcb_ce_onSetDefaultViewsFormBottom',
				'jcb_ce_onSetDefaultViewsBodyBottom',
			],
			$seen
		);
	}

	/**
	 * The modal body closes on the default-views bottom event.
	 *
	 * The trailing event name does not match its modal siblings. That is the
	 * current emitted contract and is preserved by the extraction.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testModalBodyClosesOnTheDefaultViewsBottomEvent(): void
	{
		$seen = [];
		$subject = $this->viewBody('JoomlaSix', null, null, $this->recordingEvent($seen));
		$subject->getModal('article', 'articles');

		$this->assertSame(
			[
				'jcb_ce_onSetModalViewsBodyTop',
				'jcb_ce_onSetModalViewsFormTop',
				'jcb_ce_onSetModalViewsFormBottom',
				'jcb_ce_onSetDefaultViewsBodyBottom',
			],
			$seen
		);
	}

	/**
	 * Event listeners can rewrite the body through the by-reference payload.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEventListenersCanAppendToTheBody(): void
	{
		$event = new class implements EventInterface
		{
			/**
			 * Append a marker when the body top event fires.
			 *
			 * @param   string  $event  The event name.
			 * @param   mixed   $data   The event payload.
			 *
			 * @return  void
			 * @since   6.1.7
			 */
			public function trigger(string $event, $data = null)
			{
				if ($event === 'jcb_ce_onSetDefaultViewsBodyTop')
				{
					$data[0][] = '<!-- injected by a listener -->';
				}
			}
		};

		$subject = $this->viewBody('JoomlaSix', null, null, $event);
		$body = $subject->getDefault('article', 'articles');

		$this->assertStringContainsString('<!-- injected by a listener -->', $body);
	}

	/**
	 * Create an event double that records the names it receives.
	 *
	 * @param   array  $seen  Collected event names, by reference.
	 *
	 * @return  EventInterface
	 * @since   6.1.7
	 */
	private function recordingEvent(array &$seen): EventInterface
	{
		$event = $this->createStub(EventInterface::class);
		$event->method('trigger')
			->willReturnCallback(
				static function (string $name) use (&$seen): void
				{
					$seen[] = $name;
				}
			);

		return $event;
	}

	/**
	 * Create a versioned view-body renderer.
	 *
	 * @param   string                    $version      Target namespace segment.
	 * @param   AdminFilterType|null      $filterType   The filter-type registry.
	 * @param   TemplatelayoutData|null   $layout       The template-layout double.
	 * @param   EventInterface|null       $event        The event double.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function viewBody(
		string $version,
		?AdminFilterType $filterType = null,
		?TemplatelayoutData $layout = null,
		?EventInterface $event = null
	): object
	{
		if ($layout === null)
		{
			$layout = $this->createStub(TemplatelayoutData::class);
			$layout->method('set')->willReturn(false);
		}

		return $this->renderer(
			// only Joomla 3 keeps its own container and batch modal
			$this->targetClass($version, 'AdminViews\\ViewBody', ['JoomlaThree']),
			[
				'adminfiltertype' => $filterType ?? new AdminFilterType(),
				'templatelayoutdata' => $layout,
				'event' => $event ?? $this->createStub(EventInterface::class),
			]
		);
	}
}
