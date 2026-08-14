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

namespace VDM\Joomla\Tests\Data\Action;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Data\Action\Delete;
use VDM\Joomla\Interfaces\Database\DeleteInterface;
use VDM\Tests\Support\TestCase;


/**
 * Data delete action table routing and database delegation tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Delete::class)]
final class DeleteTest extends TestCase
{
	/**
	 * Preserve the active table when a null selection is supplied.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableIsFluentAndIgnoresNullSelection(): void
	{
		$subject = new Delete($this->createStub(DeleteInterface::class), 'initial');

		$this->assertSame($subject, $subject->table('changed'));
		$this->assertSame($subject, $subject->table(null));
		$this->assertSame('changed', $subject->getTable());
	}

	/**
	 * Forward deletion conditions unchanged to the selected table.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemsDelegatesConditionsAndReturnsDatabaseResult(): void
	{
		$conditions = [
			'guid' => ['operator' => 'IN', 'value' => ['one', 'two']],
			'published' => 0,
		];
		$database = $this->createMock(DeleteInterface::class);
		$database->expects($this->once())
			->method('items')
			->with($conditions, 'records')
			->willReturn(true);

		$this->assertTrue((new Delete($database, 'records'))->items($conditions));
	}

	/**
	 * Forward truncation to the selected table exactly once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTruncateDelegatesSelectedTable(): void
	{
		$database = $this->createMock(DeleteInterface::class);
		$database->expects($this->once())->method('truncate')->with('records');

		(new Delete($database, 'records'))->truncate();
		$this->addToAssertionCount(1);
	}
}
