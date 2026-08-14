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


use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use UnexpectedValueException;
use VDM\Joomla\Import\ParentTable;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Interfaces\Import\MapperInterface;
use VDM\Joomla\Interfaces\Import\RowInterface;
use VDM\Joomla\Interfaces\Import\RowItemInterface;
use VDM\Joomla\Interfaces\Registryinterface;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Tests\Support\TestCase;


/**
 * Parent import validation, upsert selection, and identity tests.
 *
 * @since  6.1.6
 */
#[CoversClass(ParentTable::class)]
#[UsesClass(GuidHelper::class)]
final class ParentTableTest extends TestCase
{
	/**
	 * Reject a row that cannot supply its required parent link value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRejectsMissingParentLinkWithRowContext(): void
	{
		$row = $this->createStub(RowInterface::class);
		$row->method('getIndex')->willReturn(14);
		$importItem = $this->createStub(RowItemInterface::class);
		$importItem->method('get')->willReturn(['name' => 'No email']);
		$subject = $this->subject(row: $row, importItem: $importItem);

		$this->expectException(UnexpectedValueException::class);
		$this->expectExceptionMessage('Row 14 is missing required parent key "people:email".');

		$subject->set('email', 'guid', 'people');
	}

	/**
	 * Update a matching parent and preserve the resolved GUID.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExistingParentUsesUpdateAndDefaultModifier(): void
	{
		$guid = 'a2246e21-0e70-4137-bfa4-31cb22e62a9d';
		$importItem = $this->createMock(RowItemInterface::class);
		$importItem->expects($this->once())->method('get')->with('people', ['A' => ['name' => 'email']])
			->willReturn(['email' => 'one@example.test', 'name' => 'One']);
		$load = $this->createMock(LoadInterface::class);
		$load->expects($this->once())->method('value')->with(
			['a.guid' => 'guid'],
			['a' => 'people'],
			['a.email' => 'one@example.test']
		)->willReturn($guid);
		$data = $this->createStub(Registryinterface::class);
		$data->method('get')->willReturn(81);
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->once())->method('table')->with('people')->willReturnSelf();
		$item->expects($this->once())->method('set')->with(
			$this->callback(static fn (object $value): bool =>
				$value->guid === $guid && $value->modified_by === 81 && $value->email === 'one@example.test'
			),
			'guid',
			'update'
		)->willReturn(true);

		$this->assertSame($guid, $this->subject(
			importItem: $importItem,
			data: $data,
			item: $item,
			load: $load
		)->set('email', 'guid', 'people'));
	}

	/**
	 * Insert an ID-keyed parent with defaults and return the generated identifier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNewIdParentReturnsGeneratedId(): void
	{
		$importItem = $this->createStub(RowItemInterface::class);
		$importItem->method('get')->willReturn(['code' => 'P-1', 'name' => 'Parent']);
		$load = $this->createStub(LoadInterface::class);
		$load->method('value')->willReturn(null);
		$data = $this->createStub(Registryinterface::class);
		$data->method('get')->willReturn(17);
		$item = $this->createMock(ItemInterface::class);
		$item->method('table')->willReturnSelf();
		$item->expects($this->once())->method('set')->with(
			$this->callback(static fn (object $value): bool =>
				$value->id === 0 && $value->access === 1 && $value->created_by === 17 && $value->code === 'P-1'
			),
			'id'
		)->willReturn(true);
		$item->expects($this->once())->method('id')->willReturn(29);

		$this->assertSame(29, $this->subject(
			importItem: $importItem,
			data: $data,
			item: $item,
			load: $load
		)->set('code', 'id', 'parents'));
	}

	/**
	 * Reject a parent identity that is empty after lookup and processing.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUnsupportedEmptyParentIdentityIsRejected(): void
	{
		$importItem = $this->createStub(RowItemInterface::class);
		$importItem->method('get')->willReturn(['email' => 'one@example.test']);
		$load = $this->createStub(LoadInterface::class);
		$load->method('value')->willReturn(null);

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('resolved an invalid parent value for "people:slug"');

		$this->subject(importItem: $importItem, load: $load)->set('email', 'slug', 'people');
	}

	/**
	 * Build the parent-table orchestrator with inert defaults.
	 *
	 * @return  ParentTable
	 * @since   6.1.6
	 */
	private function subject(
		?RowInterface $row = null,
		?RowItemInterface $importItem = null,
		?Registryinterface $data = null,
		?ItemInterface $item = null,
		?LoadInterface $load = null
	): ParentTable
	{
		$mapper = $this->createStub(MapperInterface::class);
		$mapper->method('getParent')->willReturn(['A' => ['name' => 'email']]);

		return new ParentTable(
			$row ?? $this->createStub(RowInterface::class),
			$importItem ?? $this->createStub(RowItemInterface::class),
			$mapper,
			$data ?? $this->createStub(Registryinterface::class),
			$item ?? $this->createStub(ItemInterface::class),
			$load ?? $this->createStub(LoadInterface::class)
		);
	}
}
