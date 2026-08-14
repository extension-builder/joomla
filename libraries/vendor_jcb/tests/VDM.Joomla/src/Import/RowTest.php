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


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Import\Row;
use VDM\Tests\Support\TestCase;


/**
 * Import-row state machine and value-consumption tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Row::class)]
final class RowTest extends TestCase
{
	/**
	 * Reject index and value access until a row is installed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUninitializedRowRejectsAccess(): void
	{
		$subject = new Row();

		try
		{
			$subject->getIndex();
			$this->fail('Reading an uninitialized index must fail.');
		}
		catch (InvalidArgumentException $error)
		{
			$this->assertStringContainsString('Use the set method', $error->getMessage());
		}

		$this->expectException(InvalidArgumentException::class);
		$subject->getValue('title');
	}

	/**
	 * Expose installed values, return null for absent keys, and consume selected values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetAndUnsetManageOneRowDeterministically(): void
	{
		$subject = new Row();
		$subject->set(12, ['title' => 'Example', 'state' => 1]);

		$this->assertSame(12, $subject->getIndex());
		$this->assertSame('Example', $subject->getValue('title'));
		$this->assertNull($subject->getValue('missing'));

		$subject->unsetValue('title');
		$this->assertNull($subject->getValue('title'));
		$this->assertSame(1, $subject->getValue('state'));
	}

	/**
	 * Clear invalidates both index and values and remains fluent.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testClearInvalidatesPreviouslyInstalledState(): void
	{
		$subject = new Row();
		$subject->set(3, ['title' => 'old']);

		$this->assertSame($subject, $subject->clear());
		$this->expectException(InvalidArgumentException::class);
		$subject->unsetValue('title');
	}
}
