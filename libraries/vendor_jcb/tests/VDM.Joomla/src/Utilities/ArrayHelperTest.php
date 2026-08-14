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
use PHPUnit\Framework\TestCase;
use stdClass;
use VDM\Joomla\Utilities\ArrayHelper;


/**
 * Array validation, merge, intersection, and deep-copy contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(ArrayHelper::class)]
final class ArrayHelperTest extends TestCase
{
	/**
	 * Return element count for arrays and false outside the non-empty boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCheckReturnsCountOnlyForNonEmptyArray(): void
	{
		$this->assertSame(3, ArrayHelper::check(['alpha', null, '']));
		$this->assertFalse(ArrayHelper::check([]));
		$this->assertFalse(ArrayHelper::check('not-an-array'));
		$this->assertFalse(ArrayHelper::check(null));
	}

	/**
	 * Apply PHP's falsey-value filtering when empty removal is requested.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCheckWithRemovalCountsOnlyTruthyValues(): void
	{
		$this->assertSame(
			2,
			ArrayHelper::check(['alpha', '', 0, '0', false, null, 'beta'], true)
		);
		$this->assertFalse(ArrayHelper::check(['', 0, '0', false, null], true));
	}

	/**
	 * Merge valid child arrays in order with PHP array-merge key semantics.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMergeCombinesOnlyNonEmptyChildArrays(): void
	{
		$this->assertSame(
			[0 => 'first', 'name' => 'replacement', 1 => 'second'],
			ArrayHelper::merge([
				[0 => 'first', 'name' => 'original'],
				[],
				'ignored',
				[0 => 'second', 'name' => 'replacement']
			])
		);
		$this->assertNull(ArrayHelper::merge([]));
		$this->assertNull(ArrayHelper::merge(null));
	}

	/**
	 * Detect at least one shared scalar value and reject disjoint sets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testIntersectReportsSharedValuePresence(): void
	{
		$this->assertTrue(ArrayHelper::intersect(['alpha', 'beta'], ['gamma', 'beta']));
		$this->assertTrue(ArrayHelper::intersect(['1'], [1]));
		$this->assertFalse(ArrayHelper::intersect(['alpha'], ['ALPHA']));
		$this->assertFalse(ArrayHelper::intersect([], ['alpha']));
	}

	/**
	 * Clone every nested object while preserving keys and immutable scalar values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCloneCreatesIndependentObjectsAtEveryDepth(): void
	{
		$outer = new stdClass();
		$outer->name = 'outer';
		$inner = new stdClass();
		$inner->name = 'inner';
		$source = [
			'outer' => $outer,
			'nested' => ['inner' => $inner, 'count' => 3]
		];

		$copy = ArrayHelper::clone($source);
		$copy['outer']->name = 'changed outer';
		$copy['nested']['inner']->name = 'changed inner';

		$this->assertNotSame($source['outer'], $copy['outer']);
		$this->assertNotSame($source['nested']['inner'], $copy['nested']['inner']);
		$this->assertSame('outer', $source['outer']->name);
		$this->assertSame('inner', $source['nested']['inner']->name);
		$this->assertSame(3, $copy['nested']['count']);
	}
}
