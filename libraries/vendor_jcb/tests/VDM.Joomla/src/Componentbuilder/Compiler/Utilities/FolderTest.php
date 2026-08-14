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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Folder;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Compiler directory creation and selective cleanup contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Folder::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(Counter::class)]
#[UsesClass(File::class)]
final class FolderTest extends FilesystemTestCase
{
	/**
	 * Create a missing path once, count it, and request its blank index.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateBuildsMissingDirectoryAndAddsIndexOnce(): void
	{
		$counter = $this->createStub(Counter::class);
		$file = $this->createMock(File::class);
		$path = $this->temporaryPath('generated/admin/views');
		$file->expects($this->once())->method('html')->with($path, '');
		$subject = new Folder($counter, $file);

		$subject->create($path);
		$subject->create($path);

		$this->assertDirectoryExists($path);
		$this->assertSame(1, $counter->folder);
	}

	/**
	 * Create a missing path without requesting a blank index when disabled.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateCanDisableBlankIndexGeneration(): void
	{
		$counter = $this->createStub(Counter::class);
		$file = $this->createMock(File::class);
		$file->expects($this->never())->method('html');
		$path = $this->temporaryPath('generated/assets');

		(new Folder($counter, $file))->create($path, false);

		$this->assertDirectoryExists($path);
		$this->assertSame(1, $counter->folder);
	}

	/**
	 * Return false for an absent path and remove a complete existing tree.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRemoveRejectsMissingPathAndDeletesCompleteTree(): void
	{
		$subject = new Folder(
			$this->createStub(Counter::class),
			$this->createStub(File::class)
		);
		$path = $this->createTemporaryDirectory('remove/all/nested');
		$this->writeTemporaryFile('remove/all/root.txt', 'root');
		$this->writeTemporaryFile('remove/all/nested/child.txt', 'child');
		$root = dirname($path);

		$this->assertFalse($subject->remove($this->temporaryPath('missing')));
		$this->assertTrue($subject->remove($root));
		$this->assertDirectoryDoesNotExist($root);
	}

	/**
	 * Preserve ignored relative subtrees while deleting all other descendants.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRemovePreservesIgnoredSubtreeAndRoot(): void
	{
		$subject = new Folder(
			$this->createStub(Counter::class),
			$this->createStub(File::class)
		);
		$root = $this->createTemporaryDirectory('remove/selective');
		$this->writeTemporaryFile('remove/selective/keep/nested.txt', 'keep');
		$this->writeTemporaryFile('remove/selective/delete/nested.txt', 'delete');
		$this->writeTemporaryFile('remove/selective/delete.txt', 'delete');

		$this->assertTrue($subject->remove($root, ['keep']));

		$this->assertDirectoryExists($root);
		$this->assertFileExists($root . '/keep/nested.txt');
		$this->assertDirectoryDoesNotExist($root . '/delete');
		$this->assertFileDoesNotExist($root . '/delete.txt');
	}
}
