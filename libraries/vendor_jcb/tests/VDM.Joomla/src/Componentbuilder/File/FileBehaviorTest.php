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

namespace VDM\Joomla\Tests\Componentbuilder\File;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\File\Display;
use VDM\Joomla\Componentbuilder\File\Handler;
use VDM\Joomla\Componentbuilder\File\Image;
use VDM\Joomla\Componentbuilder\File\Manager;
use VDM\Joomla\Componentbuilder\File\Type;
use VDM\Joomla\Componentbuilder\File\TypeDefinition;
use VDM\Joomla\File\Definition;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Interfaces\File\AgentInterface;
use VDM\Joomla\Utilities\UploadHelper;
use VDM\Tests\Support\ComponentbuilderFileManagerFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * File display, upload policy, image, type, and persistence behavior tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Display::class)]
#[CoversClass(Handler::class)]
#[CoversClass(Image::class)]
#[CoversClass(Manager::class)]
#[CoversClass(Type::class)]
#[UsesClass(Definition::class)]
#[UsesClass(TypeDefinition::class)]
#[UsesClass(UploadHelper::class)]
final class FileBehaviorTest extends FilesystemTestCase
{
	/**
	 * Original shared upload-helper state.
	 *
	 * @var    array<string, mixed>
	 * @since  6.1.6
	 */
	private array $uploadState = [];

	/**
	 * Isolate shared upload policies and errors.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$reflection = new ReflectionClass(UploadHelper::class);
		$this->uploadState = [
			'useStreams' => UploadHelper::$useStreams,
			'allowUnsafe' => UploadHelper::$allowUnsafe,
			'safeFileOptions' => UploadHelper::$safeFileOptions,
			'enqueueError' => UploadHelper::$enqueueError,
			'legalFormats' => UploadHelper::$legalFormats,
			'errors' => $reflection->getProperty('errors')->getValue(),
		];
		$reflection->getProperty('errors')->setValue(null, []);
	}

	/**
	 * Restore shared upload policies and errors.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		$reflection = new ReflectionClass(UploadHelper::class);
		UploadHelper::$useStreams = $this->uploadState['useStreams'];
		UploadHelper::$allowUnsafe = $this->uploadState['allowUnsafe'];
		UploadHelper::$safeFileOptions = $this->uploadState['safeFileOptions'];
		UploadHelper::$enqueueError = $this->uploadState['enqueueError'];
		UploadHelper::$legalFormats = $this->uploadState['legalFormats'];
		$reflection->getProperty('errors')->setValue(null, $this->uploadState['errors']);
		$this->uploadState = [];

		parent::tearDown();
	}

	/**
	 * Filter records by entity, target, and active view levels before display.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDisplayFiltersUnauthorizedAndWrongTargetFiles(): void
	{
		$user = $this->createMock(User::class);
		$user->expects($this->once())->method('getAuthorisedViewLevels')->willReturn([1, 3]);
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('getIdentity')->willReturn($user);
		$this->setJoomlaApplication($app);

		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->never())->method('table');
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->once())->method('table')->with('file')->willReturnSelf();
		$items->expects($this->once())->method('get')->with(['entity-guid'], 'entity')->willReturn([
			(object) ['guid' => 'kept', 'name' => 'kept.txt', 'entity_type' => 'admin_view', 'access' => 3],
			(object) ['guid' => 'wrong-target', 'name' => 'wrong.txt', 'entity_type' => 'field', 'access' => 3],
			(object) ['guid' => 'denied', 'name' => 'denied.txt', 'entity_type' => 'admin_view', 'access' => 7],
		]);

		$result = (new Display($item, $items))->get('entity-guid', 'admin_view');

		$this->assertCount(1, $result);
		$this->assertSame('kept', $result[0]->guid);
		$this->assertSame('error', $result[0]->type_name);
		$this->assertObjectNotHasProperty('task', $result[0]);
		$this->assertObjectNotHasProperty('link', $result[0]);
	}

	/**
	 * Expose every upload policy through fluent instance configuration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHandlerConfiguresPoliciesReportsErrorsAndRemovesFiles(): void
	{
		$subject = new Handler();
		$this->assertSame($subject, $subject->setUseStreams(true));
		$this->assertSame($subject, $subject->setAllowUnsafe(true));
		$this->assertSame($subject, $subject->setSafeFileOptions(['phar' => false]));
		$this->assertSame($subject, $subject->setEnqueueError(false));
		$this->assertSame($subject, $subject->setLegalFormats(['txt', 'json']));
		$this->assertTrue(UploadHelper::$useStreams);
		$this->assertTrue(UploadHelper::$allowUnsafe);
		$this->assertSame(['phar' => false], UploadHelper::$safeFileOptions);
		$this->assertFalse(UploadHelper::$enqueueError);
		$this->assertSame(['txt', 'json'], UploadHelper::$legalFormats);

		$reflection = new ReflectionClass(UploadHelper::class);
		$reflection->getProperty('errors')->setValue(null, ['first error', 'second error']);
		$this->assertSame(['first error', 'second error'], $subject->getErrors(false));
		$this->assertSame("first error \nsecond error", $subject->getErrors());

		$file = $this->writeTemporaryFile('discard/upload.txt', 'temporary');
		$this->assertTrue($subject->removeFile($file));
		$this->assertFileDoesNotExist($file);
		$this->assertFalse($subject->removeFile($file));
	}

	/**
	 * Read image metadata, copy matching dimensions, and reject malformed sets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testImageProcessesIsolatedFilesAndReportsMetadata(): void
	{
		$source = $this->writeTinyPng('images/source.png');
		$destination = $this->temporaryPath('generated/copy.png');
		$subject = new Image();

		$result = $subject->cropResize($source, $destination, 1, 1);

		$this->assertSame('copy.png', $result['name']);
		$this->assertSame('png', $result['extension']);
		$this->assertSame($destination, $result['path']);
		$this->assertGreaterThan(0, $result['size']);
		$this->assertFileEquals($source, $destination);
		$this->assertSame(234, $subject->info('images/banners/white.png', 'width'));
		$this->assertSame('png', $subject->info('images/banners/white.png'));
		$this->assertFalse($subject->info('images/missing.png'));

		$processed = $subject->process($source, $this->temporaryPath('batch'), [
			['name' => 'same.png', 'width' => 1, 'height' => 1],
			['name' => 'invalid.png', 'width' => 'wide', 'height' => 1],
			['height' => 1],
		]);
		$this->assertSame('same.png', $processed['same.png']['name']);
		$this->assertNull($processed['invalid.png']);
		$this->assertNull($processed['unknown']);
	}

	/**
	 * Absolute paths outside JPATH_SITE are documented as valid image inputs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testImageInfoAcceptsAnAbsolutePathOutsideJoomlaRoot(): void
	{
		$this->assertSame(1, (new Image())->info($this->writeTinyPng('external.png'), 'width'));
	}

	/**
	 * Map a file-type record into UI and immutable upload definitions.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTypeMapsTargetsFormatsCropsAndWritablePaths(): void
	{
		$path = $this->createTemporaryDirectory('uploads/images');
		$record = (object) [
			'guid' => 'image-guid',
			'name' => 'Profile image',
			'access' => 2,
			'quantity' => 3,
			'download_access' => 4,
			'target' => ['admin_view', 'field'],
			'type' => 1,
			'image_formats' => ['png', 'jpg'],
			'filter' => 'image',
			'path' => $path,
			'crop' => [(object) ['name' => 'thumb.png', 'width' => 80, 'height' => 80]],
			'display_fields' => ['caption'],
			'param_fields' => ['quality'],
		];
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->exactly(3))->method('table')->with('file_type')->willReturnSelf();
		$item->expects($this->exactly(3))->method('get')->with('image-guid')->willReturn($record);
		$subject = new Type($item);

		$this->assertSame(
			[
				'name' => 'image',
				'allow' => '*.(png|jpg)',
				'allow_span' => '(formats allowed: <b>png, jpg</b>)',
				'file_type_span' => 'Profile image',
				'display_fields' => ['caption'],
				'param_fields' => ['quality'],
			],
			$subject->get('image-guid', 'admin_view')
		);
		$definition = $subject->definition('image-guid', 'field');
		$this->assertSame('image-guid', $definition->guid());
		$this->assertSame('image', $definition->type());
		$this->assertSame(['png', 'jpg'], $definition->formats());
		$this->assertSame($path, $definition->path());
		$this->assertSame([['name' => 'thumb.png', 'width' => 80, 'height' => 80]], $definition->crop());
		$this->assertNull($subject->get('image-guid', 'component'));
	}

	/**
	 * Orchestrate upload acquisition and persist the modeled file record.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testManagerUploadsThroughTypeAgentAndDataBoundaries(): void
	{
		$path = $this->createTemporaryDirectory('manager/files');
		$filePath = $this->writeTemporaryFile('manager/files/report.txt', 'report');
		$typeRecord = (object) [
			'guid' => 'type-guid',
			'name' => 'Reports',
			'access' => 2,
			'quantity' => 0,
			'download_access' => 3,
			'target' => ['admin_view'],
			'type' => 2,
			'document_formats' => ['txt'],
			'filter' => 'file',
			'path' => $path,
		];
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->exactly(2))->method('table')->willReturnSelf();
		$item->expects($this->once())->method('get')->with('type-guid')->willReturn($typeRecord);
		$item->expects($this->once())->method('set')->with($this->callback(
			static function (object $record) use ($filePath): bool
			{
				return $record->name === 'report.txt'
					&& $record->file_type === 'type-guid'
					&& $record->extension === 'txt'
					&& $record->file_path === $filePath
					&& $record->entity_type === 'admin_view'
					&& $record->entity === 'entity-guid'
					&& $record->access === 3
					&& $record->created_by === 42
					&& preg_match('/^[a-f0-9-]{36}$/', $record->guid) === 1;
			}
		))->willReturn(true);
		$items = $this->createStub(ItemsInterface::class);
		$file = new Definition([
			'name' => 'report.txt',
			'file_name' => 'stored-report.txt',
			'full_path' => $filePath,
		]);
		$agent = $this->createMock(AgentInterface::class);
		$agent->expects($this->once())->method('type')->willReturnSelf();
		$agent->expects($this->once())->method('get')->willReturn($file);
		$user = $this->createMock(User::class);
		$user->id = 42;
		$user->expects($this->once())->method('getAuthorisedViewLevels')->willReturn([1, 2]);
		$subject = new ComponentbuilderFileManagerFixture(
			$item,
			$items,
			new Type($item),
			$agent,
			new Image(),
			$user
		);

		$subject->upload('type-guid', 'entity-guid', 'admin_view');

		$this->assertSame('file', $subject->getTable());
		$this->assertSame($subject, $subject->table('custom_file'));
		$this->assertSame('custom_file', $subject->getTable());
	}

	/**
	 * Refuse to attach a file to a record owned by somebody else.
	 *
	 * The file type's view level says who may use the type. It says nothing
	 * about the record the file is linked to, so without an entity check any
	 * user allowed to upload could replace another owner's attachments.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testManagerRefusesToAttachAFileToAnotherOwnersEntity(): void
	{
		$typeRecord = (object) [
			'guid' => 'type-guid',
			'name' => 'Reports',
			'access' => 2,
			'quantity' => 0,
			'download_access' => 3,
			'target' => ['admin_view'],
			'type' => 2,
			'document_formats' => ['txt'],
			'filter' => 'file',
			'path' => $this->createTemporaryDirectory('manager/refused'),
		];
		$item = $this->createMock(ItemInterface::class);
		$item->method('table')->willReturnSelf();
		$item->method('get')->with('type-guid')->willReturn($typeRecord);
		// nothing may be stored when the entity belongs to somebody else
		$item->expects($this->never())->method('set');

		$items = $this->createMock(ItemsInterface::class);
		$items->method('table')->with('admin_view')->willReturnSelf();
		$items->expects($this->once())
			->method('values')
			->with(['entity-guid'], 'guid', 'created_by')
			->willReturn([99]);

		$agent = $this->createMock(AgentInterface::class);
		// the upload never reaches the agent
		$agent->expects($this->never())->method('get');

		$user = $this->createStub(User::class);
		$user->id = 42;
		$user->method('getAuthorisedViewLevels')->willReturn([1, 2]);
		$user->method('authorise')->willReturn(false);

		$subject = new ComponentbuilderFileManagerFixture(
			$item,
			$items,
			new Type($item),
			$agent,
			new Image(),
			$user
		);

		$this->expectException(\InvalidArgumentException::class);

		$subject->upload('type-guid', 'entity-guid', 'admin_view');
	}

	/**
	 * Normalize names, number crop batches, and select oldest valid timestamps.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testManagerAppliesNamingNumberingAndAgePolicies(): void
	{
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->once())->method('table')->with('file')->willReturnSelf();
		$items->expects($this->once())->method('values')->with(['entity'], 'entity')->willReturn(['a', 'b', 'c', 'd']);
		$manager = $this->manager($this->createStub(ItemInterface::class), $items);
		$type = $this->typeDefinition(0, [
			['name' => 'small.png', 'width' => 10, 'height' => 10],
			['name' => 'large.png', 'width' => 100, 'height' => 100],
		]);
		$file = $this->createStub(\VDM\Joomla\Interfaces\File\DefinitionInterface::class);
		$file->method('name')->willReturn('archive.release.tar.gz');

		$this->assertSame('archive.release.tar', $manager->fileName($file, 'fallback'));
		$this->assertSame(3, $manager->fileNumber($type, 'entity'));
		$oldest = $manager->oldest([
			(object) ['guid' => 'new', 'created' => '2026-08-14 12:00:00'],
			(object) ['guid' => 'invalid', 'created' => 'not-a-date'],
			(object) ['guid' => 'old', 'created' => '2024-01-01 00:00:00'],
			(object) ['guid' => 'middle', 'created' => '2025-01-01 00:00:00'],
		], 2);
		$this->assertSame(['old'], array_column($oldest, 'guid'));
	}

	/**
	 * Enforce entity/type quantity by deleting the oldest database and disk row.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testManagerEnforcesQuantityForMatchingEntityFiles(): void
	{
		$oldPath = $this->writeTemporaryFile('limits/old.txt', 'old');
		$files = [
			(object) ['guid' => 'old', 'file_path' => $oldPath, 'created' => '2024-01-01 00:00:00', 'file_type' => 'type-guid', 'entity_type' => 'admin_view'],
			(object) ['guid' => 'middle', 'created' => '2025-01-01 00:00:00', 'file_type' => 'type-guid', 'entity_type' => 'admin_view'],
			(object) ['guid' => 'new', 'created' => '2026-01-01 00:00:00', 'file_type' => 'type-guid', 'entity_type' => 'admin_view'],
			(object) ['guid' => 'other', 'created' => '2023-01-01 00:00:00', 'file_type' => 'other-type', 'entity_type' => 'admin_view'],
		];
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->once())->method('table')->with('file')->willReturnSelf();
		$items->expects($this->once())->method('get')->with(['entity-guid'], 'entity')->willReturn($files);
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->once())->method('table')->with('file')->willReturnSelf();
		$item->expects($this->once())->method('delete')->with('old')->willReturn(true);
		$manager = $this->manager($item, $items);

		$manager->enforce($this->typeDefinition(2), 'fallback-guid', 'entity-guid', 'admin_view');

		$this->assertFileDoesNotExist($oldPath);
	}

	/**
	 * Build a manager fixture with isolated boundaries.
	 *
	 * @return  ComponentbuilderFileManagerFixture
	 * @since   6.1.6
	 */
	private function manager(ItemInterface $item, ItemsInterface $items): ComponentbuilderFileManagerFixture
	{
		$user = $this->createStub(User::class);
		$user->method('getAuthorisedViewLevels')->willReturn([1]);

		return new ComponentbuilderFileManagerFixture(
			$item,
			$items,
			new Type($item),
			$this->createStub(AgentInterface::class),
			new Image(),
			$user
		);
	}

	/**
	 * Build a complete file-type definition.
	 *
	 * @param   int                 $quantity  Entity quantity limit.
	 * @param   array<int, mixed>   $crop      Crop variants.
	 *
	 * @return  TypeDefinition
	 * @since   6.1.6
	 */
	private function typeDefinition(int $quantity, array $crop = []): TypeDefinition
	{
		return new TypeDefinition([
			'guid' => 'type-guid',
			'name' => 'Files',
			'access' => 1,
			'quantity' => $quantity,
			'download_access' => 1,
			'field' => 'file',
			'type' => $crop === [] ? 'document' : 'image',
			'filter' => 'file',
			'path' => $this->temporaryPath('files'),
			'formats' => ['txt'],
			'crop' => $crop,
		]);
	}

	/**
	 * Write a deterministic one-pixel PNG.
	 *
	 * @return  string
	 * @since   6.1.6
	 */
	private function writeTinyPng(string $relativePath): string
	{
		return $this->writeTemporaryFile(
			$relativePath,
			(string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
		);
	}
}
