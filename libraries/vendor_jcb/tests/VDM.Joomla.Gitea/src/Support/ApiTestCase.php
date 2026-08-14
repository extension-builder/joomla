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

namespace VDM\Joomla\Gitea\Tests\Support;


use Joomla\Http\Http as JoomlaHttp;
use Joomla\Registry\Registry;
use ReflectionClass;
use VDM\Joomla\Gitea\Utilities\Http;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Gitea\Utilities\Uri;
use VDM\Tests\Support\TestCase;


/**
 * Base for Gitea API tests backed by a recording HTTP transport.
 *
 * @since  1.0.0
 */
abstract class ApiTestCase extends TestCase
{
	/**
	 * Fixed API root used by endpoint contract tests.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected const API_ROOT = 'https://gitea.example/api/v1';

	/**
	 * Build an endpoint with an isolated transport and one queued response.
	 *
	 * @param   class-string  $class    Concrete endpoint class.
	 * @param   int           $status   Queued response status.
	 * @param   string        $body     Queued response body.
	 * @param   array         $headers  Queued response headers.
	 *
	 * @return  array{object, RecordingTransport, Http, Uri}
	 * @since   1.0.0
	 */
	protected function endpoint(
		string $class,
		int $status = 200,
		string $body = '{}',
		array $headers = ['Content-Type' => 'application/json']
	): array
	{
		$transport = new RecordingTransport();
		$transport->queueResponse($status, $body, $headers);
		$http = $this->http($transport);
		$uri = new Uri('https://gitea.example');

		return [new $class($http, $uri, new Response()), $transport, $http, $uri];
	}

	/**
	 * Build the final production HTTP client without selecting a real transport.
	 *
	 * Production endpoint methods still execute Joomla's real HTTP request pipeline;
	 * only its transport boundary is replaced by the in-memory recorder.
	 *
	 * @param   RecordingTransport  $transport  In-memory transport.
	 * @param   string|null         $token      Optional API token.
	 *
	 * @return  Http
	 * @since   1.0.0
	 */
	protected function http(RecordingTransport $transport, ?string $token = 'unit-token'): Http
	{
		$reflection = new ReflectionClass(Http::class);
		$http = $reflection->newInstanceWithoutConstructor();
		$options = [
			'userAgent' => 'JoomlaGitea/3.0',
			'headers' => ['Content-Type' => 'application/json']
		];

		if ($token !== null && $token !== '')
		{
			$options['headers']['Authorization'] = 'token ' . $token;
			$reflection->getProperty('_token_')->setValue($http, $token);
		}

		$joomlaHttp = new ReflectionClass(JoomlaHttp::class);
		$joomlaHttp->getProperty('options')->setValue($http, new Registry($options));
		$joomlaHttp->getProperty('transport')->setValue($http, $transport);

		return $http;
	}

	/**
	 * Assert the complete request contract recorded by the transport.
	 *
	 * @param   RecordingTransport  $transport  Recording transport.
	 * @param   string              $method     Expected HTTP method.
	 * @param   string              $uri        Expected absolute URI.
	 * @param   mixed               $data       Expected request body.
	 * @param   array|null          $headers    Expected headers, or standard authenticated headers.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function assertRequest(
		RecordingTransport $transport,
		string $method,
		string $uri,
		mixed $data = null,
		?array $headers = null
	): void
	{
		$request = $transport->lastRequest();

		$this->assertSame($method, $request['method']);
		$this->assertSame($uri, $request['uri']);
		$this->assertSame($data, $request['data']);
		$this->assertSame(
			$headers ?? [
				'Content-Type' => 'application/json',
				'Authorization' => 'token unit-token'
			],
			$request['headers']
		);
		$this->assertNull($request['timeout']);
		$this->assertSame('JoomlaGitea/3.0', $request['userAgent']);
	}

	/**
	 * Assert an exact JSON object was passed as the request body.
	 *
	 * @param   array<string, mixed>  $expected  Expected decoded body.
	 * @param   mixed                 $actual    Actual encoded request body.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function assertJsonBody(array $expected, mixed $actual): void
	{
		$this->assertIsString($actual);
		$this->assertSame($expected, json_decode($actual, true, 512, JSON_THROW_ON_ERROR));
	}
}
