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

namespace VDM\Joomla\Tests\Import;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Import\JoinTables;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Interfaces\Import\MapperInterface;
use VDM\Joomla\Interfaces\Import\RowItemInterface;
use VDM\Joomla\Interfaces\Registryinterface;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Tests\Support\TestCase;


/**
 * Joined-import readiness, identity resolution, and persistence tests.
 *
 * @since  6.1.6
 */
#[CoversClass(JoinTables::class)]
#[UsesClass(GuidHelper::class)]
final class JoinTablesTest extends TestCase
{
	/**
	 * Consume mapped rows, skip incomplete keys, and select insert/update semantics.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetPersistsOnlyReadyJoinedItemsWithResolvedActions(): void
	{
		$mapper = $this->createStub(MapperInterface::class);
		$mapper->method('getJoin')->willReturn([
			'contacts' => ['A' => ['name' => 'email']],
			'phones' => ['B' => ['name' => 'number']],
			'ignored' => ['C' => ['name' => 'value']]
		]);
		$queues = [
			'contacts' => [
				['email' => '', 'name' => 'incomplete'],
				['email' => 'one@example.test', 'name' => 'One'],
				null
			],
			'phones' => [
				['number' => '+264-1'],
				null
			]
		];
		$importItem = $this->createStub(RowItemInterface::class);
		$importItem->method('get')->willReturnCallback(
			static function (string $table) use (&$queues): ?array
			{
				return array_shift($queues[$table]);
			}
		);
		$data = $this->createStub(Registryinterface::class);
		$data->method('get')->willReturn(73);
		$load = $this->createMock(LoadInterface::class);
		$load->expects($this->exactly(2))->method('value')->willReturnCallback(
			static fn (array $select, array $tables): ?string => $tables['a'] === 'contacts'
				? '2f89a8e2-37c9-4cc5-8d61-f8ac32a34012'
				: null
		);
		$saved = [];
		$item = $this->createMock(ItemInterface::class);
		$item->method('table')->willReturnSelf();
		$item->expects($this->exactly(2))->method('set')->willReturnCallback(
			static function (object $value, string $key, ?string $action) use (&$saved): bool
			{
				$saved[] = [(array) $value, $key, $action];
				return true;
			}
		);
		$subject = new JoinTables($mapper, $importItem, $data, $item, $load);

		$subject->set('person_id', 19, [
			'contacts' => ['link_fields' => ['email']],
			'phones' => ['link_fields' => ['number']]
		]);

		$this->assertCount(2, $saved);
		$this->assertSame('update', $saved[0][2]);
		$this->assertSame('guid', $saved[0][1]);
		$this->assertSame(19, $saved[0][0]['person_id']);
		$this->assertSame(73, $saved[0][0]['modified_by']);
		$this->assertSame('2f89a8e2-37c9-4cc5-8d61-f8ac32a34012', $saved[0][0]['guid']);
		$this->assertSame('insert', $saved[1][2]);
		$this->assertSame(73, $saved[1][0]['created_by']);
		$this->assertTrue(GuidHelper::valid($saved[1][0]['guid']));
	}

	/**
	 * Ignore mapped tables that have no reviewed link-field configuration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetDoesNotConsumeRowsForUnconfiguredJoinTable(): void
	{
		$mapper = $this->createStub(MapperInterface::class);
		$mapper->method('getJoin')->willReturn(['notes' => ['A' => ['name' => 'body']]]);
		$importItem = $this->createMock(RowItemInterface::class);
		$importItem->expects($this->never())->method('get');
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->never())->method('set');

		(new JoinTables(
			$mapper,
			$importItem,
			$this->createStub(Registryinterface::class),
			$item,
			$this->createStub(LoadInterface::class)
		))->set('parent_id', 1);

		$this->addToAssertionCount(1);
	}
}
