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

namespace VDM\Joomla\Openai\Tests\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Openai\Utilities\Uri;


/**
 * OpenAI URI builder test.
 *
 * @since  6.1.6
 */
#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
	/**
	 * Compose the configured base URL, version, resource path, and query values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetBuildsVersionedApiUri(): void
	{
		$subject = new Uri('https://openai.example.test/base', 'v-test');

		$uri = $subject->get('/models');
		$uri->setVar('after', 'model-id');

		$this->assertSame('https://openai.example.test/base/v-test', $subject->api());
		$this->assertSame(
			'https://openai.example.test/base/v-test/models?after=model-id',
			(string) $uri
		);
	}

	/**
	 * Keep separate URI objects isolated across requests.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetReturnsIndependentUriInstances(): void
	{
		$subject = new Uri();
		$first = $subject->get('/files');
		$second = $subject->get('/files');

		$first->setVar('purpose', 'assistants');

		$this->assertNotSame($first, $second);
		$this->assertStringContainsString('purpose=assistants', (string) $first);
		$this->assertStringNotContainsString('purpose=', (string) $second);
	}
}
