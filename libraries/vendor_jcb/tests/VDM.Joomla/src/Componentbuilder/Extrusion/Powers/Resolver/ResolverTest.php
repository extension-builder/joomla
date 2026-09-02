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
		// override values are stored as the plain text the person typed,
		// exactly as the compiler reads them in applyComponentOverrides
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
	 * A library harvested on its own still recognises the component area.
	 *
	 * No component is paired and no source names one -- but the system knows
	 * the component by name, and its segment answers by its word whatever
	 * casing the library carries.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testALibraryAloneStillRecognisesAKnownComponent(): void
	{
		$this->load->component(9, 'guid-nine', 'sermondistributor');
		$namespacer = $this->namespacer();

		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Utilities.Permitted.Actions',
			$namespacer->placeholderize(
				'TrueChristianSermon\Joomla\SermonDistributor.Utilities.Permitted.Actions'
			),
			'The component the system knows answers for its segment, paired '
			. 'or not.'
		);
	}

	/**
	 * A reference written under another prefix folds to its power.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAReferenceUnderAnotherPrefixFoldsToItsPower(): void
	{
		$this->load->power(
			5, 'eeeeeeee-5555-4555-8555-555555555555', 'GetHelper',
			'[[[NamespacePrefix]]]\Joomla\Utilities.GetHelper'
		);
		$existing = $this->existing();

		$this->assertSame(
			'eeeeeeee-5555-4555-8555-555555555555',
			$existing->fold('TrueChristianSermon\Joomla\Utilities\GetHelper')['guid'] ?? null,
			'An import written under another component\'s prefix is still '
			. 'that power.'
		);
		$this->assertNull($existing->fold('Registry'));
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
			'[[[NamespacePrefix]]]\Joomla\Data.Load',
			$namespacer->placeholderize('Other\Joomla\Data.Load'),
			'The prefix is ALWAYS the first segment, whatever it reads -- '
			. 'deferring it is what lets one class serve components whose '
			. 'prefixes differ.'
		);
	}

	/**
	 * The prefix is always the first segment, and the component answers by word.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testThePrefixIsAlwaysDeferredAndTheComponentAnswersByWord(): void
	{
		$this->load->component(3, 'comp-guid', 'componentbuilder', 1, 'VDM');
		$this->config->set('component', 3);
		$placeholders = $this->placeholders();
		$namespacer = new Namespacer($placeholders);

		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\Data.Load',
			$namespacer->placeholderize('Other\Joomla\Data.Load'),
			'The first segment is the vendor prefix, whatever it reads.'
		);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].File.Display',
			$namespacer->placeholderize('Other\Joomla\ComponentBuilder.File.Display'),
			'A namespace is case-insensitive to PHP: the segment answers by '
			. 'its word, and the casing it actually carried is witnessed.'
		);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Query',
			$namespacer->placeholderize('Acme\Query'),
			'A vendor of its own defers its prefix like any other.'
		);
		$this->assertSame(
			[['prefix' => 'Other', 'component' => 'ComponentBuilder', 'count' => 1]],
			$placeholders->witnessed(),
			'The component-owned class witnessed the values its library was '
			. 'built with.'
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
	 * The seam is read from the file's real ancestry, wherever the run was aimed.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testTheSeamIsReadFromTheFilesRealAncestry(): void
	{
		$namespacer = $this->namespacer();
		$site = ['var', 'www', 'html', 'administrator', 'components', 'com_demo', 'src'];

		$this->assertSame(
			'VDM\Component\Demo\Administrator\Engine.Team',
			$namespacer->stored(
				'VDM\Component\Demo\Administrator\Engine', 'Team', [], 'Engine',
				array_merge($site, ['Engine'])
			),
			'Aimed at the Engine folder itself, the folder above the root still '
			. 'mirrors the last segment: Engine is a folder under src, so it is '
			. 'a dot part, never part of the head.'
		);
		$this->assertSame(
			'VDM\Component\Demo\Administrator\Engine.Team',
			$namespacer->stored(
				'VDM\Component\Demo\Administrator\Engine', 'Team', ['Engine'], 'src', $site
			),
			'Aimed at src, the folder below the root says the same thing.'
		);
		$this->assertSame(
			'VDM\Component\Demo\Administrator\Team',
			$namespacer->stored('VDM\Component\Demo\Administrator', 'Team', [], 'src', $site),
			'A class directly below the area\'s src has no dot part: the head is '
			. 'the area, exactly as the compiler places it.'
		);
		$this->assertSame(
			'VDM\Joomla\Data.Action.Load',
			$namespacer->stored(
				'VDM\Joomla\Data\Action', 'Load', ['Data', 'Action'], 'VDM.Joomla',
				['var', 'www', 'html', 'libraries', 'vendor_vdm', 'VDM.Joomla', 'src']
			),
			'A vendor folder in the libraries layout folds exactly as before.'
		);
		$this->assertSame(
			'VDM\Plugin\System\Demo\Extension.Demo',
			$namespacer->stored(
				'VDM\Plugin\System\Demo\Extension', 'Demo', [], 'Extension',
				['var', 'www', 'html', 'plugins', 'system', 'demo', 'src', 'Extension']
			),
			'The mirroring stops at src, so a plugin keeps its whole head.'
		);
		$this->assertNull(
			$namespacer->stored(
				'VDM\Component\Demo\Administrator\Engine', 'Team', ['Other'], 'src', $site
			),
			'A folder below the root that the namespace does not mirror is still '
			. 'a contradiction.'
		);
	}

	/**
	 * A person's own placeholders resolve in the compiler's order.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testThePersonsPlaceholdersResolveInTheCompilersOrder(): void
	{
		$this->load->component(3, 'comp-guid', 'demo', 1, 'VDM');
		$this->load
			->placeholder(
				1, '[[[ComponentEngineNamespace]]]',
				'[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine'
			)
			->placeholder(2, '[[[COMPANY]]]', 'VDM')
			->placeholder(3, '[[[ComponentNamespace]]]', 'Remembered');
		$this->load->overrides('comp-guid', [
			[
				'target' => '[[[ComponentMotorNamespace]]]',
				'value' => '[[[ComponentEngineNamespace]]]\Motor'
			]
		]);
		$this->config->set('component', 3);
		$placeholders = $this->placeholders();
		$namespacer = new Namespacer($placeholders);

		$this->assertSame(
			[
				'[[[ComponentEngineNamespace]]]' => '[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine',
				'[[[COMPANY]]]' => 'VDM',
				'[[[ComponentNamespace]]]' => 'Demo',
				'[[[NamespacePrefix]]]' => 'VDM',
				'[[[ComponentMotorNamespace]]]' => '[[[ComponentEngineNamespace]]]\Motor'
			],
			$placeholders->map(),
			'The system-wide table first, the core values over it in place -- '
			. 'the paired component outranks a remembered global segment -- '
			. 'and the component\'s own overrides last, keeping the core '
			. 'placeholders they lean on.'
		);
		$this->assertSame(
			'VDM\Component\Demo\Administrator\Engine\Team',
			$namespacer->resolve('[[[ComponentEngineNamespace]]].Team'),
			'A namespace stored through the person\'s placeholder resolves to '
			. 'the very class the compiler writes.'
		);
		$this->assertSame(
			'VDM\Component\Demo\Administrator\Engine\Motor\Belt',
			$namespacer->resolve('[[[ComponentMotorNamespace]]].Belt'),
			'A value that names another placeholder is reached however the '
			. 'definitions are ordered.'
		);
		$this->assertSame(
			[
				'[[[ComponentEngineNamespace]]]' => '[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine',
				'[[[COMPANY]]]' => 'VDM',
				'[[[ComponentMotorNamespace]]]' => '[[[ComponentEngineNamespace]]]\Motor'
			],
			$placeholders->custom(),
			'Only the person\'s own targets are custom; the core ones never are.'
		);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine.Team',
			$namespacer->canonical('###ComponentEngineNamespace###.Team'),
			'The canonical form unfolds the person\'s placeholder, keeps the '
			. 'core ones standing, and speaks one wrapper form.'
		);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine\Motor.Belt',
			$namespacer->canonical('[[[ComponentMotorNamespace]]].Belt')
		);

		$this->config->set('component', 0);

		$this->assertSame(
			'Remembered',
			$placeholders->map()['[[[ComponentNamespace]]]'] ?? null,
			'With nothing paired or named, the remembered global row answers.'
		);
	}

	/**
	 * A namespace is expressed through the longest placeholder that stands for its head.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testExpressWritesThroughTheLongestKnownPlaceholder(): void
	{
		$this->load->component(3, 'comp-guid', 'demo', 1, 'VDM');
		$this->load
			->placeholder(
				1, '[[[ComponentAdminNamespace]]]',
				'[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator'
			)
			->placeholder(2, '[[[ComponentEngineNamespace]]]', '[[[ComponentAdminNamespace]]]\Engine')
			->placeholder(3, '[[[COMPANY]]]', 'VDM')
			->placeholder(4, '[[[gitea_url]]]', 'git.vdm.dev')
			->placeholder(
				5, '[[[ComponentSiteNamespace]]]',
				'VDM\Component\Demo\Site'
			);
		$this->config->set('component', 3);
		$namespacer = $this->namespacer();
		$admin = '[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator';

		$this->assertSame(
			'[[[ComponentEngineNamespace]]].Team',
			$namespacer->express($admin . '\Engine.Team'),
			'The longest covering placeholder wins, and a value leaning on '
			. 'another placeholder unfolds first.'
		);
		$this->assertSame(
			'[[[ComponentEngineNamespace]]].Sub.Team',
			$namespacer->express($admin . '\Engine.Sub.Team'),
			'The joiner after the covered run is kept: a dot where a folder follows.'
		);
		$this->assertSame(
			'[[[ComponentAdminNamespace]]]\Helper.Tool',
			$namespacer->express($admin . '\Helper.Tool'),
			'A backslash where the head continues.'
		);
		$this->assertSame(
			'[[[ComponentAdminNamespace]]]\Team',
			$namespacer->express($admin . '\Team'),
			'A placeholder never swallows the class itself.'
		);
		$this->assertSame(
			'[[[ComponentSiteNamespace]]]\Helper.Tool',
			$namespacer->express(
				'[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Site\Helper.Tool'
			),
			'A concrete value stands for the head when it resolves to the same words.'
		);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\Data.Load',
			$namespacer->express('[[[NamespacePrefix]]]\Joomla\Data.Load'),
			'A value that is no namespace fragment never stands for one, '
			. 'whatever word it happens to share.'
		);
		$this->assertSame(
			'[[[ComponentEngineNamespace]]].Team',
			$namespacer->express('[[[ComponentEngineNamespace]]].Team'),
			'What is already expressed stays as it is.'
		);
	}

	/**
	 * The catalogue matches a power stored through a person's placeholder.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testTheCatalogueMatchesAPowerStoredThroughAPersonsPlaceholder(): void
	{
		$guid = 'aaaaaaaa-1111-4111-8111-111111111111';
		$this->load->component(3, 'comp-guid', 'demo', 1, 'VDM');
		$this->load->placeholder(
			1, '[[[ComponentEngineNamespace]]]',
			'[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine'
		);
		$this->load->power(1, $guid, 'Team', '[[[ComponentEngineNamespace]]].Team');
		$this->config->set('component', 3);
		$existing = $this->existing();
		$long = '[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine.Team';

		$this->assertSame(
			$guid,
			$existing->match($long)['guid'] ?? null,
			'Identity is the canonical form, so the long form the placeholder '
			. 'stands for is the same power.'
		);
		$this->assertSame(
			'[[[ComponentEngineNamespace]]].Team',
			$existing->match($long)['namespace'] ?? null,
			'The form the person stored travels with the match.'
		);
		$this->assertSame(
			$guid,
			$existing->find('VDM\Component\Demo\Administrator\Engine\Team')['guid'] ?? null,
			'The class it compiles to resolves through the person\'s placeholder.'
		);
		$this->assertSame(
			$guid,
			$existing->power(strtoupper($guid))['guid'] ?? null
		);
		$this->assertSame(
			$guid,
			$existing->fold('Other\Component\DEMO\Administrator\Engine\Team')['guid'] ?? null,
			'A reference under another prefix folds at the seam the power '
			. 'keeps, not only at the conventional one.'
		);
		$this->assertNull(
			$this->report->get('powers.unresolved.namespace.aaaaaaaa_1111_4111_8111_111111111111'),
			'Nothing is unresolved: the person\'s placeholder has a value.'
		);
	}

	/**
	 * An override is the plain text the person typed, and the report never carries it.
	 *
	 * JCB stores a component's override values exactly as typed -- "7M" for
	 * an upload limit -- and the compiler reads them as they stand. Treating
	 * them as base64 turned such a value into bytes that are not text, and a
	 * report carrying those bytes cannot be encoded for the page, which then
	 * received an empty response. The value must reach the map as typed, and
	 * the report must name the override without carrying its value.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAnOverrideIsPlainTextAndTheReportStaysEncodable(): void
	{
		$this->load->component(3, 'comp-guid', 'demo', 1, 'Demo');
		$this->load->overrides('comp-guid', [
			['target' => '[[[upload_max_filesize]]]', 'value' => '7M'],
			['target' => '[[[post_max_size]]]', 'value' => '6M'],
			['target' => '[[[ComponentNamespace]]]', 'value' => 'DeMo']
		]);
		$this->config->set('component', 3);
		$placeholders = $this->placeholders();

		$this->assertSame('DeMo', $placeholders->component());
		$this->assertSame('7M', $placeholders->map()['[[[upload_max_filesize]]]'] ?? null);
		$this->assertSame(
			['[[[upload_max_filesize]]]' => '7M', '[[[post_max_size]]]' => '6M'],
			$placeholders->custom()
		);
		$this->assertSame(
			['upload_max_filesize', 'post_max_size', 'ComponentNamespace'],
			$this->report->get('powers.placeholders.overrides'),
			'The report names the overrides that stood, never their values.'
		);
		$this->assertNotFalse(
			json_encode($this->report->toArray()),
			'Whatever a person typed, the report the page reads must encode.'
		);
	}

	/**
	 * A system-wide row that does not decode to text is left out and named.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAnUndecodableGlobalRowIsLeftOutAndNamed(): void
	{
		$this->load
			->placeholder(1, '[[[COMPANY]]]', 'VDM')
			->placeholder(2, '[[[Broken]]]', "\xff\xfe binary");
		$placeholders = $this->placeholders();

		$this->assertSame(['[[[COMPANY]]]' => 'VDM'], $placeholders->custom());
		$this->assertSame(
			'Broken',
			$this->report->get('powers.undecodable.placeholder.Broken')
		);
		$this->assertNotFalse(json_encode($this->report->toArray()));
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
