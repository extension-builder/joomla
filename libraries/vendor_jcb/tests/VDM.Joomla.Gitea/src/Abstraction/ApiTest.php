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

namespace VDM\Joomla\Gitea\Tests\Abstraction;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Gitea\Abstraction\Api;
use VDM\Joomla\Gitea\Tests\Support\ApiTestCase;
use VDM\Joomla\Gitea\Tests\Support\RecordingTransport;
use VDM\Joomla\Gitea\Utilities\Http;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Gitea\Utilities\Uri;


/**
 * Gitea API base state-switching contract tests.
 *
 * @since  1.0.0
 */
#[CoversClass(Api::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class ApiTest extends ApiTestCase
{
	/**
	 * Temporarily replace URL and token, then restore both original values.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testLoadAndResetRestoreBackedUpConnectionState(): void
	{
		$http = $this->http(new RecordingTransport(), 'original-token');
		$uri = new Uri('https://original.example');
		$subject = new class($http, $uri, new Response()) extends Api
		{
		};

		$subject->load_('https://alternate.example', 'temporary-token');

		$this->assertSame('https://alternate.example', $uri->getUrl());
		$this->assertSame('temporary-token', $http->getToken());
		$this->assertSame('https://alternate.example/api/v1', $subject->api());

		$subject->reset_();

		$this->assertSame('https://original.example', $uri->getUrl());
		$this->assertSame('original-token', $http->getToken());
		$this->assertSame('https://original.example/api/v1', $subject->api());
	}

	/**
	 * Suppress backup when a caller deliberately wants a persistent state change.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testLoadWithoutBackupMakesResetANoOp(): void
	{
		$http = $this->http(new RecordingTransport(), 'original-token');
		$uri = new Uri('https://original.example');
		$subject = new class($http, $uri, new Response()) extends Api
		{
		};

		$subject->load_('https://persistent.example', 'persistent-token', false);
		$subject->reset_();

		$this->assertSame('https://persistent.example', $uri->getUrl());
		$this->assertSame('persistent-token', $http->getToken());
	}

	/**
	 * A null override must leave the corresponding connection setting untouched.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testNullOverridesDoNotDisturbExistingState(): void
	{
		$http = $this->http(new RecordingTransport(), 'original-token');
		$uri = new Uri('https://original.example');
		$subject = new class($http, $uri, new Response()) extends Api
		{
		};

		$subject->load_(null, null);
		$subject->reset_();

		$this->assertSame('https://original.example', $uri->getUrl());
		$this->assertSame('original-token', $http->getToken());
	}
}
