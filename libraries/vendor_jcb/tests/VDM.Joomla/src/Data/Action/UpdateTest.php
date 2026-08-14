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

namespace VDM\Joomla\Tests\Data\Action;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Data\Action\Update;
use VDM\Joomla\Interfaces\Database\UpdateInterface;
use VDM\Joomla\Interfaces\ModelInterface;
use VDM\Tests\Support\TestCase;


/**
 * Data update action modelling, key routing, and affected-ID tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Update::class)]
final class UpdateTest extends TestCase
{
	/**
	 * Preserve the active table when a null selection is supplied.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableIsFluentAndIgnoresNullSelection(): void
	{
		$subject = $this->subject(table: 'initial');

		$this->assertSame($subject, $subject->table('changed'));
		$this->assertSame($subject, $subject->table(null));
		$this->assertSame('changed', $subject->getTable());
	}

	/**
	 * Build a keyed row for scalar update and preserve a custom key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValueBuildsKeyedRowAndPreservesCustomKey(): void
	{
		$input = ['id' => '17', 'title' => 'Demo'];
		$modelled = ['id' => 17, 'title' => 'Demo'];
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())->method('row')->with($input, 'records')->willReturn($modelled);
		$database = $this->createMock(UpdateInterface::class);
		$database->expects($this->once())
			->method('row')
			->with($modelled, 'id', 'records')
			->willReturn(true);

		$this->assertTrue((new Update($model, $database, 'records'))->value('Demo', 'title', '17', 'id'));
	}

	/**
	 * Reject a row when the model cannot produce persistence data.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowReturnsFalseWithoutCallingDatabaseWhenModelRejectsInput(): void
	{
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())->method('row')->with(['title' => ''], 'records')->willReturn(null);
		$database = $this->createMock(UpdateInterface::class);
		$database->expects($this->never())->method('row');

		$this->assertFalse((new Update($model, $database, 'records'))->row(['title' => ''], 'id'));
	}

	/**
	 * Model and persist row, object, and collection shapes with their selected keys.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testShapeSpecificMethodsPersistModelledValuesAndKeys(): void
	{
		$rowInput = [['id' => 1, 'title' => 'one']];
		$rowOutput = [['id' => 1, 'title' => 'ONE']];
		$itemInput = (object) ['guid' => 'two'];
		$itemOutput = (object) ['guid' => 'TWO'];
		$itemsInput = [(object) ['uuid' => 'three']];
		$itemsOutput = [(object) ['uuid' => 'THREE']];
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())->method('rows')->with($rowInput, 'records')->willReturn($rowOutput);
		$model->expects($this->once())->method('item')->with($itemInput, 'records')->willReturn($itemOutput);
		$model->expects($this->once())->method('items')->with($itemsInput, 'records')->willReturn($itemsOutput);
		$database = $this->createMock(UpdateInterface::class);
		$database->expects($this->once())->method('rows')->with($rowOutput, 'id', 'records')->willReturn(true);
		$database->expects($this->once())->method('item')->with($itemOutput, 'guid', 'records')->willReturn(true);
		$database->expects($this->once())->method('items')->with($itemsOutput, 'uuid', 'records')->willReturn(false);
		$subject = new Update($model, $database, 'records');

		$this->assertTrue($subject->rows($rowInput, 'id'));
		$this->assertTrue($subject->item($itemInput));
		$this->assertFalse($subject->items($itemsInput, 'uuid'));
	}

	/**
	 * Return false for every collection shape rejected by the model.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCollectionsDoNotReachDatabaseWhenModelReturnsNull(): void
	{
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())->method('rows')->with(null, 'records')->willReturn(null);
		$model->expects($this->once())->method('items')->with(null, 'records')->willReturn(null);
		$database = $this->createMock(UpdateInterface::class);
		$database->expects($this->never())->method('rows');
		$database->expects($this->never())->method('items');
		$subject = new Update($model, $database, 'records');

		$this->assertFalse($subject->rows(null));
		$this->assertFalse($subject->items(null));
	}

	/**
	 * Forward the reset policy and exact affected identifier order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdateIdsDelegatesResetPolicy(): void
	{
		$database = $this->createMock(UpdateInterface::class);
		$database->expects($this->exactly(2))
			->method('updateids')
			->willReturnMap([[true, [9, 3]], [false, [9, 3]]]);
		$subject = $this->subject(database: $database, table: 'records');

		$this->assertSame([9, 3], $subject->updateids());
		$this->assertSame([9, 3], $subject->updateids(false));
	}

	/**
	 * Construct the action with defaults for collaborators not under assertion.
	 *
	 * @param   ModelInterface|null   $model     Model collaborator.
	 * @param   UpdateInterface|null  $database  Database collaborator.
	 * @param   string|null           $table     Active table.
	 *
	 * @return  Update
	 * @since   6.1.6
	 */
	private function subject(
		?ModelInterface $model = null,
		?UpdateInterface $database = null,
		?string $table = null
	): Update
	{
		return new Update(
			$model ?? $this->createStub(ModelInterface::class),
			$database ?? $this->createStub(UpdateInterface::class),
			$table
		);
	}
}
