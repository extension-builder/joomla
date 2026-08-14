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


use Joomla\Uri\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Openai\Utilities\Http;
use VDM\Tests\Support\RecordingHttpTransport;


/**
 * OpenAI HTTP header lifecycle test.
 *
 * @since  6.1.6
 */
#[CoversClass(Http::class)]
final class HttpTest extends TestCase
{
	/**
	 * Configure the default media type and optional authorization headers.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorConfiguresHeadersAndUserAgent(): void
	{
		$subject = new Http('user-token', 'organization-token');

		$this->assertSame([
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer user-token',
			'OpenAI-Organization' => 'organization-token'
		], (array) $subject->getOption('headers'));
		$this->assertSame('JoomlaOpenai/3.0', $subject->getOption('userAgent'));
	}

	/**
	 * Leave authorization headers absent when both constructor tokens are null.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorOmitsNullTokens(): void
	{
		$subject = new Http(null, null);

		$this->assertSame(
			['Content-Type' => 'application/json'],
			(array) $subject->getOption('headers')
		);
	}

	/**
	 * Apply user-token mutations to the effective headers of subsequent requests.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUserTokenMutatorsUpdateEffectiveRequestHeaders(): void
	{
		$subject = new Http('old-user', 'old-organization');
		$transport = (new RecordingHttpTransport(
			RecordingHttpTransport::cmsResponse(200, '{}')
		))->attachTo($subject);

		$subject->setTokens('combined-user', 'combined-organization');
		$subject->setToken('final-user');
		$subject->get(new Uri('https://openai.example.test/v1/models'));

		$request = $transport->requests[0];
		$this->assertSame('Bearer final-user', $request['headers']['Authorization']);
		$this->assertSame('combined-organization', $request['headers']['OpenAI-Organization']);
		$this->assertSame('application/json', $request['headers']['Content-Type']);
	}

	/**
	 * Define the intended organization-token mutation contract while the known typo exists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testSetOrgTokenUpdatesEffectiveRequestHeaderWithoutWarning(): void
	{
		$subject = new Http('user-token', 'old-organization');
		$transport = (new RecordingHttpTransport(
			RecordingHttpTransport::cmsResponse(200, '{}')
		))->attachTo($subject);

		$subject->setOrgToken('new-organization');
		$subject->get(new Uri('https://openai.example.test/v1/models'));

		$this->assertSame(
			'new-organization',
			$transport->requests[0]['headers']['OpenAI-Organization']
		);
	}
}
