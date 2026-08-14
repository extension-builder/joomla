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

namespace VDM\Joomla\Github\Tests\Utilities;


use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Github\Utilities\Response;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\RecordingHttpTransport;


/**
 * GitHub response decoding and diagnostic mapping tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Response::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(StringHelper::class)]
final class ResponseTest extends TestCase
{
	/**
	 * Decode JSON, expose decoded base64 content, and preserve plain text.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetDecodesJsonBase64AndPlainTextBodies(): void
	{
		$subject = new Response();
		$encoded = base64_encode('Hello GitHub');
		$json = $subject->get(RecordingHttpTransport::response(
			200,
			json_encode(['name' => 'README.md', 'content' => $encoded, 'encoding' => 'base64'])
		));

		$this->assertSame('README.md', $json->name);
		$this->assertSame($encoded, $json->content);
		$this->assertSame('Hello GitHub', $json->decoded_content);
		$this->assertSame(
			'plain body',
			$subject->get(RecordingHttpTransport::response(200, 'plain body'))
		);
	}

	/**
	 * Use supplied defaults for empty bodies, including null status-map defaults.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmptyBodiesUseExactConfiguredDefaults(): void
	{
		$subject = new Response();

		$this->assertSame(
			'deleted',
			$subject->get(RecordingHttpTransport::response(204), 204, 'deleted')
		);
		$this->assertNull(
			$subject->get_(RecordingHttpTransport::response(204), [200 => 'ok', 204 => null])
		);
	}

	/**
	 * Translate every supported GitHub error representation into stable diagnostics.
	 *
	 * @param   string  $body      Response body.
	 * @param   int     $status    HTTP status.
	 * @param   string  $expected  Expected extracted diagnostic.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('errorProvider')]
	public function testInvalidStatusThrowsDetailedDomainException(
		string $body,
		int $status,
		string $expected
	): void
	{
		try
		{
			(new Response())->get(RecordingHttpTransport::response($status, $body));
			$this->fail('Expected a GitHub response exception.');
		}
		catch (DomainException $error)
		{
			$this->assertSame($status, $error->getCode());
			$this->assertSame(
				'Invalid response received from GitHub API. ' . $expected,
				$error->getMessage()
			);
		}
	}

	/**
	 * Provide representative GitHub error shapes.
	 *
	 * @return  iterable<string, array{string, int, string}>
	 * @since   6.1.6
	 */
	public static function errorProvider(): iterable
	{
		yield 'message and structured details' => [
			'{"message":"Validation Failed","errors":[{"resource":"Issue","field":"title","code":"missing"},"secondary"]}',
			422,
			'Validation Failed (Issue title missing; secondary)'
		];
		yield 'JSON object without message' => [
			'{"documentation_url":"https://docs.github.test/errors"}',
			400,
			'{"documentation_url":"https://docs.github.test/errors"}'
		];
		yield 'plain response body' => ['gateway unavailable', 502, 'gateway unavailable'];
		yield 'empty body reason phrase' => ['', 503, 'Service Unavailable'];
	}

	/**
	 * Reject a status absent from the multi-status validation map.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetMultipleRejectsUnlistedStatus(): void
	{
		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('Invalid response received from GitHub API. retry later');

		(new Response())->get_(
			RecordingHttpTransport::response(429, 'retry later'),
			[200 => null, 202 => null]
		);
	}
}
