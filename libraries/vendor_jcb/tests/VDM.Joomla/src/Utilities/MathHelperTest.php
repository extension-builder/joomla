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
use VDM\Joomla\Utilities\MathHelper;


/**
 * Decimal math wrapper contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(MathHelper::class)]
final class MathHelperTest extends TestCase
{
	/**
	 * Delegate supported operations with exact scale semantics.
	 *
	 * @param   string          $operation  Math operation suffix.
	 * @param   int|float|string $left       Left operand.
	 * @param   int|float|string $right      Right operand.
	 * @param   int             $scale      Decimal scale.
	 * @param   string|int      $expected   Exact wrapper result.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('operationProvider')]
	public function testBcExecutesSupportedOperationAtRequestedScale(
		string $operation,
		int|float|string $left,
		int|float|string $right,
		int $scale,
		string|int $expected
	): void
	{
		$this->assertSame($expected, MathHelper::bc($operation, $left, $right, $scale));
	}

	/**
	 * Provide exact decimal operation contracts.
	 *
	 * @return  iterable<string, array{string, int|float|string, int|float|string, int, string|int}>
	 * @since   6.1.6
	 */
	public static function operationProvider(): iterable
	{
		yield 'addition' => ['add', '1.25', '2.50', 2, '3.75'];
		yield 'subtraction' => ['sub', '1.25', '2.50', 2, '-1.25'];
		yield 'multiplication truncates at scale' => ['mul', '1.25', '2.50', 2, '3.12'];
		yield 'division pads requested scale' => ['div', 5, 2, 3, '2.500'];
		yield 'power' => ['pow', 2, 8, 0, '256'];
		yield 'compare greater' => ['comp', '2.001', '2.000', 3, 1];
		yield 'compare equal at scale' => ['comp', '2.001', '2.000', 2, 0];
		yield 'compare lower' => ['comp', '-1', '0', 0, -1];
	}

	/**
	 * Reject non-numeric operands and unsupported operation names.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBcRejectsInvalidOperandsAndUnknownOperation(): void
	{
		$this->assertNull(MathHelper::bc('add', 'not-a-number', 2, 2));
		$this->assertNull(MathHelper::bc('unknown', 1, 2, 0));
	}

	/**
	 * Sum decimal values without losing the requested precision.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSumPreservesConfiguredDecimalScale(): void
	{
		$this->assertSame('6.7500', MathHelper::sum(['1.25', '2.5', 3], 4));
		$this->assertSame(0.0, MathHelper::sum([], 3));
	}
}
