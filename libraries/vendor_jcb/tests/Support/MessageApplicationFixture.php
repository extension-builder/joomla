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

namespace VDM\Tests\Support;


/**
 * Minimal application fixture that records enqueued messages.
 *
 * @since  1.0.0
 */
final class MessageApplicationFixture
{
	/**
	 * Enqueued message records.
	 *
	 * @var    array<int, array{message: string, type: string}>
	 * @since  1.0.0
	 */
	public array $messages = [];

	/**
	 * Record an application message.
	 *
	 * @param   string  $message  Message text.
	 * @param   string  $type     Message type.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function enqueueMessage(string $message, string $type = 'message'): void
	{
		$this->messages[] = [
			'message' => $message,
			'type' => $type
		];
	}
}
