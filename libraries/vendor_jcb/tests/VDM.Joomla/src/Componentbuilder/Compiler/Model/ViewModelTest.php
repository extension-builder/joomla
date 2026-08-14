<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Model;


use Joomla\Input\Input;
use Joomla\Registry\Registry as JoomlaRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\FunctionRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;
use VDM\Joomla\Componentbuilder\Compiler\Adminview\Data as AdminviewData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\MysqlTableSetting;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteEditView;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Customview\Data as CustomviewData;
use VDM\Joomla\Componentbuilder\Compiler\Model\Adminviews;
use VDM\Joomla\Componentbuilder\Compiler\Model\Customadminviews;
use VDM\Joomla\Componentbuilder\Compiler\Model\Mysqlsettings;
use VDM\Joomla\Componentbuilder\Compiler\Model\Siteviews;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\ObjectHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * View-list normalization and builder-state contracts for compiler models.
 *
 * @since  6.1.6
 */
#[CoversClass(Adminviews::class)]
#[CoversClass(Customadminviews::class)]
#[CoversClass(Mysqlsettings::class)]
#[CoversClass(Siteviews::class)]
#[UsesClass(AdminFilterType::class)]
#[UsesClass(MysqlTableSetting::class)]
#[UsesClass(SiteEditView::class)]
#[UsesClass(Config::class)]
#[UsesClass(ComponentConfig::class)]
#[UsesClass(FunctionRegistry::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(ObjectHelper::class)]
#[UsesClass(StringHelper::class)]
final class ViewModelTest extends TestCase
{
	/**
	 * Sort admin views, resolve data, update builders, and raise component flags.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAdminviewsCoordinatesNormalizedViewState(): void
	{
		$config = $this->config();
		$siteEditView = new SiteEditView();
		$adminFilterType = new AdminFilterType();
		$resolved = [];
		$admin = $this->createStub(AdminviewData::class);
		$admin->method('get')->willReturnCallback(
			static function ($view) use (&$resolved): object
			{
				$resolved[] = $view;

				return (object) ['name_list_code' => 'list_' . $view];
			}
		);
		$item = (object) [
			'addadmin_views' => json_encode([
				[
					'adminview' => '101',
					'order' => '4',
					'edit_create_site_view' => '1',
					'port' => '1',
					'history' => '1',
					'joomla_fields' => '1',
					'filter' => '3'
				],
				[
					'adminview' => '202',
					'order' => '2',
					'edit_create_site_view' => '0',
					'filter' => '0'
				],
				[
					'adminview' => '303',
					'order' => '0'
				]
			], JSON_THROW_ON_ERROR)
		];

		(new Adminviews($config, $admin, $siteEditView, $adminFilterType))->set($item);

		$this->assertSame([202, 101, 303], array_column($item->admin_views, 'adminview'));
		$this->assertSame([202, 101, 303], $resolved);
		$this->assertSame(202, $item->admin_views[0]['view']);
		$this->assertSame('list_202', $item->admin_views[0]['settings']->name_list_code);
		$this->assertSame('both', $config->lang_target);
		$this->assertSame('admin', $config->build_target);
		$this->assertTrue($config->get('add_eximport'));
		$this->assertTrue($config->get('set_tag_history'));
		$this->assertTrue($config->get('set_joomla_fields'));
		$this->assertTrue($siteEditView->get('101'));
		$this->assertSame(3, $adminFilterType->get('list_101'));
		$this->assertSame(2, $adminFilterType->get('list_202'));
		$this->assertSame(2, $adminFilterType->get('list_303'));
	}

	/**
	 * Resolve site views, convert scalar numeric strings, and consume source data.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSiteviewsResolvesAndNormalizesRecords(): void
	{
		$config = $this->config();
		$calls = [];
		$site = $this->createStub(CustomviewData::class);
		$site->method('get')->willReturnCallback(
			static function ($view, string $table = 'site_view') use (&$calls): object
			{
				$calls[] = [$view, $table];

				return (object) ['code' => 'site_' . $view];
			}
		);
		$item = (object) [
			'addsite_views' => '{"7":{"siteview":"42","order":"2","menu":"1"}}'
		];

		(new Siteviews($site, $config))->set($item);

		$this->assertSame([['42', 'site_view']], $calls);
		$this->assertSame(42, $item->site_views[0]['siteview']);
		$this->assertSame(42, $item->site_views[0]['view']);
		$this->assertSame(2, $item->site_views[0]['order']);
		$this->assertSame(1, $item->site_views[0]['menu']);
		$this->assertSame('site_42', $item->site_views[0]['settings']->code);
		$this->assertSame('site', $config->lang_target);
		$this->assertSame('site', $config->build_target);
		$this->assertObjectNotHasProperty('addsite_views', $item);
	}

	/**
	 * Resolve custom-admin views through their explicit table context.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomadminviewsUsesCustomTableContext(): void
	{
		$config = $this->config();
		$calls = [];
		$customAdmin = $this->createStub(CustomviewData::class);
		$customAdmin->method('get')->willReturnCallback(
			static function ($view, string $table = 'site_view') use (&$calls): object
			{
				$calls[] = [$view, $table];

				return (object) ['code' => 'admin_' . $view];
			}
		);
		$item = (object) [
			'addcustom_admin_views' => '{"5":{"customadminview":"73","order":"9"}}'
		];

		(new Customadminviews($customAdmin, $config))->set($item);

		$this->assertSame([['73', 'custom_admin_view']], $calls);
		$this->assertSame(73, $item->custom_admin_views[0]['customadminview']);
		$this->assertSame(73, $item->custom_admin_views[0]['view']);
		$this->assertSame(9, $item->custom_admin_views[0]['order']);
		$this->assertSame('admin_73', $item->custom_admin_views[0]['settings']->code);
		$this->assertSame('admin', $config->lang_target);
		$this->assertSame('custom_admin', $config->build_target);
		$this->assertObjectNotHasProperty('addcustom_admin_views', $item);
	}

	/**
	 * Move explicit MySQL settings and defaults into the dedicated builder.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMysqlsettingsMovesValuesAndDefaultsToBuilder(): void
	{
		$builder = new MysqlTableSetting();
		$item = (object) [
			'name_single_code' => 'article',
			'mysql_table_engine' => 'InnoDB',
			'mysql_table_charset' => '123',
			'mysql_table_row_format' => 'DYNAMIC'
		];

		(new Mysqlsettings($this->config(), $builder))->set($item);

		$this->assertSame('InnoDB', $builder->get('article.engine'));
		$this->assertSame('utf8', $builder->get('article.charset'));
		$this->assertSame('utf8_general_ci', $builder->get('article.collate'));
		$this->assertSame('DYNAMIC', $builder->get('article.row_format'));
		$this->assertObjectNotHasProperty('mysql_table_engine', $item);
		$this->assertObjectNotHasProperty('mysql_table_charset', $item);
		$this->assertObjectNotHasProperty('mysql_table_collate', $item);
		$this->assertObjectNotHasProperty('mysql_table_row_format', $item);
	}

	/**
	 * Create an isolated compiler configuration without a Joomla application.
	 *
	 * @return  Config
	 * @since   6.1.6
	 */
	private function config(): Config
	{
		return new Config(new Input(), new JoomlaRegistry(), new JoomlaRegistry());
	}
}
