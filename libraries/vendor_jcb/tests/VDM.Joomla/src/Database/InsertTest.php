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
use ReflectionClass;
use VDM\Joomla\Abstraction\Versioning;
use VDM\Joomla\Database\DefaultTrait;
use VDM\Joomla\Database\Insert;
use VDM\Joomla\Database\QuoteTrait;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\MathHelper;
use VDM\Tests\Support\TestCase;


/**
 * Database insert mapping, batching, identifier, and failure-contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Insert::class)]
#[UsesClass(Versioning::class)]
#[UsesClass(DefaultTrait::class)]
#[UsesClass(QuoteTrait::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(MathHelper::class)]
final class InsertTest extends TestCase
{
	/**
	 * Reject empty or structurally invalid datasets before query creation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowsAndItemsRejectInvalidDatasetsWithoutDatabaseWork(): void
	{
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->never())->method('createQuery');
		$subject = $this->subject($database);

		$this->assertFalse($subject->rows([], 'records'));
		$this->assertFalse($subject->rows(['not-a-row'], 'records'));
		$this->assertFalse($subject->items([], 'records'));
		$this->assertFalse($subject->items([['not-an-object']], 'records'));
	}

	/**
	 * Infer array columns, quote each value, prefix the table, and track sequential IDs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowsBuildsInsertAndTracksGeneratedIdRange(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('insert')->with('[#__example_records]')->willReturnSelf();
		$query->expects($this->once())->method('columns')->with(['[name]', '[state]'])->willReturnSelf();
		$query->expects($this->exactly(2))->method('values')->willReturnCallback(
			static fn (string $values): QueryInterface => $query
		);
		$database = $this->database();
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute')->willReturn(true);
		$database->expects($this->once())->method('insertid')->willReturn(100);
		$subject = $this->subject($database);
		$data = [['name' => 'One', 'state' => 1], ['name' => 'Two', 'state' => 0]];

		$this->assertTrue($subject->defaults(false)->rows($data, 'records'));
		$this->assertSame([100, 101], $subject->insertids());
		$this->assertSame([100, 101], $subject->insertids(true));
		$this->assertSame([], $subject->insertids());
		$this->assertSame([['name' => 'One', 'state' => 1], ['name' => 'Two', 'state' => 0]], $data);
	}

	/**
	 * Remap object properties into explicitly selected database columns.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemsHonorsExplicitColumnRemapping(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('insert')->willReturnSelf();
		$query->expects($this->once())->method('columns')->with(['[title]', '[published]'])->willReturnSelf();
		$query->expects($this->once())->method('values')->with("'Mapped',1")->willReturnSelf();
		$database = $this->database();
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute')->willReturn(true);
		$database->expects($this->once())->method('insertid')->willReturn('9007199254740992');
		$subject = $this->subject($database);

		$this->assertTrue($subject->defaults(false)->items(
			[(object) ['source_title' => 'Mapped', 'source_state' => 1, 'ignored' => 'x']],
			'records',
			['title' => 'source_title', 'published' => 'source_state']
		));
		$this->assertSame(['9007199254740992'], $subject->insertids());
	}

	/**
	 * Split large inserts at the reviewed query boundary and preserve global ID order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowsChunksLargeBatchAndCombinesIdentifierRanges(): void
	{
		$first = $this->createStub(QueryInterface::class);
		$first->method('insert')->willReturnSelf();
		$first->method('columns')->willReturnSelf();
		$first->method('values')->willReturnSelf();
		$second = $this->createStub(QueryInterface::class);
		$second->method('insert')->willReturnSelf();
		$second->method('columns')->willReturnSelf();
		$second->method('values')->willReturnSelf();
		$database = $this->database();
		$database->expects($this->exactly(2))->method('createQuery')
			->willReturnOnConsecutiveCalls($first, $second);
		$database->expects($this->exactly(2))->method('setQuery');
		$database->expects($this->exactly(2))->method('execute')->willReturn(true);
		$database->expects($this->exactly(2))->method('insertid')
			->willReturnOnConsecutiveCalls(10, 500);
		$data = array_map(static fn (int $id): array => ['name' => 'row-' . $id], range(1, 301));
		$subject = $this->subject($database);

		$this->assertTrue($subject->defaults(false)->rows($data, 'records'));
		$this->assertCount(301, $subject->insertids());
		$this->assertSame(10, $subject->insertids()[0]);
		$this->assertSame(308, $subject->insertids()[298]);
		$this->assertSame(500, $subject->insertids()[299]);
		$this->assertSame(501, $subject->insertids()[300]);
	}

	/**
	 * Surface a failed persistence boundary instead of reporting a successful insert.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testRowsReturnsFalseWhenDatabaseExecutionFails(): void
	{
		$query = $this->createStub(QueryInterface::class);
		$query->method('insert')->willReturnSelf();
		$query->method('columns')->willReturnSelf();
		$query->method('values')->willReturnSelf();
		$database = $this->database();
		$database->method('createQuery')->willReturn($query);
		$database->method('execute')->willReturn(false);
		$database->method('insertid')->willReturn(0);

		$this->assertFalse($this->subject($database)->defaults(false)->row(['name' => 'One'], 'records'));
	}

	/**
	 * Create a deterministic insert instance without Joomla application globals.
	 *
	 * @param   DatabaseInterface  $database  Database boundary.
	 *
	 * @return  Insert
	 * @since   6.1.6
	 */
	private function subject(DatabaseInterface $database): Insert
	{
		$reflection = new ReflectionClass(Insert::class);
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
