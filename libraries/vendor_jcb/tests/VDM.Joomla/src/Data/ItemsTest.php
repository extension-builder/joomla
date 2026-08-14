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

namespace VDM\Joomla\Tests\Data;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Group;
use VDM\Joomla\Data\Guid;
use VDM\Joomla\Data\Items;
use VDM\Joomla\Interfaces\Data\DeleteInterface;
use VDM\Joomla\Interfaces\Data\InsertInterface;
use VDM\Joomla\Interfaces\Data\LoadInterface;
use VDM\Joomla\Interfaces\Data\UpdateInterface;
use VDM\Joomla\Interfaces\Database\LoadInterface as DatabaseLoadInterface;
use VDM\Tests\Support\TestCase;


/**
 * Multi-item data facade sorting, persistence, GUID, and affected-ID tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Items::class)]
#[CoversTrait(Guid::class)]
final class ItemsTest extends TestCase
{
	/**
	 * Route item and scalar collections through normalized IN conditions.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetAndValuesDelegateNormalizedInConditions(): void
	{
		$objects = [(object) ['guid' => 'a'], (object) ['guid' => 'b']];
		$load = $this->createMock(LoadInterface::class);
		$load->expects($this->exactly(2))->method('table')->with('power')->willReturnSelf();
		$load->expects($this->once())
			->method('items')
			->with(['guid' => ['operator' => 'IN', 'value' => ['a', 'b']]])
			->willReturn($objects);
		$load->expects($this->once())
			->method('values')
			->with(['id' => ['operator' => 'IN', 'value' => [7, 9]]], 'system_name')
			->willReturn(['Alpha', 'Omega']);
		$subject = $this->subject(load: $load);

		$this->assertSame($subject, $subject->table('power'));
		$this->assertSame('power', $subject->getTable());
		$this->assertSame($objects, $subject->get(['first' => 'a', 'second' => 'b']));
		$this->assertSame(
			['Alpha', 'Omega'],
			$subject->values(['first' => 7, 'second' => 9], 'id', 'system_name')
		);
	}

	/**
	 * Merge inserted and updated IDs uniquely while resetting both buckets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testIdsMergeBothActionBucketsUniquely(): void
	{
		$insert = $this->createMock(InsertInterface::class);
		$insert->expects($this->exactly(2))
			->method('insertids')
			->with(true)
			->willReturnOnConsecutiveCalls([4, 7], []);
		$update = $this->createMock(UpdateInterface::class);
		$update->expects($this->exactly(2))
			->method('updateids')
			->with(true)
			->willReturnOnConsecutiveCalls([7, 9], []);
		$subject = $this->subject(insert: $insert, update: $update);

		$this->assertSame([4, 7, 9], $subject->ids());
		$this->assertSame([], $subject->ids());
	}

	/**
	 * Split existing and new entities into update and insert batches.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetSortsExistingAndNewItemsByPersistenceLookup(): void
	{
		$items = [
			(object) ['guid' => 'existing', 'name' => 'Changed'],
			['guid' => 'new', 'name' => 'Created']
		];
		$database = $this->createMock(DatabaseLoadInterface::class);
		$database->expects($this->once())
			->method('values')
			->with(
				['a.guid' => 'guid'],
				['a' => 'power'],
				['a.guid' => ['operator' => 'IN', 'value' => ['existing', 'new']]]
			)
			->willReturn(['existing']);
		$insert = $this->createMock(InsertInterface::class);
		$insert->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$insert->expects($this->once())
			->method('rows')
			->with([['guid' => 'new', 'name' => 'Created']])
			->willReturn(true);
		$update = $this->createMock(UpdateInterface::class);
		$update->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$update->expects($this->once())
			->method('rows')
			->with([['guid' => 'existing', 'name' => 'Changed']], 'guid')
			->willReturn(true);
		$subject = $this->subject(insert: $insert, update: $update, database: $database);
		$subject->table('power');

		$this->assertTrue($subject->set($items));
	}

	/**
	 * Insert all entities when none of their supplied keys exist.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetInsertsAllWhenPersistenceLookupIsEmpty(): void
	{
		$database = $this->createStub(DatabaseLoadInterface::class);
		$database->method('values')->willReturn(null);
		$insert = $this->createMock(InsertInterface::class);
		$insert->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$insert->expects($this->once())
			->method('rows')
			->with(
				[
					['guid' => 'one', 'name' => 'One'],
					['guid' => 'two', 'name' => 'Two']
				]
			)
			->willReturn(true);
		$subject = $this->subject(insert: $insert, database: $database);
		$subject->table('power');

		$this->assertTrue(
			$subject->set(
				[
					['guid' => 'one', 'name' => 'One'],
					(object) ['guid' => 'two', 'name' => 'Two']
				]
			)
		);
	}

	/**
	 * Generate a valid RFC 4122 version-four GUID for an explicitly empty key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetGeneratesGuidForExplicitlyEmptyGuid(): void
	{
		$load = $this->createStub(LoadInterface::class);
		$load->method('table')->willReturnSelf();
		$load->method('values')->willReturn(null);
		$insert = $this->createMock(InsertInterface::class);
		$insert->method('table')->willReturnSelf();
		$insert->expects($this->once())
			->method('rows')
			->with(
				$this->callback(
					static fn (array $rows): bool =>
						isset($rows[0]['guid'])
						&& Items::validateGuid($rows[0]['guid']) === 1
						&& preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $rows[0]['guid']) === 1
				)
			)
			->willReturn(true);
		$subject = $this->subject(load: $load, insert: $insert);
		$subject->table('power');

		$this->assertTrue($subject->set([['guid' => '', 'name' => 'Generated']]));
	}

	/**
	 * Validate canonical, braced, and malformed GUID strings.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValidateGuidRecognizesCanonicalAndBalancedBracedForms(): void
	{
		$this->assertSame(1, Items::validateGuid('123e4567-e89b-42d3-a456-426614174000'));
		$this->assertSame(1, Items::validateGuid('{123e4567-e89b-42d3-a456-426614174000}'));
		$this->assertSame(0, Items::validateGuid('{123e4567-e89b-42d3-a456-426614174000'));
		$this->assertFalse(Items::validateGuid(''));
		$this->assertFalse(Items::validateGuid(null));
	}

	/**
	 * Route deletion through a stable IN condition without reindexing values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDeleteDelegatesInCondition(): void
	{
		$delete = $this->createMock(DeleteInterface::class);
		$delete->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$delete->expects($this->once())
			->method('items')
			->with(['guid' => ['operator' => 'IN', 'value' => ['a', 'b']]])
			->willReturn(true);
		$subject = $this->subject(delete: $delete);
		$subject->table('power');

		$this->assertTrue($subject->delete(['a', 'b']));
	}

	/**
	 * Reject an empty persistence batch without calling an insert action.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testSetRejectsEmptyBatch(): void
	{
		$insert = $this->createMock(InsertInterface::class);
		$insert->expects($this->never())->method('rows');
		$subject = $this->subject(insert: $insert);
		$subject->table('power');

		$this->assertFalse($subject->set([]));
	}

	/**
	 * Construct the facade with defaults for collaborators not under assertion.
	 *
	 * @param   LoadInterface|null          $load      Load action.
	 * @param   InsertInterface|null        $insert    Insert action.
	 * @param   UpdateInterface|null        $update    Update action.
	 * @param   DeleteInterface|null        $delete    Delete action.
	 * @param   DatabaseLoadInterface|null  $database  Persistence lookup.
	 *
	 * @return  Items
	 * @since   6.1.6
	 */
	private function subject(
		?LoadInterface $load = null,
		?InsertInterface $insert = null,
		?UpdateInterface $update = null,
		?DeleteInterface $delete = null,
		?DatabaseLoadInterface $database = null
	): Items
	{
		return new Items(
			$load ?? $this->createStub(LoadInterface::class),
			$insert ?? $this->createStub(InsertInterface::class),
			$update ?? $this->createStub(UpdateInterface::class),
			$delete ?? $this->createStub(DeleteInterface::class),
			$database ?? $this->createStub(DatabaseLoadInterface::class)
		);
	}
}
