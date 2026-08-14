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

namespace VDM\Joomla\Tests\Componentbuilder\Network;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Api\Network as NetworkApi;
use VDM\Joomla\Componentbuilder\Network\Core;
use VDM\Joomla\Componentbuilder\Network\ParsedUrls;
use VDM\Joomla\Componentbuilder\Network\Resolve;
use VDM\Joomla\Componentbuilder\Network\Status;
use VDM\Joomla\Componentbuilder\Network\Url;
use VDM\Joomla\Componentbuilder\Utilities\Http;
use VDM\Joomla\Componentbuilder\Utilities\Response;
use VDM\Joomla\Componentbuilder\Utilities\Uri;
use VDM\Tests\Support\RecordingHttpTransport;
use VDM\Tests\Support\TestCase;


/**
 * Repository URL discovery, network-cache, and failover contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Core::class)]
#[CoversClass(ParsedUrls::class)]
#[CoversClass(Resolve::class)]
#[CoversClass(Status::class)]
#[CoversClass(Url::class)]
#[UsesClass(NetworkApi::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class RepositoryDiscoveryTest extends TestCase
{
	/**
	 * Protect URL component extraction, caching, and comparison scopes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUrlParsesCachesAndComparesRepositoryCoordinates(): void
	{
		$cache = new ParsedUrls();
		$subject = new Url($cache);
		$url = 'https://git.example.test/acme/widget?ref=main';

		$this->assertEquals(
			(object) [
				'scheme' => 'https',
				'domain' => 'git.example.test',
				'organization' => 'acme',
				'repository' => 'widget',
			],
			$subject->parse($url)
		);
		$this->assertNotNull($cache->get($url));
		$this->assertSame('git.example.test', $subject->base('https://git.example.test/'));
		$this->assertTrue($subject->equal($url, 'https://git.example.test/other/widget'));
		$this->assertFalse($subject->equalStrict($url, 'https://git.example.test/other/widget'));
		$this->assertTrue($subject->equalRepo($url, 'https://codeberg.org/mirror/widget'));
		$this->assertFalse($subject->equalRepo($url, 'https://codeberg.org/mirror/other'));
	}

	/**
	 * Protect rejection of malformed or incomplete repository URLs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUrlRejectsMissingRepositoryCoordinates(): void
	{
		$subject = new Url(new ParsedUrls());

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('both an organization and a repository');
		$subject->parse('https://git.example.test/acme');
	}

	/**
	 * Protect remote loading, exact endpoint, and one-fetch cache semantics.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStatusFetchesNetworkOnceAndCachesTheResult(): void
	{
		$http = new Http();
		$transport = new RecordingHttpTransport(
			RecordingHttpTransport::cmsResponse(
				200,
				'{"network":[{"url":"https://codeberg.org/joomla/power","status":1}]}'
			)
		);
		$transport->attachTo($http);
		$api = new NetworkApi($http, new Uri('https://network.example.test', 'v1'), new Response());
		$core = new Core();
		$status = new Status($api, $core, new Url(new ParsedUrls()));

		$first = $status->network('power');
		$second = $status->network('power');

		$this->assertSame($first, $second);
		$this->assertSame($first, $core->get('power'));
		$this->assertCount(1, $transport->requests);
		$this->assertSame('GET', $transport->requests[0]['method']);
		$this->assertSame(
			'https://network.example.test/v1/network/community/jcb/power',
			$transport->requests[0]['uri']
		);
	}

	/**
	 * Protect coordinate filtering, status tri-state, and default exclusion.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStatusFiltersCachedNetworkAndSelectsActiveFallback(): void
	{
		[$status, $core] = $this->cachedStatus();

		$this->assertSame(0, $status->get('power', 'https://git.vdm.dev', 'power', 'joomla'));
		$this->assertSame(1, $status->get('power', 'codeberg.org', 'power', 'mirror'));
		$this->assertSame(-1, $status->get('power', 'unknown.example', 'power', 'joomla'));
		$this->assertSame('https://codeberg.org/mirror/power', $status->active('power')->url);
		$this->assertNull($status->active('power', ['git.vdm.dev', 'codeberg.org']));
		$this->assertSame($core->get('power'), $status->network('power'));
	}

	/**
	 * Protect by-reference failover to a healthy repository coordinate set.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolveRewritesInactiveApiCoordinatesToActiveMirror(): void
	{
		[$status] = $this->cachedStatus();
		$subject = new Resolve(new Url(new ParsedUrls()), $status);
		$domain = 'https://git.vdm.dev';
		$organization = 'joomla';
		$repository = 'power';

		$subject->api('power', $domain, $organization, $repository);

		$this->assertSame('https://codeberg.org', $domain);
		$this->assertSame('mirror', $organization);
		$this->assertSame('power', $repository);
	}

	/**
	 * Protect the rule that unknown non-core repositories are not rewritten.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolveLeavesUnknownCoordinatesUntouched(): void
	{
		[$status] = $this->cachedStatus();
		$subject = new Resolve(new Url(new ParsedUrls()), $status);
		$domain = 'https://unknown.example';
		$organization = 'acme';
		$repository = 'custom';

		$subject->api('power', $domain, $organization, $repository);

		$this->assertSame('https://unknown.example', $domain);
		$this->assertSame('acme', $organization);
		$this->assertSame('custom', $repository);
	}

	/**
	 * Build a status service over deterministic cached network data.
	 *
	 * @return  array{0: Status, 1: Core}
	 * @since   6.1.6
	 */
	private function cachedStatus(): array
	{
		$core = new Core();
		$core->set('power', (object) [
			'network' => [
				(object) ['url' => 'https://git.vdm.dev/joomla/power', 'status' => 0],
				(object) ['url' => 'https://codeberg.org/mirror/power', 'status' => 1],
			],
		]);
		$http = new Http();
		(new RecordingHttpTransport())->attachTo($http);
		$network = new NetworkApi($http, new Uri(), new Response());
		$status = new Status($network, $core, new Url(new ParsedUrls()));

		return [$status, $core];
	}
}
