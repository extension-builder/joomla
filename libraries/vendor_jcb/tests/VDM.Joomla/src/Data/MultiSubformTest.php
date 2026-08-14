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

namespace VDM\Joomla\Tests\Data;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use VDM\Joomla\Data\MultiSubform;
use VDM\Joomla\Interfaces\Data\SubformInterface;
use VDM\Tests\Support\TestCase;


/**
 * Multi-subform map validation, link resolution, and recursive orchestration tests.
 *
 * @since  6.1.6
 */
#[CoversClass(MultiSubform::class)]
final class MultiSubformTest extends TestCase
{
	/**
	 * Reject missing or malformed core maps before touching persistence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInvalidCoreMapsAreRejectedWithoutDelegation(): void
	{
		$subform = $this->createMock(SubformInterface::class);
		$subform->expects($this->never())->method('table');
		$subject = new MultiSubform($subform);

		$this->assertNull($subject->get([]));
		$this->assertNull($subject->get(['_core' => ['table' => 'records']]));
		$this->assertFalse($subject->set([], []));
		$this->assertFalse($subject->set([], ['_core' => ['table' => 'records']]));
	}

	/**
	 * Fetch core rows then resolve each nested link from its owning core row.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetResolvesRegularNestedSubformLinks(): void
	{
		$calls = [];
		$subform = $this->createMock(SubformInterface::class);
		$subform->expects($this->exactly(2))->method('table')->willReturnCallback(
			static function (string $table) use (&$calls, &$subform): SubformInterface
			{
				$calls[] = ['table', $table];
				return $subform;
			}
		);
		$subform->expects($this->exactly(2))->method('get')->willReturnCallback(
			static function (
				string $linkValue,
				string $linkKey,
				string $field,
				array $get
			) use (&$calls): array
			{
				$calls[] = ['get', $linkValue, $linkKey, $field, $get];
				return $field === 'rows'
					? [['guid' => 'parent-1', 'name' => 'Parent']]
					: [['guid' => 'contact-1', 'email' => 'one@example.test']];
			}
		);
		$map = [
			'_core' => [
				'table' => 'parents',
				'linkValue' => 'root',
				'linkKey' => 'owner',
				'field' => 'rows',
				'get' => ['guid', 'name']
			],
			'contacts' => [
				'table' => 'contacts',
				'linkValue' => 'parents:guid',
				'linkKey' => 'parent',
				'get' => ['guid', 'email']
			]
		];

		$this->assertSame(
			[[
				'guid' => 'parent-1',
				'name' => 'Parent',
				'contacts' => [['guid' => 'contact-1', 'email' => 'one@example.test']]
			]],
			(new MultiSubform($subform))->get($map)
		);
		$this->assertSame([
			['table', 'parents'],
			['get', 'root', 'owner', 'rows', ['guid', 'name']],
			['table', 'contacts'],
			['get', 'parent-1', 'parent', 'contacts', ['guid', 'email']]
		], $calls);
	}

	/**
	 * Persist core and nested sets, propagate link values, and aggregate failures.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetPersistsCoreAndNestedRowsAndReportsNestedFailure(): void
	{
		$calls = [];
		$subform = $this->createMock(SubformInterface::class);
		$subform->expects($this->exactly(2))->method('table')->willReturnCallback(
			static function (string $table) use (&$calls, &$subform): SubformInterface
			{
				$calls[] = ['table', $table];
				return $subform;
			}
		);
		$subform->expects($this->exactly(2))->method('set')->willReturnCallback(
			static function (
				mixed $items,
				string $indexKey,
				string $linkKey,
				string $linkValue
			) use (&$calls): bool
			{
				$calls[] = ['set', $items, $indexKey, $linkKey, $linkValue];
				return $linkKey !== 'parent';
			}
		);
		$rows = [[
			'guid' => 'parent-1',
			'name' => 'Parent',
			'contacts' => [['guid' => 'contact-1', 'email' => 'one@example.test']]
		]];
		$map = [
			'_core' => [
				'table' => 'parents',
				'indexKey' => 'guid',
				'linkKey' => 'owner',
				'linkValue' => 'root'
			],
			'contacts' => [
				'table' => 'contacts',
				'indexKey' => 'guid',
				'linkKey' => 'parent',
				'linkValue' => 'parents:guid'
			]
		];

		$this->assertFalse((new MultiSubform($subform))->set($rows, $map));
		$this->assertSame('parents', $calls[0][1]);
		$this->assertSame($rows, $calls[1][1]);
		$this->assertSame('root', $calls[1][4]);
		$this->assertSame('contacts', $calls[2][1]);
		$this->assertSame($rows[0]['contacts'], $calls[3][1]);
		$this->assertSame('parent-1', $calls[3][4]);
	}

	/**
	 * Accept a valid nested core set-map using the same set contract as the root.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testNestedCoreSetMapUsesSetValidationContract(): void
	{
		$subform = $this->createStub(SubformInterface::class);
		$subform->method('table')->willReturnSelf();
		$subform->method('set')->willReturn(true);
		$rows = [[
			'guid' => 'parent-1',
			'children' => [['guid' => 'child-1']]
		]];
		$map = [
			'_core' => [
				'table' => 'parents',
				'indexKey' => 'guid',
				'linkKey' => 'owner',
				'linkValue' => 'root'
			],
			'children' => [
				'linkValue' => 'parents:guid',
				'_core' => [
					'table' => 'children',
					'indexKey' => 'guid',
					'linkKey' => 'parent',
					'linkValue' => 'parents:guid'
				]
			]
		];

		$this->assertTrue((new MultiSubform($subform))->set($rows, $map));
	}
}
