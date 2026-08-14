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

namespace VDM\Joomla\Gitea\Tests\Utilities;


use DomainException;
use Joomla\Http\Response as JoomlaResponse;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Gitea response decoding and error mapping tests.
 *
 * @since  1.0.0
 */
#[CoversClass(Response::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(StringHelper::class)]
final class ResponseTest extends TestCase
{
	/**
	 * Decode JSON and expose decoded base64 content without discarding metadata.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testJsonAndBase64ContentAreDecoded(): void
	{
		$result = (new Response())->get(
			$this->response('{"name":"README.md","content_base64":"SGVsbG8h"}', 200)
		);

		$this->assertSame('README.md', $result->name);
		$this->assertSame('SGVsbG8h', $result->content_base64);
		$this->assertSame('Hello!', $result->content);
	}

	/**
	 * Preserve non-JSON bodies and use the supplied default for an empty response.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testTextAndEmptyBodiesMapToTheirDocumentedResults(): void
	{
		$subject = new Response();

		$this->assertSame('plain response', $subject->get($this->response('plain response', 200)));
		$this->assertSame('deleted', $subject->get($this->response('', 204), 204, 'deleted'));
	}

	/**
	 * Select the default associated with the actual successful status code.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testMultipleSuccessCodesUseStatusSpecificDefaults(): void
	{
		$subject = new Response();

		$this->assertSame(
			'accepted',
			$subject->get_($this->response('', 202), [200 => null, 202 => 'accepted'])
		);
	}

	/**
	 * Map every supported error representation into the thrown API diagnostic.
	 *
	 * @param   string  $body             Response body.
	 * @param   int     $status           Response status.
	 * @param   string  $expectedMessage  Extracted message.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('errorResponseProvider')]
	public function testInvalidStatusesExposeUsefulErrorDetails(
		string $body,
		int $status,
		string $expectedMessage
	): void
	{
		try
		{
			(new Response())->get($this->response($body, $status));
			$this->fail('A non-success response must throw DomainException.');
		}
		catch (DomainException $error)
		{
			$this->assertSame($status, $error->getCode());
			$this->assertSame(
				'Invalid response received from Gitea API. ' . $expectedMessage,
				$error->getMessage()
			);
		}
	}

	/**
	 * Provide error response representations in priority order.
	 *
	 * @return  iterable<string, array{string, int, string}>
	 * @since   1.0.0
	 */
	public static function errorResponseProvider(): iterable
	{
		yield 'error field wins' => [
			'{"error":"token rejected","message":"secondary"}',
			401,
			'token rejected'
		];
		yield 'message field' => ['{"message":"repository missing"}', 404, 'repository missing'];
		yield 'other JSON object' => ['{"detail":"conflict/locked"}', 409, '{"detail":"conflict/locked"}'];
		yield 'plain body' => ['gateway exploded', 502, 'gateway exploded'];
		yield 'reason phrase' => ['', 503, 'Service Unavailable'];
	}

	/**
	 * Reject a status not present in the multi-code validation map.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testMultiCodeValidationRejectsUnlistedStatus(): void
	{
		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('Invalid response received from Gitea API. retry later');

		(new Response())->get_($this->response('retry later', 429), [200 => null, 202 => null]);
	}

	/**
	 * Build a Joomla response around an in-memory PSR-7 body stream.
	 *
	 * @param   string  $body    Complete response body.
	 * @param   int     $status  HTTP status code.
	 *
	 * @return  JoomlaResponse
	 * @since   1.0.0
	 */
	private function response(string $body, int $status): JoomlaResponse
	{
		$stream = new Stream('php://memory', 'rw');
		$stream->write($body);
		$stream->rewind();

		return new JoomlaResponse($stream, $status);
	}
}
