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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Writer;


use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Writer;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\WriterInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View as ViewRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\FieldXml;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Fieldtype;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\AdminCustomTabs;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\AdminFields;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\AdminFieldsConditions;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\AdminView;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\ComponentAdminViews;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Dispatcher;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Field;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Layout;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Template;
use VDM\Tests\Support\ExtrusionCatalogueFixture;
use VDM\Tests\Support\ExtrusionItemFixture;
use VDM\Tests\Support\TestCase;


/**
 * The extrusion writers: what actually lands in JCB's definition tables.
 *
 * One obligation dominates every case below. JCB's own data pipeline resolves
 * insert against update from the GUID and applies the storage encoding the target
 * table declares, so a writer must hand over raw values and nothing else. The
 * legacy extrusion helper base64-encodes by hand before writing, which stores the
 * encoding twice and makes the definition unusable; these tests exist so that
 * mistake cannot return.
 *
 * The rest holds the writers to the structure JCB expects: identity preferred
 * from the source, display flags taken from resolved roles rather than guessed by
 * position, and a dry run that stops exactly at the pipeline boundary.
 *
 * @since  6.1.6
 */
#[CoversClass(Writer::class)]
#[CoversClass(AdminCustomTabs::class)]
#[CoversClass(AdminFields::class)]
#[CoversClass(AdminFieldsConditions::class)]
#[CoversClass(AdminView::class)]
#[CoversClass(ComponentAdminViews::class)]
#[CoversClass(Dispatcher::class)]
#[CoversClass(Field::class)]
#[CoversClass(Layout::class)]
#[CoversClass(Template::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(Config::class)]
#[UsesClass(FieldXml::class)]
#[UsesClass(Fieldtype::class)]
#[UsesClass(Guid::class)]
#[UsesClass(Registry::class)]
#[UsesClass(Report::class)]
#[UsesClass(Resolved::class)]
#[UsesClass(Source::class)]
#[UsesClass(ViewRegistry::class)]
final class WriterTest extends TestCase
{
	/**
	 * The component option every derived identity is scoped to.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const OPTION = 'com_demo';

	/**
	 * The identity the assembler resolved for the item view.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const VIEW_GUID = 'aaaaaaaa-1111-4111-8111-aaaaaaaaaaaa';

	/**
	 * A per-field identity a JCB-built source carried, in upper case.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const SUPPLIED_GUID = 'BBBBBBBB-2222-4222-8222-BBBBBBBBBBBB';

	/**
	 * The PHP part of a source layout file, exactly as the reader split it out.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const PHP_PART = <<<'PHP'
$total = count($displayData);
$label = 'Items & "extras"';
PHP;

	/**
	 * The markup part of a source layout file, exactly as the reader split it out.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const HTML_PART = <<<'HTML'
<div class="example-layout">
	<h3><?php echo $label; ?> (<?php echo $total; ?>)</h3>
</div>
HTML;

	/**
	 * The seed INSERT a source schema carried for the item table.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const SEED = "INSERT INTO `#__demo_item` (`id`, `name`) VALUES (1, 'First; not a split');";

	/**
	 * The extrusion configuration under test.
	 *
	 * @var    Config
	 * @since  6.1.6
	 */
	private Config $config;

	/**
	 * The resolved definition registry the writers read.
	 *
	 * @var    Resolved
	 * @since  6.1.6
	 */
	private Resolved $resolved;

	/**
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	private Report $report;

	/**
	 * The source identity registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	private Source $source;

	/**
	 * The classified view layer registry.
	 *
	 * @var    ViewRegistry
	 * @since  6.1.6
	 */
	private ViewRegistry $view;

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
	 * The identity resolver.
	 *
	 * @var    Guid
	 * @since  6.1.6
	 */
	private Guid $guid;

	/**
	 * Start every case from one fresh, empty run.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->restate();
	}

	/**
	 * A layout is stored as raw PHP and raw markup, never encoded by the writer.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLayoutWriterStoresPhpAndMarkupRaw(): void
	{
		$this->view->set('layout.summary.name', 'summary');
		$this->view->set('layout.summary.php_view', self::PHP_PART);
		$this->view->set('layout.summary.layout', self::HTML_PART);
		$this->view->set('layout.summary.add_php_view', 1);

		$this->assertSame(1, $this->layout()->write());
		$this->assertSame(['layout'], $this->item->sequence());

		$definition = $this->item->definitions('layout')[0];

		$this->assertSame(self::PHP_PART, $definition->php_view);
		$this->assertSame(self::HTML_PART, $definition->layout);
		$this->assertNotSame(base64_encode(self::PHP_PART), $definition->php_view);
		$this->assertNotSame(base64_encode(self::HTML_PART), $definition->layout);
		$this->assertStringContainsString('<?php echo $label; ?>', $definition->layout);
		$this->assertSame(1, $definition->add_php_view);
		$this->assertSame('summary', $definition->name);
		$this->assertSame('summary (extruded)', $definition->description);
		$this->assertSame(1, $definition->published);
		$this->assertSame(
			$this->guid->derive([self::OPTION, 'layout', 'summary']),
			$definition->guid
		);
		$this->assertSame('guid', $this->item->records('layout')[0]['key']);
		$this->assertSame(1, $this->report->get('counts.layout'));
		$this->assertTrue($this->report->get('written.layout.' . $definition->guid));
	}

	/**
	 * A template is stored raw as well, and a nameless entry is refused.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTemplateWriterStoresPhpAndMarkupRaw(): void
	{
		$this->view->set('template.default.name', 'default');
		$this->view->set('template.default.php_view', '');
		$this->view->set('template.default.template', self::HTML_PART);
		$this->view->set('template.default.add_php_view', 0);
		$this->view->set('template.broken.name', '');
		$this->view->set('template.broken.template', self::HTML_PART);

		$this->assertSame(1, $this->template()->write());
		$this->assertSame(['template'], $this->item->sequence());

		$definition = $this->item->definitions('template')[0];

		$this->assertSame(self::HTML_PART, $definition->template);
		$this->assertNotSame(base64_encode(self::HTML_PART), $definition->template);
		$this->assertSame('', $definition->php_view);
		$this->assertSame(0, $definition->add_php_view);
		$this->assertSame('default', $definition->name);
		$this->assertSame(
			$this->guid->derive([self::OPTION, 'template', 'default']),
			$definition->guid
		);
		$this->assertSame(1, $this->report->get('counts.template'));
	}

	/**
	 * Admin view seed SQL is stored raw, because the sql column declares base64.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAdminViewStoresSeedSqlRaw(): void
	{
		$this->seedItemView();
		$this->resolved->set('view.item.seed', self::SEED);

		$this->assertSame(1, $this->adminView()->write());

		$definition = $this->item->definition('admin_view', self::VIEW_GUID);

		$this->assertNotNull($definition);
		$this->assertSame(self::SEED, $definition->sql);
		$this->assertNotSame(base64_encode(self::SEED), $definition->sql);
		$this->assertSame(1, $definition->add_sql);
		$this->assertSame(2, $definition->source);
		$this->assertSame('Item', $definition->system_name);
		$this->assertSame('item', $definition->name_single);
		$this->assertSame('items', $definition->name_list);
		$this->assertSame('Item (extruded)', $definition->short_description);
		$this->assertSame(1, $definition->type);
		$this->assertSame(1, $definition->published);
		$this->assertSame(
			['addtabs0' => ['name' => 'Item Details'], 'addtabs1' => ['name' => 'Metrics']],
			$this->decode($definition->addtabs)
		);
		$this->assertIsArray($this->decode($definition->addpermissions));
		$this->assertSame(self::VIEW_GUID, $this->resolved->get('view.item.written.view.guid'));
		$this->assertSame(1, $this->report->get('counts.admin_view'));
	}

	/**
	 * Without seed data the SQL switch is left alone rather than written empty.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAdminViewWithoutSeedLeavesTheSqlSwitchAlone(): void
	{
		$this->seedItemView();

		$this->assertSame(1, $this->adminView()->write());

		$definition = $this->item->definition('admin_view', self::VIEW_GUID);

		$this->assertNotNull($definition);
		$this->assertObjectNotHasProperty('sql', $definition);
		$this->assertObjectNotHasProperty('add_sql', $definition);
		$this->assertObjectNotHasProperty('source', $definition);
	}

	/**
	 * A dry run reports every identity it would write and writes nothing.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDryRunWritesNothingThroughTheItemService(): void
	{
		$this->config->set('dryRun', true);
		$this->seedItemView();
		$this->seedField('item', 'name', ['xml_type' => 'text', 'label' => 'Name']);
		$this->view->set('layout.summary.name', 'summary');
		$this->view->set('layout.summary.layout', self::HTML_PART);

		$fieldGuid = $this->guid->derive([self::OPTION, 'field', 'item', 'name']);
		$layoutGuid = $this->guid->derive([self::OPTION, 'layout', 'summary']);

		$this->assertSame(1, $this->field()->write());
		$this->assertSame(1, $this->adminView()->write());
		$this->assertSame(1, $this->layout()->write());
		$this->assertSame([], $this->item->records());
		$this->assertSame([], $this->item->lookups());
		$this->assertTrue($this->report->get('dryrun.field.' . $fieldGuid));
		$this->assertTrue($this->report->get('dryrun.admin_view.' . self::VIEW_GUID));
		$this->assertTrue($this->report->get('dryrun.layout.' . $layoutGuid));
		$this->assertNull($this->report->get('written'));
		$this->assertNull($this->report->get('failed'));
	}

	/**
	 * An existing identity short-circuits under skip and proceeds under update.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExistingIdentityIsSkippedOnlyUnderTheSkipPolicy(): void
	{
		$this->seedItemView();
		$this->item->identity('admin_view', self::VIEW_GUID, 42);
		$this->config->set('onExisting', 'skip');

		$this->assertSame(1, $this->adminView()->write());
		$this->assertSame([], $this->item->records());
		$this->assertTrue(
			$this->report->get('skipped.existing.admin_view.' . self::VIEW_GUID)
		);
		$this->assertSame(
			['admin_view:guid:' . self::VIEW_GUID . ':id'],
			$this->item->lookups()
		);

		$this->restate();
		$this->seedItemView();
		$this->item->identity('admin_view', self::VIEW_GUID, 42);
		$this->config->set('onExisting', 'update');

		$this->assertSame(1, $this->adminView()->write());
		$this->assertCount(1, $this->item->records('admin_view'));
		$this->assertTrue($this->report->get('written.admin_view.' . self::VIEW_GUID));
		$this->assertNull($this->report->get('skipped.existing'));
	}

	/**
	 * A definition carrying no identity is refused before the pipeline is touched.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDefinitionWithoutAGuidIsRefused(): void
	{
		$this->seedItemView();
		$this->resolved->set('view.item.guid', '');

		$this->assertSame(0, $this->adminView()->write());
		$this->assertSame([], $this->item->records());
		$this->assertSame([], $this->item->lookups());
		$this->assertTrue($this->report->get('failed.admin_view.missing_guid'));
		$this->assertNull($this->resolved->get('view.item.written.view.guid'));
		$this->assertSame(0, $this->report->get('counts.admin_view'));
	}

	/**
	 * A refused pipeline write is recorded against the identity that failed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRefusedWriteIsRecordedAsFailed(): void
	{
		$this->seedItemView();
		$this->item->refuse('admin_view', self::VIEW_GUID);

		$this->assertSame(0, $this->adminView()->write());
		$this->assertSame([], $this->item->records());
		$this->assertTrue($this->report->get('failed.admin_view.' . self::VIEW_GUID));
		$this->assertNull($this->report->get('written.admin_view.' . self::VIEW_GUID));
		$this->assertNull($this->resolved->get('view.item.written.view.guid'));
	}

	/**
	 * A field keeps the identity its source stated and derives one otherwise.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldWriterPrefersTheStatedIdentity(): void
	{
		$this->seedItemView();
		$this->seedField('item', 'name', [
			'xml_type' => 'text',
			'label' => 'Name',
			'guid' => self::SUPPLIED_GUID
		]);
		$this->seedField('item', 'alias', ['xml_type' => 'text', 'label' => 'Alias']);

		$derived = $this->guid->derive([self::OPTION, 'field', 'item', 'alias']);

		$this->assertSame(2, $this->field()->write());
		$this->assertSame(
			[strtolower(self::SUPPLIED_GUID), $derived],
			array_column($this->item->definitions('field'), 'guid')
		);
		$this->assertSame(
			strtolower(self::SUPPLIED_GUID),
			$this->resolved->get('view.item.written.name.guid')
		);
		$this->assertSame('Text', $this->resolved->get('view.item.written.name.fieldtype'));
		$this->assertSame($derived, $this->resolved->get('view.item.written.alias.guid'));
		$this->assertSame(
			ExtrusionCatalogueFixture::identity('Text'),
			$this->item->definition('field', $derived)->fieldtype
		);
		$this->assertSame('Alias', $this->item->definition('field', $derived)->name);
		$this->assertSame(2, $this->report->get('counts.field'));
		$this->assertSame(1, $this->catalogue->calls());
	}

	/**
	 * A declared storage encoding maps onto the JCB store code, never onto values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldWriterMapsTheDeclaredStoreOntoTheJcbCode(): void
	{
		$this->seedItemView();
		$stores = [
			'plain' => '',
			'encoded' => 'base64',
			'shouted' => ' BASE64 ',
			'structured' => 'json',
			'secret' => 'basic_encryption',
			'guarded' => 'whmcs_encryption',
			'expert' => 'expert_mode_encryption',
			'unknown' => 'something_else'
		];

		foreach ($stores as $column => $store)
		{
			$this->seedField('item', $column, ['xml_type' => 'text', 'store' => $store]);
		}

		$this->assertSame(8, $this->field()->write());
		$this->assertSame(
			[0, 1, 1, 2, 3, 4, 5, 0],
			array_column($this->item->definitions('field'), 'store')
		);
	}

	/**
	 * A length or default JCB does not offer collapses into its other column.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldWriterCollapsesUnsupportedSizeAndDefaultToOther(): void
	{
		$this->seedItemView();
		$this->seedField('item', 'name', [
			'xml_type' => 'text',
			'size' => '255',
			'default' => '1',
			'datatype' => 'VARCHAR',
			'null' => 'NOT NULL',
			'key' => 1
		]);
		$this->seedField('item', 'colour', [
			'xml_type' => 'color',
			'size' => '37',
			'default' => '#ffffff',
			'datatype' => 'CHAR'
		]);
		$this->seedField('item', 'note', ['xml_type' => 'text']);

		$this->assertSame(3, $this->field()->write());

		[$name, $colour, $note] = $this->item->definitions('field');

		$this->assertSame('255', $name->datalenght);
		$this->assertSame('', $name->datalenght_other);
		$this->assertSame('1', $name->datadefault);
		$this->assertSame('', $name->datadefault_other);
		$this->assertSame('VARCHAR', $name->datatype);
		$this->assertSame('NOT NULL', $name->null_switch);
		$this->assertSame(1, $name->indexes);
		$this->assertSame('Other', $colour->datalenght);
		$this->assertSame('37', $colour->datalenght_other);
		$this->assertSame('Other', $colour->datadefault);
		$this->assertSame('#ffffff', $colour->datadefault_other);
		$this->assertSame('', $note->datalenght);
		$this->assertSame('', $note->datalenght_other);
		$this->assertSame('', $note->datadefault);
		$this->assertSame('', $note->datadefault_other);
		$this->assertSame('TEXT', $note->datatype);
		$this->assertSame('NULL', $note->null_switch);
		$this->assertSame(0, $note->indexes);
	}

	/**
	 * The composed field element is stored as JSON, which is not an encoding.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldWriterJsonEncodesTheComposedFieldXml(): void
	{
		$this->seedItemView();
		$this->seedField('item', 'name', [
			'xml_type' => 'text',
			'label' => 'Name',
			'description' => 'The name shown to users.',
			'required' => 'true',
			'class' => 'form-control'
		]);

		$this->assertSame(1, $this->field()->write());

		$definition = $this->item->definitions('field')[0];
		$xml = json_decode($definition->xml);

		$this->assertJson($definition->xml);
		$this->assertIsString($xml);
		$this->assertNotSame($xml, $definition->xml);
		$this->assertStringStartsWith('<field', $xml);
		$this->assertStringContainsString('name="name"', $xml);
		$this->assertStringContainsString('label="Name"', $xml);
		$this->assertStringContainsString('required="true"', $xml);
		$this->assertStringNotContainsString('type="text"', $xml);
	}

	/**
	 * A field type nothing in the catalogue can answer is a recorded failure.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldWriterRecordsAnUnresolvableFieldType(): void
	{
		$this->catalogue = new ExtrusionCatalogueFixture([]);
		$this->seedItemView();
		$this->seedField('item', 'name', ['xml_type' => 'text', 'label' => 'Name']);

		$this->assertSame(0, $this->field()->write());
		$this->assertSame([], $this->item->records());
		$this->assertSame('text', $this->report->get('failed.field.unresolved_type.name'));
		$this->assertSame(0, $this->report->get('counts.field'));
		$this->assertNull($this->resolved->get('view.item.written.name.guid'));
	}

	/**
	 * List, sort, search and filter flags come from the roles, not from position.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAdminFieldsDerivesFlagsFromTheResolvedRoles(): void
	{
		$this->seedItemView();
		$this->seedField('item', 'name', ['xml_type' => 'text'], 1);
		$this->seedField('item', 'alias', ['xml_type' => 'text'], 1);
		$this->seedField('item', 'description', ['xml_type' => 'editor'], 1);
		$this->seedField('item', 'counter', ['xml_type' => 'list'], 2);
		$this->resolved->set('view.item.roles', [
			'name' => ['title' => true, 'alias' => false, 'list' => true, 'order' => 0],
			'alias' => ['title' => false, 'alias' => true, 'list' => false, 'order' => 1],
			'description' => ['title' => false, 'alias' => false, 'list' => true, 'order' => 2],
			'counter' => ['title' => false, 'alias' => false, 'list' => true, 'order' => 3]
		]);

		$this->seedWritten('item', 'view', self::VIEW_GUID);

		foreach (['name', 'alias', 'description', 'counter'] as $index => $column)
		{
			$this->seedWritten('item', $column, 'ffffffff-0000-4000-8000-00000000000' . $index);
		}

		$this->assertSame(1, $this->adminFields()->write());

		$definition = $this->item->definitions('admin_fields')[0];
		$subform = $this->decode($definition->addfields);

		$this->assertSame(self::VIEW_GUID, $definition->admin_view);
		$this->assertSame(
			$this->guid->derive([self::OPTION, 'admin_fields', 'item']),
			$definition->guid
		);
		$this->assertSame(
			['addfields0', 'addfields1', 'addfields2', 'addfields3'],
			array_keys($subform)
		);
		$this->assertSame('ffffffff-0000-4000-8000-000000000000', $subform['addfields0']['field']);
		$this->assertSame(
			[
				'list' => 1, 'order_list' => 1, 'sort' => 1, 'search' => 1,
				'filter' => 1, 'title' => 1, 'link' => 1
			],
			$this->flags($subform['addfields0']),
			'The first configured list field must keep every flag; losing them is the legacy defect.'
		);
		$this->assertSame(
			[
				'list' => 0, 'order_list' => 0, 'sort' => 0, 'search' => 0,
				'filter' => 0, 'title' => 0, 'link' => 0
			],
			$this->flags($subform['addfields1'])
		);
		$this->assertSame(1, $subform['addfields1']['alias']);
		$this->assertSame(4, $subform['addfields1']['alignment']);
		$this->assertSame(
			[
				'list' => 1, 'order_list' => 2, 'sort' => 1, 'search' => 1,
				'filter' => 1, 'title' => 0, 'link' => 0
			],
			$this->flags($subform['addfields2'])
		);
		$this->assertSame(3, $subform['addfields3']['order_list']);
		$this->assertSame(
			1,
			count(array_filter($subform, static fn (array $row): bool => $row['link'] === 1)),
			'Exactly one field may be the list link.'
		);
		$this->assertSame([1, 1, 1, 2], array_column($subform, 'tab'));
		$this->assertSame([0, 1, 2, 3], array_column($subform, 'order_edit'));
		$this->assertSame(1, $this->report->get('counts.admin_fields'));
	}

	/**
	 * Without a written view or written fields there is nothing to link.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAdminFieldsWritesNothingWithoutWrittenIdentities(): void
	{
		$this->seedItemView();
		$this->seedField('item', 'name', ['xml_type' => 'text'], 1);
		$this->resolved->set('view.item.roles', [
			'name' => ['title' => true, 'list' => true, 'order' => 0]
		]);

		$this->assertSame(0, $this->adminFields()->write());
		$this->assertSame([], $this->item->records());

		$this->resolved->set('view.item.written.view.guid', self::VIEW_GUID);

		$this->assertSame(
			0,
			$this->adminFields()->write(),
			'A view whose fields were never written cannot be linked.'
		);
		$this->assertSame([], $this->item->records());
		$this->assertSame(0, $this->report->get('counts.admin_fields'));
	}

	/**
	 * A view whose form declared no dependency writes no conditions at all.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConditionsWriteNothingWithoutAShowon(): void
	{
		$this->seedItemView();
		$this->seedWritten('item', 'view', self::VIEW_GUID);
		$this->seedWritten('item', 'amount', 'ffffffff-0000-4000-8000-0000000000a0');

		$this->assertSame(0, $this->conditions()->write());
		$this->assertSame([], $this->item->records());
		$this->assertSame(0, $this->report->get('counts.admin_fields_conditions'));

		$this->restate();
		$this->seedItemView();
		$this->seedWritten('item', 'view', self::VIEW_GUID);
		$this->config->set('conditions', false);
		$this->resolved->set('view.item.conditions', [
			['match' => 'amount', 'targets' => ['counter'], 'values' => ['0'], 'negate' => true]
		]);
		$this->seedWritten('item', 'amount', 'ffffffff-0000-4000-8000-0000000000a0');
		$this->seedWritten('item', 'counter', 'ffffffff-0000-4000-8000-0000000000c0');

		$this->assertSame(0, $this->conditions()->write());
		$this->assertSame([], $this->item->records());
		$this->assertNull(
			$this->report->get('counts.admin_fields_conditions'),
			'The conditions scope switch stops the writer before it counts anything.'
		);
	}

	/**
	 * A declared dependency becomes target and match identities, or is dropped.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConditionsWriteTargetAndMatchIdentities(): void
	{
		$this->seedItemView();
		$this->resolved->set('view.item.conditions', [
			['match' => 'amount', 'targets' => ['counter'], 'values' => ['0'], 'negate' => true],
			[
				'match' => 'colour',
				'targets' => ['counter', 'ghost'],
				'values' => ['#ffffff', '#000000'],
				'negate' => false
			],
			['match' => 'phantom', 'targets' => ['counter'], 'values' => ['1'], 'negate' => false],
			['match' => 'amount', 'targets' => ['ghost'], 'values' => ['2'], 'negate' => false]
		]);
		$this->seedWritten('item', 'view', self::VIEW_GUID);
		$this->seedWritten('item', 'amount', 'ffffffff-0000-4000-8000-0000000000a0');
		$this->seedWritten('item', 'colour', 'ffffffff-0000-4000-8000-0000000000b0');
		$this->seedWritten('item', 'counter', 'ffffffff-0000-4000-8000-0000000000c0');

		$this->assertSame(1, $this->conditions()->write());

		$definition = $this->item->definitions('admin_fields_conditions')[0];
		$subform = $this->decode($definition->addconditions);

		$this->assertSame(self::VIEW_GUID, $definition->admin_view);
		$this->assertSame(
			$this->guid->derive([self::OPTION, 'admin_fields_conditions', 'item']),
			$definition->guid
		);
		$this->assertSame(
			['addconditions0', 'addconditions1'],
			array_keys($subform),
			'A condition whose match or targets were never written must be dropped.'
		);
		$this->assertSame(
			[
				'target_field' => ['ffffffff-0000-4000-8000-0000000000c0'],
				'match_field' => 'ffffffff-0000-4000-8000-0000000000a0',
				'target_behavior' => 2,
				'target_relation' => 1,
				'options' => '0'
			],
			$subform['addconditions0']
		);
		$this->assertSame(
			['ffffffff-0000-4000-8000-0000000000c0'],
			$subform['addconditions1']['target_field']
		);
		$this->assertSame(1, $subform['addconditions1']['target_behavior']);
		$this->assertSame('#ffffff,#000000', $subform['addconditions1']['options']);
		$this->assertSame(1, $this->report->get('counts.admin_fields_conditions'));
	}

	/**
	 * Custom tabs are written only where a view really has more than one tab.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomTabsAreWrittenOnlyForTwoOrMoreTabs(): void
	{
		$this->seedItemView();
		$this->seedWritten('item', 'view', self::VIEW_GUID);
		$this->resolved->set('view.item.tabs', ['Details']);

		$this->assertSame(0, $this->customTabs()->write());
		$this->assertSame([], $this->item->records());
		$this->assertSame(0, $this->report->get('counts.admin_custom_tabs'));

		$this->restate();
		$this->seedItemView();
		$this->seedWritten('item', 'view', self::VIEW_GUID);

		$this->assertSame(1, $this->customTabs()->write());

		$definition = $this->item->definitions('admin_custom_tabs')[0];

		$this->assertSame(self::VIEW_GUID, $definition->admin_view);
		$this->assertSame(
			$this->guid->derive([self::OPTION, 'admin_custom_tabs', 'item']),
			$definition->guid
		);
		$this->assertSame(
			[
				'tabs0' => ['name' => 'Item Details', 'html' => '', 'php' => ''],
				'tabs1' => ['name' => 'Metrics', 'html' => '', 'php' => '']
			],
			$this->decode($definition->tabs)
		);
		$this->assertSame(1, $this->report->get('counts.admin_custom_tabs'));

		$this->restate();
		$this->seedItemView();
		$this->seedWritten('item', 'view', self::VIEW_GUID);
		$this->config->set('tabs', false);

		$this->assertSame(0, $this->customTabs()->write());
		$this->assertSame([], $this->item->records());
		$this->assertNull($this->report->get('counts.admin_custom_tabs'));
	}

	/**
	 * Views cannot be linked to a component that was never named.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testComponentAdminViewsRefusesWithoutAComponentId(): void
	{
		$this->seedItemView();
		$this->seedWritten('item', 'view', self::VIEW_GUID);

		$this->assertSame(0, $this->componentViews()->write());
		$this->assertSame([], $this->item->records());
		$this->assertTrue($this->report->get('failed.component_admin_views.no_component'));
		$this->assertNull($this->report->get('counts.component_admin_views'));
	}

	/**
	 * Each written view becomes one ordered entry on the component's subform.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testComponentAdminViewsEmitsOneOrderedEntryPerWrittenView(): void
	{
		$this->config->set('component', 9);
		$this->resolved->set('views', ['item', 'category', 'ghost']);
		$this->resolved->set('view.item.written.view.guid', self::VIEW_GUID);
		$this->resolved->set(
			'view.category.written.view.guid',
			'cccccccc-3333-4333-8333-cccccccccccc'
		);

		$this->assertSame(2, $this->componentViews()->write());
		$this->assertCount(1, $this->item->records('component_admin_views'));

		$definition = $this->item->definitions('component_admin_views')[0];
		$subform = $this->decode($definition->addadmin_views);

		$this->assertSame(9, $definition->joomla_component);
		$this->assertSame(
			$this->guid->derive([self::OPTION, 'component_admin_views', '9']),
			$definition->guid
		);
		$this->assertSame(['addadmin_views0', 'addadmin_views1'], array_keys($subform));
		$this->assertSame(
			[self::VIEW_GUID, 'cccccccc-3333-4333-8333-cccccccccccc'],
			array_column($subform, 'adminview')
		);
		$this->assertSame([1, 2], array_column($subform, 'order'));
		$this->assertSame(1, $subform['addadmin_views0']['mainmenu']);
		$this->assertSame(0, $subform['addadmin_views0']['edit_create_site_view']);
		$this->assertSame(2, $this->report->get('counts.component_admin_views'));

		$this->restate();
		$this->config->set('component', 9);
		$this->resolved->set('views', ['ghost']);

		$this->assertSame(
			0,
			$this->componentViews()->write(),
			'A run that wrote no view has nothing to link and no failure to report.'
		);
		$this->assertSame([], $this->item->records());
		$this->assertNull($this->report->get('failed.component_admin_views.no_component'));
	}

	/**
	 * Fields run before views, views before their links, and the component last.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDispatcherRunsWritersInDependencyOrder(): void
	{
		$calls = new ArrayObject();
		$names = [
			'field', 'admin_view', 'admin_fields', 'admin_fields_conditions',
			'admin_custom_tabs', 'component_admin_views', 'layout', 'template'
		];
		$writers = [];

		foreach ($names as $index => $name)
		{
			$writers[$name] = $this->recorder($name, $index + 1, $calls);
		}

		$dispatcher = new Dispatcher(
			$this->config,
			$this->report,
			$writers['field'],
			$writers['admin_view'],
			$writers['admin_fields'],
			$writers['admin_fields_conditions'],
			$writers['admin_custom_tabs'],
			$writers['component_admin_views'],
			$writers['layout'],
			$writers['template']
		);
		$expected = [
			'field', 'admin_view', 'admin_fields', 'admin_fields_conditions',
			'admin_custom_tabs', 'layout', 'template', 'component_admin_views'
		];

		$this->assertSame($expected, array_keys($dispatcher->order()));
		$this->assertSame('component_admin_views', array_key_last($dispatcher->order()));
		$this->assertSame(36, $dispatcher->dispatch());
		$this->assertSame($expected, $calls->getArrayCopy());
		$this->assertSame(1, $this->report->get('written_counts.field'));
		$this->assertSame(2, $this->report->get('written_counts.admin_view'));
		$this->assertSame(6, $this->report->get('written_counts.component_admin_views'));
		$this->assertSame(36, $this->report->get('counts.written'));

		$this->config->set('admin', false);

		$this->assertSame(
			['layout', 'template'],
			array_keys($dispatcher->order()),
			'With the admin scope off only the shared view layers are written.'
		);
	}

	/**
	 * The scope switches are honoured by the real writers the dispatcher drives.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDispatcherHonoursTheTabAndConditionScopeSwitches(): void
	{
		$this->config->set('component', 4);
		$this->config->set('tabs', false);
		$this->config->set('conditions', false);
		$this->seedItemView();
		$this->seedField('item', 'name', ['xml_type' => 'text', 'label' => 'Name'], 1);
		$this->seedField('item', 'counter', ['xml_type' => 'list'], 2);
		$this->resolved->set('view.item.roles', [
			'name' => ['title' => true, 'list' => true, 'order' => 0],
			'counter' => ['title' => false, 'list' => true, 'order' => 1]
		]);
		$this->resolved->set('view.item.conditions', [
			['match' => 'name', 'targets' => ['counter'], 'values' => ['1'], 'negate' => false]
		]);
		$this->view->set('layout.summary.name', 'summary');
		$this->view->set('layout.summary.layout', self::HTML_PART);

		$this->assertSame(6, $this->dispatcher()->dispatch());
		$this->assertSame(
			['field', 'field', 'admin_view', 'admin_fields', 'layout', 'component_admin_views'],
			$this->item->sequence(),
			'Fields must be written before the view that references them.'
		);
		$this->assertSame(0, $this->report->get('written_counts.admin_custom_tabs'));
		$this->assertSame(0, $this->report->get('written_counts.admin_fields_conditions'));
		$this->assertSame(2, $this->report->get('written_counts.field'));
		$this->assertSame(1, $this->report->get('written_counts.component_admin_views'));
		$this->assertSame([], $this->item->records('admin_custom_tabs'));
		$this->assertSame([], $this->item->records('admin_fields_conditions'));
	}

	/**
	 * Rebuild the whole write boundary, discarding everything a case has done.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function restate(): void
	{
		$this->config = new Config();
		$this->resolved = new Resolved();
		$this->report = new Report();
		$this->source = new Source();
		$this->view = new ViewRegistry();
		$this->item = new ExtrusionItemFixture();
		$this->catalogue = new ExtrusionCatalogueFixture();
		$this->guid = new Guid();

		$this->source->set('code_name', self::OPTION);
	}

	/**
	 * Seed one resolved item view with two tabs and a resolved identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function seedItemView(): void
	{
		$this->resolved->set('views', ['item']);
		$this->resolved->set('view.item.name_single', 'item');
		$this->resolved->set('view.item.name_list', 'items');
		$this->resolved->set('view.item.system_name', 'Item');
		$this->resolved->set('view.item.guid', self::VIEW_GUID);
		$this->resolved->set('view.item.tabs', ['Item Details', 'Metrics']);
	}

	/**
	 * Seed one resolved field, in the value and origin shape writers read.
	 *
	 * @param   string                $view    The view name.
	 * @param   string                $column  The source column name.
	 * @param   array<string, mixed>  $values  The resolved property values.
	 * @param   int|null              $tab     The one-based tab index, when known.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function seedField(string $view, string $column, array $values, ?int $tab = null): void
	{
		$properties = ['name' => ['value' => $column, 'origin' => 'derived']];

		foreach ($values as $property => $value)
		{
			$properties[$property] = ['value' => $value, 'origin' => 'xml'];
		}

		$this->resolved->set('view.' . $view . '.field.' . $column, $properties);

		if ($tab !== null)
		{
			$this->resolved->set('view.' . $view . '.field.' . $column . '.tab_index', $tab);
		}
	}

	/**
	 * Seed the identity one column was already written under.
	 *
	 * @param   string  $view    The view name.
	 * @param   string  $column  The source column name.
	 * @param   string  $guid    The written identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function seedWritten(string $view, string $column, string $guid): void
	{
		$this->resolved->set('view.' . $view . '.written.' . $column . '.guid', $guid);
	}

	/**
	 * The display flags of one admin fields subform row.
	 *
	 * @param   array<string, mixed>  $row  The subform row.
	 *
	 * @return  array<string, int>  The flags that decide list behaviour.
	 * @since   6.1.6
	 */
	private function flags(array $row): array
	{
		$flags = [];

		foreach (['list', 'order_list', 'sort', 'search', 'filter', 'title', 'link'] as $flag)
		{
			$flags[$flag] = (int) ($row[$flag] ?? -1);
		}

		return $flags;
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
	 * A writer that records that it ran, for the dependency order case.
	 *
	 * @param   string       $name   The writer name.
	 * @param   int          $count  The number of definitions to report.
	 * @param   ArrayObject  $calls  The shared call log.
	 *
	 * @return  WriterInterface  The recording writer.
	 * @since   6.1.6
	 */
	private function recorder(string $name, int $count, ArrayObject $calls): WriterInterface
	{
		return new class($name, $count, $calls) implements WriterInterface
		{
			/**
			 * The writer name recorded when this writer runs.
			 *
			 * @var    string
			 * @since  6.1.6
			 */
			private string $name;

			/**
			 * The number of definitions this writer reports.
			 *
			 * @var    int
			 * @since  6.1.6
			 */
			private int $count;

			/**
			 * The shared call log.
			 *
			 * @var    ArrayObject
			 * @since  6.1.6
			 */
			private ArrayObject $calls;

			/**
			 * Constructor.
			 *
			 * @param   string       $name   The writer name.
			 * @param   int          $count  The number of definitions to report.
			 * @param   ArrayObject  $calls  The shared call log.
			 *
			 * @since   6.1.6
			 */
			public function __construct(string $name, int $count, ArrayObject $calls)
			{
				$this->name = $name;
				$this->count = $count;
				$this->calls = $calls;
			}

			/**
			 * Record this writer's turn and report its count.
			 *
			 * @return  int  The number of definitions written.
			 * @since   6.1.6
			 */
			public function write(): int
			{
				$this->calls->append($this->name);

				return $this->count;
			}
		};
	}

	/**
	 * The field writer over the current boundary.
	 *
	 * @return  Field  The writer.
	 * @since   6.1.6
	 */
	private function field(): Field
	{
		$fieldtype = new Fieldtype($this->catalogue, $this->source, $this->report);

		return new Field(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$fieldtype,
			new FieldXml($fieldtype, $this->report),
			$this->guid,
			$this->source
		);
	}

	/**
	 * The admin view writer over the current boundary.
	 *
	 * @return  AdminView  The writer.
	 * @since   6.1.6
	 */
	private function adminView(): AdminView
	{
		return new AdminView(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->guid,
			$this->source
		);
	}

	/**
	 * The admin fields writer over the current boundary.
	 *
	 * @return  AdminFields  The writer.
	 * @since   6.1.6
	 */
	private function adminFields(): AdminFields
	{
		return new AdminFields(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->guid,
			$this->source
		);
	}

	/**
	 * The field conditions writer over the current boundary.
	 *
	 * @return  AdminFieldsConditions  The writer.
	 * @since   6.1.6
	 */
	private function conditions(): AdminFieldsConditions
	{
		return new AdminFieldsConditions(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->guid,
			$this->source
		);
	}

	/**
	 * The custom tabs writer over the current boundary.
	 *
	 * @return  AdminCustomTabs  The writer.
	 * @since   6.1.6
	 */
	private function customTabs(): AdminCustomTabs
	{
		return new AdminCustomTabs(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->guid,
			$this->source
		);
	}

	/**
	 * The component link writer over the current boundary.
	 *
	 * @return  ComponentAdminViews  The writer.
	 * @since   6.1.6
	 */
	private function componentViews(): ComponentAdminViews
	{
		return new ComponentAdminViews(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->guid,
			$this->source
		);
	}

	/**
	 * The layout writer over the current boundary.
	 *
	 * @return  Layout  The writer.
	 * @since   6.1.6
	 */
	private function layout(): Layout
	{
		return new Layout(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->view,
			$this->guid,
			$this->source
		);
	}

	/**
	 * The template writer over the current boundary.
	 *
	 * @return  Template  The writer.
	 * @since   6.1.6
	 */
	private function template(): Template
	{
		return new Template(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->view,
			$this->guid,
			$this->source
		);
	}

	/**
	 * The writer dispatcher over the real writer graph.
	 *
	 * @return  Dispatcher  The dispatcher.
	 * @since   6.1.6
	 */
	private function dispatcher(): Dispatcher
	{
		return new Dispatcher(
			$this->config,
			$this->report,
			$this->field(),
			$this->adminView(),
			$this->adminFields(),
			$this->conditions(),
			$this->customTabs(),
			$this->componentViews(),
			$this->layout(),
			$this->template()
		);
	}
}
