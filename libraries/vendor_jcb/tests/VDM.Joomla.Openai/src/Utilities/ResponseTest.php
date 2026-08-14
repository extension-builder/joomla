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


use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Openai\Utilities\Response;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\RecordingHttpTransport;


/**
 * OpenAI response translator test.
 *
 * @since  6.1.6
 */
#[CoversClass(Response::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(StringHelper::class)]
final class ResponseTest extends TestCase
{
	/**
	 * Decode JSON success bodies and preserve plain text bodies.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetMapsJsonTextAndEmptySuccessBodies(): void
	{
		$subject = new Response();
		$json = $subject->get(RecordingHttpTransport::cmsResponse(200, '{"id":"item-1"}'));
		$text = $subject->get(RecordingHttpTransport::cmsResponse(200, 'plain text'));
		$empty = $subject->get(RecordingHttpTransport::cmsResponse(200), 200, 'fallback');

		$this->assertSame('item-1', $json->id);
		$this->assertSame('plain text', $text);
		$this->assertSame('fallback', $empty);
	}

	/**
	 * Use the status-specific default supplied by a multi-status map.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetMultipleUsesNonNullStatusDefaultForEmptyBody(): void
	{
		$subject = new Response();

		$this->assertSame(
			'accepted',
			$subject->get_(
				RecordingHttpTransport::cmsResponse(202),
				[200 => 'ok', 202 => 'accepted']
			)
		);
	}

	/**
	 * Define the intended null-default status-map contract while the known defect exists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testGetMultipleAcceptsStatusWhoseConfiguredDefaultIsNull(): void
	{
		$subject = new Response();

		$this->assertNull(
			$subject->get_(RecordingHttpTransport::cmsResponse(204), [204 => null])
		);
	}

	/**
	 * Translate error response shapes into stable diagnostics.
	 *
	 * @param   string  $body      The response body.
	 * @param   string  $expected  The expected exception message.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('errorProvider')]
	public function testGetThrowsDomainExceptionWithApiDiagnostic(string $body, string $expected): void
	{
		$subject = new Response();

		try
		{
			$subject->get(RecordingHttpTransport::cmsResponse(429, $body));
			$this->fail('Expected an OpenAI response exception.');
		}
		catch (DomainException $error)
		{
			$this->assertSame(429, $error->getCode());
			$this->assertSame($expected, $error->getMessage());
		}
	}

	/**
	 * Provide representative OpenAI error response shapes.
	 *
	 * @return  iterable<string, array{string, string}>
	 * @since   6.1.6
	 */
	public static function errorProvider(): iterable
	{
		yield 'complete error' => [
			'{"error":{"message":"Rate limited","code":"rate_limit"}}',
			'OpenAI Error: Rate limited Code: rate_limit'
		];
		yield 'error defaults' => [
			'{"error":{}}',
			'OpenAI Error: Unknown error. Code: Unknown error code.'
		];
		yield 'json without error' => [
			'{"message":"Not the expected shape"}',
			'No error information found in response.'
		];
		yield 'invalid body' => [
			'upstream unavailable',
			'Invalid or empty response body.'
		];
	}
}
