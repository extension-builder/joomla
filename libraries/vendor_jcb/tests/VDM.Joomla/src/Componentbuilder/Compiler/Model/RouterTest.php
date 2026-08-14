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
use VDM\Joomla\Componentbuilder\Compiler\Builder\Router as RouterBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Model\Router;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Site-router input modeling and generated-state contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Router::class)]
#[UsesClass(Config::class)]
#[UsesClass(RouterBuilder::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(StringHelper::class)]
final class RouterTest extends CompilerDomainTestCase
{
	/**
	 * Enrich site routes, dispense custom code, and consume component inputs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetBuildsRouterStateAndConsumesRawInputs(): void
	{
		$config = $this->compilerConfig(['component_code_name' => 'demo']);
		$dispenserCalls = [];
		$dispenser = $this->createMock(Dispenser::class);
		$dispenser->expects($this->once())
			->method('set')
			->willReturnCallback(
				static function (
					&$script,
					string $first,
					?string $second = null,
					?string $third = null,
					array $options = []
				) use (&$dispenserCalls): bool
				{
					$dispenserCalls[] = [$script, $first, $second, $third, $options];
					$script = 'normalized:' . $script;

					return true;
				}
			);
		$builder = new RouterBuilder();
		$mainGet = (object) [
			'gettype' => 1,
			'main_get' => [[
				'as' => 'a',
				'selection' => [
					'select_gets' => ['id', 'alias'],
					'name' => 'article',
					'view' => 'article',
					'table' => '#__demo_article'
				]
			]]
		];
		$fields = [
			['alias' => 1, 'type_name' => 'text', 'base_name' => 'alias'],
			['alias' => 1, 'type_name' => 'textarea', 'base_name' => 'ignored_alias']
		];
		$item = (object) [
			'router_mode_constructor_before_parent' => 2,
			'router_constructor_before_parent_code' => 'ignored before code',
			'router_mode_constructor_after_parent' => 3,
			'router_constructor_after_parent_code' => 'return parent::build();',
			'router_mode_methods' => 1,
			'router_methods_code' => '',
			'router_constructor_before_parent_manual' => json_encode(
				['parent' => 'before'],
				JSON_THROW_ON_ERROR
			),
			'site_views' => [
				['settings' => (object) [
					'code' => 'article',
					'Code' => 'Article',
					'main_get' => $mainGet
				]],
				['settings' => (object) [
					'code' => 'search',
					'Code' => 'Search',
					'main_get' => (object) ['gettype' => 2, 'main_get' => null]
				]]
			],
			'admin_views' => [[
				'edit_create_site_view' => 1,
				'settings' => (object) [
					'name_single_code' => 'article',
					'fields' => $fields
				]
			]]
		];

		(new Router($config, $dispenser, $builder))->set($item);

		$this->assertSame('site', $config->lang_target);
		$this->assertSame([[
			'return parent::build();',
			'_site_router_',
			'constructor_after_parent',
			null,
			[
				'table' => 'component_router',
				'id' => null,
				'field' => 'constructor_after_parent_code',
				'type' => 'php'
			]
		]], $dispenserCalls);

		$views = $builder->get('views');
		$this->assertCount(3, $views);
		$this->assertEquals((object) [
			'view' => 'article',
			'View' => 'Article',
			'stable' => true,
			'target_view' => 'article',
			'table' => '#__demo_article',
			'table_name' => 'article',
			'alias' => 'alias',
			'key' => 'id',
			'form' => false
		], $views[0]);
		$this->assertEquals((object) [
			'view' => 'search',
			'View' => 'Search',
			'stable' => false,
			'target_view' => null,
			'table' => null,
			'table_name' => null,
			'alias' => null,
			'key' => null,
			'form' => false
		], $views[1]);
		$this->assertEquals((object) [
			'view' => 'article',
			'View' => 'Article',
			'stable' => true,
			'target_view' => 'article',
			'table' => '#__demo_article',
			'alias' => 'alias',
			'key' => 'id',
			'form' => true
		], $views[2]);
		$this->assertEquals((object) ['parent' => 'before'], $builder->get('manual'));
		$this->assertSame(2, $builder->get('mode_before'));
		$this->assertSame(3, $builder->get('mode_after'));
		$this->assertSame(1, $builder->get('mode_method'));
		$this->assertRawRouterPropertiesConsumed($item);
	}

	/**
	 * Store empty view state while rejecting disabled code and invalid manual JSON.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetHandlesEmptyViewsAndDisabledCustomCode(): void
	{
		$dispenser = $this->createMock(Dispenser::class);
		$dispenser->expects($this->never())->method('set');
		$builder = new RouterBuilder();
		$item = (object) [
			'router_mode_constructor_before_parent' => 2,
			'router_constructor_before_parent_code' => '',
			'router_mode_constructor_after_parent' => 0,
			'router_constructor_after_parent_code' => 'disabled',
			'router_mode_methods' => 3,
			'router_methods_code' => '',
			'router_constructor_before_parent_manual' => '{invalid',
			'site_views' => [],
			'admin_views' => []
		];

		(new Router($this->compilerConfig(), $dispenser, $builder))->set($item);

		$this->assertSame([], $builder->get('views'));
		$this->assertFalse($builder->exists('manual'));
		$this->assertSame(2, $builder->get('mode_before'));
		$this->assertSame(0, $builder->get('mode_after'));
		$this->assertSame(3, $builder->get('mode_method'));
		$this->assertRawRouterPropertiesConsumed($item);
	}

	/**
	 * Assert that transient router fields are removed after modeling.
	 *
	 * @param   object  $item  Modeled component item.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function assertRawRouterPropertiesConsumed(object $item): void
	{
		$this->assertObjectNotHasProperty('router_constructor_before_parent_code', $item);
		$this->assertObjectNotHasProperty('router_constructor_after_parent_code', $item);
		$this->assertObjectNotHasProperty('router_methods_code', $item);
		$this->assertObjectNotHasProperty('router_mode_constructor_before_parent', $item);
		$this->assertObjectNotHasProperty('router_mode_constructor_after_parent', $item);
		$this->assertObjectNotHasProperty('router_mode_methods', $item);
		$this->assertObjectNotHasProperty('router_constructor_before_parent_manual', $item);
	}
}
