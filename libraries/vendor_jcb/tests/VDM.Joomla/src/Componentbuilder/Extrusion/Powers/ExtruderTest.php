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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Powers;


use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Assembler;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Extruder;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Harvester;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Writer\Power as PowerWriter;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Writer\Vendor as VendorWriter;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Harvest;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Message;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Discovery;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Extrusion;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Powers;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Reader;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Registry as RegistryProvider;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Resolver;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Writer as WriterProvider;
use VDM\Joomla\Componentbuilder\Table as JcbTable;
use VDM\Tests\Support\ExtrusionItemFixture;
use VDM\Tests\Support\ExtrusionLibraryFixture;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * The powers branch of the extrusion engine, run whole over a real tree.
 *
 * Everything here goes through the real graph: the providers compose the
 * actual harvester, reader, resolvers and writer, and only the two database
 * boundaries are faked -- the JCB data pipeline that would write, and the
 * loader that serves the power catalogue and component row. The value being
 * proven is the whole story: classes in a folder become identified, linked
 * power definitions, in two deliberate steps.
 *
 * @since  6.1.7
 */
#[CoversClass(Extruder::class)]
#[CoversClass(Harvester::class)]
#[CoversClass(Assembler::class)]
#[CoversClass(PowerWriter::class)]
#[CoversClass(VendorWriter::class)]
#[UsesClass(Config::class)]
#[UsesClass(Harvest::class)]
#[UsesClass(Report::class)]
#[UsesClass(Message::class)]
final class ExtruderTest extends FilesystemTestCase
{
	/**
	 * The identity of the power that already exists in the catalogue.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXISTING_GUID = 'aaaaaaaa-1111-4111-8111-111111111111';

	/**
	 * The identity of a power stored through a person's own placeholder.
	 *
	 * @var    string
	 * @since  6.1.9
	 */
	private const TEAM_GUID = 'bbbbbbbb-2222-4222-8222-222222222222';

	/**
	 * The identity of a second power stored through the same placeholder.
	 *
	 * @var    string
	 * @since  6.1.9
	 */
	private const REFEREE_GUID = 'cccccccc-3333-4333-8333-333333333333';

	/**
	 * The value a person gave their own namespace placeholder.
	 *
	 * @var    string
	 * @since  6.1.9
	 */
	private const ENGINE = '[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine';

	/**
	 * The recorded JCB data pipeline boundary.
	 *
	 * @var    ExtrusionItemFixture
	 * @since  6.1.7
	 */
	private ExtrusionItemFixture $item;

	/**
	 * The served database loader boundary.
	 *
	 * @var    ExtrusionPowerLoadFixture
	 * @since  6.1.7
	 */
	private ExtrusionPowerLoadFixture $load;

	/**
	 * The composed extrusion container.
	 *
	 * @var    Container
	 * @since  6.1.7
	 */
	private Container $container;

	/**
	 * The library root the fixture tree was written below.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private string $library;

	/**
	 * Compose the real graph over the two faked boundaries.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->item = new ExtrusionItemFixture();
		$this->load = new ExtrusionPowerLoadFixture();
		$this->load->component(3, 'comp-guid-0001', 'demo', 1, 'Demo');
		$this->load->power(
			7, self::EXISTING_GUID, 'LoaderInterface',
			'[[[NamespacePrefix]]]\Joomla\Interfaces.LoaderInterface'
		);
		$this->container = new Container();
		$this->container->share('Data.Item', fn (): ExtrusionItemFixture => $this->item);
		$this->container->share('Load', fn (): ExtrusionPowerLoadFixture => $this->load);
		// the table definitions say how every column is stored, which is what
		// weighing a write against what stands has to speak in
		$this->container->share('Table', fn (): JcbTable => new JcbTable());
		$this->container->registerServiceProvider(new RegistryProvider())
			->registerServiceProvider(new Discovery())
			->registerServiceProvider(new Reader())
			->registerServiceProvider(new Resolver())
			->registerServiceProvider(new WriterProvider())
			->registerServiceProvider(new Powers())
			->registerServiceProvider(new Extrusion());

		foreach (ExtrusionLibraryFixture::files() as $path => $content)
		{
			$this->writeTemporaryFile('lib/' . $path, $content);
		}

		$this->library = $this->temporaryPath('lib/Demo.Joomla');
	}

	/**
	 * Every setter answers with the same instance and writes into the shared config.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEverySetterChainsAndWritesIntoTheSharedConfig(): void
	{
		$extruder = $this->extruder();
		$config = $this->config();

		$this->assertSame($extruder, $extruder->library('/srv/lib/one'));
		$this->assertSame($extruder, $extruder->library('/srv/lib/one'));
		$this->assertSame($extruder, $extruder->library('  '));
		$this->assertSame($extruder, $extruder->library('/srv/lib/two'));
		$this->assertSame(
			['/srv/lib/one', '/srv/lib/two'],
			$config->get('libraries'),
			'A repeated or empty folder is not a second library.'
		);

		$this->assertSame($extruder, $extruder->libraries(['/srv/lib/three']));
		$this->assertSame(['/srv/lib/three'], $config->get('libraries'));

		$this->assertSame($extruder, $extruder->component(-4));
		$this->assertSame(0, $config->get('component'));
		$this->assertSame($extruder, $extruder->component(3));
		$this->assertSame(3, $config->get('component'));

		$this->assertSame($extruder, $extruder->onExisting(' Skip '));
		$this->assertSame('skip', $config->get('onExisting'));
		$this->assertSame($extruder, $extruder->onExisting('nonsense'));
		$this->assertSame('skip', $config->get('onExisting'), 'A rejected policy must not corrupt the stored one.');
		$this->assertSame(
			'rejected "nonsense"; allowed: skip, update, replace',
			$this->report()->get('failed.option.onExisting')
		);

		$this->assertSame($extruder, $extruder->include(['a']));
		$this->assertSame($extruder, $extruder->exclude(['b']));
		$this->assertSame(['a'], $config->get('include'));
		$this->assertSame(['b'], $config->get('exclude'));
		$this->assertSame($extruder, $extruder->dryRun());
		$this->assertTrue($config->get('dryRun'));
		$this->assertSame($extruder, $extruder->limits(4, 250));
		$this->assertSame(4, $config->get('depth'));
		$this->assertSame(250, $config->get('maxFiles'));
	}

	/**
	 * A run given no library folder refuses plainly, in both steps.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testARunWithoutALibraryFolderRefuses(): void
	{
		$extruder = $this->extruder();

		$this->assertFalse((bool) $extruder->harvest()->get('powers.completed'));
		$this->assertFalse((bool) $extruder->extrude()->get('powers.completed'));
		$this->assertSame(
			[['message' => 'No library folder was given to harvest powers from.']],
			$extruder->messages()['error']
		);
		$this->assertSame([], $this->item->records());
	}

	/**
	 * Harvesting builds the approval-ready tree without writing anything.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testHarvestBuildsTheApprovalReadyTreeWithoutWriting(): void
	{
		$extruder = $this->extruder();
		$report = $extruder->reset()
			->library($this->library)
			->component(3)
			->harvest();

		$this->assertTrue((bool) $report->get('powers.completed'));
		$this->assertSame(3, $report->get('counts.powers.classes'));
		$this->assertSame(2, $report->get('counts.powers.new'));
		$this->assertSame(1, $report->get('counts.powers.existing'));
		$this->assertSame([], $this->item->records(), 'Harvesting must never write.');

		$tree = $extruder->harvested();
		$library = $tree['libraries']['Demo_Joomla'] ?? null;

		$this->assertNotNull($library);
		$this->assertSame('Demo.Joomla', $library['folder']);
		$this->assertSame(3, $library['count']);
		$this->assertSame(
			['Data', 'Data_Action', 'Interfaces'],
			$this->sorted(array_keys($library['bundles'])),
			'Classes bundle by the folder they sit in.'
		);

		$loader = $tree['classes'][$this->guid('Demo\Joomla\Data\Loader')] ?? null;

		$this->assertNotNull($loader);
		$this->assertSame('Loader', $loader['class']);
		$this->assertSame('final class', $loader['type']);
		$this->assertSame('Demo\Joomla\Data.Loader', $loader['stored']);
		$this->assertSame('[[[NamespacePrefix]]]\Joomla\Data.Loader', $loader['placeholder']);
		$this->assertSame('create', $loader['action']);
		$this->assertFalse($loader['exists']);

		$interface = $tree['classes'][self::EXISTING_GUID] ?? null;

		$this->assertNotNull($interface);
		$this->assertTrue($interface['exists']);
		$this->assertSame('update', $interface['action']);
		$this->assertSame(7, $interface['id']);
		$this->assertSame(
			[['message' => 'Harvested 3 class(es): 2 new, 1 already a power.']],
			$extruder->messages()['success']
		);
	}

	/**
	 * A library extension folder is a folder of vendors, and each is a library.
	 *
	 * Joomla installs the extension folder, but the vendor folder inside it is
	 * what names the namespace head. Reading the extension folder as the vendor
	 * would throw that name away, and with it the only statement of where the
	 * head ends -- so aiming at either lands on the same library.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testAnExtensionFolderIsReadAsTheVendorFoldersItHolds(): void
	{
		$extruder = $this->extruder();
		$report = $extruder->reset()
			->library($this->temporaryPath('lib'))
			->component(3)
			->harvest();

		$this->assertSame(3, $report->get('counts.powers.classes'));
		$this->assertSame(1, $report->get('counts.powers.existing'));

		$tree = $extruder->harvested();
		$library = $tree['libraries']['Demo_Joomla'] ?? null;

		$this->assertNotNull(
			$library,
			'The vendor folder inside is the library, not the folder holding it.'
		);
		$this->assertSame('Demo.Joomla', $library['folder']);

		$loader = $tree['classes'][$this->guid('Demo\Joomla\Data\Loader')] ?? null;

		$this->assertNotNull($loader);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\Data.Loader',
			$loader['placeholder'],
			'The vendor folder states its head either way, so the prefix is '
			. 'deferred either way.'
		);
	}

	/**
	 * Extruding writes the whole set, linked by identity.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testExtrudeWritesTheWholeSetLinkedByIdentity(): void
	{
		$this->item->identity('power', self::EXISTING_GUID, 7);
		$report = $this->extruder()->reset()
			->library($this->library)
			->component(3)
			->extrude();

		$this->assertTrue((bool) $report->get('powers.completed'));
		$this->assertSame(['power', 'power', 'power'], $this->item->sequence());

		$fetch = $this->item->definition('power', $this->guid('Demo\Joomla\Data\Action\Fetch'));

		$this->assertNotNull($fetch);
		$this->assertSame('Fetch', $fetch->name);
		$this->assertSame('abstract class', $fetch->type);
		$this->assertSame('[[[NamespacePrefix]]]\Joomla\Data.Action.Fetch', $fetch->namespace);
		// the system name speaks JCB's own convention: the vendor prefix,
		// then the dotted tail -- VDM.Data.Action.Load, never the Joomla
		// connector between them
		$this->assertSame('Demo.Data.Action.Fetch', $fetch->system_name);
		$this->assertSame('1.0.0', $fetch->power_version);
		$this->assertSame(1, $fetch->published);
		// the block keeps the line break that closes it, exactly as JCB
		// stores its own licence templates
		$this->assertSame(ExtrusionLibraryFixture::LICENSE . "\n", $fetch->licensing_template);
		$this->assertSame(2, $fetch->add_licensing_template);
		$this->assertStringContainsString('abstract public function fetch();', $fetch->main_class_code);
		$this->assertStringNotContainsString('class Fetch', $fetch->main_class_code);
		$this->assertSame('', $fetch->extends, 'A class without a parent states so, clearing any stale link.');
		$this->assertSame([], $fetch->use_selection);
		$this->assertSame('', $fetch->head);
		$this->assertSame(0, $fetch->add_head);

		$loader = $this->item->definition('power', $this->guid('Demo\Joomla\Data\Loader'));

		$this->assertNotNull($loader);
		$this->assertSame(
			'-1',
			$loader->extends,
			'An aliased parent keeps its alias as the custom name, because the body may lean on it.'
		);
		$this->assertSame('Getter', $loader->extends_custom);
		$this->assertSame(
			['use_selection0' => [
				'use' => $this->guid('Demo\Joomla\Data\Action\Fetch'),
				'as' => 'Getter'
			]],
			$loader->use_selection,
			'The aliased parent still links by identity, through the use selection.'
		);
		$this->assertSame(
			[self::EXISTING_GUID],
			$loader->implements,
			'An interface that already is a power links by its own identity, and its
			plain import is never also a use selection.'
		);
		$this->assertSame('use Joomla\CMS\Factory;', $loader->head);
		$this->assertSame(1, $loader->add_head);
		// the compiler wrote the component's own name into this body, and a
		// power that read it back would name that component wherever it was
		// used next
		$this->assertStringContainsString(
			"return 'com_[[[component]]]';",
			$loader->main_class_code,
			'A class body names the component through the placeholder that stands for it.'
		);
		$this->assertStringNotContainsString("'com_demo'", $loader->main_class_code);

		$interface = $this->item->definition('power', self::EXISTING_GUID);

		$this->assertNotNull($interface);
		$this->assertSame('LoaderInterface', $interface->name);
		$this->assertObjectNotHasProperty(
			'system_name', $interface,
			'An update must not overwrite the human-chosen system name.'
		);
		$this->assertObjectNotHasProperty('power_version', $interface);
		$this->assertObjectNotHasProperty('published', $interface);

		$success = $this->extruderMessages('success');

		$this->assertSame(['Extruded 3 class(es) into JCB powers.'], $success);
	}

	/**
	 * Pairing verdicts govern the powers write: ignore and retarget.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testPairingVerdictsGovernThePowersWrite(): void
	{
		$other = 'eeeeeeee-5555-4555-8555-555555555555';
		$fetch = $this->guid('Demo\Joomla\Data\Action\Fetch');
		$loader = $this->guid('Demo\Joomla\Data\Loader');

		$extruder = $this->extruder()->reset()
			->library($this->library)
			->component(3);

		// verdicts load after the reset, because reset is the run boundary
		$this->container->get('Extrusion.Resolver.Pairing')->load([
			'power' => [
				$loader => ['action' => 'ignore'],
				$fetch => ['action' => 'update', 'target' => $other]
			]
		]);
		$report = $extruder->extrude();

		$this->assertTrue((bool) $report->get('powers.completed'));

		$written = array_column($this->item->records('power'), 'item');
		$guids = array_map(static fn (object $definition): string => $definition->guid, $written);

		$this->assertNotContains($loader, $guids, 'An ignored class is never written.');
		$this->assertContains(
			$other,
			$guids,
			'A retargeted class updates the power the person pointed at.'
		);
		$this->assertNotContains($fetch, $guids);

		// the board knows the class by the identity the harvest gave it, so
		// that is the row its weight answers on, whatever it is written under
		$summary = $this->container->get('Extrusion.Registry.Proposal')->summary();

		$this->assertArrayHasKey('power|' . $fetch, $summary, 'The retargeted class weighs on its own board row.');
		$this->assertArrayNotHasKey('power|' . $other, $summary);
		$this->assertArrayNotHasKey('power|' . $loader, $summary, 'An ignored class is out of the run, so it has no weight.');
		$this->assertSame(1, $summary['power|' . $fetch]['records']);
	}

	/**
	 * The skip policy mentions an existing power and leaves it untouched.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheSkipPolicyMentionsAnExistingPowerWithoutTouchingIt(): void
	{
		$this->item->identity('power', self::EXISTING_GUID, 7);
		$report = $this->extruder()->reset()
			->library($this->library)
			->component(3)
			->onExisting('skip')
			->extrude();

		$this->assertTrue((bool) $report->get('powers.completed'));
		$this->assertNull(
			$this->item->definition('power', self::EXISTING_GUID),
			'A skipped power is mentioned, never written.'
		);
		$this->assertTrue(
			(bool) $report->get('skipped.existing.power.' . self::EXISTING_GUID)
		);
		$this->assertCount(2, $this->item->records('power'));

		$loader = $this->item->definition('power', $this->guid('Demo\Joomla\Data\Loader'));

		$this->assertSame(
			[self::EXISTING_GUID],
			$loader->implements ?? null,
			'Dropping a class from the write must not drop it from the wiring: '
			. 'the power it stands for is still what the classes beside it '
			. 'refer to, and they still have to reach it.'
		);
		$this->assertSame(
			['Extruded 2 class(es) into JCB powers (1 left untouched because they already exist).'],
			$this->extruderMessages('success')
		);
	}

	/**
	 * A dry run prepares every definition and writes none of them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testADryRunPreparesEverythingAndWritesNothing(): void
	{
		$report = $this->extruder()->reset()
			->library($this->library)
			->component(3)
			->dryRun()
			->extrude();

		$this->assertTrue((bool) $report->get('powers.completed'));
		$this->assertSame([], $this->item->records());
		$this->assertCount(3, (array) $report->get('dryrun.power'));
		$this->assertSame(
			['Reviewed 3 class(es) and prepared 3 power definition(s). Nothing was written, because this was a dry run.'],
			$this->extruderMessages('success')
		);
	}

	/**
	 * The include filter narrows the write to the approved candidates.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheIncludeFilterNarrowsTheWriteToTheApproved(): void
	{
		$report = $this->extruder()->reset()
			->library($this->library)
			->component(3)
			->include(['Demo\Joomla\Data\Action\Fetch'])
			->extrude();

		$this->assertTrue((bool) $report->get('powers.completed'));
		$this->assertCount(1, $this->item->records('power'));
		$this->assertNotNull(
			$this->item->definition('power', $this->guid('Demo\Joomla\Data\Action\Fetch'))
		);
		$this->assertSame(2, count((array) $report->get('powers.skipped.filtered')));
	}

	/**
	 * A reset starts a genuinely fresh run.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAResetStartsAFreshRun(): void
	{
		$extruder = $this->extruder();
		$extruder->reset()->library($this->library)->component(3)->harvest();

		$this->assertNotSame([], $extruder->harvested());

		$extruder->reset();

		$this->assertSame([], $extruder->harvested());
		$this->assertSame([], $this->config()->get('libraries'));
		$this->assertSame(0, $this->config()->get('component'));
	}

	/**
	 * Harvesting twice in one run gathers once.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testHarvestingTwiceInOneRunGathersOnce(): void
	{
		$extruder = $this->extruder()->reset()->library($this->library)->component(3);
		$extruder->harvest();
		$first = $extruder->harvested();
		$extruder->harvest();

		$this->assertSame($first, $extruder->harvested());
		$this->assertSame(3, $this->report()->get('counts.powers.classes'));
	}

	/**
	 * The witnessed placeholder values are recorded onto the component.
	 *
	 * A component-owned class carries the very values its placeholders must
	 * resolve back to. The person's standing prefix is never overwritten --
	 * only the disagreement is named -- while the component segment's own
	 * casing, differing from what the code name derives, is recorded as a
	 * ComponentNamespace override, as the plain text the compiler
	 * decodes it.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testTheWitnessedValuesAreRecordedOnTheComponent(): void
	{
		$this->writeTemporaryFile(
			'vend/Acme.Joomla/src/DeMo/Helper.php',
			"<?php\nnamespace Acme\\Joomla\\DeMo;\n\nuse Acme\\Joomla\\Interfaces\\LoaderInterface;\n\n/**\n * The demo helper.\n *\n * @since 1.0.0\n */\nfinal class Helper\n{\n\tpublic function go(): bool\n\t{\n\t\treturn true;\n\t}\n}\n"
		);

		$report = $this->extruder()->reset()
			->library($this->temporaryPath('vend/Acme.Joomla'))
			->component(3)
			->extrude();

		$this->assertTrue((bool) $report->get('powers.completed'));

		$power = $this->item->definition(
			'power',
			(new Guid())->derive([
				'power',
				'[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Helper'
			])
		);

		$this->assertNotNull($power);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Helper',
			$power->namespace,
			'The prefix is always deferred, and the segment answers by word.'
		);
		$this->assertSame('Acme.DeMo.Helper', $power->system_name);
		$this->assertSame(
			[['use' => self::EXISTING_GUID, 'as' => 'default']],
			array_values(array_map(
				static fn ($row): array => (array) $row,
				(array) $power->use_selection
			)),
			'An import written under this library\'s own prefix is the very '
			. 'power that already stands, so it links by identity instead of '
			. 'landing in the class header.'
		);
		$this->assertSame('', $power->head);

		$this->assertSame(
			'Demo (the library was built with Acme)',
			$report->get('powers.vendor.kept.namespace_prefix'),
			'The person\'s standing prefix is kept, and the disagreement named.'
		);

		// the placeholder row is keyed by its component, not by a guid
		$placeholders = null;

		foreach ($this->item->definitions('component_placeholders') as $definition)
		{
			if ((string) ($definition->joomla_component ?? '') === 'comp-guid-0001')
			{
				$placeholders = $definition;
			}
		}

		$this->assertNotNull($placeholders);
		$this->assertSame(
			['target' => '[[[ComponentNamespace]]]', 'value' => 'DeMo'],
			(array) ($placeholders->addplaceholders['addplaceholders0'] ?? null),
			'The casing the library was built with is recorded for the compiler '
			. 'to resolve back to.'
		);
		$this->assertSame('DeMo', $report->get('powers.vendor.component_namespace'));
	}

	/**
	 * With no component paired, the named component is remembered globally.
	 *
	 * The person selected none and named the component instead: the classes
	 * still defer the component segment to its placeholder, and the casing
	 * the library carries is remembered as a global placeholder row -- the
	 * system's own memory of the name -- since no component row stands to
	 * carry it.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testANamedComponentIsRememberedGlobally(): void
	{
		$this->writeTemporaryFile(
			'named/Acme.Joomla/src/DeMo/Helper.php',
			"<?php\nnamespace Acme\\Joomla\\DeMo;\n\n/**\n * The demo helper.\n *\n * @since 1.0.0\n */\nfinal class Helper\n{\n\tpublic function go(): bool\n\t{\n\t\treturn true;\n\t}\n}\n"
		);

		$report = $this->extruder()->reset()
			->library($this->temporaryPath('named/Acme.Joomla'))
			->component(0)
			->componentCode('com_demo')
			->extrude();

		$this->assertTrue((bool) $report->get('powers.completed'));

		$power = $this->item->definition(
			'power',
			(new Guid())->derive([
				'power',
				'[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Helper'
			])
		);

		$this->assertNotNull($power);
		$this->assertSame(
			'[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Helper',
			$power->namespace,
			'The named component answers for its segment with no row paired.'
		);

		$global = null;

		foreach ($this->item->definitions('placeholder') as $definition)
		{
			$global = $definition;
		}

		$this->assertNotNull($global);
		$this->assertSame('[[[ComponentNamespace]]]', $global->target);
		$this->assertSame(
			'DeMo',
			$global->value,
			'The casing the library carries is remembered raw; the table\'s '
			. 'own storage encoding is the pipeline\'s to apply.'
		);
		$this->assertSame('DeMo', $report->get('powers.vendor.global_component_namespace'));
	}

	/**
	 * Stand one power in JCB, curated as the given columns say.
	 *
	 * @param   array<string, mixed>  $columns  What the record holds.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	private function stand(array $columns): void
	{
		$this->load->placeholder(1, '[[[ComponentEngineNamespace]]]', self::ENGINE);
		$this->load->power(9, self::TEAM_GUID, 'Team', '[[[ComponentEngineNamespace]]].Team');
		$this->item->identity('power', self::TEAM_GUID, 9);
		$this->item->serve('power', self::TEAM_GUID, (object) ([
			'guid' => self::TEAM_GUID,
			'name' => 'Team',
			'namespace' => '[[[ComponentEngineNamespace]]].Team'
		] + $columns));
	}

	/**
	 * Extrude the standing power's own file and hand back what would be written.
	 *
	 * @param   string|null  $class  The class declaration, when the test needs its own.
	 *
	 * @return  object  The definition.
	 * @since   6.2.0
	 */
	private function written(?string $class = null): object
	{
		$engine = 'site/administrator/components/com_demo/src/Engine';

		$this->writeTemporaryFile(
			$engine . '/Team.php',
			"<?php\n" . ExtrusionLibraryFixture::LICENSE . "\n\n"
			. "namespace Demo\\Component\\Demo\\Administrator\\Engine;\n\n"
			. "/**\n * The team engine.\n *\n * @since 1.0.0\n */\n"
			. ($class ?? "final class Team\n{\n\tpublic function play(): bool\n\t{\n\t\treturn true;\n\t}\n}\n")
		);

		$this->extruder()->reset()
			->library($this->temporaryPath($engine))
			->component(3)
			->extrude();

		$written = $this->item->definition('power', self::TEAM_GUID);

		$this->assertNotNull($written, 'The standing power is recognised and written.');

		return $written;
	}

	/**
	 * A run never lowers a resolved parent to a written-out name.
	 *
	 * JCB compiles the two differently: a link carries the parent's import with
	 * it, a written-out name is emitted exactly as it reads with nothing to
	 * resolve it. A run that failed to work out what the parent is has learnt
	 * nothing about the record, so it may not act on that failure.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAResolvedParentIsNeverLoweredToAName(): void
	{
		$this->stand([
			'extends' => self::REFEREE_GUID,
			'extends_custom' => 'Abstraction\\Model',
			'add_licensing_template' => 1
		]);

		$written = $this->written("final class Team extends \\Some\\Unresolvable\\Model\n{\n}\n");

		$this->assertFalse(
			property_exists($written, 'extends'),
			'The record names a power and this run does not, so the record stands.'
		);
		$this->assertFalse(
			property_exists($written, 'extends_custom'),
			'And the written-out name that would have replaced it is not left behind.'
		);
	}

	/**
	 * A power that takes the global licence keeps taking it.
	 *
	 * Every compiled file carries a licence block, because the compiler wrote
	 * the global one into it. Reading that back as this power's own licence
	 * would take the power out of the global's reach and leave a private copy
	 * of it behind -- on every power, on every run. Only the person can decide
	 * that, by saying so on the record.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAPowerOnTheGlobalLicenceKeepsIt(): void
	{
		$this->stand(['add_licensing_template' => 1, 'licensing_template' => '']);

		$written = $this->written();

		$this->assertFalse(
			property_exists($written, 'add_licensing_template'),
			'The setting is the person\'s, so the run does not touch it.'
		);
		$this->assertFalse(
			property_exists($written, 'licensing_template'),
			'And it does not leave a copy of the global licence behind either.'
		);
	}

	/**
	 * A power carrying its own licence takes what the file now says.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAPowerCarryingItsOwnLicenceTakesTheFilesOne(): void
	{
		$this->stand([
			'add_licensing_template' => 2,
			'licensing_template' => 'what it used to say'
		]);

		$written = $this->written();

		$this->assertSame(
			ExtrusionLibraryFixture::LICENSE . "\n",
			$written->licensing_template,
			'The person asked this power to carry its own, so the file states it.'
		);
		$this->assertFalse(
			property_exists($written, 'add_licensing_template'),
			'Which licence it carries is settled; the setting itself is not restated.'
		);
	}

	/**
	 * A run that worked out less than the last one never unsays a link.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSilenceNeverClearsWhatStands(): void
	{
		$this->stand([
			'add_licensing_template' => 2,
			'licensing_template' => 'kept',
			'extends_custom' => 'Abstraction\\Model',
			'implements_custom' => 'Some\\Contract'
		]);

		$written = $this->written();

		$this->assertFalse(
			property_exists($written, 'extends_custom'),
			'The run has nothing to put there, and the record does.'
		);
		$this->assertFalse(property_exists($written, 'implements_custom'));
	}

	/**
	 * A power in a component's own folder is recognised through the person's placeholder.
	 *
	 * The powers live outside the libraries folder, in the component's own
	 * administrator src, and the person stores their namespaces through a
	 * placeholder of their own that stands for that whole head. Aimed at the
	 * Engine folder itself, the run must still recognise the standing power,
	 * leave its namespace exactly as the person stored it, link a reference
	 * to a sibling power by identity, and store a new class under the same
	 * head the way the person stores everything there.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAPowerInAComponentsOwnFolderIsRecognisedAndKeptInThePersonsForm(): void
	{
		$this->load->placeholder(1, '[[[ComponentEngineNamespace]]]', self::ENGINE);
		$this->load
			->power(9, self::TEAM_GUID, 'Team', '[[[ComponentEngineNamespace]]].Team')
			->power(10, self::REFEREE_GUID, 'Referee', '[[[ComponentEngineNamespace]]].Referee');
		$this->item->identity('power', self::TEAM_GUID, 9);
		$engine = 'site/administrator/components/com_demo/src/Engine';
		$this->writeTemporaryFile(
			$engine . '/Team.php',
			"<?php\nnamespace Demo\\Component\\Demo\\Administrator\\Engine;\n\nuse Demo\\Component\\Demo\\Administrator\\Engine\\Referee;\n\n/**\n * The team engine.\n *\n * @since 1.0.0\n */\nfinal class Team\n{\n\tpublic function judge(Referee \$referee): bool\n\t{\n\t\treturn true;\n\t}\n}\n"
		);
		$this->writeTemporaryFile(
			$engine . '/Season.php',
			"<?php\nnamespace Demo\\Component\\Demo\\Administrator\\Engine;\n\n/**\n * The season engine.\n *\n * @since 1.0.0\n */\nfinal class Season\n{\n\tpublic function play(): bool\n\t{\n\t\treturn true;\n\t}\n}\n"
		);

		$extruder = $this->extruder();
		$report = $extruder->reset()
			->library($this->temporaryPath($engine))
			->component(3)
			->extrude();

		$this->assertTrue((bool) $report->get('powers.completed'));
		$this->assertSame(2, $report->get('counts.powers.classes'));
		$this->assertSame(1, $report->get('counts.powers.existing'));

		$team = $extruder->harvested()['classes'][self::TEAM_GUID] ?? null;

		$this->assertNotNull(
			$team,
			'The standing power is recognised by identity, aimed at the Engine '
			. 'folder itself.'
		);
		$this->assertSame('identity', $team['matched']);
		$this->assertSame(
			self::ENGINE . '.Team',
			$team['placeholder'],
			'Engine is a folder under src, so it is a dot part, never a head segment.'
		);
		$this->assertSame('[[[ComponentEngineNamespace]]].Team', $team['standing']);

		$written = $this->item->definition('power', self::TEAM_GUID);

		$this->assertNotNull($written);
		$this->assertFalse(
			property_exists($written, 'namespace'),
			'The form the person stored stands; nothing is restated.'
		);
		$this->assertSame("The team engine.\n\n@since 1.0.0", $written->description);
		$this->assertSame(
			[['use' => self::REFEREE_GUID, 'as' => 'default']],
			array_values(array_map(
				static fn ($row): array => (array) $row,
				(array) $written->use_selection
			)),
			'A reference to a sibling power resolves through the person\'s '
			. 'placeholder and links by identity.'
		);
		$this->assertSame('', $written->head);
		$this->assertNull($report->get('powers.namespace.restated'));

		$season = $this->item->definition(
			'power',
			(new Guid())->derive(['power', self::ENGINE . '.Season'])
		);

		$this->assertNotNull($season);
		$this->assertSame(
			'[[[ComponentEngineNamespace]]].Season',
			$season->namespace,
			'A new class under the same head is stored the way the person '
			. 'stores everything there.'
		);
		$this->assertSame('Demo.Engine.Season', $season->system_name);
	}

	/**
	 * A power recognised only by the class it compiles to follows the file's placement.
	 *
	 * An earlier run stored the class glued onto the head with a backslash,
	 * which the compiler would write one folder too high. The class still
	 * compiles to the same name, so it is the same power -- and the file is
	 * the evidence of where it really sits, so the placement is restated
	 * through the person's placeholder and the restatement is named.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAPowerRecognisedByItsClassNameFollowsTheFilesPlacement(): void
	{
		$this->load->placeholder(1, '[[[ComponentEngineNamespace]]]', self::ENGINE);
		$this->load->power(9, self::TEAM_GUID, 'Team', self::ENGINE . '\Team');
		$this->item->identity('power', self::TEAM_GUID, 9);
		$source = 'site/administrator/components/com_demo/src';
		$this->writeTemporaryFile(
			$source . '/Engine/Team.php',
			"<?php\nnamespace Demo\\Component\\Demo\\Administrator\\Engine;\n\n/**\n * The team engine.\n *\n * @since 1.0.0\n */\nfinal class Team\n{\n\tpublic function play(): bool\n\t{\n\t\treturn true;\n\t}\n}\n"
		);

		$extruder = $this->extruder();
		$report = $extruder->reset()
			->library($this->temporaryPath($source))
			->component(3)
			->extrude();

		$this->assertTrue((bool) $report->get('powers.completed'));

		$team = $extruder->harvested()['classes'][self::TEAM_GUID] ?? null;

		$this->assertNotNull($team, 'The class it compiles to is the standing power.');
		$this->assertSame('class', $team['matched']);

		$written = $this->item->definition('power', self::TEAM_GUID);

		$this->assertNotNull($written);
		$this->assertSame(
			'[[[ComponentEngineNamespace]]].Team',
			$written->namespace,
			'The placement follows the file, expressed through the person\'s placeholder.'
		);
		$this->assertSame(
			['from' => self::ENGINE . '\Team', 'to' => '[[[ComponentEngineNamespace]]].Team'],
			$report->get('powers.namespace.restated.bbbbbbbb_2222_4222_8222_222222222222')
		);
		$this->assertContains(
			'1 existing power(s) were recognised by the class they compile to, '
			. 'and had their stored namespace restated from where the class '
			. 'really sits.',
			$this->extruderMessages('notice')
		);
	}

	/**
	 * The powers extruder under test.
	 *
	 * @return  Extruder  The resolved entry point.
	 * @since   6.1.7
	 */
	private function extruder(): Extruder
	{
		return $this->container->get('Extrusion.Powers.Extruder');
	}

	/**
	 * The shared run configuration.
	 *
	 * @return  Config  The resolved configuration.
	 * @since   6.1.7
	 */
	private function config(): Config
	{
		return $this->container->get('Extrusion.Config');
	}

	/**
	 * The shared run report.
	 *
	 * @return  Report  The resolved report registry.
	 * @since   6.1.7
	 */
	private function report(): Report
	{
		return $this->container->get('Extrusion.Registry.Report');
	}

	/**
	 * The identity the harvest derives for one class.
	 *
	 * A new power's identity comes from its stored namespace, not the name it
	 * happened to be built under, so the same class harvested from two
	 * libraries whose prefixes differ lands on one identity. The fixture
	 * library folds two head segments and defers the first, so the same
	 * conversion is applied here.
	 *
	 * @param   string  $fqn  The fully qualified class name.
	 *
	 * @return  string  The derived identity.
	 * @since   6.1.7
	 */
	private function guid(string $fqn): string
	{
		$segments = explode('\\', trim($fqn, '\\'));
		$class = array_pop($segments);
		$head = array_splice($segments, 0, 2);
		$head[0] = '[[[NamespacePrefix]]]';

		return (new Guid())->derive([
			'power',
			implode('\\', $head) . '\\'
			. implode('.', array_merge($segments, [$class]))
		]);
	}

	/**
	 * The messages of one level, as plain strings.
	 *
	 * @param   string  $level  The message level.
	 *
	 * @return  array<int, string>  The message texts.
	 * @since   6.1.7
	 */
	private function extruderMessages(string $level): array
	{
		return array_column(
			$this->extruder()->messages()[$level] ?? [],
			'message'
		);
	}

	/**
	 * One list sorted, so a comparison ignores discovery order only.
	 *
	 * @param   array<int, string>  $values  The values to sort.
	 *
	 * @return  array<int, string>  The sorted values.
	 * @since   6.1.7
	 */
	private function sorted(array $values): array
	{
		sort($values, SORT_STRING);

		return $values;
	}
}
