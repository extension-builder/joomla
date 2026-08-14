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

namespace VDM\Tests\Support;


use FilesystemIterator;
use InvalidArgumentException;
use RuntimeException;


/**
 * Test case with a unique, recursively cleaned temporary filesystem root.
 *
 * @since  1.0.0
 */
abstract class FilesystemTestCase extends JoomlaTestCase
{
	/**
	 * Absolute temporary root for the current test.
	 *
	 * @var    string|null
	 * @since  1.0.0
	 */
	private ?string $temporaryDirectory = null;

	/**
	 * Create a unique temporary root for the current test.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->temporaryDirectory = rtrim(sys_get_temp_dir(), '/\\')
			. '/jcb-tests-' . bin2hex(random_bytes(16));

		if (!mkdir($this->temporaryDirectory, 0700, true) && !is_dir($this->temporaryDirectory))
		{
			throw new RuntimeException('Unable to create temporary test directory: ' . $this->temporaryDirectory);
		}
	}

	/**
	 * Get an absolute path constrained to the current test's temporary root.
	 *
	 * @param   string  $relativePath  Optional relative path below the temporary root.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function temporaryPath(string $relativePath = ''): string
	{
		if ($this->temporaryDirectory === null)
		{
			throw new RuntimeException('The temporary test directory has not been initialized.');
		}

		if ($relativePath === '')
		{
			return $this->temporaryDirectory;
		}

		$relativePath = str_replace('\\', '/', $relativePath);
		$segments = explode('/', $relativePath);

		if (str_starts_with($relativePath, '/')
			|| preg_match('/^[A-Za-z]:/', $relativePath) === 1
			|| str_contains($relativePath, "\0")
			|| in_array('..', $segments, true))
		{
			throw new InvalidArgumentException('Temporary paths must be relative and cannot traverse their root.');
		}

		return $this->temporaryDirectory . '/' . $relativePath;
	}

	/**
	 * Create a directory below the current test's temporary root.
	 *
	 * @param   string  $relativePath  The relative directory path.
	 *
	 * @return  string  The absolute directory path.
	 * @since   1.0.0
	 */
	protected function createTemporaryDirectory(string $relativePath): string
	{
		$directory = $this->temporaryPath($relativePath);

		if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory))
		{
			throw new RuntimeException('Unable to create temporary directory: ' . $directory);
		}

		return $directory;
	}

	/**
	 * Write a file below the current test's temporary root.
	 *
	 * @param   string  $relativePath  The relative file path.
	 * @param   string  $contents      The complete file contents.
	 *
	 * @return  string  The absolute file path.
	 * @since   1.0.0
	 */
	protected function writeTemporaryFile(string $relativePath, string $contents): string
	{
		$file = $this->temporaryPath($relativePath);
		$directory = dirname($file);

		if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory))
		{
			throw new RuntimeException('Unable to create temporary file directory: ' . $directory);
		}

		if (file_put_contents($file, $contents) === false)
		{
			throw new RuntimeException('Unable to write temporary test file: ' . $file);
		}

		return $file;
	}

	/**
	 * Remove the current test's temporary root before restoring global state.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		try
		{
			if ($this->temporaryDirectory !== null && is_dir($this->temporaryDirectory))
			{
				$this->removeDirectory($this->temporaryDirectory);
			}
		}
		finally
		{
			$this->temporaryDirectory = null;
			parent::tearDown();
		}
	}

	/**
	 * Recursively remove a directory without following symbolic links.
	 *
	 * @param   string  $directory  The absolute directory to remove.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function removeDirectory(string $directory): void
	{
		$iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

		foreach ($iterator as $item)
		{
			$path = $item->getPathname();

			if ($item->isLink() || !$item->isDir())
			{
				if (!unlink($path))
				{
					throw new RuntimeException('Unable to remove temporary test file: ' . $path);
				}

				continue;
			}

			if ($item->isDir())
			{
				$this->removeDirectory($path);
			}
		}

		unset($iterator);

		if (!rmdir($directory))
		{
			throw new RuntimeException('Unable to remove temporary test directory: ' . $directory);
		}
	}
}
