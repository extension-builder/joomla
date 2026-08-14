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
use VDM\Joomla\Openai\FineTunes;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI fine-tunes endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(FineTunes::class)]
final class FineTunesTest extends OpenaiTestCase
{
	/**
	 * Fetch fine-tune jobs from the exact collection resource.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testListUsesFineTunesCollection(): void
	{
		/** @var FineTunes $subject */
		[$subject, $transport] = $this->createEndpoint(
			FineTunes::class,
			[[200, '{"data":[{"id":"job-1"}]}']]
		);

		$result = $subject->list();

		$this->assertSame('job-1', $result?->data[0]->id);
		$this->assertOpenaiRequest($transport, 'GET', '/fine-tunes');
		$this->assertNull($transport->requests[0]['data']);
	}
}
