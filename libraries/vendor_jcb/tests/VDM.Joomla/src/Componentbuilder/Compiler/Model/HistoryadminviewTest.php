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
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HistoryInterface;
use VDM\Joomla\Componentbuilder\Compiler\Model\Historyadminview;
use VDM\Joomla\Componentbuilder\Compiler\Model\Updatesql;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Admin-view history to update-SQL transition contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Historyadminview::class)]
#[UsesClass(Config::class)]
#[UsesClass(StringHelper::class)]
final class HistoryadminviewTest extends CompilerDomainTestCase
{
	/**
	 * Record renamed tables and explicit or default-backed table-setting changes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRecordsHistoryTransitionsAndSkipsNumericFallbacks(): void
	{
		$config = $this->compilerConfig([
			'mysql_table_keys' => [
				'engine' => ['default' => 'MyISAM'],
				'charset' => ['default' => 'utf8'],
				'collate' => ['default' => 'utf8_general_ci']
			]
		]);
		$old = (object) [
			'name_single' => 'Old News',
			'mysql_table_engine' => 'MyISAM'
		];
		$history = $this->createMock(HistoryInterface::class);
		$history->expects($this->once())
			->method('get')
			->with('admin_view', 41)
			->willReturn($old);
		$calls = [];
		$updates = $this->createMock(Updatesql::class);
		$updates->expects($this->exactly(3))
			->method('set')
			->willReturnCallback(
				static function ($oldValue, $newValue, string $type, $key = null) use (&$calls): void
				{
					$calls[] = [$oldValue, $newValue, $type, $key];
				}
			);
		$item = (object) [
			'id' => 41,
			'name_single_code' => 'news_item',
			'mysql_table_engine' => 'InnoDB',
			'mysql_table_charset' => 'utf8mb4',
			'mysql_table_collate' => 123
		];

		(new Historyadminview($config, $history, $updates))->set($item);

		$this->assertSame([
			['old_news', 'news_item', 'table_name', 'news_item'],
			['MyISAM', 'InnoDB', 'table_engine', 'news_item'],
			['utf8', 'utf8mb4', 'table_charset', 'news_item']
		], $calls);
	}

	/**
	 * Leave update state untouched when no historical admin-view record exists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetDoesNothingWithoutHistory(): void
	{
		$history = $this->createMock(HistoryInterface::class);
		$history->expects($this->once())
			->method('get')
			->with('admin_view', 9)
			->willReturn(null);
		$updates = $this->createMock(Updatesql::class);
		$updates->expects($this->never())->method('set');
		$item = (object) [
			'id' => 9,
			'name_single_code' => 'article'
		];

		(new Historyadminview($this->compilerConfig(), $history, $updates))->set($item);

		$this->assertObjectNotHasProperty('old_component_version', $item);
	}
}
