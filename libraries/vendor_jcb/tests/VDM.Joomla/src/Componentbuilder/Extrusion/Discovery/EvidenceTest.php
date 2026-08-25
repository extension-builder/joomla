<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    25th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Discovery;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Access;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Mvc;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Scanner;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Screen;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Selector;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaFive;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaFour;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaSix;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaThree;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Php\Methods;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Tests\Support\ExtrusionComponentFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * What a compiled component states about itself, read from its own code.
 *
 * @since  6.1.8
 */
#[CoversClass(Mvc::class)]
#[CoversClass(Screen::class)]
#[CoversClass(Access::class)]
final class EvidenceTest extends FilesystemTestCase
{
	/**
	 * The run configuration.
	 *
	 * @var    Config
	 * @since  6.1.8
	 */
	private Config $config;

	/**
	 * The source identity registry.
	 *
	 * @var    Source
	 * @since  6.1.8
	 */
	private Source $source;

	/**
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.1.8
	 */
	private Report $report;

	/**
	 * Start every test from untouched run registries.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->config = new Config();
		$this->source = new Source();
		$this->report = new Report();

		$this->source->set('code_name', 'com_example');
		$this->source->set('layout', 'J4');
	}

	/**
	 * The controllers say which screen is a table view's list, and which is its own.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testTheControllersPairEachListScreenWithTheViewItServes(): void
	{
		$root = $this->compiledRoot();

		$this->assertSame(3, (new Mvc(
			$this->scanner(),
			$this->selector(),
			$this->source,
			$this->report,
			new Methods()
		))->establish($root));

		$this->assertSame(
			'itemsall',
			$this->source->get('mvc_list.item'),
			'A controller answering with another view\'s model is that view\'s list '
			. 'screen, whatever the folder is called -- which is the only place a '
			. 'component states its plural name.'
		);
		$this->assertSame('item', $this->source->get('mvc_of.itemsall'));
		$this->assertNull(
			$this->source->get('mvc_of.dashboard'),
			'A screen whose controller answers with its own model belongs to no '
			. 'other view.'
		);
		$this->assertNull($this->source->get('mvc_of.item'));
		$this->assertSame('FormController', $this->source->get('mvc.item.extends'));
		$this->assertSame('AdminController', $this->source->get('mvc.itemsall.extends'));
	}

	/**
	 * The edit screen states its tabs, and where each field stands.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testTheEditScreenStatesItsTabsAndTheirFields(): void
	{
		$root = $this->compiledRoot();

		$this->assertSame(1, $this->screen()->establish($root));

		$this->assertSame(2, $this->source->get('screen.item.tab_count'));
		$this->assertSame('details', $this->source->get('screen.item.tabs.0.key'));
		$this->assertSame(
			'COM_EXAMPLE_ITEM_DETAILS',
			$this->source->get('screen.item.tabs.0.label'),
			'The label is carried as the screen states it; turning a constant into '
			. 'its English is the language resolver\'s work, not this reader\'s.'
		);
		$this->assertSame('metrics', $this->source->get('screen.item.tabs.1.key'));

		$this->assertSame(
			['tab' => 'details', 'alignment' => 1, 'order' => 1, 'generated' => 0],
			$this->source->get('screen.item.place.name')
		);
		$this->assertSame(
			['tab' => 'details', 'alignment' => 1, 'order' => 2, 'generated' => 0],
			$this->source->get('screen.item.place.alias')
		);
		$this->assertSame(
			['tab' => 'details', 'alignment' => 2, 'order' => 1, 'generated' => 0],
			$this->source->get('screen.item.place.description')
		);
		$this->assertSame(
			['tab' => 'metrics', 'alignment' => 3, 'order' => 1, 'generated' => 0],
			$this->source->get('screen.item.place.counter')
		);
	}

	/**
	 * A section the compiler generates is not a tab of the view's own.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testAGeneratedSectionCountsItsFieldsButIsNoTabOfItsOwn(): void
	{
		$this->screen()->establish($this->compiledRoot());

		foreach (['guid', 'published'] as $column)
		{
			$placement = $this->source->get('screen.item.place.' . $column);

			$this->assertSame('publishing', $placement['tab']);
			$this->assertSame(
				1,
				$placement['generated'],
				'The publishing section is the compiler\'s own; JCB puts these '
				. 'fields back itself, so the tab is not stored twice.'
			);
		}

		$keys = [];
		$number = 0;

		while (($key = $this->source->get('screen.item.tabs.' . $number . '.key')) !== null)
		{
			$keys[] = $key;
			$number++;
		}

		$this->assertSame(['details', 'metrics'], $keys);
	}

	/**
	 * A tab holding markup of its own is a tab someone added by hand.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testATabWithItsOwnMarkupIsRecoveredAsACustomTab(): void
	{
		$this->screen()->establish($this->compiledRoot());

		$this->assertSame(1, $this->source->get('screen.item.custom_count'));
		$this->assertSame('notes', $this->source->get('screen.item.custom.0.key'));
		$this->assertSame(
			2,
			$this->source->get('screen.item.custom.0.after'),
			'It stands after the tabs the view states before it, which is where '
			. 'the screen shows it.'
		);
		$this->assertStringContainsString(
			'a note the author wrote',
			(string) $this->source->get('screen.item.custom.0.html')
		);
		$this->assertNull(
			$this->source->get('screen.item.custom.1.key'),
			'The permissions section is the rules field, which JCB adds itself.'
		);
	}

	/**
	 * The access rules state each permission at the level the component offers it.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testTheAccessRulesAreReadSectionBySection(): void
	{
		$root = $this->compiledRoot();

		$this->assertSame(2, (new Access(
			$this->scanner(),
			$this->selector(),
			$this->source,
			$this->report
		))->establish($root));

		$this->assertSame(
			['core.admin', 'item.access', 'item.batch', 'item.edit', 'other.edit'],
			$this->source->get('access.component')
		);
		$this->assertSame(
			['item.edit', 'core.delete'],
			$this->source->get('access.item')
		);
		$this->assertSame(2, $this->report->get('source.access_sections'));
	}

	/**
	 * A tree with none of this evidence is read without complaint.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testATreeStatingNoneOfThisIsReadWithoutComplaint(): void
	{
		$root = $this->root('plain', ExtrusionComponentFixture::modern());

		$this->assertSame(0, (new Mvc(
			$this->scanner(),
			$this->selector(),
			$this->source,
			$this->report,
			new Methods()
		))->establish($root));
		$this->assertSame(0, $this->screen()->establish($root));
		$this->assertSame(0, (new Access(
			$this->scanner(),
			$this->selector(),
			$this->source,
			$this->report
		))->establish($root));

		$this->assertNull($this->source->get('mvc_list.item'));
		$this->assertNull($this->source->get('screen.item.tab_count'));
		$this->assertNull($this->source->get('access.component'));
	}

	/**
	 * The root of the compiled component fixture.
	 *
	 * @return  string  The resolved component root.
	 * @since   6.1.8
	 */
	private function compiledRoot(): string
	{
		return $this->root('compiled', ExtrusionComponentFixture::compiled());
	}

	/**
	 * Materialise one fixture tree and resolve the component root inside it.
	 *
	 * @param   string                $prefix  The relative tree prefix.
	 * @param   array<string,string>  $files   Relative path keyed to its contents.
	 *
	 * @return  string  The resolved component root.
	 * @since   6.1.8
	 */
	private function root(string $prefix, array $files): string
	{
		foreach ($files as $relative => $contents)
		{
			$this->writeTemporaryFile($prefix . '/' . $relative, $contents);
		}

		$root = $this->scanner()->root(
			$this->temporaryPath($prefix) . '/com_example'
		);

		$this->assertIsString($root);

		return $root;
	}

	/**
	 * A screen reader bound to the current run registries.
	 *
	 * @return  Screen  The screen reader.
	 * @since   6.1.8
	 */
	private function screen(): Screen
	{
		return new Screen(
			$this->scanner(),
			$this->selector(),
			$this->source,
			$this->report
		);
	}

	/**
	 * A scanner bound to the current run registries.
	 *
	 * @return  Scanner  The bounded scanner.
	 * @since   6.1.8
	 */
	private function scanner(): Scanner
	{
		return new Scanner($this->config, $this->report);
	}

	/**
	 * A selector carrying all four target-version layouts.
	 *
	 * @return  Selector  The layout selector.
	 * @since   6.1.8
	 */
	private function selector(): Selector
	{
		return new Selector(
			$this->config,
			$this->source,
			new JoomlaThree(),
			new JoomlaFour(),
			new JoomlaFive(),
			new JoomlaSix()
		);
	}
}
