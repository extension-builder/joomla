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

namespace VDM\Joomla\Openai\Tests\Abstraction;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Openai\Abstraction\Api;
use VDM\Joomla\Openai\Utilities\Http;
use VDM\Joomla\Openai\Utilities\Response;
use VDM\Joomla\Openai\Utilities\Uri;


/**
 * OpenAI API abstraction composition test.
 *
 * @since  6.1.6
 */
#[CoversClass(Api::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class ApiTest extends TestCase
{
	/**
	 * Retain the exact transport collaborators supplied by the composition root.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorRetainsInjectedCollaborators(): void
	{
		$http = new Http(null);
		$uri = new Uri('https://openai.example.test', 'v-test');
		$response = new Response();
		$subject = new class($http, $uri, $response) extends Api
		{
			/**
			 * Expose the injected API collaborators for identity assertions.
			 *
			 * @return  array<int, object>  Injected collaborators in constructor order.
			 * @since   6.1.6
			 */
			public function collaborators(): array
			{
				return [$this->http, $this->uri, $this->response];
			}
		};

		$this->assertSame([$http, $uri, $response], $subject->collaborators());
	}
}
