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
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Language as LanguageRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View as ViewRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\FieldXml;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Fieldtype;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Decision;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Language;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Text;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\AdminCustomTabs;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\AdminFields;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\AdminFieldsConditions;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\AdminView;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Component;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\ComponentAdminViews;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\ComponentCustomAdminViews;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\ComponentSiteViews;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\CustomAdminView;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\DynamicGet;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\SiteView;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Dispatcher;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Field;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Layout;
use VDM\Joomla\Componentbuilder\Extrusion\Writer\Template;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Tests\Support\ExtrusionCatalogueFixture;
use VDM\Tests\Support\ExtrusionDatabaseFixture;
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
#[CoversClass(Component::class)]
#[CoversClass(ComponentAdminViews::class)]
#[CoversClass(ComponentCustomAdminViews::class)]
#[CoversClass(ComponentSiteViews::class)]
#[CoversClass(CustomAdminView::class)]
#[CoversClass(DynamicGet::class)]
#[CoversClass(SiteView::class)]
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
	 * The target component's guid, as the Table class keys the link columns.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const COMPONENT_GUID = 'eeeeeeee-9999-4999-8999-999999999999';

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
		$permissions = $this->decode($definition->addpermissions);

		$this->assertSame(
			['action' => 'view.edit', 'implementation' => 3],
			$permissions['addpermissions0'] ?? null,
			'Permissions are rows of one action and one implementation each -- the '
			. 'only shape the admin_view form renders; the legacy parallel arrays '
			. 'display as nothing at all.'
		);
		$this->assertSame(
			[
				'view.edit', 'view.edit.own', 'view.edit.state', 'view.edit.access',
				'view.edit.created_by', 'view.edit.created', 'view.create',
				'view.delete', 'view.access'
			],
			array_column($permissions, 'action'),
			'The full action set of JCB\'s own demo views, implementation 3 (both) throughout.'
		);
		$this->assertSame([3], array_values(array_unique(array_column($permissions, 'implementation'))));
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
	 * A default longer than the Table class's column stays out, and is said.
	 *
	 * The datadefault_other column is CHAR(64) by the Table class's own
	 * declaration; a longer harvested default is a form default that lives
	 * on in the field's xml, and a strict live database refuses it here.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testFieldWriterRefusesADefaultLongerThanItsColumn(): void
	{
		$oversized = str_repeat('a very long harvested form default ', 4);

		$this->seedItemView();
		$this->seedField('item', 'notes', [
			'xml_type' => 'textarea',
			'default' => $oversized,
			'datatype' => 'TEXT'
		]);

		$this->assertSame(1, $this->field()->write());

		$definition = $this->item->definitions('field')[0];

		$this->assertSame('', $definition->datadefault);
		$this->assertSame('', $definition->datadefault_other);
		$this->assertSame(
			strlen($oversized),
			$this->report->get('skipped.default.too_long.notes'),
			'What could not be carried must be said, with its size.'
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
	 * The composed field element travels raw; the Table class encodes it.
	 *
	 * The field's xml column declares json storage, so the model applies
	 * the encoding at write time -- a writer that encoded it first would
	 * have it encoded twice, and every consumer that decodes once would
	 * read a quoted string where the element should stand.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testFieldWriterHandsTheComposedFieldXmlRaw(): void
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

		$this->assertIsString($definition->xml);
		$this->assertStringStartsWith('<field', $definition->xml);
		$this->assertNull(
			json_decode($definition->xml),
			'The element is the raw stored value, never pre-encoded JSON.'
		);
		$this->assertStringContainsString('name="name"', $definition->xml);
		$this->assertStringContainsString('label="Name"', $definition->xml);
		$this->assertStringContainsString('description="The name shown to users."', $definition->xml);
		$this->assertStringContainsString('required="true"', $definition->xml);
		$this->assertStringContainsString('class="form-control"', $definition->xml);
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
		$this->assertObjectNotHasProperty(
			'guid',
			$definition,
			'A linked-map table holds no guid; its key is the view it links.'
		);
		$this->assertSame('admin_view', $this->item->records('admin_fields')[0]['key']);
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
		$this->assertArrayNotHasKey(
			'sort',
			$subform['addfields1'],
			'A checkbox the form leaves unticked is absent from the row, as the form itself stores it.'
		);
		$this->assertArrayNotHasKey(
			'permission',
			$subform['addfields0'],
			'A field with no permission rule omits the key; the form reads that as none.'
		);
		$this->assertSame('1', $subform['addfields1']['alias']);
		$this->assertSame(4, $subform['addfields1']['alignment']);
		$this->assertSame(
			[
				'list' => 1, 'order_list' => 2, 'sort' => 1, 'search' => 1,
				'filter' => 1, 'title' => 0, 'link' => 0
			],
			$this->flags($subform['addfields2'])
		);
		$this->assertSame('3', $subform['addfields3']['order_list']);
		$this->assertSame(
			1,
			count(array_filter(
				$subform,
				static fn (array $row): bool => ($row['link'] ?? '') === '1'
			)),
			'Exactly one field may be the list link.'
		);
		$this->assertSame(['1', '1', '1', '2'], array_column($subform, 'tab'));
		$this->assertSame(
			['1', '2', '3', '1'],
			array_column($subform, 'order_edit'),
			'The edit order counts from one within each tab, as a person lays a view out.'
		);
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
	 * Nothing may be attached to an administrator view that was never written.
	 *
	 * Every writer below hands JCB an admin_view column. If the view write failed
	 * or was skipped, that column can only be empty, and an empty foreign key is
	 * worse than no row: it is a definition JCB will load and cannot resolve. All
	 * three must therefore stand down together, and resume together once the view
	 * really has an identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDependentWritersRefuseAViewThatWasNeverWritten(): void
	{
		$this->seedItemView();
		$this->seedField('item', 'name', ['xml_type' => 'text'], 1);
		$this->seedField('item', 'counter', ['xml_type' => 'list'], 2);
		$this->resolved->set('view.item.roles', [
			'name' => ['title' => true, 'list' => true, 'order' => 0],
			'counter' => ['title' => false, 'list' => true, 'order' => 1]
		]);
		$this->resolved->set('view.item.conditions', [
			['match' => 'name', 'targets' => ['counter'], 'values' => ['1'], 'negate' => false]
		]);
		$this->seedWritten('item', 'name', 'ffffffff-0000-4000-8000-0000000000d0');
		$this->seedWritten('item', 'counter', 'ffffffff-0000-4000-8000-0000000000e0');

		$this->assertSame(0, $this->adminFields()->write());
		$this->assertSame(0, $this->conditions()->write());
		$this->assertSame(0, $this->customTabs()->write());
		$this->assertSame(
			[],
			$this->item->records(),
			'A definition whose admin_view column would be empty must never be written.'
		);

		$this->seedWritten('item', 'view', self::VIEW_GUID);

		$this->assertSame(1, $this->adminFields()->write());
		$this->assertSame(1, $this->conditions()->write());
		$this->assertSame(1, $this->customTabs()->write());
		$this->assertSame(
			['admin_fields', 'admin_fields_conditions', 'admin_custom_tabs'],
			$this->item->sequence()
		);
		$this->assertSame(
			[self::VIEW_GUID, self::VIEW_GUID, self::VIEW_GUID],
			array_column(array_column($this->item->records(), 'item'), 'admin_view')
		);
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
		$this->assertObjectNotHasProperty(
			'guid',
			$definition,
			'A linked-map table holds no guid; its key is the view it links.'
		);
		$this->assertSame('admin_view', $this->item->records('admin_fields_conditions')[0]['key']);
		$this->assertSame(
			['addconditions0', 'addconditions1'],
			array_keys($subform),
			'A condition whose match or targets were never written must be dropped.'
		);
		$this->assertSame(
			[
				'target_field' => ['ffffffff-0000-4000-8000-0000000000c0'],
				'match_field' => 'ffffffff-0000-4000-8000-0000000000a0',
				'target_behavior' => 1,
				'target_relation' => 1,
				'match_behavior' => 2,
				'match_options' => '0'
			],
			$subform['addconditions0'],
			'A showon rule says when to show its target, so the negation belongs on how '
			. 'the match is evaluated and never on the target behaviour.'
		);
		$this->assertSame(
			['ffffffff-0000-4000-8000-0000000000c0'],
			$subform['addconditions1']['target_field']
		);
		$this->assertSame(1, $subform['addconditions1']['target_behavior']);
		$this->assertSame(1, $subform['addconditions1']['match_behavior']);
		$this->assertSame(
			"#ffffff\n#000000",
			$subform['addconditions1']['match_options'],
			'The compiler splits the options on a newline, so that is how they are stored.'
		);
		$this->assertSame(1, $this->report->get('counts.admin_fields_conditions'));

		$this->assertSame(
			'its match field was not extruded as a field',
			$this->report->get('dropped.condition.item.phantom'),
			'A dropped dependency must be named in the report, not lost quietly.'
		);
		$this->assertSame(
			'the target field was not extruded as a field',
			$this->report->get('dropped.condition.item.ghost'),
			'An unresolvable target must be named in the report too.'
		);
	}

	/**
	 * A dependency on a column Joomla manages itself is reported, not lost.
	 *
	 * A real component routinely writes showon="access:1". JCB generates access
	 * from its own switch rather than as an extruded field, so the dependency has
	 * nothing to point at and has to be dropped. Dropping it silently would lose
	 * part of the source component with nothing to show for it, which is exactly
	 * what the getbible component exposed across three of its twelve views.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testADependencyOnABoilerplateColumnIsReported(): void
	{
		$this->seedItemView();
		$this->resolved->set('view.item.conditions', [
			['match' => 'access', 'targets' => ['counter'], 'values' => ['1'], 'negate' => false]
		]);
		$this->seedWritten('item', 'view', self::VIEW_GUID);
		$this->seedWritten('item', 'counter', 'ffffffff-0000-4000-8000-0000000000c0');

		$this->assertSame(
			0,
			$this->conditions()->write(),
			'Nothing can be written when the only dependency has no match field.'
		);
		$this->assertSame([], $this->item->records());
		$this->assertSame(
			'its match field was not extruded as a field',
			$this->report->get('dropped.condition.item.access'),
			'The lost dependency must be visible in the report.'
		);
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
		$this->assertObjectNotHasProperty(
			'guid',
			$definition,
			'A linked-map table holds no guid; its key is the view it links.'
		);
		$this->assertSame('admin_view', $this->item->records('admin_custom_tabs')[0]['key']);
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

		$this->assertSame(
			self::COMPONENT_GUID,
			$definition->joomla_component,
			'The link column speaks the component guid the Table class defines, never its id.'
		);
		$this->assertObjectNotHasProperty(
			'guid',
			$definition,
			'A linked-map table holds no guid; its key is the component it links.'
		);
		$this->assertSame('joomla_component', $this->item->records('component_admin_views')[0]['key']);
		$this->assertSame(['addadmin_views0', 'addadmin_views1'], array_keys($subform));
		$this->assertSame(
			[self::VIEW_GUID, 'cccccccc-3333-4333-8333-cccccccccccc'],
			array_column($subform, 'adminview')
		);
		$this->assertSame(['1', '2'], array_column($subform, 'order'));
		$this->assertSame('1', $subform['addadmin_views0']['mainmenu']);
		$this->assertSame('1', $subform['addadmin_views0']['dashboard_add']);
		$this->assertSame('1', $subform['addadmin_views0']['joomla_fields']);
		$this->assertSame('0', $subform['addadmin_views0']['add_api']);
		$this->assertSame(
			'2',
			$subform['addadmin_views0']['filter'],
			'The side-filter layout is the form\'s own default for a new view link.'
		);
		$this->assertSame('', $subform['addadmin_views0']['edit_create_site_view']);
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
			'admin_custom_tabs', 'component_admin_views', 'joomla_component',
			'layout', 'template', 'dynamic_get', 'site_view',
			'component_site_views', 'custom_admin_view',
			'component_custom_admin_views'
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
			$writers['joomla_component'],
			$writers['layout'],
			$writers['template'],
			$writers['site_view'],
			$writers['component_site_views'],
			$writers['dynamic_get'],
			$writers['custom_admin_view'],
			$writers['component_custom_admin_views']
		);
		$expected = [
			'joomla_component', 'field', 'admin_view', 'admin_fields',
			'admin_fields_conditions', 'admin_custom_tabs', 'layout', 'template',
			'dynamic_get', 'site_view', 'component_site_views',
			'custom_admin_view', 'component_custom_admin_views',
			'component_admin_views'
		];

		$this->assertSame($expected, array_keys($dispatcher->order()));
		$this->assertSame(
			'joomla_component',
			array_key_first($dispatcher->order()),
			'The component record is filled in first, because everything else belongs to it.'
		);
		$this->assertSame('component_admin_views', array_key_last($dispatcher->order()));
		$this->assertSame(105, $dispatcher->dispatch());
		$this->assertSame($expected, $calls->getArrayCopy());
		$this->assertSame(1, $this->report->get('written_counts.field'));
		$this->assertSame(2, $this->report->get('written_counts.admin_view'));
		$this->assertSame(6, $this->report->get('written_counts.component_admin_views'));
		$this->assertSame(7, $this->report->get('written_counts.joomla_component'));
		$this->assertSame(105, $this->report->get('counts.written'));

		$this->config->set('admin', false);

		$this->assertSame(
			['joomla_component', 'layout', 'template', 'dynamic_get', 'site_view', 'component_site_views'],
			array_keys($dispatcher->order()),
			'With the admin scope off the component record and the shared view layers '
			. 'are still written.'
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

		$this->assertSame(7, $this->dispatcher()->dispatch());
		$this->assertSame(
			[
				'joomla_component', 'field', 'field', 'admin_view', 'admin_fields',
				'layout', 'component_admin_views'
			],
			$this->item->sequence(),
			'Fields must be written before the view that references them.'
		);
		$this->assertSame(
			['name_code'],
			$this->report->get('component.details'),
			'With no manifest read, the code name is all there is to fill in.'
		);
		$this->assertSame(0, $this->report->get('written_counts.admin_custom_tabs'));
		$this->assertSame(0, $this->report->get('written_counts.admin_fields_conditions'));
		$this->assertSame(2, $this->report->get('written_counts.field'));
		$this->assertSame(1, $this->report->get('written_counts.component_admin_views'));
		$this->assertSame([], $this->item->records('admin_custom_tabs'));
		$this->assertSame([], $this->item->records('admin_fields_conditions'));
	}

	/**
	 * The manifest fills in the component record, and only what it stated.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheManifestFillsInTheComponentRecord(): void
	{
		$this->config->set('component', 12);
		$this->source->set('name', 'COM_DEMO');
		$this->source->set('version', '2.4.1');
		$this->source->set('manifest_data', [
			'description' => '<h1>Demo</h1><p>A demo component for testing. '
				. 'It does very little indeed.</p>',
			'author' => 'A Person',
			'email' => 'person@example.com',
			'website' => 'https://example.com',
			'copyright' => 'Copyright (C) 2026',
			'license' => 'GNU/GPL Version 2',
			'namespace' => 'Vendor\\Component\\Demo',
			'target' => '5.0'
		]);

		$this->assertSame(1, $this->details()->write());

		$record = $this->item->definitions('joomla_component')[0];

		$this->assertSame(12, $record->id, 'The component is updated in place, keyed by id.');
		$this->assertSame('A Person', $record->author);
		$this->assertSame('person@example.com', $record->email);
		$this->assertSame('https://example.com', $record->website);
		$this->assertSame('Copyright (C) 2026', $record->copyright);
		$this->assertSame('GNU/GPL Version 2', $record->license);
		$this->assertSame('2.4.1', $record->component_version);
		$this->assertSame(5, $record->preferred_joomla_version);
		$this->assertSame(
			'Vendor',
			$record->namespace_prefix,
			'JCB owns the rest of a Joomla namespace and asks only for the vendor.'
		);
		$this->assertSame(1, $record->add_namespace_prefix);
		$this->assertSame(
			"Demo\nA demo component for testing. It does very little indeed.",
			$record->description,
			'The description is the readable text of what the manifest gave, because '
			. 'the column holds what a person would have typed there, never markup.'
		);
		$this->assertSame(
			'Demo A demo component for testing.',
			$record->short_description,
			'The short description is the first sentence of the readable text.'
		);
		$this->assertSame(
			self::OPTION,
			'com_' . $record->name_code,
			'The code name is stored without its com_ prefix.'
		);
		$this->assertFalse(
			property_exists($record, 'sql'),
			'A column the manifest said nothing about must not be blanked.'
		);
	}

	/**
	 * Without a component id, and with the scope off, nothing is written.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheComponentRecordIsLeftAloneWhenItCannotOrShouldNotBeTouched(): void
	{
		$this->source->clear('code_name');

		$this->assertSame(
			0,
			$this->details()->write(),
			'With no component id there is no record to fill in.'
		);

		$this->config->set('component', 12);

		$this->assertSame(
			0,
			$this->details()->write(),
			'A run that recovered nothing about the component writes nothing.'
		);
		$this->assertSame(
			'the manifest stated nothing to fill in',
			$this->report->get('component.details')
		);

		$this->source->set('name', 'Demo');
		$this->config->set('component_details', false);

		$this->assertSame(
			0,
			$this->details()->write(),
			'The scope switch has to be able to turn this off entirely.'
		);
		$this->assertSame([], $this->item->records('joomla_component'));

		$this->config->set('component_details', true);
		$this->config->set('dryRun', true);

		$this->assertSame(1, $this->details()->write());
		$this->assertSame(
			[],
			$this->item->records('joomla_component'),
			'A dry run reports what it would do and writes nothing.'
		);
		$this->assertTrue($this->report->get('dryrun.joomla_component.12'));
	}

	/**
	 * A target-less harvest creates the component its findings belong to.
	 *
	 * A component source names a component, so importing it without a target
	 * must not leave the harvest unrelated: the component record is created
	 * from the source's own identity and manifest, and its guid is recorded
	 * for every linked-map writer to link through.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATargetlessHarvestCreatesTheComponentItBelongsTo(): void
	{
		$this->source->set('name', 'Demo Shop');
		$this->source->set('version', '2.4.1');
		$this->source->set('manifest_data', ['author' => 'A Person']);

		$this->assertSame(1, $this->details()->write());

		$expected = $this->guid->derive(['joomla_component', 'demo']);
		$record = $this->item->definitions('joomla_component')[0];

		$this->assertSame($expected, $record->guid);
		$this->assertSame(
			'guid',
			$this->item->records('joomla_component')[0]['key'],
			'A created component is keyed by its guid, never a phantom id.'
		);
		$this->assertSame('demo', $record->name_code, 'The code name drops its com_ prefix.');
		$this->assertSame('Demo Shop', $record->name);
		$this->assertSame(
			'Demo Shop (extruded)',
			$record->system_name,
			'The system name says where this component record came from.'
		);
		$this->assertSame('2.4.1', $record->component_version);
		$this->assertSame('A Person', $record->author);
		$this->assertSame(1, $record->published);
		$this->assertSame(
			$expected,
			$this->resolved->get('component.guid'),
			'The created guid is recorded for every linked-map writer to link through.'
		);
		$this->assertSame(1, $this->report->get('counts.joomla_component'));
		$this->assertTrue($this->report->get('written.joomla_component.' . $expected));

		// a dry run still records the guid, so the linkers can rehearse the
		// same relationships the real run would write
		$this->restate();
		$this->source->set('name', 'Demo Shop');
		$this->config->set('dryRun', true);

		$this->assertSame(1, $this->details()->write());
		$this->assertSame([], $this->item->records());
		$this->assertSame($expected, $this->resolved->get('component.guid'));
		$this->assertTrue($this->report->get('dryrun.joomla_component.' . $expected));
	}

	/**
	 * The view links speak the guid of the component this run created.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheViewLinksSpeakTheCreatedComponentsGuid(): void
	{
		$created = $this->guid->derive(['joomla_component', 'demo']);
		$this->resolved->set('component.guid', $created);
		$this->resolved->set('views', ['item']);
		$this->resolved->set('view.item.written.view.guid', self::VIEW_GUID);

		$this->assertSame(
			1,
			$this->componentViews()->write(),
			'A created component needs no configured id to be linked to.'
		);

		$definition = $this->item->definitions('component_admin_views')[0];

		$this->assertSame($created, $definition->joomla_component);
		$this->assertSame(self::VIEW_GUID, $definition->addadmin_views['addadmin_views0']['adminview']);
		$this->assertSame(
			['component_admin_views:joomla_component:' . $created . ':id'],
			$this->item->lookups(),
			'Only the linked-map existence check runs; the recorded guid is never re-resolved.'
		);
	}

	/**
	 * A language constant becomes the English string it stands for.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAConstantNameBecomesItsTranslation(): void
	{
		$catalogue = new LanguageRegistry();
		$catalogue->set('constant.COM_DEMO', 'Demo Shop');
		$this->config->set('component', 3);
		$this->source->set('name', 'COM_DEMO');

		$writer = new Component(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->source,
			new Language($catalogue, $this->report),
			$this->guid
		);

		$this->assertSame(
			['name' => 'Demo Shop', 'system_name' => 'Demo Shop', 'name_code' => 'demo'],
			$writer->names()
		);
		$this->assertSame(1, $writer->write());
		$this->assertSame('Demo Shop', $this->item->definitions('joomla_component')[0]->name);
	}

	/**
	 * A stated target version is kept only when it names a whole Joomla major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOnlyACredibleTargetVersionIsKept(): void
	{
		$writer = $this->details();

		$this->assertSame(['preferred_joomla_version' => 5], $writer->target('5.0'));
		$this->assertSame(['preferred_joomla_version' => 3], $writer->target(' 3.10 '));
		$this->assertSame([], $writer->target(''));
		$this->assertSame([], $writer->target('2.5'));
		$this->assertSame([], $writer->target('not a version'));
		$this->assertSame(
			[],
			$writer->target('12.0'),
			'A version outside the range Joomla has ever used is not a version.'
		);
	}

	/**
	 * A page of marketing HTML still yields one readable line.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testALongDescriptionIsSummarisedToOneLine(): void
	{
		$writer = $this->details();

		$this->assertSame('', $writer->summarise(''));
		$this->assertSame('', $writer->summarise('<div><br /></div>'));
		$this->assertSame('Short and sweet.', $writer->summarise('<p>Short and sweet.</p>'));
		$this->assertSame(
			'Ignored A first sentence long enough to be worth keeping.',
			$writer->summarise(
				"<h1>Ignored</h1>\n<p>A first sentence long enough to be worth "
				. 'keeping. A second one that is not.</p>'
			),
			'Whitespace collapses and everything up to the first sentence end is kept.'
		);
		$this->assertSame(
			'Ampersands &amp; entities survive as text.',
			$writer->summarise('<p>Ampersands &amp;amp; entities survive as text.</p>')
		);

		$long = $writer->summarise(str_repeat('word ', 100));

		$this->assertSame(150, strlen($long), 'A run-on with no sentence end is cut to length.');
		$this->assertStringEndsWith('...', $long);
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
			$flags[$flag] = (int) ($row[$flag] ?? 0);
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
	 * A pairing resolver with no verdicts, over the current boundary.
	 *
	 * @return  Pairing  The resolver.
	 * @since   6.1.7
	 */
	private function pairing(): Pairing
	{
		return new Pairing(new Decision(), $this->guid, $this->report);
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
			$this->source,
			$this->pairing(),
			new Table()
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
			$this->source,
			$this->pairing()
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
			$this->source,
			$this->componentLoad()
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
			$this->source,
			$this->componentLoad()
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
			$this->source,
			$this->pairing()
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
			$this->source,
			$this->pairing()
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
			$this->details(),
			$this->layout(),
			$this->template(),
			$this->siteView(),
			$this->componentSiteViews(),
			$this->dynamicGet(),
			$this->customAdminView(),
			$this->componentCustomAdminViews()
		);
	}

	/**
	 * The dynamic get writer under test.
	 *
	 * @return  DynamicGet  The writer.
	 * @since   6.1.8
	 */
	private function dynamicGet(): DynamicGet
	{
		return new DynamicGet(
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
	 * The custom admin view writer under test.
	 *
	 * @return  CustomAdminView  The writer.
	 * @since   6.1.8
	 */
	private function customAdminView(): CustomAdminView
	{
		return new CustomAdminView(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->view,
			$this->guid,
			$this->source,
			$this->pairing(),
			new Text()
		);
	}

	/**
	 * The component custom admin views writer under test.
	 *
	 * @return  ComponentCustomAdminViews  The writer.
	 * @since   6.1.8
	 */
	private function componentCustomAdminViews(): ComponentCustomAdminViews
	{
		return new ComponentCustomAdminViews(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->source,
			$this->componentLoad()
		);
	}

	/**
	 * The site view writer under test.
	 *
	 * @return  SiteView  The writer.
	 * @since   6.1.6
	 */
	private function siteView(): SiteView
	{
		return new SiteView(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->view,
			$this->guid,
			$this->source,
			$this->pairing()
		);
	}

	/**
	 * The component site views writer under test.
	 *
	 * @return  ComponentSiteViews  The writer.
	 * @since   6.1.6
	 */
	private function componentSiteViews(): ComponentSiteViews
	{
		return new ComponentSiteViews(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->source,
			$this->componentLoad()
		);
	}

	/**
	 * The component details writer under test.
	 *
	 * @return  Component  The writer.
	 * @since   6.1.6
	 */
	private function details(): Component
	{
		return new Component(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->source,
			new Language(new LanguageRegistry(), $this->report),
			$this->guid
		);
	}
	/**
	 * A recovered site view is written whole and linked to the component.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSiteViewsAreWrittenAndLinkedToTheComponent(): void
	{
		$this->config->set('component', 9);
		$this->view->set('site_view.app.name', 'app');
		$this->view->set('site_view.app.codename', 'app');
		$this->view->set('site_view.app.context', 'app');
		$this->view->set('site_view.app.system_name', 'App');
		$this->view->set('site_view.app.description', 'App');
		$this->view->set('site_view.app.default', '<h1>App</h1>');
		$this->view->set('site_view.app.php_view', '$a = 1;');
		$this->view->set('site_view.app.add_php_view', 1);
		$this->view->set('site_view.tag.name', 'tag');
		$this->view->set('site_view.tag.default', '<p>Tag</p>');

		$this->assertSame(2, $this->siteView()->write());

		$app = $this->item->definitions('site_view')[0];

		$this->assertSame('app', $app->name);
		$this->assertSame('app', $app->codename);
		$this->assertSame('app', $app->context);
		$this->assertSame('App', $app->system_name);
		$this->assertSame('<h1>App</h1>', $app->default);
		$this->assertSame('$a = 1;', $app->php_view);
		$this->assertSame(1, $app->add_php_view);
		$this->assertSame(1, $app->published);
		$this->assertSame(
			(new Guid())->derive([self::OPTION, 'site_view', 'app']),
			$app->guid
		);
		$this->assertSame(2, $this->report->get('counts.site_view'));
		$this->assertSame(
			'',
			$app->main_get,
			'With no dynamic get written for this view, the source is honestly empty.'
		);

		$this->assertSame(2, $this->componentSiteViews()->write());

		$link = $this->item->definitions('component_site_views')[0];
		$subform = $this->decode($link->addsite_views);

		$this->assertSame(
			self::COMPONENT_GUID,
			$link->joomla_component,
			'The link column speaks the component guid the Table class defines, never its id.'
		);
		$this->assertSame(['addsite_views0', 'addsite_views1'], array_keys($subform));
		$this->assertSame($app->guid, $subform['addsite_views0']['siteview']);
		$this->assertSame(
			'1',
			$subform['addsite_views0']['default_view'],
			'A component with no default front end view has no reachable front end.'
		);
		$this->assertSame('', $subform['addsite_views1']['default_view']);
		$this->assertSame('app', $this->report->get('site_view.default'));
		$this->assertSame(2, $this->report->get('counts.component_site_views'));
	}

	/**
	 * Every recovered view feeds from a dynamic get the run itself wrote.
	 *
	 * A view named after an admin view of this run gets the real relationship:
	 * a back end source aimed at that admin view, an item get for the single
	 * name and a list query for the plural. A view no admin view answers for
	 * still gets its get, as a custom scaffold -- never nothing at all.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testDynamicGetsFeedRecoveredViewsFromTheRunsOwnAdminViews(): void
	{
		$this->seedItemView();
		$this->seedWritten('item', 'view', self::VIEW_GUID);
		$this->view->set('site_view.item.name', 'item');
		$this->view->set('site_view.items.name', 'items');
		$this->view->set('site_view.about.name', 'about');
		$this->view->set('custom_admin_view.import.name', 'import');
		$this->view->set('custom_admin_view.item.name', 'item');

		$this->assertSame(4, $this->dynamicGet()->write());

		$single = $this->item->definition(
			'dynamic_get',
			$this->guid->derive([self::OPTION, 'dynamic_get', 'item'])
		);

		$this->assertNotNull($single);
		$this->assertSame('Item Data', $single->name);
		$this->assertSame('1', $single->main_source, 'The source is a back end view.');
		$this->assertSame('1', $single->gettype, 'The single name is an item get.');
		$this->assertSame(
			self::VIEW_GUID,
			$single->view_table_main,
			'The get feeds from the admin view this very run wrote.'
		);
		$this->assertSame('1', $single->select_all);

		$list = $this->item->definition(
			'dynamic_get',
			$this->guid->derive([self::OPTION, 'dynamic_get', 'items'])
		);

		$this->assertNotNull($list);
		$this->assertSame('2', $list->gettype, 'The plural name is a list query.');

		$scaffold = $this->item->definition(
			'dynamic_get',
			$this->guid->derive([self::OPTION, 'dynamic_get', 'about'])
		);

		$this->assertNotNull($scaffold);
		$this->assertSame('3', $scaffold->main_source);
		$this->assertSame('3', $scaffold->gettype);
		$this->assertSame('getAbout', $scaffold->getcustom);
		$this->assertStringContainsString(
			'custom scaffold',
			(string) $this->report->get('dynamic_get.custom.about'),
			'A guessed-at source is refused; the scaffold says plainly what it awaits.'
		);

		$this->assertNotNull($this->item->definition(
			'dynamic_get',
			$this->guid->derive([self::OPTION, 'dynamic_get', 'import'])
		));
		$this->assertNull(
			$this->resolved->get('dynamic_get.custom_admin_view.item.guid'),
			'A custom candidate an admin view answers for gets no view and no get.'
		);
		$this->assertSame(
			$single->guid,
			$this->resolved->get('dynamic_get.site_view.item.guid'),
			'The written get is recorded for its view to link as main_get.'
		);
		$this->assertSame(4, $this->report->get('counts.dynamic_get'));
	}

	/**
	 * A site view carries the dynamic get the run wrote for it.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testSiteViewsCarryTheDynamicGetTheRunWroteForThem(): void
	{
		$get = $this->guid->derive([self::OPTION, 'dynamic_get', 'app']);
		$this->view->set('site_view.app.name', 'app');
		$this->view->set('site_view.app.default', '<h1>App</h1>');
		$this->resolved->set('dynamic_get.site_view.app.guid', $get);

		$this->assertSame(1, $this->siteView()->write());
		$this->assertSame(
			$get,
			$this->item->definitions('site_view')[0]->main_get,
			'main_get names the view\'s own dynamic get, because a site view '
			. 'without a source displays nothing at all.'
		);
	}

	/**
	 * The administrator screens outside the tables arrive whole and linked.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testCustomAdminViewsAreWrittenWholeAndLinkedToTheComponent(): void
	{
		$this->config->set('component', 9);
		$this->seedItemView();
		$get = $this->guid->derive([self::OPTION, 'dynamic_get', 'import']);
		$this->resolved->set('dynamic_get.custom_admin_view.import.guid', $get);
		$this->view->set('custom_admin_view.import.name', 'import');
		$this->view->set('custom_admin_view.import.system_name', 'Importer');
		$this->view->set('custom_admin_view.import.description', 'Importer');
		$this->view->set('custom_admin_view.import.default', '<div>Import</div>');
		$this->view->set('custom_admin_view.import.php_view', '$a = 1;');
		$this->view->set('custom_admin_view.import.add_php_view', 1);
		$this->view->set('custom_admin_view.item.name', 'item');
		$this->view->set('custom_admin_view.item.default', '<p>generated</p>');
		$this->view->set('custom_admin_view.editor.name', 'editor');
		$this->view->set('custom_admin_view.editor.default', '<p>edited</p>');
		$this->view->set('custom_admin_view.editor.crud', 1);
		$this->view->set('custom_admin_view.venues.name', 'venues');
		$this->view->set('custom_admin_view.venues.default', '<p>venues</p>');
		$this->resolved->set('existing.admin_view_names', ['venues']);

		$this->assertSame(1, $this->customAdminView()->write());

		$definition = $this->item->definitions('custom_admin_view')[0];

		$this->assertSame(
			$this->guid->derive([self::OPTION, 'custom_admin_view', 'import']),
			$definition->guid
		);
		$this->assertSame('Importer', $definition->name);
		$this->assertSame('import', $definition->codename);
		$this->assertSame('<div>Import</div>', $definition->default);
		$this->assertSame('$a = 1;', $definition->php_view);
		$this->assertSame(1, $definition->add_php_view);
		$this->assertSame($get, $definition->main_get);
		$this->assertSame(1, $definition->published);
		$this->assertSame(
			'a table view answers for this template',
			$this->report->get('skipped.custom_admin_view.item'),
			'An admin view\'s own generated template is never a custom admin view.'
		);
		$this->assertSame(
			'a table view answers for this template',
			$this->report->get('skipped.custom_admin_view.editor'),
			'A folder an editor marked as a table view\'s own is refused even when '
			. 'no resolved view answers for its name.'
		);
		$this->assertSame(
			'a table view answers for this template',
			$this->report->get('skipped.custom_admin_view.venues'),
			'A folder answering to one of the component\'s own admin views in the '
			. 'database is that view\'s territory, whether or not this run resolved it.'
		);
		$this->assertSame(1, $this->report->get('counts.custom_admin_view'));

		$this->assertSame(1, $this->componentCustomAdminViews()->write());

		$link = $this->item->definitions('component_custom_admin_views')[0];
		$subform = $this->decode($link->addcustom_admin_views);

		$this->assertSame(
			self::COMPONENT_GUID,
			$link->joomla_component,
			'The link column speaks the component guid the Table class defines, never its id.'
		);
		$this->assertObjectNotHasProperty(
			'guid',
			$link,
			'A linked-map table holds no guid; its key is the component it links.'
		);
		$this->assertSame(
			$definition->guid,
			$subform['addcustom_admin_views0']['customadminview']
		);
		$this->assertSame('1', $subform['addcustom_admin_views0']['mainmenu']);
		$this->assertSame('1', $subform['addcustom_admin_views0']['dashboard_list']);
		$this->assertSame('1', $subform['addcustom_admin_views0']['access']);
		$this->assertSame(1, $this->report->get('counts.component_custom_admin_views'));
	}

	/**
	 * A shared field is placed where the system's own views already place it.
	 *
	 * JCB's shared fields have a home their links declare -- the Globally
	 * Unique ID field sits on the publishing tab in every view that links it
	 * -- and a new link honours that testimony rather than putting the field
	 * somewhere new. A field nothing links yet keeps the harvest's reading.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testASharedFieldIsPlacedWhereTheSystemAlreadyPlacesIt(): void
	{
		$shared = '5aa57bbe-7b19-4db9-915c-561863458d2b';
		$this->seedItemView();
		$this->seedField('item', 'guid', ['xml_type' => 'text'], 1);
		$this->seedField('item', 'title', ['xml_type' => 'text'], 1);
		$this->resolved->set('view.item.roles', [
			'guid' => ['order' => 0],
			'title' => ['title' => true, 'list' => true, 'order' => 1]
		]);
		$this->seedWritten('item', 'view', self::VIEW_GUID);
		$this->seedWritten('item', 'guid', $shared);
		$this->seedWritten('item', 'title', 'ffffffff-0000-4000-8000-000000000009');

		// what the rest of the system says about where this field lives
		$load = (new ExtrusionDatabaseFixture())->table('admin_fields', [
			['admin_view' => 'dddddddd-1111-4111-8111-dddddddddddd',
				'addfields' => json_encode(['addfields0' => [
					'field' => $shared, 'tab' => '15', 'alignment' => 4
				]])],
			['admin_view' => 'eeeeeeee-1111-4111-8111-eeeeeeeeeeee',
				'addfields' => json_encode(['addfields0' => [
					'field' => $shared, 'tab' => '15', 'alignment' => 4
				]])]
		]);
		$writer = new AdminFields(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->source,
			$load
		);

		$this->assertSame(1, $writer->write());

		$subform = $this->decode($this->item->definitions('admin_fields')[0]->addfields);
		$rows = array_column($subform, null, 'field');

		$this->assertSame(
			'15',
			$rows[$shared]['tab'],
			'The publishing tab is where every other view places this field.'
		);
		$this->assertSame(4, $rows[$shared]['alignment']);
		$this->assertSame(
			'1',
			$rows['ffffffff-0000-4000-8000-000000000009']['tab'],
			'A field nothing else links keeps the tab the harvest read.'
		);
	}

	/**
	 * Updating a view someone has curated refreshes evidence and nothing else.
	 *
	 * A re-run against a system that already holds the view must carry over
	 * what the source says -- its names, its seed data -- while the tabs,
	 * permissions and description that person arranged stay untouched. The
	 * scaffolding a new view needs is offered only when the view is new.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testAnExistingViewKeepsWhatWasCuratedAndTakesTheSourcesEvidence(): void
	{
		$this->seedItemView();
		$this->resolved->set('view.item.seed', self::SEED);
		$this->item->identity('admin_view', self::VIEW_GUID, 47);

		$this->assertSame(1, $this->adminView()->write());

		$definition = $this->item->definitions('admin_view')[0];

		$this->assertSame(self::VIEW_GUID, $definition->guid);
		$this->assertSame('item', $definition->name_single, 'The source states the names.');
		$this->assertSame('items', $definition->name_list);
		$this->assertSame('Item', $definition->system_name);
		$this->assertSame(self::SEED, $definition->sql, 'The source states the seed data.');

		foreach (['addtabs', 'addpermissions', 'description', 'short_description'] as $curated)
		{
			$this->assertObjectNotHasProperty(
				$curated,
				$definition,
				'A re-run must not reset the ' . $curated . ' someone arranged.'
			);
		}

		$this->assertSame(
			['short_description', 'description', 'type', 'add_fadein',
				'addpermissions', 'addtabs', 'published'],
			$this->report->get('kept.admin_view.' . self::VIEW_GUID),
			'The report says plainly what the re-run left alone.'
		);

		$this->restate();
		$this->seedItemView();

		$this->assertSame(1, $this->adminView()->write());

		$created = $this->item->definitions('admin_view')[0];

		$this->assertObjectHasProperty(
			'addpermissions',
			$created,
			'A view that does not yet exist still arrives with its scaffolding.'
		);
		$this->assertObjectHasProperty('addtabs', $created);
	}

	/**
	 * What the view already links is discovered and kept, never replaced.
	 *
	 * The person's own wiring -- here the Globally Unique ID field standing
	 * on the publishing tab -- survives the import exactly as it was, and a
	 * harvested field already linked adds nothing. Only fields the view does
	 * not yet link are appended, their edit order counted on from what each
	 * tab already holds.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testAdminFieldsKeepsWhatTheViewAlreadyLinks(): void
	{
		$standing = '5aa57bbe-7b19-4db9-915c-561863458d2b';
		$this->seedItemView();
		$this->seedField('item', 'name', ['xml_type' => 'text'], 1);
		$this->seedField('item', 'guid', ['xml_type' => 'text'], 1);
		$this->resolved->set('view.item.roles', [
			'name' => ['title' => true, 'list' => true, 'order' => 0],
			'guid' => ['order' => 1]
		]);
		$this->seedWritten('item', 'view', self::VIEW_GUID);
		$this->seedWritten('item', 'name', 'ffffffff-0000-4000-8000-000000000001');
		// the guid column was matched to the field the view already links
		$this->resolved->set('view.item.linked.guid.guid', $standing);

		$load = (new ExtrusionDatabaseFixture())->table('admin_fields', [
			['admin_view' => self::VIEW_GUID, 'addfields' => json_encode([
				'addfields0' => [
					'field' => $standing,
					'list' => '',
					'order_list' => '0',
					'filter' => '',
					'tab' => '15',
					'alignment' => 4,
					'order_edit' => '3'
				]
			])]
		]);
		$writer = new AdminFields(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->source,
			$load
		);

		$this->assertSame(1, $writer->write());

		$subform = $this->decode($this->item->definitions('admin_fields')[0]->addfields);

		$this->assertSame(
			[
				'field' => $standing,
				'list' => '',
				'order_list' => '0',
				'filter' => '',
				'tab' => '15',
				'alignment' => 4,
				'order_edit' => '3'
			],
			$subform['addfields0'],
			'The standing link survives verbatim -- its tab, order and flags untouched.'
		);
		$this->assertCount(
			2,
			$subform,
			'A harvested field already linked adds nothing; only the new field appends.'
		);
		$this->assertSame(
			'ffffffff-0000-4000-8000-000000000001',
			$subform['addfields1']['field'],
			'The field the view does not yet link is appended after what stands.'
		);
		$this->assertSame(
			'1',
			$subform['addfields1']['order_edit'],
			'An appended field counts its order on within its own tab.'
		);
	}

	/**
	 * What the component already links is discovered and kept, never replaced.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testComponentLinksKeepExistingSettingsAndAppendOnlyNewViews(): void
	{
		$this->config->set('component', 9);
		$this->resolved->set('views', ['item', 'category']);
		$this->resolved->set('view.item.written.view.guid', self::VIEW_GUID);
		$this->resolved->set(
			'view.category.written.view.guid',
			'cccccccc-3333-4333-8333-cccccccccccc'
		);

		$existing = [
			'addadmin_views0' => [
				'adminview' => self::VIEW_GUID,
				'icomoon' => 'shield',
				'mainmenu' => '',
				'dashboard_add' => '',
				'dashboard_list' => '1',
				'order' => '4'
			]
		];
		$load = (new ExtrusionDatabaseFixture())
			->table('joomla_component', [['id' => 9, 'guid' => self::COMPONENT_GUID]])
			->table('component_admin_views', [
				['joomla_component' => self::COMPONENT_GUID,
					'addadmin_views' => json_encode($existing)]
			]);
		$writer = new ComponentAdminViews(
			$this->config,
			$this->resolved,
			$this->item,
			$this->report,
			$this->source,
			$load
		);

		$this->assertSame(2, $writer->write());

		$subform = $this->decode(
			$this->item->definitions('component_admin_views')[0]->addadmin_views
		);

		$this->assertSame(
			$existing['addadmin_views0'],
			$subform['addadmin_views0'],
			'The person\'s own settings -- their icon, their switches -- survive verbatim.'
		);
		$this->assertCount(2, $subform, 'A view already linked adds nothing.');
		$this->assertSame(
			'cccccccc-3333-4333-8333-cccccccccccc',
			$subform['addadmin_views1']['adminview']
		);
		$this->assertSame(
			'5',
			$subform['addadmin_views1']['order'],
			'An appended view counts its order on from what already stands.'
		);
	}

	/**
	 * Nothing is written when there is nothing to write or nowhere to link it.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSiteViewsAreSkippedWhenThereIsNothingToWriteOrLink(): void
	{
		$this->assertSame(
			0,
			$this->siteView()->write(),
			'A source with no site folder yields no site view.'
		);
		$this->assertSame(
			0,
			$this->componentSiteViews()->write(),
			'With no site view recovered there is nothing to link.'
		);
		$this->assertNull($this->report->get('failed.component_site_views.no_component'));

		$this->view->set('site_view.app.name', 'app');
		$this->view->set('site_view.app.default', '<h1>App</h1>');
		$this->config->set('siteViews', false);

		$this->assertSame(
			0,
			$this->siteView()->write(),
			'The scope switch has to be able to turn site views off entirely.'
		);

		$this->config->set('siteViews', true);
		$this->siteView()->write();

		$this->assertSame(
			0,
			$this->componentSiteViews()->write(),
			'A view cannot be linked to a component the run was never given.'
		);
		$this->assertTrue($this->report->get('failed.component_site_views.no_component'));
	}

	/**
	 * The database boundary serving the target component's identity.
	 *
	 * @return  ExtrusionDatabaseFixture  The served component row.
	 * @since   6.1.7
	 */
	private function componentLoad(): ExtrusionDatabaseFixture
	{
		return (new ExtrusionDatabaseFixture())->table('joomla_component', [
			['id' => 9, 'guid' => self::COMPONENT_GUID],
			['id' => 3, 'guid' => 'eeeeeeee-0003-4999-8999-999999999999'],
			['id' => 4, 'guid' => 'eeeeeeee-0004-4999-8999-999999999999'],
			['id' => 12, 'guid' => 'eeeeeeee-0012-4999-8999-999999999999']
		]);
	}
}
