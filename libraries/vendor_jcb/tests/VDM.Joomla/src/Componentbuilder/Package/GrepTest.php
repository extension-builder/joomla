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

namespace VDM\Joomla\Tests\Componentbuilder\Package;


use ArrayObject;
use Joomla\CMS\Application\CMSApplicationInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Network\Resolve;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\Grep;
use VDM\Joomla\Componentbuilder\Package\GrepContent;
use VDM\Joomla\Git\Repository\Contents as GitContents;
use VDM\Joomla\Gitea\Repository\Contents as GiteaContents;
use VDM\Joomla\Github\Repository\Contents as GithubContents;
use VDM\Joomla\Interfaces\Remote\ConfigInterface;
use VDM\Tests\Support\TestCase;


/**
 * Package remote-index and content lookup tests.
 *
 * @since  1.0.0
 */
#[CoversClass(Grep::class)]
#[CoversClass(GrepContent::class)]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Remote')]
#[UsesNamespace('VDM\Joomla\Git')]
final class GrepTest extends TestCase
{
	/**
	 * Stable relationship identifiers.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private const ITEM_GUID = '11111111-1111-4111-8111-111111111111';
	private const PARENT_GUID = '22222222-2222-4222-8222-222222222222';

	/**
	 * Entity Grep loads write-branch metadata and queues exported dependencies.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testEntityGrepLoadsRemoteItemShaAndDependencyQueues(): void
	{
		$config = $this->config(
			table: 'admin_view',
			indexPath: 'index/admin-views.json',
			settingsName: 'item.json',
			hasReadme: true,
			readmeName: 'README.md'
		);
		$path = $this->repository();
		$calls = new ArrayObject();
		$metadataCalls = new ArrayObject();
		$parent = [
			'key' => 'guid',
			'value' => self::PARENT_GUID,
			'entity' => 'field',
			'table' => '#__componentbuilder_field',
			'direction' => 'out',
		];
		$file = [
			'key' => 'asset.key.txt',
			'pointer' => 'asset--key--txt',
			'value' => 'media/example.txt',
			'entity' => 'file',
			'table' => 'file_system',
			'target' => 'full',
		];
		$gitea = $this->createStub(GiteaContents::class);
		$gitea->method('get')->willReturnCallback(
			static function (string $owner, string $repo, string $filePath, ?string $branch) use ($calls, $parent, $file): object
			{
				$calls[] = [$owner, $repo, $filePath, $branch];

				if ($filePath === 'index/admin-views.json')
				{
					return (object) [
						self::ITEM_GUID => (object) ['path' => 'src/admin_view/item'],
					];
				}

				return (object) [
					'guid' => self::ITEM_GUID,
					'name' => 'Example View',
					'@dependencies' => [
						$parent,
						(object) $file,
						['key' => '', 'value' => 'invalid', 'entity' => 'ignored'],
					],
				];
			}
		);
		$gitea->method('metadata')->willReturnCallback(
			static function (string $owner, string $repo, string $filePath, ?string $branch) use ($metadataCalls): object
			{
				$metadataCalls[] = [$owner, $repo, $filePath, $branch];

				return (object) ['sha' => 'sha-' . basename($filePath)];
			}
		);
		$tracker = new Tracker();
		$grep = new Grep(
			$config,
			$this->git($gitea),
			$this->resolve(),
			$tracker,
			[$path],
			null,
			$this->createStub(CMSApplicationInterface::class)
		);
		$grep->setBranchField('write_branch');

		$result = $grep->get(self::ITEM_GUID, ['remote']);

		$this->assertNotNull($result);
		$this->assertSame(self::ITEM_GUID, $result->guid);
		$this->assertSame('Example View', $result->name);
		$this->assertSame(
			[
				'repo-guid-settings' => 'sha-item.json',
				'repo-guid-readme' => 'sha-README.md',
			],
			$result->params->source
		);
		$this->assertSame(
			[
				['example', 'definitions', 'index/admin-views.json', 'staging'],
				['example', 'definitions', 'src/admin_view/item/item.json', 'staging'],
			],
			$calls->getArrayCopy()
		);
		$this->assertSame(
			[
				['example', 'definitions', 'src/admin_view/item/item.json', 'staging'],
				['example', 'definitions', 'src/admin_view/item/README.md', 'staging'],
			],
			$metadataCalls->getArrayCopy()
		);
		$this->assertSame($parent, $tracker->get('get.field.guid|' . self::PARENT_GUID));
		$this->assertEquals((object) $file, $tracker->get('file.get.asset--key--txt'));
		$this->assertSame('package', $grep->getNetworkTarget());
		$this->assertSame($path, $grep->getPath('repo-guid'));
	}

	/**
	 * Content Grep returns exact remote bytes and annotates write SHA metadata.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testContentGrepLoadsExactRemoteContentAndWriteSha(): void
	{
		$config = $this->config(
			table: 'file_system',
			indexPath: 'index/file-folder.json',
			settingsName: ''
		);
		$path = $this->repository();
		$calls = new ArrayObject();
		$gitea = $this->createStub(GiteaContents::class);
		$gitea->method('get')->willReturnCallback(
			static function (string $owner, string $repo, string $filePath, ?string $branch) use ($calls): mixed
			{
				$calls[] = [$owner, $repo, $filePath, $branch];

				if ($filePath === 'index/file-folder.json')
				{
					return (object) [
						'asset.key.txt' => (object) [
							'path' => 'src/file_folder/asset.key.txt',
							'value' => 'media/example.txt',
							'target' => 'full',
						],
					];
				}

				return "binary\0payload";
			}
		);
		$gitea->method('metadata')->willReturn((object) ['sha' => 'content-sha']);
		$tracker = new Tracker();
		$grep = new GrepContent(
			$config,
			$this->git($gitea),
			$this->resolve(),
			$tracker,
			[$path],
			null,
			$this->createStub(CMSApplicationInterface::class)
		);
		$grep->setBranchField('write_branch');

		$result = $grep->get('asset.key.txt', ['remote']);

		$this->assertNotNull($result);
		$this->assertSame('media/example.txt', $result->value);
		$this->assertSame('full', $result->target);
		$this->assertSame("binary\0payload", $result->content);
		$this->assertSame(['repo-guid-settings' => 'content-sha'], $result->params->source);
		$this->assertSame(
			[
				['example', 'definitions', 'index/file-folder.json', 'staging'],
				['example', 'definitions', 'src/file_folder/asset.key.txt', 'staging'],
			],
			$calls->getArrayCopy()
		);
		$this->assertSame('package', $grep->getNetworkTarget());
		$this->assertNull($tracker->get('get'));
	}

	/**
	 * Both Package grep variants surface index failures through Joomla diagnostics.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testPackageGrepVariantsReportRemoteIndexFailures(): void
	{
		$subjects = [
			'entity' => [Grep::class, 'COM_COMPONENTBUILDER_PPACKAGEB_REPOSITORY'],
			'content' => [GrepContent::class, 'COM_COMPONENTBUILDER_PPACKAGECONTENTB_REPOSITORY'],
		];

		foreach ($subjects as $name => [$class, $messagePrefix])
		{
			$gitea = $this->createStub(GiteaContents::class);
			$gitea->method('get')->willThrowException(new RuntimeException('index unavailable'));
			$gitea->method('api')->willReturn('https://git.example/api');
			$messages = new ArrayObject();
			$app = $this->createStub(CMSApplicationInterface::class);
			$app->method('enqueueMessage')->willReturnCallback(
				static function (string $message, string $type) use ($messages): void
				{
					$messages[] = [$message, $type];
				}
			);
			$config = $this->config(
				table: $name === 'entity' ? 'admin_view' : 'file_system',
				indexPath: 'index/unavailable.json',
				settingsName: 'item.json'
			);
			$grep = new $class(
				$config,
				$this->git($gitea),
				$this->resolve(),
				new Tracker(),
				[$this->repository()],
				null,
				$app
			);

			$this->assertNull($grep->get('missing', ['remote']));
			$this->assertCount(1, $messages, $name);
			$this->assertStringStartsWith($messagePrefix, $messages[0][0], $name);
			$this->assertSame('Error', $messages[0][1], $name);
		}
	}

	/**
	 * Repository validation trims identifiers and rejects incomplete locations.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testRepositoryValidationNormalizesAndMarksValidPaths(): void
	{
		$grep = new Grep(
			$this->config('admin_view', 'index/admin-views.json', 'item.json'),
			$this->git($this->createStub(GiteaContents::class)),
			$this->resolve(),
			new Tracker(),
			[],
			null,
			$this->createStub(CMSApplicationInterface::class)
		);
		$invalid = (object) ['organisation' => 'example', 'repository' => '   '];
		$valid = (object) [
			'organisation' => ' example ',
			'repository' => ' definitions ',
			'read_branch' => 'default',
		];

		$this->assertFalse($grep->validRepo($invalid));
		$this->assertTrue($grep->validRepo($valid));
		$this->assertSame('example', $valid->organisation);
		$this->assertSame('definitions', $valid->repository);
		$this->assertSame('example/definitions', $valid->path);
		$this->assertNull($valid->read_branch);
		$this->assertTrue($valid->grep_validated);
		$this->assertTrue($grep->validRepo($valid), 'Validated repositories should use the fast path.');
	}

	/**
	 * Build a minimal remote configuration fixture.
	 *
	 * @param   string  $table         Active entity.
	 * @param   string  $indexPath     Repository index path.
	 * @param   string  $settingsName  Item settings name.
	 * @param   bool    $hasReadme     Whether item README metadata is tracked.
	 * @param   string  $readmeName    Item README name.
	 *
	 * @return  ConfigInterface
	 * @since   1.0.0
	 */
	private function config(
		string $table,
		string $indexPath,
		string $settingsName,
		bool $hasReadme = false,
		string $readmeName = ''
	): ConfigInterface
	{
		$config = $this->createStub(ConfigInterface::class);
		$config->method('getTable')->willReturn($table);
		$config->method('getIndexPath')->willReturn($indexPath);
		$config->method('getSettingsName')->willReturn($settingsName);
		$config->method('getGuidField')->willReturn('guid');
		$config->method('getGuidHelperField')->willReturn(null);
		$config->method('hasItemReadme')->willReturn($hasReadme);
		$config->method('getItemReadmeName')->willReturn($readmeName);

		return $config;
	}

	/**
	 * Build one approved repository path fixture.
	 *
	 * @return  object
	 * @since   1.0.0
	 */
	private function repository(): object
	{
		return (object) [
			'guid' => 'repo-guid',
			'organisation' => 'example',
			'repository' => 'definitions',
			'read_branch' => 'main',
			'write_branch' => 'staging',
			'target' => 'gitea',
		];
	}

	/**
	 * Wrap a recording Gitea boundary in the production target router.
	 *
	 * @param   GiteaContents  $gitea  Recording Gitea implementation.
	 *
	 * @return  GitContents
	 * @since   1.0.0
	 */
	private function git(GiteaContents $gitea): GitContents
	{
		$github = (new ReflectionClass(GithubContents::class))->newInstanceWithoutConstructor();

		return new GitContents($gitea, $github);
	}

	/**
	 * Create a resolver that remains inert because test repositories have no base.
	 *
	 * @return  Resolve
	 * @since   1.0.0
	 */
	private function resolve(): Resolve
	{
		return (new ReflectionClass(Resolve::class))->newInstanceWithoutConstructor();
	}
}
