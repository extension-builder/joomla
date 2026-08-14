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


use Joomla\Uri\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Github\Utilities\Http;
use VDM\Tests\Support\RecordingHttpTransport;


/**
 * GitHub HTTP header and credential lifecycle tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Http::class)]
final class HttpTest extends TestCase
{
	/**
	 * Configure the documented media, version, user-agent, and bearer headers.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorConfiguresDefaultHeaders(): void
	{
		$subject = new Http('access-token', '2026-03-10');

		$this->assertSame([
			'Content-Type' => 'application/json',
			'Accept' => 'application/vnd.github+json',
			'X-GitHub-Api-Version' => '2026-03-10',
			'Authorization' => 'Bearer access-token'
		], (array) $subject->getOption('headers'));
		$this->assertSame('JoomlaGitHub/3.0', $subject->getOption('userAgent'));
		$this->assertSame('access-token', $subject->getToken());
	}

	/**
	 * Omit authorization when no usable token is supplied.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorOmitsEmptyCredentials(): void
	{
		$subject = new Http('');

		$this->assertArrayNotHasKey('Authorization', (array) $subject->getOption('headers'));
		$this->assertNull($subject->getToken());
	}

	/**
	 * Switch GitHub response media types on subsequent requests.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJsonAndRawSwitchEffectiveAcceptHeader(): void
	{
		$subject = new Http('token');
		$transport = (new RecordingHttpTransport(
			RecordingHttpTransport::cmsResponse(200, '{}'),
			RecordingHttpTransport::cmsResponse(200, 'raw')
		))->attachTo($subject);

		$this->assertSame($subject, $subject->json());
		$subject->get(new Uri('https://api.github.test/json'));
		$this->assertSame($subject, $subject->raw());
		$subject->get(new Uri('https://api.github.test/raw'));

		$this->assertSame('application/vnd.github+json', $transport->requests[0]['headers']['Accept']);
		$this->assertSame('application/vnd.github.raw+json', $transport->requests[1]['headers']['Accept']);
	}

	/**
	 * Apply and remove bearer authorization from effective request headers.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetTokenUpdatesEffectiveAuthorizationHeader(): void
	{
		$subject = new Http('old-token');
		$transport = (new RecordingHttpTransport(
			RecordingHttpTransport::cmsResponse(200, '{}'),
			RecordingHttpTransport::cmsResponse(200, '{}')
		))->attachTo($subject);

		$subject->setToken('new-token');
		$subject->get(new Uri('https://api.github.test/authorized'));
		$subject->setToken('');
		$subject->get(new Uri('https://api.github.test/anonymous'));

		$this->assertSame('Bearer new-token', $transport->requests[0]['headers']['Authorization']);
		$this->assertArrayNotHasKey('Authorization', $transport->requests[1]['headers']);
	}

	/**
	 * Define the intended identity contract when authorization is cleared.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testClearingTokenAlsoClearsReportedTokenIdentity(): void
	{
		$subject = new Http('old-token');

		$subject->setToken('');

		$this->assertNull($subject->getToken());
	}
}
