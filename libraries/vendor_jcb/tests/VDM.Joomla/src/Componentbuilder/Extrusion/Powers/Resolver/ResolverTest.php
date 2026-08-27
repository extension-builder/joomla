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
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
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
	 * A dotted library folder states its own head, however long it runs.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testTheLibraryFolderStatesHowManySegmentsTheHeadKeeps(): void
	{
		$namespacer = $this->namespacer();

		$this->assertSame(
			'VDM\Joomla\Openai\Chat.Request',
			$namespacer->stored(
				'VDM\Joomla\Openai\Chat', 'Request', ['Chat'], 'VDM.Joomla.Openai'
			),
			'Three dots in the folder name are three segments of head, which no '
			. 'count of folders below src could have said.'
		);
		$this->assertSame(
			'Acme\Db\Deep.Query',
			$namespacer->stored('Acme\Db\Deep', 'Query', ['Deep'], 'Acme'),
			'A folder name carrying no dots states nothing, so the path answers.'
		);
		$this->assertSame(
			'VDM\Joomla\Data.Action.Load',
			$namespacer->stored(
				'VDM\Joomla\Data\Action', 'Load', ['Data', 'Action'], 'Other.Name'
			),
			'A folder name the namespace does not open with is not its head.'
		);
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
	 * A library that states its head has stated its prefix along with it.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testALibraryStatingItsHeadHasItsPrefixDeferred(): void
	{
		$this->load->component(3, 'comp-guid', 'componentbuilder', 1, 'VDM');
		$this->config->set('component', 3);
		$namespacer = $this->namespacer();

		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\Data.Load',
			$namespacer->placeholderize('Other\Joomla\Data.Load', 'Other.Joomla'),
			'Deferring the prefix whatever it reads is the point: it is what '
			. 'lets one class serve components whose prefixes differ.'
		);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].File.Display',
			$namespacer->placeholderize(
				'Other\Joomla\Componentbuilder.File.Display', 'Other.Joomla'
			)
		);
		$this->assertSame(
			'Acme\Query',
			$namespacer->placeholderize('Acme\Query', 'Acme'),
			'A folder stating no head has not claimed the convention, so a '
			. 'vendor of its own is left exactly as it is.'
		);
	}

	/**
	 * A dotted folder speaks only for a namespace it actually opens.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testAFolderThatDoesNotOpenTheNamespaceDefersNothing(): void
	{
		$namespacer = $this->namespacer();

		$this->assertSame(
			'Zoo\Joomla\Abstraction.Model',
			$namespacer->placeholderize('Zoo\Joomla\Abstraction.Model', 'vdm.io'),
			'Carrying dots is not the same as naming these segments. Deferring '
			. 'on the first would fold this class onto whatever power already '
			. 'stands at that tail, and a run updating what exists would then '
			. 'write over it.'
		);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\Abstraction.Model',
			$namespacer->placeholderize('Zoo\Joomla\Abstraction.Model', 'Zoo.Joomla'),
			'A folder that does open it still speaks for it.'
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
	 * A namespace this run cannot resolve still identifies its power.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnUnresolvableNamespaceIsReportedNotMatched(): void
	{
		$this->load->power(1, 'aaaaaaaa-1111-4111-8111-111111111111', 'Ghost',
			'[[[SomeOtherPlaceholder]]]\Joomla\Ghost');
		$existing = $this->existing();

		$this->assertSame(1, $existing->count());
		$this->assertSame(
			'aaaaaaaa-1111-4111-8111-111111111111',
			$existing->match('[[[SomeOtherPlaceholder]]]\Joomla\Ghost')['guid'] ?? null,
			'A stored namespace is the identity, whether or not this run can '
			. 'say what class it becomes.'
		);
		$this->assertNull(
			$existing->find('JCB\Joomla\Ghost'),
			'What it cannot resolve, it cannot answer for by class name.'
		);
		$this->assertSame(
			'[[[SomeOtherPlaceholder]]]\Joomla\Ghost',
			$this->report->get(
				'powers.unresolved.namespace.aaaaaaaa_1111_4111_8111_111111111111'
			)
		);
	}

	/**
	 * Two powers reaching one class name keep the first and report the second.
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

		$this->assertSame(
			2,
			$existing->count(),
			'Two stored namespaces are two identities, however they resolve.'
		);
		$this->assertSame(
			'aaaaaaaa-1111-4111-8111-111111111111',
			$existing->find('JCB\Joomla\Data\Load')['guid'] ?? null
		);
		$this->assertSame(
			'JCB\Joomla\Data\Load',
			$this->report->get(
				'powers.duplicate.class.bbbbbbbb_2222_4222_8222_222222222222'
			)
		);
	}

	/**
	 * A power is the same power whatever prefix its library was built with.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testAPowerIsMatchedByItsStoredNamespaceNotItsBuiltName(): void
	{
		$this->load->power(
			1, 'aaaaaaaa-1111-4111-8111-111111111111', 'GuidHelper',
			'[[[NamespacePrefix]]]\Joomla\Utilities.GuidHelper'
		);
		$existing = $this->existing();

		$this->assertSame(
			'aaaaaaaa-1111-4111-8111-111111111111',
			$existing->match('[[[NamespacePrefix]]]\Joomla\Utilities.GuidHelper')['guid'] ?? null,
			'A class harvested out of a library built as Other\Joomla folds to '
			. 'this same stored namespace, so it is this same power -- which is '
			. 'the whole reason the prefix is deferred.'
		);
		$this->assertNull(
			$existing->match('[[[NamespacePrefix]]]\Joomla\Utilities.Other')
		);
	}

	/**
	 * A class keeps its underscores through the round trip.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAClassKeepsItsUnderscoresThroughTheRoundTrip(): void
	{
		$namespacer = $this->namespacer();

		$this->assertSame(
			'Acme\Lib\Foo_Bar',
			$namespacer->resolve('Acme\Lib.Foo_Bar'),
			'The compiler keeps underscores in the class name, so recognition must too.'
		);
		$this->assertSame(
			'Acme\Lib\Load',
			$namespacer->resolve('Ac_me\Lib.Load'),
			'A namespace segment is cleaned harder than the class, exactly as the compiler cleans it.'
		);
	}

	/**
	 * The stored form always keeps a backslash head the compiler accepts.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheStoredFormAlwaysKeepsABackslashHead(): void
	{
		$namespacer = $this->namespacer();

		$this->assertSame(
			'Acme\Db\Query',
			$namespacer->stored('Acme\Db', 'Query', ['Acme', 'Db']),
			'A path that mirrors the whole namespace must not fold into a dot-only form.'
		);
		$this->assertSame(
			'Acme\Db\Deep.Query',
			$namespacer->stored('Acme\Db\Deep', 'Query', ['Acme', 'Db', 'Deep'])
		);
	}

	/**
	 * The values are held in the form a built class actually carries.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheValuesAreHeldInTheirBuiltForm(): void
	{
		$this->load->component(3, 'comp-guid', 'componentbuilder', 1, 'My_Vendor');
		$this->config->set('component', 3);
		$placeholders = $this->placeholders();

		$this->assertSame(
			'MyVendor',
			$placeholders->prefix(),
			'The compiler strips a prefix to its namespace-safe form at build time.'
		);
	}

	/**
	 * An override value may lean on the core placeholders, as the compiler allows.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnOverrideValueMayLeanOnTheCorePlaceholders(): void
	{
		$this->load->component(3, 'comp-guid', 'demo', 1, 'VDM');
		$this->load->overrides('comp-guid', [
			['target' => '[[[ComponentNamespace]]]', 'value' => '[[[Component]]]Portal']
		]);
		$this->config->set('component', 3);
		$placeholders = $this->placeholders();

		$this->assertSame('DemoPortal', $placeholders->component());
	}

	/**
	 * The catalogue follows a change of placeholder values, never staling.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheCatalogueFollowsAChangeOfPlaceholderValues(): void
	{
		$this->load->component(3, 'guid-three', 'componentbuilder', 1, 'VDM');
		$this->load->component(4, 'guid-four', 'componentbuilder', 1, 'Acme');
		$this->load->power(1, 'aaaaaaaa-1111-4111-8111-111111111111', 'Load',
			'[[[NamespacePrefix]]]\Joomla\Data.Load');
		$this->config->set('component', 3);
		$existing = $this->existing();

		$this->assertNotNull($existing->find('VDM\Joomla\Data\Load'));
		$this->assertNull($existing->find('Acme\Joomla\Data\Load'));

		$this->config->set('component', 4);

		$this->assertNull(
			$existing->find('VDM\Joomla\Data\Load'),
			'A catalogue resolved under other values must not be reused.'
		);
		$this->assertNotNull($existing->find('Acme\Joomla\Data\Load'));
	}

	/**
	 * The placeholders resolver under test.
	 *
	 * @return  Placeholders  The resolver.
	 * @since   6.1.7
	 */
	private function placeholders(): Placeholders
	{
		return new Placeholders(
			$this->config,
			$this->load,
			$this->report,
			new Source()
		);
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
