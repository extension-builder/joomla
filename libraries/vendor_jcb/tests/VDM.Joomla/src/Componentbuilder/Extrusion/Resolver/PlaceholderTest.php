<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    4th September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Placeholders;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Placeholder;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
use VDM\Tests\Support\TestCase;


/**
 * A component's own name, said through the placeholder that stands for it.
 *
 * Two obligations meet here. Everywhere the compiler writes the component's
 * name has to defer to a placeholder again, or the record is bound to the one
 * component it was lifted out of. And nothing else may be touched at all:
 * every other run of those same letters is the source's own, and a
 * placeholder written over one of them turns into somebody else's name the
 * next time the component is renamed.
 *
 * @since  6.2.0
 */
#[CoversClass(Placeholder::class)]
#[UsesClass(Config::class)]
#[UsesClass(Placeholders::class)]
#[UsesClass(Report::class)]
final class PlaceholderTest extends TestCase
{
	/**
	 * The shared run configuration.
	 *
	 * @var    Config
	 * @since  6.2.0
	 */
	private Config $config;

	/**
	 * The served database boundary.
	 *
	 * @var    ExtrusionPowerLoadFixture
	 * @since  6.2.0
	 */
	private ExtrusionPowerLoadFixture $load;

	/**
	 * Start every case from a component named demo.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->config = new Config();
		$this->load = new ExtrusionPowerLoadFixture();
		$this->load->component(3, 'comp-guid', 'demo', 1, 'VDM');
		$this->config->set('component', 3);
	}

	/**
	 * The extension element every option and folder is named by.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheExtensionElementIsSaidThroughThePlaceholder(): void
	{
		$this->assertSame(
			"\$user->authorise('core.edit', 'com_[[[component]]]');",
			$this->resolver()->reverse("\$user->authorise('core.edit', 'com_demo');")
		);
		$this->assertSame(
			'index.php?option=com_[[[component]]]&view=addresses',
			$this->resolver()->reverse('index.php?option=com_demo&view=addresses')
		);
	}

	/**
	 * The prefix of every table the component keeps its records in.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheTablePrefixIsSaidThroughThePlaceholder(): void
	{
		$this->assertSame(
			'CREATE TABLE `#__[[[component]]]_address`',
			$this->resolver()->reverse('CREATE TABLE `#__demo_address`')
		);
	}

	/**
	 * The language prefix, said as COM_ and the name rather than as itself.
	 *
	 * The compiler reassigns lang_prefix while it builds a module or a plugin,
	 * so a power carrying that placeholder would say MOD_ or PLG_ there. COM_
	 * followed by the upper case name says the same thing in every one of
	 * those places, and it is the pair JCB's own custom code extractor writes.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheLanguagePrefixIsSaidAsComAndTheName(): void
	{
		$said = $this->resolver()->reverse("Text::_('COM_DEMO_SAVED')");

		$this->assertSame("Text::_('COM_[[[COMPONENT]]]_SAVED')", $said);
		$this->assertStringNotContainsString('LANG_PREFIX', $said);
	}

	/**
	 * The component's own helper class, and its namespace segment.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheHelperAndTheNamespaceSegmentAreSaidThroughTheirPlaceholders(): void
	{
		$this->assertSame(
			'class [[[Component]]]Helper extends Helper',
			$this->resolver()->reverse('class DemoHelper extends Helper')
		);
		$this->assertSame(
			'use VDM\\Component\\[[[ComponentNamespace]]]\\Administrator\\Helper\\[[[Component]]]Helper;',
			$this->resolver()->reverse(
				'use VDM\\Component\\Demo\\Administrator\\Helper\\DemoHelper;'
			)
		);
	}

	/**
	 * Everywhere else those letters stand, they are the source's own.
	 *
	 * The name is only given back where the compiler itself put it, so a
	 * component named demo keeps its demonstrations, its demoted rows, and the
	 * example address in a form's hint.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNothingButThoseIdiomsIsTouched(): void
	{
		$said = 'a demonstration of the demoted DEMOGRAPHIC data, com_democracy, '
			. 'COM_DEMOGRAPHIC, hint="demo@example.com" and $mode = \'demo\';';

		$this->assertSame($said, $this->resolver()->reverse($said));
	}

	/**
	 * A value a person defined for themselves is never touched.
	 *
	 * The compiler substitutes those as unconditionally as it substitutes the
	 * component's name, but only the person knows where they meant them, and a
	 * run that acted on that would be guessing. JCB ships two placeholders
	 * standing for VDM and two standing for 60.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAValueAPersonDefinedIsNeverTouched(): void
	{
		$this->load->placeholder(20, '[[[COMPANY]]]', 'VDM');
		$this->load->placeholder(22, '[[[gitea_host_name]]]', 'VDM');
		$this->load->placeholder(27, '[[[max_execution_time]]]', '60');
		$this->load->placeholder(19, '[[[gitea_api_url]]]', 'https://git.vdm.dev/api/v1');

		$said = 'use VDM\\Joomla\\Utilities; $timeout = 60; '
			. '$url = "https://git.vdm.dev/api/v1";';

		$this->assertSame($said, $this->resolver()->reverse($said));
	}

	/**
	 * A run with no component to name says nothing at all.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testARunWithNoComponentToNameSaysNothing(): void
	{
		$this->config->set('component', 0);
		$said = 'class DemoHelper {} com_demo #__demo_address COM_DEMO';

		$this->assertSame($said, $this->resolver()->reverse($said));
		$this->assertSame('', $this->resolver()->reverse(''));
	}

	/**
	 * A short name is still safe, because every idiom is anchored.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAShortNameIsStillSafeBecauseTheIdiomsAreAnchored(): void
	{
		$this->load->component(4, 'brief-guid', 'cw', 1, 'VDM');
		$this->config->set('component', 4);

		$this->assertSame(
			'com_[[[component]]] but not cworks, cw or Cwikipedia',
			$this->resolver()->reverse('com_cw but not cworks, cw or Cwikipedia')
		);
	}

	/**
	 * The resolver over the current boundary.
	 *
	 * @return  Placeholder  The resolver.
	 * @since   6.2.0
	 */
	private function resolver(): Placeholder
	{
		return new Placeholder(
			new Placeholders($this->config, $this->load, new Report(), new Source())
		);
	}
}
