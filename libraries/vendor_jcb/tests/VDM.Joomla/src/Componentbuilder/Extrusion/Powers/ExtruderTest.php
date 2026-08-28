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
		$this->assertSame(ExtrusionLibraryFixture::LICENSE, $fetch->licensing_template);
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
	 * ComponentNamespace override, base64 encoded exactly as the compiler
	 * decodes it.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testTheWitnessedValuesAreRecordedOnTheComponent(): void
	{
		$this->writeTemporaryFile(
			'vend/Acme.Joomla/src/DeMo/Helper.php',
			"<?php\nnamespace Acme\\Joomla\\DeMo;\n\n/**\n * The demo helper.\n *\n * @since 1.0.0\n */\nfinal class Helper\n{\n\tpublic function go(): bool\n\t{\n\t\treturn true;\n\t}\n}\n"
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
			['target' => '[[[ComponentNamespace]]]', 'value' => base64_encode('DeMo')],
			(array) ($placeholders->addplaceholders['addplaceholders0'] ?? null),
			'The casing the library was built with is recorded for the compiler '
			. 'to resolve back to.'
		);
		$this->assertSame('DeMo', $report->get('powers.vendor.component_namespace'));
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
