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


use Joomla\Http\Response;
use Joomla\Http\TransportInterface;
use Joomla\Uri\UriInterface;
use Laminas\Diactoros\Stream;
use RuntimeException;


/**
 * In-memory Joomla HTTP transport that records requests and returns queued responses.
 *
 * @since  1.0.0
 */
final class RecordingTransport implements TransportInterface
{
	/**
	 * Recorded HTTP requests in call order.
	 *
	 * @var    array<int, array{method: string, uri: string, data: mixed, headers: array<string, mixed>, timeout: mixed, userAgent: mixed}>
	 * @since  1.0.0
	 */
	private array $requests = [];

	/**
	 * Responses queued for subsequent requests.
	 *
	 * @var    array<int, Response>
	 * @since  1.0.0
	 */
	private array $responses = [];

	/**
	 * This test transport is always available.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public static function isSupported(): bool
	{
		return true;
	}

	/**
	 * Queue a response for the next request.
	 *
	 * @param   int                   $status   HTTP status code.
	 * @param   string                $body     Complete response body.
	 * @param   array<string, string>  $headers  Response headers.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function queueResponse(
		int $status = 200,
		string $body = '{}',
		array $headers = ['Content-Type' => 'application/json']
	): void
	{
		$stream = new Stream('php://memory', 'rw');
		$stream->write($body);
		$stream->rewind();

		$this->responses[] = new Response($stream, $status, $headers);
	}

	/**
	 * Record a request and return the next queued response.
	 *
	 * @param   string        $method     HTTP method.
	 * @param   UriInterface  $uri        Request URI.
	 * @param   mixed         $data       Request body.
	 * @param   array         $headers    Request headers.
	 * @param   mixed         $timeout    Request timeout.
	 * @param   mixed         $userAgent  Request user agent.
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
			'method' => (string) $method,
			'uri' => (string) $uri,
			'data' => $data,
			'headers' => $headers,
			'timeout' => $timeout,
			'userAgent' => $userAgent
		];

		if ($this->responses === [])
		{
			throw new RuntimeException('No response was queued for the recorded HTTP request.');
		}

		return array_shift($this->responses);
	}

	/**
	 * Return all recorded requests.
	 *
	 * @return  array<int, array{method: string, uri: string, data: mixed, headers: array<string, mixed>, timeout: mixed, userAgent: mixed}>
	 * @since   1.0.0
	 */
	public function requests(): array
	{
		return $this->requests;
	}

	/**
	 * Return the most recently recorded request.
	 *
	 * @return  array{method: string, uri: string, data: mixed, headers: array<string, mixed>, timeout: mixed, userAgent: mixed}
	 * @since   1.0.0
	 */
	public function lastRequest(): array
	{
		if ($this->requests === [])
		{
			throw new RuntimeException('No HTTP request has been recorded.');
		}

		return $this->requests[array_key_last($this->requests)];
	}
}
