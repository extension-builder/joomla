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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;
use VDM\Joomla\Gitea\Abstraction\Api;
use VDM\Joomla\Gitea\Issue\Deadline;
use VDM\Joomla\Gitea\Service\Issue as IssueProvider;
use VDM\Joomla\Gitea\Tests\Support\RecordingTransport;
use VDM\Joomla\Gitea\Tests\Support\ServiceProviderTestCase;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Gitea\Utilities\Uri;


/**
 * Desired contracts quarantined until their documented production defects are fixed.
 *
 * These tests intentionally describe the behavior that must pass after each defect is
 * corrected. The known-defect group is excluded from the normal suite and is expected
 * to fail when run explicitly until the corresponding production debt is removed.
 *
 * @since  1.0.0
 */
#[CoversClass(IssueProvider::class)]
#[CoversClass(\VDM\Joomla\Gitea\Admin\Cron::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Branch\Protection::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Commits::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Hooks::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Mirror::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Mirrors::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Patch::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Pulls::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Releases::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Reviews::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Stargazers::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Transfer::class)]
#[CoversClass(\VDM\Joomla\Gitea\Repository\Wiki::class)]
#[CoversClass(\VDM\Joomla\Gitea\User\Following::class)]
#[CoversClass(\VDM\Joomla\Gitea\Utilities\Http::class)]
#[Group('known-defect')]
#[UsesClass(Api::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class KnownDefectContractsTest extends ServiceProviderTestCase
{
	/**
	 * Require the Issue provider's Deadline alias to resolve with standard dependencies.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testIssueDeadlineProviderMustResolve(): void
	{
		$this->assertEndpointProvider(new IssueProvider(), [
			Deadline::class => 'Gitea.Issue.Deadline'
		]);
	}

	/**
	 * Require every mandatory push-mirror argument to precede optional arguments.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[IgnoreDeprecations]
	public function testPushMirrorSignatureHasNoRequiredParameterAfterOptionalParameters(): void
	{
		$parameters = (new ReflectionMethod(
			\VDM\Joomla\Gitea\Repository\Mirrors::class,
			'add'
		))->getParameters();
		$parametersByName = [];

		foreach ($parameters as $parameter)
		{
			$parametersByName[$parameter->getName()] = $parameter;
		}

		$this->assertTrue($parametersByName['remoteUsername']->isOptional());
		$this->assertTrue($parametersByName['remotePassword']->isOptional());
	}

	/**
	 * Require bodyless mutation endpoints to pass an explicit empty request body.
	 *
	 * @param   class-string  $class           Endpoint class.
	 * @param   Closure       $invoke          Endpoint invocation.
	 * @param   int           $responseStatus  Expected response status.
	 * @param   string        $responseBody    Response body.
	 * @param   mixed         $expectedResult  Expected mapped result.
	 * @param   string        $uri             Expected request URI.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('bodylessPostContracts')]
	#[IgnoreDeprecations]
	public function testBodylessPostMutationSendsExplicitEmptyData(
		string $class,
		Closure $invoke,
		int $responseStatus,
		string $responseBody,
		mixed $expectedResult,
		string $uri
	): void
	{
		[$subject, $transport] = $this->endpoint($class, $responseStatus, $responseBody);

		$this->assertEquals($expectedResult, $invoke($subject));
		$this->assertRequest($transport, 'POST', $uri, '');
	}

	/**
	 * Describe all bodyless mutation endpoints affected by the missing data argument.
	 *
	 * @return  iterable<string, array{class-string, Closure, int, string, mixed, string}>
	 * @since   1.0.0
	 */
	public static function bodylessPostContracts(): iterable
	{
		$objectBody = '{"id":1}';
		$objectResult = json_decode($objectBody);

		yield 'run admin cron task' => [
			\VDM\Joomla\Gitea\Admin\Cron::class,
			static fn (object $api): mixed => $api->run('archive_cleanup'),
			204,
			'',
			'success',
			self::API_ROOT . '/admin/cron/archive_cleanup'
		];
		yield 'sync repository mirror' => [
			\VDM\Joomla\Gitea\Repository\Mirror::class,
			static fn (object $api): mixed => $api->sync('acme', 'widget'),
			200,
			'',
			'success',
			self::API_ROOT . '/repos/acme/widget/mirror-sync'
		];
		yield 'sync repository push mirrors' => [
			\VDM\Joomla\Gitea\Repository\Mirrors::class,
			static fn (object $api): mixed => $api->sync('acme', 'widget'),
			200,
			'',
			'success',
			self::API_ROOT . '/repos/acme/widget/push_mirrors-sync'
		];
		yield 'test repository hook' => [
			\VDM\Joomla\Gitea\Repository\Hooks::class,
			static fn (object $api): mixed => $api->test('acme', 'widget', 7, 'refs/heads/main'),
			204,
			'',
			'success',
			self::API_ROOT . '/repos/acme/widget/hooks/7/tests?ref=refs/heads/main'
		];
		yield 'undismiss pull request review' => [
			\VDM\Joomla\Gitea\Repository\Reviews::class,
			static fn (object $api): mixed => $api->undismiss('acme', 'widget', 17, 9),
			200,
			$objectBody,
			$objectResult,
			self::API_ROOT . '/repos/acme/widget/pulls/17/reviews/9/undismissals'
		];
		yield 'accept repository transfer' => [
			\VDM\Joomla\Gitea\Repository\Transfer::class,
			static fn (object $api): mixed => $api->accept('acme', 'widget'),
			202,
			$objectBody,
			$objectResult,
			self::API_ROOT . '/repos/acme/widget/transfer/accept'
		];
		yield 'reject repository transfer' => [
			\VDM\Joomla\Gitea\Repository\Transfer::class,
			static fn (object $api): mixed => $api->reject('acme', 'widget'),
			200,
			$objectBody,
			$objectResult,
			self::API_ROOT . '/repos/acme/widget/transfer/reject'
		];
	}

	/**
	 * Require Following::check() to use the PSR response status accessor.
	 *
	 * @param   int   $status    Response status.
	 * @param   bool  $expected  Expected following state.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('followingStatusContracts')]
	public function testFollowingCheckUsesPsrResponseStatus(int $status, bool $expected): void
	{
		[$subject, $transport] = $this->endpoint(
			\VDM\Joomla\Gitea\User\Following::class,
			$status,
			''
		);

		$this->assertSame($expected, $subject->check('alice'));
		$this->assertRequest($transport, 'GET', self::API_ROOT . '/user/following/alice');
	}

	/**
	 * Provide followed and not-followed response statuses.
	 *
	 * @return  iterable<string, array{int, bool}>
	 * @since   1.0.0
	 */
	public static function followingStatusContracts(): iterable
	{
		yield 'followed' => [204, true];
		yield 'not followed' => [404, false];
	}

	/**
	 * Require token removal to clear both the header and the in-memory token value.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testClearingTokenClearsStoredTokenState(): void
	{
		$http = $this->http(new RecordingTransport(), 'temporary-token');
		$http->setToken('');

		$this->assertNull($http->getToken());
		$this->assertArrayNotHasKey('Authorization', (array) $http->getOption('headers'));
	}

	/**
	 * Require Patch::applyDiffPatch() to encode its actual option argument.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testDiffPatchUsesSuppliedOptionBody(): void
	{
		$body = '{"id":1}';
		[$subject, $transport] = $this->endpoint(
			\VDM\Joomla\Gitea\Repository\Patch::class,
			200,
			$body
		);
		$options = ['body' => ['content' => 'SGVsbG8=', 'sha' => 'abc123']];

		$this->assertEquals(json_decode($body), $subject->applyDiffPatch('acme', 'widget', $options));
		$this->assertRequest(
			$transport,
			'POST',
			self::API_ROOT . '/repos/acme/widget/diffpatch',
			json_encode($options)
		);
	}

	/**
	 * Require repository methods to mutate the request URI returned by Uri::get().
	 *
	 * @param   class-string  $class           Endpoint class.
	 * @param   Closure       $invoke          Endpoint invocation.
	 * @param   int           $responseStatus  Response status.
	 * @param   string        $responseBody    Response body.
	 * @param   mixed         $expectedResult  Expected mapped result.
	 * @param   string        $method          Expected HTTP method.
	 * @param   string        $uri             Expected request URI.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('uriMutationContracts')]
	public function testRepositoryQueryParametersAreAppliedToRequestUri(
		string $class,
		Closure $invoke,
		int $responseStatus,
		string $responseBody,
		mixed $expectedResult,
		string $method,
		string $uri
	): void
	{
		[$subject, $transport] = $this->endpoint($class, $responseStatus, $responseBody);

		$this->assertEquals($expectedResult, $invoke($subject));
		$this->assertRequest($transport, $method, $uri);
	}

	/**
	 * Describe request-URI mutation contracts affected by the Uri factory mix-up.
	 *
	 * @return  iterable<string, array{class-string, Closure, int, string, mixed, string, string}>
	 * @since   1.0.0
	 */
	public static function uriMutationContracts(): iterable
	{
		$listBody = '[{"id":1}]';
		$listResult = json_decode($listBody);
		$objectBody = '{"id":1}';
		$objectResult = json_decode($objectBody);

		yield 'pull requests list' => [
			\VDM\Joomla\Gitea\Repository\Pulls::class,
			static fn (object $api): mixed => $api->list(
				'acme',
				'widget',
				'open',
				'oldest',
				9,
				[3, 4],
				2,
				15
			),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/pulls?page=2&limit=15&state=open'
				. '&sort=oldest&milestone=9&labels=3,4'
		];
		yield 'repository stargazers' => [
			\VDM\Joomla\Gitea\Repository\Stargazers::class,
			static fn (object $api): mixed => $api->list('acme', 'widget', 2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/stargazers?page=2&limit=15'
		];
		yield 'wiki revisions' => [
			\VDM\Joomla\Gitea\Repository\Wiki::class,
			static fn (object $api): mixed => $api->revisions('acme', 'widget', 'Home', 2),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/wiki/revisions/Home?page=2'
		];
		yield 'commit diff type' => [
			\VDM\Joomla\Gitea\Repository\Commits::class,
			static fn (object $api): mixed => $api->diff('acme', 'widget', 'abc123', 'diff'),
			200,
			'diff text',
			'diff text',
			'GET',
			self::API_ROOT . '/repos/acme/widget/git/commits/abc123?diffType=diff'
		];
		yield 'release list filters' => [
			\VDM\Joomla\Gitea\Repository\Releases::class,
			static fn (object $api): mixed => $api->list('acme', 'widget', true, false, 2, 15),
			200,
			$listBody,
			$listResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/releases?page=2&limit=15&draft=1&pre-release=0'
		];
		yield 'delete branch protection' => [
			\VDM\Joomla\Gitea\Repository\Branch\Protection::class,
			static fn (object $api): mixed => $api->delete('acme', 'widget', 'main'),
			204,
			'',
			'success',
			'DELETE',
			self::API_ROOT . '/repos/acme/widget/branch_protections/main'
				. '?owner=acme&repo=widget&name=main'
		];
		yield 'get release by tag' => [
			\VDM\Joomla\Gitea\Repository\Releases::class,
			static fn (object $api): mixed => $api->getByTag('acme', 'widget', 'v1.2.3'),
			200,
			$objectBody,
			$objectResult,
			'GET',
			self::API_ROOT . '/repos/acme/widget/releases/tags/v1.2.3'
				. '?owner=acme&repo=widget&tag=v1.2.3'
		];
	}
}
