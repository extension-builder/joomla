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

namespace VDM\Joomla\Gitea\Tests\Contract;


use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Gitea\Abstraction\Api;
use VDM\Joomla\Gitea\Tests\Support\ApiTestCase;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Gitea\Utilities\Uri;


/**
 * Request contracts for organization, package, and authenticated-user APIs.
 *
 * @since  1.0.0
 */
#[CoversClass(\VDM\Joomla\Gitea\Organization::class)]
#[CoversClass(\VDM\Joomla\Gitea\Organization\Hooks::class)]
#[CoversClass(\VDM\Joomla\Gitea\Organization\Labels::class)]
#[CoversClass(\VDM\Joomla\Gitea\Organization\Repository::class)]
#[CoversClass(\VDM\Joomla\Gitea\Organization\Teams::class)]
#[CoversClass(\VDM\Joomla\Gitea\Organization\Teams\Repository::class)]
#[CoversClass(\VDM\Joomla\Gitea\Organization\User::class)]
#[CoversClass(\VDM\Joomla\Gitea\Package::class)]
#[CoversClass(\VDM\Joomla\Gitea\Package\Files::class)]
#[CoversClass(\VDM\Joomla\Gitea\Package\Owner::class)]
#[CoversClass(\VDM\Joomla\Gitea\User::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Applications::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Followers::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Gpg::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Keys::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Repos::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Settings::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Subscriptions::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Teams::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Times::class)]
#[UsesClass(Api::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class OrganizationPackageUserEndpointRequestContractsTest extends ApiTestCase
{
	/**
	 * Assert one complete request and response-mapping contract.
	 *
	 * @param   class-string  $class           Endpoint class.
	 * @param   Closure       $invoke          Endpoint invocation.
	 * @param   int           $responseStatus  Queued response status.
	 * @param   string        $responseBody    Queued response body.
	 * @param   mixed         $expectedResult  Expected mapped result.
	 * @param   string        $method          Expected HTTP method.
	 * @param   string        $uri             Expected absolute URI.
	 * @param   mixed         $data            Expected request data.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('endpointContracts')]
	public function testEndpointRequestContract(
		string $class,
		Closure $invoke,
		int $responseStatus,
		string $responseBody,
		mixed $expectedResult,
		string $method,
		string $uri,
		mixed $data
	): void
	{
		[$subject, $transport] = $this->endpoint($class, $responseStatus, $responseBody);

		$this->assertEquals($expectedResult, $invoke($subject));
		$this->assertCount(1, $transport->requests());
		$this->assertRequest($transport, $method, $uri, $data);
	}

	/**
	 * Provide organization, package, and authenticated-user contracts.
	 *
	 * @return  iterable<string, array{class-string, Closure, int, string, mixed, string, string, mixed}>
	 * @since   1.0.0
	 */
	public static function endpointContracts(): iterable
	{
		$objectBody = '{"id":42,"name":"sample"}';
		$objectResult = json_decode($objectBody);
		$listBody = '[{"id":42},{"id":43}]';
		$listResult = json_decode($listBody);

		yield 'get organization' => [
			\VDM\Joomla\Gitea\Organization::class,
			static fn (object $api): mixed => $api->get('acme'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/orgs/acme',
			null
		];

		yield 'get organization hook' => [
			\VDM\Joomla\Gitea\Organization\Hooks::class,
			static fn (object $api): mixed => $api->get('acme', 7),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/orgs/acme/hooks/7',
			null
		];

		yield 'get organization label' => [
			\VDM\Joomla\Gitea\Organization\Labels::class,
			static fn (object $api): mixed => $api->get('acme', 9),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/orgs/acme/labels/9',
			null
		];

		yield 'list organization repositories' => [
			\VDM\Joomla\Gitea\Organization\Repository::class,
			static fn (object $api): mixed => $api->list('acme', 2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/orgs/acme/repos?page=2&limit=15',
			null
		];

		yield 'get organization team' => [
			\VDM\Joomla\Gitea\Organization\Teams::class,
			static fn (object $api): mixed => $api->get(42),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/teams/42',
			null
		];

		yield 'list team repositories' => [
			\VDM\Joomla\Gitea\Organization\Teams\Repository::class,
			static fn (object $api): mixed => $api->list(42, 2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/teams/42/repos?page=2&limit=15',
			null
		];

		yield 'get organization permissions for user' => [
			\VDM\Joomla\Gitea\Organization\User::class,
			static fn (object $api): mixed => $api->permissions('alice', 'acme'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/users/alice/orgs/acme/permissions',
			null
		];

		yield 'get package' => [
			\VDM\Joomla\Gitea\Package::class,
			static fn (object $api): mixed => $api->get('acme', 'composer', 'widget', '1.2.3'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/packages/acme/composer/widget/1.2.3',
			null
		];

		yield 'get package files' => [
			\VDM\Joomla\Gitea\Package\Files::class,
			static fn (object $api): mixed => $api->get('acme', 'composer', 'widget', '1.2.3'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/packages/acme/composer/widget/1.2.3/files',
			null
		];

		yield 'list owner packages with filters' => [
			\VDM\Joomla\Gitea\Package\Owner::class,
			static fn (object $api): mixed => $api->get('acme', 2, 15, 'composer', 'widget'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/packages/acme?page=2&limit=15&type=composer&q=widget',
			null
		];

		yield 'authenticate current user' => [
			\VDM\Joomla\Gitea\User::class,
			static fn (object $api): mixed => $api->authenticate(),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/user',
			null
		];

		yield 'get OAuth application' => [
			\VDM\Joomla\Gitea\User\Applications::class,
			static fn (object $api): mixed => $api->id(7),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/user/applications/oauth2/7',
			null
		];

		yield 'list followers' => [
			\VDM\Joomla\Gitea\User\Followers::class,
			static fn (object $api): mixed => $api->list(2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/user/followers?page=2&limit=15',
			null
		];

		yield 'get user GPG key' => [
			\VDM\Joomla\Gitea\User\Gpg::class,
			static fn (object $api): mixed => $api->get(7),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/user/gpg_keys/7',
			null
		];

		yield 'get user SSH key' => [
			\VDM\Joomla\Gitea\User\Keys::class,
			static fn (object $api): mixed => $api->get(7),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/user/keys/7',
			null
		];

		yield 'list authenticated user repositories' => [
			\VDM\Joomla\Gitea\User\Repos::class,
			static fn (object $api): mixed => $api->list(2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/user/repos?page=2&limit=15',
			null
		];

		yield 'get user settings' => [
			\VDM\Joomla\Gitea\User\Settings::class,
			static fn (object $api): mixed => $api->get(),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/user/settings',
			null
		];

		yield 'list user subscriptions' => [
			\VDM\Joomla\Gitea\User\Subscriptions::class,
			static fn (object $api): mixed => $api->list(2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/user/subscriptions?page=2&limit=15',
			null
		];

		yield 'list current user teams' => [
			\VDM\Joomla\Gitea\User\Teams::class,
			static fn (object $api): mixed => $api->list(2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/user/teams?page=2&limit=15',
			null
		];

		yield 'list current user times with time window' => [
			\VDM\Joomla\Gitea\User\Times::class,
			static fn (object $api): mixed => $api->list(
				2,
				15,
				'2026-08-01T00:00:00Z',
				'2026-08-31T23:59:59Z'
			),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/user/times?page=2&limit=15&since=2026-08-01T00:00:00Z'
				. '&before=2026-08-31T23:59:59Z',
			null
		];
	}
}
