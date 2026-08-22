<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    22nd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Powers\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Existing;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Namespacer;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Placeholders;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
use VDM\Tests\Support\TestCase;


/**
 * The resolvers that turn namespaces into identities and back.
 *
 * Three obligations meet here. The placeholder values must come from exactly
 * the places the compiler takes them, in the same order of authority. The
 * namespace conversions must be the exact inverse of what the compiler
 * unfolds, or a harvested class would land in a different place than the
 * power it claims to be. And recognising an existing power must survive every
 * storage form a namespace legitimately takes.
 *
 * @since  6.1.7
 */
#[CoversClass(Existing::class)]
#[CoversClass(Namespacer::class)]
#[CoversClass(Placeholders::class)]
#[UsesClass(Config::class)]
#[UsesClass(Report::class)]
final class ResolverTest extends TestCase
{
	/**
	 * The shared run configuration.
	 *
	 * @var    Config
	 * @since  6.1.7
	 */
	private Config $config;

	/**
	 * The served database boundary.
	 *
	 * @var    ExtrusionPowerLoadFixture
	 * @since  6.1.7
	 */
	private ExtrusionPowerLoadFixture $load;

	/**
	 * The run report.
	 *
	 * @var    Report
	 * @since  6.1.7
	 */
	private Report $report;

	/**
	 * Compose fresh state for every test.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->config = new Config();
		$this->load = new ExtrusionPowerLoadFixture();
		$this->report = new Report();
	}

	/**
	 * A component that carries its own prefix supplies both values.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithItsOwnPrefixSuppliesBothValues(): void
	{
		$this->load->component(3, 'comp-guid', 'componentbuilder', 1, 'VDM');
		$this->config->set('component', 3);
		$placeholders = $this->placeholders();

		$this->assertSame('VDM', $placeholders->prefix());
		$this->assertSame('Componentbuilder', $placeholders->component());
		$this->assertSame(
			[
				'[[[NamespacePrefix]]]' => 'VDM',
				'[[[ComponentNamespace]]]' => 'Componentbuilder'
			],
			$placeholders->map()
		);
	}

	/**
	 * A component that declines its own prefix falls back to the global one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutItsOwnPrefixFallsBackToTheGlobalOne(): void
	{
		$this->load->component(3, 'comp-guid', 'demo comp', 0, 'Ignored');
		$this->load->params(['namespace_prefix' => 'Acme']);
		$this->config->set('component', 3);
		$placeholders = $this->placeholders();

		$this->assertSame('Acme', $placeholders->prefix());
		$this->assertSame(
			'Democomp',
			$placeholders->component(),
			'The compiler derivation folds the code name into one raised segment.'
		);
	}

	/**
	 * With no component and no configuration the platform default stands.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithNothingConfiguredThePlatformDefaultStands(): void
	{
		$placeholders = $this->placeholders();

		$this->assertSame('JCB', $placeholders->prefix());
		$this->assertSame('', $placeholders->component());
		$this->assertSame(['[[[NamespacePrefix]]]' => 'JCB'], $placeholders->map());
	}

	/**
	 * A missing component row is reported, not silently ignored.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAMissingComponentRowIsReported(): void
	{
		$this->config->set('component', 9);
		$this->placeholders()->prefix();

		$this->assertSame(9, $this->report->get('powers.failed.component'));
	}

	/**
	 * The component's own placeholder overrides outrank everything.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testComponentOverridesOutrankEverything(): void
	{
		$this->load->component(3, 'comp-guid', 'componentbuilder', 1, 'VDM');
		$this->load->overrides('comp-guid', [
			['target' => '[[[NamespacePrefix]]]', 'value' => 'Custom'],
			['target' => '###ComponentNamespace###', 'value' => 'Rebuilt'],
			['target' => 'Unrelated', 'value' => 'Noise']
		]);
		$this->config->set('component', 3);
		$placeholders = $this->placeholders();

		$this->assertSame('Custom', $placeholders->prefix());
		$this->assertSame('Rebuilt', $placeholders->component());
	}

	/**
	 * Folding a class's location back into the stored dot form.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testStoredFoldsTheLocationBackIntoTheDotForm(): void
	{
		$namespacer = $this->namespacer();

		$this->assertSame(
			'VDM\Joomla\Data.Action.Load',
			$namespacer->stored('VDM\Joomla\Data\Action', 'Load', ['Data', 'Action'])
		);
		$this->assertSame(
			'VDM\Joomla\Load',
			$namespacer->stored('VDM\Joomla', 'Load', []),
			'A file directly below src has no dot part at all.'
		);
		$this->assertNull(
			$namespacer->stored('VDM\Joomla\Data\Action', 'Load', ['Other', 'Place']),
			'A path that does not mirror the namespace has no seam to fold at.'
		);
		$this->assertNull($namespacer->stored('', 'Load', []));
	}

	/**
	 * The convention folds the first two segments when the path cannot say.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testConventionalFoldsAfterTheFirstTwoSegments(): void
	{
		$namespacer = $this->namespacer();

		$this->assertSame(
			'VDM\Joomla\Data.Action.Load',
			$namespacer->conventional('VDM\Joomla\Data\Action', 'Load')
		);
		$this->assertSame(
			'VDM\Joomla\Load',
			$namespacer->conventional('VDM\Joomla', 'Load')
		);
		$this->assertSame(
			'Solo\Load',
			$namespacer->conventional('Solo', 'Load')
		);
	}

	/**
	 * Deferring the resolved values back to their placeholders.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testPlaceholderizeDefersTheResolvedValues(): void
	{
		$this->load->component(3, 'comp-guid', 'componentbuilder', 1, 'VDM');
		$this->config->set('component', 3);
		$namespacer = $this->namespacer();

		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Package.Readme.Item',
			$namespacer->placeholderize('VDM\Joomla\Componentbuilder.Package.Readme.Item')
		);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\Data.Action.Load',
			$namespacer->placeholderize('VDM\Joomla\Data.Action.Load')
		);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\Data.Componentbuilder',
			$namespacer->placeholderize('VDM\Joomla\Data.Componentbuilder'),
			'The final dot part is the class itself, never a placeholder.'
		);
		$this->assertSame(
			'Other\Joomla\Data.Load',
			$namespacer->placeholderize('Other\Joomla\Data.Load'),
			'A prefix that is not the resolved one stays as written.'
		);
	}

	/**
	 * Unfolding a stored namespace into the class it compiles to.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testResolveUnfoldsEveryStoredForm(): void
	{
		$this->load->component(3, 'comp-guid', 'componentbuilder', 1, 'VDM');
		$this->config->set('component', 3);
		$namespacer = $this->namespacer();

		$this->assertSame(
			'VDM\Joomla\Componentbuilder\Package\Readme\Item',
			$namespacer->resolve('[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Package.Readme.Item')
		);
		$this->assertSame(
			'VDM\Joomla\Data\Action\Load',
			$namespacer->resolve('###NamespacePrefix###\Joomla\Data.Action.Load'),
			'The hashed placeholder form resolves the same way.'
		);
		$this->assertSame(
			'VDM\Joomla\Data\Load',
			$namespacer->resolve('VDM\Joomla\Data\Load'),
			'A namespace stored without placeholders or dots is already real.'
		);
		$this->assertSame(
			'',
			$namespacer->resolve('[[[Unknowable]]]\Joomla\Data.Load'),
			'A placeholder with no value cannot be matched, and must say so.'
		);
	}

	/**
	 * The catalogue recognises a class under every legitimate storage form.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheCatalogueRecognisesEveryStorageForm(): void
	{
		$this->load->component(3, 'comp-guid', 'componentbuilder', 1, 'VDM');
		$this->config->set('component', 3);
		$this->load
			->power(1, 'aaaaaaaa-1111-4111-8111-111111111111', 'Load',
				'[[[NamespacePrefix]]]\Joomla\Data.Action.Load')
			->power(2, 'bbbbbbbb-2222-4222-8222-222222222222', 'Item',
				'[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Package.Item')
			->power(3, 'cccccccc-3333-4333-8333-333333333333', 'Raw',
				'Other\Joomla\Raw');
		$existing = $this->existing();

		$this->assertSame(3, $existing->count());
		$this->assertSame(
			'aaaaaaaa-1111-4111-8111-111111111111',
			$existing->find('VDM\Joomla\Data\Action\Load')['guid'] ?? null
		);
		$this->assertSame(
			'bbbbbbbb-2222-4222-8222-222222222222',
			$existing->find('vdm\joomla\componentbuilder\package\item')['guid'] ?? null,
			'Recognition is case-insensitive, as PHP class names are.'
		);
		$this->assertSame(
			'cccccccc-3333-4333-8333-333333333333',
			$existing->find('Other\Joomla\Raw')['guid'] ?? null
		);
		$this->assertNull($existing->find('VDM\Joomla\Never\Stored'));
	}

	/**
	 * A namespace this run cannot resolve is reported and left unmatchable.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnUnresolvableNamespaceIsReportedNotMatched(): void
	{
		$this->load->power(1, 'aaaaaaaa-1111-4111-8111-111111111111', 'Ghost',
			'[[[SomeOtherPlaceholder]]]\Joomla\Ghost');
		$existing = $this->existing();

		$this->assertSame(0, $existing->count());
		$this->assertSame(
			'[[[SomeOtherPlaceholder]]]\Joomla\Ghost',
			$this->report->get(
				'powers.unresolved.namespace.aaaaaaaa_1111_4111_8111_111111111111'
			)
		);
	}

	/**
	 * Two powers claiming one class keep the first and report the second.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testADuplicateClaimKeepsTheFirstAndReportsTheSecond(): void
	{
		$this->load
			->power(1, 'aaaaaaaa-1111-4111-8111-111111111111', 'Load', 'JCB\Joomla\Data.Load')
			->power(2, 'bbbbbbbb-2222-4222-8222-222222222222', 'Load', 'JCB\Joomla\Data\Load');
		$existing = $this->existing();

		$this->assertSame(1, $existing->count());
		$this->assertSame(
			'aaaaaaaa-1111-4111-8111-111111111111',
			$existing->find('JCB\Joomla\Data\Load')['guid'] ?? null
		);
		$this->assertSame(
			'JCB\Joomla\Data\Load',
			$this->report->get(
				'powers.duplicate.namespace.bbbbbbbb_2222_4222_8222_222222222222'
			)
		);
	}

	/**
	 * The placeholders resolver under test.
	 *
	 * @return  Placeholders  The resolver.
	 * @since   6.1.7
	 */
	private function placeholders(): Placeholders
	{
		return new Placeholders($this->config, $this->load, $this->report);
	}

	/**
	 * The namespacer under test.
	 *
	 * @return  Namespacer  The resolver.
	 * @since   6.1.7
	 */
	private function namespacer(): Namespacer
	{
		return new Namespacer($this->placeholders());
	}

	/**
	 * The existing power resolver under test.
	 *
	 * @return  Existing  The resolver.
	 * @since   6.1.7
	 */
	private function existing(): Existing
	{
		return new Existing($this->load, $this->namespacer(), $this->report);
	}
}
