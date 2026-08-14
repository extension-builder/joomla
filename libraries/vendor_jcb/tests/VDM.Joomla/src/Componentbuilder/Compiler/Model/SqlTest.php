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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Model\Sql;
use VDM\Joomla\Componentbuilder\Compiler\Model\Sqldump;
use VDM\Tests\Support\TestCase;


/**
 * SQL source selection and collaborator contracts for the compiler model.
 *
 * @since  6.1.6
 */
#[CoversClass(Sql::class)]
#[UsesClass(Dispenser::class)]
final class SqlTest extends TestCase
{
	/**
	 * Store a generated table dump directly under the current view code.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetStoresGeneratedDumpAndConsumesRawSources(): void
	{
		$dumpCalls = [];
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = ['sql' => []];
		$dump = $this->createStub(Sqldump::class);
		$dump->method('get')->willReturnCallback(
			static function (array $tables, string $view, string $guid) use (&$dumpCalls): string
			{
				$dumpCalls[] = [$tables, $view, $guid];

				return 'INSERT INTO `#__demo` VALUES (1);';
			}
		);
		$item = (object) [
			'add_sql' => 1,
			'source' => 1,
			'name_single_code' => 'article',
			'guid' => '57d5f6fa-7b1c-4d36-b382-93281c3ef020',
			'tables' => ['#__demo'],
			'sql' => 'unused'
		];

		(new Sql($dispenser, $dump))->set($item);

		$this->assertSame([[
			['#__demo'],
			'article',
			'57d5f6fa-7b1c-4d36-b382-93281c3ef020'
		]], $dumpCalls);
		$this->assertSame(
			'INSERT INTO `#__demo` VALUES (1);',
			$dispenser->hub['sql']['article']
		);
		$this->assertObjectNotHasProperty('tables', $item);
		$this->assertObjectNotHasProperty('sql', $item);
	}

	/**
	 * Delegate custom SQL to the dispenser by reference under the view code.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetDispensesCustomSqlAndConsumesRawSources(): void
	{
		$calls = [];
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = ['sql' => []];
		$dispenser->method('set')->willReturnCallback(
			static function (&$script, string $first, ?string $second = null) use (&$calls): bool
			{
				$calls[] = [$script, $first, $second];
				$script = 'normalized:' . $script;

				return true;
			}
		);
		$dump = $this->createMock(Sqldump::class);
		$dump->expects($this->never())->method('get');
		$item = (object) [
			'add_sql' => 1,
			'source' => 2,
			'name_single_code' => 'article',
			'sql' => 'CREATE TABLE `#__demo` (`id` INT);',
			'tables' => ['unused']
		];

		(new Sql($dispenser, $dump))->set($item);

		$this->assertSame([[
			'CREATE TABLE `#__demo` (`id` INT);',
			'sql',
			'article'
		]], $calls);
		$this->assertObjectNotHasProperty('tables', $item);
		$this->assertObjectNotHasProperty('sql', $item);
	}

	/**
	 * Consume raw SQL data even when SQL inclusion is disabled.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetConsumesRawSourcesWhenSqlIsDisabled(): void
	{
		$dispenser = $this->createMock(Dispenser::class);
		$dispenser->hub = ['sql' => []];
		$dispenser->expects($this->never())->method('set');
		$dump = $this->createMock(Sqldump::class);
		$dump->expects($this->never())->method('get');
		$item = (object) [
			'add_sql' => 0,
			'source' => 2,
			'name_single_code' => 'article',
			'sql' => 'raw',
			'tables' => ['raw']
		];

		(new Sql($dispenser, $dump))->set($item);

		$this->assertObjectNotHasProperty('tables', $item);
		$this->assertObjectNotHasProperty('sql', $item);
	}

	/**
	 * Consume redundant source fields after detecting an existing generated dump.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testSetConsumesRawSourcesWhenGeneratedDumpAlreadyExists(): void
	{
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = ['sql' => ['article' => 'existing']];
		$dump = $this->createMock(Sqldump::class);
		$dump->expects($this->never())->method('get');
		$item = (object) [
			'add_sql' => 1,
			'source' => 1,
			'name_single_code' => 'article',
			'guid' => '57d5f6fa-7b1c-4d36-b382-93281c3ef020',
			'tables' => ['raw'],
			'sql' => 'raw'
		];

		(new Sql($dispenser, $dump))->set($item);

		$this->assertSame('existing', $dispenser->hub['sql']['article']);
		$this->assertObjectNotHasProperty('tables', $item);
		$this->assertObjectNotHasProperty('sql', $item);
	}
}
