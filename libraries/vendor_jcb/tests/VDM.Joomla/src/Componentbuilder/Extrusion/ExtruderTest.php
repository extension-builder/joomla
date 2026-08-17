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
			[['message' => 'No component source root and no schema dump were given.']],
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
				'message' => 'No schema, table definition class or form XML was found, so there is '
					. 'nothing to describe any field with.',
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
			(string) $report->get('failed.root')
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
		$this->assertSame(6, $second->get('counts.artifacts'));
		$this->assertSame('com_legacy', $source->get('code_name'));
		$this->assertSame('J3', $source->get('layout'));
		$this->assertNull(
			$second->get('written.field.' . self::STATED_GUID),
			'The second run must not inherit an identity only the first run could write.'
		);
		$this->assertCount(
			8,
			(array) $second->get('written.field'),
			'The report counts only what this run wrote.'
		);
		$this->assertGreaterThan($written, count($this->item->records()));
		$this->assertSame(
			1,
			$this->catalogue->calls(),
			'The field type catalogue is read once per request, not once per run.'
		);
	}

	/**
	 * A full run over a modern tree produces the whole definition set.
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
		$this->assertSame(7, $report->get('counts.artifacts'));
		$this->assertSame(2, $report->get('counts.views'));
		$this->assertSame(
			[
				'field' => 8,
				'admin_view' => 2,
				'admin_fields' => 2,
				'admin_fields_conditions' => 1,
				'admin_custom_tabs' => 1,
				'layout' => 1,
				'template' => 2,
				'component_admin_views' => 2
			],
			(array) $report->get('written_counts')
		);
		$this->assertSame(19, $report->get('counts.written'));
		$this->assertSame(['item', 'category'], $resolved->get('views'));
		$this->assertSame(
			['field' => 8, 'admin_view' => 2, 'admin_fields' => 2,
				'admin_fields_conditions' => 1, 'admin_custom_tabs' => 1,
				'layout' => 1, 'template' => 2, 'component_admin_views' => 1],
			$this->tallied()
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
		$this->assertCount(
			2,
			(array) $this->decode($this->item->definitions('admin_custom_tabs')[0]->tabs)
		);
		$this->assertCount(2, (array) $resolved->get('view.item.conditions'));
		$this->assertSame(2, $report->get('conditions.item'));
		$this->assertCount(
			2,
			$this->decode($this->item->definitions('admin_fields_conditions')[0]->addconditions)
		);

		$layout = $this->item->definitions('layout')[0];

		$this->assertSame('summary', $layout->name);
		$this->assertStringContainsString('$total = count($displayData);', $layout->php_view);
		$this->assertStringContainsString('<div class="example-layout">', $layout->layout);
		$this->assertNotSame(base64_encode($layout->php_view), $layout->php_view);
		$this->assertSame(
			['default', 'default_extra'],
			array_column($this->item->definitions('template'), 'name')
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
			7,
			$this->item->definitions('component_admin_views')[0]->joomla_component
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
		$this->assertSame(6, $report->get('counts.artifacts'));
		$this->assertSame(2, $report->get('counts.views'));
		$this->assertSame(19, $report->get('counts.written'));
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
			['field' => 8, 'admin_view' => 2, 'admin_fields' => 2,
				'admin_fields_conditions' => 1, 'admin_custom_tabs' => 1,
				'layout' => 1, 'template' => 2, 'component_admin_views' => 1],
			$this->tallied()
		);
		$this->assertSame(
			['default', 'default_extra'],
			array_column($this->item->definitions('template'), 'name'),
			'The Joomla 3 view folder holds its templates one level deeper.'
		);
		$this->assertStringContainsString(
			'$total = count($displayData);',
			$this->item->definitions('layout')[0]->php_view
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
			[['message' => 'Extruded 2 view(s) into 19 JCB definition(s).']],
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
	private function decode(string $json): array
	{
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
}
