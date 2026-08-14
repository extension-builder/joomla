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
use VDM\Joomla\Data\Item;
use VDM\Joomla\Interfaces\Data\DeleteInterface;
use VDM\Joomla\Interfaces\Data\InsertInterface;
use VDM\Joomla\Interfaces\Data\LoadInterface;
use VDM\Joomla\Interfaces\Data\UpdateInterface;
use VDM\Joomla\Interfaces\Database\LoadInterface as DatabaseLoadInterface;
use VDM\Tests\Support\TestCase;


/**
 * Single-item data facade routing, persistence, and affected-ID tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Item::class)]
final class ItemTest extends TestCase
{
	/**
	 * Route item and scalar reads through the selected table and key map.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetAndValueDelegateToLoadAction(): void
	{
		$expected = (object) ['guid' => 'abc', 'name' => 'Demo'];
		$load = $this->createMock(LoadInterface::class);
		$load->expects($this->exactly(2))->method('table')->with('power')->willReturnSelf();
		$load->expects($this->once())
			->method('item')
			->with(['guid' => 'abc'])
			->willReturn($expected);
		$load->expects($this->once())
			->method('value')
			->with(['id' => '7'], 'system_name')
			->willReturn('Demo');
		$subject = $this->subject(load: $load);

		$this->assertSame($subject, $subject->table('power'));
		$this->assertSame('power', $subject->getTable());
		$this->assertSame($expected, $subject->get('abc'));
		$this->assertSame('Demo', $subject->value('7', 'id', 'system_name'));
		$this->assertSame(0, $subject->id());
	}

	/**
	 * Route deletion through the selected table and reset affected-ID state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDeleteDelegatesKeyCondition(): void
	{
		$delete = $this->createMock(DeleteInterface::class);
		$delete->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$delete->expects($this->once())
			->method('items')
			->with(['guid' => 'abc'])
			->willReturn(true);
		$subject = $this->subject(delete: $delete);
		$subject->table('power');

		$this->assertTrue($subject->delete('abc'));
		$this->assertSame(0, $subject->id());
	}

	/**
	 * Execute an explicit insert and return the first inserted ID once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExplicitInsertTracksFirstAffectedId(): void
	{
		$item = (object) ['guid' => 'abc', 'name' => 'Demo'];
		$insert = $this->createMock(InsertInterface::class);
		$insert->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$insert->expects($this->once())->method('item')->with($item)->willReturn(true);
		$insert->expects($this->exactly(2))
			->method('insertids')
			->with(true)
			->willReturnOnConsecutiveCalls([12, 14], []);
		$subject = $this->subject(insert: $insert);
		$subject->table('power');

		$this->assertTrue($subject->set($item, action: 'insert'));
		$this->assertSame(12, $subject->id());
		$this->assertSame(0, $subject->id());
	}

	/**
	 * Detect an existing entity, update it by key, and expose its affected ID.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAutomaticActionUpdatesExistingEntity(): void
	{
		$item = (object) ['guid' => 'abc', 'name' => 'Changed'];
		$database = $this->createMock(DatabaseLoadInterface::class);
		$database->expects($this->once())
			->method('value')
			->with(['a.id' => 'id'], ['a' => 'power'], ['a.guid' => 'abc'])
			->willReturn(17);
		$update = $this->createMock(UpdateInterface::class);
		$update->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$update->expects($this->once())->method('item')->with($item, 'guid')->willReturn(true);
		$update->expects($this->once())->method('updateids')->with(true)->willReturn([17]);
		$subject = $this->subject(update: $update, database: $database);
		$subject->table('power');

		$this->assertTrue($subject->set($item));
		$this->assertSame(17, $subject->id());
	}

	/**
	 * Insert a new entity when the persistence lookup has no matching ID.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAutomaticActionInsertsMissingEntity(): void
	{
		$item = (object) ['guid' => 'new-guid'];
		$database = $this->createStub(DatabaseLoadInterface::class);
		$database->method('value')->willReturn(null);
		$insert = $this->createMock(InsertInterface::class);
		$insert->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$insert->expects($this->once())->method('item')->with($item)->willReturn(true);
		$subject = $this->subject(insert: $insert, database: $database);
		$subject->table('power');

		$this->assertTrue($subject->set($item));
	}

	/**
	 * Reject missing keys and unsupported explicit action names.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRejectsMissingKeyAndUnsupportedAction(): void
	{
		$subject = $this->subject();
		$subject->table('power');

		$this->assertFalse($subject->set((object) ['name' => 'No GUID']));
		$this->assertFalse($subject->set((object) ['guid' => 'abc'], action: 'merge'));
		$this->assertSame(0, $subject->id());
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
	 * @return  Item
	 * @since   6.1.6
	 */
	private function subject(
		?LoadInterface $load = null,
		?InsertInterface $insert = null,
		?UpdateInterface $update = null,
		?DeleteInterface $delete = null,
		?DatabaseLoadInterface $database = null
	): Item
	{
		return new Item(
			$load ?? $this->createStub(LoadInterface::class),
			$insert ?? $this->createStub(InsertInterface::class),
			$update ?? $this->createStub(UpdateInterface::class),
			$delete ?? $this->createStub(DeleteInterface::class),
			$database ?? $this->createStub(DatabaseLoadInterface::class)
		);
	}
}
