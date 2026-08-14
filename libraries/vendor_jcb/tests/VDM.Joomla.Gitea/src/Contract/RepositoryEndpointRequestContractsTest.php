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
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Gitea\Abstraction\Api;
use VDM\Joomla\Gitea\Tests\Support\ApiTestCase;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Gitea\Utilities\Uri;


/**
 * Request contracts for repository-scoped Gitea APIs.
 *
 * @since  1.0.0
 */
#[CoversClass(\VDM\Joomla\Gitea\Repository::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Archive::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Assignees::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Attachments::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Branch::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Branch\Protection::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Collaborator::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Commits::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Contents::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Forks::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Gpg::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Hooks::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Hooks\Git::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Keys::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Media::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Merge::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Mirror::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Mirrors::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Notes::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Patch::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Pulls::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Releases::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Remote::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Reviewers::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Reviews::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Stargazers::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Statuses::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Tags::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Templates::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Times::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Transfer::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Trees::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Watchers::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Wiki::class)]
#[UsesClass(Api::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class RepositoryEndpointRequestContractsTest extends ApiTestCase
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
	 * Exercise push-mirror listing while isolating its known PHP signature deprecations.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[IgnoreDeprecations]
	public function testPushMirrorsListContract(): void
	{
		$body = '[{"remote_name":"backup"}]';
		[$subject, $transport] = $this->endpoint(
			\VDM\Joomla\Gitea\Repository\Mirrors::class,
			200,
			$body
		);

		$this->assertEquals(json_decode($body), $subject->get('acme', 'widget', 2, 15));
		$this->assertCount(1, $transport->requests());
		$this->assertRequest(
			$transport,
			'GET',
			self::API_ROOT . '/repos/acme/widget/push_mirrors?page=2&limit=15'
		);
	}

	/**
	 * Keep connection switching and restoration blocking for defective endpoints.
	 *
	 * Their endpoint-specific operations remain executable desired contracts in
	 * the known-defect lane. This contract ensures each concrete endpoint still
	 * participates in the shared API lifecycle during ordinary CI.
	 *
	 * @param   class-string<Api>  $class  Concrete endpoint class.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('defectiveEndpointClasses')]
	public function testDefectiveEndpointRetainsConnectionLifecycle(string $class): void
	{
		[$subject, $transport, $http, $uri] = $this->endpoint($class);

		$this->assertSame(self::API_ROOT, $subject->api());
		$subject->load_('https://alternate.example', 'temporary-token');
		$this->assertSame('https://alternate.example', $uri->getUrl());
		$this->assertSame('temporary-token', $http->getToken());

		$subject->reset_();

		$this->assertSame('https://gitea.example', $uri->getUrl());
		$this->assertSame('unit-token', $http->getToken());
		$this->assertCount(0, $transport->requests());
	}

	/**
	 * Provide endpoints whose operation-specific contracts expose known defects.
	 *
	 * @return  iterable<string, array{class-string<Api>}>  Endpoint classes.
	 * @since   1.0.0
	 */
	public static function defectiveEndpointClasses(): iterable
	{
		yield 'mirror synchronization' => [\VDM\Joomla\Gitea\Repository\Mirror::class];
		yield 'diff patch application' => [\VDM\Joomla\Gitea\Repository\Patch::class];
		yield 'stargazer listing' => [\VDM\Joomla\Gitea\Repository\Stargazers::class];
	}

	/**
	 * Provide executable repository endpoint contracts.
	 *
	 * Mirror, Patch, and Stargazers retain blocking lifecycle contracts above;
	 * their desired operation-specific paths remain in KnownDefectContractsTest
	 * because production currently fails before transport can complete.
	 *
	 * @return  iterable<string, array{class-string, Closure, int, string, mixed, string, string, mixed}>
	 * @since   1.0.0
	 */
	public static function endpointContracts(): iterable
	{
		$objectBody = '{"id":73,"name":"sample"}';
		$objectResult = json_decode($objectBody);
		$listBody = '[{"id":73},{"id":74}]';
		$listResult = json_decode($listBody);

		yield 'get repository' => [
			\VDM\Joomla\Gitea\Repository::class,
			static fn (object $api): mixed => $api->get('acme', 'widget'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget',
			null
		];

		yield 'download repository archive' => [
			\VDM\Joomla\Gitea\Repository\Archive::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 'zip'),
			200,
			'',
			'success',
			'GET',
			self::API_ROOT . '/repos/acme/widget/archive/zip?owner=acme&repo=widget&archive=zip',
			null
		];

		yield 'get repository assignees' => [
			\VDM\Joomla\Gitea\Repository\Assignees::class,
			static fn (object $api): mixed => $api->get('acme', 'widget'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/assignees?owner=acme&repo=widget',
			null
		];

		yield 'get release attachment' => [
			\VDM\Joomla\Gitea\Repository\Attachments::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 4, 9),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/releases/4/assets/9',
			null
		];

		yield 'get repository branch' => [
			\VDM\Joomla\Gitea\Repository\Branch::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 'main'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/branches/main?owner=acme&repo=widget&branch=main',
			null
		];

		yield 'get branch protection' => [
			\VDM\Joomla\Gitea\Repository\Branch\Protection::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 'main'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/branch_protections/main',
			null
		];

		yield 'get collaborator permission' => [
			\VDM\Joomla\Gitea\Repository\Collaborator::class,
			static fn (object $api): mixed => $api->permission('acme', 'widget', 'alice'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/collaborators/alice/permission',
			null
		];

		yield 'get repository commit' => [
			\VDM\Joomla\Gitea\Repository\Commits::class,
			static fn (object $api): mixed => $api->getCommit('acme', 'widget', 'abc123'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/git/commits/abc123',
			null
		];

		yield 'get repository content metadata at ref' => [
			\VDM\Joomla\Gitea\Repository\Contents::class,
			static fn (object $api): mixed => $api->metadata('acme', 'widget', 'docs/readme.md', 'main'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/contents/docs/readme.md?ref=main',
			null
		];

		yield 'list repository forks' => [
			\VDM\Joomla\Gitea\Repository\Forks::class,
			static fn (object $api): mixed => $api->listForks('acme', 'widget', 2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/forks?page=2&limit=15',
			null
		];

		yield 'get repository signing key' => [
			\VDM\Joomla\Gitea\Repository\Gpg::class,
			static fn (object $api): mixed => $api->get('acme', 'widget'),
			200,
			'PUBLIC KEY',
			'PUBLIC KEY',
			'GET',
			self::API_ROOT . '/repos/acme/widget/signing-key.gpg',
			null
		];

		yield 'get repository hook' => [
			\VDM\Joomla\Gitea\Repository\Hooks::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 7),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/hooks/7',
			null
		];

		yield 'get repository Git hook' => [
			\VDM\Joomla\Gitea\Repository\Hooks\Git::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 7),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/hooks/git/7',
			null
		];

		yield 'get repository deploy key' => [
			\VDM\Joomla\Gitea\Repository\Keys::class,
			static fn (object $api): mixed => $api->id('acme', 'widget', 7),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/keys/7',
			null
		];

		yield 'get repository media at ref' => [
			\VDM\Joomla\Gitea\Repository\Media::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 'docs/guide.pdf', 'main'),
			200,
			'',
			'success',
			'GET',
			self::API_ROOT . '/repos/acme/widget/media/docs%2Fguide.pdf?ref=main',
			null
		];

		yield 'check pull request mergeability' => [
			\VDM\Joomla\Gitea\Repository\Merge::class,
			static fn (object $api): mixed => $api->check('acme', 'widget', 17),
			204,
			'',
			'success',
			'GET',
			self::API_ROOT . '/repos/acme/widget/pulls/17/merge',
			null
		];

		yield 'get Git note' => [
			\VDM\Joomla\Gitea\Repository\Notes::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 'abc123'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/git/notes/abc123',
			null
		];

		yield 'get pull request' => [
			\VDM\Joomla\Gitea\Repository\Pulls::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 17),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/pulls/17',
			null
		];

		yield 'get release' => [
			\VDM\Joomla\Gitea\Repository\Releases::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 4),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/releases/4',
			null
		];

		yield 'migrate remote repository with required fields' => [
			\VDM\Joomla\Gitea\Repository\Remote::class,
			static fn (object $api): mixed => $api->migrate(
				'https://source.example/acme/widget.git',
				'widget',
				'acme',
				'42'
			),
			201,
			$objectBody,
			$objectResult,
			'POST',
			self::API_ROOT . '/repos/migrate',
			'{"cloneAddr":"https:\/\/source.example\/acme\/widget.git","repoName":"widget",'
				. '"repoOwner":"acme","uid":"42","description":"","private":false}'
		];

		yield 'get available reviewers' => [
			\VDM\Joomla\Gitea\Repository\Reviewers::class,
			static fn (object $api): mixed => $api->get('acme', 'widget'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/reviewers',
			null
		];

		yield 'get pull review with explicit identifiers' => [
			\VDM\Joomla\Gitea\Repository\Reviews::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 17, 9),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/pulls/17/reviews/9'
				. '?owner=acme&repo=widget&index=17&id=9',
			null
		];

		yield 'get commit statuses with filters' => [
			\VDM\Joomla\Gitea\Repository\Statuses::class,
			static fn (object $api): mixed => $api->get(
				'acme',
				'widget',
				'abc123',
				'leastupdate',
				'success',
				2,
				15
			),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/statuses/abc123'
				. '?sort=leastupdate&state=success&page=2&limit=15',
			null
		];

		yield 'get repository tag' => [
			\VDM\Joomla\Gitea\Repository\Tags::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 'v1.2.3'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/tags/v1.2.3',
			null
		];

		yield 'get issue templates' => [
			\VDM\Joomla\Gitea\Repository\Templates::class,
			static fn (object $api): mixed => $api->issue('acme', 'widget'),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/issue_templates',
			null
		];

		yield 'list repository times with filters' => [
			\VDM\Joomla\Gitea\Repository\Times::class,
			static fn (object $api): mixed => $api->list(
				'acme',
				'widget',
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
			self::API_ROOT . '/repos/acme/widget/times?user=alice&since=2026-08-01T00:00:00Z'
				. '&before=2026-08-31T23:59:59Z&page=2&limit=15',
			null
		];

		yield 'create repository transfer with teams' => [
			\VDM\Joomla\Gitea\Repository\Transfer::class,
			static fn (object $api): mixed => $api->create('acme', 'widget', 'next-owner', [7, 9]),
			202,
			$objectBody,
			$objectResult,
			'POST',
			self::API_ROOT . '/repos/acme/widget/transfer',
			'{"new_owner":"next-owner","team_ids":[7,9]}'
		];

		yield 'get recursive repository tree' => [
			\VDM\Joomla\Gitea\Repository\Trees::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 'abc123', true, 2, 50),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/git/trees/abc123?recursive=1&page=2&per_page=50',
			null
		];

		yield 'check repository subscription' => [
			\VDM\Joomla\Gitea\Repository\Watchers::class,
			static fn (object $api): mixed => $api->check('acme', 'widget'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/subscription',
			null
		];

		yield 'get repository wiki page' => [
			\VDM\Joomla\Gitea\Repository\Wiki::class,
			static fn (object $api): mixed => $api->get('acme', 'widget', 'Home'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/wiki/page/Home',
			null
		];
	}
}
