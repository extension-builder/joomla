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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use VDM\Joomla\Data\Guid;
use VDM\Joomla\Data\Subform;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Tests\Support\TestCase;


/**
 * Subform projection, normalization, purge, and persistence tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Subform::class)]
#[UsesTrait(Guid::class)]
#[UsesClass(GuidHelper::class)]
final class SubformTest extends TestCase
{
	/**
	 * Select the active child table fluently.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableSelectionIsFluent(): void
	{
		$subject = new Subform($this->createStub(ItemsInterface::class), 'initial');

		$this->assertSame($subject, $subject->table('contacts'));
		$this->assertSame('contacts', $subject->getTable());
	}

	/**
	 * Project mixed item shapes into the numbered subform field contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetProjectsOnlyRequestedKeysForMultipleRows(): void
	{
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->once())->method('table')->with('contacts')->willReturnSelf();
		$items->expects($this->once())->method('get')->with(['parent-guid'], 'parent')->willReturn([
			(object) ['guid' => 'a', 'name' => 'One', 'secret' => 'x'],
			['guid' => 'b', 'name' => 'Two', 'secret' => 'y']
		]);

		$this->assertSame(
			[
				'contact0' => ['guid' => 'a', 'name' => 'One'],
				'contact1' => ['guid' => 'b', 'name' => 'Two']
			],
			(new Subform($items, 'contacts'))->get('parent-guid', 'parent', 'contact', ['guid', 'name'])
		);
	}

	/**
	 * Return the first projected row for a single-value subform and preserve null loads.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetSupportsSingleProjectionAndNullResult(): void
	{
		$items = $this->createStub(ItemsInterface::class);
		$items->method('table')->willReturnSelf();
		$items->method('get')->willReturnOnConsecutiveCalls(
			[['name' => 'One', 'ignored' => true], ['name' => 'Two']],
			null
		);
		$subject = new Subform($items, 'contacts');

		$this->assertSame(['name' => 'One'], $subject->get('p', 'parent', 'contact', ['name'], false));
		$this->assertNull($subject->get('missing', 'parent', 'contact', ['name']));
	}

	/**
	 * Normalize a single row, add link/default ID, purge stale identities, and persist.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetNormalizesPurgesAndPersistsSingleRow(): void
	{
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->exactly(3))->method('table')->with('contacts')->willReturnSelf();
		$items->expects($this->once())->method('values')->with(['parent-guid'], 'parent', 'id')->willReturn([3, 7]);
		$items->expects($this->once())->method('delete')->with([3, 7], 'id')->willReturn(true);
		$items->expects($this->once())->method('set')->with(
			[['name' => 'One', 'id' => 0, 'parent' => 'parent-guid']],
			'id'
		)->willReturn(true);

		$this->assertTrue((new Subform($items, 'contacts'))->set(
			['name' => 'One'],
			'id',
			'parent',
			'parent-guid'
		));
	}

	/**
	 * Treat non-array input as an explicit clear and avoid an empty write.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetWithNonArrayPurgesAllAndDoesNotPersistEmptyBatch(): void
	{
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->exactly(2))->method('table')->with('contacts')->willReturnSelf();
		$items->expects($this->once())->method('values')->willReturn(['old-a', 'old-b']);
		$items->expects($this->once())->method('delete')->with(['old-a', 'old-b'], 'guid')->willReturn(true);
		$items->expects($this->never())->method('set');

		$this->assertTrue((new Subform($items, 'contacts'))->set(null, 'guid', 'parent', 'p'));
	}

	/**
	 * Generate a unique GUID for a new row before purge comparison and persistence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetGeneratesUniqueGuidForNewRows(): void
	{
		$generated = null;
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->exactly(3))->method('table')->with('contacts')->willReturnSelf();
		$items->expects($this->exactly(2))->method('values')->willReturnCallback(
			static function (array $values, string $key, string $get = 'id') use (&$generated): ?array
			{
				if ($values === ['parent-guid'])
				{
					return [];
				}

				$generated = $values[0];
				return null;
			}
		);
		$items->expects($this->never())->method('delete');
		$items->expects($this->once())->method('set')->with(
			$this->callback(static fn (array $rows): bool =>
				count($rows) === 1
				&& GuidHelper::valid($rows[0]['guid'])
				&& $rows[0]['parent'] === 'parent-guid'
			),
			'guid'
		)->willReturn(true);

		$this->assertTrue((new Subform($items, 'contacts'))->set(
			[['name' => 'New']],
			'guid',
			'parent',
			'parent-guid'
		));
		$this->assertTrue(GuidHelper::valid($generated));
	}
	/**
	 * Refuse to adopt a row that belongs to a different parent.
	 *
	 * The index value comes from the submitted subform, and the write is an
	 * UPDATE ... WHERE guid = <submitted>, so a guid copied from another
	 * parent would re-parent and overwrite that parent's row. Only an index
	 * value already linked to the saving parent may select an existing row.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSetTreatsAForeignIndexValueAsANewRow(): void
	{
		$items = $this->createMock(ItemsInterface::class);
		$items->method('table')->with('contacts')->willReturnSelf();
		$items->method('values')->willReturnCallback(
			static function (array $values, string $key, string $get = 'id'): ?array
			{
				// the ownership lookup: what this parent already links to
				if ($values === ['parent-guid'] && $key === 'parent')
				{
					return ['mine-1'];
				}

				// the guid uniqueness probe for a freshly generated value
				return null;
			}
		);
		$items->expects($this->once())
			->method('set')
			->with(
				$this->callback(
					static function (array $rows): bool
					{
						// the row this parent owns keeps its guid and is updated
						if ($rows[0]['guid'] !== 'mine-1' || $rows[0]['parent'] !== 'parent-guid')
						{
							return false;
						}

						// the stolen guid must have been replaced by a fresh one
						return $rows[1]['guid'] !== 'victim-guid'
							&& $rows[1]['guid'] !== ''
							&& $rows[1]['parent'] === 'parent-guid';
					}
				),
				'guid'
			)
			->willReturn(true);

		$this->assertTrue((new Subform($items, 'contacts'))->set(
			[
				['name' => 'Mine', 'guid' => 'mine-1'],
				['name' => 'Stolen', 'guid' => 'victim-guid'],
			],
			'guid',
			'parent',
			'parent-guid'
		));
	}
}
