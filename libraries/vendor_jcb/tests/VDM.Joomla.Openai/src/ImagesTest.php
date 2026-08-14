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
use VDM\Joomla\Openai\Images;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI images endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(Images::class)]
final class ImagesTest extends OpenaiTestCase
{
	/**
	 * Send every generation option to the generations resource.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGenerateSendsCompletePayload(): void
	{
		/** @var Images $subject */
		[$subject, $transport] = $this->createEndpoint(Images::class);

		$subject->generate('A precise diagram', '1024x1024', 'b64_json', 2, 'user-4');

		$this->assertOpenaiRequest($transport, 'POST', '/images/generations');
		$this->assertSame([
			'prompt' => 'A precise diagram',
			'size' => '1024x1024',
			'response_format' => 'b64_json',
			'n' => 2,
			'user' => 'user-4'
		], $this->jsonRequest($transport));
	}

	/**
	 * Keep a minimal generation request free of null-valued optional fields.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGenerateOmitsNullOptionalFields(): void
	{
		/** @var Images $subject */
		[$subject, $transport] = $this->createEndpoint(Images::class);

		$subject->generate('Only a prompt');

		$this->assertSame(['prompt' => 'Only a prompt'], $this->jsonRequest($transport));
	}

	/**
	 * Send the source image, prompt, mask, and all optional edit controls.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEditSendsCompletePayload(): void
	{
		/** @var Images $subject */
		[$subject, $transport] = $this->createEndpoint(Images::class);

		$subject->edit(
			'/tmp/image.png',
			'Add a toolbar',
			'/tmp/mask.png',
			'512x512',
			'url',
			3,
			'user-5'
		);

		$this->assertOpenaiRequest($transport, 'POST', '/images/edits');
		$this->assertSame([
			'image' => '/tmp/image.png',
			'prompt' => 'Add a toolbar',
			'mask' => '/tmp/mask.png',
			'size' => '512x512',
			'response_format' => 'url',
			'n' => 3,
			'user' => 'user-5'
		], $this->jsonRequest($transport));
	}

	/**
	 * Send the source image and all variation controls to the variation resource.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testVariationSendsCompletePayload(): void
	{
		/** @var Images $subject */
		[$subject, $transport] = $this->createEndpoint(Images::class);

		$subject->variation('/tmp/image.png', '256x256', 'b64_json', 4, 'user-6');

		$this->assertOpenaiRequest($transport, 'POST', '/images/variations');
		$this->assertSame([
			'image' => '/tmp/image.png',
			'size' => '256x256',
			'response_format' => 'b64_json',
			'n' => 4,
			'user' => 'user-6'
		], $this->jsonRequest($transport));
	}
}
