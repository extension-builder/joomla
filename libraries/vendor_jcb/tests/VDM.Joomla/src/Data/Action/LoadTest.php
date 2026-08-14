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
use VDM\Joomla\Data\Action\Load;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Interfaces\ModelInterface;
use VDM\Tests\Support\TestCase;


/**
 * Data load action query construction and model pipeline tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Load::class)]
final class LoadTest extends TestCase
{
	/**
	 * Preserve the active table when a null selection is supplied.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableIsFluentAndIgnoresNullSelection(): void
	{
		$subject = new Load(
			$this->createStub(ModelInterface::class),
			$this->createStub(LoadInterface::class),
			'initial'
		);

		$this->assertSame($subject, $subject->table('changed'));
		$this->assertSame($subject, $subject->table(null));
		$this->assertSame('changed', $subject->getTable());
	}

	/**
	 * Build an aliased scalar query and model the loaded value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValuePrefixesConditionsAndModelsLoadedScalar(): void
	{
		$database = $this->createMock(LoadInterface::class);
		$database->expects($this->once())
			->method('value')
			->with(['a.name' => 'name'], ['a' => 'records'], ['a.guid' => 'abc', 'a.state' => 1])
			->willReturn('raw');
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())
			->method('value')
			->with('raw', 'name', 'records')
			->willReturn('modelled');

		$this->assertSame(
			'modelled',
			(new Load($model, $database, 'records'))->value(['guid' => 'abc', 'state' => 1], 'name')
		);
	}

	/**
	 * Build an aliased multi-value query and pass null through the model.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValuesPreservesNullPipelineResult(): void
	{
		$database = $this->createMock(LoadInterface::class);
		$database->expects($this->once())
			->method('values')
			->with(['a.name' => 'name'], ['a' => 'records'], ['a.published' => 1])
			->willReturn(null);
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())
			->method('values')
			->with(null, 'name', 'records')
			->willReturn(null);

		$this->assertNull((new Load($model, $database, 'records'))->values(['published' => 1], 'name'));
	}

	/**
	 * Load all aliased columns for one row and model the database object.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemLoadsAllColumnsAndModelsObject(): void
	{
		$raw = (object) ['guid' => 'abc'];
		$expected = (object) ['guid' => 'abc', 'clean' => true];
		$database = $this->createMock(LoadInterface::class);
		$database->expects($this->once())
			->method('item')
			->with(['all' => 'a.*'], ['a' => 'records'], ['a.guid' => 'abc'])
			->willReturn($raw);
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())
			->method('item')
			->with($raw, 'records')
			->willReturn($expected);

		$this->assertSame($expected, (new Load($model, $database, 'records'))->item(['guid' => 'abc']));
	}

	/**
	 * Load all aliased columns for many rows and model the result collection.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemsLoadsAllColumnsAndModelsCollection(): void
	{
		$raw = [(object) ['id' => 3], (object) ['id' => 7]];
		$expected = [(object) ['id' => 3, 'clean' => true]];
		$conditions = ['id' => ['operator' => 'IN', 'value' => [3, 7]]];
		$database = $this->createMock(LoadInterface::class);
		$database->expects($this->once())
			->method('items')
			->with(
				['all' => 'a.*'],
				['a' => 'records'],
				['a.id' => ['operator' => 'IN', 'value' => [3, 7]]]
			)
			->willReturn($raw);
		$model = $this->createMock(ModelInterface::class);
		$model->expects($this->once())
			->method('items')
			->with($raw, 'records')
			->willReturn($expected);

		$this->assertSame($expected, (new Load($model, $database, 'records'))->items($conditions));
		$this->assertSame(['id' => ['operator' => 'IN', 'value' => [3, 7]]], $conditions);
	}
}
