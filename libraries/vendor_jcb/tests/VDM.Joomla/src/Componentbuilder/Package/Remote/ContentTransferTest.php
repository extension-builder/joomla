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

namespace VDM\Joomla\Tests\Componentbuilder\Package\Remote;


use ArrayObject;
use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Joomla\Componentbuilder\Package\Remote\GetContent;
use VDM\Joomla\Componentbuilder\Package\Remote\GetFile;
use VDM\Joomla\Componentbuilder\Package\Remote\GetFolder;
use VDM\Joomla\Componentbuilder\Package\Remote\SetContent;
use VDM\Joomla\Componentbuilder\Package\Remote\SetFile;
use VDM\Joomla\Componentbuilder\Package\Remote\SetFolder;
use VDM\Joomla\Componentbuilder\Utilities\Normalize;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Interfaces\GrepInterface;
use VDM\Joomla\Interfaces\Readme\ItemInterface as ItemReadmeInterface;
use VDM\Joomla\Interfaces\Readme\MainInterface as MainReadmeInterface;
use VDM\Joomla\Interfaces\Remote\ConfigInterface;
use VDM\Joomla\Interfaces\Remote\Dependency\ResolverInterface;
use VDM\Joomla\Git\Repository\Contents as GitContents;
use VDM\Joomla\Gitea\Repository\Contents as GiteaContents;
use VDM\Joomla\Github\Repository\Contents as GithubContents;
use VDM\Tests\Support\FilesystemTestCase;
use ZipArchive;


/**
 * Package filesystem-content transfer tests.
 *
 * @since  1.0.0
 */
#[CoversClass(GetContent::class)]
#[CoversClass(GetFile::class)]
#[CoversClass(GetFolder::class)]
#[CoversClass(SetContent::class)]
#[CoversClass(SetFile::class)]
#[CoversClass(SetFolder::class)]
#[UsesClass(Tracker::class)]
#[UsesClass(MessageBus::class)]
#[UsesClass(GitContents::class)]
final class ContentTransferTest extends FilesystemTestCase
{
	/**
	 * File pulls classify local, missing, and restored content and cache work.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testGetFileCategorizesMixedInputAndAvoidsDuplicateRemoteWork(): void
	{
		$local = $this->writeTemporaryFile('local.txt', 'local bytes');
		$remote = $this->temporaryPath('restored/remote.txt');
		$missing = $this->temporaryPath('missing.txt');
		$paths = [
			'local.txt' => $local,
			'remote.txt' => $remote,
			'missing.txt' => $missing,
		];
		$calls = new ArrayObject();
		$repo = (object) ['guid' => 'repo-guid'];
		[$get, $tracker, $messages] = $this->getFile(
			static function (string $guid, ?array $order, ?object $targetRepo) use ($calls, $repo): ?object
			{
				$calls[] = [$guid, $order, $targetRepo];

				return $guid === 'remote.key.txt'
					? (object) ['content' => 'remote bytes']
					: null;
			},
			static fn(string $path, string $target): ?string => $paths[$path] ?? null
		);
		$items = [
			[
				'key' => 'local.key.txt',
				'value' => 'local.txt',
				'entity' => 'file',
				'target' => 'full',
				'pointer' => 'local--key--txt',
			],
			(object) [
				'key' => 'remote.key.txt',
				'value' => 'remote.txt',
				'entity' => 'file',
				'target' => 'full',
				'pointer' => 'remote--key--txt',
			],
			[
				'key' => 'missing.key.txt',
				'value' => 'missing.txt',
				'entity' => 'file',
				'target' => 'full',
				'pointer' => 'missing--key--txt',
			],
			['key' => 'invalid-without-contract-fields'],
		];

		$result = $get->init($items, $repo);

		$this->assertSame(
			[
				'local' => ['local.key.txt' => 'local.txt'],
				'not_found' => ['missing.key.txt' => 'missing.txt'],
				'added' => ['remote.key.txt' => 'remote.txt'],
			],
			$result
		);
		$this->assertSame('remote bytes', file_get_contents($remote));
		$this->assertSame(
			[
				['remote.key.txt', ['remote'], $repo],
				['missing.key.txt', ['remote'], $repo],
			],
			$calls->getArrayCopy()
		);
		$this->assertTrue($tracker->get('file.save.local--key--txt'));
		$this->assertTrue($tracker->get('file.save.remote--key--txt'));
		$this->assertTrue($tracker->get('file.save.missing--key--txt'));
		$this->assertNull($messages->get('error'));
		$this->assertSame(
			['local' => [], 'not_found' => [], 'added' => []],
			$get->init($items, $repo)
		);
		$this->assertCount(2, $calls);
	}

	/**
	 * Force mode replaces an existing file rather than classifying it as local.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testGetFileForceReplacesExistingContent(): void
	{
		$file = $this->writeTemporaryFile('force.txt', 'old bytes');
		[$get] = $this->getFile(
			static fn(string $guid): ?object => (object) ['content' => 'new bytes'],
			static fn(string $path, string $target): ?string => $file
		);
		$item = [[
			'key' => 'force.key.txt',
			'value' => 'force.txt',
			'entity' => 'file',
			'target' => 'full',
			'pointer' => 'force--key--txt',
		]];

		$result = $get->init($item, null, true);

		$this->assertSame(['force.key.txt' => 'force.txt'], $result['added']);
		$this->assertSame([], $result['local']);
		$this->assertSame('new bytes', file_get_contents($file));
	}

	/**
	 * Reset accepts mixed identifier shapes and reports successes and failures.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testGetFileResetHandlesMixedIdentifierShapesAndDeduplicatesSuccessMessage(): void
	{
		$file = $this->temporaryPath('reset/good.txt');
		$calls = new ArrayObject();
		[$get, $tracker, $messages] = $this->getFile(
			static function (string $guid, ?array $order) use ($calls): ?object
			{
				$calls[] = [$guid, $order];

				return $guid === 'good.key.txt'
					? (object) [
						'value' => 'good.txt',
						'target' => 'full',
						'content' => 'reset bytes',
					]
					: null;
			},
			static fn(string $path, string $target): ?string => $file
		);

		$this->assertTrue(
			$get->reset([
				'good.key.txt',
				['key' => 'bad.key.txt'],
				(object) ['missing' => 'key'],
			])
		);
		$this->assertSame('reset bytes', file_get_contents($file));
		$this->assertCount(2, $messages->get('warning'));
		$this->assertCount(1, $messages->get('success'));
		$this->assertTrue($tracker->get('message.reset.file'));

		$this->assertTrue($get->reset(['good.key.txt']));
		$this->assertCount(1, $messages->get('success'));
		$this->assertSame(
			[
				['good.key.txt', ['remote']],
				['bad.key.txt', ['remote']],
			],
			$calls->getArrayCopy()
		);
	}

	/**
	 * Folder pulls unpack valid archive bytes and always clean the temporary zip.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testGetFolderRestoresArchiveAndCleansTemporaryFile(): void
	{
		$destination = $this->temporaryPath('restored-folder');
		$archive = $this->archiveContent('nested/data.txt', 'folder payload');
		[$get, $messages] = $this->getFolder(
			static fn(string $guid): ?object => (object) ['content' => $archive],
			static fn(string $path, string $target): ?string => $destination
		);
		$item = [[
			'key' => 'folder.key.zip',
			'value' => 'restored-folder',
			'entity' => 'folder',
			'target' => 'full',
			'pointer' => 'folder--key--zip',
		]];

		$result = $get->init($item, null, true);

		$this->assertSame(['folder.key.zip' => 'restored-folder'], $result['added']);
		$this->assertSame('folder payload', file_get_contents($destination . '/nested/data.txt'));
		$this->assertFileDoesNotExist($destination . '.restore.zip');
		$this->assertNull($messages->get('error'));
	}

	/**
	 * File pushes load exact local bytes before invoking the isolated Git boundary.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testSetFilePushesExactContentAndCachesTheOutcome(): void
	{
		$file = $this->writeTemporaryFile('upload/file.txt', "payload\nwith bytes");
		$creates = new ArrayObject();
		[$set, $tracker, $messages] = $this->setFile($creates);
		$item = [
			'key' => 'file.key.txt',
			'pointer' => 'file--key--txt',
			'value' => 'upload/file.txt',
			'entity' => 'file',
			'target' => 'full',
			'full' => $file,
		];

		$this->assertTrue($set->items([$item]));
		$this->assertCount(1, $creates);
		$this->assertSame('src/file_folder/file.key.txt', $creates[0][2]);
		$this->assertSame("payload\nwith bytes", $creates[0][3]);
		$this->assertTrue($tracker->get('file.save.file--key--txt'));
		$this->assertCount(1, $messages->get('success'));

		$this->assertTrue($set->items([$item]));
		$this->assertCount(1, $creates);
		$this->assertCount(1, $messages->get('success'));
	}

	/**
	 * Folder pushes create a readable archive and remove the local staging file.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testSetFolderPushesReadableArchiveAndRemovesStagingFile(): void
	{
		$folder = $this->createTemporaryDirectory('upload-folder');
		$this->writeTemporaryFile('upload-folder/nested/payload.txt', 'zipped payload');
		$creates = new ArrayObject();
		[$set, $tracker, $messages] = $this->setFolder($creates);
		$item = [
			'key' => 'folder.key.zip',
			'pointer' => 'folder--key--zip',
			'value' => 'upload-folder',
			'entity' => 'folder',
			'target' => 'full',
			'full' => $folder,
		];

		$this->assertTrue($set->items([$item]));
		$this->assertCount(1, $creates);
		$this->assertStringStartsWith('PK', $creates[0][3]);
		$this->assertFileDoesNotExist($folder . '.folder.key.zip');
		$this->assertTrue($tracker->get('folder.save.folder--key--zip'));
		$this->assertNull($messages->get('error'));

		$archivePath = $this->writeTemporaryFile('captured-folder.zip', $creates[0][3]);
		$zip = new ZipArchive();
		$this->assertTrue($zip->open($archivePath));
		$payload = null;
		for ($index = 0; $index < $zip->numFiles; $index++)
		{
			$name = $zip->getNameIndex($index);
			if (is_string($name) && str_ends_with($name, 'nested/payload.txt'))
			{
				$payload = $zip->getFromIndex($index);
				break;
			}
		}
		$zip->close();
		$this->assertSame('zipped payload', $payload);
	}

	/**
	 * Invalid upload entries fail the batch without invoking the Git boundary.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testSetContentRejectsMissingContractFieldsBeforeRemoteWork(): void
	{
		$creates = new ArrayObject();
		[$set, $tracker, $messages] = $this->setFile($creates);

		$this->assertFalse($set->items([['key' => 'incomplete']]));
		$this->assertCount(0, $creates);
		$this->assertCount(1, $messages->get('error'));
		$this->assertNull($tracker->get('file.save'));
	}

	/**
	 * A failed pull must not be cached as a successful later item operation.
	 *
	 * The batch initializer currently stores a true "processed" marker before
	 * path resolution, while item() reads the same marker as an outcome cache.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testFailedInitDoesNotPoisonItemOutcomeCache(): void
	{
		[$get] = $this->getFile(
			static fn(string $guid): ?object => null,
			static fn(string $path, string $target): ?string => null
		);
		$item = [[
			'key' => 'broken.key.txt',
			'value' => 'outside.txt',
			'entity' => 'file',
			'target' => 'full',
			'pointer' => 'broken--key--txt',
		]];

		$get->init($item);

		$this->assertFalse($get->item('broken.key.txt'));
	}

	/**
	 * Failed archive validation must preserve the last known-good folder.
	 *
	 * The current implementation removes the destination before it validates or
	 * extracts the replacement archive, making failed updates non-atomic.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testFailedFolderRestorePreservesExistingDirectoryAtomically(): void
	{
		$destination = $this->createTemporaryDirectory('atomic-folder');
		$sentinel = $this->writeTemporaryFile('atomic-folder/sentinel.txt', 'known good');
		[$get] = $this->getFolder(
			static fn(string $guid): ?object => (object) ['content' => 'not a zip archive'],
			static fn(string $path, string $target): ?string => $destination
		);
		$item = [[
			'key' => 'atomic.key.zip',
			'value' => 'atomic-folder',
			'entity' => 'folder',
			'target' => 'full',
			'pointer' => 'atomic--key--zip',
		]];

		$result = $get->init($item, null, true);

		$this->assertSame([], $result['added']);
		$this->assertFileDoesNotExist($destination . '.restore.zip');
		$this->assertFileExists($sentinel);
		$this->assertSame('known good', file_get_contents($sentinel));
	}

	/**
	 * Build a file puller with isolated collaborators.
	 *
	 * @param   Closure  $remoteGet  Remote lookup callback.
	 * @param   Closure  $fullPath   Path reconstruction callback.
	 *
	 * @return  array{GetFile, Tracker, MessageBus}
	 * @since   1.0.0
	 */
	private function getFile(Closure $remoteGet, Closure $fullPath): array
	{
		[$config, $grep, $item, $normalize, $tracker, $messages] = $this->getDependencies(
			'File',
			$remoteGet,
			$fullPath
		);

		return [
			new GetFile($config, $grep, $item, $normalize, $tracker, $messages),
			$tracker,
			$messages,
		];
	}

	/**
	 * Build a folder puller with isolated collaborators.
	 *
	 * @param   Closure  $remoteGet  Remote lookup callback.
	 * @param   Closure  $fullPath   Path reconstruction callback.
	 *
	 * @return  array{GetFolder, MessageBus}
	 * @since   1.0.0
	 */
	private function getFolder(Closure $remoteGet, Closure $fullPath): array
	{
		[$config, $grep, $item, $normalize, $tracker, $messages] = $this->getDependencies(
			'Folder',
			$remoteGet,
			$fullPath
		);

		return [
			new GetFolder($config, $grep, $item, $normalize, $tracker, $messages),
			$messages,
		];
	}

	/**
	 * Build common pull collaborators.
	 *
	 * @param   string   $area       Package area.
	 * @param   Closure  $remoteGet  Remote lookup callback.
	 * @param   Closure  $fullPath   Path reconstruction callback.
	 *
	 * @return  array{ConfigInterface, GrepInterface, ItemInterface, Normalize, Tracker, MessageBus}
	 * @since   1.0.0
	 */
	private function getDependencies(string $area, Closure $remoteGet, Closure $fullPath): array
	{
		$config = $this->createStub(ConfigInterface::class);
		$config->method('getGuidField')->willReturn('key');
		$config->method('getArea')->willReturn($area);
		$grep = $this->createStub(GrepInterface::class);
		$grep->method('get')->willReturnCallback($remoteGet);
		$item = $this->createStub(ItemInterface::class);
		$normalize = $this->createStub(Normalize::class);
		$normalize->method('full')->willReturnCallback($fullPath);

		return [$config, $grep, $item, $normalize, new Tracker(), new MessageBus()];
	}

	/**
	 * Build a file pusher around a recording in-memory Git boundary.
	 *
	 * @param   ArrayObject<int, array<int, mixed>>  $creates  Captured create calls.
	 *
	 * @return  array{SetFile, Tracker, MessageBus}
	 * @since   1.0.0
	 */
	private function setFile(ArrayObject $creates): array
	{
		[$arguments, $tracker, $messages] = $this->buildSetDependencies('File', $creates);

		return [new SetFile(...$arguments), $tracker, $messages];
	}

	/**
	 * Build a folder pusher around a recording in-memory Git boundary.
	 *
	 * @param   ArrayObject<int, array<int, mixed>>  $creates  Captured create calls.
	 *
	 * @return  array{SetFolder, Tracker, MessageBus}
	 * @since   1.0.0
	 */
	private function setFolder(ArrayObject $creates): array
	{
		[$arguments, $tracker, $messages] = $this->buildSetDependencies('Folder', $creates);

		return [new SetFolder(...$arguments), $tracker, $messages];
	}

	/**
	 * Build common pusher constructor arguments.
	 *
	 * @param   string                                  $area     Package area.
	 * @param   ArrayObject<int, array<int, mixed>>     $creates  Captured create calls.
	 *
	 * @return  array{array<int, mixed>, Tracker, MessageBus}
	 * @since   1.0.0
	 */
	private function buildSetDependencies(string $area, ArrayObject $creates): array
	{
		$tracker = new Tracker();
		$messages = new MessageBus();
		$grep = $this->createStub(GrepInterface::class);
		$grep->method('get')->willReturn(null);
		$grep->method('getRemoteIndex')->willReturn(null);
		$config = $this->createStub(ConfigInterface::class);
		$config->method('getArea')->willReturn($area);
		$config->method('getTable')->willReturn('file_system');
		$config->method('getPlaceholders')->willReturn([]);
		$config->method('getSrcPath')->willReturn('src/file_folder');
		$config->method('getSettingsName')->willReturn('');
		$config->method('getIndexPath')->willReturn('');
		$config->method('getIndexMap')->willReturn([
			'name' => 'index_map_IndexName',
			'path' => 'index_map_IndexPath',
			'guid' => 'index_map_IndexGUID',
		]);
		$config->method('getTitleName')->willReturn('value');
		$config->method('getGuidField')->willReturn('key');
		$config->method('hasMainReadme')->willReturn(false);
		$gitea = $this->createStub(GiteaContents::class);
		$gitea->method('create')->willReturnCallback(
			static function (...$arguments) use ($creates): object
			{
				$creates[] = $arguments;

				return (object) ['sha' => 'created'];
			}
		);
		$github = (new ReflectionClass(GithubContents::class))->newInstanceWithoutConstructor();
		$git = new GitContents($gitea, $github);
		$repo = (object) [
			'guid' => 'repo-guid',
			'organisation' => 'example',
			'repository' => 'definitions',
			'write_branch' => 'main',
			'author_name' => 'Test Agent',
			'author_email' => 'test@example.test',
			'target' => 'gitea',
		];

		return [[
			$tracker,
			$messages,
			$grep,
			$this->createStub(ResolverInterface::class),
			$config,
			$this->createStub(ItemReadmeInterface::class),
			$this->createStub(MainReadmeInterface::class),
			$git,
			$this->createStub(ItemsInterface::class),
			[$repo],
		], $tracker, $messages];
	}

	/**
	 * Create a valid zip archive fixture inside the per-test root.
	 *
	 * @param   string  $path     Archive member path.
	 * @param   string  $content  Archive member content.
	 *
	 * @return  string  Complete archive bytes.
	 * @since   1.0.0
	 */
	private function archiveContent(string $path, string $content): string
	{
		$archivePath = $this->temporaryPath('fixture-' . bin2hex(random_bytes(4)) . '.zip');
		$zip = new ZipArchive();
		$this->assertTrue($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE));
		$this->assertTrue($zip->addFromString($path, $content));
		$this->assertTrue($zip->close());
		$archive = file_get_contents($archivePath);

		$this->assertIsString($archive);

		return $archive;
	}
}
