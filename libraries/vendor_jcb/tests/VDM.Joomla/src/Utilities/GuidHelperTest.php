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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Utilities\GuidHelper;


/**
 * GUID generation and syntax-validation contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(GuidHelper::class)]
final class GuidHelperTest extends TestCase
{
	/**
	 * Generate lowercase RFC 4122 version-four identifiers with a valid variant.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetReturnsTrimmedVersionFourGuid(): void
	{
		$guid = GuidHelper::get();

		$this->assertMatchesRegularExpression(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
			$guid
		);
		$this->assertTrue(GuidHelper::valid($guid));
	}

	/**
	 * Accept canonical identifiers with either balanced braces or no braces.
	 *
	 * @param   string  $guid  Candidate identifier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('validGuidProvider')]
	public function testValidAcceptsCanonicalGuidSyntax(string $guid): void
	{
		$this->assertTrue(GuidHelper::valid($guid));
	}

	/**
	 * Provide canonical plain, uppercase, and braced identifiers.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.1.6
	 */
	public static function validGuidProvider(): iterable
	{
		yield 'plain lowercase' => ['123e4567-e89b-12d3-a456-426614174000'];
		yield 'plain uppercase' => ['123E4567-E89B-12D3-A456-426614174000'];
		yield 'balanced braces' => ['{123e4567-e89b-12d3-a456-426614174000}'];
	}

	/**
	 * Reject malformed, unbalanced, empty, and non-string identifier values.
	 *
	 * @param   mixed  $guid  Candidate identifier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('invalidGuidProvider')]
	public function testValidRejectsMalformedGuidSyntax(mixed $guid): void
	{
		$this->assertFalse(GuidHelper::valid($guid));
	}

	/**
	 * Provide invalid identifier boundary cases.
	 *
	 * @return  iterable<string, array{mixed}>
	 * @since   6.1.6
	 */
	public static function invalidGuidProvider(): iterable
	{
		yield 'empty' => [''];
		yield 'whitespace' => ['   '];
		yield 'too short' => ['123e4567-e89b-12d3-a456'];
		yield 'invalid hexadecimal' => ['zzze4567-e89b-12d3-a456-426614174000'];
		yield 'opening brace only' => ['{123e4567-e89b-12d3-a456-426614174000'];
		yield 'closing brace only' => ['123e4567-e89b-12d3-a456-426614174000}'];
		yield 'integer' => [123];
		yield 'null' => [null];
	}

	/**
	 * Require the untrimmed generation mode to balance its surrounding braces.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testGetWithBracesReturnsBalancedGuid(): void
	{
		$this->assertMatchesRegularExpression(
			'/^\{[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\}$/',
			GuidHelper::get(false)
		);
	}
}
