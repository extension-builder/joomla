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
 * Two obligations meet here. Everything the compiler wrote the component's
 * name into has to defer to a placeholder again, or the record is bound to
 * the one component it was lifted out of. And nothing that merely looks like
 * that name may be touched, because a placeholder written over a coincidence
 * turns into somebody else's name the next time the component is renamed.
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
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.2.0
	 */
	private Report $report;

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
		$this->report = new Report();
		$this->load = new ExtrusionPowerLoadFixture();
		$this->load->component(3, 'comp-guid', 'demo', 1, 'VDM');
		$this->config->set('component', 3);
	}

	/**
	 * The component is named through its placeholder wherever it is named.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheComponentIsSaidThroughItsPlaceholderInEveryShapeItIsWritten(): void
	{
		$resolver = $this->resolver();

		$this->assertSame(
			'class [[[Component]]]Helper extends Helper',
			$resolver->reverse('class DemoHelper extends Helper')
		);
		$this->assertSame(
			"\$user->authorise('core.edit', 'com_[[[component]]]');",
			$resolver->reverse("\$user->authorise('core.edit', 'com_demo');")
		);
		$this->assertSame(
			'CREATE TABLE `#__[[[component]]]_address`',
			$resolver->reverse('CREATE TABLE `#__demo_address`')
		);
		$this->assertSame(
			"Text::_('COM_[[[COMPONENT]]]_SAVED')",
			$resolver->reverse("Text::_('COM_DEMO_SAVED')")
		);
	}

	/**
	 * A name is bounded by the seams of a name, not by a word boundary.
	 *
	 * A component is named in the middle of identifiers all day, so a word
	 * boundary would find none of the places that matter. What bounds a name
	 * is the edge of the text, anything that is not a letter or a digit, and
	 * the hump where a lower case run gives way to an upper case one.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAWordThatMerelyBeginsWithTheNameIsLeftAlone(): void
	{
		$said = 'a demonstration of the demoted DEMOGRAPHIC data, $demo2 and $xdemo';

		$this->assertSame($said, $this->resolver()->reverse($said));
	}

	/**
	 * A hump on both sides is a name, whatever stands around it.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testANameBetweenTwoHumpsIsStillTheName(): void
	{
		$this->assertSame(
			'class My[[[Component]]]Thing',
			$this->resolver()->reverse('class MyDemoThing')
		);
	}

	/**
	 * The language prefix is said as COM_ and the name, not as itself.
	 *
	 * The compiler reassigns the language prefix while it builds a module or
	 * a plugin, so a power carrying that placeholder would say MOD_ or PLG_
	 * there. COM_ followed by the upper case name says the same thing in
	 * every one of those places, and it is the pair JCB's own custom code
	 * extractor writes.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheLanguagePrefixIsNeverSaidAsAPlaceholderOfItsOwn(): void
	{
		$said = $this->resolver()->reverse("Text::_('COM_DEMO_SAVED')");

		$this->assertStringNotContainsString('LANG_PREFIX', $said);
		$this->assertStringStartsWith("Text::_('COM_[[[COMPONENT]]]", $said);
	}

	/**
	 * A placeholder more than one target claims names none of them.
	 *
	 * JCB ships two placeholders standing for VDM and two standing for 60,
	 * and a run that trusted them would rewrite every namespace and every
	 * small number the component holds.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAValueMoreThanOnePlaceholderClaimsIsLeftUnsaid(): void
	{
		$this->load->placeholder(20, '[[[COMPANY]]]', 'VDM');
		$this->load->placeholder(22, '[[[gitea_host_name]]]', 'VDM');
		$this->load->placeholder(27, '[[[max_execution_time]]]', '60');
		$this->load->placeholder(29, '[[[max_input_time]]]', '60');

		$said = 'use VDM\\Joomla\\Utilities; $timeout = 60;';

		$this->assertSame($said, $this->resolver()->reverse($said));
		$this->assertSame(
			'more than one placeholder stands for this same value, so reading '
			. 'it back names none of them',
			$this->report->get('unsaid.placeholder.COMPANY')
		);
		$this->assertNotNull($this->report->get('unsaid.placeholder.max_execution_time'));
	}

	/**
	 * A value that is only a number, or barely there, names nothing.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAValueTooShortOrOnlyANumberIsLeftUnsaid(): void
	{
		$this->load->placeholder(31, '[[[tiny]]]', 'ab');
		$this->load->placeholder(32, '[[[year]]]', '2026');

		$said = 'about the abbey in 2026';

		$this->assertSame($said, $this->resolver()->reverse($said));
		$this->assertSame(
			'the value is too short to be told from a coincidence in the source',
			$this->report->get('unsaid.placeholder.tiny')
		);
		$this->assertSame(
			'the value is only a number, which the source says for its own '
			. 'reasons far more often than for this one',
			$this->report->get('unsaid.placeholder.year')
		);
	}

	/**
	 * A placeholder only one target claims, and distinctive, is said.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAPlaceholderOnlyOneTargetClaimsIsSaid(): void
	{
		$this->load->placeholder(19, '[[[gitea_api_url]]]', 'https://git.vdm.dev/api/v1');

		$this->assertSame(
			'$url = "[[[gitea_api_url]]]/repos";',
			$this->resolver()->reverse('$url = "https://git.vdm.dev/api/v1/repos";')
		);
	}

	/**
	 * The longer of two overlapping values settles first.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTheLongerOfTwoOverlappingValuesSettlesFirst(): void
	{
		$this->load->placeholder(40, '[[[host]]]', 'git.vdm.dev');
		$this->load->placeholder(41, '[[[api]]]', 'git.vdm.dev/api');

		$this->assertSame(
			'[[[api]]]/v1',
			$this->resolver()->reverse('git.vdm.dev/api/v1')
		);
	}

	/**
	 * A placeholder just written is never read again as if it were source.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testWhatWasJustSaidIsNeverReadAgain(): void
	{
		$this->load->placeholder(43, '[[[nested]]]', 'Component');

		$said = $this->resolver()->reverse('the Demo thing');

		$this->assertSame('the [[[Component]]] thing', $said);
		$this->assertStringNotContainsString('[[[[[[nested]]]]]]', $said);
	}

	/**
	 * A component named too briefly to be told apart is left unsaid.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAComponentNamedTooBrieflyIsLeftUnsaid(): void
	{
		$this->load->component(4, 'brief-guid', 'cw', 1, 'VDM');
		$this->config->set('component', 4);

		$said = 'the cw thing and the Cw thing and CW';

		$this->assertSame($said, $this->resolver()->reverse($said));
		$this->assertSame(
			'the component is named too briefly to be told from a coincidence, '
			. 'so the source keeps it as it stated it',
			$this->report->get('unsaid.placeholder.component')
		);
	}

	/**
	 * Text carrying nothing to say comes back exactly as it stands.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTextCarryingNothingToSayComesBackUnchanged(): void
	{
		$resolver = $this->resolver();

		$this->assertSame('', $resolver->reverse(''));
		$this->assertSame(
			'nothing here names anything',
			$resolver->reverse('nothing here names anything')
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
			new Placeholders($this->config, $this->load, $this->report, new Source()),
			$this->report
		);
	}
}
