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
use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Gitea\Abstraction\Api;
use VDM\Joomla\Gitea\Admin\Cron;
use VDM\Joomla\Gitea\Admin\Organizations as AdminOrganizations;
use VDM\Joomla\Gitea\Admin\Unadopted;
use VDM\Joomla\Gitea\Issue\Deadline;
use VDM\Joomla\Gitea\Issue\Reactions\Comment as CommentReactions;
use VDM\Joomla\Gitea\Issue\Stopwatch;
use VDM\Joomla\Gitea\Miscellaneous\Activitypub;
use VDM\Joomla\Gitea\Miscellaneous\Gpg;
use VDM\Joomla\Gitea\Miscellaneous\Markdown;
use VDM\Joomla\Gitea\Miscellaneous\NodeInfo;
use VDM\Joomla\Gitea\Miscellaneous\Version;
use VDM\Joomla\Gitea\Notifications\Thread;
use VDM\Joomla\Gitea\Organization\Members as OrganizationMembers;
use VDM\Joomla\Gitea\Organization\PublicMembers;
use VDM\Joomla\Gitea\Organization\Teams\Members as TeamMembers;
use VDM\Joomla\Gitea\Repository\Languages;
use VDM\Joomla\Gitea\Repository\Refs;
use VDM\Joomla\Gitea\Repository\Teams as RepositoryTeams;
use VDM\Joomla\Gitea\Repository\Topics;
use VDM\Joomla\Gitea\Settings\Api as ApiSettings;
use VDM\Joomla\Gitea\Settings\Attachment as AttachmentSettings;
use VDM\Joomla\Gitea\Settings\Repository as RepositorySettings;
use VDM\Joomla\Gitea\Settings\Ui as UiSettings;
use VDM\Joomla\Gitea\Tests\Support\ApiTestCase;
use VDM\Joomla\Gitea\User\Emails;
use VDM\Joomla\Gitea\User\Following;
use VDM\Joomla\Gitea\User\Starred;
use VDM\Joomla\Gitea\User\Tokens;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Gitea\Utilities\Uri;


/**
 * Data-driven HTTP contracts for Gitea endpoint classes.
 *
 * Each case asserts the exact HTTP method, absolute URI, encoded request body,
 * merged headers, response status expectation, and mapped return value.
 *
 * @since  1.0.0
 */
#[CoversClass(Cron::class)]
#[CoversClass(AdminOrganizations::class)]
#[CoversClass(Unadopted::class)]
#[CoversClass(Deadline::class)]
#[CoversClass(CommentReactions::class)]
#[CoversClass(Stopwatch::class)]
#[CoversClass(Activitypub::class)]
#[CoversClass(Gpg::class)]
#[CoversClass(Markdown::class)]
#[CoversClass(NodeInfo::class)]
#[CoversClass(Version::class)]
#[CoversClass(Thread::class)]
#[CoversClass(OrganizationMembers::class)]
#[CoversClass(PublicMembers::class)]
#[CoversClass(TeamMembers::class)]
#[CoversClass(Languages::class)]
#[CoversClass(Refs::class)]
#[CoversClass(RepositoryTeams::class)]
#[CoversClass(Topics::class)]
#[CoversClass(ApiSettings::class)]
#[CoversClass(AttachmentSettings::class)]
#[CoversClass(RepositorySettings::class)]
#[CoversClass(UiSettings::class)]
#[CoversClass(Emails::class)]
#[CoversClass(Following::class)]
#[CoversClass(Starred::class)]
#[CoversClass(Tokens::class)]
#[UsesClass(Api::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class EndpointRequestContractsTest extends ApiTestCase
{
	/**
	 * Verify one complete endpoint request/response contract.
	 *
	 * @param   class-string  $class           Endpoint class.
	 * @param   Closure       $invoke          Endpoint invocation.
	 * @param   int           $responseStatus  Queued response status.
	 * @param   string        $responseBody    Queued response body.
	 * @param   mixed         $expectedResult  Expected mapped result.
	 * @param   string        $method          Expected HTTP method.
	 * @param   string        $uri             Expected absolute URI.
	 * @param   mixed         $data            Expected request data.
	 * @param   array|null    $headers         Expected headers, or standard headers.
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
		mixed $data,
		?array $headers
	): void
	{
		[$subject, $transport] = $this->endpoint($class, $responseStatus, $responseBody);

		$result = $invoke($subject);

		$this->assertEquals($expectedResult, $result);
		$this->assertCount(1, $transport->requests());
		$this->assertRequest($transport, $method, $uri, $data, $headers);
	}

	/**
	 * Preserve the response error while retaining the exact outgoing request.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testEndpointPropagatesMappedApiError(): void
	{
		[$subject, $transport] = $this->endpoint(
			Version::class,
			404,
			'{"message":"version endpoint disabled"}'
		);

		try
		{
			$subject->get();
			$this->fail('The endpoint must propagate a response status mismatch.');
		}
		catch (DomainException $error)
		{
			$this->assertSame(404, $error->getCode());
			$this->assertSame(
				'Invalid response received from Gitea API. version endpoint disabled',
				$error->getMessage()
			);
		}

		$this->assertRequest($transport, 'GET', self::API_ROOT . '/version');
	}

	/**
	 * Provide representative success, optional-field, and mutation contracts.
	 *
	 * @return  iterable<string, array{class-string, Closure, int, string, mixed, string, string, mixed, array|null}>
	 * @since   1.0.0
	 */
	public static function endpointContracts(): iterable
	{
		$objectBody = '{"id":7,"name":"sample"}';
		$objectResult = json_decode($objectBody);
		$listBody = '[{"id":7},{"id":8}]';
		$listResult = json_decode($listBody);

		yield 'application version' => [
			Version::class,
			static fn (object $api): mixed => $api->get(),
			200,
			'{"version":"1.22.0"}',
			json_decode('{"version":"1.22.0"}'),
			'GET',
			self::API_ROOT . '/version',
			null,
			null
		];

		yield 'node information' => [
			NodeInfo::class,
			static fn (object $api): mixed => $api->get(),
			200,
			'{"software":{"name":"gitea"}}',
			json_decode('{"software":{"name":"gitea"}}'),
			'GET',
			self::API_ROOT . '/nodeinfo',
			null,
			null
		];

		yield 'default signing key' => [
			Gpg::class,
			static fn (object $api): mixed => $api->get(),
			200,
			'-----BEGIN PGP PUBLIC KEY BLOCK-----',
			'-----BEGIN PGP PUBLIC KEY BLOCK-----',
			'GET',
			self::API_ROOT . '/signing-key.gpg',
			null,
			null
		];

		yield 'activitypub actor' => [
			Activitypub::class,
			static fn (object $api): mixed => $api->get('alice'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/activitypub/user/alice',
			null,
			null
		];

		yield 'activitypub inbox' => [
			Activitypub::class,
			static fn (object $api): mixed => $api->send(
				'alice',
				(object) ['type' => 'Create', 'id' => 'urn:activity:1']
			),
			204,
			'',
			'success',
			'POST',
			self::API_ROOT . '/activitypub/user/alice/inbox',
			'{"type":"Create","id":"urn:activity:1"}',
			null
		];

		yield 'render markdown with explicit options' => [
			Markdown::class,
			static fn (object $api): mixed => $api->render('**bold**', true, 'acme/repo', 'gfm'),
			200,
			'<strong>bold</strong>',
			'<strong>bold</strong>',
			'POST',
			self::API_ROOT . '/markdown',
			'{"Text":"**bold**","Wiki":true,"Context":"acme\\/repo","Mode":"gfm"}',
			[
				'accept' => 'text/html',
				'Content-Type' => 'application/json',
				'Authorization' => 'token unit-token'
			]
		];

		yield 'render raw markdown' => [
			Markdown::class,
			static fn (object $api): mixed => $api->raw('# Title'),
			200,
			'<h1>Title</h1>',
			'<h1>Title</h1>',
			'POST',
			self::API_ROOT . '/markdown/raw',
			'# Title',
			[
				'Content-Type' => 'text/plain',
				'accept' => 'text/html',
				'Authorization' => 'token unit-token'
			]
		];

		foreach ([
			'api settings' => [ApiSettings::class, '/settings/api'],
			'attachment settings' => [AttachmentSettings::class, '/settings/attachment'],
			'repository settings' => [RepositorySettings::class, '/settings/repository'],
			'ui settings' => [UiSettings::class, '/settings/ui']
		] as $name => [$class, $path])
		{
			yield $name => [
				$class,
				static fn (object $api): mixed => $api->get(),
				200,
				$objectBody,
				$objectResult,
				'GET',
				self::API_ROOT . $path,
				null,
				null
			];
		}

		yield 'cron pagination' => [
			Cron::class,
			static fn (object $api): mixed => $api->list(3, 25),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/admin/cron?page=3&limit=25',
			null,
			null
		];

		yield 'admin organization pagination' => [
			AdminOrganizations::class,
			static fn (object $api): mixed => $api->list(2, 50),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/admin/orgs?page=2&limit=50',
			null,
			null
		];

		yield 'unadopted optional pattern' => [
			Unadopted::class,
			static fn (object $api): mixed => $api->list(4, 15, 'needle'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/admin/unadopted?page=4&limit=15&pattern=needle',
			null,
			null
		];

		yield 'adopt repository files' => [
			Unadopted::class,
			static fn (object $api): mixed => $api->adopt('acme', 'widget'),
			204,
			'',
			'success',
			'POST',
			self::API_ROOT . '/admin/unadopted/acme/widget',
			'',
			null
		];

		yield 'delete unadopted files' => [
			Unadopted::class,
			static fn (object $api): mixed => $api->delete('acme', 'widget'),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/admin/unadopted/acme/widget',
			null,
			null
		];

		yield 'list user emails' => [
			Emails::class,
			static fn (object $api): mixed => $api->list(),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/user/emails',
			null,
			null
		];

		yield 'add user emails' => [
			Emails::class,
			static fn (object $api): mixed => $api->add(['first@example.com', 'second@example.com']),
			201,
			$listBody,
			$listResult,
			'POST',
			self::API_ROOT . '/user/emails',
			'{"emails":["first@example.com","second@example.com"]}',
			null
		];

		yield 'delete user emails through query' => [
			Emails::class,
			static fn (object $api): mixed => $api->delete(['first@example.com', 'second@example.com']),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/user/emails?emails=["first@example.com","second@example.com"]',
			null,
			null
		];

		yield 'list followed users' => [
			Following::class,
			static fn (object $api): mixed => $api->list(2, 40),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/user/following?page=2&limit=40',
			null,
			null
		];

		yield 'follow user' => [
			Following::class,
			static fn (object $api): mixed => $api->follow('alice'),
			204,
			'',
			'success',
			'PUT',
			self::API_ROOT . '/user/following/alice',
			'',
			null
		];

		yield 'unfollow user' => [
			Following::class,
			static fn (object $api): mixed => $api->unfollow('alice'),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/user/following/alice',
			null,
			null
		];

		yield 'list starred repositories' => [
			Starred::class,
			static fn (object $api): mixed => $api->list(5, 12),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/user/starred?page=5&limit=12',
			null,
			null
		];

		yield 'check starred repository' => [
			Starred::class,
			static fn (object $api): mixed => $api->check('acme', 'widget'),
			204,
			'',
			'success',
			'GET',
			self::API_ROOT . '/user/starred/acme/widget',
			null,
			null
		];

		yield 'tokens omit null pagination' => [
			Tokens::class,
			static fn (object $api): mixed => $api->list('alice'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/users/alice/tokens',
			null,
			null
		];

		yield 'tokens include pagination' => [
			Tokens::class,
			static fn (object $api): mixed => $api->list('alice', 2, 30),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/users/alice/tokens?page=2&limit=30',
			null,
			null
		];

		yield 'create user token' => [
			Tokens::class,
			static fn (object $api): mixed => $api->create('alice', 'automation'),
			201,
			$objectBody,
			$objectResult,
			'POST',
			self::API_ROOT . '/users/alice/tokens',
			'{"name":"automation"}',
			null
		];

		yield 'delete user token' => [
			Tokens::class,
			static fn (object $api): mixed => $api->delete('alice', 'token-id'),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/users/alice/tokens/token-id',
			null,
			null
		];

		yield 'start issue stopwatch' => [
			Stopwatch::class,
			static fn (object $api): mixed => $api->start('acme', 'widget', 17),
			201,
			'',
			'success',
			'POST',
			self::API_ROOT . '/repos/acme/widget/issues/17/stopwatch/start',
			'',
			null
		];

		yield 'stop issue stopwatch' => [
			Stopwatch::class,
			static fn (object $api): mixed => $api->stop('acme', 'widget', 17),
			201,
			'',
			'success',
			'POST',
			self::API_ROOT . '/repos/acme/widget/issues/17/stopwatch/stop',
			'',
			null
		];

		yield 'delete issue stopwatch' => [
			Stopwatch::class,
			static fn (object $api): mixed => $api->delete('acme', 'widget', 17),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/repos/acme/widget/issues/17/stopwatch/delete',
			null,
			null
		];

		yield 'set issue deadline' => [
			Deadline::class,
			static fn (object $api): mixed => $api->set('acme', 'widget', 17, '2026-09-01'),
			200,
			$objectBody,
			$objectResult,
			'POST',
			self::API_ROOT . '/repos/acme/widget/issues/17/deadline',
			'{"due_date":"2026-09-01"}',
			null
		];

		yield 'clear issue deadline with explicit null' => [
			Deadline::class,
			static fn (object $api): mixed => $api->set('acme', 'widget', 17, null),
			200,
			$objectBody,
			$objectResult,
			'POST',
			self::API_ROOT . '/repos/acme/widget/issues/17/deadline',
			'{"due_date":null}',
			null
		];

		yield 'list comment reactions' => [
			CommentReactions::class,
			static fn (object $api): mixed => $api->list('acme', 'widget', 99),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issues/comments/99/reactions',
			null,
			null
		];

		yield 'add comment reaction' => [
			CommentReactions::class,
			static fn (object $api): mixed => $api->add('acme', 'widget', 99, '+1'),
			200,
			$objectBody,
			$objectResult,
			'POST',
			self::API_ROOT . '/repos/acme/widget/issues/comments/99/reactions',
			'{"content":"+1"}',
			null
		];

		yield 'remove comment reaction through query' => [
			CommentReactions::class,
			static fn (object $api): mixed => $api->remove('acme', 'widget', 99, '+1'),
			200,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/repos/acme/widget/issues/comments/99/reactions?content=+1',
			null,
			null
		];

		yield 'list organization members' => [
			OrganizationMembers::class,
			static fn (object $api): mixed => $api->list('acme', 2, 18),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/orgs/acme/members?page=2&limit=18',
			null,
			null
		];

		yield 'check organization membership' => [
			OrganizationMembers::class,
			static fn (object $api): mixed => $api->check('acme', 'alice'),
			204,
			'',
			'success',
			'GET',
			self::API_ROOT . '/orgs/acme/members/alice',
			null,
			null
		];

		yield 'remove organization member' => [
			OrganizationMembers::class,
			static fn (object $api): mixed => $api->remove('acme', 'alice'),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/orgs/acme/members/alice',
			null,
			null
		];

		yield 'list public members' => [
			PublicMembers::class,
			static fn (object $api): mixed => $api->list('acme', 3, 21),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/orgs/acme/public_members?page=3&limit=21',
			null,
			null
		];

		yield 'publicize member' => [
			PublicMembers::class,
			static fn (object $api): mixed => $api->publicize('acme', 'alice'),
			204,
			'',
			null,
			'PUT',
			self::API_ROOT . '/orgs/acme/public_members/alice',
			'',
			null
		];

		yield 'conceal member' => [
			PublicMembers::class,
			static fn (object $api): mixed => $api->conceal('acme', 'alice'),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/orgs/acme/public_members/alice',
			null,
			null
		];

		yield 'list team members' => [
			TeamMembers::class,
			static fn (object $api): mixed => $api->list(42, 2, 16),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/teams/42/members?page=2&limit=16',
			null,
			null
		];

		yield 'add team member' => [
			TeamMembers::class,
			static fn (object $api): mixed => $api->add(42, 'alice'),
			204,
			'',
			'success',
			'PUT',
			self::API_ROOT . '/teams/42/members/alice',
			'',
			null
		];

		yield 'remove team member' => [
			TeamMembers::class,
			static fn (object $api): mixed => $api->remove(42, 'alice'),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/teams/42/members/alice',
			null,
			null
		];

		yield 'repository languages' => [
			Languages::class,
			static fn (object $api): mixed => $api->getLanguages('acme', 'widget'),
			200,
			'{"PHP":1200,"JavaScript":300}',
			json_decode('{"PHP":1200,"JavaScript":300}'),
			'GET',
			self::API_ROOT . '/repos/acme/widget/languages',
			null,
			null
		];

		yield 'repository refs' => [
			Refs::class,
			static fn (object $api): mixed => $api->list('acme', 'widget'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/git/refs',
			null,
			null
		];

		yield 'specific repository ref' => [
			Refs::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 'heads/main'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/git/refs/heads/main',
			null,
			null
		];

		yield 'repository teams' => [
			RepositoryTeams::class,
			static fn (object $api): mixed => $api->list('acme', 'widget'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/teams',
			null,
			null
		];

		yield 'add repository team' => [
			RepositoryTeams::class,
			static fn (object $api): mixed => $api->add('acme', 'widget', 'maintainers'),
			204,
			'',
			'success',
			'PUT',
			self::API_ROOT . '/repos/acme/widget/teams/maintainers',
			'',
			null
		];

		yield 'delete repository team' => [
			RepositoryTeams::class,
			static fn (object $api): mixed => $api->delete('acme', 'widget', 'maintainers'),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/repos/acme/widget/teams/maintainers',
			null,
			null
		];

		yield 'repository topics pagination' => [
			Topics::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 2, 20),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/topics?page=2&limit=20',
			null,
			null
		];

		yield 'replace repository topics' => [
			Topics::class,
			static fn (object $api): mixed => $api->replace('acme', 'widget', ['php', 'joomla']),
			204,
			'',
			'success',
			'PUT',
			self::API_ROOT . '/repos/acme/widget/topics',
			'{"topics":["php","joomla"]}',
			null
		];

		yield 'search topics' => [
			Topics::class,
			static fn (object $api): mixed => $api->search('component-builder', 3, 14),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/topics/search?q=component-builder&page=3&limit=14',
			null,
			null
		];

		yield 'notification thread' => [
			Thread::class,
			static fn (object $api): mixed => $api->get(88),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/notifications/threads/88',
			null,
			null
		];

		yield 'mark notification with all optional fields' => [
			Thread::class,
			static fn (object $api): mixed => $api->mark(
				88,
				'2026-08-14T10:00:00Z',
				true,
				['unread', 'pinned'],
				'read'
			),
			205,
			'',
			null,
			'PUT',
			self::API_ROOT
				. '/notifications/threads/88?last_read_at=2026-08-14T10:00:00Z'
				. '&all=1&status-types=unread,pinned&to-status=read',
			'',
			null
		];

		yield 'mark notification omits null optional fields' => [
			Thread::class,
			static fn (object $api): mixed => $api->mark(88),
			205,
			'',
			null,
			'PUT',
			self::API_ROOT . '/notifications/threads/88',
			'',
			null
		];
	}
}
