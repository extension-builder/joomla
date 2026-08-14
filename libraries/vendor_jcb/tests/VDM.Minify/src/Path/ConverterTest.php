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

namespace VDM\Minify\Tests\Path;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use VDM\Minify\Path\Converter;


/**
 * Relative path converter test.
 *
 * @since  6.1.6
 */
#[CoversClass(Converter::class)]
final class ConverterTest extends TestCase
{
	/**
	 * Rebase relative assets between source and target directories.
	 *
	 * @param   string  $from      The original base.
	 * @param   string  $to        The target base.
	 * @param   string  $path      The path to convert.
	 * @param   string  $expected  The expected rebased path.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('relativePathProvider')]
	public function testConvertRebasesRelativePath(
		string $from,
		string $to,
		string $path,
		string $expected
	): void
	{
		$converter = new Converter($from, $to, '/test-root');

		$this->assertSame($expected, $converter->convert($path));
	}

	/**
	 * Provide representative path topology and normalization cases.
	 *
	 * @return  iterable<string, array{string, string, string, string}>
	 * @since   6.1.6
	 */
	public static function relativePathProvider(): iterable
	{
		yield 'up and into sibling tree' => [
			'/home/site/core/layout/css',
			'/home/site/cache/minified_css',
			'../images/img.gif',
			'../../core/layout/images/img.gif'
		];

		yield 'target is source parent' => [
			'/css/imports',
			'/css',
			'../../images/icon.jpg',
			'../images/icon.jpg'
		];

		yield 'sibling target' => [
			'/project/css',
			'/project/dist',
			'asset.png',
			'../css/asset.png'
		];

		yield 'dot and parent segments normalized' => [
			'/project/css',
			'/project/dist',
			'./themes/../images/icon.png',
			'../css/images/icon.png'
		];

		yield 'file-shaped bases use parent directories' => [
			'/project/css/source.css',
			'/project/dist/bundle.css',
			'../images/icon.png',
			'../images/icon.png'
		];

		yield 'identical bases are a no-op' => [
			'/project/css',
			'/project/css',
			'../images/icon.png',
			'../images/icon.png'
		];
	}

	/**
	 * Leave absolute paths unchanged regardless of base topology.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConvertPreservesAbsolutePath(): void
	{
		$converter = new Converter('/project/css', '/project/dist');

		$this->assertSame('/shared/icon.svg', $converter->convert('/shared/icon.svg'));
	}
}
