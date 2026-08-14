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

namespace VDM\Joomla\Tests\Componentbuilder\Utilities;


use DomainException;
use Joomla\Http\Response as JoomlaResponse;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use VDM\Joomla\Componentbuilder\Utilities\Response;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Component Builder API response translation tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Response::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(StringHelper::class)]
final class ResponseTest extends TestCase
{
	/**
	 * Decode JSON and XML while preserving ordinary text.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSuccessfulBodiesAreTranslatedByTheirRepresentation(): void
	{
		$subject = new Response();

		$json = $subject->get($this->response('{"id":7,"name":"JCB"}', 200));
		$xml = $subject->get($this->response('<root><name>JCB</name></root>', 200));

		$this->assertSame(7, $json->id);
		$this->assertSame('JCB', $json->name);
		$this->assertInstanceOf(SimpleXMLElement::class, $xml);
		$this->assertSame('JCB', (string) $xml->name);
		$this->assertSame('plain response', $subject->get($this->response('plain response', 200)));
	}

	/**
	 * Use the caller's default when a successful response has no body.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmptySuccessfulBodyReturnsSuppliedDefault(): void
	{
		$this->assertSame(
			['deleted' => true],
			(new Response())->get($this->response('', 204), 204, ['deleted' => true])
		);
	}

	/**
	 * Extract the most useful diagnostic from each supported error representation.
	 *
	 * @param   string  $body             Complete response body.
	 * @param   int     $status           HTTP status code.
	 * @param   string  $expectedMessage  Expected exception message.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideErrorResponses')]
	public function testUnexpectedStatusThrowsMappedDomainError(
		string $body,
		int $status,
		string $expectedMessage
	): void
	{
		try
		{
			(new Response())->get($this->response($body, $status));
			$this->fail('An unexpected response status must throw DomainException.');
		}
		catch (DomainException $error)
		{
			$this->assertSame($status, $error->getCode());
			$this->assertSame($expectedMessage, $error->getMessage());
		}
	}

	/**
	 * Supply error representations in extraction-priority order.
	 *
	 * @return  iterable<string, array{string, int, string}>
	 * @since   6.1.6
	 */
	public static function provideErrorResponses(): iterable
	{
		yield 'error field wins' => ['{"error":"token rejected","message":"secondary"}', 401, 'token rejected'];
		yield 'message field' => ['{"message":"not found"}', 404, 'not found'];
		yield 'other JSON object' => ['{"detail":"locked/path"}', 409, '{"detail":"locked/path"}'];
		yield 'plain response' => ['upstream unavailable', 502, 'upstream unavailable'];
		yield 'reason phrase' => ['', 503, 'Service Unavailable'];
	}

	/**
	 * Build a Joomla response around an in-memory PSR-7 stream.
	 *
	 * @param   string  $body    Complete response body.
	 * @param   int     $status  HTTP status code.
	 *
	 * @return  JoomlaResponse
	 * @since   6.1.6
	 */
	private function response(string $body, int $status): JoomlaResponse
	{
		$stream = new Stream('php://memory', 'rw');
		$stream->write($body);
		$stream->rewind();

		return new JoomlaResponse($stream, $status);
	}
}
