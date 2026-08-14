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
use VDM\Joomla\Model\Load;
use VDM\Tests\Support\TestCase;


/**
 * Stored-value decoding and load-model validation tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Load::class)]
#[UsesClass(Model::class)]
final class LoadTest extends TestCase
{
	/**
	 * Decode base64 and JSON fields according to table metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValueDecodesConfiguredStorageFormats(): void
	{
		$subject = new Load($this->table(), 'records');

		$this->assertSame('JCB compiler', $subject->value(base64_encode('JCB compiler'), 'payload'));
		$this->assertEquals((object) ['enabled' => true], $subject->value('{"enabled":true}', 'settings'));
		$this->assertSame('plain', $subject->value('plain', 'name'));
	}

	/**
	 * Keep null untouched and avoid consulting storage metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValuePreservesNullWithoutMetadataLookup(): void
	{
		$table = $this->createMock(TableInterface::class);
		$table->expects($this->never())->method('get');

		$this->assertNull((new Load($table, 'records'))->value(null, 'payload'));
	}

	/**
	 * Decode stored fields through the inherited object and row pipelines.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowsAndItemsDecodeValuesAndDiscardUnknownFields(): void
	{
		$subject = new Load($this->table(), 'records', false);
		$row = $subject->row([
			'id' => 7,
			'payload' => base64_encode('content'),
			'settings' => '{"mode":"safe"}',
			'unknown' => 'discarded',
		]);
		$item = $subject->item((object) [
			'id' => 8,
			'name' => 'demo',
			'settings' => '{"mode":"fast"}',
		]);

		$this->assertSame('content', $row['payload']);
		$this->assertEquals((object) ['mode' => 'safe'], $row['settings']);
		$this->assertArrayNotHasKey('unknown', $row);
		$this->assertSame('demo', $item->name);
		$this->assertEquals((object) ['mode' => 'fast'], $item->settings);
	}

	/**
	 * Filter unsupported pre-decode values while permitting empty values only by policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValidationFiltersUnsupportedAndPolicyControlledEmptyValues(): void
	{
		$strict = new Load($this->table(), 'records', false);
		$permissive = new Load($this->table(), 'records', true);

		$this->assertNull($strict->values([[], false, ''], 'name'));
		$this->assertSame([[], false, ''], $permissive->values([[], false, ''], 'name'));
		$this->assertSame([0, 'valid'], $strict->values([0, 'valid', new \stdClass()], 'name'));
	}

	/**
	 * Build deterministic table metadata for load modelling.
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
