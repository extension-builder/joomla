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
use VDM\Joomla\File\TypeDefinition;
use VDM\Joomla\Interfaces\File\TypeDefinitionInterface;
use VDM\Tests\Support\TestCase;


/**
 * Immutable file-type blueprint contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(TypeDefinition::class)]
final class TypeDefinitionTest extends TestCase
{
	/**
	 * Sentinel used to distinguish an omitted key from a null value.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const OMITTED = '__omitted__';

	/**
	 * Preserve the configured upload contract and normalize one trailing path separator.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigurationIsExposedThroughExactImmutableAccessors(): void
	{
		$config = [
			'field' => 'archive',
			'type' => 'document',
			'filter' => 'safe',
			'path' => '/srv/uploads///',
			'formats' => ['zip', 'tar.gz']
		];

		$subject = new TypeDefinition($config);
		$config['field'] = 'changed';
		$config['formats'][] = 'exe';

		$this->assertInstanceOf(TypeDefinitionInterface::class, $subject);
		$this->assertSame('archive', $subject->field());
		$this->assertSame('document', $subject->type());
		$this->assertSame('safe', $subject->filter());
		$this->assertSame('/srv/uploads', $subject->path());
		$this->assertSame(['zip', 'tar.gz'], $subject->formats());
		$this->assertSame(
			[
				'field' => 'archive',
				'type' => 'document',
				'filter' => 'safe',
				'path' => '/srv/uploads',
				'formats' => ['zip', 'tar.gz']
			],
			$subject->toArray()
		);
	}

	/**
	 * Apply deterministic null and empty-array defaults to optional configuration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOptionalConfigurationHasStableDefaults(): void
	{
		$subject = new TypeDefinition([
			'field' => 'image',
			'type' => 'media',
			'path' => '/srv/images'
		]);

		$this->assertNull($subject->filter());
		$this->assertSame([], $subject->formats());
		$this->assertSame(
			[
				'field' => 'image',
				'type' => 'media',
				'filter' => null,
				'path' => '/srv/images',
				'formats' => []
			],
			$subject->toArray()
		);
	}

	/**
	 * Reject each absent, empty, or zero-like mandatory configuration value.
	 *
	 * @param   string  $key    Required configuration key.
	 * @param   mixed   $value  Invalid value to provide, or the sentinel for omission.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('invalidRequiredConfigurationProvider')]
	public function testInvalidRequiredConfigurationIsRejected(string $key, mixed $value): void
	{
		$config = [
			'field' => 'upload',
			'type' => 'file',
			'path' => '/srv/uploads'
		];

		if ($value === self::OMITTED)
		{
			unset($config[$key]);
		}
		else
		{
			$config[$key] = $value;
		}

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Missing file type config: ' . $key);

		new TypeDefinition($config);
	}

	/**
	 * Provide every invalid form of the mandatory configuration values.
	 *
	 * @return  iterable<string, array{string, mixed}>
	 * @since   6.1.6
	 */
	public static function invalidRequiredConfigurationProvider(): iterable
	{
		foreach (['field', 'type', 'path'] as $key)
		{
			yield $key . ' omitted' => [$key, self::OMITTED];
			yield $key . ' empty string' => [$key, ''];
			yield $key . ' null' => [$key, null];
			yield $key . ' numeric zero' => [$key, 0];
		}
	}
}
