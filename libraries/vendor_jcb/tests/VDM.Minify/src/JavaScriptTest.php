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

namespace VDM\Minify\Tests;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Minify\Abstraction\Minify;
use VDM\Minify\JavaScript;


/**
 * JavaScript minifier behavior test.
 *
 * @since  6.1.6
 */
#[CoversClass(JavaScript::class)]
#[UsesClass(Minify::class)]
final class JavaScriptTest extends TestCase
{
	/**
	 * Minify syntax while preserving language-sensitive tokens.
	 *
	 * @param   string  $source    The unminified JavaScript.
	 * @param   string  $expected  The exact expected output.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('javascriptProvider')]
	public function testMinifyPreservesJavascriptSemantics(string $source, string $expected): void
	{
		$this->assertSame($expected, (new JavaScript($source))->minify());
	}

	/**
	 * Provide high-risk JavaScript lexical and whitespace cases.
	 *
	 * @return  iterable<string, array{string, string}>
	 * @since   6.1.6
	 */
	public static function javascriptProvider(): iterable
	{
		yield 'comments do not consume string or regular expression content' => [
			"// banner\n"
				. 'var text = "a  // b"; '
				. 'var pattern = /a\\/\\/b/g; '
				. 'if (true) { object["safeKey"] = false; }',
			'var text="a  // b";var pattern=/a\\/\\/b/g;if(!0){object.safeKey=!1}'
		];

		yield 'license and preserve comments survive while ordinary comments disappear' => [
			"/*! License A */\n/** ordinary */\n/* @preserve Keep B */\nvar x = true;",
			"/*! License A */\n/* @preserve Keep B */\nvar x=!0"
		];

		yield 'safe property notation is shortened without changing unsafe or reserved keys' => [
			'object["safeKey"] = true; '
				. 'object["not-safe"] = false; '
				. 'object["return"] = true;',
			'object.safeKey=!0;object["not-safe"]=!1;object["return"]=!0'
		];

		yield 'automatic semicolon insertion newline remains after return' => [
			"function value() {\n return\n { ok: true };\n}\nnext()",
			"function value(){return\n{ok:!0}}\nnext()"
		];

		yield 'division remains division and regular expression character classes remain intact' => [
			'var half = total / 2 / count; '
				. 'var re = /a\\/\\/b[ /]/gi; // gone' . "\n"
				. 'var url = "http://x.test/a // b";',
			'var half=total/2/count;var re=/a\\/\\/b[ /]/gi;'
				. 'var url="http://x.test/a // b"'
		];

		yield 'template and quoted string whitespace remains exact' => [
			'const text = `a   b // untouched`; const quoted = \'x  y\';',
			'const text=`a   b // untouched`;const quoted=\'x  y\''
		];

		yield 'boolean values shorten without rewriting property names or object keys' => [
			'return true; value.false = false; object = { true: true };',
			'return!0;value.false=!1;object={true:!0}'
		];
	}

	/**
	 * Separate independently added scripts so their boundary cannot merge tokens.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMultipleSourcesAreSeparatedBySingleSemicolon(): void
	{
		$subject = new JavaScript('var first = 1', 'var second = 2;');

		$this->assertSame('var first=1;var second=2', $subject->minify());
	}

	/**
	 * Clear extracted token state so a reusable instance remains deterministic.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepeatedMinificationRestoresAndClearsExtractedTokens(): void
	{
		$subject = new JavaScript('var text = "a  b"; // removed');

		$first = $subject->minify();
		$second = $subject->minify();

		$this->assertSame('var text="a  b"', $first);
		$this->assertSame($first, $second);
		$this->assertSame([], $subject->extracted);
	}
}
