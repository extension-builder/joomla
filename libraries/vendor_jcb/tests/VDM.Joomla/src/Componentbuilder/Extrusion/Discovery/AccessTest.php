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
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Scanner;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Selector;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaFive;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaFour;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaSix;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaThree;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Tests\Support\ExtrusionComponentFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * What a component's own access rules state about its permissions.
 *
 * access.xml is a file every Joomla component ships, whoever built it, and it
 * states each permission at the level the component offers it. That is the
 * whole of what is read here: nothing about the shape of a component's compiled
 * output, which only a component JCB itself built would have.
 *
 * @since  6.1.8
 */
#[CoversClass(Access::class)]
final class AccessTest extends FilesystemTestCase
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
	 * The access rules state each permission at the level the component offers it.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testTheAccessRulesAreReadSectionBySection(): void
	{
		$this->assertSame(2, $this->access()->establish($this->compiledRoot()));

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
	 * Every action names the screen it belongs to, which is a screen list of its own.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testEveryActionNamesTheScreenItBelongsTo(): void
	{
		$this->access()->establish($this->compiledRoot());

		$this->assertTrue((bool) $this->source->get('access_screens.item'));
		$this->assertTrue((bool) $this->source->get('access_screens.other'));
		$this->assertNull(
			$this->source->get('access_screens.core'),
			'core is Joomla\'s own prefix, never a screen of the component.'
		);
		$this->assertTrue(
			(bool) $this->source->get('access_screens_actions.item.batch')
		);
		$this->assertSame(
			'ITEM_ACCESS',
			$this->source->get('access_titles.item.access'),
			'The title names the constant a rule is worded under, which is the source\'s own statement of the screen\'s list.'
		);
	}

	/**
	 * A tree shipping no access rules is read without complaint.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testATreeShippingNoRulesIsReadWithoutComplaint(): void
	{
		$root = $this->root('plain', ExtrusionComponentFixture::modern());

		$this->assertSame(0, $this->access()->establish($root));
		$this->assertNull($this->source->get('access.component'));
	}

	/**
	 * The root of the fixture shipping access rules.
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
	 * An access reader bound to the current run registries.
	 *
	 * @return  Access  The access reader.
	 * @since   6.1.8
	 */
	private function access(): Access
	{
		return new Access(
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
