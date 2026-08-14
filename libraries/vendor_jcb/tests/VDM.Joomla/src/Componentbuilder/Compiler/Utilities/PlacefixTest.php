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
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Tests\Support\TestCase;


/**
 * Compiler placeholder delimiter contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Placefix::class)]
final class PlacefixTest extends TestCase
{
	/**
	 * Keep bracket and hash placeholder grammars exact for generated replacements.
	 *
	 * @param   string  $input            Placeholder body.
	 * @param   string  $expectedBracket  Expected bracket form.
	 * @param   string  $expectedHash     Expected hash form.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('placeholderProvider')]
	public function testPlaceholderWrappingPreservesExactContent(
		string $input,
		string $expectedBracket,
		string $expectedHash
	): void
	{
		$this->assertSame($expectedBracket, Placefix::_($input));
		$this->assertSame($expectedHash, Placefix::_h($input));
	}

	/**
	 * Provide normal, empty, spaced, and already-delimited values.
	 *
	 * @return  iterable<string, array{string, string, string}>
	 * @since   6.1.6
	 */
	public static function placeholderProvider(): iterable
	{
		yield 'name' => ['COMPONENT', '[[[COMPONENT]]]', '###COMPONENT###'];
		yield 'empty' => ['', '[[[]]]', '######'];
		yield 'spaces preserved' => [' view name ', '[[[ view name ]]]', '### view name ###'];
		yield 'nested text preserved' => ['[[[INNER]]]', '[[[[[[INNER]]]]]]', '###[[[INNER]]]###'];
	}

	/**
	 * Expose the exact individual delimiters used by legacy compiler output.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDelimiterAccessorsRemainExact(): void
	{
		$this->assertSame('[[[', Placefix::b());
		$this->assertSame(']]]', Placefix::d());
		$this->assertSame('###', Placefix::h());
	}
}
