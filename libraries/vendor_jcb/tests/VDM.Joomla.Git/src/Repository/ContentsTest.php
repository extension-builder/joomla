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

namespace VDM\Joomla\Git\Tests\Repository;


use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use VDM\Joomla\Git\Repository\Contents;
use VDM\Joomla\Gitea\Repository\Contents as Gitea;
use VDM\Joomla\Github\Abstraction\Api as GithubApi;
use VDM\Joomla\Github\Repository\Contents as Github;
use VDM\Joomla\Github\Utilities\Http as GithubHttp;
use VDM\Joomla\Github\Utilities\Response as GithubResponse;
use VDM\Joomla\Github\Utilities\Uri as GithubUri;


/**
 * Provider-neutral Git repository contents facade test.
 *
 * @since  6.1.6
 */
#[CoversClass(Contents::class)]
#[UsesClass(Gitea::class)]
#[UsesClass(GithubApi::class)]
#[UsesClass(Github::class)]
#[UsesClass(GithubHttp::class)]
#[UsesClass(GithubResponse::class)]
#[UsesClass(GithubUri::class)]
final class ContentsTest extends TestCase
{
	/**
	 * Reject calls until an explicit provider target has been selected.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepositoryOperationRequiresAnExplicitTarget(): void
	{
		$subject = $this->createSubject();

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage(
			'No target system selected. Use $this->setTarget("gitea"|"github") before calling this method.'
		);

		$subject->get('owner', 'repository', 'README.md');
	}

	/**
	 * Reject unsupported provider names after applying the public normalization.
	 *
	 * @param   string  $target      The unsupported provider target.
	 * @param   string  $normalized  The normalized value included in the diagnostic.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('unsupportedTargetProvider')]
	public function testUnsupportedTargetIsRejected(string $target, string $normalized): void
	{
		$subject = $this->createSubject();

		$this->expectException(DomainException::class);
		$this->expectExceptionMessage('Invalid target system: ' . $normalized);

		$subject->setTarget($target);
	}

	/**
	 * Provide invalid target normalization cases.
	 *
	 * @return  iterable<string, array{string, string}>
	 * @since   6.1.6
	 */
	public static function unsupportedTargetProvider(): iterable
	{
		yield 'empty' => ['', ''];
		yield 'whitespace' => [" \t\n", ''];
		yield 'unknown provider' => ['GitLab', 'gitlab'];
		yield 'provider suffix' => ['github.com', 'github.com'];
	}

	/**
	 * Normalize a supported target and retain fluent identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetTargetNormalizesSupportedProviderAndReturnsSameFacade(): void
	{
		$gitea = $this->createMock(Gitea::class);
		$gitea->expects($this->once())
			->method('root')
			->with('owner', 'repository', 'release')
			->willReturn([]);
		$subject = $this->createSubject($gitea);

		$returned = $subject->setTarget(" \tGiTeA\n");

		$this->assertSame($subject, $returned);
		$this->assertSame([], $subject->root('owner', 'repository', 'release'));
	}

	/**
	 * Forward API loading options to only the selected provider.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLoadForwardsEveryOptionToSelectedProvider(): void
	{
		$gitea = $this->createMock(Gitea::class);
		$gitea->expects($this->once())
			->method('load_')
			->with('https://git.example.test', 'temporary-token', false);
		$subject = $this->createSubject($gitea);

		$subject->setTarget('gitea')->load_(
			'https://git.example.test',
			'temporary-token',
			false
		);
	}

	/**
	 * Forward reset to only the selected provider.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResetIsDelegatedToSelectedProvider(): void
	{
		$gitea = $this->createMock(Gitea::class);
		$gitea->expects($this->once())->method('reset_');
		$subject = $this->createSubject($gitea);

		$subject->setTarget('gitea')->reset_();
	}

	/**
	 * Select GitHub independently and preserve its load/reset token lifecycle.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGithubTargetOwnsItsLoadAndResetState(): void
	{
		$gitea = $this->createMock(Gitea::class);
		$gitea->expects($this->never())->method('load_');
		$gitea->expects($this->never())->method('reset_');
		$http = new GithubHttp('original-token');
		$github = new Github(
			$http,
			new GithubUri('https://api.github.example'),
			new GithubResponse()
		);
		$subject = new Contents($gitea, $github);

		$subject->setTarget(' github ')->load_(null, 'temporary-token', true);
		$this->assertSame('temporary-token', $http->getToken());

		$subject->reset_();
		$this->assertSame('original-token', $http->getToken());
	}

	/**
	 * Forward repository method arguments without reordering or coercion.
	 *
	 * @param   string         $method    The facade method under test.
	 * @param   array<mixed>   $arguments The complete argument list.
	 * @param   mixed          $result    The provider result to propagate.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('repositoryDelegationProvider')]
	public function testRepositoryMethodsDelegateArgumentsAndPropagateResult(
		string $method,
		array $arguments,
		mixed $result
	): void
	{
		$gitea = $this->createMock(Gitea::class);
		$gitea->expects($this->once())
			->method($method)
			->with(...$arguments)
			->willReturn($result);
		$subject = $this->createSubject($gitea);

		$actual = $subject->setTarget('gitea')->{$method}(...$arguments);

		$this->assertSame($result, $actual);
	}

	/**
	 * Provide every repository-content delegation contract.
	 *
	 * @return  iterable<string, array{string, array<mixed>, mixed}>
	 * @since   6.1.6
	 */
	public static function repositoryDelegationProvider(): iterable
	{
		$get = new stdClass();
		$get->content = 'raw content';
		yield 'get' => [
			'get',
			['owner', 'repository', 'src/File.php', 'feature/ref'],
			$get
		];

		$metadata = new stdClass();
		$metadata->sha = 'abc123';
		yield 'metadata' => [
			'metadata',
			['owner', 'repository', 'src/File.php', null],
			$metadata
		];

		$created = new stdClass();
		$created->sha = 'created-sha';
		yield 'create' => [
			'create',
			[
				'owner', 'repository', 'src/New.php', '<?php', 'Create file',
				'develop', 'Author', 'author@example.test', 'Committer',
				'committer@example.test', 'new-branch', '2026-08-13T12:00:00Z',
				'2026-08-13T12:01:00Z', true
			],
			$created
		];

		yield 'root' => [
			'root',
			['owner', 'repository', 'v6.1.6'],
			[(object) ['name' => 'README.md']]
		];

		$updated = new stdClass();
		$updated->sha = 'updated-sha';
		yield 'update' => [
			'update',
			[
				'owner', 'repository', 'src/New.php', '<?php // changed',
				'Update file', 'old-sha', 'develop', 'Author',
				'author@example.test', 'Committer', 'committer@example.test',
				'2026-08-13T12:02:00Z', '2026-08-13T12:03:00Z',
				'src/Old.php', 'new-branch', false
			],
			$updated
		];

		$deleted = new stdClass();
		$deleted->sha = 'deleted-sha';
		yield 'delete' => [
			'delete',
			[
				'owner', 'repository', 'src/Old.php', 'Delete file', 'old-sha',
				'develop', 'Author', 'author@example.test', 'Committer',
				'committer@example.test', '2026-08-13T12:04:00Z',
				'2026-08-13T12:05:00Z', 'new-branch', true
			],
			$deleted
		];

		yield 'editor config' => [
			'editor',
			['owner', 'repository', 'src/File.php', 'develop'],
			'indent_style = tab'
		];

		$blob = new stdClass();
		$blob->content = 'encoded';
		yield 'blob' => [
			'blob',
			['owner', 'repository', 'abc123'],
			$blob
		];
	}

	/**
	 * Define the intended API URL return contract while the known defect exists.
	 *
	 * The facade currently delegates the call but drops the provider's return
	 * value. This test is deliberately grouped so CI can expose the defect as a
	 * distinct known-contract failure until production is changed in its own PR.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testApiPropagatesSelectedProviderUrl(): void
	{
		$gitea = $this->createMock(Gitea::class);
		$gitea->expects($this->once())
			->method('api')
			->willReturn('https://git.example.test/api/v1');
		$subject = $this->createSubject($gitea);

		$this->assertSame(
			'https://git.example.test/api/v1',
			$subject->setTarget('gitea')->api()
		);
	}

	/**
	 * Create the facade with an inert GitHub collaborator unless one is supplied.
	 *
	 * @param   Gitea|null   $gitea   Optional Gitea collaborator.
	 * @param   Github|null  $github  Optional GitHub collaborator.
	 *
	 * @return  Contents
	 * @since   6.1.6
	 */
	private function createSubject(?Gitea $gitea = null, ?Github $github = null): Contents
	{
		$gitea ??= $this->createStub(Gitea::class);
		$github ??= new Github(
			new GithubHttp(),
			new GithubUri(),
			new GithubResponse()
		);

		return new Contents($gitea, $github);
	}
}
