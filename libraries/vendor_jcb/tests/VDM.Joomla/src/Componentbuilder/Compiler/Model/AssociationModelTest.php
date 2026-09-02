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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Plugin\Routes;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomAlias as BuilderCustomAlias;
use VDM\Joomla\Componentbuilder\Compiler\Field\Name as FieldName;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\ModuleDataInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PluginDataInterface;
use VDM\Joomla\Componentbuilder\Compiler\Model\Customalias;
use VDM\Joomla\Componentbuilder\Compiler\Model\Joomlamodules;
use VDM\Joomla\Componentbuilder\Compiler\Model\Joomlaplugins;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Tests\Support\TestCase;


/**
 * Association filtering and injected collaborator contracts for compiler models.
 *
 * @since  6.1.6
 */
#[CoversClass(Customalias::class)]
#[CoversClass(Joomlamodules::class)]
#[CoversClass(Joomlaplugins::class)]
#[UsesClass(BuilderCustomAlias::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
final class AssociationModelTest extends TestCase
{
	/**
	 * Load module associations except records explicitly targeting another extension.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJoomlamodulesFiltersTargetTwoAndForwardsItemContext(): void
	{
		$calls = [];
		$module = $this->createStub(ModuleDataInterface::class);
		$module->method('set')->willReturnCallback(
			static function ($id) use (&$calls): bool
			{
				$calls[] = func_get_args();

				return true;
			}
		);
		$item = (object) [
			'name_code' => 'example',
			'addjoomla_modules' => json_encode([
				['module' => 11],
				['module' => 22, 'target' => 1],
				['module' => 33, 'target' => 2],
				['module' => 44, 'target' => '2']
			], JSON_THROW_ON_ERROR)
		];

		(new Joomlamodules($module))->set($item);

		$this->assertCount(2, $calls);
		$this->assertSame(11, $calls[0][0]);
		$this->assertSame($item, $calls[0][1]);
		$this->assertSame(22, $calls[1][0]);
		$this->assertSame($item, $calls[1][1]);
		$this->assertObjectNotHasProperty('addjoomla_modules', $item);
	}

	/**
	 * Load plugin associations except records explicitly targeting another extension.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJoomlapluginsFiltersTargetTwoAndForwardsItemContext(): void
	{
		$calls = [];
		$plugin = $this->createStub(PluginDataInterface::class);
		$plugin->method('set')->willReturnCallback(
			static function ($id) use (&$calls): bool
			{
				$calls[] = func_get_args();

				return true;
			}
		);
		$item = (object) [
			'name_code' => 'example',
			'addjoomla_plugins' => json_encode([
				['plugin' => 'alpha'],
				['plugin' => 'beta', 'target' => 0],
				['plugin' => 'gamma', 'target' => 2]
			], JSON_THROW_ON_ERROR)
		];

		(new Joomlaplugins($plugin, $this->createStub(Routes::class)))->set($item);

		$this->assertCount(2, $calls);
		$this->assertSame('alpha', $calls[0][0]);
		$this->assertSame($item, $calls[0][1]);
		$this->assertSame('beta', $calls[1][0]);
		$this->assertSame($item, $calls[1][1]);
		$this->assertObjectNotHasProperty('addjoomla_plugins', $item);
	}

	/**
	 * Register the API routes of the admin views before any plugin loads.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlapluginsRegistersTheApiRoutesBeforeLoadingThePlugins(): void
	{
		$order = [];
		$views = [['add_api' => 2, 'settings' => (object) ['name_list_code' => 'articles']]];
		$custom = [['settings' => (object) ['code' => 'report']]];
		$site = [['settings' => (object) ['code' => 'page']]];

		$routes = $this->createMock(Routes::class);
		$routes->expects($this->once())
			->method('set')
			->with($views, $custom, $site)
			->willReturnCallback(
				static function () use (&$order): void
				{
					$order[] = 'routes';
				}
			);

		$plugin = $this->createStub(PluginDataInterface::class);
		$plugin->method('set')->willReturnCallback(
			static function () use (&$order): bool
			{
				$order[] = 'plugin';
				return true;
			}
		);

		$item = (object) [
			'admin_views' => $views,
			'custom_admin_views' => $custom,
			'site_views' => $site,
			'addjoomla_plugins' => json_encode([['plugin' => 'alpha']], JSON_THROW_ON_ERROR)
		];

		(new Joomlaplugins($plugin, $routes))->set($item);

		$this->assertSame(['routes', 'plugin'], $order);
		$this->assertSame($views, $item->admin_views);
		$this->assertSame($custom, $item->custom_admin_views);
		$this->assertSame($site, $item->site_views);
	}

	/**
	 * A component without admin views still offers the plugins the empty route set.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlapluginsOffersTheEmptyRouteSetWithoutAdminViews(): void
	{
		$routes = $this->createMock(Routes::class);
		$routes->expects($this->once())->method('set')->with([], [], []);

		$plugin = $this->createStub(PluginDataInterface::class);
		$plugin->method('set')->willReturn(true);

		$item = (object) [
			'addjoomla_plugins' => json_encode([['plugin' => 'alpha']], JSON_THROW_ON_ERROR)
		];

		(new Joomlaplugins($plugin, $routes))->set($item);
	}

	/**
	 * A component without plugins registers no route placeholders.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlapluginsRegistersNoRoutesWithoutPlugins(): void
	{
		$routes = $this->createMock(Routes::class);
		$routes->expects($this->never())->method('set');

		$plugin = $this->createMock(PluginDataInterface::class);
		$plugin->expects($this->never())->method('set');

		$item = (object) ['admin_views' => [], 'addjoomla_plugins' => '[]'];

		(new Joomlaplugins($plugin, $routes))->set($item);

		$this->assertObjectNotHasProperty('addjoomla_plugins', $item);
	}

	/**
	 * Reject invalid association JSON without invoking injected loaders.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testModuleAndPluginModelsConsumeInvalidJsonWithoutCalls(): void
	{
		$module = $this->createMock(ModuleDataInterface::class);
		$plugin = $this->createMock(PluginDataInterface::class);
		$module->expects($this->never())->method('set');
		$plugin->expects($this->never())->method('set');
		$moduleItem = (object) ['addjoomla_modules' => '{invalid'];
		$pluginItem = (object) ['addjoomla_plugins' => '{invalid'];

		(new Joomlamodules($module))->set($moduleItem);
		(new Joomlaplugins($plugin, $this->createStub(Routes::class)))->set($pluginItem);

		$this->assertObjectNotHasProperty('addjoomla_modules', $moduleItem);
		$this->assertObjectNotHasProperty('addjoomla_plugins', $pluginItem);
	}

	/**
	 * Resolve selected alias fields in view-field order and store their names.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomaliasStoresResolvedNamesForSelectedFields(): void
	{
		$builder = new BuilderCustomAlias();
		$calls = [];
		$fieldName = $this->createStub(FieldName::class);
		$fieldName->method('get')->willReturnCallback(
			static function (array &$field, ?string $listView) use (&$calls): string
			{
				$calls[] = [$field['field'], $listView];

				return 'field_' . $field['field'];
			}
		);
		$item = (object) [
			'name_single_code' => 'article',
			'name_list_code' => 'articles',
			'alias_builder_type' => 2,
			'alias_builder' => '[33,11]',
			'fields' => [
				['field' => 11],
				['field' => 22],
				['field' => 33]
			]
		];

		(new Customalias($builder, $fieldName))->set($item);

		$this->assertSame([
			[11, 'articles'],
			[33, 'articles']
		], $calls);
		$this->assertSame([
			0 => 'field_11',
			2 => 'field_33'
		], $builder->get('article'));
		$this->assertObjectNotHasProperty('alias_builder', $item);
	}

	/**
	 * Preserve the first alias registry value and avoid resolving it again.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomaliasDoesNotOverwriteExistingBuilderState(): void
	{
		$builder = new BuilderCustomAlias();
		$builder->set('article', ['existing_alias']);
		$fieldName = $this->createMock(FieldName::class);
		$fieldName->expects($this->never())->method('get');
		$item = (object) [
			'name_single_code' => 'article',
			'name_list_code' => 'articles',
			'alias_builder_type' => 2,
			'alias_builder' => '[11]',
			'fields' => [['field' => 11]]
		];

		(new Customalias($builder, $fieldName))->set($item);

		$this->assertSame(['existing_alias'], $builder->get('article'));
		$this->assertObjectNotHasProperty('alias_builder', $item);
	}
}
