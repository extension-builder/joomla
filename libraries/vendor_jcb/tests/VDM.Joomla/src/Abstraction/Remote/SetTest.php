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

namespace VDM\Joomla\Tests\Abstraction\Remote;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Remote\Set;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Joomla\Componentbuilder\Power\Interfaces\TableInterface;
use VDM\Joomla\Git\Repository\Contents as GitContents;
use VDM\Joomla\Gitea\Repository\Contents as GiteaContents;
use VDM\Joomla\Github\Repository\Contents as GithubContents;
use VDM\Joomla\Github\Utilities\Http as GithubHttp;
use VDM\Joomla\Github\Utilities\Response as GithubResponse;
use VDM\Joomla\Github\Utilities\Uri as GithubUri;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Interfaces\GrepInterface;
use VDM\Joomla\Interfaces\Readme\ItemInterface as ItemReadmeInterface;
use VDM\Joomla\Interfaces\Readme\MainInterface as MainReadmeInterface;
use VDM\Tests\Support\RecordingHttpTransport;
use VDM\Tests\Support\RemoteConfigFixture;
use VDM\Tests\Support\RemoteSetFixture;
use VDM\Tests\Support\TestCase;

/**
 * Shared remote-set orchestration, index, and repository-write tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Set::class)]
#[UsesClass(Tracker::class)]
#[UsesClass(MessageBus::class)]
#[UsesClass(GitContents::class)]
final class SetTest extends TestCase
{
	/**
	 * Initialize table-specific configuration and select the write branch.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorInitializesConfigurationAndWriteBranch(): void
	{
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->once())->method('setBranchField')->with('write_branch');
		$subject = $this->subject(
			$grep,
			$this->createStub(ItemsInterface::class),
			[],
			table: 'class_property',
			settingsName: 'property.json',
			indexPath: 'catalog.json'
		);

		$this->assertSame('class_property', $subject->getTable());
		$this->assertSame('Class property', $subject->getArea());
		$this->assertSame('property.json', $subject->getSettingsName());
		$this->assertSame('catalog.json', $subject->getIndexPath());
	}

	/**
	 * Reject empty pushes and explain the missing writable repository contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemsRejectsEmptyInputAndRepositoriesWithoutWriteBranches(): void
	{
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->exactly(2))->method('setBranchField')->with('write_branch');
		$grep->expects($this->once())->method('getNetworkTarget')->willReturn('Super Powers');
		$messages = new MessageBus();
		$subject = $this->subject(
			$grep,
			$this->createStub(ItemsInterface::class),
			[(object) ['write_branch' => 'default']],
			messages: $messages
		);

		$this->assertFalse($subject->items([]));
		$this->assertFalse($subject->items(['one']));
		$this->assertSame(
			[
				'At least one [Super Powers] content repository must be configured with a '
				. '[Write Branch] value in the repositories area, for the push function to operate correctly.',
			],
			$messages->get('error')
		);
	}

	/**
	 * Load local items, update existing remote content, and publish one success message.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemsRunsExistingItemPipelineAndTracksSuccess(): void
	{
		$raw = (object) ['guid' => 'one', 'system_name' => 'First'];
		$existing = (object) ['guid' => 'one', 'system_name' => 'Old'];
		$repo = $this->repo();
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$items->expects($this->once())->method('get')->with(['one'], 'guid')->willReturn([$raw]);
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->exactly(2))->method('setBranchField')->with('write_branch');
		$grep->expects($this->once())
			->method('loadApi')
			->with($this->isInstanceOf(GitContents::class), null, null);
		$grep->expects($this->once())
			->method('get')
			->with('one', ['remote'], $repo)
			->willReturn($existing);
		$gitea = $this->createMock(GiteaContents::class);
		$gitea->expects($this->once())->method('reset_');
		$messages = new MessageBus();
		$tracker = new Tracker();
		$subject = $this->subject(
			$grep,
			$items,
			[$repo],
			$tracker,
			$messages,
			$this->git($gitea)
		);

		$this->assertTrue($subject->items(['one']));
		$this->assertSame('update', $subject->calls[0][0]);
		$this->assertSame('update-readme', $subject->calls[1][0]);
		$this->assertSame($existing, $subject->calls[0][2]);
		$this->assertTrue($tracker->get('save.power.guid|one'));
		$this->assertSame(['Power item has been pushed successfully.'], $messages->get('success'));
	}

	/**
	 * Reject mapped items without the configured identity before repository access.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSaveRejectsItemsWithoutGuidAndAddsActionableMessage(): void
	{
		$messages = new MessageBus();
		$subject = $this->subject(
			$this->createStub(GrepInterface::class),
			$this->createStub(ItemsInterface::class),
			[$this->repo()],
			messages: $messages
		);

		$this->assertFalse($subject->saveItem((object) ['id' => 42, 'system_name' => 'Missing']));
		$this->assertSame(
			['Power item [Missing] id [42] missing the ::guid:: key value.'],
			$messages->get('error')
		);
		$this->assertSame([], $subject->calls);
	}

	/**
	 * Merge a fresh index over remote values and always clear the Grep entity cache.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMergeSettingsPreservesRemoteEntriesAndLetsFreshValuesWin(): void
	{
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->once())->method('setBranchField')->with('write_branch');
		$grep->expects($this->once())
			->method('getRemoteIndex')
			->with('repo-guid', true)
			->willReturn(
				(object) [
					'old' => (object) ['name' => 'Old'],
					'shared' => (object) ['name' => 'Remote'],
				]
			);
		$grep->expects($this->once())->method('resetEntityIndex');
		$subject = $this->subject($grep, $this->createStub(ItemsInterface::class), []);

		$this->assertSame(
			[
				'old' => ['name' => 'Old'],
				'shared' => ['name' => 'Fresh'],
				'new' => ['name' => 'New'],
			],
			$subject->mergeSettings(
				'repo-guid',
				[
					'shared' => ['name' => 'Fresh'],
					'new' => (object) ['name' => 'New'],
				]
			)
		);
	}

	/**
	 * Update known files and create missing files with exact repository metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testWriteRepoFileSelectsUpdateAndCreateOperations(): void
	{
		$repo = $this->repo();
		$gitea = $this->createMock(GiteaContents::class);
		$gitea->expects($this->exactly(2))
			->method('metadata')
			->willReturnOnConsecutiveCalls((object) ['sha' => 'old-sha'], null);
		$gitea->expects($this->once())
			->method('update')
			->with(
				'acme',
				'catalog',
				'index.json',
				'updated',
				'Update index',
				'old-sha',
				'main',
				'JCB',
				'jcb@example.test'
			);
		$gitea->expects($this->once())
			->method('create')
			->with(
				'acme',
				'catalog',
				'README.md',
				'created',
				'Create readme',
				'main',
				'JCB',
				'jcb@example.test'
			);
		$git = $this->git($gitea);
		$git->setTarget('gitea');
		$subject = $this->subject(
			$this->createStub(GrepInterface::class),
			$this->createStub(ItemsInterface::class),
			[],
			git: $git
		);

		$subject->writeRepoFile($repo, 'index.json', 'updated', 'Update index', 'Create index');
		$subject->writeRepoFile($repo, 'README.md', 'created', 'Update readme', 'Create readme');
	}

	/**
	 * Apply repository placeholders and compare items while ignoring dependencies.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPlaceholderAndObjectHelpersPreserveRemoteContracts(): void
	{
		$subject = $this->subject(
			$this->createStub(GrepInterface::class),
			$this->createStub(ItemsInterface::class),
			[]
		);

		$this->assertSame(
			'Custom/name/Custom',
			$subject->replaceForRepo(
				(object) ['placeholders' => ['[[NAME]]' => 'name', '[[TYPE]]' => 'Custom']],
				'[[TYPE]]/[[NAME]]/[[TYPE]]'
			)
		);
		$this->assertTrue(
			$subject->objectsEqual(
				(object) ['guid' => 'one', '@dependencies' => ['old']],
				(object) ['@dependencies' => ['new'], 'guid' => 'one']
			)
		);
		$this->assertFalse($subject->objectsEqual((object) ['guid' => 'one'], null));
		$this->assertTrue($subject->invalidIndexRepo((object) [], ['one']));
		$this->assertTrue($subject->invalidIndexRepo((object) ['guid' => 'repo'], []));
		$this->assertFalse($subject->invalidIndexRepo((object) ['guid' => 'repo'], ['one']));
		$this->assertSame('[core]/[organisation]/[repository]', $subject->repoName((object) []));
	}

	/**
	 * Build a configured remote-set fixture.
	 *
	 * @param   GrepInterface       $grep          Repository search boundary.
	 * @param   ItemsInterface      $items         Local item boundary.
	 * @param   array<int, object>  $repos         Writable repositories.
	 * @param   Tracker|null        $tracker       Operation tracker.
	 * @param   MessageBus|null     $messages      Operation messages.
	 * @param   GitContents|null    $git           Provider-neutral Git facade.
	 * @param   string              $table         Entity table.
	 * @param   string|null         $settingsName  Settings filename.
	 * @param   string|null         $indexPath     Repository index path.
	 *
	 * @return  RemoteSetFixture
	 * @since   6.1.6
	 */
	private function subject(
		GrepInterface $grep,
		ItemsInterface $items,
		array $repos,
		?Tracker $tracker = null,
		?MessageBus $messages = null,
		?GitContents $git = null,
		string $table = 'power',
		?string $settingsName = null,
		?string $indexPath = null
	): RemoteSetFixture
	{
		$core = $this->createStub(TableInterface::class);
		$core->method('fields')->willReturn(['guid', 'system_name']);
		$core->method('titleName')->willReturn('system_name');

		return new RemoteSetFixture(
			new RemoteConfigFixture($core),
			$grep,
			$items,
			$this->createStub(ItemReadmeInterface::class),
			$this->createStub(MainReadmeInterface::class),
			$git ?? $this->git(),
			$tracker ?? new Tracker(),
			$messages ?? new MessageBus(),
			$repos,
			$table,
			$settingsName,
			$indexPath
		);
	}

	/**
	 * Create the provider-neutral Git facade with an inert GitHub collaborator.
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
	 * Create a complete writable repository configuration.
	 *
	 * @return  object
	 * @since   6.1.6
	 */
	private function repo(): object
	{
		return (object) [
			'guid' => 'repo-guid',
			'organisation' => 'acme',
			'repository' => 'catalog',
			'write_branch' => 'main',
			'author_name' => 'JCB',
			'author_email' => 'jcb@example.test',
		];
	}
}
