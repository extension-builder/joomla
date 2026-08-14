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

namespace VDM\Joomla\Tests\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Utilities\MimeHelper;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * File-extension catalogue and MIME detection contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(MimeHelper::class)]
final class MimeHelperTest extends FilesystemTestCase
{
	/**
	 * Expose a stable, duplicate-free catalogue containing core extension families.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCompleteExtensionCatalogueContainsRepresentativeFamilies(): void
	{
		$extensions = MimeHelper::getFileExtensions();

		$this->assertGreaterThan(900, count($extensions));
		$this->assertSame($extensions, array_values(array_unique($extensions)));
		$this->assertContains('css', $extensions);
		$this->assertContains('json', $extensions);
		$this->assertContains('jpg', $extensions);
		$this->assertContains('mp4', $extensions);
		$this->assertContains('zip', $extensions);
	}

	/**
	 * Filter and group extensions by the public family catalogue.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTargetedExtensionCatalogueUsesExtensionIdentityAndMimeGroups(): void
	{
		$flat = MimeHelper::getFileExtensions('image');
		$grouped = MimeHelper::getFileExtensions('image', true);

		$this->assertSame('jpg', $flat['jpg']);
		$this->assertSame('woff2', $flat['woff2']);
		$this->assertArrayNotHasKey('txt', $flat);
		$this->assertSame(['image', 'model', 'font'], array_keys($grouped));
		$this->assertSame('svg', $grouped['image']['svg']);
		$this->assertSame('woff', $grouped['font']['woff']);
		$this->assertSame('dae', $grouped['model']['dae']);
		$this->assertSame([], MimeHelper::getFileExtensions('unsupported'));
	}

	/**
	 * Normalize extensions from paths and filenames.
	 *
	 * @param   string  $file      Candidate path or filename.
	 * @param   string  $expected  Expected lowercase extension.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('extensionProvider')]
	public function testExtensionNormalizesPathSuffix(string $file, string $expected): void
	{
		$this->assertSame($expected, MimeHelper::extension($file));
	}

	/**
	 * Provide extension case, multi-dot, dotfile, and absent-suffix cases.
	 *
	 * @return  iterable<string, array{string, string}>
	 * @since   6.1.6
	 */
	public static function extensionProvider(): iterable
	{
		yield 'uppercase suffix' => ['/tmp/Report.PDF', 'pdf'];
		yield 'multiple dots' => ['archive.tar.GZ', 'gz'];
		yield 'dotfile' => ['.env', 'env'];
		yield 'no suffix' => ['/tmp/README', ''];
		yield 'trailing dot' => ['file.', ''];
	}

	/**
	 * Resolve nonexistent paths from the extension map with a binary fallback.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMimeTypeFallsBackToFilenameCatalogueWhenFileIsUnavailable(): void
	{
		$this->assertSame('application/json', MimeHelper::mimeType('/missing/data.json'));
		$this->assertSame('image/svg+xml', MimeHelper::mimeType('/missing/vector.svg'));
		$this->assertSame(
			'application/octet-stream',
			MimeHelper::mimeType('/missing/archive.unknown-jcb-extension')
		);
	}

	/**
	 * Prefer physical file content over a misleading filename suffix.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMimeTypeInspectsReadablePhysicalContent(): void
	{
		$file = $this->writeTemporaryFile(
			'uploads/not-really-an-image.jpg',
			"This is deterministic plain text.\n"
		);

		$this->assertSame('text/plain', MimeHelper::mimeType($file));
	}
}
