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
use ReflectionClass;
use VDM\Joomla\Data\UsersSubform;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Tests\Support\TestCase;


/**
 * User-subform projection and safe clear-operation tests.
 *
 * User-account persistence is deliberately kept behind the Joomla user boundary;
 * these tests isolate the deterministic subform behavior without writing users.
 *
 * @since  6.1.6
 */
#[CoversClass(UsersSubform::class)]
final class UsersSubformTest extends TestCase
{
	/**
	 * Select the active association table fluently.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableSelectionIsFluent(): void
	{
		$subject = $this->subject($this->createStub(ItemsInterface::class), 'initial');

		$this->assertSame($subject, $subject->table('members'));
		$this->assertSame('members', $subject->getTable());
	}

	/**
	 * Project association values while leaving invalid user IDs at the Joomla boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetProjectsRowsWithoutLoadingInvalidUsers(): void
	{
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->once())->method('table')->with('members')->willReturnSelf();
		$items->expects($this->once())->method('get')->with(['team-a'], 'team')->willReturn([
			['guid' => 'first', 'user_id' => 0, 'role' => 'lead', 'secret' => 'x'],
			(object) ['guid' => 'second', 'role' => 'member']
		]);
		$subject = $this->subject($items, 'members');

		$this->assertSame(
			[
				'member0' => ['guid' => 'first', 'user_id' => 0, 'role' => 'lead'],
				'member1' => ['guid' => 'second', 'role' => 'member']
			],
			$subject->get('team-a', 'team', 'member', ['guid', 'user_id', 'role'])
		);
	}

	/**
	 * Preserve a null association load rather than manufacturing an empty form.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetPreservesNullAssociationLoad(): void
	{
		$items = $this->createStub(ItemsInterface::class);
		$items->method('table')->willReturnSelf();
		$items->method('get')->willReturn(null);

		$this->assertNull($this->subject($items, 'members')->get('missing', 'team', 'member', ['guid']));
	}

	/**
	 * Clear every stale association for non-array input without invoking user persistence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetWithNonArrayPurgesAssociationsAndAvoidsEmptyWrite(): void
	{
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->exactly(2))->method('table')->with('members')->willReturnSelf();
		$items->expects($this->once())->method('values')->with(['team-a'], 'team', 'guid')->willReturn(['old']);
		$items->expects($this->once())->method('delete')->with(['old'], 'guid')->willReturn(true);
		$items->expects($this->never())->method('set');

		$this->assertTrue($this->subject($items, 'members')->set(null, 'guid', 'team', 'team-a'));
	}

	/**
	 * Construct without touching global Joomla user state and inject deterministic collaborators.
	 *
	 * @param   ItemsInterface  $items  Association persistence collaborator.
	 * @param   string          $table  Active association table.
	 *
	 * @return  UsersSubform
	 * @since   6.1.6
	 */
	private function subject(ItemsInterface $items, string $table): UsersSubform
	{
		$reflection = new ReflectionClass(UsersSubform::class);
		$subject = $reflection->newInstanceWithoutConstructor();
		$reflection->getProperty('items')->setValue($subject, $items);
		$reflection->getProperty('table')->setValue($subject, $table);
		$reflection->getProperty('user')->setValue($subject, []);
		$reflection->getProperty('activeUsers')->setValue($subject, []);

		return $subject;
	}
}
