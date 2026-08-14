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

namespace VDM\Joomla\Tests\Abstraction;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Model;
use VDM\Joomla\Interfaces\TableInterface;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Tests\Support\ModelFixture;
use VDM\Tests\Support\TestCase;


/**
 * Base model normalization, filtering, state, and last-ID tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Model::class)]
#[UsesClass(ArrayHelper::class)]
final class ModelTest extends TestCase
{
	/**
	 * Apply constructor policy and preserve fluent table switching.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableAndEmptyPolicyStateAreMutable(): void
	{
		$subject = new ModelFixture($this->table(), 'power', false);

		$this->assertSame('power', $subject->activeTable());
		$this->assertFalse($subject->allowsEmpty());
		$this->assertSame($subject, $subject->table('repository'));
		$this->assertSame('repository', $subject->activeTable());
		$subject->setAllowEmpty(true);
		$this->assertTrue($subject->allowsEmpty());
	}

	/**
	 * Normalize value lists while filtering both validation boundaries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValuesNormalizeAndFilterEntries(): void
	{
		$subject = new ModelFixture($this->table(), 'power', false);

		$this->assertSame(
			['ALPHA', 'BETA'],
			$subject->values([' alpha ', '', 'before-reject', 'after-reject', 'beta'], 'name')
		);
		$this->assertNull($subject->values([], 'name'));
		$this->assertNull($subject->values(['alpha'], 'missing'));
		$this->assertNull($subject->values(['', 'before-reject'], 'name'));
	}

	/**
	 * Model object fields, ignore unknown values, and require more than one field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemModelsKnownFieldsAndRequiresPayloadBeyondKey(): void
	{
		$subject = new ModelFixture($this->table(), 'power', false);

		$this->assertEquals(
			(object) ['id' => 7, 'name' => 'DEMO', 'status' => 'PUBLISHED'],
			$subject->item(
				(object) [
					'id' => 7,
					'name' => ' demo ',
					'status' => 'published',
					'unknown' => 'discarded'
				]
			)
		);
		$this->assertNull($subject->item((object) ['id' => 7]));
		$this->assertNull($subject->item(null));
	}

	/**
	 * Remove invalid objects and preserve the last successfully modeled ID.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemsFilterInvalidEntriesAndTrackLastId(): void
	{
		$subject = new ModelFixture($this->table(), 'power', false);
		$result = $subject->items(
			[
				'first' => (object) ['id' => 5, 'name' => 'first'],
				'invalid' => (object) ['id' => 6],
				'last' => (object) ['id' => 9, 'name' => 'last']
			]
		);

		$this->assertSame(['first', 'last'], array_keys($result));
		$this->assertSame('FIRST', $result['first']->name);
		$this->assertSame('LAST', $result['last']->name);
		$this->assertSame(9, $subject->last());
		$this->assertNull($subject->last('repository'));
	}

	/**
	 * Model array rows and track IDs across a filtered collection.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowsMirrorObjectModelingContract(): void
	{
		$subject = new ModelFixture($this->table(), 'power', false);

		$this->assertSame(
			['id' => 3, 'name' => 'DEMO', 'status' => 'PUBLISHED'],
			$subject->row(['id' => 3, 'name' => ' demo ', 'status' => 'published', 'extra' => true])
		);
		$this->assertNull($subject->row(['id' => 3]));
		$result = $subject->rows(
			[
				['id' => 4, 'name' => 'alpha'],
				['id' => 5],
				['id' => 8, 'name' => 'omega']
			]
		);

		$this->assertSame([0, 2], array_keys($result));
		$this->assertSame('OMEGA', $result[2]['name']);
		$this->assertSame(8, $subject->last());
		$this->assertNull($subject->rows([]));
	}

	/**
	 * Build a table metadata double for the modeled entity.
	 *
	 * @return  TableInterface
	 * @since   6.1.6
	 */
	private function table(): TableInterface
	{
		$table = $this->createStub(TableInterface::class);
		$table->method('exist')->willReturnCallback(
			static fn (string $tableName, ?string $field = null): bool =>
				$tableName === 'power' && in_array($field, ['id', 'name', 'status'], true)
		);
		$table->method('fields')->willReturnCallback(
			static fn (string $tableName): ?array =>
				$tableName === 'power' ? ['id', 'name', 'status'] : null
		);

		return $table;
	}
}
