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
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Minify\Abstraction\Minify;
use VDM\Minify\Css;
use VDM\Minify\Exceptions\FileImportException;
use VDM\Minify\Path\Converter;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * CSS minifier behavior test.
 *
 * @since  6.1.6
 */
#[CoversClass(Css::class)]
#[UsesClass(Minify::class)]
#[UsesClass(FileImportException::class)]
#[UsesClass(Converter::class)]
final class CssTest extends FilesystemTestCase
{
	/**
	 * Optimize safe CSS tokens while preserving strings and math whitespace.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMinifyOptimizesCssWithoutChangingProtectedContent(): void
	{
		$source = "/* banner */\n"
			. '.a { color: #AABBCC; margin: 0px 0.50em; '
			. 'font-weight: normal; content: "a  b/*x*/"; '
			. 'width: calc(100% - 2px); }';

		$this->assertSame(
			'.a{color:#ABC;margin:0 .5em;font-weight:400;'
				. 'content:"a  b/*x*/";width:calc(100% - 2px)}',
			(new Css($source))->minify()
		);
	}

	/**
	 * Preserve custom-property values that may be consumed in whitespace-sensitive contexts.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomPropertiesAndNestedMathRemainSemanticallyIntact(): void
	{
		$source = ':root { --gap: calc(10px + 2px); --raw: 0px  5px; } '
			. '.a { width: calc(100% - var(--gap)); margin: 0px 0.50em; }';

		$this->assertSame(
			':root{--gap:calc(10px + 2px);--raw:0px  5px}'
				. '.a{width:calc(100% - var(--gap));margin:0 .5em}',
			(new Css($source))->minify()
		);
	}

	/**
	 * Preserve license comments and discard ordinary comments.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLicenseCommentsSurviveAndOrdinaryCommentsAreRemoved(): void
	{
		$license = (new Css("/*! License A */\n/** ordinary */\n.a { color: white; }"))->minify();
		$preserve = (new Css("/* @preserve Keep B */\n/** ordinary */\n.b { color: black; }"))->minify();

		$this->assertStringContainsString('/*! License A */', $license);
		$this->assertStringNotContainsString('ordinary', $license);
		$this->assertStringContainsString('.a{color:#fff}', $license);
		$this->assertStringContainsString('/* @preserve Keep B */', $preserve);
		$this->assertStringNotContainsString('ordinary', $preserve);
		$this->assertStringContainsString('.b{color:#000}', $preserve);
	}

	/**
	 * Strip empty rules and apply compact color, weight, and flex zero forms.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmptyRulesAndCanonicalValueShortcutsAreApplied(): void
	{
		$source = '.empty { } .full { color: #FF0000; '
			. 'font-weight: bold; flex-basis: 0px; }';

		$this->assertSame(
			'.full{color:red;font-weight:700;flex-basis:0%}',
			(new Css($source))->minify()
		);
	}

	/**
	 * Keep remote, data, root-relative, and fragment URL boundaries unchanged.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNonRelativeUrlsAreNeverRebasedOrImported(): void
	{
		$source = '.a { '
			. 'a:url(https://example.test/a.png); '
			. 'b:url(data:image/svg+xml;base64,AA==); '
			. 'c:url(/root/a.png); d:url("#icon"); }';

		$this->assertSame(
			'.a{a:url(https://example.test/a.png);'
				. 'b:url(data:image/svg+xml;base64,AA==);'
				. 'c:url(/root/a.png);d:url("#icon")}',
			(new Css($source))->minify()
		);
	}

	/**
	 * Inline a configured small local asset with its declared media type.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfiguredSmallAssetIsEmbeddedAsDataUri(): void
	{
		$this->writeTemporaryFile('images/icon.png', 'PNG');
		$source = $this->writeTemporaryFile(
			'css/main.css',
			'.icon { background: url("../images/icon.png"); }'
		);
		$subject = new Css($source);
		$subject->setMaxImportSize(1);
		$subject->setImportExtensions(['png' => 'application/vnd.test-image']);

		$this->assertSame(
			'.icon{background:url(application/vnd.test-image;base64,UE5H)}',
			$subject->minify()
		);
	}

	/**
	 * Respect the maximum import size before converting an asset to a data URI.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAssetAboveConfiguredLimitRemainsRelativeUrl(): void
	{
		$this->writeTemporaryFile('images/icon.png', 'PNG');
		$source = $this->writeTemporaryFile(
			'css/main.css',
			'.icon { background: url("../images/icon.png"); }'
		);
		$subject = new Css($source);
		$subject->setMaxImportSize(0);

		$this->assertSame(
			'.icon{background:url(../images/icon.png)}',
			$subject->minify()
		);
	}

	/**
	 * Recursively combine local imports, preserve media scope, and rebase query URLs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLocalImportIsCombinedAndRebasedToOutputFile(): void
	{
		$this->writeTemporaryFile(
			'css/nested/import.css',
			'.imported { background: url("../../images/bg.svg"); color: #AABBCC; }'
		);
		$source = $this->writeTemporaryFile(
			'css/main.css',
			'@import "nested/import.css" screen; '
				. '.main { background: url("../images/icon.png?version=1"); }'
		);
		$this->createTemporaryDirectory('build');
		$output = $this->temporaryPath('build/app.css');
		$subject = new Css($source);
		$subject->setImportExtensions([]);

		$result = $subject->minify($output);

		$this->assertSame(
			'@media screen{.imported{background:url(../images/bg.svg);color:#ABC}}'
				. '.main{background:url(../images/icon.png?version=1)}',
			$result
		);
		$this->assertSame($result, file_get_contents($output));
	}

	/**
	 * Move unresolved imports ahead of ordinary rules without changing their order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUnresolvedImportsMoveToTopAcrossSources(): void
	{
		$subject = new Css(
			'.a{color:red}@import url(https://example.test/a.css);.b{color:blue}',
			'@import "https://example.test/print.css";.c{color:black}'
		);

		$this->assertSame(
			'@import url(https://example.test/a.css);'
				. '@import "https://example.test/print.css";'
				. '.a{color:red}.b{color:blue}.c{color:#000}',
			$subject->minify()
		);
	}

	/**
	 * Stop a recursive import chain with a path-specific domain failure.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCircularImportThrowsFileImportException(): void
	{
		$source = $this->writeTemporaryFile('css/self.css', '@import "self.css";');

		$this->expectException(FileImportException::class);
		$this->expectExceptionMessage(
			'Failed to import file "' . $source . '": circular reference detected.'
		);

		(new Css($source))->minify();
	}

	/**
	 * Clear extracted content state after each execution on a reusable instance.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepeatedMinificationRestoresAndClearsExtractedTokens(): void
	{
		$subject = new Css('.a { content: "a  b"; /* removed */ }');

		$first = $subject->minify();
		$second = $subject->minify();

		$this->assertSame('.a{content:"a  b"}', $first);
		$this->assertSame($first, $second);
		$this->assertSame([], $subject->extracted);
	}
}
