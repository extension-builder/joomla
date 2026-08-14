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
 * Request contracts for the remaining administration, issue, and notification APIs.
 *
 * @since  1.0.0
 */
#[CoversClass(\VDM\Joomla\Gitea\Admin\Users::class)]
#[CoversClass(\VDM\Joomla\Gitea\Admin\Users\Keys::class)]
#[CoversClass(\VDM\Joomla\Gitea\Admin\Users\Organization::class)]
#[CoversClass(\VDM\Joomla\Gitea\Admin\Users\Repository::class)]
#[CoversClass(\VDM\Joomla\Gitea\Issue::class)]
#[CoversClass(\VDM\Joomla\Gitea\Issue\Comments::class)]
#[CoversClass(\VDM\Joomla\Gitea\Issue\Labels::class)]
#[CoversClass(\VDM\Joomla\Gitea\Issue\Milestones::class)]
#[CoversClass(\VDM\Joomla\Gitea\Issue\Reactions::class)]
#[CoversClass(\VDM\Joomla\Gitea\Issue\Repository\Comments::class)]
#[CoversClass(\VDM\Joomla\Gitea\Issue\Subscriptions::class)]
#[CoversClass(\VDM\Joomla\Gitea\Issue\Timeline::class)]
#[CoversClass(\VDM\Joomla\Gitea\Issue\Times::class)]
#[CoversClass(\VDM\Joomla\Gitea\Labels::class)]
#[CoversClass(\VDM\Joomla\Gitea\Notifications::class)]
#[CoversClass(\VDM\Joomla\Gitea\Notifications\Repository::class)]
#[UsesClass(Api::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class AdministrativeEndpointRequestContractsTest extends ApiTestCase
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
	 * Provide administration, issue, and notification contracts.
	 *
	 * @return  iterable<string, array{class-string, Closure, int, string, mixed, string, string, mixed}>
	 * @since   1.0.0
	 */
	public static function endpointContracts(): iterable
	{
		$objectBody = '{"id":17,"name":"sample"}';
		$objectResult = json_decode($objectBody);
		$listBody = '[{"id":17},{"id":18}]';
		$listResult = json_decode($listBody);

		yield 'admin users pagination' => [
			\VDM\Joomla\Gitea\Admin\Users::class,
			static fn (object $api): mixed => $api->list(2, 20),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/admin/users?page=2&limit=20',
			null
		];

		yield 'delete admin user key' => [
			\VDM\Joomla\Gitea\Admin\Users\Keys::class,
			static fn (object $api): mixed => $api->delete('alice', 7),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/admin/users/alice/keys/7',
			null
		];

		yield 'create organization for user with explicit defaults' => [
			\VDM\Joomla\Gitea\Admin\Users\Organization::class,
			static fn (object $api): mixed => $api->create('alice', 'Acme Incorporated'),
			201,
			$objectBody,
			$objectResult,
			'POST',
			self::API_ROOT . '/admin/users/alice/orgs',
			'{"full_name":"Acme Incorporated","description":null,"location":null,'
				. '"repo_admin_change_team_access":false,"visibility":"public","website":null}'
		];

		yield 'create repository for user with explicit defaults' => [
			\VDM\Joomla\Gitea\Admin\Users\Repository::class,
			static fn (object $api): mixed => $api->create('alice', 'widget'),
			201,
			$objectBody,
			$objectResult,
			'POST',
			self::API_ROOT . '/admin/users/alice/repos',
			'{"name":"widget","description":null,"auto_init":false,"default_branch":null,'
				. '"gitignores":null,"issue_labels":null,"license":null,"private":false,'
				. '"readme":null,"template":false,"trust_model":null}'
		];

		yield 'get issue' => [
			\VDM\Joomla\Gitea\Issue::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 17),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issues/17',
			null
		];

		yield 'get issue comment' => [
			\VDM\Joomla\Gitea\Issue\Comments::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 91),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issues/comments/91',
			null
		];

		yield 'get issue labels' => [
			\VDM\Joomla\Gitea\Issue\Labels::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 17),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issues/17/labels',
			null
		];

		yield 'get milestone' => [
			\VDM\Joomla\Gitea\Issue\Milestones::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', '5'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/milestones/5',
			null
		];

		yield 'list issue reactions' => [
			\VDM\Joomla\Gitea\Issue\Reactions::class,
			static fn (object $api): mixed => $api->list('acme', 'widget', 17, 2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issues/17/reactions?page=2&limit=15',
			null
		];

		yield 'list repository issue comments with time window' => [
			\VDM\Joomla\Gitea\Issue\Repository\Comments::class,
			static fn (object $api): mixed => $api->list(
				'acme',
				'widget',
				2,
				25,
				'2026-08-01T00:00:00Z',
				'2026-08-31T23:59:59Z'
			),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issues/comments?page=2&limit=25'
				. '&since=2026-08-01T00:00:00Z&before=2026-08-31T23:59:59Z',
			null
		];

		yield 'get issue subscriptions with pagination' => [
			\VDM\Joomla\Gitea\Issue\Subscriptions::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 17, 2, 15),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issues/17/subscriptions?page=2&limit=15',
			null
		];

		yield 'get issue timeline with all optional filters' => [
			\VDM\Joomla\Gitea\Issue\Timeline::class,
			static fn (object $api): mixed => $api->get(
				'acme',
				'widget',
				17,
				'2026-08-01T00:00:00Z',
				2,
				15,
				'2026-08-31T23:59:59Z'
			),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issues/17/timeline?since=2026-08-01T00:00:00Z'
				. '&page=2&limit=15&before=2026-08-31T23:59:59Z',
			null
		];

		yield 'list issue times with all optional filters' => [
			\VDM\Joomla\Gitea\Issue\Times::class,
			static fn (object $api): mixed => $api->list(
				'acme',
				'widget',
				17,
				'alice',
				'2026-08-01T00:00:00Z',
				'2026-08-31T23:59:59Z',
				2,
				15
			),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issues/17/times?user=alice'
				. '&since=2026-08-01T00:00:00Z&before=2026-08-31T23:59:59Z&page=2&limit=15',
			null
		];

		yield 'get repository label' => [
			\VDM\Joomla\Gitea\Labels::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', '9'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/labels/9',
			null
		];

		yield 'check unread notifications' => [
			\VDM\Joomla\Gitea\Notifications::class,
			static fn (object $api): mixed => $api->check(),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/notifications/new',
			null
		];

		yield 'repository notifications use default pagination' => [
			\VDM\Joomla\Gitea\Notifications\Repository::class,
			static fn (object $api): mixed => $api->get('acme', 'widget'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/notifications?page=1&limit=10',
			null
		];
	}
}
