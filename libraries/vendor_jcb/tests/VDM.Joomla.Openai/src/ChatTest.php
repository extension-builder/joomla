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

namespace VDM\Joomla\Openai\Tests;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Openai\Chat;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI chat endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(Chat::class)]
final class ChatTest extends OpenaiTestCase
{
	/**
	 * Preserve false, zero, arrays, and dynamic token keys in a complete request.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateSendsCompleteChatPayload(): void
	{
		$messages = [
			['role' => 'system', 'content' => 'Be exact.'],
			['role' => 'user', 'content' => 'Answer.']
		];
		/** @var Chat $subject */
		[$subject, $transport] = $this->createEndpoint(
			Chat::class,
			[[200, '{"id":"chat-1"}']]
		);

		$result = $subject->create(
			'gpt-test',
			$messages,
			0,
			0.0,
			0.95,
			2,
			false,
			['END', 'STOP'],
			-0.5,
			0.25,
			['123' => -100, '456' => 4],
			'user-1'
		);

		$this->assertSame('chat-1', $result?->id);
		$this->assertOpenaiRequest($transport, 'POST', '/chat/completions');
		$this->assertSame([
			'model' => 'gpt-test',
			'messages' => $messages,
			'max_tokens' => 0,
			'temperature' => 0,
			'top_p' => 0.95,
			'n' => 2,
			'stream' => false,
			'stop' => ['END', 'STOP'],
			'presence_penalty' => -0.5,
			'frequency_penalty' => 0.25,
			'logit_bias' => ['123' => -100, '456' => 4],
			'user' => 'user-1'
		], $this->jsonRequest($transport));
	}

	/**
	 * Keep the minimal request limited to mandatory model and messages fields.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateOmitsNullOptionalFields(): void
	{
		$messages = [['role' => 'user', 'content' => 'Hello']];
		/** @var Chat $subject */
		[$subject, $transport] = $this->createEndpoint(Chat::class);

		$subject->create('gpt-test', $messages);

		$this->assertSame([
			'model' => 'gpt-test',
			'messages' => $messages
		], $this->jsonRequest($transport));
	}
}
