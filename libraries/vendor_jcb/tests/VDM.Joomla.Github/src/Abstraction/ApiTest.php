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

namespace VDM\Joomla\Github\Tests\Abstraction;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Github\Abstraction\Api;
use VDM\Joomla\Github\Utilities\Http;
use VDM\Joomla\Github\Utilities\Response;
use VDM\Joomla\Github\Utilities\Uri;


/**
 * GitHub API state lifecycle tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Api::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class ApiTest extends TestCase
{
	/**
	 * Expose the configured API root through the shared URI utility.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testApiReturnsConfiguredApiRoot(): void
	{
		[$subject] = $this->createSubject();

		$this->assertSame('https://github.example.test/root/', $subject->api());
	}

	/**
	 * Back up a swapped token and restore it exactly once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLoadAndResetRestoreBackedUpToken(): void
	{
		[$subject, $http] = $this->createSubject('original-token');

		$subject->load_('https://ignored.example.test', 'temporary-token');
		$this->assertSame('temporary-token', $http->getToken());
		$this->assertSame('https://github.example.test/root/', $subject->api());

		$subject->reset_();
		$this->assertSame('original-token', $http->getToken());

		$subject->reset_();
		$this->assertSame('original-token', $http->getToken());
	}

	/**
	 * Keep an unbacked token swap and ignore a null-token load.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUnbackedAndNullLoadsDoNotScheduleRestoration(): void
	{
		[$subject, $http] = $this->createSubject('original-token');

		$subject->load_(null, 'persistent-token', false);
		$subject->reset_();
		$this->assertSame('persistent-token', $http->getToken());

		$subject->load_('https://also-ignored.example.test', null);
		$subject->reset_();
		$this->assertSame('persistent-token', $http->getToken());
		$this->assertSame('https://github.example.test/root/', $subject->api());
	}

	/**
	 * Build a concrete API fixture around production utilities.
	 *
	 * @param   string|null  $token  Initial access token.
	 *
	 * @return  array{0: Api, 1: Http}
	 * @since   6.1.6
	 */
	private function createSubject(?string $token = null): array
	{
		$http = new Http($token);
		$subject = new class($http, new Uri('https://github.example.test/root'), new Response()) extends Api
		{
		};

		return [$subject, $http];
	}
}
