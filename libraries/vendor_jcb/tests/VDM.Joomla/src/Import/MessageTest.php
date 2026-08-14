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
use VDM\Joomla\Import\Message;
use VDM\Tests\Support\TestCase;


/**
 * Import message-bus accumulation and lifecycle tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Message::class)]
final class MessageTest extends TestCase
{
	/**
	 * Accumulate each severity independently in insertion order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMessagesAccumulateFluentlyBySeverity(): void
	{
		$subject = new Message();

		$this->assertSame($subject, $subject->addSuccess('created'));
		$this->assertSame($subject, $subject->addInfo('normalized'));
		$this->assertSame($subject, $subject->addError('row 9 failed'));
		$this->assertSame($subject, $subject->addSuccess('updated'));
		$this->assertEquals(
			(object) [
				'message_success' => ['created', 'updated'],
				'message_info' => ['normalized'],
				'message_error' => ['row 9 failed']
			],
			$subject->get()
		);
	}

	/**
	 * Clear all severities without retaining references to the prior event.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResetStartsACompletelyEmptyImportEvent(): void
	{
		$subject = (new Message())->addSuccess('old')->addInfo('old')->addError('old');

		$subject->reset();

		$this->assertEquals(
			(object) [
				'message_success' => [],
				'message_info' => [],
				'message_error' => []
			],
			$subject->get()
		);
	}
}
