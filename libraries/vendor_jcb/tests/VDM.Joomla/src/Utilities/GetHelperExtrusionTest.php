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
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\GetHelper;
use VDM\Joomla\Utilities\GetHelperExtrusion;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * Delimited-content extrusion contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(GetHelperExtrusion::class)]
#[UsesClass(GetHelper::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(StringHelper::class)]
final class GetHelperExtrusionTest extends TestCase
{
	/**
	 * Return the first delimited segment or the caller's fallback.
	 *
	 * @param   string  $content   Content to inspect.
	 * @param   string  $start     Opening delimiter.
	 * @param   string  $end       Closing delimiter.
	 * @param   string  $default   Missing-segment fallback.
	 * @param   string  $expected  Expected segment.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('betweenProvider')]
	public function testBetweenReturnsFirstCompleteDelimitedSegment(
		string $content,
		string $start,
		string $end,
		string $default,
		string $expected
	): void
	{
		$this->assertSame(
			$expected,
			GetHelperExtrusion::between($content, $start, $end, $default)
		);
	}

	/**
	 * Provide complete, repeated, empty, and missing delimiter cases.
	 *
	 * @return  iterable<string, array{string, string, string, string, string}>
	 * @since   6.1.6
	 */
	public static function betweenProvider(): iterable
	{
		yield 'simple' => ['before {{value}} after', '{{', '}}', '', 'value'];
		yield 'first match' => ['[first] and [second]', '[', ']', '', 'first'];
		yield 'empty match' => ['prefix <> suffix', '<', '>', 'fallback', ''];
		yield 'missing opening' => ['value> only', '<', '>', 'fallback', 'fallback'];
		yield 'missing closing' => ['<value only', '<', '>', 'fallback', 'fallback'];
		yield 'multicharacter delimiters' => ['x<!--body-->y', '<!--', '-->', '', 'body'];
	}

	/**
	 * Extract each distinct wrapped value in discovery order.
	 *
	 * @param   string      $content   Content to inspect.
	 * @param   array|null  $expected  Expected extracted values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('allBetweenProvider')]
	public function testAllBetweenReturnsDistinctSegmentsInDiscoveryOrder(
		string $content,
		?array $expected
	): void
	{
		$this->assertSame($expected, GetHelperExtrusion::allBetween($content, '[[', ']]'));
	}

	/**
	 * Provide repeated, absent, incomplete, and empty captured segments.
	 *
	 * @return  iterable<string, array{string, array|null}>
	 * @since   6.1.6
	 */
	public static function allBetweenProvider(): iterable
	{
		yield 'several values' => ['[[alpha]] text [[beta]] text [[gamma]]', ['alpha', 'beta', 'gamma']];
		yield 'duplicates collapse' => ['[[alpha]][[beta]][[alpha]]', ['alpha', 'beta']];
		yield 'absent' => ['plain content', null];
		yield 'incomplete closing delimiter' => ['[[alpha', null];
		yield 'empty first segment stops scan' => ['[[]][[later]]', null];
	}
}
