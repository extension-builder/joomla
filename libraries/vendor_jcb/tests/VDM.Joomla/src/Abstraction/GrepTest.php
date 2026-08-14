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

namespace VDM\Joomla\Tests\Abstraction;

use Joomla\CMS\Application\CMSApplicationInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Grep;
use VDM\Joomla\Componentbuilder\Api\Network;
use VDM\Joomla\Componentbuilder\Network\Core;
use VDM\Joomla\Componentbuilder\Network\ParsedUrls;
use VDM\Joomla\Componentbuilder\Network\Resolve;
use VDM\Joomla\Componentbuilder\Network\Status;
use VDM\Joomla\Componentbuilder\Network\Url;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Power\Interfaces\TableInterface;
use VDM\Joomla\Componentbuilder\Utilities\Http;
use VDM\Joomla\Componentbuilder\Utilities\Response;
use VDM\Joomla\Componentbuilder\Utilities\Uri;
use VDM\Joomla\Git\Repository\Contents as GitContents;
use VDM\Joomla\Gitea\Repository\Contents as GiteaContents;
use VDM\Joomla\Github\Repository\Contents as GithubContents;
use VDM\Joomla\Github\Utilities\Http as GithubHttp;
use VDM\Joomla\Github\Utilities\Response as GithubResponse;
use VDM\Joomla\Github\Utilities\Uri as GithubUri;
use VDM\Joomla\Interfaces\Git\ApiInterface;
use VDM\Joomla\Interfaces\Git\Repository\ContentsInterface;
use VDM\Tests\Support\GrepFixture;
use VDM\Tests\Support\RecordingHttpTransport;
use VDM\Tests\Support\RemoteConfigFixture;
use VDM\Tests\Support\TestCase;

/**
 * Global repository path, index, API, and GUID resolution tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Grep::class)]
#[UsesClass(Tracker::class)]
#[UsesClass(GitContents::class)]
final class GrepTest extends TestCase
{
	/**
	 * Normalize valid repositories and discard incomplete path records.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorValidatesAndNormalizesRepositoryPaths(): void
	{
		$valid = (object) [
			'guid' => 'repo-guid',
			'organisation' => ' acme ',
			'repository' => ' powers ',
			'read_branch' => 'default',
		];
		$invalid = (object) ['guid' => 'invalid', 'organisation' => '', 'repository' => 'repo'];
		$subject = $this->subject([$valid, $invalid]);

		$this->assertSame([$valid], array_values($subject->getPaths()));
		$this->assertSame($valid, $subject->getPath('repo-guid'));
		$this->assertNull($subject->getPath('missing'));
		$this->assertSame('acme', $valid->organisation);
		$this->assertSame('powers', $valid->repository);
		$this->assertSame('acme/powers', $valid->path);
		$this->assertNull($valid->read_branch);
		$this->assertTrue($valid->grep_validated);
	}

	/**
	 * Preserve a validated repository without repeating normalization.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValidRepoHonorsValidatedMarkerAndBranchDefaults(): void
	{
		$subject = $this->subject([]);
		$subject->setBranchField('write_branch');
		$subject->setBranchDefaultName('release');
		$repo = (object) ['organisation' => 'acme', 'repository' => 'powers'];

		$this->assertTrue($subject->validRepo($repo));
		$this->assertSame('release', $subject->branchName($repo));
		$this->assertFalse(isset($repo->write_branch));
		$repo->organisation = ' preserved ';
		$this->assertTrue($subject->validRepo($repo));
		$this->assertSame(' preserved ', $repo->organisation);
	}

	/**
	 * Use global tokens only for core hosts and clear absent custom-host tokens.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLoadApiAppliesHostSpecificNullTokenPolicy(): void
	{
		$api = $this->createMock(ApiInterface::class);
		$api->expects($this->exactly(3))
			->method('load_')
			->willReturnCallback(
				function (?string $base, ?string $token) use (&$calls): void
				{
					$calls[] = [$base, $token];
				}
			);
		$calls = [];
		$subject = $this->subject([]);

		$subject->loadApi($api, 'https://git.vdm.dev/api/v1', null);
		$subject->loadApi($api, 'https://api.github.com', null);
		$subject->loadApi($api, 'https://code.example.test/api', null);

		$this->assertSame(
			[
				['https://git.vdm.dev/api/v1', null],
				['https://api.github.com', null],
				['https://code.example.test/api', ''],
			],
			$calls
		);
	}

	/**
	 * Resolve helper-field values from a preloaded repository index.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetValidGuidsKeepsValidValuesAndResolvesHelperFields(): void
	{
		$first = '11111111-1111-4111-8111-111111111111';
		$resolved = '22222222-2222-4222-8222-222222222222';
		$repo = (object) [
			'guid' => 'repo-guid',
			'organisation' => 'acme',
			'repository' => 'powers',
			'index' => [
				'power' => (object) [
					$resolved => (object) ['system_name' => 'Resolved Power'],
				],
			],
		];
		$subject = $this->subject([$repo]);

		$this->assertSame(
			[$first, $resolved],
			$subject->getValidGuids(['', $first, 'Resolved Power', 'Missing'], $repo)
		);
	}

	/**
	 * Fetch, cache, reload, and clear a repository entity index.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRemoteIndexLifecycleUsesSelectedGitProvider(): void
	{
		$first = (object) ['one' => (object) ['name' => 'One']];
		$second = (object) ['two' => (object) ['name' => 'Two']];
		$gitea = $this->createMock(GiteaContents::class);
		$gitea->expects($this->exactly(2))
			->method('load_')
			->with(null, '');
		$gitea->expects($this->exactly(2))
			->method('get')
			->with('acme', 'powers', 'index.json', 'main')
			->willReturnOnConsecutiveCalls($first, $second);
		$gitea->expects($this->exactly(2))->method('reset_');
		$repo = (object) [
			'guid' => 'repo-guid',
			'organisation' => 'acme',
			'repository' => 'powers',
			'read_branch' => 'main',
		];
		$subject = $this->subject([$repo], $this->git($gitea));

		$this->assertSame($first, $subject->getRemoteIndex('repo-guid'));
		$this->assertSame($first, $subject->getRemoteIndex('repo-guid'));
		$this->assertSame($second, $subject->getRemoteIndex('repo-guid', true));
		$subject->resetEntityIndex();
		$this->assertFalse(isset($repo->index['power']));
	}

	/**
	 * Add repository metadata without discarding existing source hashes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepositoryShaEnrichmentPreservesExistingSources(): void
	{
		$contents = $this->createMock(ContentsInterface::class);
		$contents->expects($this->exactly(2))
			->method('metadata')
			->with('acme', 'powers', 'src/item.json', 'main')
			->willReturnOnConsecutiveCalls((object) ['sha' => 'sha-one'], (object) ['sha' => 'sha-two']);
		$subject = $this->subject([], $contents);
		$path = (object) ['organisation' => 'acme', 'repository' => 'powers'];
		$empty = (object) [];
		$existing = (object) ['params' => (object) ['source' => ['readme' => 'old']]];

		$subject->addRepositorySha($empty, $path, 'src/item.json', 'main', 'settings');
		$subject->addRepositorySha($existing, $path, 'src/item.json', 'main', 'settings');

		$this->assertSame(['settings' => 'sha-one'], $empty->params->source);
		$this->assertSame(
			['readme' => 'old', 'settings' => 'sha-two'],
			$existing->params->source
		);
	}

	/**
	 * Build a Grep fixture with deterministic configuration and application state.
	 *
	 * @param   array<int, object>     $paths     Repository paths.
	 * @param   ContentsInterface|null  $contents  Git contents boundary.
	 *
	 * @return  GrepFixture
	 * @since   6.1.6
	 */
	private function subject(array $paths, ?ContentsInterface $contents = null): GrepFixture
	{
		$table = $this->createStub(TableInterface::class);
		$config = new RemoteConfigFixture($table);
		$config->table('power');

		return new GrepFixture(
			$config,
			$contents ?? $this->createStub(ContentsInterface::class),
			$this->resolver(),
			new Tracker(),
			$paths,
			null,
			$this->createStub(CMSApplicationInterface::class)
		);
	}

	/**
	 * Create the provider-neutral Git facade with a selectable Gitea client.
	 *
	 * @param   GiteaContents|null  $gitea  Optional Gitea collaborator.
	 *
	 * @return  GitContents
	 * @since   6.1.6
	 */
	private function git(?GiteaContents $gitea = null): GitContents
	{
		$http = new GithubHttp();
		(new RecordingHttpTransport())->attachTo($http);

		return new GitContents(
			$gitea ?? $this->createStub(GiteaContents::class),
			new GithubContents($http, new GithubUri(), new GithubResponse())
		);
	}

	/**
	 * Build an inert but fully constructed network resolver.
	 *
	 * @return  Resolve
	 * @since   6.1.6
	 */
	private function resolver(): Resolve
	{
		$url = new Url(new ParsedUrls());
		$http = new Http();
		(new RecordingHttpTransport())->attachTo($http);
		$network = new Network($http, new Uri(), new Response());

		return new Resolve($url, new Status($network, new Core(), $url));
	}
}
