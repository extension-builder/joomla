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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Form as FormReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Language as LanguageReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Php\Literal;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Schema as SchemaReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Sql\CreateTable;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Sql\Insert;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Sql\Splitter;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Table as TableReader;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Form as FormRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Language as Catalogue;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Schema as SchemaRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Table as TableRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Assembler;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Condition;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Language;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Precedence;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Relation;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Role;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Tab;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Text;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\ViewName;
use VDM\Tests\Support\ExtrusionComponentFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Everything the readers gathered, turned into one resolved definition set.
 *
 * The registries are filled by running the real readers over a materialised
 * source tree, because the join the assembler performs is only real if the
 * schema keys its tables the way a dump does and the table definition class
 * keys them the way a JCB component does. The tree therefore holds a table both
 * registries describe, one only the schema knows, one only the table class
 * knows, and one that carries nothing extrudable at all.
 *
 * @since  6.1.6
 */
#[CoversClass(Assembler::class)]
final class AssemblerTest extends FilesystemTestCase
{
	/**
	 * A table whose every column is Joomla boilerplate.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const BOILERPLATE_SCHEMA = <<<'SQL'
CREATE TABLE IF NOT EXISTS `#__example_boiler` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`published` TINYINT(1) NOT NULL DEFAULT 1,
	`created` DATETIME NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

	/**
	 * A table definition class the schema knows nothing about.
	 *
	 * It adds one field to a table the schema does describe, and one whole
	 * table the schema never mentions, so both halves of the join are tested.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const EXTRA_TABLE_CLASS = <<<'PHP'
<?php
namespace Example\Power;

use VDM\Joomla\Abstraction\BaseTable;
use VDM\Joomla\Interfaces\TableInterface;

class ExtraTable extends BaseTable implements TableInterface
{
	protected array $tables = [
		'example_item' => [
			'extra_note' => [
				'name' => 'extra_note',
				'guid' => 'cccccccc-dddd-4eee-8fff-000000000000',
				'label' => 'Extra Note',
				'type' => 'textarea',
				'title' => false,
				'list' => 'items',
				'store' => 'base64',
				'tab_name' => 'Extra Data',
				'db' => [
					'type' => 'TEXT',
					'default' => 'EMPTY',
					'null_switch' => 'NOT NULL',
				],
				'link' => NULL,
			],
		],
		'example_tag' => [
			'code' => [
				'name' => 'code',
				'guid' => 'dddddddd-eeee-4fff-8000-111111111111',
				'label' => 'Code',
				'type' => 'text',
				'title' => true,
				'list' => 'tags',
				'store' => NULL,
				'tab_name' => 'Details',
				'db' => [
					'type' => 'VARCHAR(64)',
					'default' => '',
					'null_switch' => 'NOT NULL',
				],
				'link' => NULL,
			],
		],
	];
}
PHP;

	/**
	 * The materialised source files, keyed by their role in the tree.
	 *
	 * @var    array<string, string>
	 * @since  6.1.6
	 */
	private array $paths = [];

	/**
	 * The run configuration.
	 *
	 * @var    Config
	 * @since  6.1.6
	 */
	private Config $config;

	/**
	 * The parsed schema registry.
	 *
	 * @var    SchemaRegistry
	 * @since  6.1.6
	 */
	private SchemaRegistry $schema;

	/**
	 * The table definition registry.
	 *
	 * @var    TableRegistry
	 * @since  6.1.6
	 */
	private TableRegistry $table;

	/**
	 * The parsed form registry.
	 *
	 * @var    FormRegistry
	 * @since  6.1.6
	 */
	private FormRegistry $form;

	/**
	 * The language constant catalogue.
	 *
	 * @var    Catalogue
	 * @since  6.1.6
	 */
	private Catalogue $catalogue;

	/**
	 * The resolved definition registry.
	 *
	 * @var    Resolved
	 * @since  6.1.6
	 */
	private Resolved $resolved;

	/**
	 * The source identity registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	private Source $source;

	/**
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	private Report $report;

	/**
	 * The tab resolver, kept to cross-check the stored tab indexes.
	 *
	 * @var    Tab
	 * @since  6.1.6
	 */
	private Tab $tab;

	/**
	 * The subject under test.
	 *
	 * @var    Assembler
	 * @since  6.1.6
	 */
	private Assembler $assembler;

	/**
	 * Materialise the source tree, then start from untouched registries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->paths = [
			'schema' => $this->writeTemporaryFile(
				'com_example/admin/sql/install.mysql.utf8.sql', ExtrusionComponentFixture::SCHEMA
			),
			'boilerplate' => $this->writeTemporaryFile(
				'com_example/admin/sql/boiler.mysql.utf8.sql', self::BOILERPLATE_SCHEMA
			),
			'table' => $this->writeTemporaryFile(
				'com_example/admin/src/Table.php', ExtrusionComponentFixture::tableClass()
			),
			'extra' => $this->writeTemporaryFile(
				'com_example/admin/src/ExtraTable.php', self::EXTRA_TABLE_CLASS
			),
			'form' => $this->writeTemporaryFile(
				'com_example/admin/forms/item.xml', ExtrusionComponentFixture::FORM
			),
			'language' => $this->writeTemporaryFile(
				'com_example/admin/language/en-GB/com_example.ini', ExtrusionComponentFixture::LANGUAGE
			)
		];

		$this->restate();
	}

	/**
	 * Replace every registry and rebuild the assembler around them.
	 *
	 * The registries are shared services, so a second assembly inside one test
	 * has to start from a clean boundary or it would inherit the first one.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function restate(): void
	{
		$this->config = new Config();
		$this->schema = new SchemaRegistry();
		$this->table = new TableRegistry();
		$this->form = new FormRegistry();
		$this->catalogue = new Catalogue();
		$this->resolved = new Resolved();
		$this->source = new Source();
		$this->report = new Report();

		$this->source->set('code_name', 'com_example');

		$this->read();

		$text = new Text();
		$language = new Language($this->catalogue, $this->report);
		$viewname = new ViewName($this->source, $text);
		$this->tab = new Tab($this->form, $language, $this->report);

		$this->assembler = new Assembler(
			$this->config,
			$this->schema,
			$this->table,
			$this->resolved,
			$this->source,
			new Precedence(
				$this->config,
				$this->table,
				$this->schema,
				$this->form,
				$language,
				$text,
				$this->report
			),
			$viewname,
			new Role($this->resolved, $this->report),
			$this->tab,
			new Condition($this->report),
			new Relation($this->config, $viewname, $this->report),
			new Guid(),
			$this->report
		);
	}

	/**
	 * Read every materialised artifact with the readers the pipeline uses.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function read(): void
	{
		$schema = new SchemaReader(
			$this->schema, new Splitter(), new CreateTable(), new Insert(), $this->report
		);
		$table = new TableReader($this->table, new Literal(), $this->report);

		$this->assertTrue($schema->read($this->paths['schema']));
		$this->assertTrue($schema->read($this->paths['boilerplate']));
		$this->assertTrue($table->read($this->paths['table']));
		$this->assertTrue($table->read($this->paths['extra']));
		$this->assertTrue((new FormReader($this->form, $this->report))->read($this->paths['form']));
		$this->assertTrue(
			(new LanguageReader($this->catalogue, $this->report))->read($this->paths['language'])
		);
	}

	/**
	 * The stored tab index of every field of one view.
	 *
	 * @param   string  $view  The JCB view name.
	 *
	 * @return  array<string, int>  Column name keyed to its one-based tab index.
	 * @since   6.1.6
	 */
	private function indexes(string $view): array
	{
		$indexes = [];

		foreach ((array) $this->resolved->get('view.' . $view . '.field') as $column => $properties)
		{
			$indexes[(string) $column] = $properties['tab_index'];
		}

		return $indexes;
	}

	/**
	 * Both registries meet on one canonical identity, each keeping its own key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTablesJoinBothRegistriesOnTheCanonicalIdentity(): void
	{
		$tables = $this->assembler->tables();

		$this->assertSame(
			[
				'example_item' => [
					'name' => '#__example_item',
					'schema' => '___example_item',
					'table' => 'example_item'
				],
				'example_category' => [
					'name' => '#__example_category',
					'schema' => '___example_category',
					'table' => ''
				],
				'example_boiler' => [
					'name' => '#__example_boiler',
					'schema' => '___example_boiler',
					'table' => ''
				],
				'example_tag' => [
					'name' => 'example_tag',
					'schema' => '',
					'table' => 'example_tag'
				]
			],
			$tables
		);

		$this->assertSame('#__example_item', $this->schema->get('table.___example_item.name'));
		$this->assertSame('example_item', $this->table->get('table.example_item.name'));
		$this->assertNotSame($tables['example_item']['schema'], $tables['example_item']['table']);
		$this->assertSame('', $tables['example_category']['table']);
		$this->assertSame('', $tables['example_tag']['schema']);
	}

	/**
	 * Columns come from the schema in declaration order, plus what only the class knows.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testColumnsMergeTheSchemaOrderWithTableOnlyFields(): void
	{
		$columns = $this->assembler->columns(
			['name' => '#__example_item', 'schema' => '___example_item', 'table' => 'example_item']
		);

		$this->assertSame(
			[
				'id', 'name', 'alias', 'description', 'colour',
				'amount', 'counter', 'published', 'created', 'extra_note'
			],
			$columns
		);
		$this->assertSame($columns, array_values(array_unique($columns)));
		$this->assertFalse($this->schema->exists('table.___example_item.column.extra_note'));
		$this->assertSame(
			'extra_note',
			$this->table->get('table.example_item.field.extra_note.name')
		);

		$this->assertSame(
			['id', 'title', 'note'],
			$this->assembler->columns(
				['name' => '#__example_category', 'schema' => '___example_category', 'table' => '']
			)
		);
		$this->assertSame(
			['code'],
			$this->assembler->columns(['name' => 'example_tag', 'schema' => '', 'table' => 'example_tag'])
		);
		$this->assertSame(
			[],
			$this->assembler->columns(['name' => 'example_gone', 'schema' => '', 'table' => ''])
		);
	}

	/**
	 * A table both registries describe still becomes exactly one view.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAssembleProducesEachViewExactlyOnce(): void
	{
		$this->assertSame(3, $this->assembler->assemble());

		$views = (array) $this->resolved->get('views');

		$this->assertSame(['item', 'category', 'tag'], $views);
		$this->assertSame($views, array_values(array_unique($views)));
		$this->assertCount(1, array_keys($views, 'item', true));
		$this->assertSame('example_item', $this->resolved->get('view.item.key'));
		$this->assertSame('#__example_item', $this->resolved->get('view.item.table'));
		$this->assertFalse($this->resolved->exists('view.example_item'));
		$this->assertFalse($this->resolved->exists('view.boiler'));
	}

	/**
	 * Boilerplate never becomes a field, and the filters decide the rest.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBoilerplateColumnsAreSkippedAndTheFiltersAreHonoured(): void
	{
		$this->assembler->assemble();

		$this->assertSame(
			['name', 'alias', 'description', 'colour', 'amount', 'counter', 'extra_note'],
			array_keys((array) $this->resolved->get('view.item.field'))
		);
		$this->assertFalse($this->resolved->exists('view.item.field.id'));
		$this->assertFalse($this->resolved->exists('view.item.field.published'));
		$this->assertFalse($this->resolved->exists('view.item.field.created'));
		$this->assertNull($this->report->get('skipped.filtered'));

		$this->restate();
		$this->config->set('exclude', ['#__example_category']);

		$this->assertSame(2, $this->assembler->assemble());
		$this->assertSame(['item', 'tag'], (array) $this->resolved->get('views'));
		$this->assertSame(
			'#__example_category',
			$this->report->get('skipped.filtered.example_category')
		);
		$this->assertFalse($this->resolved->exists('view.category.name_single'));

		$this->restate();
		$this->config->set('include', ['#__example_item']);

		$this->assertSame(1, $this->assembler->assemble());
		$this->assertSame(['item'], (array) $this->resolved->get('views'));
		$this->assertSame(
			[
				'example_category' => '#__example_category',
				'example_boiler' => '#__example_boiler',
				'example_tag' => 'example_tag'
			],
			(array) $this->report->get('skipped.filtered')
		);
	}

	/**
	 * A table left with nothing extrudable is skipped and recorded as empty.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testATableWithNoResolvableColumnIsRecordedAsEmpty(): void
	{
		$this->assembler->assemble();

		$this->assertSame(
			['#__example_boiler'],
			array_values((array) $this->report->get('skipped.empty'))
		);
		$this->assertSame(
			'#__example_boiler',
			$this->report->get('skipped.empty.example_boiler')
		);
		$this->assertNotContains('boiler', (array) $this->resolved->get('views'));
		$this->assertFalse($this->resolved->exists('view.boiler.name_single'));
		$this->assertSame(
			['id', 'published', 'created'],
			$this->assembler->columns(
				['name' => '#__example_boiler', 'schema' => '___example_boiler', 'table' => '']
			)
		);
	}

	/**
	 * Every view lands complete, and only a seeded table carries its INSERT.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheResolvedRegistryCarriesTheWholeViewDefinition(): void
	{
		$this->assembler->assemble();

		$this->assertSame('item', $this->resolved->get('view.item.name_single'));
		$this->assertSame('items', $this->resolved->get('view.item.name_list'));
		$this->assertSame('Item', $this->resolved->get('view.item.system_name'));
		$this->assertSame('#__example_item', $this->resolved->get('view.item.table'));
		$this->assertSame('example_item', $this->resolved->get('view.item.key'));
		$this->assertSame(
			['Item Details', 'Metrics', 'Extra Data'],
			(array) $this->resolved->get('view.item.tabs')
		);
		$this->assertSame(
			['title' => true, 'alias' => false, 'description' => false, 'list' => true, 'order' => 0],
			(array) $this->resolved->get('view.item.roles.name')
		);
		$this->assertSame('table', $this->report->get('roles.item.origin'));
		$this->assertSame(
			[
				[
					'column' => 'counter',
					'table' => '#__example_category',
					'view' => 'category',
					'entity' => 'category',
					'value' => 'title',
					'key' => 'id',
					'component' => 'com_example',
					'local' => true
				]
			],
			(array) $this->resolved->get('view.item.relations')
		);
		$this->assertSame(
			[
				['match' => 'amount', 'values' => ['0'], 'targets' => ['counter'], 'negate' => true],
				['match' => 'colour', 'values' => ['#ffffff'], 'targets' => ['counter'], 'negate' => false]
			],
			(array) $this->resolved->get('view.item.conditions')
		);
		$this->assertSame(
			(new Guid())->derive(['com_example', 'admin_view', '#__example_item']),
			$this->resolved->get('view.item.guid')
		);

		$this->assertFalse($this->resolved->exists('view.item.seed'));
		$this->assertSame(
			"INSERT INTO `#__example_category` (`id`, `title`) VALUES (1, 'First; not a split')",
			$this->resolved->get('view.category.seed')
		);
		$this->assertSame('categories', $this->resolved->get('view.category.name_list'));
		$this->assertSame([], (array) $this->resolved->get('view.category.relations'));

		$this->assertSame('example_tag', $this->resolved->get('view.tag.table'));
		$this->assertSame('example_tag', $this->resolved->get('view.tag.key'));
		$this->assertSame('Tag', $this->resolved->get('view.tag.system_name'));
		$this->assertSame(['Details'], (array) $this->resolved->get('view.tag.tabs'));
		$this->assertFalse($this->resolved->exists('view.tag.seed'));
	}

	/**
	 * Each field's tab index is one-based and points at its own tab.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEachFieldCarriesAOneBasedTabIndexMatchingItsTab(): void
	{
		$this->assembler->assemble();

		$tabs = (array) $this->resolved->get('view.item.tabs');

		$this->assertSame(
			[
				'name' => 1,
				'alias' => 1,
				'description' => 1,
				'colour' => 1,
				'amount' => 2,
				'counter' => 2,
				'extra_note' => 3
			],
			$this->indexes('item')
		);

		foreach ((array) $this->resolved->get('view.item.field') as $column => $properties)
		{
			$index = $properties['tab_index'];

			$this->assertGreaterThanOrEqual(1, $index);
			$this->assertLessThanOrEqual(count($tabs), $index);
			$this->assertSame($tabs[$index - 1], $this->tab->nameFor('item', $properties));
		}

		$this->assertSame(['title' => 1, 'note' => 1], $this->indexes('category'));
		$this->assertSame(['code' => 1], $this->indexes('tag'));
	}

	/**
	 * A relationship is local only when its target view is part of the run.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testReconcileMarksRelationsLocalOrExternal(): void
	{
		$this->assembler->assemble();

		$relations = (array) $this->resolved->get('view.item.relations');

		$this->assertCount(1, $relations);
		$this->assertSame('category', $relations[0]['view']);
		$this->assertTrue($relations[0]['local']);
		$this->assertNull($this->report->get('relations.external'));
		$this->assertSame(
			'#__example_category via id showing title',
			$this->report->get('relations.item.counter')
		);

		$this->restate();
		$this->config->set('exclude', ['#__example_category']);
		$this->assembler->assemble();

		$external = (array) $this->resolved->get('view.item.relations');

		$this->assertCount(1, $external);
		$this->assertSame('category', $external[0]['view']);
		$this->assertFalse($external[0]['local']);
		$this->assertSame(
			'#__example_category',
			$this->report->get('relations.external.category')
		);

		$this->restate();
		$this->config->set('relations', false);
		$this->assembler->assemble();

		$this->assertSame([], (array) $this->resolved->get('view.item.relations'));
		$this->assertNull($this->report->get('relations.item'));
	}
}
