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
use VDM\Joomla\Componentbuilder\File\TypeDefinition;
use VDM\Tests\Support\TestCase;


/**
 * File-type configuration validation, normalization, and export tests.
 *
 * @since  6.1.6
 */
#[CoversClass(TypeDefinition::class)]
final class TypeDefinitionTest extends TestCase
{
	/**
	 * Normalize scalar values and preserve complete optional configuration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorNormalizesAndExportsCompleteConfiguration(): void
	{
		$config = [
			'guid' => 'type-guid',
			'name' => 'Images',
			'access' => '2',
			'quantity' => '0',
			'download_access' => 3,
			'field' => 'jform_image',
			'type' => 'image',
			'filter' => 'safehtml',
			'path' => '/images/components',
			'formats' => ['png', 'webp'],
			'crop' => [['width' => 640, 'height' => 480]],
		];
		$subject = new TypeDefinition($config);

		$this->assertSame('type-guid', $subject->guid());
		$this->assertSame('Images', $subject->name());
		$this->assertSame(2, $subject->access());
		$this->assertSame(0, $subject->quantity());
		$this->assertSame(3, $subject->downloadAccess());
		$this->assertSame('jform_image', $subject->field());
		$this->assertSame('image', $subject->type());
		$this->assertSame('safehtml', $subject->filter());
		$this->assertSame('/images/components', $subject->path());
		$this->assertSame(['png', 'webp'], $subject->formats());
		$this->assertSame([['width' => 640, 'height' => 480]], $subject->crop());
		$this->assertSame(
			[
				'guid' => 'type-guid',
				'name' => 'Images',
				'access' => 2,
				'quantity' => 0,
				'download_access' => 3,
				'field' => 'jform_image',
				'type' => 'image',
				'formats' => ['png', 'webp'],
				'filter' => 'safehtml',
				'path' => '/images/components',
				'crop' => [['width' => 640, 'height' => 480]],
			],
			$subject->toArray()
		);
	}

	/**
	 * Default optional filter, formats, and crop configuration predictably.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorDefaultsOptionalConfiguration(): void
	{
		$subject = new TypeDefinition($this->required());

		$this->assertNull($subject->filter());
		$this->assertSame([], $subject->formats());
		$this->assertSame([], $subject->crop());
		$this->assertSame(
			[
				'guid' => 'type-guid',
				'name' => 'Documents',
				'access' => 0,
				'quantity' => 0,
				'download_access' => 0,
				'field' => 'jform_file',
				'type' => 'document',
				'formats' => [],
				'filter' => null,
				'path' => '/documents',
				'crop' => [],
			],
			$subject->toArray()
		);
	}

	/**
	 * Reject every absent required configuration field by name.
	 *
	 * @param   string  $key  Required key to remove.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideRequiredKeys')]
	public function testConstructorRejectsMissingRequiredConfiguration(string $key): void
	{
		$config = $this->required();
		unset($config[$key]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Missing type definition config: ' . $key);
		new TypeDefinition($config);
	}

	/**
	 * Reject whitespace-only identity and path fields.
	 *
	 * @param   string  $key  Non-empty key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideNonEmptyKeys')]
	public function testConstructorRejectsEmptyIdentityConfiguration(string $key): void
	{
		$config = $this->required();
		$config[$key] = '  ';

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Empty value not allowed for config: ' . $key);
		new TypeDefinition($config);
	}

	/**
	 * Supply every required configuration key.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.1.6
	 */
	public static function provideRequiredKeys(): iterable
	{
		foreach (['guid', 'name', 'access', 'quantity', 'download_access', 'field', 'type', 'path'] as $key)
		{
			yield $key => [$key];
		}
	}

	/**
	 * Supply required values that cannot be empty.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.1.6
	 */
	public static function provideNonEmptyKeys(): iterable
	{
		foreach (['guid', 'name', 'field', 'type', 'path'] as $key)
		{
			yield $key => [$key];
		}
	}

	/**
	 * Build the minimum valid configuration.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.6
	 */
	private function required(): array
	{
		return [
			'guid' => 'type-guid',
			'name' => 'Documents',
			'access' => 0,
			'quantity' => 0,
			'download_access' => 0,
			'field' => 'jform_file',
			'type' => 'document',
			'path' => '/documents',
		];
	}
}
