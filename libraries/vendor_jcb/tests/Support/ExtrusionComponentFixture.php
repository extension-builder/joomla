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

namespace VDM\Tests\Support;


/**
 * Builds throwaway component source trees for the extrusion tests.
 *
 * The trees are written at run time rather than committed, so no fake component
 * file ever has to satisfy the first-party style guard, and each case states its
 * own content beside the assertion that depends on it.
 *
 * Two shapes are provided because both matter: the modern administrator layout,
 * and the Joomla 3 layout that most components an extrusion run will meet still
 * use.
 *
 * @since  6.1.6
 */
final class ExtrusionComponentFixture
{
	/**
	 * The install schema shared by both fixture shapes.
	 *
	 * It deliberately exercises the parser's real obligations: a JSON note in a
	 * column comment, a table-level primary key and unique key, a composite
	 * index, a quoted default, CURRENT_TIMESTAMP, and a seed INSERT.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	public const SCHEMA = <<<'SQL'
CREATE TABLE IF NOT EXISTS `#__example_item` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '{"label":"Item Name","type":"text"}',
	`alias` VARCHAR(255) NOT NULL DEFAULT '',
	`description` MEDIUMTEXT NOT NULL,
	`colour` CHAR(7) NOT NULL DEFAULT '#ffffff',
	`amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
	`counter` INT(10) unsigned NOT NULL DEFAULT 0,
	`published` TINYINT(1) NOT NULL DEFAULT 1,
	`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `idx_alias` (`alias`),
	KEY `idx_name_colour` (`name`,`colour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__example_category` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(100) NOT NULL DEFAULT '',
	`note` TEXT NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `#__example_category` (`id`, `title`) VALUES (1, 'First; not a split');
SQL;

	/**
	 * The item edit form shared by both fixture shapes.
	 *
	 * It carries a fieldset grouping, a language constant in every display
	 * attribute, a list field with options, and a showon dependency.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	public const FORM = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<form>
	<fieldset name="details" label="COM_EXAMPLE_ITEM_FIELDSET_DETAILS">
		<field name="name" type="text" label="COM_EXAMPLE_ITEM_NAME_LABEL"
			description="COM_EXAMPLE_ITEM_NAME_DESC" size="60" required="true"
			hint="COM_EXAMPLE_ITEM_NAME_HINT" class="form-control" />
		<field name="alias" type="text" label="COM_EXAMPLE_ITEM_ALIAS_LABEL" />
		<field name="description" type="editor" label="COM_EXAMPLE_ITEM_DESCRIPTION_LABEL" />
		<field name="colour" type="color" label="COM_EXAMPLE_ITEM_COLOUR_LABEL" />
	</fieldset>
	<fieldset name="metrics" label="COM_EXAMPLE_ITEM_FIELDSET_METRICS">
		<field name="amount" type="number" label="COM_EXAMPLE_ITEM_AMOUNT_LABEL" />
		<field name="counter" type="list" label="COM_EXAMPLE_ITEM_COUNTER_LABEL"
			showon="amount!:0[AND]colour:#ffffff">
			<option value="1">COM_EXAMPLE_ITEM_COUNTER_ONE</option>
			<option value="2">COM_EXAMPLE_ITEM_COUNTER_TWO</option>
		</field>
	</fieldset>
</form>
XML;

	/**
	 * The English language catalogue shared by both fixture shapes.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	public const LANGUAGE = <<<'INI'
COM_EXAMPLE="Example"
COM_EXAMPLE_ITEM_FIELDSET_DETAILS="Item Details"
COM_EXAMPLE_ITEM_FIELDSET_METRICS="Metrics"
COM_EXAMPLE_ITEM_NAME_LABEL="Name"
COM_EXAMPLE_ITEM_NAME_DESC="The name of the _QQ_item_QQ_ shown to users."
COM_EXAMPLE_ITEM_NAME_HINT="Enter a name"
COM_EXAMPLE_ITEM_ALIAS_LABEL="Alias"
COM_EXAMPLE_ITEM_DESCRIPTION_LABEL="Description"
COM_EXAMPLE_ITEM_COLOUR_LABEL="Colour"
COM_EXAMPLE_ITEM_AMOUNT_LABEL="Amount"
COM_EXAMPLE_ITEM_COUNTER_LABEL="Counter"
COM_EXAMPLE_ITEM_COUNTER_ONE="One"
COM_EXAMPLE_ITEM_COUNTER_TWO="Two"
INI;

	/**
	 * A layout carrying both a PHP part and an HTML part.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	public const LAYOUT = <<<'LAYOUT'
<?php
/**
 * A fixture layout header that extrusion must discard.
 */

namespace Example\Layout;

use Joomla\CMS\Factory;

defined('_JEXEC') or die;

$total = count($displayData);
$label = 'Items';
?>
<div class="example-layout">
	<h3><?php echo $label; ?> (<?php echo $total; ?>)</h3>
</div>
LAYOUT;

	/**
	 * The component manifest, named after the component.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	public const MANIFEST = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<extension type="component" method="upgrade">
	<name>com_example</name>
	<version>2.4.1</version>
</extension>
XML;

	/**
	 * A decoy manifest that must never win the identity ranking.
	 *
	 * A generically named manifest buried in a compiler template folder is
	 * exactly what previously hijacked the component code name and silently
	 * ruined every view name downstream.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	public const DECOY = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<extension type="component" method="upgrade">
	<name>com_decoy</name>
	<version>0.0.1</version>
</extension>
XML;

	/**
	 * The relative file map of a modern component tree.
	 *
	 * @return  array<string, string>  Relative path keyed to its contents.
	 * @since   6.1.6
	 */
	/**
	 * The access rules of the compiled component.
	 *
	 * @var    string
	 * @since  6.1.8
	 */
	public const ACCESS = <<<'XML'
<?xml version="1.0" encoding="utf-8" ?>
<access component="com_example">
	<section name="component">
		<action name="core.admin" title="JACTION_ADMIN" />
		<action name="item.access" title="ITEM_ACCESS" />
		<action name="item.batch" title="ITEM_BATCH" />
		<action name="item.edit" title="ITEM_EDIT" />
		<action name="other.edit" title="OTHER_EDIT" />
	</section>
	<section name="item">
		<action name="item.edit" title="ITEM_EDIT" />
		<action name="core.delete" title="ITEM_DELETE" />
	</section>
</access>
XML;

	/**
	 * The controller of the screen that edits one record.
	 *
	 * @var    string
	 * @since  6.1.8
	 */
	public const EDIT_CONTROLLER = <<<'PHP'
<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

class ItemController extends FormController
{
	protected $view_item = 'item';
}
PHP;

	/**
	 * The controller of the list screen, naming the edit screen's model.
	 *
	 * @var    string
	 * @since  6.1.8
	 */
	public const LIST_CONTROLLER = <<<'PHP'
<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class ItemsAllController extends AdminController
{
	public function getModel($name = 'Item', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
PHP;

	/**
	 * The controller of a screen that answers for itself.
	 *
	 * @var    string
	 * @since  6.1.8
	 */
	public const OWN_CONTROLLER = <<<'PHP'
<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

class DashboardController extends AdminController
{
	public function getModel($name = 'Dashboard', $prefix = 'Administrator', $config = ['ignore_request' => true])
	{
		return parent::getModel($name, $prefix, $config);
	}
}
PHP;

	/**
	 * The edit screen, stating its tabs and rendering their columns.
	 *
	 * @var    string
	 * @since  6.1.8
	 */
	public const EDIT_SCREEN = <<<'PHP'
<?php
defined('_JEXEC') or die;
?>
<?php echo LayoutHelper::render('item.details_above', $this); ?>
<?php echo Html::_('uitab.startTabSet', 'itemTab', ['active' => 'details']); ?>
<?php echo Html::_('uitab.addTab', 'itemTab', 'details', Text::_('COM_EXAMPLE_ITEM_DETAILS', true)); ?>
	<div class="row">
		<div class="col-md-6"><?php echo LayoutHelper::render('item.details_left', $this); ?></div>
		<div class="col-md-6"><?php echo LayoutHelper::render('item.details_right', $this); ?></div>
	</div>
<?php echo Html::_('uitab.endTab'); ?>
<?php echo Html::_('uitab.addTab', 'itemTab', 'metrics', Text::_('COM_EXAMPLE_ITEM_METRICS', true)); ?>
	<div class="row">
		<div class="col-md-12"><?php echo LayoutHelper::render('item.metrics_fullwidth', $this); ?></div>
	</div>
<?php echo Html::_('uitab.endTab'); ?>
<?php echo Html::_('uitab.addTab', 'itemTab', 'notes', Text::_('COM_EXAMPLE_ITEM_NOTES', true)); ?>
	<div class="row"><p>Nothing here but a note the author wrote.</p></div>
<?php echo Html::_('uitab.endTab'); ?>
<?php echo Html::_('uitab.addTab', 'itemTab', 'publishing', Text::_('COM_EXAMPLE_ITEM_PUBLISHING', true)); ?>
	<div class="row">
		<div class="col-md-6"><?php echo LayoutHelper::render('item.publishing', $this); ?></div>
	</div>
<?php echo Html::_('uitab.endTab'); ?>
<?php echo Html::_('uitab.addTab', 'itemTab', 'permissions', Text::_('COM_EXAMPLE_ITEM_PERMISSION', true)); ?>
	<div class="row"><?php echo $this->form->getInput('rules'); ?></div>
<?php echo Html::_('uitab.endTab'); ?>
<?php echo Html::_('uitab.endTabSet'); ?>
PHP;

	/**
	 * The left column of the details tab.
	 *
	 * @var    string
	 * @since  6.1.8
	 */
	public const COLUMN_LEFT = <<<'PHP'
<?php
defined('_JEXEC') or die;
$fields = $displayData->get($fields_tab_layout) ?: array(
	'name',
	'alias'
);
?>
PHP;

	/**
	 * The right column of the details tab.
	 *
	 * @var    string
	 * @since  6.1.8
	 */
	public const COLUMN_RIGHT = <<<'PHP'
<?php
defined('_JEXEC') or die;
$fields = $displayData->get($fields_tab_layout) ?: array(
	'description'
);
?>
PHP;

	/**
	 * The full width column of the metrics tab.
	 *
	 * @var    string
	 * @since  6.1.8
	 */
	public const COLUMN_WIDE = <<<'PHP'
<?php
defined('_JEXEC') or die;
$fields = $displayData->get($fields_tab_layout) ?: array(
	'counter'
);
?>
PHP;

	/**
	 * The column of the section the compiler generates for publishing.
	 *
	 * @var    string
	 * @since  6.1.8
	 */
	public const COLUMN_PUBLISHING = <<<'PHP'
<?php
defined('_JEXEC') or die;
$fields = $displayData->get($fields_tab_layout) ?: array(
	'guid',
	'published'
);
?>
PHP;

	/**
	 * The relative file map of a modern administrator component tree.
	 *
	 * @return  array<string, string>  Relative path keyed to its contents.
	 * @since   6.1.6
	 */
	public static function modern(): array
	{
		return [
			'com_example/com_example.xml' => self::MANIFEST,
			'com_example/admin/sql/install.mysql.utf8.sql' => self::SCHEMA,
			'com_example/admin/forms/item.xml' => self::FORM,
			'com_example/admin/language/en-GB/com_example.ini' => self::LANGUAGE,
			'com_example/admin/layouts/summary.php' => self::LAYOUT,
			'com_example/admin/tmpl/item/default.php' => "<?php\ndefined('_JEXEC') or die;\n?>\n<p>main</p>",
			'com_example/admin/tmpl/item/default_extra.php' => self::LAYOUT,
			'com_example/admin/src/Model/ItemModel.php' => "<?php\nclass ItemModel {}",
			'com_example/admin/src/Table/ItemTable.php' => "<?php\nclass ItemTable {}",
			'com_example/admin/services/provider.php' => "<?php\nreturn null;",
			'com_example/compiler/joomla_3/component.xml' => self::DECOY
		];
	}

	/**
	 * The relative file map of a component JCB itself compiled.
	 *
	 * What matters here is everything a compiled component states about
	 * itself that nothing else can: the controller of a list screen naming
	 * the model of the screen that edits one record, the edit screen naming
	 * its tabs and rendering each column from a layout of the view's own
	 * folder, those layouts listing their fields in order, and the access
	 * rules stating each permission at the level the component offers it.
	 *
	 * @return  array<string, string>  Relative path keyed to its contents.
	 * @since   6.1.8
	 */
	public static function compiled(): array
	{
		return [
			'com_example/com_example.xml' => self::MANIFEST,
			'com_example/admin/sql/install.mysql.utf8.sql' => self::SCHEMA,
			'com_example/admin/forms/item.xml' => self::FORM,
			'com_example/admin/language/en-GB/com_example.ini' => self::LANGUAGE,
			'com_example/admin/access.xml' => self::ACCESS,
			'com_example/admin/src/Controller/ItemController.php' => self::EDIT_CONTROLLER,
			'com_example/admin/src/Controller/ItemsAllController.php' => self::LIST_CONTROLLER,
			'com_example/admin/src/Controller/DashboardController.php' => self::OWN_CONTROLLER,
			'com_example/admin/tmpl/item/default.php' => self::EDIT_SCREEN,
			'com_example/admin/tmpl/itemsall/default.php' => "<?php\ndefined('_JEXEC') or die;\n?>\n<p>list</p>",
			'com_example/admin/tmpl/dashboard/default.php' => "<?php\ndefined('_JEXEC') or die;\n?>\n<p>dash</p>",
			'com_example/admin/layouts/item/details_left.php' => self::COLUMN_LEFT,
			'com_example/admin/layouts/item/details_right.php' => self::COLUMN_RIGHT,
			'com_example/admin/layouts/item/metrics_fullwidth.php' => self::COLUMN_WIDE,
			'com_example/admin/layouts/item/publishing.php' => self::COLUMN_PUBLISHING
		];
	}

	/**
	 * The relative file map of a Joomla 3 component tree.
	 *
	 * @return  array<string, string>  Relative path keyed to its contents.
	 * @since   6.1.6
	 */
	public static function legacy(): array
	{
		return [
			'com_legacy/com_legacy.xml' => str_replace('com_example', 'com_legacy', self::MANIFEST),
			'com_legacy/admin/sql/install.mysql.utf8.sql' => str_replace(
				'#__example_', '#__legacy_', self::SCHEMA
			),
			'com_legacy/admin/models/forms/item.xml' => self::FORM,
			'com_legacy/admin/language/en-GB/en-GB.com_legacy.ini' => self::LANGUAGE,
			'com_legacy/admin/layouts/summary.php' => self::LAYOUT,
			'com_legacy/admin/views/item/tmpl/default.php' => "<?php\ndefined('_JEXEC') or die;\n?>\n<p>main</p>",
			'com_legacy/admin/views/item/tmpl/default_extra.php' => self::LAYOUT,
			'com_legacy/admin/models/item.php' => "<?php\nclass LegacyModelItem {}",
			'com_legacy/admin/tables/item.php' => "<?php\nclass LegacyTableItem {}"
		];
	}

	/**
	 * A JCB table definition class, the highest precedence source.
	 *
	 * The map states a relationship, a per-field GUID, a storage encoding, the
	 * title field and a tab grouping -- none of which the schema or the form XML
	 * can express.
	 *
	 * @return  string  The class source.
	 * @since   6.1.6
	 */
	public static function tableClass(): string
	{
		return <<<'PHP'
<?php
namespace Example\Power;

use VDM\Joomla\Abstraction\BaseTable;
use VDM\Joomla\Interfaces\TableInterface;

class Table extends BaseTable implements TableInterface
{
	protected array $tables = [
		'example_item' => [
			'name' => [
				'name' => 'name',
				'guid' => '11111111-2222-4333-8444-555555555555',
				'label' => 'COM_EXAMPLE_ITEM_NAME_LABEL',
				'type' => 'text',
				'title' => true,
				'list' => 'items',
				'store' => NULL,
				'tab_name' => 'Item Details',
				'db' => [
					'type' => 'VARCHAR(255)',
					'default' => '',
					'null_switch' => 'NOT NULL',
					'unique_key' => false,
					'key' => true,
				],
				'link' => NULL,
			],
			'description' => [
				'name' => 'description',
				'guid' => '66666666-7777-4888-8999-aaaaaaaaaaaa',
				'label' => 'COM_EXAMPLE_ITEM_DESCRIPTION_LABEL',
				'type' => 'editor',
				'title' => false,
				'list' => 'items',
				'store' => 'base64',
				'tab_name' => 'Item Details',
				'db' => [
					'type' => 'MEDIUMTEXT',
					'default' => 'EMPTY',
					'null_switch' => 'NOT NULL',
				],
				'link' => NULL,
			],
			'counter' => [
				'name' => 'counter',
				'guid' => 'bbbbbbbb-cccc-4ddd-8eee-ffffffffffff',
				'label' => 'COM_EXAMPLE_ITEM_COUNTER_LABEL',
				'type' => 'list',
				'title' => false,
				'list' => 'items',
				'store' => 'json',
				'tab_name' => 'Metrics',
				'db' => [
					'type' => 'INT(10) unsigned',
					'default' => '0',
					'null_switch' => 'NOT NULL',
				],
				'link' => [
					'type' => 1,
					'table' => '#__example_category',
					'component' => 'com_example',
					'entity' => 'category',
					'value' => 'title',
					'key' => 'id',
				],
			],
		],
	];
}
PHP;
	}

	/**
	 * A table definition class whose map is not a safe literal.
	 *
	 * The literal reader must refuse this outright rather than partially trust
	 * it, because the source tree is untrusted.
	 *
	 * @return  string  The class source.
	 * @since   6.1.6
	 */
	public static function unsafeTableClass(): string
	{
		return <<<'PHP'
<?php
class Unsafe extends Base
{
	protected array $tables = [
		'example_item' => [
			'name' => [
				'name' => strtolower('NAME'),
			],
		],
	];
}
PHP;
	}
}
