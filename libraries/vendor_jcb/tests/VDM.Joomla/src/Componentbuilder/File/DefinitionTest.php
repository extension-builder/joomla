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


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use VDM\Joomla\Componentbuilder\File\Definition;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Persistent file-definition validation and metadata contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Definition::class)]
final class DefinitionTest extends FilesystemTestCase
{
	/**
	 * Reject every absent or empty required metadata field by name.
	 *
	 * @param   string  $key  Required key to remove.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideRequiredKeys')]
	public function testConstructorRejectsMissingRequiredMetadata(string $key): void
	{
		$details = $this->details('/virtual/file.txt');
		unset($details[$key]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Definition missing required key: ' . $key);
		new Definition($details);
	}

	/**
	 * Build a complete definition from explicit and filesystem-derived metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testConstructorBuildsCompleteFilesystemMetadata(): void
	{
		$file = $this->writeTemporaryFile('nested/document.txt', 'JCB content');
		$subject = new Definition($this->details($file));

		$this->assertSame('document.txt', $subject->fileName());
		$this->assertSame('txt', $subject->extension());
		$this->assertSame(11, $subject->size());
		$this->assertSame($file, $subject->filePath());
		$this->assertSame('', $subject->random());
		$this->assertSame($this->details($file)['guid'], $subject->guid());
		$this->assertSame($subject->toArray(), $subject->toArray());
	}

	/**
	 * Supply every required persistent file field.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.1.6
	 */
	public static function provideRequiredKeys(): iterable
	{
		foreach (['name', 'file_type', 'file_path', 'entity_type', 'entity', 'access', 'guid', 'created_by'] as $key)
		{
			yield $key => [$key];
		}
	}

	/**
	 * Build complete persistent file details.
	 *
	 * @param   string  $path  Absolute file path.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.6
	 */
	private function details(string $path): array
	{
		return [
			'name' => 'Original document.txt',
			'file_type' => 'document-guid',
			'file_path' => $path,
			'entity_type' => 'admin_view',
			'entity' => 'view-guid',
			'access' => 1,
			'guid' => 'file-guid',
			'created_by' => 42,
		];
	}
}
