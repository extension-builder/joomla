<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion;


use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Extruder;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Message;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Discovery;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Extrusion;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Reader;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Registry as RegistryProvider;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Resolver;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Writer as WriterProvider;
use VDM\Tests\Support\ExtrusionCatalogueFixture;
use VDM\Tests\Support\ExtrusionComponentFixture;
use VDM\Tests\Support\ExtrusionItemFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * The one entry point a caller resolves to consume a component into JCB.
 *
 * Everything here goes through the real graph: the providers compose the actual
 * discovery, reader, resolver and writer classes, and only two things are faked --
 * the JCB data pipeline that would write, and the database that serves the field
 * type catalogue. That is deliberate. The value of this class is not any single
 * method but that a whole source tree arrives as JCB definitions, so a test that
 * stubbed the steps would prove nothing about the thing being sold.
 *
 * Two obligations get particular attention: a rejected option must not corrupt the
 * stored value, and a run must leave no state behind for the next one.
 *
 * @since  6.1.6
 */
#[CoversClass(Extruder::class)]
#[UsesClass(Config::class)]
#[UsesClass(Report::class)]
#[UsesClass(Resolved::class)]
#[UsesClass(Source::class)]
#[UsesClass(Guid::class)]
final class ExtruderTest extends FilesystemTestCase
{
	/**
	 * The per-field identity the modern fixture's table class states.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const STATED_GUID = '11111111-2222-4333-8444-555555555555';

	/**
	 * The recorded JCB data pipeline boundary.
	 *
	 * @var    ExtrusionItemFixture
	 * @since  6.1.6
	 */
	private ExtrusionItemFixture $item;

	/**
	 * The served JCB field type catalogue.
	 *
	 * @var    ExtrusionCatalogueFixture
	 * @since  6.1.6
	 */
	private ExtrusionCatalogueFixture $catalogue;

	/**
	 * The composed extrusion container.
	 *
	 * @var    Container
	 * @since  6.1.6
	 */
	private Container $container;

	/**
	 * Compose the real graph over the two faked boundaries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->item = new ExtrusionItemFixture();
		$this->catalogue = new ExtrusionCatalogueFixture();
		$this->container = new Container();
		$this->container->share('Data.Item', fn (): ExtrusionItemFixture => $this->item);
		$this->container->share('Load', fn (): ExtrusionCatalogueFixture => $this->catalogue);
		$this->container->share('Table', static fn (): Table => new Table(), true);
		$this->container->registerServiceProvider(new RegistryProvider())
			->registerServiceProvider(new Discovery())
			->registerServiceProvider(new Reader())
			->registerServiceProvider(new Resolver())
			->registerServiceProvider(new WriterProvider())
			->registerServiceProvider(new Extrusion());
	}

	/**
	 * Every setter answers with the same instance and writes into the shared config.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEverySetterChainsAndWritesIntoTheSharedConfig(): void
	{
		$extruder = $this->extruder();
		$config = $this->config();

		$this->assertSame($extruder, $extruder->path('/srv/com_demo'));
		$this->assertSame($extruder, $extruder->component(3));
		$this->assertSame($extruder, $extruder->mode(' UPDATE '));
		$this->assertSame($extruder, $extruder->onExisting('Skip'));
		$this->assertSame($extruder, $extruder->layout(' J5 '));
		$this->assertSame($extruder, $extruder->languageTag(' af-ZA '));
		$this->assertSame($extruder, $extruder->tableClass('OFF'));
		$this->assertSame($extruder, $extruder->include(['example_item', 7]));
		$this->assertSame($extruder, $extruder->exclude(['example_log']));
		$this->assertSame($extruder, $extruder->dryRun());
		$this->assertSame($extruder, $extruder->strict());
		$this->assertSame($extruder, $extruder->scope('site'));
		$this->assertSame($extruder, $extruder->limits(4, 250));

		$this->assertSame(
			'update',
			$config->get('mode'),
			'A setter folds and trims its value before the catalogue is consulted.'
		);
		$this->assertSame('skip', $config->get('onExisting'));
		$this->assertSame('j5', $config->get('layout'));
		$this->assertSame('off', $config->get('tableClass'));
		$this->assertSame('af-ZA', $config->get('languageTag'));
		$this->assertNull(
			$this->report()->get('failed.option'),
			'A value that only needed normalising is not a rejected value.'
		);

		$this->assertSame($extruder, $extruder->reset());

		$extruder->path('/srv/com_demo')
			->component(3)
			->mode('update')
			->onExisting('skip')
			->layout('j5')
			->languageTag('af-ZA')
			->tableClass('off')
			->include(['example_item', 7])
			->exclude(['example_log'])
			->dryRun()
			->strict()
			->scope('site')
			->limits(4, 250);

		$this->assertSame('/srv/com_demo', $config->get('path'));
		$this->assertSame(3, $config->get('component'));
		$this->assertSame('update', $config->get('mode'));
		$this->assertSame('skip', $config->get('onExisting'));
		$this->assertSame('j5', $config->get('layout'));
		$this->assertSame('af-ZA', $config->get('languageTag'));
		$this->assertSame('off', $config->get('tableClass'));
		$this->assertSame(['example_item', '7'], $config->get('include'));
		$this->assertSame(['example_log'], $config->get('exclude'));
		$this->assertTrue($config->get('dryRun'));
		$this->assertTrue($config->get('strict'));
		$this->assertTrue($config->get('site'));
		$this->assertSame(4, $config->get('depth'));
		$this->assertSame(250, $config->get('maxFiles'));

		$extruder->component(-8)->limits(0, 0)->dryRun(false)->strict(false)->scope('site', false);

		$this->assertSame(0, $config->get('component'));
		$this->assertSame(1, $config->get('depth'));
		$this->assertSame(1, $config->get('maxFiles'));
		$this->assertFalse($config->get('dryRun'));
		$this->assertFalse($config->get('strict'));
		$this->assertFalse($config->get('site'));
		$this->assertSame($extruder, $this->extruder());
	}

	/**
	 * A value outside an option's catalogue is refused and the stored value stands.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRejectedOptionIsRecordedWithoutCorruptingTheStoredValue(): void
	{
		$extruder = $this->extruder();
		$config = $this->config();
		$report = $this->report();

		$extruder->mode('update')
			->mode('bogus')
			->onExisting('destroy')
			->layout('j9')
			->tableClass('sometimes')
			->languageTag('nl-NL')
			->path('/srv/com_demo');

		$this->assertSame('update', $config->get('mode'));
		$this->assertSame('update', $config->get('onExisting'));
		$this->assertSame('auto', $config->get('layout'));
		$this->assertSame('auto', $config->get('tableClass'));
		$this->assertStringContainsString(
			'rejected "bogus"',
			(string) $report->get('failed.option.mode')
		);
		$this->assertStringContainsString(
			'create, update',
			(string) $report->get('failed.option.mode')
		);
		$this->assertStringContainsString(
			'skip, update, replace',
			(string) $report->get('failed.option.onExisting')
		);
		$this->assertStringContainsString(
			'j3, j4, j5, j6',
			(string) $report->get('failed.option.layout')
		);
		$this->assertStringContainsString(
			'auto, off',
			(string) $report->get('failed.option.tableClass')
		);
		$this->assertSame('nl-NL', $config->get('languageTag'));
		$this->assertSame('/srv/com_demo', $config->get('path'));
		$this->assertNull(
			$report->get('failed.option.languageTag'),
			'An unconstrained option accepts any value.'
		);
		$this->assertNull($report->get('failed.option.path'));
	}

	/**
	 * Reordering the tiers drops the unknown, keeps the order, and appends the rest.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPrecedenceNormalisesTheTierOrder(): void
	{
		$extruder = $this->extruder();
		$config = $this->config();

		$extruder->precedence(['xml', 'nonsense', ' XML ', 'table']);

		$this->assertSame(['xml', 'table', 'notes', 'derived'], $config->get('precedence'));
		$this->assertSame(0, $config->rank('xml'));
		$this->assertSame(3, $config->rank('derived'));

		$extruder->precedence([]);

		$this->assertSame(Config::TIERS, $config->get('precedence'));

		$extruder->precedence(['derived', 'notes', 'xml', 'table']);

		$this->assertSame(['derived', 'notes', 'xml', 'table'], $config->get('precedence'));
		$this->assertSame(0, $config->rank('derived'));
	}

	/**
	 * A run with no source root set stops before discovery and says why.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRunWithoutASourceRootFails(): void
	{
		$report = $this->extruder()->extrude();

		$this->assertSame($this->report(), $report);
		$this->assertFalse($report->get('completed'));
		$this->assertFalse($report->get('dry_run'));
		$this->assertSame('create', $report->get('mode'));
		$this->assertSame(
			[['message' => 'No component source folder and no schema dump were given.']],
			$this->messages()->level('error'),
			'A run with nothing to work on must say so on the bus.'
		);
		$this->assertNull($report->get('counts.artifacts'));
		$this->assertSame([], $this->item->records());
	}

	/**
	 * A tree with no structural source, and a path that is not one, both fail.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRunAgainstAnUnusablePathRecordsDiscoveryFailure(): void
	{
		$bare = $this->tree('bare', [
			'com_bare/com_bare.xml' => str_replace(
				'com_example', 'com_bare', ExtrusionComponentFixture::MANIFEST
			),
			'com_bare/admin/language/en-GB/com_bare.ini' => "COM_BARE=\"Bare\"\n"
		]);
		$report = $this->extruder()->path($bare . '/com_bare')->extrude();

		$this->assertFalse($report->get('completed'));
		$this->assertSame(
			[[
				'message' => 'Nothing was found to extrude: no schema, table definition class '
					. 'or form XML to describe a field with, and no layouts or templates either.',
				'subject' => $bare . '/com_bare'
			]],
			$this->messages()->level('error'),
			'The bus must name what was missing and where.'
		);
		$this->assertNull($report->get('counts.views'));
		$this->assertSame([], $this->item->records());

		$report = $this->extruder()->reset()->path($bare . '/com_missing')->extrude();

		$this->assertFalse($report->get('completed'));
		$this->assertStringContainsString(
			'not a readable directory',
			(string) $report->get('failed.root.' . md5($bare . '/com_missing')),
			'Each unusable root is reported under its own path, because a run may be '
			. 'given several and only some of them may be wrong.'
		);
		$this->assertSame(
			[[
				'message' => 'The given component source is not a readable directory, so '
					. 'nothing could be read from it.',
				'subject' => $bare . '/com_missing'
			]],
			$this->messages()->level('error'),
			'An unusable root must speak on the bus, not only in the report.'
		);
		$this->assertSame([], $this->item->records());
	}

	/**
	 * A reset run inherits nothing from the run before it.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResetClearsTheConfigAndEveryRegistryBetweenRuns(): void
	{
		$extruder = $this->extruder();
		$config = $this->config();
		$report = $this->report();
		$resolved = $this->resolved();
		$source = $this->source();
		$first = $extruder->path($this->modern())
			->component(7)
			->mode('update')
			->extrude();

		$this->assertTrue($first->get('completed'));
		$this->assertSame(
			'update',
			$first->get('mode'),
			'The report echoes the mode the run actually used, not the default.'
		);
		$this->assertSame(2, $first->get('counts.views'));
		$this->assertTrue($first->get('written.field.' . self::STATED_GUID));

		$extruder->reset();

		$this->assertNull($config->get('path'));
		$this->assertSame('create', $config->get('mode'));
		$this->assertSame(0, $config->get('component'));
		$this->assertNull($report->get('completed'));
		$this->assertNull($report->get('counts.views'));
		$this->assertNull($report->get('written'));
		$this->assertNull($resolved->get('views'));
		$this->assertNull($source->get('code_name'));

		$written = count($this->item->records());
		$second = $extruder->path($this->legacy())->component(3)->extrude();

		$this->assertTrue($second->get('completed'));
		$this->assertSame('create', $second->get('mode'));
		$this->assertSame(2, $second->get('counts.views'));
		$this->assertSame(4, $second->get('counts.artifacts'));
		$this->assertSame('com_legacy', $source->get('code_name'));
		$this->assertSame('J3', $source->get('layout'));
		$this->assertNull(
			$second->get('written.field.' . self::STATED_GUID),
			'The second run must not inherit an identity only the first run could write.'
		);
		$this->assertCount(
			3,
			(array) $second->get('written.field'),
			'The report counts only what this run wrote, and two views stating '
			. 'the same field state one field: the rest are linked to it rather '
			. 'than written again under another identity.'
		);
		$this->assertGreaterThan($written, count($this->item->records()));
		$this->assertSame(
			1,
			$this->catalogue->calls(),
			'The field type catalogue is read once per request, not once per run.'
		);
	}

	/**
	 * Harvesting assembles the whole source and writes none of it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testHarvestAssemblesTheSourceWithoutWriting(): void
	{
		$resolved = $this->resolved();
		$report = $this->extruder()->path($this->modern())->component(7)->harvest();

		$this->assertTrue($report->get('completed'));
		$this->assertSame(2, $report->get('counts.views'));
		$this->assertSame(['item', 'category'], $resolved->get('views'));
		$this->assertSame(
			[],
			$this->item->records(),
			'Harvesting must present the run, never perform it.'
		);
	}

	/**
	 * Pairing verdicts govern the write: ignore, retarget, and force new.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testPairingVerdictsGovernWhatIsWritten(): void
	{
		$target = 'dddddddd-4444-4444-8444-444444444444';
		$root = $this->modern();

		// a plain run settles the identities the verdicts will overrule
		$this->extruder()->path($root)->component(7)->extrude();
		$plainItem = null;

		foreach ($this->item->definitions('admin_view') as $definition)
		{
			if ($definition->name_single === 'item')
			{
				$plainItem = $definition->guid;
			}
		}

		$this->assertNotNull($plainItem);
		$before = count($this->item->records());

		$this->container->get('Extrusion.Resolver.Pairing')->load([
			'admin_view' => [
				'category' => ['action' => 'ignore'],
				'item' => ['action' => 'create']
			],
			'field' => [
				'item.name' => ['action' => 'update', 'target' => $target]
			]
		]);
		$report = $this->extruder()->path($root)->component(7)->extrude();

		$this->assertTrue($report->get('completed'));

		$written = array_slice($this->item->records(), $before);
		$views = [];
		$fields = [];

		foreach ($written as $record)
		{
			if ($record['table'] === 'admin_view')
			{
				$views[$record['item']->name_single] = $record['item']->guid;
			}

			if ($record['table'] === 'field')
			{
				$fields[] = $record['item']->guid;
			}
		}

		$this->assertArrayNotHasKey(
			'category',
			$views,
			'An ignored view is mentioned in the report, never written.'
		);
		$this->assertTrue((bool) $report->get('skipped.decision.admin_view.category'));
		$this->assertArrayHasKey('item', $views);
		$this->assertNotSame(
			$plainItem,
			$views['item'],
			'A create verdict forces a fresh identity, even where a match existed.'
		);
		$this->assertContains(
			$target,
			$fields,
			'An update verdict writes the candidate onto the definition the person chose.'
		);
	}

	/**
	 * A second run against a system that already holds the component updates it.
	 *
	 * This is the working case: someone built the component in JCB, kept
	 * developing the real component, and runs the extrusion again. The
	 * component's own link tables say which admin view is theirs and which
	 * fields that view links, so the run updates those very records instead
	 * of creating a second set beside them -- and everything they arranged
	 * around those records survives.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testASecondRunUpdatesTheRecordsTheComponentAlreadyDeclares(): void
	{
		$standing = 'aaaaaaaa-1111-4111-8111-111111111111';
		$standingField = 'bbbbbbbb-2222-4222-8222-222222222222';
		$component = ExtrusionCatalogueFixture::componentGuid(7);

		// what the live system already holds: the component links one admin
		// view, and that view links one field on a tab someone chose
		$this->catalogue
			->table('joomla_component', [
				['id' => 7, 'guid' => $component, 'system_name' => 'Demo',
					'name_code' => 'example']
			])
			->table('component_admin_views', [
				['joomla_component' => $component, 'addadmin_views' => json_encode([
					'addadmin_views0' => [
						'adminview' => $standing,
						'icomoon' => 'shield',
						'mainmenu' => '',
						'order' => '9'
					]
				])]
			])
			->table('admin_view', [
				['guid' => $standing, 'name_single' => 'item', 'name_list' => 'items',
					'system_name' => 'Item']
			])
			->table('admin_fields', [
				['admin_view' => $standing, 'addfields' => json_encode([
					'addfields0' => [
						'field' => $standingField,
						'tab' => '15',
						'alignment' => 4,
						'order_edit' => '2'
					]
				])]
			])
			->table('field', [
				['guid' => $standingField, 'name' => 'Name']
			]);

		// the write boundary knows the same records stand, exactly as the
		// live pipeline finds them when it looks the identity up
		$this->item
			->identity('admin_view', $standing, 47)
			->identity('field', $standingField, 48);

		$report = $this->extruder()->path($this->modern())->component(7)->extrude();

		$this->assertTrue($report->get('completed'));

		$view = $this->item->definition('admin_view', $standing);

		$this->assertNotNull(
			$view,
			'The view the component itself links is the record the run updates.'
		);
		$this->assertSame('item', $view->name_single);
		$this->assertObjectNotHasProperty(
			'addtabs',
			$view,
			'A view that already exists keeps the tabs someone arranged.'
		);

		$links = $this->item->records('admin_fields');
		$fields = [];

		foreach ($links as $link)
		{
			if ($link['item']->admin_view === $standing)
			{
				$fields = $this->decode($link['item']->addfields);
			}
		}

		$this->assertSame(
			['field' => $standingField, 'tab' => '15', 'alignment' => 4, 'order_edit' => '2'],
			$fields['addfields0'] ?? null,
			'The field link someone arranged survives the re-run exactly as it stood.'
		);
		$this->assertGreaterThan(
			1,
			count($fields),
			'Columns the view does not yet link are appended beside what stands.'
		);

		$componentLink = $this->item->definitions('component_admin_views')[0] ?? null;

		$this->assertNotNull($componentLink);

		$linked = $this->decode($componentLink->addadmin_views);

		$this->assertSame(
			['adminview' => $standing, 'icomoon' => 'shield', 'mainmenu' => '', 'order' => '9'],
			$linked['addadmin_views0'],
			'The component link settings someone chose survive the re-run verbatim.'
		);
	}

	/**
	 * The whole modern tree becomes the complete definition set.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
		public function testFullRunAgainstTheModernTreeProducesTheDefinitionSet(): void
	{
		$resolved = $this->resolved();
		$report = $this->extruder()->path($this->modern())->component(7)->extrude();

		$this->assertTrue($report->get('completed'));
		$this->assertFalse($report->get('dry_run'));
		$this->assertSame(5, $report->get('counts.artifacts'));
		$this->assertSame(2, $report->get('counts.views'));
		$this->assertSame(
			[
				'joomla_component' => 1,
				'field' => 8,
				'admin_view' => 2,
				'admin_fields' => 2,
				'admin_fields_conditions' => 1,
				'dynamic_get' => 0,
				'site_view' => 0,
				'component_site_views' => 0,
				'custom_admin_view' => 0,
				'component_custom_admin_views' => 0,
				'component_admin_views' => 2
			],
			(array) $report->get('written_counts')
		);
		$this->assertSame(
			'a table view answers for this template',
			$report->get('skipped.custom_admin_view.item'),
			'An admin view\'s own template is generated output, never a custom admin view.'
		);
		$this->assertSame(16, $report->get('counts.written'));
		$this->assertSame(['item', 'category'], $resolved->get('views'));
		$this->assertSame(
			['joomla_component' => 1, 'field' => 8, 'admin_view' => 2,
				'admin_fields' => 2, 'admin_fields_conditions' => 1,
				'component_admin_views' => 1],
			$this->tallied(),
			'A view\'s own PHP is its author\'s, never a record: nothing here is '
			. 'recovered from the files a component builds its screens with.'
		);

		$title = $this->item->definition('field', self::STATED_GUID);

		$this->assertNotNull(
			$title,
			'A source that came out of JCB keeps the identity its table class stated.'
		);
		$this->assertSame('Name', $title->name);
		$this->assertSame(ExtrusionCatalogueFixture::identity('Text'), $title->fieldtype);
		$this->assertSame(0, $title->store);
		$this->assertTrue($resolved->get('view.item.roles.name.title'));
		$this->assertTrue($resolved->get('view.item.roles.name.list'));
		$this->assertSame('table', $report->get('roles.item.origin'));
		$this->assertSame(
			1,
			$this->item->definition('field', '66666666-7777-4888-8999-aaaaaaaaaaaa')->store,
			'A base64 store is written as the JCB store code, never applied to values.'
		);
		$this->assertSame(
			2,
			$this->item->definition('field', 'bbbbbbbb-cccc-4ddd-8eee-ffffffffffff')->store
		);
		$this->assertSame(['Item Details', 'Metrics'], $resolved->get('view.item.tabs'));
		$this->assertSame(['Details'], $resolved->get('view.category.tabs'));
		$this->assertCount(2, (array) $resolved->get('view.item.conditions'));
		$this->assertSame(2, $report->get('conditions.item'));
		$this->assertCount(
			2,
			$this->decode($this->item->definitions('admin_fields_conditions')[0]->addconditions)
		);

		$this->assertSame(
			[],
			$this->item->definitions('layout'),
			'A layout is markup someone wrote, not a record: nothing is lifted '
			. 'out of the files a component builds its screens with.'
		);

		$seeded = $this->item->definition(
			'admin_view',
			(string) $resolved->get('view.category.written.view.guid')
		);

		$this->assertNotNull($seeded);
		$this->assertStringContainsString('INSERT INTO `#__example_category`', $seeded->sql);
		$this->assertStringContainsString("First; not a split", $seeded->sql);
		$this->assertSame(1, $seeded->add_sql);
		$this->assertSame(
			ExtrusionCatalogueFixture::componentGuid(7),
			$this->item->definitions('component_admin_views')[0]->joomla_component,
			'The link column speaks the component guid, never its id.'
		);
	}

	/**
	 * A full run over a Joomla 3 tree completes on the legacy layout as well.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFullRunAgainstTheLegacyTreeAlsoCompletes(): void
	{
		$resolved = $this->resolved();
		$report = $this->extruder()->path($this->legacy())->component(3)->extrude();
		$guid = (new Guid())->derive(['com_legacy', 'field', 'item', 'name']);

		$this->assertTrue($report->get('completed'));
		$this->assertSame('J3', $this->source()->get('layout'));
		$this->assertSame('com_legacy', $this->source()->get('code_name'));
		$this->assertSame(4, $report->get('counts.artifacts'));
		$this->assertSame(2, $report->get('counts.views'));
		$this->assertSame(16, $report->get('counts.written'));
		$this->assertSame(['item', 'category'], $resolved->get('views'));
		$this->assertSame('derived', $report->get('roles.item.origin'));
		$this->assertTrue(
			$resolved->get('view.item.roles.name.title'),
			'Without a table class the title role is inferred from the column names.'
		);
		$this->assertSame(
			'Item Name',
			$this->item->definition('field', $guid)->name,
			'A JSON note in the column comment outranks the form XML label.'
		);
		$this->assertSame(['Item Details', 'Metrics'], $resolved->get('view.item.tabs'));
		$this->assertCount(2, (array) $resolved->get('view.item.conditions'));
		$this->assertSame(
			['joomla_component' => 1, 'field' => 8, 'admin_view' => 2, 'admin_fields' => 2,
				'admin_fields_conditions' => 1, 'component_admin_views' => 1],
			$this->tallied()
		);
		$this->assertSame(
			[],
			$this->item->definitions('layout'),
			'On the legacy layout either: a view\'s own markup stays its author\'s.'
		);
	}

	/**
	 * A bare schema dump still produces views, honouring the comment notation.
	 *
	 * This is the capability the original dump-driven extruder had, and it must
	 * survive: a folder holding nothing but a .sql file is a complete source. The
	 * JSON note in a column comment stays the author's explicit instruction and
	 * still outranks anything derived from the column itself.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testABareSchemaDumpIsACompleteSource(): void
	{
		$this->writeTemporaryFile('dump/only.sql', <<<'SQL'
CREATE TABLE IF NOT EXISTS `#__demo_widget` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '{"label":"Widget Name","type":"text"}',
	`body` MEDIUMTEXT NOT NULL COMMENT '{"label":"Body Copy","type":"editor"}',
	`rank` INT(10) NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

		$report = $this->extruder()
			->path($this->temporaryPath('dump'))
			->component(4)
			->codeName('com_demo')
			->extrude();

		$this->assertTrue($report->get('completed'), 'A dump alone must be enough to run.');
		$this->assertSame(
			['widget'],
			$this->resolved()->get('views'),
			'A supplied code name must strip the table prefix exactly as the dump-driven extruder did.'
		);

		$fields = (array) $this->resolved()->get('view.widget.field', []);

		$this->assertSame(['name', 'body', 'rank'], array_keys($fields));
		$this->assertSame('Widget Name', $fields['name']['label']['value']);
		$this->assertSame('notes', $fields['name']['label']['origin']);
		$this->assertSame('text', $fields['name']['xml_type']['value']);
		$this->assertSame('editor', $fields['body']['xml_type']['value']);
		$this->assertSame(
			'Rank',
			$fields['rank']['label']['value'],
			'A column carrying no note still gets a readable label.'
		);
		$this->assertSame('derived', $fields['rank']['label']['origin']);
	}

	/**
	 * Dump text alone runs the whole engine, exactly as a folder does.
	 *
	 * This is the seam the component form has always used, and it is now the same
	 * engine a folder goes through. It has no tree to search and no manifest to
	 * read, so everything it recovers comes from the dump itself plus the code name
	 * the caller supplies.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDumpTextAloneIsACompleteSource(): void
	{
		$dump = <<<'SQL'
CREATE TABLE IF NOT EXISTS `#__demo_widget` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '{"label":"Widget Name","type":"text"}',
	`body` MEDIUMTEXT NOT NULL COMMENT '{"label":"Body Copy","type":"editor"}',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

		$report = $this->extruder()->dump($dump)->component(6)->codeName('demo')->extrude();

		$this->assertTrue($report->get('completed'), 'A dump with no path at all must run.');
		$this->assertSame(
			['widget'],
			$this->resolved()->get('views'),
			'The supplied code name is the only thing that can strip the table prefix.'
		);
		$this->assertSame(
			'Widget Name',
			$this->resolved()->get('view.widget.field.name.label.value')
		);
		$this->assertSame(
			[
				'joomla_component' => 1,
				'field' => 2,
				'admin_view' => 1,
				'admin_fields' => 1,
				'admin_fields_conditions' => 0,
				'dynamic_get' => 0,
				'site_view' => 0,
				'component_site_views' => 0,
				'custom_admin_view' => 0,
				'component_custom_admin_views' => 0,
				'component_admin_views' => 1
			],
			(array) $report->get('written_counts'),
			'The dump path runs every writer a folder does; the ones with no source '
			. 'to draw on simply write nothing.'
		);
		$this->assertSame(
			ExtrusionCatalogueFixture::componentGuid(6),
			$this->item->definitions('component_admin_views')[0]->joomla_component,
			'The link column speaks the component guid, never its id.'
		);

		$this->extruder()->reset()->dump($dump)->component(6)->extrude();

		$this->assertSame(
			['demo_widget'],
			$this->resolved()->get('views'),
			'One table alone cannot testify to a prefix, so its whole name is the view.'
		);
		$this->assertContains(
			'The component name could not be established, and the table names do not '
			. 'share a prefix that would imply it, so every view name keeps whatever '
			. 'prefix its table had.',
			array_column($this->messages()->level('warning'), 'message'),
			'A run that could not strip the prefix has to say so.'
		);
	}

	/**
	 * Two or more tables state their own component, so nobody has to be asked.
	 *
	 * Joomla's own convention has a component prefix every table it owns. That makes
	 * the component name a fact about the dump rather than something the caller must
	 * supply, and recovering it is what turns a pasted dump into properly named
	 * views without any further input.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheComponentNameIsRecoveredFromTheTableNames(): void
	{
		$report = $this->extruder()->component(8)->dump(<<<'SQL'
CREATE TABLE IF NOT EXISTS `#__demo_widget` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `#__demo_widget_note` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`body` MEDIUMTEXT NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `#__demo_gadget` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL)->extrude();

		$this->assertTrue($report->get('completed'));
		$this->assertSame(
			'com_demo',
			$this->source()->get('code_name'),
			'The shared part of the table names is the component, cut at an underscore.'
		);
		$this->assertSame('demo', $report->get('source.prefix'));
		$this->assertSame(
			['widget', 'widget_note', 'gadget'],
			$this->resolved()->get('views'),
			'Only the component prefix comes off; a compound view name stays whole.'
		);
		$this->assertContains(
			'No component name was given, so it was taken from the part every table '
			. 'name shares: com_demo.',
			array_column($this->messages()->level('notice'), 'message')
		);
		$this->assertFalse(
			$report->get('source.jcb_built'),
			'A dump whose tables carry no guid is not something JCB built.'
		);

		$this->extruder()->reset()->component(8)->codeName('other')->dump(<<<'SQL'
CREATE TABLE IF NOT EXISTS `#__demo_widget` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `#__demo_gadget` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL)->extrude();

		$this->assertSame(
			'com_other',
			$this->source()->get('code_name'),
			'Inference is the weakest tier and must never overrule a caller who knows.'
		);
	}

	/**
	 * A supplied code name outranks whatever the tree happens to declare.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testASuppliedCodeNameOutranksTheManifest(): void
	{
		$this->extruder()
			->path($this->modern())
			->component(9)
			->codeName('override')
			->extrude();

		$this->assertSame(
			'com_override',
			$this->source()->get('code_name'),
			'A bare name must be normalised and must beat the manifest.'
		);
	}

	/**
	 * Without a table definition class the run degrades rather than fails.
	 *
	 * Most components an extrusion run will meet were never built by JCB, so the
	 * absence of that class is the normal case and not a failure. The structure
	 * must still come across whole; what is lost is the stated truth only the
	 * table class carries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheRunDegradesGracefullyWithoutATableDefinitionClass(): void
	{
		$root = $this->modern();

		$rich = $this->extruder()->path($root)->component(7)->extrude();
		$richViews = (array) $this->resolved()->get('views', []);
		$richRelations = count((array) $this->resolved()->get('view.item.relations', []));
		$richTabs = (array) $this->resolved()->get('view.item.tabs', []);
		$richFields = count((array) $this->resolved()->get('view.item.field', []));

		$this->assertTrue($rich->get('completed'));
		$this->assertGreaterThan(0, $richRelations, 'The table class is the only source of a relationship.');

		$poor = $this->extruder()->reset()
			->path($root)
			->component(7)
			->tableClass('off')
			->extrude();
		$poorViews = (array) $this->resolved()->get('views', []);

		$this->assertTrue($poor->get('completed'), 'A component without a table class must still complete.');
		$this->assertSame($richViews, $poorViews, 'The same views must be recovered either way.');
		$this->assertSame(
			$richFields,
			count((array) $this->resolved()->get('view.item.field', [])),
			'No field may be lost merely because the table class is absent.'
		);
		$this->assertSame(
			[],
			(array) $this->resolved()->get('view.item.relations', []),
			'Relationships cannot be recovered without the table class, and must not be invented.'
		);
		$this->assertSame(
			$richTabs,
			(array) $this->resolved()->get('view.item.tabs', []),
			'Where the form fieldsets carry the same grouping the table class stated, '
			. 'the tabs survive its absence: a weaker source recovering the same answer '
			. 'is the whole point of falling back rather than giving up.'
		);
	}

	/**
	 * A run that lost something says so, and a run that lost nothing stays quiet.
	 *
	 * A partial result that reports itself as an unqualified success is the one
	 * outcome this engine must never produce, because the caller would have no
	 * reason to look at the fields it now has to finish by hand.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testWhatTheRunCouldNotCarryOverIsNamedOnTheBus(): void
	{
		$this->extruder()->path($this->modern())->component(7)->extrude();

		$this->assertSame(
			[['message' => 'Extruded 2 view(s) into 16 JCB definition(s).']],
			$this->messages()->level('success')
		);
		$this->assertSame(
			[],
			$this->messages()->level('notice'),
			'A source that gave everything has no shortfall to report.'
		);

		$this->writeTemporaryFile('thin/only.sql', <<<'SQL'
CREATE TABLE IF NOT EXISTS `#__thin_gadget` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`tagger` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '{"label":"Tagger","type":"mytags"}',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS `#__thin_boiler` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`published` TINYINT(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

		$report = $this->extruder()->reset()
			->path($this->temporaryPath('thin'))
			->component(5)
			->codeName('com_thin')
			->extrude();

		$this->assertTrue($report->get('completed'));
		$this->assertSame(['gadget'], $this->resolved()->get('views'));

		$notices = array_column($this->messages()->level('notice'), 'message', 'subject');

		$this->assertSame(
			'1 field type(s) had no JCB equivalent and were extruded as a custom field, '
			. 'so their options have to be set by hand.',
			$notices['unmapped.fieldtype'] ?? null
		);
		$this->assertSame(
			'1 table(s) described no extrudable field and became no view.',
			$notices['skipped.empty'] ?? null
		);
		$this->assertContains(
			'No JCB table definition class was found, which is normal for a component '
			. 'JCB did not build. Relationships, storage encodings and stated field roles '
			. 'cannot be recovered from anything else.',
			array_column($this->messages()->level('notice'), 'message'),
			'The thinner source must also account for the tier it never had.'
		);
	}

	/**
	 * Materialise the modern fixture tree, table definition class included.
	 *
	 * @return  string  The absolute component root.
	 * @since   6.1.6
	 */
	private function modern(): string
	{
		$files = ExtrusionComponentFixture::modern();
		$files['com_example/admin/powers/Table.php'] = ExtrusionComponentFixture::tableClass();

		return $this->tree('modern', $files) . '/com_example';
	}

	/**
	 * Materialise the Joomla 3 fixture tree.
	 *
	 * @return  string  The absolute component root.
	 * @since   6.1.6
	 */
	private function legacy(): string
	{
		return $this->tree('legacy', ExtrusionComponentFixture::legacy()) . '/com_legacy';
	}

	/**
	 * Write one fixture file map below the temporary root.
	 *
	 * @param   string                $prefix  The relative tree prefix.
	 * @param   array<string,string>  $files   Relative path keyed to its contents.
	 *
	 * @return  string  The absolute tree path.
	 * @since   6.1.6
	 */
	private function tree(string $prefix, array $files): string
	{
		foreach ($files as $relative => $contents)
		{
			$this->writeTemporaryFile($prefix . '/' . $relative, $contents);
		}

		return $this->temporaryPath($prefix);
	}

	/**
	 * How many definitions landed in each JCB table.
	 *
	 * @return  array<string, int>  Table name keyed to its recorded write count.
	 * @since   6.1.6
	 */
	private function tallied(): array
	{
		$tally = [];

		foreach ($this->item->sequence() as $table)
		{
			$tally[$table] = ($tally[$table] ?? 0) + 1;
		}

		return $tally;
	}

	/**
	 * Decode one stored JSON payload into an array.
	 *
	 * @param   string  $json  The stored payload.
	 *
	 * @return  array<string, mixed>  The decoded payload.
	 * @since   6.1.6
	 */
	private function decode($json): array
	{
		// the writers hand the model raw structures now -- the Table class
		// encodes at write time -- so a recorded container is the array itself
		if (is_array($json))
		{
			return $json;
		}

		$decoded = json_decode($json, true);

		$this->assertIsArray($decoded);

		return $decoded;
	}

	/**
	 * The extruder the container composes.
	 *
	 * @return  Extruder  The entry point.
	 * @since   6.1.6
	 */
	private function extruder(): Extruder
	{
		return $this->container->get('Extruder');
	}

	/**
	 * The shared extrusion configuration.
	 *
	 * @return  Config  The configuration.
	 * @since   6.1.6
	 */
	private function config(): Config
	{
		return $this->container->get('Extrusion.Config');
	}

	/**
	 * The shared message bus.
	 *
	 * @return  Message  The message bus.
	 * @since   6.1.6
	 */
	private function messages(): Message
	{
		return $this->container->get('Extrusion.Registry.Message');
	}

	/**
	 * The shared run report.
	 *
	 * @return  Report  The report registry.
	 * @since   6.1.6
	 */
	private function report(): Report
	{
		return $this->container->get('Extrusion.Registry.Report');
	}

	/**
	 * The shared resolved definition registry.
	 *
	 * @return  Resolved  The resolved registry.
	 * @since   6.1.6
	 */
	private function resolved(): Resolved
	{
		return $this->container->get('Extrusion.Registry.Resolved');
	}

	/**
	 * The shared source identity registry.
	 *
	 * @return  Source  The source registry.
	 * @since   6.1.6
	 */
	private function source(): Source
	{
		return $this->container->get('Extrusion.Registry.Source');
	}
	/**
	 * A component is two folders, and the run takes either or both.
	 *
	 * The interface that drives this offers an administrator folder and a site
	 * folder, and someone may have one, the other, or both. Giving both has to reach
	 * exactly what giving their common parent reaches, or the two ways of asking for
	 * the same component would answer differently.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheAdministratorAndSiteFoldersCanBeGivenSeparatelyOrTogether(): void
	{
		$root = $this->split();

		$this->extruder()->adminPath($root . '/admin')->component(2)->extrude();

		$this->assertSame(
			['item', 'category'],
			$this->resolved()->get('views'),
			'The administrator folder alone yields the administrator views.'
		);
		$this->assertSame(
			[],
			(array) $this->resolved()->get('site_view', []),
			'An administrator view own default.php is generated output, never a site view.'
		);

		$this->extruder()->reset()->sitePath($root . '/site')->component(2)->extrude();

		$this->assertSame(
			['looking'],
			array_keys((array) $this->resolved()->get('site_view', [])),
			'The site folder alone yields the site views.'
		);
		$this->assertSame(
			[],
			(array) $this->resolved()->get('views', []),
			'A site folder describes no field, so it builds no administrator view.'
		);

		$report = $this->extruder()->reset()
			->adminPath($root . '/admin')
			->sitePath($root . '/site')
			->component(2)
			->extrude();

		$this->assertTrue($report->get('completed'));
		$this->assertSame(['item', 'category'], $this->resolved()->get('views'));
		$this->assertSame(
			['looking'],
			array_keys((array) $this->resolved()->get('site_view', [])),
			'Given both folders the run reaches both halves in one pass.'
		);
		$this->assertSame(
			[$root . '/admin', $root . '/site'],
			$report->get('source.roots'),
			'Every root the run was given is named in the report.'
		);
		$this->assertSame(1, $report->get('written_counts.site_view'));
		$this->assertSame(1, $report->get('written_counts.component_site_views'));
	}

	/**
	 * A component split across two folders with no common parent worth pointing at.
	 *
	 * @return  string  The absolute tree path holding admin and site.
	 * @since   6.1.6
	 */
	private function split(): string
	{
		return $this->tree('split', [
			'admin/com_example.xml' => ExtrusionComponentFixture::MANIFEST,
			'admin/sql/install.mysql.utf8.sql' => ExtrusionComponentFixture::SCHEMA,
			'admin/forms/item.xml' => ExtrusionComponentFixture::FORM,
			'admin/language/en-GB/com_example.ini' => ExtrusionComponentFixture::LANGUAGE,
			'admin/tmpl/item/default.php' => "<?php\ndefined('_JEXEC') or die;\n?>\n<p>edit</p>",
			'site/tmpl/looking/default.php' => "<?php\ndefined('_JEXEC') or die;\n?>\n<p>look</p>",
			'site/tmpl/looking/default_extra.php' => ExtrusionComponentFixture::LAYOUT
		]);
	}
}
