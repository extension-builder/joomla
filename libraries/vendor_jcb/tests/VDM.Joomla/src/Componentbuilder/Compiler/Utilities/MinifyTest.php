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
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Minify;
use VDM\Minify\Css;
use VDM\Minify\JavaScript;
use VDM\Tests\Support\TestCase;


/**
 * Build-scoped JavaScript and CSS minification facade contract test.
 *
 * Each test runs in a separate process because the production minifier
 * properties begin uninitialized and PHP cannot restore that state.
 *
 * @since  6.1.6
 */
#[CoversClass(Minify::class)]
#[UsesClass(Css::class)]
#[UsesClass(JavaScript::class)]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MinifyTest extends TestCase
{
	/**
	 * Start with fresh build-scoped minifier instances.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->resetMinifiers();
	}

	/**
	 * Minify JavaScript and retain earlier fragments in one build-scoped stream.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJavaScriptAccumulatesAndMinifiesBuildFragments(): void
	{
		$this->assertSame(
			'function enabled(){return!0}',
			Minify::js('function enabled() { return true; }')
		);
		$this->assertSame(
			'function enabled(){return!0};const count=1',
			Minify::js('const count = 1;')
		);
		$this->assertInstanceOf(JavaScript::class, Minify::$js);
	}

	/**
	 * Minify CSS and retain earlier fragments without contaminating JavaScript state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCssAccumulatesIndependentlyFromJavaScript(): void
	{
		$this->assertSame('body{color:red}', Minify::css('body { color: red; }'));
		$this->assertSame(
			'body{color:red}a{margin:0 1px}',
			Minify::css('a { margin: 0 1px; }')
		);
		$this->assertInstanceOf(Css::class, Minify::$css);

		$this->assertSame('const ready=!0', Minify::js('const ready = true;'));
	}

	/**
	 * Replace typed static properties with empty minifier instances.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function resetMinifiers(): void
	{
		Minify::$js = new JavaScript();
		Minify::$css = new Css();
	}
}
