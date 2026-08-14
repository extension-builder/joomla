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
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * Legacy query-helper delimited-content contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(GetHelper::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(StringHelper::class)]
final class GetHelperTest extends TestCase
{
	/**
	 * Return the first complete delimited value or the supplied default.
	 *
	 * @param   string  $content   Content to inspect.
	 * @param   string  $start     Opening delimiter.
	 * @param   string  $end       Closing delimiter.
	 * @param   string  $default   Missing-value default.
	 * @param   string  $expected  Expected result.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideBetweenCases')]
	public function testBetweenReturnsFirstCompleteValue(
		string $content,
		string $start,
		string $end,
		string $default,
		string $expected
	): void
	{
		$this->assertSame($expected, GetHelper::between($content, $start, $end, $default));
	}

	/**
	 * Supply complete, empty, repeated, and missing delimiter cases.
	 *
	 * @return  iterable<string, array{string, string, string, string, string}>
	 * @since   6.1.6
	 */
	public static function provideBetweenCases(): iterable
	{
		yield 'complete' => ['before {{value}} after', '{{', '}}', '', 'value'];
		yield 'first wins' => ['[first] [second]', '[', ']', '', 'first'];
		yield 'empty' => ['prefix <> suffix', '<', '>', 'fallback', ''];
		yield 'missing opener' => ['value>', '<', '>', 'fallback', 'fallback'];
		yield 'missing closer' => ['<value', '<', '>', 'fallback', 'fallback'];
	}

	/**
	 * Extract unique non-empty values in discovery order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAllBetweenReturnsUniqueValuesInDiscoveryOrder(): void
	{
		$this->assertSame(
			['alpha', 'beta'],
			GetHelper::allBetween('[[alpha]][[beta]][[alpha]]', '[[', ']]')
		);
		$this->assertNull(GetHelper::allBetween('plain content', '[[', ']]'));
		$this->assertNull(GetHelper::allBetween('[[]][[later]]', '[[', ']]'));
	}

	/**
	 * Enforce the documented safety bound on adversarial repeated input.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAllBetweenStopsAtSafetyLimit(): void
	{
		$values = [];

		for ($index = 0; $index < 510; $index++)
		{
			$values[] = '[[' . $index . ']]';
		}

		$result = GetHelper::allBetween(implode('', $values), '[[', ']]');

		$this->assertCount(501, $result);
		$this->assertSame('0', $result[0]);
		$this->assertSame('500', $result[500]);
	}
}
