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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Tests\Support\CompilerUtilityTestCase;


/**
 * Generated-code indentation contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Indent::class)]
final class IndentTest extends CompilerUtilityTestCase
{
	/**
	 * Repeat the configured tab for every requested indentation depth.
	 *
	 * @param   int     $depth     Indentation depth.
	 * @param   string  $expected  Exact indentation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('indentationProvider')]
	public function testIndentReturnsExactConfiguredDepth(int $depth, string $expected): void
	{
		$this->assertSame($expected, Indent::_($depth));
	}

	/**
	 * Provide zero and representative nested depths.
	 *
	 * @return  iterable<string, array{int, string}>
	 * @since   6.1.6
	 */
	public static function indentationProvider(): iterable
	{
		yield 'zero' => [0, ''];
		yield 'one' => [1, "\t"];
		yield 'three' => [3, "\t\t\t"];
		yield 'eight' => [8, str_repeat("\t", 8)];
	}

	/**
	 * Cache a computed depth so later global indentation mutation cannot rewrite it.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testComputedDepthIsCachedForTheBuildScope(): void
	{
		$this->assertSame("\t\t", Indent::_(2));

		(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, '  ');

		$this->assertSame("\t\t", Indent::_(2));
		$this->assertSame('      ', Indent::_(3));
	}
}
