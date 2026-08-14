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
use VDM\Joomla\Import\Mapper;
use VDM\Joomla\Interfaces\TableInterface;
use VDM\Tests\Support\TestCase;


/**
 * Import-column mapper parsing, grouping, and state-reset tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Mapper::class)]
final class MapperTest extends TestCase
{
	/**
	 * Group valid parent, join, and two-value subform mappings by source column.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetBuildsParentAndJoinMapsAndSkipsInvalidRows(): void
	{
		$table = $this->createStub(TableInterface::class);
		$table->method('exist')->willReturnCallback(
			static fn (string $table, ?string $field = null): bool => in_array(
				$table . '.' . $field,
				['people.name', 'contacts.email', 'contacts.address'],
				true
			)
		);
		$table->method('get')->willReturnCallback(
			static fn (?string $table = null, ?string $field = null): array => [
				'table' => $table,
				'name' => $field,
				'type' => 'text'
			]
		);
		$map = (object) [
			(object) ['column' => 'A', 'target' => 'people.name'],
			(object) ['column' => 'B', 'target' => 'contacts.email'],
			(object) ['column' => 'C', 'target' => 'contacts.address|country|country_code|ZA'],
			(object) ['column' => 'D', 'target' => 'missing.field'],
			(object) ['column' => '', 'target' => 'people.name'],
			(object) ['column' => 'E', 'target' => 'malformed']
		];
		$subject = new Mapper($table);

		$subject->set($map, 'people');

		$this->assertSame(
			['A' => ['table' => 'people', 'name' => 'name', 'type' => 'text']],
			$subject->getParent()
		);
		$this->assertSame(['table' => 'contacts', 'name' => 'email', 'type' => 'text'], $subject->getJoin()['contacts']['B']);
		$this->assertSame('address', $subject->getJoin()['contacts']['C']['name']);
		$subform = $subject->getJoin()['contacts']['C']['subform_2'];
		$this->assertSame('contacts', $subform->table);
		$this->assertSame('address', $subform->field);
		$this->assertSame('country', $subform->value);
		$this->assertSame('country_code', $subform->column);
		$this->assertSame('ZA', $subform->column_value);
	}

	/**
	 * Reset both maps on every invocation rather than leaking a prior import schema.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetResetsPriorMappingsEvenWhenNewMapIsEmpty(): void
	{
		$table = $this->createStub(TableInterface::class);
		$table->method('exist')->willReturn(true);
		$table->method('get')->willReturn(['name' => 'title']);
		$subject = new Mapper($table);
		$subject->set((object) [(object) ['column' => 'A', 'target' => 'records.title']], 'records');

		$subject->set((object) [], 'records');

		$this->assertSame([], $subject->getParent());
		$this->assertSame([], $subject->getJoin());
	}
}
