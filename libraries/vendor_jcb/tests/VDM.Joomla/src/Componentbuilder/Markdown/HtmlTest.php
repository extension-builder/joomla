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
}
