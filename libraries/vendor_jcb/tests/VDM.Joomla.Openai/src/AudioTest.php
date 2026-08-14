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
use VDM\Joomla\Openai\Audio;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI audio endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(Audio::class)]
final class AudioTest extends OpenaiTestCase
{
	/**
	 * Send all transcription options with the endpoint's multipart media type.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTranscribeSendsCompletePayloadAndReturnsDecodedResponse(): void
	{
		/** @var Audio $subject */
		[$subject, $transport] = $this->createEndpoint(
			Audio::class,
			[[200, '{"text":"hello"}']]
		);

		$result = $subject->transcribe(
			'/audio/input.wav',
			'Expected vocabulary',
			'verbose_json',
			0.25,
			'en',
			'whisper-test'
		);

		$this->assertSame('hello', $result?->text);
		$this->assertOpenaiRequest($transport, 'POST', '/audio/transcriptions');
		$this->assertSame('multipart/form-data', $transport->requests[0]['headers']['Content-Type']);
		$this->assertSame([
			'file' => '/audio/input.wav',
			'prompt' => 'Expected vocabulary',
			'response_format' => 'verbose_json',
			'temperature' => 0.25,
			'language' => 'en',
			'model' => 'whisper-test'
		], $this->jsonRequest($transport));
	}

	/**
	 * Omit every optional translation field when the caller leaves it unset.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTranslationOmitsNullOptionsAndUsesDefaultModel(): void
	{
		/** @var Audio $subject */
		[$subject, $transport] = $this->createEndpoint(Audio::class);

		$subject->translation('/audio/input.mp3');

		$this->assertOpenaiRequest($transport, 'POST', '/audio/translations');
		$this->assertSame('multipart/form-data', $transport->requests[0]['headers']['Content-Type']);
		$this->assertSame([
			'file' => '/audio/input.mp3',
			'model' => 'whisper-1'
		], $this->jsonRequest($transport));
	}
}
