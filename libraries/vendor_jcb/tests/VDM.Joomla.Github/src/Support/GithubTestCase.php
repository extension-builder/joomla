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

namespace VDM\Joomla\Github\Tests\Support;


use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Github\Abstraction\Api;
use VDM\Joomla\Github\Utilities\Http;
use VDM\Joomla\Github\Utilities\Response;
use VDM\Joomla\Github\Utilities\Uri;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\RecordingHttpTransport;


/**
 * Shared deterministic composition for GitHub endpoint tests.
 *
 * @since  6.1.6
 */
#[UsesClass(Api::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(StringHelper::class)]
abstract class GithubTestCase extends TestCase
{
	/**
	 * Build an API endpoint around a recording transport.
	 *
	 * @param   class-string<Api>            $class      Endpoint class to construct.
	 * @param   array<int, array{int,string}> $responses  Queued status/body pairs.
	 *
	 * @return  array{0: Api, 1: RecordingHttpTransport, 2: Http}
	 * @since   6.1.6
	 */
	protected function createEndpoint(string $class, array $responses = [[200, '{}']]): array
	{
		$queued = [];

		foreach ($responses as [$status, $body])
		{
			$queued[] = RecordingHttpTransport::response($status, $body);
		}

		$http = new Http('test-token', '2026-03-10');
		$transport = (new RecordingHttpTransport(...$queued))->attachTo($http);
		$endpoint = new $class(
			$http,
			new Uri('https://github.example.test/base'),
			new Response()
		);

		return [$endpoint, $transport, $http];
	}

	/**
	 * Get and validate one decoded JSON request body.
	 *
	 * @param   RecordingHttpTransport  $transport  The recording boundary.
	 * @param   int                     $index      Request index.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.6
	 */
	protected function jsonRequest(RecordingHttpTransport $transport, int $index = 0): array
	{
		$this->assertArrayHasKey($index, $transport->requests);
		$data = $transport->requests[$index]['data'];
		$this->assertIsString($data);
		$decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
		$this->assertIsArray($decoded);

		return $decoded;
	}

	/**
	 * Assert the common GitHub request envelope.
	 *
	 * @param   RecordingHttpTransport  $transport  The recording boundary.
	 * @param   string                  $method     Expected HTTP method.
	 * @param   string                  $path       Expected path and query.
	 * @param   string                  $accept     Expected media type.
	 * @param   int                     $index      Request index.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function assertGithubRequest(
		RecordingHttpTransport $transport,
		string $method,
		string $path,
		string $accept = 'application/vnd.github+json',
		int $index = 0
	): void
	{
		$this->assertArrayHasKey($index, $transport->requests);
		$request = $transport->requests[$index];

		$this->assertSame($method, $request['method']);
		$this->assertSame('https://github.example.test/base/' . ltrim($path, '/'), $request['uri']);
		$this->assertSame('Bearer test-token', $request['headers']['Authorization'] ?? null);
		$this->assertSame($accept, $request['headers']['Accept'] ?? null);
		$this->assertSame('2026-03-10', $request['headers']['X-GitHub-Api-Version'] ?? null);
		$this->assertSame('JoomlaGitHub/3.0', $request['userAgent']);
	}
}
