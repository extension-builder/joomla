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

namespace VDM\Joomla\Tests\Database;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use ReflectionClass;
use VDM\Joomla\Abstraction\Versioning;
use VDM\Joomla\Database\DefaultTrait;
use VDM\Joomla\Database\QuoteTrait;
use VDM\Joomla\Database\Update;
use VDM\Tests\Support\TestCase;


/**
 * Database update validation, identity tracking, and batch failure tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Update::class)]
#[UsesClass(Versioning::class)]
#[UsesTrait(DefaultTrait::class)]
#[UsesTrait(QuoteTrait::class)]
final class UpdateTest extends TestCase
{
	/**
	 * Reject missing data, key, or column targets before creating a query.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInvalidUpdatesAreRejectedWithoutDatabaseWork(): void
	{
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->never())->method('createQuery');
		$subject = $this->subject($database);

		$this->assertFalse($subject->row([], 'id', 'records'));
		$this->assertFalse($subject->row(['id' => 1], '', 'records'));
		$this->assertFalse($subject->rows([], 'id', 'records'));
		$this->assertFalse($subject->items([], 'id', 'records'));
		$this->assertFalse($subject->column('x', '', 'records'));
		$this->assertFalse($subject->column('x', 'title', ''));
	}

	/**
	 * Build a keyed update and track its explicit entity ID without a lookup query.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowBuildsUpdateAndTracksDirectIdentifier(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('update')->with('[#__example_records]')->willReturnSelf();
		$sets = [];
		$query->expects($this->exactly(2))->method('set')->willReturnCallback(
			static function (string $value) use (&$sets, &$query): QueryInterface
			{
				$sets[] = $value;
				return $query;
			}
		);
		$query->expects($this->once())->method('where')->with("[guid] = 'record-guid'")->willReturnSelf();
		$database = $this->database();
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute')->willReturn(true);
		$subject = $this->subject($database);

		$this->assertTrue($subject->row(
			['guid' => 'record-guid', 'id' => 7, 'title' => 'Updated'],
			'guid',
			'records'
		));
		$this->assertSame(['[id] = 7', "[title] = 'Updated'"], $sets);
		$this->assertSame([7], $subject->updateids());
		$this->assertSame([7], $subject->updateids(true));
		$this->assertSame([], $subject->updateids());
	}

	/**
	 * Return database failure and do not publish unresolved affected IDs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowReturnsFalseAndLeavesIdBucketEmptyWhenExecutionFails(): void
	{
		$query = $this->createStub(QueryInterface::class);
		$query->method('update')->willReturnSelf();
		$query->method('set')->willReturnSelf();
		$query->method('where')->willReturnSelf();
		$database = $this->database();
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute')->willReturn(false);
		$subject = $this->subject($database);

		$this->assertFalse($subject->row(
			['guid' => 'record-guid', 'id' => 8, 'title' => 'Failed'],
			'guid',
			'records'
		));
		$this->assertSame([], $subject->updateids());
	}

	/**
	 * Resolve all affected IDs by GUID before executing an update without an explicit ID.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowResolvesAffectedIdentifiersByGuidFallback(): void
	{
		$updateQuery = $this->createMock(QueryInterface::class);
		$updateQuery->expects($this->once())->method('update')->with('[#__example_records]')->willReturnSelf();
		$sets = [];
		$updateQuery->expects($this->exactly(2))->method('set')->willReturnCallback(
			static function (string $value) use (&$sets, &$updateQuery): QueryInterface
			{
				$sets[] = $value;
				return $updateQuery;
			}
		);
		$updateQuery->expects($this->once())->method('where')->with("[slug] = 'example'")->willReturnSelf();
		$lookupQuery = $this->createMock(QueryInterface::class);
		$lookupQuery->expects($this->once())->method('select')->with('[id]')->willReturnSelf();
		$lookupQuery->expects($this->once())->method('from')->with('[#__example_records]')->willReturnSelf();
		$lookupQuery->expects($this->once())->method('where')->with("[guid] = 'record-guid'")->willReturnSelf();
		$database = $this->database();
		$database->expects($this->exactly(2))->method('createQuery')
			->willReturnOnConsecutiveCalls($updateQuery, $lookupQuery);
		$database->expects($this->exactly(2))->method('setQuery');
		$database->expects($this->exactly(2))->method('execute')->willReturn(true);
		$database->expects($this->once())->method('getNumRows')->willReturn(2);
		$database->expects($this->once())->method('loadColumn')->willReturn([4, 5]);
		$subject = $this->subject($database);

		$this->assertTrue($subject->row(
			['slug' => 'example', 'guid' => 'record-guid', 'title' => 'Updated'],
			'slug',
			'records'
		));
		$this->assertSame(["[guid] = 'record-guid'", "[title] = 'Updated'"], $sets);
		$this->assertSame([4, 5], $subject->updateids());
	}

	/**
	 * Update one column across the selected table using the shared quoting policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testColumnBuildsUnfilteredTableUpdate(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('update')->with('[#__example_records]')->willReturnSelf();
		$query->expects($this->once())->method('set')->with('[state] = 0')->willReturnSelf();
		$query->expects($this->never())->method('where');
		$database = $this->database();
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute')->willReturn(true);

		$this->assertTrue($this->subject($database)->column(0, 'state', 'records'));
	}

	/**
	 * Report a failed batch when every underlying row update fails.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testItemsReturnsFalseWhenAllDatabaseUpdatesFail(): void
	{
		$first = $this->createStub(QueryInterface::class);
		$first->method('update')->willReturnSelf();
		$first->method('set')->willReturnSelf();
		$first->method('where')->willReturnSelf();
		$second = $this->createStub(QueryInterface::class);
		$second->method('update')->willReturnSelf();
		$second->method('set')->willReturnSelf();
		$second->method('where')->willReturnSelf();
		$database = $this->database();
		$database->expects($this->exactly(2))->method('createQuery')
			->willReturnOnConsecutiveCalls($first, $second);
		$database->expects($this->exactly(2))->method('execute')->willReturn(false);

		$this->assertFalse($this->subject($database)->items([
			(object) ['guid' => 'one', 'id' => 1, 'title' => 'One'],
			(object) ['guid' => 'two', 'id' => 2, 'title' => 'Two']
		], 'guid', 'records'));
	}

	/**
	 * Create a deterministic update instance without Joomla application globals.
	 *
	 * @param   DatabaseInterface  $database  Database boundary.
	 *
	 * @return  Update
	 * @since   6.1.6
	 */
	private function subject(DatabaseInterface $database): Update
	{
		$reflection = new ReflectionClass(Update::class);
		$subject = $reflection->newInstanceWithoutConstructor();
		$values = [
			'db' => $database,
			'componentCode' => 'example',
			'table' => '#__example',
			'componentNamespace' => 'Example\\Component\\Example',
			'params' => new Registry(['save_history' => 0]),
			'userId' => 0,
			'history' => 0,
			'maxVersions' => 0,
			'defaults' => false
		];

		foreach ($values as $property => $value)
		{
			$reflection->getProperty($property)->setValue($subject, $value);
		}

		return $subject;
	}

	/**
	 * Build deterministic SQL quoting at the mocked database boundary.
	 *
	 * @return  DatabaseInterface
	 * @since   6.1.6
	 */
	private function database(): DatabaseInterface
	{
		$database = $this->createMock(DatabaseInterface::class);
		$database->method('quoteName')->willReturnCallback(
			static fn (string|array $name): string|array => is_array($name)
				? array_map(static fn (string $value): string => '[' . $value . ']', $name)
				: '[' . $name . ']'
		);
		$database->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);

		return $database;
	}
}
