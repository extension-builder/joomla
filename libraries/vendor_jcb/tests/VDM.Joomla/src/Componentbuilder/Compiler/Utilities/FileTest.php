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
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Joomla\Utilities\FileHelper;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Compiler file creation and blank-index contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(File::class)]
#[UsesClass(Counter::class)]
#[UsesClass(FileHelper::class)]
#[UsesClass(Paths::class)]
final class FileTest extends FilesystemTestCase
{
	/**
	 * Copy the template index into a component-relative directory and increment once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHtmlCopiesTemplateIntoComponentPathAndCountsFile(): void
	{
		$template = $this->createTemporaryDirectory('template');
		$component = $this->createTemporaryDirectory('component');
		$this->writeTemporaryFile('template/index.html', '<!doctype html><title>guard</title>');
		$this->createTemporaryDirectory('component/admin/views');
		$counter = $this->createStub(Counter::class);
		$paths = $this->createStub(Paths::class);
		$paths->method('__get')->willReturnMap([
			['template_path', $template],
			['component_path', $component]
		]);
		$subject = new File($counter, $paths);

		$subject->html('admin/views');

		$this->assertSame(
			'<!doctype html><title>guard</title>',
			file_get_contents($component . '/admin/views/index.html')
		);
		$this->assertSame(1, $counter->file);
	}

	/**
	 * Honor an explicit root and support the root directory itself.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHtmlSupportsExplicitRootWithoutSubpath(): void
	{
		$template = $this->createTemporaryDirectory('template');
		$destination = $this->createTemporaryDirectory('standalone');
		$this->writeTemporaryFile('template/index.html', 'blank index');
		$counter = $this->createStub(Counter::class);
		$paths = $this->createStub(Paths::class);
		$paths->method('__get')->willReturnMap([
			['template_path', $template],
			['component_path', '/unused']
		]);

		(new File($counter, $paths))->html('', $destination);

		$this->assertSame('blank index', file_get_contents($destination . '/index.html'));
		$this->assertSame(1, $counter->file);
	}

	/**
	 * Delegate binary-safe overwrite behavior to the shared file helper.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testWriteCreatesAndOverwritesExactBytes(): void
	{
		$counter = $this->createStub(Counter::class);
		$paths = $this->createStub(Paths::class);
		$subject = new File($counter, $paths);
		$file = $this->temporaryPath('output.bin');

		$this->assertTrue($subject->write($file, "first\0value"));
		$this->assertSame("first\0value", file_get_contents($file));
		$this->assertTrue($subject->write($file, 'second'));
		$this->assertSame('second', file_get_contents($file));
		$this->assertSame(0, $counter->file);
	}
}
