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
use VDM\Joomla\Openai\Embeddings;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI embeddings endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(Embeddings::class)]
final class EmbeddingsTest extends OpenaiTestCase
{
	/**
	 * Preserve array token input and the optional end-user identifier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateSendsArrayInputAndUser(): void
	{
		/** @var Embeddings $subject */
		[$subject, $transport] = $this->createEndpoint(
			Embeddings::class,
			[[200, '{"object":"list"}']]
		);

		$result = $subject->create('embedding-model', [[1, 2], [3, 4]], 'user-3');

		$this->assertSame('list', $result?->object);
		$this->assertOpenaiRequest($transport, 'POST', '/embeddings');
		$this->assertSame([
			'model' => 'embedding-model',
			'input' => [[1, 2], [3, 4]],
			'user' => 'user-3'
		], $this->jsonRequest($transport));
	}

	/**
	 * Omit the user field without changing a scalar input.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateOmitsNullUserForScalarInput(): void
	{
		/** @var Embeddings $subject */
		[$subject, $transport] = $this->createEndpoint(Embeddings::class);

		$subject->create('embedding-model', 'input text');

		$this->assertSame([
			'model' => 'embedding-model',
			'input' => 'input text'
		], $this->jsonRequest($transport));
	}
}
