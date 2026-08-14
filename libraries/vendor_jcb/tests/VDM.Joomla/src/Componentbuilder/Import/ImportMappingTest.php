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

namespace VDM\Joomla\Tests\Componentbuilder\Import;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use VDM\Joomla\Componentbuilder\Import\Assessor;
use VDM\Joomla\Componentbuilder\Import\Item;
use VDM\Joomla\Componentbuilder\Import\Status;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\Import\MessageInterface;
use VDM\Joomla\Interfaces\Import\RowInterface;
use VDM\Joomla\Interfaces\TableValidatorInterface;
use VDM\Tests\Support\TestCase;


/**
 * Import row mapping, status persistence, and result assessment contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Item::class)]
#[CoversClass(Status::class)]
#[CoversClass(Assessor::class)]
final class ImportMappingTest extends TestCase
{
	/**
	 * Validate scalar columns, build indexed subforms, and consume row values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemMapsValidatedColumnsAndLevelTwoSubforms(): void
	{
		$validator = $this->createMock(TableValidatorInterface::class);
		$validator->expects($this->once())
			->method('getValid')
			->with('Alpha', 'title', 'article')
			->willReturn('Validated Alpha');
		$row = new class implements RowInterface
		{
			/**
			 * Current row values.
			 *
			 * @var    array<string, mixed>
			 * @since  6.1.6
			 */
			public array $values = ['A' => 'Alpha', 'B' => 'red', 'C' => 'blue', 'D' => null];

			/**
			 * Removed row keys.
			 *
			 * @var    array<int, string>
			 * @since  6.1.6
			 */
			public array $removed = [];

			/**
			 * {@inheritDoc}
			 */
			public function set(int $index, array $values): void
			{
			}

			/**
			 * {@inheritDoc}
			 */
			public function clear(): self
			{
				return $this;
			}

			/**
			 * {@inheritDoc}
			 */
			public function getIndex(): int
			{
				return 1;
			}

			/**
			 * {@inheritDoc}
			 */
			public function getValue(string $key)
			{
				return $this->values[$key] ?? null;
			}

			/**
			 * {@inheritDoc}
			 */
			public function unsetValue(string $key): void
			{
				$this->removed[] = $key;
				unset($this->values[$key]);
			}
		};
		$subform = static fn(string $columnValue): object => (object) [
			'field' => 'option',
			'column' => 'name',
			'column_value' => $columnValue,
			'value' => 'value',
		];
		$subject = new Item($validator, $this->createStub(ItemInterface::class), $row);

		$result = $subject->get('article', [
			'A' => ['name' => 'title'],
			'B' => ['name' => 'properties', 'subform_2' => $subform('foreground')],
			'C' => ['name' => 'properties', 'subform_2' => $subform('background')],
			'D' => ['name' => 'ignored'],
		]);

		$this->assertSame('Validated Alpha', $result['title']);
		$this->assertEquals(
			(object) [
				'option0' => (object) ['name' => 'foreground', 'value' => 'red'],
				'option1' => (object) ['name' => 'background', 'value' => 'blue'],
			],
			$result['properties']
		);
		$this->assertSame(['A', 'B', 'C'], $row->removed);
		$this->assertSame(['D' => null], $row->values);
	}

	/**
	 * Resolve a human linked value to the local key before field validation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemResolvesLinkedDisplayValueToLocalIdentifier(): void
	{
		$validator = $this->createMock(TableValidatorInterface::class);
		$validator->expects($this->once())->method('getValid')->with(7, 'category', 'article')->willReturn(7);
		$data = $this->createMock(ItemInterface::class);
		$data->expects($this->once())->method('table')->with('category')->willReturnSelf();
		$data->expects($this->once())->method('value')->with('Books', 'name', 'id')->willReturn(7);
		$row = $this->createMock(RowInterface::class);
		$row->expects($this->once())->method('getValue')->with('A')->willReturn('Books');
		$row->expects($this->once())->method('unsetValue')->with('A');
		$subject = new Item($validator, $data, $row);

		$result = $subject->get('article', [
			'A' => [
				'name' => 'category',
				'link' => ['type' => 1, 'table' => 'category', 'key' => 'id', 'value' => 'name'],
			],
		]);

		$this->assertSame(['category' => 7], $result);
	}

	/**
	 * Persist status through the selected table, field, value, and key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStatusUsesFluentTableAndFieldForPersistence(): void
	{
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->once())->method('table')->with('import_queue')->willReturnSelf();
		$item->expects($this->once())->method('set')->with((object) [
			'uuid' => 'queue-guid',
			'import_status' => 3,
		])->willReturn(true);
		$subject = new Status($item);

		$this->assertSame($subject, $subject->table('import_queue'));
		$this->assertSame($subject, $subject->field('import_status'));
		$this->assertSame('import_queue', $subject->getTable());
		$this->assertSame('import_status', $subject->getField());
		$subject->set(3, 'queue-guid', 'uuid');
	}

	/**
	 * Constructor arguments must initialize both optional selectors.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	#[IgnoreDeprecations]
	public function testStatusConstructorInitializesTheStatusField(): void
	{
		$subject = new Status($this->createStub(ItemInterface::class), 'import_queue', 'import_status');

		$this->assertSame('import_queue', $subject->getTable());

		try
		{
			$field = $subject->getField();
		}
		catch (\Throwable $error)
		{
			$this->fail(
				'The constructor field selector should be readable, not raise '
				. $error::class . ': ' . $error->getMessage()
			);
		}

		$this->assertSame('import_status', $field);
	}

	/**
	 * Apply the exact eighty-percent success threshold and empty-run policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAssessorRoutesSuccessFailureAndEmptyRuns(): void
	{
		$success = $this->createMock(MessageInterface::class);
		$success->expects($this->once())
			->method('addSuccess')
			->with('COM_COMPONENTBUILDER_D_ROWS_PROCESSED_SUCCESS_RATE_TWOF_IMPORT_SUCCESSFUL')
			->willReturnSelf();
		$success->expects($this->never())->method('addError');
		(new Assessor($success))->evaluate(10, 8, 2);

		$failure = $this->createMock(MessageInterface::class);
		$failure->expects($this->never())->method('addSuccess');
		$failure->expects($this->once())
			->method('addError')
			->with('COM_COMPONENTBUILDER_IMPORT_FAILED_D_ROWS_PROCESSED_WITH_ONLY_D_SUCCESSES_ERROR_RATE_TWOF')
			->willReturnSelf();
		(new Assessor($failure))->evaluate(10, 7, 3);

		$empty = $this->createMock(MessageInterface::class);
		$empty->expects($this->once())->method('addError')->with('COM_COMPONENTBUILDER_NO_ROWS_WERE_PROCESSED')->willReturnSelf();
		(new Assessor($empty))->evaluate(0, 0, 0);
	}
}
