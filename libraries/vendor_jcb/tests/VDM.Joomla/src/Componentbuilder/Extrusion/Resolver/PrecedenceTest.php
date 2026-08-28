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
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source as SourceRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Schema as SchemaRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Table as TableRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Language;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Precedence;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Text;
use VDM\Tests\Support\ExtrusionComponentFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Which source wins for each property of one field, and why.
 *
 * The tiers are fed by running the real readers over a materialised source
 * tree, so a boundary is only proven if the reader, the registry path and the
 * arbitration all agree. Every column of the probe schema isolates exactly one
 * boundary, which is what makes a winner attributable to the tier order rather
 * than to an accident of the fixture.
 *
 * @since  6.1.6
 */
#[CoversClass(Precedence::class)]
final class PrecedenceTest extends FilesystemTestCase
{
	/**
	 * A schema whose columns each isolate one precedence question.
	 *
	 * Every comment is deliberate: valid JSON notes, a plain English comment,
	 * and a JSON list that is syntactically valid but is not a note map.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const PROBE_SCHEMA = <<<'SQL'
CREATE TABLE IF NOT EXISTS `#__example_probe` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`stored` TEXT NOT NULL COMMENT '{"store":"json"}',
	`flagged` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '{"required":"2"}',
	`tinted` CHAR(7) NOT NULL DEFAULT '#ffffff',
	`noted` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '{"label":"Noted Label","type":"radio"}',
	`plain` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'plain english, not json',
	`listed` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '["one","two"]',
	`boxed` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '{"label":"Boxed Label","nested":{"deep":"value"},"class":"boxy"}',
	`ghosted` VARCHAR(32) NOT NULL DEFAULT '',
	`graded` VARCHAR(64) NOT NULL DEFAULT '',
	`amounted` DECIMAL(8,4) NOT NULL,
	`keyed` INT(11) NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

	/**
	 * The probe form, carrying the XML tier's own answers.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const PROBE_FORM = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<form>
	<fieldset name="probe" label="COM_EXAMPLE_PROBE_FIELDSET">
		<field name="flagged" type="radio" label="COM_EXAMPLE_PROBE_FLAGGED_LABEL" required="true" />
		<field name="tinted" type="color" default="blue"
			label="COM_EXAMPLE_PROBE_TINTED_LABEL"
			description="COM_EXAMPLE_PROBE_TINTED_DESC"
			hint="COM_EXAMPLE_PROBE_TINTED_HINT"
			message="COM_EXAMPLE_PROBE_TINTED_MESSAGE" />
		<field name="ghosted" type="text" label="COM_EXAMPLE_PROBE_MISSING_LABEL" />
		<field name="graded" type="list"
			label="COM_EXAMPLE_PROBE_GRADED_LABEL"
			header="COM_EXAMPLE_PROBE_GRADED_HEADER">
			<option value="1">COM_EXAMPLE_PROBE_GRADED_HIGH</option>
			<option value="0">Low</option>
		</field>
	</fieldset>
</form>
XML;

	/**
	 * The probe catalogue, deliberately missing one constant.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const PROBE_LANGUAGE = <<<'INI'
COM_EXAMPLE_PROBE_FIELDSET="Probe Fields"
COM_EXAMPLE_PROBE_FLAGGED_LABEL="Flagged"
COM_EXAMPLE_PROBE_TINTED_LABEL="Tinted"
COM_EXAMPLE_PROBE_TINTED_DESC="The _QQ_tint_QQ_ to use."
COM_EXAMPLE_PROBE_TINTED_HINT="Pick a colour"
COM_EXAMPLE_PROBE_TINTED_MESSAGE="A tint is required"
COM_EXAMPLE_PROBE_GRADED_LABEL="Graded"
COM_EXAMPLE_PROBE_GRADED_HEADER="Grade"
COM_EXAMPLE_PROBE_GRADED_HIGH="High"
INI;

	/**
	 * A table definition class stating what only the top tier can state.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const PROBE_TABLE_CLASS = <<<'PHP'
<?php
namespace Example\Power;

use VDM\Joomla\Abstraction\BaseTable;
use VDM\Joomla\Interfaces\TableInterface;

class ProbeTable extends BaseTable implements TableInterface
{
	protected array $tables = [
		'example_probe' => [
			'stored' => [
				'name' => 'stored',
				'store' => 'base64',
				'db' => [
					'type' => 'TEXT',
					'null_switch' => 'NOT NULL',
				],
			],
			'amounted' => [
				'name' => 'amounted',
				'db' => [
					'type' => 'DECIMAL(10,2)',
					'default' => 'EMPTY',
					'null_switch' => 'NULL',
					'primary_key' => false,
					'unique_key' => true,
				],
			],
			'keyed' => [
				'name' => 'keyed',
				'db' => [
					'type' => 'INT(11)',
					'default' => 'EMPTY',
					'null_switch' => 'NOT NULL',
					'primary_key' => true,
				],
			],
		],
	];
}
PHP;

	/**
	 * The run configuration, which owns the tier order.
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
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	private Report $report;

	/**
	 * The subject under test.
	 *
	 * @var    Precedence
	 * @since  6.1.6
	 */
	private Precedence $precedence;

	/**
	 * Fill every tier from a real source tree read by the real readers.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->config = new Config();
		$this->schema = new SchemaRegistry();
		$this->table = new TableRegistry();
		$this->form = new FormRegistry();
		$this->catalogue = new Catalogue();
		$this->report = new Report();

		$this->read();

		$this->precedence = new Precedence(
			$this->config,
			$this->table,
			$this->schema,
			$this->form,
			new Language($this->catalogue, $this->report, new SourceRegistry()),
			new Text(),
			$this->report
		);
	}

	/**
	 * Materialise the source tree and read every artifact it holds.
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
		$form = new FormReader($this->form, $this->report);
		$language = new LanguageReader($this->catalogue, $this->report);

		$this->assertTrue($schema->read(
			$this->writeTemporaryFile('com_example/sql/install.mysql.utf8.sql', ExtrusionComponentFixture::SCHEMA)
		));
		$this->assertTrue($schema->read(
			$this->writeTemporaryFile('com_example/sql/probe.mysql.utf8.sql', self::PROBE_SCHEMA)
		));
		$this->assertTrue($table->read(
			$this->writeTemporaryFile('com_example/src/Table.php', ExtrusionComponentFixture::tableClass())
		));
		$this->assertTrue($table->read(
			$this->writeTemporaryFile('com_example/src/ProbeTable.php', self::PROBE_TABLE_CLASS)
		));
		$this->assertTrue($form->read(
			$this->writeTemporaryFile('com_example/forms/item.xml', ExtrusionComponentFixture::FORM)
		));
		$this->assertTrue($form->read(
			$this->writeTemporaryFile('com_example/forms/probe.xml', self::PROBE_FORM)
		));
		$this->assertTrue($language->read(
			$this->writeTemporaryFile('com_example/language/en-GB/com_example.ini', ExtrusionComponentFixture::LANGUAGE)
		));
		$this->assertTrue($language->read(
			$this->writeTemporaryFile('com_example/language/en-GB/probe.ini', self::PROBE_LANGUAGE)
		));
	}

	/**
	 * Resolve one column of the fixture's item table.
	 *
	 * @param   string  $column  The source column name.
	 *
	 * @return  array<string, array{value: mixed, origin: string}>  The resolved properties.
	 * @since   6.1.6
	 */
	private function item(string $column): array
	{
		$resolved = $this->precedence->resolve(
			'example_item',
			['schema' => '___example_item', 'table' => 'example_item'],
			$column
		);

		$this->assertIsArray($resolved);

		return $resolved;
	}

	/**
	 * Resolve one column of the probe table.
	 *
	 * @param   string  $column  The source column name.
	 *
	 * @return  array<string, array{value: mixed, origin: string}>  The resolved properties.
	 * @since   6.1.6
	 */
	private function probe(string $column): array
	{
		$resolved = $this->precedence->resolve(
			'example_probe',
			['schema' => '___example_probe', 'table' => 'example_probe'],
			$column
		);

		$this->assertIsArray($resolved);

		return $resolved;
	}

	/**
	 * An unresolved language constant is dropped, never carried as a value.
	 *
	 * JCB stores the language itself and its compiler builds the constants
	 * back from the English -- a stored constant becomes a key built from a
	 * key. And since every view's constants name that view, carrying one also
	 * makes two identical fields look different in the only place they never
	 * were: two views' guid fields with per-view constants resolving to the
	 * same English are one field, and per-view constants nothing answered for
	 * must not split them either.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function testAnUnresolvedConstantIsDroppedNotCarried(): void
	{
		$this->catalogue->set('constant.COM_DEMO_A_GUID_LABEL', 'Guid');

		$language = new Language(
			$this->catalogue,
			$this->report,
			new SourceRegistry()
		);

		$resolved = $language->bag(
			[
				'label' => 'COM_DEMO_A_GUID_LABEL',
				'description' => 'COM_DEMO_A_GUID_DESCRIPTION',
				'hint' => 'Auto Generated'
			],
			['label', 'description', 'hint']
		);

		$this->assertSame('Guid', $resolved['label']);
		$this->assertArrayNotHasKey(
			'description',
			$resolved,
			'A constant nothing answered for is dropped and named in the '
			. 'report, never stored as the value it only names.'
		);
		$this->assertSame(
			'Auto Generated',
			$resolved['hint'],
			'Plain English passes through untouched.'
		);
		$this->assertTrue(
			(bool) $this->report->get('unresolved.language.COM_DEMO_A_GUID_DESCRIPTION')
		);
	}

	/**
	 * The default tier order settles each boundary in favour of the stronger tier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheDefaultTierOrderDecidesEveryContestedBoundary(): void
	{
		$this->assertSame(['table', 'notes', 'xml', 'derived'], $this->config->get('precedence'));

		$stored = $this->probe('stored');
		$flagged = $this->probe('flagged');
		$tinted = $this->probe('tinted');

		$this->assertSame('base64', $stored['store']['value']);
		$this->assertSame('table', $stored['store']['origin']);
		$this->assertSame(
			'{"store":"json"}',
			$this->schema->get('table.___example_probe.column.stored.comment')
		);

		$this->assertSame('2', $flagged['required']['value']);
		$this->assertSame('notes', $flagged['required']['origin']);
		$this->assertSame(
			'true',
			$this->form->get('view.probe.field.flagged.attribute.required')
		);

		$this->assertSame('blue', $tinted['default']['value']);
		$this->assertSame('xml', $tinted['default']['origin']);
		$this->assertSame(
			'#ffffff',
			$this->schema->get('table.___example_probe.column.tinted.default')
		);
	}

	/**
	 * Reordering the tier option moves the win to the promoted tier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testReorderingThePrecedenceOptionChangesTheWinner(): void
	{
		$this->config->set('precedence', ['table', 'xml', 'notes', 'derived']);
		$promoted = $this->probe('flagged');

		$this->assertSame('true', $promoted['required']['value']);
		$this->assertSame('xml', $promoted['required']['origin']);

		$this->config->set('precedence', ['derived', 'xml', 'notes', 'table']);
		$reversed = $this->probe('tinted');

		$this->assertSame('#ffffff', $reversed['default']['value']);
		$this->assertSame('derived', $reversed['default']['origin']);
		$this->assertSame('json', $this->probe('stored')['store']['value']);
		$this->assertSame('notes', $this->probe('stored')['store']['origin']);

		$this->config->set('precedence', Config::TIERS);

		$this->assertSame('base64', $this->probe('stored')['store']['value']);
		$this->assertSame('table', $this->probe('stored')['store']['origin']);
		$this->assertSame('blue', $this->probe('tinted')['default']['value']);
	}

	/**
	 * An unknown tier ranks below every configured one, so it never wins.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testATierMissingFromTheOptionLosesToEveryConfiguredTier(): void
	{
		$this->config->set('precedence', ['notes', 'xml']);
		$stored = $this->probe('stored');

		$this->assertSame('json', $stored['store']['value']);
		$this->assertSame('notes', $stored['store']['origin']);
		$this->assertSame('derived', $stored['label']['origin']);
		$this->assertSame(count(Config::TIERS) + 1, $this->config->rank('table'));
	}

	/**
	 * Two tiers the option never mentioned are settled by the default order.
	 *
	 * Every omitted tier shares one rank, so without a deliberate tie-break the
	 * winner would be whichever tier the resolver happened to ask first, which is
	 * the weakest one. A partial option must not invert the tiers it never named.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTwoUnrankedTiersAreSettledByTheDefaultTierStrength(): void
	{
		$this->config->set('precedence', ['notes', 'xml']);

		$this->assertSame(
			$this->config->rank('derived'),
			$this->config->rank('table')
		);

		$amounted = $this->probe('amounted');

		$this->assertSame('10,2', $amounted['size']['value']);
		$this->assertSame('table', $amounted['size']['origin']);
		$this->assertSame('NULL', $amounted['null']['value']);
		$this->assertSame('table', $amounted['null']['origin']);
		$this->assertSame('8,4', $this->schema->get('table.___example_probe.column.amounted.size'));

		$this->config->set('precedence', ['derived']);
		$promoted = $this->probe('amounted');

		$this->assertSame('8,4', $promoted['size']['value']);
		$this->assertSame('derived', $promoted['size']['origin']);
	}

	/**
	 * Single-tier properties are taken from their only source, or not at all.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUncontestedPropertiesComeOnlyFromTheirOwnTier(): void
	{
		$counter = $this->item('counter');

		$this->assertSame('table', $counter['link']['origin']);
		$this->assertSame('#__example_category', $counter['link']['value']['table']);
		$this->assertSame('category', $counter['link']['value']['entity']);
		$this->assertSame('json', $counter['store']['value']);
		$this->assertSame('table', $counter['store']['origin']);
		$this->assertSame('bbbbbbbb-cccc-4ddd-8eee-ffffffffffff', $counter['guid']['value']);
		$this->assertSame('table', $counter['guid']['origin']);
		$this->assertSame('amount!:0[AND]colour:#ffffff', $counter['showon']['value']);
		$this->assertSame('xml', $counter['showon']['origin']);

		$name = $this->item('name');

		$this->assertSame('11111111-2222-4333-8444-555555555555', $name['guid']['value']);
		$this->assertArrayNotHasKey('store', $name);
		$this->assertArrayNotHasKey('link', $name);
		$this->assertArrayNotHasKey('showon', $name);

		$alias = $this->item('alias');

		$this->assertArrayNotHasKey('link', $alias);
		$this->assertArrayNotHasKey('store', $alias);
		$this->assertArrayNotHasKey('guid', $alias);
		$this->assertArrayNotHasKey('showon', $alias);
	}

	/**
	 * A JSON column comment supplies notes, and its type becomes xml_type.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAJsonColumnCommentSuppliesNotesAndNormalisesTheTypeKey(): void
	{
		$noted = $this->probe('noted');

		$this->assertSame('Noted Label', $noted['label']['value']);
		$this->assertSame('notes', $noted['label']['origin']);
		$this->assertSame('radio', $noted['xml_type']['value']);
		$this->assertSame('notes', $noted['xml_type']['origin']);
		$this->assertArrayNotHasKey('type', $noted);
		$this->assertSame('VARCHAR', $noted['datatype']['value']);
		$this->assertSame('derived', $noted['datatype']['origin']);
	}

	/**
	 * A comment that is not a JSON note map is ignored without error.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAMalformedColumnCommentIsIgnored(): void
	{
		$expected = [
			'datatype', 'db_default_stated', 'key', 'label', 'name', 'null',
			'ordinal', 'size'
		];

		$plain = $this->probe('plain');
		$keys = array_keys($plain);
		sort($keys);

		$this->assertSame(
			'plain english, not json',
			$this->schema->get('table.___example_probe.column.plain.comment')
		);
		$this->assertSame($expected, $keys);
		$this->assertSame('Plain', $plain['label']['value']);
		$this->assertSame('derived', $plain['label']['origin']);

		$listed = $this->probe('listed');
		$keys = array_keys($listed);
		sort($keys);

		$this->assertSame($expected, $keys);
		$this->assertSame('Listed', $listed['label']['value']);
		$this->assertSame('derived', $listed['label']['origin']);
	}

	/**
	 * A note whose value is not scalar is dropped, and its siblings still land.
	 *
	 * A property carries one value into a JCB field definition, so a nested note
	 * has no usable meaning and must not reach the writers as an array.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testANoteWhoseValueIsNotScalarIsDroppedWithoutItsSiblings(): void
	{
		$boxed = $this->probe('boxed');

		$this->assertSame(
			'{"label":"Boxed Label","nested":{"deep":"value"},"class":"boxy"}',
			$this->schema->get('table.___example_probe.column.boxed.comment')
		);
		$this->assertSame('Boxed Label', $boxed['label']['value']);
		$this->assertSame('notes', $boxed['label']['origin']);
		$this->assertSame('boxy', $boxed['class']['value']);
		$this->assertSame('notes', $boxed['class']['origin']);
		$this->assertArrayNotHasKey('nested', $boxed);
		$this->assertArrayNotHasKey('deep', $boxed);
	}

	/**
	 * A prefixed and a bare table name reduce to one canonical identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCanonicalReducesAPrefixedAndABareTableToOneIdentity(): void
	{
		$this->assertSame('example_item', $this->precedence->canonical('#__example_item'));
		$this->assertSame('example_item', $this->precedence->canonical('example_item'));
		$this->assertSame(
			$this->precedence->canonical('example_item'),
			$this->precedence->canonical('#__example_item')
		);
		$this->assertSame('example_item', $this->precedence->canonical('  #__Example-Item  '));
		$this->assertSame('', $this->precedence->canonical(''));
		$this->assertSame('___example_item', $this->precedence->key('#__example_item'));
		$this->assertNotSame(
			$this->precedence->key('#__example_item'),
			$this->precedence->key('example_item')
		);
		$this->assertSame('#__example_item', $this->schema->get('table.___example_item.name'));
		$this->assertSame('example_item', $this->table->get('table.example_item.name'));
	}

	/**
	 * Nothing resolves when no tier knows the column, and the name is always stamped.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolveReturnsNullForAnUnknownColumnAndAlwaysStampsTheName(): void
	{
		$keys = ['schema' => '___example_item', 'table' => 'example_item'];

		$this->assertNull($this->precedence->resolve('example_item', $keys, 'not_a_column'));
		$this->assertNull($this->precedence->resolve('example_item', [], 'not_a_column'));
		$this->assertNull($this->precedence->resolve('example_item', ['schema' => 'no_such_table'], 'not_a_column'));
		$this->assertNull($this->precedence->resolve('unrelated', ['schema' => '', 'table' => ''], 'name'));
		$this->assertIsArray($this->precedence->resolve('example_item', ['schema' => '', 'table' => ''], 'name'));

		$counter = $this->item('counter');

		$this->assertSame('counter', $counter['name']['value']);
		$this->assertSame('derived', $counter['name']['origin']);
		$this->assertSame('alias', $this->item('alias')['name']['value']);
		$this->assertSame('derived', $this->item('alias')['name']['origin']);
	}

	/**
	 * The XML tier resolves every display attribute through the catalogue.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheXmlTierResolvesLanguageConstantsInItsDisplayText(): void
	{
		$tinted = $this->probe('tinted');

		$this->assertSame('Tinted', $tinted['label']['value']);
		$this->assertSame('xml', $tinted['label']['origin']);
		$this->assertSame('The "tint" to use.', $tinted['description']['value']);
		$this->assertSame('xml', $tinted['description']['origin']);
		$this->assertSame('Pick a colour', $tinted['hint']['value']);
		$this->assertSame('xml', $tinted['hint']['origin']);
		$this->assertSame('A tint is required', $tinted['message']['value']);
		$this->assertSame('xml', $tinted['message']['origin']);
		$this->assertSame('Tinted', $tinted['attributes']['value']['label']);
		$this->assertSame('The "tint" to use.', $tinted['attributes']['value']['description']);
		$this->assertSame('xml', $tinted['attributes']['origin']);

		$graded = $this->probe('graded');

		$this->assertSame('Graded', $graded['label']['value']);
		$this->assertSame(
			'Grade',
			$graded['attributes']['value']['header'],
			'Every attribute is resolved through the catalogue, not a chosen few.'
		);
		$this->assertSame(
			['High', 'Low'],
			array_column($graded['options']['value'], 'text'),
			'Option text is stored as the English it stands for, never the constant.'
		);
		$this->assertSame(['1', '0'], array_column($graded['options']['value'], 'value'));

		$ghosted = $this->probe('ghosted');

		$this->assertSame(
			'Ghosted',
			$ghosted['label']['value'],
			'A constant nothing answered for is dropped, never stored as the '
			. 'value it only names -- the compiler builds constants back from '
			. 'the English, and a stored constant becomes a key built from a '
			. 'key. The derived tier\'s own humanised column name stands in.'
		);
		$this->assertSame('derived', $ghosted['label']['origin']);
		$this->assertTrue($this->report->get('unresolved.language.COM_EXAMPLE_PROBE_MISSING_LABEL'));

		$description = $this->item('name')['description'];

		$this->assertSame('The name of the "item" shown to users.', $description['value']);
		$this->assertSame('xml', $description['origin']);
		$this->assertSame('Name', $this->item('name')['label']['value']);
		$this->assertSame('table', $this->item('name')['label']['origin']);
	}

	/**
	 * A form registered under a shorter name is still matched to its view.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAFormRegisteredUnderAShorterViewNameIsStillFound(): void
	{
		$keys = ['schema' => '___example_item', 'table' => 'example_item'];

		$this->assertSame('item', $this->form->get('view.item.name'));

		$aliased = $this->precedence->resolve('example_item', $keys, 'alias');

		$this->assertIsArray($aliased);
		$this->assertSame('Alias', $aliased['label']['value']);
		$this->assertSame('xml', $aliased['label']['origin']);
		$this->assertSame('text', $aliased['xml_type']['value']);
		$this->assertSame('xml', $aliased['xml_type']['origin']);

		$deeper = $this->precedence->resolve('com_example_item', $keys, 'alias');

		$this->assertIsArray($deeper);
		$this->assertSame('xml', $deeper['label']['origin']);

		$unrelated = $this->precedence->resolve('unrelated', $keys, 'alias');

		$this->assertIsArray($unrelated);
		$this->assertSame('Alias', $unrelated['label']['value']);
		$this->assertSame('derived', $unrelated['label']['origin']);
		$this->assertArrayNotHasKey('xml_type', $unrelated);
	}

	/**
	 * The table tier's db block states the column's storage shape.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheTableDbBlockSuppliesTypeSizeNullAndKeyStatus(): void
	{
		$amounted = $this->probe('amounted');

		$this->assertSame('DECIMAL', $amounted['datatype']['value']);
		$this->assertSame('table', $amounted['datatype']['origin']);
		$this->assertSame('10,2', $amounted['size']['value']);
		$this->assertSame('table', $amounted['size']['origin']);
		$this->assertSame('DECIMAL(10,2)', $amounted['raw_type']['value']);
		$this->assertSame('NULL', $amounted['null']['value']);
		$this->assertSame('table', $amounted['null']['origin']);
		$this->assertSame(
			2,
			$amounted['key']['value'],
			'A unique key ranks above a plain index and below a primary.'
		);
		$this->assertSame('table', $amounted['key']['origin']);
		$this->assertArrayNotHasKey('default', $amounted);
		$this->assertSame('8,4', $this->schema->get('table.___example_probe.column.amounted.size'));
		$this->assertSame('NOT NULL', $this->schema->get('table.___example_probe.column.amounted.null'));

		$keyed = $this->probe('keyed');

		$this->assertSame(
			3,
			$keyed['key']['value'],
			'A primary key is the strongest claim a column can carry.'
		);
		$this->assertSame('table', $keyed['key']['origin']);
		$this->assertSame('11', $keyed['size']['value']);
		$this->assertSame('EMPTY', $this->table->get('table.example_probe.field.keyed.db.default'));
		$this->assertSame('0', $keyed['default']['value']);
		$this->assertSame('derived', $keyed['default']['origin']);

		$stored = $this->probe('stored');

		$this->assertSame('TEXT', $stored['datatype']['value']);
		$this->assertSame('table', $stored['datatype']['origin']);
		$this->assertArrayNotHasKey('size', $stored);
		$this->assertSame(0, $stored['key']['value']);
		$this->assertSame('derived', $stored['key']['origin']);
	}
}
