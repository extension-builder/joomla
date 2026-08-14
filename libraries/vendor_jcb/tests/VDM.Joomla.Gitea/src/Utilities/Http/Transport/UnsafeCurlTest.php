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

namespace VDM\Joomla\Gitea\Tests\Utilities\Http\Transport;


use Joomla\Http\Exception\InvalidResponseCodeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use VDM\Joomla\Gitea\Utilities\Http\Transport\UnsafeCurl;


/**
 * Unsafe cURL availability and response parsing tests.
 *
 * No test invokes the live cURL request method.
 * A separate process preserves the production class's initially uninitialized
 * security switch, which PHP cannot restore after the first assignment.
 *
 * @since  1.0.0
 */
#[CoversClass(UnsafeCurl::class)]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class UnsafeCurlTest extends TestCase
{
	/**
	 * Leave transport selection in its safe disabled state.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		(new ReflectionClass(UnsafeCurl::class))->getProperty('allowSelfSigned')->setValue(null, false);

		parent::tearDown();
	}

	/**
	 * Require both the explicit unsafe switch and the cURL extension.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testSupportRequiresConfigurationAndCurlExtension(): void
	{
		$property = (new ReflectionClass(UnsafeCurl::class))->getProperty('allowSelfSigned');

		$property->setValue(null, false);
		$this->assertFalse(UnsafeCurl::isSupported());

		$property->setValue(null, true);
		$this->assertSame(function_exists('curl_version'), UnsafeCurl::isSupported());
	}

	/**
	 * Parse the final response in a redirected cURL header block.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testResponseParserUsesFinalHeaderBlockAndBody(): void
	{
		$headers = "HTTP/1.1 301 Moved Permanently\r\nLocation: /final\r\n\r\n"
			. "HTTP/2 200 OK\r\nContent-Type: application/json\r\nX-Trace: first\r\nX-Trace: second\r\n\r\n";
		$response = $this->invokeResponseParser(
			$headers . '{"ok":true}',
			['header_size' => strlen($headers), 'redirect_count' => 1]
		);

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('{"ok":true}', (string) $response->getBody());
		$this->assertSame(['application/json'], $response->getHeader('Content-Type'));
		$this->assertSame(['first', 'second'], $response->getHeader('X-Trace'));
	}

	/**
	 * Parse a response when cURL did not report an explicit header size.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testResponseParserSupportsHeaderSizeFallback(): void
	{
		$response = $this->invokeResponseParser(
			"HTTP/1.1 202 Accepted\r\nContent-Type: text/plain\r\n\r\nqueued",
			['redirect_count' => 0]
		);

		$this->assertSame(202, $response->getStatusCode());
		$this->assertSame('queued', (string) $response->getBody());
	}

	/**
	 * Reject malformed transport responses without an HTTP status code.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testResponseParserRejectsMissingStatusCode(): void
	{
		$this->expectException(InvalidResponseCodeException::class);
		$this->expectExceptionMessage('No HTTP response code found.');

		$this->invokeResponseParser("not-http\r\nX-Test: value\r\n\r\nbody", ['redirect_count' => 0]);
	}

	/**
	 * Invoke the protected response parser without constructing a live transport.
	 *
	 * @param   string  $content  Raw response.
	 * @param   array   $info     cURL response metadata.
	 *
	 * @return  \Joomla\Http\Response
	 * @since   1.0.0
	 */
	private function invokeResponseParser(string $content, array $info): \Joomla\Http\Response
	{
		$reflection = new ReflectionClass(UnsafeCurl::class);
		$transport = $reflection->newInstanceWithoutConstructor();

		return $reflection->getMethod('getResponse')->invoke($transport, $content, $info);
	}
}
