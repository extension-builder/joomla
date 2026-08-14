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

namespace VDM\Joomla\Tests\File;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use VDM\Joomla\File\Definition;
use VDM\Joomla\Interfaces\File\DefinitionInterface;
use VDM\Joomla\Utilities\MimeHelper;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Immutable uploaded-file definition contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Definition::class)]
#[CoversClass(DefinitionInterface::class)]
#[UsesClass(MimeHelper::class)]
final class DefinitionTest extends FilesystemTestCase
{
	/**
	 * Preserve every supplied upload field and expose one exact array shape.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExplicitUploadDetailsRemainExactAcrossEveryAccessor(): void
	{
		$file = $this->writeTemporaryFile('uploads/server-name.bin', 'original bytes');
		$details = [
			'name' => 'client-name.txt',
			'file_name' => 'server-name.bin',
			'full_path' => $file,
			'random' => 'a1b2c3',
			'extension' => 'custom',
			'size' => 912,
			'mime' => 'application/vnd.example'
		];

		$subject = new Definition($details);

		$this->assertInstanceOf(DefinitionInterface::class, $subject);
		$this->assertSame('client-name.txt', $subject->name());
		$this->assertSame('server-name.bin', $subject->fileName());
		$this->assertSame('a1b2c3', $subject->random());
		$this->assertSame('custom', $subject->extension());
		$this->assertSame(912, $subject->size());
		$this->assertSame('application/vnd.example', $subject->mime());
		$this->assertSame($file, $subject->filePath());
		$this->assertSame(
			[
				'name' => 'client-name.txt',
				'file_name' => 'server-name.bin',
				'file_path' => $file,
				'random' => 'a1b2c3',
				'extension' => 'custom',
				'size' => 912,
				'mime' => 'application/vnd.example'
			],
			$subject->toArray()
		);
	}

	/**
	 * Derive extension, byte count, MIME type, and empty random fragment from disk.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOmittedMetadataIsDerivedFromThePhysicalFile(): void
	{
		$contents = "alpha\nbeta\n";
		$file = $this->writeTemporaryFile('uploads/report.TXT', $contents);

		$subject = new Definition([
			'name' => 'report.TXT',
			'file_name' => 'stored-report.TXT',
			'full_path' => $file
		]);

		$this->assertSame('txt', $subject->extension());
		$this->assertSame(strlen($contents), $subject->size());
		$this->assertSame('text/plain', $subject->mime());
		$this->assertSame('', $subject->random());
	}

	/**
	 * Reject an absent or empty required upload field before reading the file.
	 *
	 * @param   string  $missingKey  Required key to remove.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('requiredFieldProvider')]
	public function testMissingRequiredFieldIsRejected(string $missingKey): void
	{
		$file = $this->writeTemporaryFile('uploads/file.txt', 'content');
		$details = [
			'name' => 'file.txt',
			'file_name' => 'stored-file.txt',
			'full_path' => $file
		];
		unset($details[$missingKey]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('File object missing required key: ' . $missingKey);

		new Definition($details);
	}

	/**
	 * Provide every mandatory upload-detail key.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.1.6
	 */
	public static function requiredFieldProvider(): iterable
	{
		yield 'client filename' => ['name'];
		yield 'server filename' => ['file_name'];
		yield 'physical path' => ['full_path'];
	}

	/**
	 * Reject metadata that points at a path without a physical file.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMissingPhysicalFileIsRejected(): void
	{
		$file = $this->temporaryPath('uploads/missing.txt');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('File does not exist on disk: ' . $file);

		new Definition([
			'name' => 'missing.txt',
			'file_name' => 'stored-missing.txt',
			'full_path' => $file
		]);
	}

	/**
	 * Snapshot metadata so later caller-array and file-content changes do not leak in.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructionSnapshotsMetadataForSubsequentReads(): void
	{
		$file = $this->writeTemporaryFile('uploads/snapshot.dat', 'first');
		$details = [
			'name' => 'snapshot.dat',
			'file_name' => 'stored-snapshot.dat',
			'full_path' => $file,
			'random' => 'original',
			'extension' => 'dat',
			'size' => 5,
			'mime' => 'application/original'
		];
		$subject = new Definition($details);

		$details['name'] = 'mutated.dat';
		$details['random'] = 'mutated';
		file_put_contents($file, 'changed after construction');

		$this->assertSame('snapshot.dat', $subject->name());
		$this->assertSame('original', $subject->random());
		$this->assertSame(5, $subject->size());
		$this->assertSame('application/original', $subject->mime());
	}
}
