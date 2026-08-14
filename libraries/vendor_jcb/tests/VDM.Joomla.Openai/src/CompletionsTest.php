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
use VDM\Joomla\Openai\Completions;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI legacy completions endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(Completions::class)]
final class CompletionsTest extends OpenaiTestCase
{
	/**
	 * Map every public option to its exact API field without dropping false or zero.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateSendsCompleteCompletionPayload(): void
	{
		/** @var Completions $subject */
		[$subject, $transport] = $this->createEndpoint(Completions::class);

		$subject->create(
			'model-test',
			['first', 'second'],
			0,
			' suffix',
			0.0,
			0.9,
			2,
			false,
			0,
			false,
			['END'],
			-0.25,
			0.5,
			3,
			['42' => -100],
			'user-2'
		);

		$this->assertOpenaiRequest($transport, 'POST', '/completions');
		$this->assertSame([
			'model' => 'model-test',
			'prompt' => ['first', 'second'],
			'max_tokens' => 0,
			'temperature' => 0,
			'suffix' => ' suffix',
			'top_p' => 0.9,
			'n' => 2,
			'stream' => false,
			'logprobs' => 0,
			'echo' => false,
			'stop' => ['END'],
			'presence_penalty' => -0.25,
			'frequency_penalty' => 0.5,
			'best_of' => 3,
			'logit_bias' => ['42' => -100],
			'user' => 'user-2'
		], $this->jsonRequest($transport));
	}

	/**
	 * Omit all null options from the minimal request.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateOmitsNullOptionalFields(): void
	{
		/** @var Completions $subject */
		[$subject, $transport] = $this->createEndpoint(Completions::class);

		$subject->create('model-test', 'Prompt');

		$this->assertSame([
			'model' => 'model-test',
			'prompt' => 'Prompt'
		], $this->jsonRequest($transport));
	}
}
