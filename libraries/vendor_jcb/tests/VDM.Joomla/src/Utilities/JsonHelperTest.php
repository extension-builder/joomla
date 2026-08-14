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
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Utilities\JsonHelper;


/**
 * JSON recognition and display conversion contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(JsonHelper::class)]
final class JsonHelperTest extends TestCase
{
	/**
	 * Recognize every valid non-empty JSON value accepted by PHP's decoder.
	 *
	 * @param   string  $json  Valid JSON text.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('validJsonProvider')]
	public function testCheckAcceptsValidNonEmptyJson(string $json): void
	{
		$this->assertTrue(JsonHelper::check($json));
	}

	/**
	 * Provide valid object, array, scalar, boolean, and null JSON cases.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.1.6
	 */
	public static function validJsonProvider(): iterable
	{
		yield 'object' => ['{"name":"JCB"}'];
		yield 'array' => ['[1,2,3]'];
		yield 'string scalar' => ['"JCB"'];
		yield 'number scalar' => ['0'];
		yield 'boolean scalar' => ['false'];
		yield 'null scalar' => ['null'];
	}

	/**
	 * Reject malformed JSON and values outside the non-empty string boundary.
	 *
	 * @param   mixed  $value  Candidate value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('invalidJsonProvider')]
	public function testCheckRejectsInvalidOrNonStringInput(mixed $value): void
	{
		$this->assertFalse(JsonHelper::check($value));
	}

	/**
	 * Provide malformed and out-of-contract JSON candidates.
	 *
	 * @return  iterable<string, array{mixed}>
	 * @since   6.1.6
	 */
	public static function invalidJsonProvider(): iterable
	{
		yield 'empty' => [''];
		yield 'whitespace' => [" \t\n"];
		yield 'truncated object' => ['{"name":'];
		yield 'single quoted value' => ["'JCB'"];
		yield 'null input' => [null];
		yield 'integer input' => [123];
		yield 'array input' => [['valid' => 'shape']];
	}

	/**
	 * Join a decoded JSON list with the caller's exact separator.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStringJoinsDecodedListWithRequestedSeparator(): void
	{
		$this->assertSame(
			'alpha | beta | 3',
			JsonHelper::string('["alpha","beta",3]', ' | ')
		);
	}

	/**
	 * Convert decoded scalar JSON and preserve invalid text unchanged.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStringConvertsScalarsAndPreservesInvalidText(): void
	{
		$this->assertSame('JCB', JsonHelper::string('"JCB"'));
		$this->assertSame('42', JsonHelper::string('42'));
		$this->assertSame('not-json', JsonHelper::string('not-json'));
	}
}
