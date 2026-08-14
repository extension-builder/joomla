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
use VDM\Joomla\Openai\Models;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI models endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(Models::class)]
final class ModelsTest extends OpenaiTestCase
{
	/**
	 * Fetch and decode the models collection without a request body.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testListUsesModelsCollection(): void
	{
		/** @var Models $subject */
		[$subject, $transport] = $this->createEndpoint(
			Models::class,
			[[200, '{"data":[{"id":"model-1"}]}']]
		);

		$result = $subject->list();

		$this->assertSame('model-1', $result?->data[0]->id);
		$this->assertOpenaiRequest($transport, 'GET', '/models');
		$this->assertNull($transport->requests[0]['data']);
	}
}
