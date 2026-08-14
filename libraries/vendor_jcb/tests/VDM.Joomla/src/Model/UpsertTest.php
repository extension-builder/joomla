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

namespace VDM\Joomla\Tests\Model;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Model;
use VDM\Joomla\Interfaces\TableInterface;
use VDM\Joomla\Model\Upsert;
use VDM\Tests\Support\TestCase;


/**
 * Persistence-value encoding and upsert-model validation tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Upsert::class)]
#[UsesClass(Model::class)]
final class UpsertTest extends TestCase
{
	/**
	 * Encode base64 and JSON fields according to table metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValueEncodesConfiguredStorageFormats(): void
	{
		$subject = new Upsert($this->table(), 'records');

		$this->assertSame(base64_encode('JCB compiler'), $subject->value('JCB compiler', 'payload'));
		$this->assertSame('{"enabled":true}', $subject->value(['enabled' => true], 'settings'));
		$this->assertSame('plain', $subject->value('plain', 'name'));
	}

	/**
	 * Encode configured object data as JSON object data rather than an array.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJsonStoragePreservesObjectShape(): void
	{
		$subject = new Upsert($this->table(), 'records');

		$this->assertSame('{"0":"first","1":"second"}', $subject->value(['first', 'second'], 'settings'));
		$this->assertSame('{"mode":"safe"}', $subject->value((object) ['mode' => 'safe'], 'settings'));
	}

	/**
	 * Encode stored fields through inherited object and row pipelines.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowsAndItemsEncodeValuesAndDiscardUnknownFields(): void
	{
		$subject = new Upsert($this->table(), 'records', false);
		$row = $subject->row([
			'id' => 7,
			'payload' => 'content',
			'settings' => ['mode' => 'safe'],
			'unknown' => 'discarded',
		]);
		$item = $subject->item((object) [
			'id' => 8,
			'name' => 'demo',
			'settings' => (object) ['mode' => 'fast'],
		]);

		$this->assertSame(base64_encode('content'), $row['payload']);
		$this->assertSame('{"mode":"safe"}', $row['settings']);
		$this->assertArrayNotHasKey('unknown', $row);
		$this->assertSame('demo', $item->name);
		$this->assertSame('{"mode":"fast"}', $item->settings);
	}

	/**
	 * Accept structured pre-encode input and enforce the empty-value policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValidationAcceptsStructuredValuesAndControlsEmptyValues(): void
	{
		$strict = new Upsert($this->table(), 'records', false);
		$permissive = new Upsert($this->table(), 'records', true);

		$this->assertSame(['{"mode":"safe"}'], $strict->values([['mode' => 'safe']], 'settings'));
		$this->assertNull($strict->values([null, false, ''], 'name'));
		$this->assertSame([null, false, ''], $permissive->values([null, false, ''], 'name'));
	}

	/**
	 * Build deterministic table metadata for upsert modelling.
	 *
	 * @return  TableInterface
	 * @since   6.1.6
	 */
	private function table(): TableInterface
	{
		$stores = ['payload' => 'base64', 'settings' => 'json'];
		$fields = ['id', 'name', 'payload', 'settings'];
		$table = $this->createStub(TableInterface::class);
		$table->method('get')->willReturnCallback(
			static fn (string $tableName, string $field, string $key): ?string =>
				$tableName === 'records' && $key === 'store' ? ($stores[$field] ?? null) : null
		);
		$table->method('exist')->willReturnCallback(
			static fn (string $tableName, ?string $field = null): bool =>
				$tableName === 'records' && ($field === null || in_array($field, $fields, true))
		);
		$table->method('fields')->willReturnCallback(
			static fn (string $tableName): ?array => $tableName === 'records' ? $fields : null
		);

		return $table;
	}
}
