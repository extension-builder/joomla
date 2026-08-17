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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Reader;


use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\ReaderInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Dispatcher;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Form as FormReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Language as LanguageReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Php\Literal;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Schema as SchemaReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Sql\CreateTable;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Sql\Insert;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Sql\Splitter;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Table as TableReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\View\Layout as LayoutReader;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\View\Split;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\View\Template as TemplateReader;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Form as FormRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Inventory;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Language as LanguageRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Schema as SchemaRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Table as TableRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View as ViewRegistry;
use VDM\Tests\Support\ExtrusionComponentFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Reading an untrusted component source tree into the run registries.
 *
 * Every artifact below is materialised on disk from the run-time fixture and
 * read as text. No file the readers touch is ever included, required, or
 * evaluated, which is the property these tests exist to keep.
 *
 * @since  6.1.6
 */
#[CoversClass(Splitter::class)]
#[CoversClass(CreateTable::class)]
#[CoversClass(Insert::class)]
#[CoversClass(SchemaReader::class)]
#[CoversClass(FormReader::class)]
#[CoversClass(LanguageReader::class)]
#[CoversClass(TableReader::class)]
#[CoversClass(Literal::class)]
#[CoversClass(Split::class)]
#[CoversClass(LayoutReader::class)]
#[CoversClass(TemplateReader::class)]
#[CoversClass(Dispatcher::class)]
final class ReaderTest extends FilesystemTestCase
{
	/**
	 * A CREATE TABLE whose keys are declared at table level only.
	 *
	 * A composite primary key must rank both of its columns, a composite unique
	 * key must rank both of its own, and a plain index must rank nothing at all.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const COMPOSITE = <<<'SQL'
CREATE TABLE `#__pair` (
	`left` INT(11) NULL,
	`right` INT(11) NULL,
	`one` VARCHAR(20) NOT NULL,
	`two` VARCHAR(20) NOT NULL,
	`plain` VARCHAR(20) NOT NULL,
	PRIMARY KEY (`left`,`right`),
	UNIQUE KEY `idx_pair` (`one`,`two`),
	KEY `idx_plain` (`plain`)
)
SQL;

	/**
	 * A CREATE TABLE carrying escaped comments and function style defaults.
	 *
	 * The masked column proves the attribute scan cannot read a keyword that is
	 * only present inside a comment.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const COMMENTED = <<<'SQL'
CREATE TABLE `#__note` (
	`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'it''s mine',
	`note` TEXT NOT NULL COMMENT 'line\nbreak with a \'quote\'',
	`masked` VARCHAR(10) NULL COMMENT 'UNSIGNED AUTO_INCREMENT DEFAULT ''x''',
	`stamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`money` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	PRIMARY KEY (`id`)
)
SQL;

	/**
	 * A view file whose closing tags are mostly content rather than structure.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const TRICKY = <<<'VIEW'
<?php
/**
 * @package    Joomla.Component.Example
 */

namespace Example\Layout;

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

$single = 'not a ?> close';
$double = "also not a ?> close";
$heredoc = <<<HTML
	a heredoc holding ?> inside
HTML;
// a line comment mentioning ?> in prose
$after = 1;
?>
<p>markup <?php echo $after; ?> tail</p>
VIEW;

	/**
	 * A PHP source whose array literal is not a literal at all.
	 *
	 * Reading it must neither define the constant nor declare the class, which
	 * is what proves the parser lexes instead of evaluating.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const HOSTILE = <<<'PHP'
<?php
define('JCB_EXTRUSION_READER_TEST_EVALUATED', true);

class JcbExtrusionReaderTestHostile
{
	protected array $tables = [
		'example_item' => [
			'name' => strtolower('NAME'),
		],
	];
}
PHP;

	/**
	 * A view file whose boilerplate is scattered through the PHP part.
	 *
	 * The guard stands behind a statement rather than in front of one, an import
	 * follows it, and a class body holds both a trait import and a construct that
	 * is shaped exactly like an access guard. Only the top level boilerplate is
	 * the compiler's to regenerate.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const GUARDED = <<<'VIEW'
<?php
$assets = 1;

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

$after = 2;

class Inline
{
	use SomeTrait;

	public function go(): void
	{
		if (defined('X')) { exit; }
	}
}
?>
<p>tail</p>
VIEW;

	/**
	 * A form document that is neither rooted at form nor free of name clashes.
	 *
	 * One field names its fieldset by attribute, one repeats a name already taken
	 * in the same group, one carries no name at all, and two more sit inside a
	 * subform and a fields group respectively.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const GROUPED = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<config>
	<field name="loose" type="text" fieldset="basic" label="LOOSE" />
	<field type="text" label="NAMELESS" />
	<field name="wrapper" type="subform">
		<form>
			<field name="loose" type="text" label="IN SUBFORM" />
		</form>
	</field>
	<fields name="params">
		<fieldset name="advanced" label="ADVANCED">
			<field name="deep" type="text" label="DEEP" />
		</fieldset>
	</fields>
</config>
XML;

	/**
	 * A table definition map in which two fields both claim to be the title.
	 *
	 * It also declares a subform, a property whose value is a literal null, a
	 * second list view, and two entries the reader has to refuse.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const TITLED = <<<'PHP'
<?php
class T
{
	protected array $tables = [
		'example_item' => [
			'first' => [
				'name' => 'first',
				'title' => true,
				'list' => 'items',
				'store' => NULL,
				'fields' => [
					'kid' => ['name' => 'kid', 'type' => 'text'],
					'kid_two' => ['name' => 'kid_two', 'type' => 'text'],
				],
			],
			'second' => [
				'name' => 'second',
				'title' => true,
				'list' => 'others',
			],
			'broken' => 'not an array',
		],
		'example_empty' => [],
	];
}
PHP;

	/**
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	private Report $report;

	/**
	 * The parsed schema registry.
	 *
	 * @var    SchemaRegistry
	 * @since  6.1.6
	 */
	private SchemaRegistry $schema;

	/**
	 * The parsed form registry.
	 *
	 * @var    FormRegistry
	 * @since  6.1.6
	 */
	private FormRegistry $form;

	/**
	 * The language catalogue registry.
	 *
	 * @var    LanguageRegistry
	 * @since  6.1.6
	 */
	private LanguageRegistry $language;

	/**
	 * The table definition registry.
	 *
	 * @var    TableRegistry
	 * @since  6.1.6
	 */
	private TableRegistry $table;

	/**
	 * The classified view layer registry.
	 *
	 * @var    ViewRegistry
	 * @since  6.1.6
	 */
	private ViewRegistry $view;

	/**
	 * Start every test from an untouched set of run registries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->report = new Report();
		$this->schema = new SchemaRegistry();
		$this->form = new FormRegistry();
		$this->language = new LanguageRegistry();
		$this->table = new TableRegistry();
		$this->view = new ViewRegistry();
	}

	/**
	 * A semicolon inside a literal, an identifier, or a comment never splits.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitterKeepsASemicolonThatIsNotAStatementBoundary(): void
	{
		$splitter = new Splitter();
		$statements = $splitter->split(ExtrusionComponentFixture::SCHEMA);

		$this->assertCount(3, $statements);
		$this->assertStringStartsWith('CREATE TABLE IF NOT EXISTS `#__example_item`', $statements[0]);
		$this->assertStringStartsWith('CREATE TABLE IF NOT EXISTS `#__example_category`', $statements[1]);
		$this->assertSame(
			"INSERT INTO `#__example_category` (`id`, `title`) VALUES (1, 'First; not a split')",
			$statements[2]
		);
		$this->assertStringContainsString('First; not a split', $statements[2]);

		$this->assertSame(
			["INSERT INTO `a` VALUES ('x; y')", 'SELECT 1'],
			$splitter->split("INSERT INTO `a` VALUES ('x; y'); SELECT 1;")
		);
		$this->assertSame(
			['CREATE TABLE `we;ird` (`id` INT)', 'SELECT 2'],
			$splitter->split('CREATE TABLE `we;ird` (`id` INT); SELECT 2;')
		);
		$this->assertSame(
			['SELECT 1', 'SELECT 2'],
			$splitter->split("SELECT 1 -- a; b\n; # c; d\nSELECT 2;")
		);
		$this->assertSame(['SELECT   3'], $splitter->split('SELECT /* one; two */ 3;'));
		$this->assertSame([], $splitter->split("\xEF\xBB\xBF   ;  ;  "));
	}

	/**
	 * A double dash only opens a comment when whitespace or the end follows it.
	 *
	 * MySQL's rule, which keeps an expression such as 1--2 whole. Treating every
	 * double dash as a comment would silently truncate the statement it sits in
	 * and take the semicolon that ends it along too.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitterOnlyOpensACommentOnADoubleDashWhitespaceFollows(): void
	{
		$splitter = new Splitter();

		$this->assertSame(['SELECT 1--2'], $splitter->split('SELECT 1--2;'));
		$this->assertSame(
			['SELECT 3 --4', 'SELECT 5'],
			$splitter->split("SELECT 3 --4;\nSELECT 5;")
		);
		$this->assertSame(['SELECT 6'], $splitter->split('SELECT 6 --'));
		$this->assertSame(
			["SELECT 7  \nSELECT 8"],
			$splitter->split("SELECT 7 -- a;\nSELECT 8;")
		);
	}

	/**
	 * Every column property the fixture schema declares is reproduced.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateTableReproducesEveryDeclaredColumnProperty(): void
	{
		$parsed = (new CreateTable())->parse(
			(new Splitter())->split(ExtrusionComponentFixture::SCHEMA)[0]
		);

		$this->assertIsArray($parsed);
		$this->assertSame('#__example_item', $parsed['table']);
		$this->assertSame(
			['id', 'name', 'alias', 'description', 'colour', 'amount', 'counter', 'published', 'created'],
			array_keys($parsed['columns'])
		);

		$this->assertSame([
			'name' => 'id',
			'type' => 'INT',
			'raw_type' => 'INT(11)',
			'size' => '11',
			'unsigned' => false,
			'null' => 'NOT NULL',
			'default' => '',
			'auto_increment' => true,
			'comment' => null,
			'key' => 2,
			'ordinal' => 0
		], $parsed['columns']['id']);

		$this->assertSame([
			'name' => 'name',
			'type' => 'VARCHAR',
			'raw_type' => 'VARCHAR(255)',
			'size' => '255',
			'unsigned' => false,
			'null' => 'NOT NULL',
			'default' => '',
			'auto_increment' => false,
			'comment' => '{"label":"Item Name","type":"text"}',
			'key' => 0,
			'ordinal' => 1
		], $parsed['columns']['name']);

		$this->assertSame('10,2', $parsed['columns']['amount']['size']);
		$this->assertSame('DECIMAL(10,2)', $parsed['columns']['amount']['raw_type']);
		$this->assertSame('0.00', $parsed['columns']['amount']['default']);
		$this->assertSame('CURRENT_TIMESTAMP', $parsed['columns']['created']['default']);
		$this->assertSame('#ffffff', $parsed['columns']['colour']['default']);
		$this->assertTrue($parsed['columns']['counter']['unsigned']);
		$this->assertFalse($parsed['columns']['published']['unsigned']);
		$this->assertNull($parsed['columns']['description']['size']);
		$this->assertSame(8, $parsed['columns']['created']['ordinal']);
	}

	/**
	 * Table level key clauses decide the 2, 1, and 0 key ranks.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateTableHonoursEveryTableLevelKeyClause(): void
	{
		$create = new CreateTable();
		$fixture = $create->parse((new Splitter())->split(ExtrusionComponentFixture::SCHEMA)[0]);

		$this->assertIsArray($fixture);
		$this->assertSame(2, $fixture['columns']['id']['key']);
		$this->assertSame(1, $fixture['columns']['alias']['key']);
		$this->assertSame(0, $fixture['columns']['name']['key']);
		$this->assertSame(0, $fixture['columns']['colour']['key']);

		$composite = $create->parse(self::COMPOSITE);

		$this->assertIsArray($composite);
		$this->assertSame('#__pair', $composite['table']);
		$this->assertSame(2, $composite['columns']['left']['key']);
		$this->assertSame(2, $composite['columns']['right']['key']);
		$this->assertSame(1, $composite['columns']['one']['key']);
		$this->assertSame(1, $composite['columns']['two']['key']);
		$this->assertSame(0, $composite['columns']['plain']['key']);
		$this->assertSame('NOT NULL', $composite['columns']['left']['null']);
		$this->assertSame('NOT NULL', $composite['columns']['right']['null']);
	}

	/**
	 * A comment is unescaped, and a comment's words are not read as attributes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateTableUnescapesACommentWithoutReadingItsKeywords(): void
	{
		$parsed = (new CreateTable())->parse(self::COMMENTED);

		$this->assertIsArray($parsed);
		$this->assertSame("it's mine", $parsed['columns']['id']['comment']);
		$this->assertSame("line\nbreak with a 'quote'", $parsed['columns']['note']['comment']);
		$this->assertSame(
			"UNSIGNED AUTO_INCREMENT DEFAULT 'x'",
			$parsed['columns']['masked']['comment']
		);
		$this->assertFalse($parsed['columns']['masked']['unsigned']);
		$this->assertFalse($parsed['columns']['masked']['auto_increment']);
		$this->assertSame('', $parsed['columns']['masked']['default']);
		$this->assertSame('NULL', $parsed['columns']['masked']['null']);
		$this->assertSame('CURRENT_TIMESTAMP', $parsed['columns']['stamp']['default']);
		$this->assertSame('0.00', $parsed['columns']['money']['default']);
	}

	/**
	 * A statement that is not a CREATE TABLE yields nothing.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateTableRefusesAStatementItDoesNotUnderstand(): void
	{
		$create = new CreateTable();
		$statements = (new Splitter())->split(ExtrusionComponentFixture::SCHEMA);

		$this->assertNull($create->parse($statements[2]));
		$this->assertNull($create->parse('DROP TABLE `#__example_item`'));
		$this->assertNull($create->parse('CREATE TABLE `#__empty` ()'));
		$this->assertNull($create->parse(''));
	}

	/**
	 * An INSERT INTO is recognised and handed back verbatim.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInsertRecognisesAnInsertAndKeepsTheStatementVerbatim(): void
	{
		$insert = new Insert();
		$statements = (new Splitter())->split(ExtrusionComponentFixture::SCHEMA);
		$seed = $insert->parse($statements[2]);

		$this->assertIsArray($seed);
		$this->assertSame('#__example_category', $seed['table']);
		$this->assertSame($statements[2], $seed['sql']);

		$ignored = $insert->parse('INSERT IGNORE INTO `db`.`#__other` (`a`) VALUES (1)');

		$this->assertIsArray($ignored);
		$this->assertSame('#__other', $ignored['table']);

		$this->assertNull($insert->parse($statements[0]));
		$this->assertNull($insert->parse('UPDATE `#__example_category` SET `title` = "x"'));
		$this->assertNull($insert->parse(''));
	}

	/**
	 * The schema reader fills the table, column, and seed paths.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSchemaReaderPopulatesEveryColumnPathAndTheSeedStatement(): void
	{
		$path = $this->writeTemporaryFile(
			'sql/install.mysql.utf8.sql',
			ExtrusionComponentFixture::SCHEMA
		);
		$reader = $this->schemaReader();

		$this->assertTrue($reader->read($path, 'install'));
		$this->assertSame('#__example_item', $this->schema->get('table.___example_item.name'));
		$this->assertSame(
			'name',
			$this->schema->get('table.___example_item.column.name.name')
		);
		$this->assertSame(
			'VARCHAR',
			$this->schema->get('table.___example_item.column.name.type')
		);
		$this->assertSame(
			'{"label":"Item Name","type":"text"}',
			$this->schema->get('table.___example_item.column.name.comment')
		);
		$this->assertSame(2, $this->schema->get('table.___example_item.column.id.key'));
		$this->assertSame(1, $this->schema->get('table.___example_item.column.alias.key'));
		$this->assertTrue($this->schema->get('table.___example_item.column.counter.unsigned'));
		$this->assertSame(6, $this->schema->get('table.___example_item.column.counter.ordinal'));
		$this->assertCount(9, (array) $this->schema->get('table.___example_item.column'));

		$this->assertSame(
			"INSERT INTO `#__example_category` (`id`, `title`) VALUES (1, 'First; not a split')",
			$this->schema->get('seed.___example_category.sql')
		);
		$this->assertSame(
			'#__example_category',
			$this->schema->get('seed.___example_category.name')
		);
		$this->assertFalse($this->schema->exists('seed.___example_item.sql'));

		$this->assertSame(9, $this->report->get('schema.___example_item.columns'));
		$this->assertSame('id', $this->report->get('schema.___example_item.primary'));
		$this->assertSame('alias', $this->report->get('schema.___example_item.unique'));
		$this->assertSame('install', $this->report->get('schema.___example_item.artifact'));
		$this->assertSame(1, $this->report->get('schema.___example_category.seed'));
	}

	/**
	 * A path segment is sanitised, and an unreadable file is reported.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSchemaReaderSanitisesSegmentsAndReportsAnUnreadableFile(): void
	{
		$reader = $this->schemaReader();

		$this->assertSame('___example_item', $reader->key('#__example_item'));
		$this->assertSame('a_b_c', $reader->key('a.b-c'));
		$this->assertSame('_', $reader->key(''));

		$missing = $this->temporaryPath('sql/absent.sql');

		$this->assertFalse($reader->read($missing, 'absent'));
		$this->assertSame($missing, $this->report->get('schema.unreadable.absent_sql'));
		$this->assertSame([], $this->schema->toArray());
	}

	/**
	 * A table seeded twice keeps both statements, and a key clash is recorded.
	 *
	 * A batched dump seeds one table from several statements, so keeping only the
	 * last would silently drop rows. Two table names that differ only in a
	 * character the path sanitiser folds share one registry key, which is a
	 * collision the run has to be told about rather than a silent overwrite.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSchemaReaderJoinsRepeatedSeedsAndRecordsAKeyCollision(): void
	{
		$reader = $this->schemaReader();
		$path = $this->writeTemporaryFile('sql/batched.sql', <<<'SQL'
CREATE TABLE `#__seeded` (`id` INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`));
INSERT INTO `#__seeded` (`id`) VALUES (1);
INSERT INTO `#__seeded` (`id`) VALUES (2);
CREATE TABLE `#__a-b` (`id` INT(11) NOT NULL);
CREATE TABLE `#__a_b` (`id` INT(11) NOT NULL);
SQL);

		$this->assertTrue($reader->read($path, 'install'));
		$this->assertSame(
			"INSERT INTO `#__seeded` (`id`) VALUES (1)\nINSERT INTO `#__seeded` (`id`) VALUES (2)",
			$this->schema->get('seed.___seeded.sql')
		);
		$this->assertSame(2, $this->report->get('schema.___seeded.seed'));

		$this->assertSame('___a_b', $reader->key('#__a-b'));
		$this->assertSame('___a_b', $reader->key('#__a_b'));
		$this->assertSame('#__a_b', $this->schema->get('table.___a_b.name'));
		$this->assertSame('#__a-b | #__a_b', $this->report->get('schema.___a_b.collision'));
		$this->assertFalse($this->report->exists('schema.___seeded.collision'));
	}

	/**
	 * The whole attribute bag, the fieldsets, the options, and the order survive.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFormReaderCapturesTheWholeAttributeBagFieldsetsAndOptions(): void
	{
		$path = $this->writeTemporaryFile('forms/item.xml', ExtrusionComponentFixture::FORM);
		$reader = $this->formReader();

		$this->assertTrue($reader->read($path, 'item'));
		$this->assertSame('item', $this->form->get('view.item.name'));
		$this->assertSame([
			'name' => 'name',
			'type' => 'text',
			'label' => 'COM_EXAMPLE_ITEM_NAME_LABEL',
			'description' => 'COM_EXAMPLE_ITEM_NAME_DESC',
			'size' => '60',
			'required' => 'true',
			'hint' => 'COM_EXAMPLE_ITEM_NAME_HINT',
			'class' => 'form-control'
		], (array) $this->form->get('view.item.field.name.attribute'));
		$this->assertSame(
			'amount!:0[AND]colour:#ffffff',
			$this->form->get('view.item.field.counter.attribute.showon')
		);

		$this->assertSame('details', $this->form->get('view.item.field.name.fieldset'));
		$this->assertSame('details', $this->form->get('view.item.field.colour.fieldset'));
		$this->assertSame('metrics', $this->form->get('view.item.field.amount.fieldset'));
		$this->assertSame('metrics', $this->form->get('view.item.field.counter.fieldset'));
		$this->assertSame(
			'COM_EXAMPLE_ITEM_FIELDSET_DETAILS',
			$this->form->get('view.item.fieldset.details.label')
		);
		$this->assertSame(0, $this->form->get('view.item.fieldset.details.order'));
		$this->assertSame(1, $this->form->get('view.item.fieldset.metrics.order'));

		$this->assertSame([
			'name' => 0,
			'alias' => 1,
			'description' => 2,
			'colour' => 3,
			'amount' => 4,
			'counter' => 5
		], $this->orders());

		$this->assertSame([
			['value' => '1', 'text' => 'COM_EXAMPLE_ITEM_COUNTER_ONE'],
			['value' => '2', 'text' => 'COM_EXAMPLE_ITEM_COUNTER_TWO']
		], (array) $this->form->get('view.item.field.counter.option'));

		$this->assertSame(6, $this->report->get('form.item.fields'));
		$this->assertSame(2, $this->report->get('form.item.fieldsets'));
	}

	/**
	 * A malformed document is reported rather than warned about.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFormReaderRecordsAMalformedDocumentWithoutRaisingAWarning(): void
	{
		$reader = $this->formReader();
		$broken = $this->writeTemporaryFile(
			'forms/broken.xml',
			"<?xml version=\"1.0\"?>\n<form><field name=\"a\"></form>"
		);

		$this->assertFalse($reader->read($broken, 'broken'));
		$this->assertSame($broken, $this->report->get('form.broken.path'));
		$this->assertIsString($this->report->get('form.broken.error'));
		$this->assertStringContainsString('mismatch', $this->report->get('form.broken.error'));
		$this->assertFalse($this->form->exists('view.broken.field'));

		$empty = $this->writeTemporaryFile('forms/empty.xml', "   \n");

		$this->assertFalse($reader->read($empty, 'empty'));
		$this->assertSame('the file could not be read', $this->report->get('form.empty.error'));

		$this->assertSame('a_b_c', $reader->key('a b.c'));
		$this->assertSame('unknown', $reader->key('   '));
	}

	/**
	 * A subform names its group, a fieldset attribute stands in for the element.
	 *
	 * A name repeated inside a different group is a legitimate second field and
	 * gets a numbered key plus a note in the report, a field with no name at all
	 * is counted rather than stored, and a document rooted at something other
	 * than form is read anyway with its root recorded.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFormReaderGroupsSubformFieldsAndNumbersAGenuineNameClash(): void
	{
		$path = $this->writeTemporaryFile('forms/config.xml', self::GROUPED);
		$reader = $this->formReader();

		$this->assertTrue($reader->read($path, 'config'));
		$this->assertSame(
			['loose', 'wrapper', 'loose_2', 'deep'],
			array_keys((array) $this->form->get('view.config.field'))
		);

		$this->assertSame('basic', $this->form->get('view.config.field.loose.fieldset'));
		$this->assertFalse($this->form->exists('view.config.field.loose.subform'));

		$this->assertSame('loose', $this->form->get('view.config.field.loose_2.name'));
		$this->assertSame('wrapper', $this->form->get('view.config.field.loose_2.subform'));
		$this->assertSame('IN SUBFORM', $this->form->get('view.config.field.loose_2.attribute.label'));
		$this->assertSame('loose', $this->report->get('form.config.duplicate.loose'));

		$this->assertSame('params', $this->form->get('view.config.field.deep.subform'));
		$this->assertSame('advanced', $this->form->get('view.config.field.deep.fieldset'));

		$this->assertSame(1, $this->report->get('form.config.unnamed'));
		$this->assertSame('config', $this->report->get('form.config.root'));
		$this->assertSame(4, $this->report->get('form.config.fields'));
		$this->assertSame(1, $this->report->get('form.config.fieldsets'));
	}

	/**
	 * Quotes are stripped once and the legacy quote token becomes a quote.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLanguageReaderStripsOneQuoteLayerAndDecodesTheQuoteTokens(): void
	{
		$reader = $this->languageReader();
		$path = $this->writeTemporaryFile(
			'language/en-GB/com_example.ini',
			ExtrusionComponentFixture::LANGUAGE
		);

		$this->assertTrue($reader->read($path, 'com_example'));
		$this->assertSame('Example', $this->language->get('constant.COM_EXAMPLE'));
		$this->assertSame(
			'The name of the "item" shown to users.',
			$this->language->get('constant.COM_EXAMPLE_ITEM_NAME_DESC')
		);
		$this->assertSame(13, $reader->count());
		$this->assertSame(13, $this->report->get('language.com_example.constants'));
		$this->assertSame(13, $this->report->get('language.com_example.stored'));
		$this->assertSame(0, $this->report->get('language.com_example.kept'));

		$raw = $this->writeTemporaryFile(
			'language/en-GB/raw.ini',
			"RAW_ESCAPED=\"say \\\"hi\\\" now\"\nRAW_PADDED=\"  padded  \"\nRAW_TOKEN=\"a _QQ_x_QQ_ b\"\n"
		);

		$this->assertTrue($reader->read($raw, 'raw'));
		$this->assertSame('say "hi" now', $this->language->get('constant.RAW_ESCAPED'));
		$this->assertSame('  padded  ', $this->language->get('constant.RAW_PADDED'));
		$this->assertSame('a "x" b', $this->language->get('constant.RAW_TOKEN'));
		$this->assertSame('a "x" b " c', $reader->value('a _QQ_x_QQ_ b \\" c'));

		$missing = $this->temporaryPath('language/en-GB/absent.ini');

		$this->assertFalse($reader->read($missing, 'absent'));
		$this->assertSame('the file could not be read', $this->report->get('language.absent.error'));
	}

	/**
	 * The main catalogue keeps its values when the sys catalogue follows.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLanguageReaderLetsTheFirstNonEmptyValueWin(): void
	{
		$reader = $this->languageReader();
		$main = $this->writeTemporaryFile(
			'language/en-GB/com_example.ini',
			"COM_EXAMPLE=\"Example\"\nCOM_EXAMPLE_BLANK=\"\"\n"
		);
		$sys = $this->writeTemporaryFile(
			'language/en-GB/com_example.sys.ini',
			"COM_EXAMPLE=\"Sys Example\"\nCOM_EXAMPLE_BLANK=\"Filled by sys\"\nCOM_EXAMPLE_SYS=\"Sys only\"\n"
		);

		$this->assertTrue($reader->read($main, 'com_example'));
		$this->assertTrue($reader->read($sys, 'com_example.sys'));

		$this->assertSame('Example', $this->language->get('constant.COM_EXAMPLE'));
		$this->assertSame('Filled by sys', $this->language->get('constant.COM_EXAMPLE_BLANK'));
		$this->assertSame('Sys only', $this->language->get('constant.COM_EXAMPLE_SYS'));
		$this->assertSame(3, $reader->count());
		$this->assertSame(3, $this->report->get('language.com_example_sys.constants'));
		$this->assertSame(2, $this->report->get('language.com_example_sys.stored'));
		$this->assertSame(1, $this->report->get('language.com_example_sys.kept'));
	}

	/**
	 * An unquoted value stays the text a translator wrote.
	 *
	 * The ini scanner has three modes and only the raw one leaves a value alone.
	 * The typed scanner turns an unquoted Off into the boolean false and the
	 * normal scanner turns it into an empty string, either of which would silently
	 * blank a language constant whose English happens to be a word ini treats as
	 * a switch.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLanguageReaderKeepsAnUnquotedValueAsTheTextItIs(): void
	{
		$reader = $this->languageReader();
		$path = $this->writeTemporaryFile(
			'language/en-GB/switches.ini',
			"COM_EXAMPLE_OFF=Off\nCOM_EXAMPLE_ON=On\nCOM_EXAMPLE_NO=\"no\"\nCOM_EXAMPLE_NONE=none\n"
		);

		$this->assertTrue($reader->read($path, 'switches'));
		$this->assertSame('Off', $this->language->get('constant.COM_EXAMPLE_OFF'));
		$this->assertSame('On', $this->language->get('constant.COM_EXAMPLE_ON'));
		$this->assertSame('no', $this->language->get('constant.COM_EXAMPLE_NO'));
		$this->assertSame('none', $this->language->get('constant.COM_EXAMPLE_NONE'));
		$this->assertSame(4, $reader->count());
		$this->assertSame(4, $this->report->get('language.switches.stored'));
	}

	/**
	 * A nested literal of scalars, NULL, true, and false is parsed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLiteralParsesANestedArrayOfScalarsAndKeywords(): void
	{
		$literal = new Literal();
		$parsed = $literal->parse(
			$this->writeAndRead('src/Safe.php', ExtrusionComponentFixture::tableClass()),
			'tables'
		);

		$this->assertNull($literal->reason());
		$this->assertIsArray($parsed);
		$this->assertSame(['example_item'], array_keys($parsed));
		$this->assertSame(
			['name', 'description', 'counter'],
			array_keys($parsed['example_item'])
		);
		$this->assertTrue($parsed['example_item']['name']['title']);
		$this->assertFalse($parsed['example_item']['description']['title']);
		$this->assertNull($parsed['example_item']['name']['store']);
		$this->assertNull($parsed['example_item']['name']['link']);
		$this->assertSame('base64', $parsed['example_item']['description']['store']);
		$this->assertSame(1, $parsed['example_item']['counter']['link']['type']);
		$this->assertFalse($parsed['example_item']['name']['db']['unique_key']);
		$this->assertTrue($parsed['example_item']['name']['db']['key']);

		$escapes = $literal->parse(
			"<?php\nclass E { protected array \$tables = ["
			. "'single' => 'it\\'s a \\\\ backslash', "
			. "'double' => \"tab\\there\", "
			. "'negative' => -12, 'real' => 1.5]; }",
			'tables'
		);

		$this->assertNull($literal->reason());
		$this->assertSame("it's a \\ backslash", $escapes['single']);
		$this->assertSame("tab\there", $escapes['double']);
		$this->assertSame(-12, $escapes['negative']);
		$this->assertSame(1.5, $escapes['real']);
	}

	/**
	 * A literal holding a call is refused whole, without the file running.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLiteralRefusesACallWithoutIncludingOrEvaluatingTheSource(): void
	{
		$literal = new Literal();
		$hostile = $this->writeAndRead('src/Hostile.php', self::HOSTILE);

		$this->assertNull($literal->parse($hostile, 'tables'));
		$this->assertIsString($literal->reason());
		$this->assertStringContainsString('strtolower', $literal->reason());
		$this->assertStringContainsString(
			'only NULL, true, and false are accepted',
			$literal->reason()
		);
		$this->assertFalse(defined('JCB_EXTRUSION_READER_TEST_EVALUATED'));
		$this->assertFalse(class_exists('JcbExtrusionReaderTestHostile', false));

		$fixture = $this->writeAndRead('src/Unsafe.php', ExtrusionComponentFixture::unsafeTableClass());

		$this->assertNull($literal->parse($fixture, 'tables'));
		$this->assertStringContainsString('strtolower', (string) $literal->reason());

		$this->assertNull($literal->parse('<?php class N {}', 'tables'));
		$this->assertSame(
			'no $tables array property was declared in the source (line 1)',
			$literal->reason()
		);
		$this->assertNull($literal->parse('', 'tables'));
		$this->assertSame('nothing to parse (line 1)', $literal->reason());

		$this->assertSame(
			['a' => 1],
			$literal->parse("<?php\nclass G { protected array \$tables = ['a' => 1]; }", 'tables')
		);
		$this->assertNull($literal->reason());
	}

	/**
	 * Every declared property of a table definition map is stored.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableReaderStoresEveryDeclaredPropertyOfTheDefinitionMap(): void
	{
		$path = $this->writeTemporaryFile('src/Table.php', ExtrusionComponentFixture::tableClass());
		$reader = $this->tableReader();

		$this->assertTrue($reader->read($path, 'Example\Power\Table'));
		$this->assertSame('example_item', $this->table->get('table.example_item.name'));

		$field = 'table.example_item.field.name';

		$this->assertSame('name', $this->table->get($field . '.name'));
		$this->assertSame(
			'11111111-2222-4333-8444-555555555555',
			$this->table->get($field . '.guid')
		);
		$this->assertSame('COM_EXAMPLE_ITEM_NAME_LABEL', $this->table->get($field . '.label'));
		$this->assertSame('text', $this->table->get($field . '.type'));
		$this->assertTrue($this->table->get($field . '.title'));
		$this->assertSame('items', $this->table->get($field . '.list'));
		$this->assertNull($this->table->get($field . '.store'));
		$this->assertSame('Item Details', $this->table->get($field . '.tab_name'));
		$this->assertSame('VARCHAR(255)', $this->table->get($field . '.db.type'));
		$this->assertSame('', $this->table->get($field . '.db.default'));
		$this->assertSame('NOT NULL', $this->table->get($field . '.db.null_switch'));
		$this->assertFalse($this->table->get($field . '.db.unique_key'));
		$this->assertTrue($this->table->get($field . '.db.key'));

		$link = 'table.example_item.field.counter.link';

		$this->assertSame(1, $this->table->get($link . '.type'));
		$this->assertSame('#__example_category', $this->table->get($link . '.table'));
		$this->assertSame('com_example', $this->table->get($link . '.component'));
		$this->assertSame('category', $this->table->get($link . '.entity'));
		$this->assertSame('title', $this->table->get($link . '.value'));
		$this->assertSame('id', $this->table->get($link . '.key'));
		$this->assertSame('json', $this->table->get('table.example_item.field.counter.store'));

		$this->assertSame('name', $this->table->get('table.example_item.title'));
		$this->assertSame('items', $this->table->get('table.example_item.listview'));
		$this->assertSame('items', $this->table->get('table.example_item.list.counter'));
		$this->assertSame(3, $this->report->get('table.fields'));
		$this->assertSame(1, $this->report->get('table.definition.example_item.links'));
		$this->assertSame('Example\Power\Table', $this->report->get('table.artifact'));
	}

	/**
	 * The first claim wins the title and the list view, and a null is stored.
	 *
	 * JCB's own BaseTable takes the first field that declares itself, so a second
	 * claim is a defect in the source component rather than an override: the first
	 * stands and the clash is reported. A declared null is written to the registry
	 * rather than skipped, because a field that says it has no store is not the
	 * same as a field that says nothing, and the registry answers null to either
	 * question -- so the stored map itself is what has to be inspected.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableReaderKeepsTheFirstTitleAndListViewAndStoresADeclaredNull(): void
	{
		$path = $this->writeTemporaryFile('src/Titled.php', self::TITLED);

		$this->assertTrue($this->tableReader()->read($path, 'Example\Power\T'));

		$this->assertSame('first', $this->table->get('table.example_item.title'));
		$this->assertSame('items', $this->table->get('table.example_item.listview'));
		$this->assertSame('items', $this->table->get('table.example_item.list.first'));
		$this->assertSame('others', $this->table->get('table.example_item.list.second'));
		$this->assertSame(
			'first, second',
			$this->report->get('table.definition.example_item.title_collision')
		);

		$field = $this->table->toArray()['table']['example_item']['field']['first'];

		$this->assertArrayHasKey('store', $field);
		$this->assertNull($field['store']);
		$this->assertSame(
			['kid', 'kid_two'],
			[$field['subfield'][0]['name'], $field['subfield'][1]['name']]
		);
		$this->assertSame(2, $this->report->get('table.definition.example_item.subfields'));

		$this->assertFalse($this->table->exists('table.example_empty.name'));
		$this->assertSame(
			'no field definitions',
			$this->report->get('table.skipped.example_empty')
		);
		$this->assertSame(
			'the field definition is not an array',
			$this->report->get('table.definition.example_item.unusable.broken')
		);
		$this->assertSame(2, $this->report->get('table.fields'));
		$this->assertSame(1, $this->report->get('table.tables'));
	}

	/**
	 * An unsafe map is refused whole and the reason is recorded.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableReaderRefusesAnUnsafeMapAndRecordsTheReason(): void
	{
		$path = $this->writeTemporaryFile(
			'src/Unsafe.php',
			ExtrusionComponentFixture::unsafeTableClass()
		);
		$reader = $this->tableReader();

		$this->assertFalse($reader->read($path));
		$this->assertSame([], $this->table->toArray());
		$this->assertIsString($this->report->get('table.reason'));
		$this->assertStringContainsString('strtolower', $this->report->get('table.reason'));
		$this->assertSame($path, $this->report->get('table.refused'));

		$missing = $this->temporaryPath('src/Absent.php');

		$this->assertFalse($reader->read($missing));
		$this->assertSame(
			'the table definition class could not be read as text',
			$this->report->get('table.reason')
		);
		$this->assertSame($missing, $this->report->get('table.unreadable.Absent_php'));
	}

	/**
	 * The cut is the tag that closes the file's first PHP block.
	 *
	 * The implementation deliberately does not take the file's last closing tag:
	 * on a layout whose every markup line is its own inline block the last tag is
	 * the final line and the HTML part would come out empty. A tag belonging to a
	 * later inline block therefore stays in the HTML part, where it belongs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitCutsTheFileAtTheTagClosingItsFirstPhpBlock(): void
	{
		$parts = (new Split())->split(
			"<?php\n\$a = 1;\n?>\n<p>x</p>\n<?php echo 2; ?>\n<p>y</p>"
		);

		$this->assertSame('$a = 1;', $parts['php']);
		$this->assertSame("<p>x</p>\n<?php echo 2; ?>\n<p>y</p>", $parts['html']);
		$this->assertTrue($parts['add_php']);

		$markup = (new Split())->split("<p>first</p>\n<?php echo 1; ?>\n<p>last</p>");

		$this->assertSame('', $markup['php']);
		$this->assertSame("<p>first</p>\n<?php echo 1; ?>\n<p>last</p>", $markup['html']);
		$this->assertFalse($markup['add_php']);
	}

	/**
	 * A closing tag inside a literal, a heredoc, or a comment never cuts.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitIgnoresACloseTagThatIsContentRatherThanStructure(): void
	{
		$parts = (new Split())->split(self::TRICKY);

		$this->assertSame(
			"\$single = 'not a ?> close';\n"
			. "\$double = \"also not a ?> close\";\n"
			. "\$heredoc = <<<HTML\n"
			. "\ta heredoc holding ?> inside\n"
			. "HTML;\n"
			. "// a line comment mentioning ?> in prose\n"
			. '$after = 1;',
			$parts['php']
		);
		$this->assertSame('<p>markup <?php echo $after; ?> tail</p>', $parts['html']);
		$this->assertTrue($parts['add_php']);
		$this->assertSame(4, substr_count($parts['php'], '?>'));
	}

	/**
	 * A file that never closes its PHP block is all PHP.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitTreatsAFileWithoutACloseTagAsAllPhp(): void
	{
		$parts = (new Split())->split("<?php\ndefined('_JEXEC') or die;\n\n\$a = 1;\n\$b = 2;\n");

		$this->assertSame("\$a = 1;\n\$b = 2;", $parts['php']);
		$this->assertSame('', $parts['html']);
		$this->assertTrue($parts['add_php']);
	}

	/**
	 * A file holding nothing but a guard is all HTML.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitTreatsAGuardOnlyFileAsAllHtml(): void
	{
		$parts = (new Split())->split("<?php\ndefined('_JEXEC') or die;\n?>\n<p>main</p>");

		$this->assertSame('', $parts['php']);
		$this->assertSame('<p>main</p>', $parts['html']);
		$this->assertFalse($parts['add_php']);
	}

	/**
	 * The header JCB regenerates for itself is discarded.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitDiscardsTheHeaderTheCompilerRegenerates(): void
	{
		$parts = (new Split())->split(ExtrusionComponentFixture::LAYOUT);

		$this->assertSame(
			"\$total = count(\$displayData);\n\$label = 'Items';",
			$parts['php']
		);
		$this->assertStringNotContainsString('namespace', $parts['php']);
		$this->assertStringNotContainsString('use Joomla', $parts['php']);
		$this->assertStringNotContainsString('_JEXEC', $parts['php']);
		$this->assertStringNotContainsString('fixture layout header', $parts['php']);
		$this->assertSame(
			"<div class=\"example-layout\">\n"
			. "\t<h3><?php echo \$label; ?> (<?php echo \$total; ?>)</h3>\n"
			. '</div>',
			$parts['html']
		);
	}

	/**
	 * A leading docblock only goes when it is the file header.
	 *
	 * The compiler regenerates the file docblock, so a leading docblock carrying a
	 * header tag is discarded whatever follows it. A leading comment that carries
	 * no such tag is documentation the author wrote about the view and survives,
	 * because a blanket rule would eat a line of php_view on every pass.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitDropsAFileHeaderDocblockButKeepsAViewComment(): void
	{
		$split = new Split();
		$tagged = $split->split("<?php\n/**\n * @package  Example\n */\n\$a = 1;\n?>\n<p>x</p>");

		$this->assertSame('$a = 1;', $tagged['php']);
		$this->assertStringNotContainsString('@package', $tagged['php']);

		$documented = $split->split(
			"<?php\n/**\n * What this layout renders.\n */\n\$a = 1;\n?>\n<p>x</p>"
		);

		$this->assertSame(
			"/**\n * What this layout renders.\n */\n\$a = 1;",
			$documented['php']
		);

		$noted = $split->split("<?php\n// what this layout renders\n\$a = 1;\n?>\n<p>x</p>");

		$this->assertSame("// what this layout renders\n\$a = 1;", $noted['php']);
	}

	/**
	 * Boilerplate goes wherever it stands, and only at the top level.
	 *
	 * A real template sets its assets up before the access guard and imports a
	 * class after it, so a scan that stopped at the first statement would leave
	 * both in php_view and the compiler would emit a duplicate import, which is a
	 * fatal error. The removal takes each cut line's ending with it. Inside a class
	 * body nothing is boilerplate: a trait import and a construct shaped exactly
	 * like an access guard both belong to the view.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitRemovesScatteredBoilerplateWithoutTouchingAClassBody(): void
	{
		$parts = (new Split())->split(self::GUARDED);

		$this->assertSame(
			"\$assets = 1;\n"
			. "\n"
			. "\n"
			. "\n"
			. "\$after = 2;\n"
			. "\n"
			. "class Inline\n"
			. "{\n"
			. "\tuse SomeTrait;\n"
			. "\n"
			. "\tpublic function go(): void\n"
			. "\t{\n"
			. "\t\tif (defined('X')) { exit; }\n"
			. "\t}\n"
			. '}',
			$parts['php']
		);
		$this->assertStringNotContainsString("defined('_JEXEC')", $parts['php']);
		$this->assertStringNotContainsString('No direct access', $parts['php']);
		$this->assertStringNotContainsString('use Joomla', $parts['php']);
		$this->assertSame('<p>tail</p>', $parts['html']);
	}

	/**
	 * Reassembling the two parts round-trips the file body.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSplitReassembleRoundTripsTheBody(): void
	{
		$split = new Split();
		$parts = $split->split(self::TRICKY);
		$body = $split->reassemble($parts);
		$again = $split->split($body);

		$this->assertSame($parts['php'], $again['php']);
		$this->assertSame($parts['html'], $again['html']);
		$this->assertSame($parts['add_php'], $again['add_php']);
		$this->assertSame($body, $split->reassemble($again));
		$this->assertStringStartsWith("<?php\n", $body);
		$this->assertStringContainsString("\n?>\n", $body);

		$guard = $split->split("<?php\ndefined('_JEXEC') or die;\n?>\n<p>main</p>");

		$this->assertSame("<?php\n?>\n<p>main</p>", $split->reassemble($guard));
	}

	/**
	 * A layout and a template store the raw parts and the add_php_view switch.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLayoutAndTemplateReadersStoreTheRawPartsAndTheSwitch(): void
	{
		$layoutPath = $this->writeTemporaryFile(
			'layouts/summary.php',
			ExtrusionComponentFixture::LAYOUT
		);
		$templatePath = $this->writeTemporaryFile(
			'tmpl/item/default.php',
			"<?php\ndefined('_JEXEC') or die;\n?>\n<p>main</p>"
		);

		$this->assertTrue($this->layoutReader()->read($layoutPath, 'summary'));
		$this->assertTrue($this->templateReader()->read($templatePath, 'item_default'));

		$this->assertSame('summary', $this->view->get('layout.summary.name'));
		$this->assertSame(
			"\$total = count(\$displayData);\n\$label = 'Items';",
			$this->view->get('layout.summary.php_view')
		);
		$this->assertStringContainsString(
			'<?php echo $label; ?>',
			$this->view->get('layout.summary.layout')
		);
		$this->assertSame(1, $this->view->get('layout.summary.add_php_view'));
		$this->assertSame($layoutPath, $this->view->get('layout.summary.path'));

		$this->assertSame('', $this->view->get('template.item_default.php_view'));
		$this->assertSame('<p>main</p>', $this->view->get('template.item_default.template'));
		$this->assertSame(0, $this->view->get('template.item_default.add_php_view'));
		$this->assertSame(1, $this->report->get('layout.summary.add_php_view'));
		$this->assertSame(0, $this->report->get('template.item_default.add_php_view'));
		$this->assertSame(11, $this->report->get('template.item_default.template'));
	}

	/**
	 * A view file that cannot be read is reported instead of stored.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLayoutAndTemplateReadersReportAFileTheyCannotRead(): void
	{
		$missing = $this->temporaryPath('layouts/absent.php');

		$this->assertFalse($this->layoutReader()->read($missing, 'absent'));
		$this->assertSame('the file could not be read', $this->report->get('layout.absent.error'));

		$blank = $this->writeTemporaryFile('tmpl/blank.php', "\n\n");

		$this->assertFalse($this->templateReader()->read($blank, 'blank'));
		$this->assertSame('the file could not be read', $this->report->get('template.blank.error'));
		$this->assertSame([], $this->view->toArray());

		$renamed = $this->writeTemporaryFile('layouts/odd.php', ExtrusionComponentFixture::LAYOUT);

		$this->assertTrue($this->layoutReader()->read($renamed, 'default extra'));
		$this->assertSame('default_extra', $this->view->get('layout.default_extra.name'));
		$this->assertSame('default extra', $this->report->get('layout.default_extra.renamed'));
	}

	/**
	 * The language catalogue is read before every other artifact kind.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDispatcherReadsTheLanguageCatalogueBeforeEveryOtherKind(): void
	{
		$order = new ArrayObject();
		$dispatcher = $this->dispatcher($this->inventory(), new Config(), $order);

		$this->assertSame(4, $dispatcher->dispatch());
		$this->assertSame(
			['language', 'table_class', 'schema', 'form'],
			$order->getArrayCopy()
		);
		$this->assertSame('language', $order->getArrayCopy()[0]);
		$this->assertSame(4, $this->report->get('counts.read'));
	}

	/**
	 * A disabled catalogue and a disabled table class are not read at all.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDispatcherHonoursTheLanguageAndTableClassSwitches(): void
	{
		$order = new ArrayObject();
		$config = new Config();
		$config->set('language', false);
		$config->set('tableClass', 'off');
		$dispatcher = $this->dispatcher($this->inventory(), $config, $order);

		$this->assertSame(2, $dispatcher->dispatch());
		$this->assertSame(['schema', 'form'], $order->getArrayCopy());
		$this->assertSame(2, $this->report->get('counts.read'));
	}

	/**
	 * An artifact no reader could read is recorded under failed.read.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDispatcherRecordsAnUnreadableArtifactUnderFailedRead(): void
	{
		$missing = $this->temporaryPath('forms/absent.xml');
		$inventory = new Inventory();
		$inventory->set('form_count', 2);
		$inventory->set('form.0.path', $this->writeTemporaryFile(
			'forms/item.xml',
			ExtrusionComponentFixture::FORM
		));
		$inventory->set('form.0.name', 'item');
		$inventory->set('form.1.path', $missing);
		$inventory->set('form.1.name', 'absent');

		$dispatcher = new Dispatcher(
			new Config(),
			$inventory,
			$this->report,
			$this->languageReader(),
			$this->tableReader(),
			$this->schemaReader(),
			$this->formReader(),
			$this->layoutReader(),
			$this->templateReader()
		);

		$this->assertSame(1, $dispatcher->dispatch());
		$this->assertSame(
			$missing,
			$this->report->get('failed.read.form.' . md5($missing))
		);
		$this->assertFalse(
			$this->report->exists('failed.read.form.' . md5((string) $inventory->get('form.0.path')))
		);
		$this->assertSame(1, $this->report->get('counts.read'));
	}

	/**
	 * Each view role reaches the reader that owns its JCB table.
	 *
	 * The layout table and the template table hold the same two columns, so a file
	 * sent to the wrong one is stored without complaint and surfaces only as a
	 * missing view much later. A role neither reader claims is left alone rather
	 * than guessed at.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDispatcherRoutesEveryViewRoleToItsOwnReader(): void
	{
		$inventory = new Inventory();
		$missing = $this->temporaryPath('tmpl/item/absent.php');
		$roles = [
			['layouts/summary.php', ExtrusionComponentFixture::LAYOUT, 'summary', 'layout'],
			['tmpl/item/default.php', "<?php\ndefined('_JEXEC') or die;\n?>\n<p>main</p>", 'item_default', 'main'],
			['tmpl/item/default_extra.php', "<?php\n\$a = 1;\n?>\n<p>extra</p>", 'item_extra', 'template'],
			['tmpl/item/stray.php', '<p>stray</p>', 'stray', 'partial']
		];
		$inventory->set('view_count', count($roles) + 1);

		foreach ($roles as $index => [$relative, $contents, $name, $role])
		{
			$inventory->set('view.' . $index . '.path', $this->writeTemporaryFile($relative, $contents));
			$inventory->set('view.' . $index . '.name', $name);
			$inventory->set('view.' . $index . '.role', $role);
		}

		$inventory->set('view.4.path', $missing);
		$inventory->set('view.4.name', 'absent');
		$inventory->set('view.4.role', 'layout');

		$dispatcher = new Dispatcher(
			new Config(),
			$inventory,
			$this->report,
			$this->languageReader(),
			$this->tableReader(),
			$this->schemaReader(),
			$this->formReader(),
			$this->layoutReader(),
			$this->templateReader()
		);

		$this->assertSame(3, $dispatcher->dispatch());
		$this->assertSame(['summary'], array_keys((array) $this->view->get('layout')));
		$this->assertSame(
			['item_default', 'item_extra'],
			array_keys((array) $this->view->get('template'))
		);
		$this->assertSame('<p>main</p>', $this->view->get('template.item_default.template'));
		$this->assertSame('$a = 1;', $this->view->get('template.item_extra.php_view'));

		$this->assertFalse($this->view->exists('template.stray.template'));
		$this->assertFalse($this->view->exists('layout.stray.layout'));
		$this->assertFalse($this->report->exists('template.stray.path'));

		$this->assertSame(
			'the file could not be read',
			$this->report->get('layout.absent.error')
		);
		$this->assertFalse($this->view->exists('layout.absent.name'));
		$this->assertSame(3, $this->report->get('counts.read'));
	}

	/**
	 * The inventory shape decides what the dispatcher believes was located.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDispatcherLocatedReadsTheInventoryShape(): void
	{
		$inventory = new Inventory();
		$inventory->set('view_count', 5);
		$inventory->set('view.0.path', '/tree/layouts/summary.php');
		$inventory->set('view.0.name', 'summary');
		$inventory->set('view.0.role', 'layout');
		$inventory->set('view.1.path', '/tree/tmpl/item/default.php');
		$inventory->set('view.2.path', '');
		$inventory->set('view.2.name', 'skipped');
		$inventory->set('view.3.name', 'no path at all');
		$inventory->set('view.4.path', '/tree/tmpl/item/blank.php');
		$inventory->set('view.4.name', '');

		$dispatcher = new Dispatcher(
			new Config(),
			$inventory,
			$this->report,
			$this->languageReader(),
			$this->tableReader(),
			$this->schemaReader(),
			$this->formReader(),
			$this->layoutReader(),
			$this->templateReader()
		);

		$this->assertSame([
			[
				'path' => '/tree/layouts/summary.php',
				'name' => 'summary',
				'role' => 'layout'
			],
			[
				'path' => '/tree/tmpl/item/default.php',
				'name' => null,
				'role' => ''
			],
			[
				'path' => '/tree/tmpl/item/blank.php',
				'name' => null,
				'role' => ''
			]
		], $dispatcher->located('view'));
		$this->assertSame([], $dispatcher->located('schema'));
		$this->assertSame([], $dispatcher->located('nothing_was_located'));
	}

	/**
	 * The schema reader wired to this test's registries.
	 *
	 * @return  SchemaReader  The reader under test.
	 * @since   6.1.6
	 */
	private function schemaReader(): SchemaReader
	{
		return new SchemaReader(
			$this->schema,
			new Splitter(),
			new CreateTable(),
			new Insert(),
			$this->report
		);
	}

	/**
	 * The form reader wired to this test's registries.
	 *
	 * @return  FormReader  The reader under test.
	 * @since   6.1.6
	 */
	private function formReader(): FormReader
	{
		return new FormReader($this->form, $this->report);
	}

	/**
	 * The language reader wired to this test's registries.
	 *
	 * @return  LanguageReader  The reader under test.
	 * @since   6.1.6
	 */
	private function languageReader(): LanguageReader
	{
		return new LanguageReader($this->language, $this->report);
	}

	/**
	 * The table definition reader wired to this test's registries.
	 *
	 * @return  TableReader  The reader under test.
	 * @since   6.1.6
	 */
	private function tableReader(): TableReader
	{
		return new TableReader($this->table, new Literal(), $this->report);
	}

	/**
	 * The layout reader wired to this test's registries.
	 *
	 * @return  LayoutReader  The reader under test.
	 * @since   6.1.6
	 */
	private function layoutReader(): LayoutReader
	{
		return new LayoutReader($this->view, new Split(), $this->report);
	}

	/**
	 * The template reader wired to this test's registries.
	 *
	 * @return  TemplateReader  The reader under test.
	 * @since   6.1.6
	 */
	private function templateReader(): TemplateReader
	{
		return new TemplateReader($this->view, new Split(), $this->report);
	}

	/**
	 * An inventory holding one artifact of every readable kind.
	 *
	 * @return  Inventory  The located artifact registry.
	 * @since   6.1.6
	 */
	private function inventory(): Inventory
	{
		$inventory = new Inventory();

		foreach (['language', 'table_class', 'schema', 'form'] as $kind)
		{
			$inventory->set($kind . '_count', 1);
			$inventory->set($kind . '.0.path', '/tree/' . $kind . '.artifact');
			$inventory->set($kind . '.0.name', $kind);
		}

		return $inventory;
	}

	/**
	 * A dispatcher whose readers record the order they were called in.
	 *
	 * @param   Inventory    $inventory  The located artifact registry.
	 * @param   Config       $config     The run configuration.
	 * @param   ArrayObject  $order      The shared call log.
	 *
	 * @return  Dispatcher  The dispatcher under test.
	 * @since   6.1.6
	 */
	private function dispatcher(Inventory $inventory, Config $config, ArrayObject $order): Dispatcher
	{
		return new Dispatcher(
			$config,
			$inventory,
			$this->report,
			$this->recorder($order, 'language'),
			$this->recorder($order, 'table_class'),
			$this->recorder($order, 'schema'),
			$this->recorder($order, 'form'),
			$this->layoutReader(),
			$this->templateReader()
		);
	}

	/**
	 * A reader that records that it ran and reports success.
	 *
	 * @param   ArrayObject  $order  The shared call log.
	 * @param   string       $kind   The artifact kind this reader stands for.
	 *
	 * @return  ReaderInterface  The recording reader.
	 * @since   6.1.6
	 */
	private function recorder(ArrayObject $order, string $kind): ReaderInterface
	{
		return new class ($order, $kind) implements ReaderInterface
		{
			/**
			 * The shared call log.
			 *
			 * @var    ArrayObject
			 * @since  6.1.6
			 */
			private ArrayObject $order;

			/**
			 * The artifact kind this reader stands for.
			 *
			 * @var    string
			 * @since  6.1.6
			 */
			private string $kind;

			/**
			 * Constructor.
			 *
			 * @param   ArrayObject  $order  The shared call log.
			 * @param   string       $kind   The artifact kind this reader stands for.
			 *
			 * @since   6.1.6
			 */
			public function __construct(ArrayObject $order, string $kind)
			{
				$this->order = $order;
				$this->kind = $kind;
			}

			/**
			 * Record that this kind was read.
			 *
			 * @param   string       $path  Absolute path to the artifact.
			 * @param   string|null  $name  Optional artifact name.
			 *
			 * @return  bool  Always true.
			 * @since   6.1.6
			 */
			public function read(string $path, ?string $name = null): bool
			{
				$this->order->append($this->kind);

				return true;
			}
		};
	}

	/**
	 * Write one file below the temporary root and read it back as text.
	 *
	 * @param   string  $relative  The relative file path.
	 * @param   string  $contents  The complete file contents.
	 *
	 * @return  string  The file contents, as read from disk.
	 * @since   6.1.6
	 */
	private function writeAndRead(string $relative, string $contents): string
	{
		return (string) file_get_contents($this->writeTemporaryFile($relative, $contents));
	}

	/**
	 * The stored order of every field of the fixture form's view.
	 *
	 * @return  array<string, int>  Field key mapped to its stored order.
	 * @since   6.1.6
	 */
	private function orders(): array
	{
		$orders = [];

		foreach ((array) $this->form->get('view.item.field', []) as $key => $field)
		{
			$orders[(string) $key] = (int) ($field['order'] ?? -1);
		}

		return $orders;
	}
}
