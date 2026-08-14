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
use VDM\Joomla\Data\Action\Insert;
use VDM\Joomla\Interfaces\Database\InsertInterface;
use VDM\Joomla\Interfaces\ModelInterface;
use VDM\Tests\Support\TestCase;


/**
 * Data insert action modelling, persistence, and affected-ID tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Insert::class)]
final class InsertTest extends TestCase
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
	 * Build a keyed row for scalar insertion before modelling and persistence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValueBuildsKeyedRowAndUsesSelectedTable(): void
	{
		$input = ['id' => '17', 'title' => 'Demo'];
		$modelled = ['id' => 17, 'title' => 'Demo'];
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())->method('row')->with($input, 'records')->willReturn($modelled);
		$database = $this->createMock(InsertInterface::class);
		$database->expects($this->once())->method('row')->with($modelled, 'records')->willReturn(true);

		$this->assertTrue((new Insert($model, $database, 'records'))->value('Demo', 'title', '17', 'id'));
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
		$database = $this->createMock(InsertInterface::class);
		$database->expects($this->never())->method('row');

		$this->assertFalse((new Insert($model, $database, 'records'))->row(['title' => '']));
	}

	/**
	 * Model and persist row, object, and collection shapes through their matching methods.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testShapeSpecificMethodsPersistModelledValues(): void
	{
		$rowInput = [['title' => 'one']];
		$rowOutput = [['title' => 'ONE']];
		$itemInput = (object) ['title' => 'two'];
		$itemOutput = (object) ['title' => 'TWO'];
		$itemsInput = [(object) ['title' => 'three']];
		$itemsOutput = [(object) ['title' => 'THREE']];
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())->method('rows')->with($rowInput, 'records')->willReturn($rowOutput);
		$model->expects($this->once())->method('item')->with($itemInput, 'records')->willReturn($itemOutput);
		$model->expects($this->once())->method('items')->with($itemsInput, 'records')->willReturn($itemsOutput);
		$database = $this->createMock(InsertInterface::class);
		$database->expects($this->once())->method('rows')->with($rowOutput, 'records')->willReturn(true);
		$database->expects($this->once())->method('item')->with($itemOutput, 'records')->willReturn(true);
		$database->expects($this->once())->method('items')->with($itemsOutput, 'records')->willReturn(false);
		$subject = new Insert($model, $database, 'records');

		$this->assertTrue($subject->rows($rowInput));
		$this->assertTrue($subject->item($itemInput));
		$this->assertFalse($subject->items($itemsInput));
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
		$database = $this->createMock(InsertInterface::class);
		$database->expects($this->never())->method('rows');
		$database->expects($this->never())->method('items');
		$subject = new Insert($model, $database, 'records');

		$this->assertFalse($subject->rows(null));
		$this->assertFalse($subject->items(null));
	}

	/**
	 * Forward the reset policy and exact generated identifier order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInsertIdsDelegatesResetPolicy(): void
	{
		$database = $this->createMock(InsertInterface::class);
		$database->expects($this->exactly(2))
			->method('insertids')
			->willReturnMap([[true, [4, 5]], [false, [4, 5]]]);
		$subject = $this->subject(database: $database, table: 'records');

		$this->assertSame([4, 5], $subject->insertids());
		$this->assertSame([4, 5], $subject->insertids(false));
	}

	/**
	 * Construct the action with defaults for collaborators not under assertion.
	 *
	 * @param   ModelInterface|null   $model     Model collaborator.
	 * @param   InsertInterface|null  $database  Database collaborator.
	 * @param   string|null           $table     Active table.
	 *
	 * @return  Insert
	 * @since   6.1.6
	 */
	private function subject(
		?ModelInterface $model = null,
		?InsertInterface $database = null,
		?string $table = null
	): Insert
	{
		return new Insert(
			$model ?? $this->createStub(ModelInterface::class),
			$database ?? $this->createStub(InsertInterface::class),
			$table
		);
	}
}
