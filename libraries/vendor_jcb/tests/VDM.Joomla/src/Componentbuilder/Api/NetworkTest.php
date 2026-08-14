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

namespace VDM\Joomla\Tests\Componentbuilder\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Abstraction\Api;
use VDM\Joomla\Componentbuilder\Api\Network;
use VDM\Joomla\Componentbuilder\Utilities\Http;
use VDM\Joomla\Componentbuilder\Utilities\Response;
use VDM\Joomla\Componentbuilder\Utilities\Uri;
use VDM\Tests\Support\RecordingHttpTransport;
use VDM\Tests\Support\TestCase;

/**
 * Component Builder network endpoint request contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Api::class)]
#[CoversClass(Network::class)]
#[UsesClass(Http::class)]
#[UsesClass(Uri::class)]
#[UsesClass(Response::class)]
final class NetworkTest extends TestCase
{
	/**
	 * Include every supplied route segment and decode the successful response.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetBuildsCompleteNetworkStatusRequest(): void
	{
		$transport = new RecordingHttpTransport(
			RecordingHttpTransport::cmsResponse(
				200,
				'{"repository":"powers","status":1}'
			)
		);
		$http = new Http();
		$transport->attachTo($http);
		$subject = new Network(
			$http,
			new Uri('https://api.example.test', 'v9'),
			new Response()
		);

		$this->assertEquals(
			(object) ['repository' => 'powers', 'status' => 1],
			$subject->get('powers', 1, 'builder', 'community')
		);
		$this->assertCount(1, $transport->requests);
		$this->assertSame('GET', $transport->requests[0]['method']);
		$this->assertSame(
			'https://api.example.test/v9/network/community/builder/powers/1',
			$transport->requests[0]['uri']
		);
		$this->assertSame('JCB/5.0', $transport->requests[0]['userAgent']);
	}

	/**
	 * Omit empty optional target and zero status route segments.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetOmitsEmptyOptionalSegments(): void
	{
		$transport = new RecordingHttpTransport(
			RecordingHttpTransport::cmsResponse(200, '{"ok":true}')
		);
		$http = new Http();
		$transport->attachTo($http);
		$subject = new Network($http, new Uri(), new Response());

		$this->assertEquals((object) ['ok' => true], $subject->get(null, 0));
		$this->assertSame(
			'https://api.joomlacomponentbuilder.com/v1/network/community/jcb',
			$transport->requests[0]['uri']
		);
	}
}
