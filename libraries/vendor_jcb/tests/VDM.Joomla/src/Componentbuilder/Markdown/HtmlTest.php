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

namespace VDM\Joomla\Tests\Componentbuilder\Markdown;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Markdown\Html;
use VDM\Tests\Support\TestCase;


/**
 * Sanitization and exact simplified-Markdown conversion contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Html::class)]
final class HtmlTest extends TestCase
{
	/**
	 * Protect escaping, headings, emphasis, links, and line-break placement.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConvertProducesExactInlineAndHeadingMarkup(): void
	{
		$subject = new Html();
		$markdown = "# Title\n\nA **bold** and *italic* [link](https://example.test).";

		$this->assertSame(
			'<p><h1>Title</h1>' . PHP_EOL
				. 'A <strong>bold</strong> and <em>italic</em> <a href="https://example.test">link</a>.</p>',
			$subject->convert($markdown)
		);
	}

	/**
	 * Protect code escaping and the distinction between fenced and inline code.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConvertEscapesCodeAndPreservesBlockBoundaries(): void
	{
		$subject = new Html();
		$markdown = "```\n<script>alert('x')</script>\n```\n\n`<tag>`";

		$this->assertSame(
			'<pre><code>&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;</code></pre>'
				. '<p><code>&lt;tag&gt;</code></p>',
			$subject->convert($markdown)
		);
	}

	/**
	 * Protect exact task and ordered-list aggregation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConvertAggregatesContiguousTaskAndOrderedLists(): void
	{
		$subject = new Html();
		$markdown = "- [x] done\n- [ ] todo\n\n1. first\n2. second";

		$this->assertSame(
			'<p><ul class="task-list"><li>☑ done</li><li>☐ todo</li></ul></p>'
				. '<ol><li>first</li><li>second</li></ol>',
			$subject->convert($markdown)
		);
	}

	/**
	 * Protect the explicit failure contract for whitespace-only input.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConvertRejectsEmptyMarkdown(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Markdown input is empty.');

		(new Html())->convert(" \n\t ");
	}

	/**
	 * Record the documented image conversion contract defeated by link ordering.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testConvertRendersMarkdownImageAsImageElement(): void
	{
		$this->assertSame(
			'<p><img src="https://example.test/logo.png" alt="Logo"></p>',
			(new Html())->convert('![Logo](https://example.test/logo.png)')
		);
	}

	/**
	 * Record the documented blockquote contract defeated by early escaping.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testConvertRendersContiguousBlockquoteLines(): void
	{
		$this->assertSame(
			'<blockquote>first<br>second</blockquote>',
			(new Html())->convert("> first\n> second")
		);
	}

	/**
	 * Refuse a URL scheme that can execute script.
	 *
	 * convert() escapes the document before the link rules run, so a URL
	 * cannot break out of the attribute, but it could still carry a
	 * scripting scheme straight into href.
	 *
	 * @param   string  $markdown  Markdown carrying the candidate URL.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('refusedUrlSchemes')]
	public function testConvertEmptiesAnExecutableUrlScheme(string $markdown): void
	{
		$this->assertStringContainsString('href=""', (new Html())->convert($markdown));
	}

	/**
	 * URL schemes that must never reach an href.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function refusedUrlSchemes(): array
	{
		return [
			'javascript' => ['[x](javascript:alert%281%29)'],
			'javascript uppercase' => ['[x](JaVaScRiPt:alert%281%29)'],
			'javascript split by a control character' => ["[x](java\tscript:alert%281%29)"],
			'vbscript' => ['[x](vbscript:msgbox)'],
			'data' => ['[x](data:text/html;base64,PHNjcmlwdD48L3NjcmlwdD4=)'],
		];
	}

	/**
	 * Keep the URLs a document legitimately links to.
	 *
	 * @param   string  $markdown  Markdown carrying the candidate URL.
	 * @param   string  $expected  The href the document must keep.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('keptUrls')]
	public function testConvertKeepsANonExecutableUrl(string $markdown, string $expected): void
	{
		$this->assertStringContainsString('href="' . $expected . '"', (new Html())->convert($markdown));
	}

	/**
	 * URLs that must survive untouched.
	 *
	 * @return  array<string, array{string,string}>
	 * @since   6.1.7
	 */
	public static function keptUrls(): array
	{
		return [
			'https' => ['[x](https://example.test/a?b=1&c=2)', 'https://example.test/a?b=1&amp;c=2'],
			'http' => ['[x](http://example.test)', 'http://example.test'],
			'mailto' => ['[x](mailto:someone@example.test)', 'mailto:someone@example.test'],
			'root relative' => ['[x](/index.php?option=com_test)', '/index.php?option=com_test'],
			'anchor' => ['[x](#section)', '#section'],
		];
	}
}
