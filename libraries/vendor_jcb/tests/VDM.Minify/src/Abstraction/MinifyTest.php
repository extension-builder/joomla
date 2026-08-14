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

namespace VDM\Minify\Tests\Abstraction;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Minify\Abstraction\Minify;
use VDM\Minify\Exceptions\IOException;
use VDM\Psr\Cache\CacheItemInterface;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Abstract minifier lifecycle and I/O contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Minify::class)]
#[UsesClass(IOException::class)]
final class MinifyTest extends FilesystemTestCase
{
	/**
	 * Accept nested and variadic sources while preserving their order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAddFlattensSourcesInOrderAndReturnsSameInstance(): void
	{
		$subject = $this->createMinifier('first');

		$returned = $subject->add(['second', ['third']], 'fourth');

		$this->assertSame($subject, $returned);
		$this->assertSame('first|second|third|fourth', $subject->minify());
	}

	/**
	 * Normalize all supported source line endings before execution.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAddNormalizesCarriageReturnLineEndings(): void
	{
		$subject = $this->createMinifier("first\r\nsecond\rthird\nfourth");

		$this->assertSame("first\nsecond\nthird\nfourth", $subject->minify());
	}

	/**
	 * Load readable local files and remove a UTF-8 byte-order mark.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAddLoadsReadableFileAndStripsUtf8Bom(): void
	{
		$path = $this->writeTemporaryFile(
			'source.txt',
			"\xEF\xBB\xBFfirst\r\nsecond"
		);
		$subject = $this->createMinifier();

		$subject->add($path);

		$this->assertSame("first\nsecond", $subject->minify());
	}

	/**
	 * Treat remote and query-bearing values as literal source, never file input.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAddDoesNotImportRemoteOrQueryBearingPaths(): void
	{
		$subject = $this->createMinifier(
			'https://example.test/source.js',
			$this->temporaryPath('source.js') . '?cache=1'
		);

		$this->assertSame(
			'https://example.test/source.js|' . $this->temporaryPath('source.js') . '?cache=1',
			$subject->minify()
		);
	}

	/**
	 * Require addFile input to be an existing readable local file.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAddFileRejectsUnreadablePathWithActionableDiagnostic(): void
	{
		$path = $this->temporaryPath('missing.css');
		$subject = $this->createMinifier();

		$this->expectException(IOException::class);
		$this->expectExceptionMessage(
			'The file "' . $path . '" could not be opened for reading. Check if PHP has enough permissions.'
		);

		$subject->addFile($path);
	}

	/**
	 * Add multiple files and preserve fluent identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAddFileSupportsNestedAndVariadicFileLists(): void
	{
		$first = $this->writeTemporaryFile('first.txt', 'first');
		$second = $this->writeTemporaryFile('second.txt', 'second');
		$third = $this->writeTemporaryFile('third.txt', 'third');
		$subject = $this->createMinifier();

		$returned = $subject->addFile([$first, [$second]], $third);

		$this->assertSame($subject, $returned);
		$this->assertSame('first|second|third', $subject->minify());
	}

	/**
	 * Return minified content and persist those exact bytes when requested.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMinifyWritesExactExecutionResult(): void
	{
		$path = $this->temporaryPath('build/output.txt');
		$this->createTemporaryDirectory('build');
		$subject = $this->createMinifier('first', 'second');

		$result = $subject->minify($path);

		$this->assertSame('first|second', $result);
		$this->assertSame($result, file_get_contents($path));
	}

	/**
	 * Gzip the execution result at the requested level and persist identical bytes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGzipReturnsAndWritesRecoverableExecutionResult(): void
	{
		$path = $this->temporaryPath('build/output.txt.gz');
		$this->createTemporaryDirectory('build');
		$subject = $this->createMinifier(str_repeat('compressible content ', 20));

		$result = $subject->gzip($path, 6);

		$this->assertSame($result, file_get_contents($path));
		$this->assertSame(str_repeat('compressible content ', 20), gzdecode($result));
	}

	/**
	 * Populate and return the same PSR cache item supplied by the caller.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCacheStoresExecutionResultAndReturnsSameItem(): void
	{
		$item = $this->createMock(CacheItemInterface::class);
		$item->expects($this->once())
			->method('set')
			->with('cache content')
			->willReturnSelf();
		$subject = $this->createMinifier('cache content');

		$this->assertSame($item, $subject->cache($item));
	}

	/**
	 * Surface target open failures rather than returning content as if persisted.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMinifyRejectsDirectoryAsOutputFile(): void
	{
		$path = $this->createTemporaryDirectory('output');
		$subject = $this->createMinifier('content');

		$this->expectException(IOException::class);
		$this->expectExceptionMessage(
			'The file "' . $path . '" could not be opened for writing. Check if PHP has enough permissions.'
		);

		$subject->minify($path);
	}

	/**
	 * Create the smallest concrete minifier needed to expose base lifecycle behavior.
	 *
	 * @param   mixed  ...$sources  Optional constructor sources.
	 *
	 * @return  Minify
	 * @since   6.1.6
	 */
	private function createMinifier(mixed ...$sources): Minify
	{
		return new class(...$sources) extends Minify
		{
			/**
			 * Join loaded sources without adding child-minifier behavior.
			 *
			 * @param   string|null  $path  Unused output context.
			 *
			 * @return  string
			 * @since   6.1.6
			 */
			public function execute($path = null): string
			{
				return implode('|', $this->data);
			}
		};
	}
}
