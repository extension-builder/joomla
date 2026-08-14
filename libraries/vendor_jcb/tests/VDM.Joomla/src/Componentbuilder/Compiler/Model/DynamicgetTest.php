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
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteDynamicGet;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteMainGet;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\Selection;
use VDM\Joomla\Componentbuilder\Compiler\Model\Dynamicget;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Dynamic-get source, join, operator, and cleanup contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Dynamicget::class)]
final class DynamicgetTest extends CompilerDomainTestCase
{
	/**
	 * Build a view source and joined selection while normalizing dynamic clauses.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testViewSourceBuildsSelectionsRelationshipsAndNormalizedClauses(): void
	{
		$config = $this->compilerConfig(['build_target' => 'site']);
		$siteDynamicGet = new SiteDynamicGet();
		$siteMainGet = new SiteMainGet();
		$customcode = $this->createStub(Customcode::class);
		$customcode->method('update')->willReturnCallback(
			static fn(string $value): string => 'dynamic:' . $value
		);
		$placeholder = $this->createStub(Placeholder::class);
		$placeholder->method('update_')->willReturnCallback(
			static fn(string $value): string => str_replace('[[state]]', 'filter.search', $value)
		);
		$selectionCalls = [];
		$selection = $this->createStub(Selection::class);
		$selection->method('get')->willReturnCallback(
			static function (string $key, string $view, string $fields, string $table, string $alias, string $type, ?int $rowType = null) use (&$selectionCalls): array
			{
				$selectionCalls[] = [$key, $view, $fields, $table, $alias, $type, $rowType];

				return ['select' => $fields, 'table' => $table, 'type' => $type];
			}
		);
		$item = (object) [
			'main_source' => 1,
			'select_all' => 0,
			'view_selection' => 'a.id',
			'view_table_main' => 'article',
			'key' => 'main',
			'join_view_table' => json_encode([[
				'selection' => 'b.id',
				'view_table' => 'category',
				'as' => 'b',
				'type' => 1,
				'operator' => 1,
				'row_type' => 1,
				'on_field' => 'a.category_id',
				'join_field' => 'b.id',
			]], JSON_THROW_ON_ERROR),
			'join_db_table' => '[]',
			'filter' => '[{"operator":12,"state_key":"[[state]]"}]',
			'where' => '[{"operator":4,"value":"0"}]',
			'order' => '[{"field":"a.ordering"}]',
			'group' => '[]',
			'global' => '{invalid',
		];
		$subject = new Dynamicget(
			$config,
			$siteDynamicGet,
			$siteMainGet,
			$customcode,
			$this->createStub(Gui::class),
			$placeholder,
			$selection
		);

		$subject->set($item, 'blog', 'site.blog');

		$this->assertSame([
			['main', 'blog', 'a.id', 'article', 'a', 'view', null],
			['main', 'blog', 'b.id', 'category', 'b', 'view', 1],
		], $selectionCalls);
		$this->assertCount(2, $item->main_get);
		$this->assertSame('LEFT', $item->main_get[1]['type']);
		$this->assertSame('=', $item->main_get[1]['operator']);
		$this->assertSame('b', $siteMainGet->get('site.blog.b'));
		$this->assertSame('LIKE', $item->filter[0]['operator']);
		$this->assertSame('dynamic:filter.search', $item->filter[0]['state_key']);
		$this->assertSame('main', $item->filter[0]['key']);
		$this->assertSame('>', $item->where[0]['operator']);
		$this->assertSame([['field' => 'a.ordering']], $item->order);
		$this->assertObjectNotHasProperty('view_selection', $item);
		$this->assertObjectNotHasProperty('join_view_table', $item);
		$this->assertObjectNotHasProperty('join_db_table', $item);
		$this->assertObjectNotHasProperty('group', $item);
		$this->assertObjectNotHasProperty('global', $item);
	}

	/**
	 * A custom query bypasses every dynamic join/filter clause and records its table name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomSourceBypassesDynamicOptionsAndExtractsTheQueryTable(): void
	{
		$config = $this->compilerConfig(['build_target' => 'site']);
		$querySource = '$query->from(\'#__demo_items\')';
		$customcode = $this->createMock(Customcode::class);
		$customcode->expects($this->once())
			->method('update')
			->with($querySource)
			->willReturn($querySource);
		$gui = $this->createMock(Gui::class);
		$gui->expects($this->once())
			->method('set')
			->willReturn($querySource);
		$selection = $this->createMock(Selection::class);
		$selection->expects($this->never())->method('get');
		$item = (object) [
			'main_source' => 3,
			'key' => 'custom',
			'php_custom_get' => base64_encode($querySource),
			'join_view_table' => '[{"selection":"ignored"}]',
			'join_db_table' => '[{"selection":"ignored"}]',
			'filter' => '[{"operator":1}]',
			'where' => '[{"operator":1}]',
			'order' => '[{"field":"ignored"}]',
			'group' => '[{"field":"ignored"}]',
			'global' => '[{"field":"ignored"}]',
		];
		$subject = new Dynamicget(
			$config,
			new SiteDynamicGet(),
			new SiteMainGet(),
			$customcode,
			$gui,
			$this->createStub(Placeholder::class),
			$selection
		);

		$subject->set($item, 'catalog', 'site.catalog');

		$this->assertSame('demo_items', $item->main_get[0]['selection']['name']);
		$this->assertSame('', $item->main_get[0]['selection']['from']);
		$this->assertSame([], $item->custom_get);
		foreach (['join_view_table', 'join_db_table', 'filter', 'where', 'order', 'group', 'global'] as $property)
		{
			$this->assertObjectNotHasProperty($property, $item);
		}
	}
}
