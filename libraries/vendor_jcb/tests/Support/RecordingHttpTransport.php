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

namespace VDM\Tests\Support;


use Joomla\Http\Http;
use Joomla\Http\Response;
use Joomla\Http\TransportInterface;
use Joomla\Uri\UriInterface;
use ReflectionProperty;
use RuntimeException;


/**
 * Deterministic Joomla HTTP transport that records requests and returns queued responses.
 *
 * @since  1.0.0
 */
final class RecordingHttpTransport implements TransportInterface
{
	/**
	 * Recorded transport calls in execution order.
	 *
	 * @var    array<int, array{method: string, uri: string, data: mixed, headers: array<mixed>, timeout: mixed, userAgent: mixed}>
	 * @since  1.0.0
	 */
	public array $requests = [];

	/**
	 * Responses waiting to be returned.
	 *
	 * @var    array<int, Response>
	 * @since  1.0.0
	 */
	private array $responses = [];

	/**
	 * Create a recorder with optional queued responses.
	 *
	 * @param   Response  ...$responses  Responses returned in request order.
	 *
	 * @since   1.0.0
	 */
	public function __construct(Response ...$responses)
	{
		$this->responses = $responses;
	}

	/**
	 * Replace a Joomla HTTP client's external transport boundary.
	 *
	 * @param   Http  $http  The client to make deterministic.
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function attachTo(Http $http): self
	{
		$property = new ReflectionProperty(Http::class, 'transport');
		$property->setValue($http, $this);

		return $this;
	}

	/**
	 * Add a response to the end of the queue.
	 *
	 * @param   Response  $response  The response to return.
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function queue(Response $response): self
	{
		$this->responses[] = $response;

		return $this;
	}

	/**
	 * Build a Joomla response with deterministic status and body.
	 *
	 * @param   int                   $status   The HTTP status code.
	 * @param   string                $body     The complete response body.
	 * @param   array<string, mixed>  $headers  Optional response headers.
	 *
	 * @return  Response
	 * @since   1.0.0
	 */
	public static function response(int $status = 200, string $body = '', array $headers = []): Response
	{
		$response = new Response('php://memory', $status, $headers);
		$response->getBody()->write($body);

		return $response;
	}

	/**
	 * Build the Joomla CMS compatibility response required by older adapters.
	 *
	 * @param   int                   $status   The HTTP status code.
	 * @param   string                $body     The complete response body.
	 * @param   array<string, mixed>  $headers  Optional response headers.
	 *
	 * @return  \Joomla\CMS\Http\Response
	 * @since   1.0.0
	 */
	public static function cmsResponse(
		int $status = 200,
		string $body = '',
		array $headers = []
	): \Joomla\CMS\Http\Response
	{
		$response = new \Joomla\CMS\Http\Response('php://memory', $status, $headers);
		$response->getBody()->write($body);

		return $response;
	}

	/**
	 * Record one request and return its queued response.
	 *
	 * @param   string        $method     The HTTP method.
	 * @param   UriInterface  $uri        The request URI.
	 * @param   mixed         $data       The request body.
	 * @param   array         $headers    The effective request headers.
	 * @param   mixed         $timeout    The effective timeout.
	 * @param   mixed         $userAgent  The effective user agent.
	 *
	 * @return  Response
	 * @since   1.0.0
	 */
	public function request(
		$method,
		UriInterface $uri,
		$data = null,
		array $headers = [],
		$timeout = null,
		$userAgent = null
	): Response
	{
		$this->requests[] = [
			'method' => $method,
			'uri' => (string) $uri,
			'data' => $data,
			'headers' => $headers,
			'timeout' => $timeout,
			'userAgent' => $userAgent
		];

		$response = array_shift($this->responses);

		if (!$response instanceof Response)
		{
			throw new RuntimeException('No HTTP response was queued for recorded request: ' . $method . ' ' . $uri);
		}

		return $response;
	}

	/**
	 * Advertise deterministic availability to the Joomla HTTP layer.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public static function isSupported(): bool
	{
		return true;
	}
}
