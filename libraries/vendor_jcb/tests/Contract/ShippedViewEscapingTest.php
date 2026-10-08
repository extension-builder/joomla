<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    7th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use VDM\Tests\Support\TestCase;


/**
 * Execute the imported administrator views' plain-text and HTML output contracts.
 *
 * Real shipped classes run without their application-dependent constructors.
 * This catches a generated copy that reintroduces the old escape override,
 * strips wanted text, or forgets to encode attribute delimiters.
 *
 * @since  6.2.0
 */
#[CoversNothing]
final class ShippedViewEscapingTest extends TestCase
{
	/**
	 * Plain-text sanitization and inherited Joomla escaping remain distinct.
	 *
	 * @param   string  $name  The administrator view directory name.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('shippedViews')]
	public function testPlainTextSanitizationAndHtmlEscaping(string $name): void
	{
		require_once dirname(__DIR__, 4) . '/admin/src/View/' . $name . '/HtmlView.php';
		$class = 'VDM\\Component\\Componentbuilder\\Administrator\\View\\' . $name . '\\HtmlView';
		$view = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		$label = '<b>Smith &amp; Sons</b> "Ltd"';

		$this->assertSame('Smith &amp; Sons &quot;Ltd&quot;', $view->sanitize($label, false));
		$this->assertSame(
			'&lt;b&gt;Smith &amp;amp; Sons&lt;/b&gt; &quot;Ltd&quot;',
			$view->escape($label),
			'Native Joomla escaping encodes markup without stripping it or decoding existing entities.'
		);
		$this->assertSame(
			'&quot; autofocus onfocus=&quot;alert(1)',
			$view->sanitize('" autofocus onfocus="alert(1)', false),
			'A value placed inside a quoted template attribute cannot create an event handler.'
		);
		$long = str_repeat('Readable text ', 8);
		$this->assertSame($long, $view->escape($long), 'Inherited escape never applies display shortening.');
		$this->assertSame($long, $view->sanitize($long, false), 'Explicit full labels remain complete.');
		$this->assertSame(42, $view->sanitize(42));
		$this->assertNull($view->sanitize(null));
	}

	/**
	 * Load each generated view family, including list, modal, and edit views.
	 *
	 * Discovery uses filenames, not method presence, so deleting a generated
	 * sanitize method cannot silently remove that view from the tested family.
	 * The dashboard has never declared the generated plain-text helper.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.2.0
	 */
	public static function shippedViews(): array
	{
		$views = [];

		foreach (glob(dirname(__DIR__, 4) . '/admin/src/View/*/HtmlView.php') as $path)
		{
			$name = basename(dirname($path));

			if ($name !== 'Componentbuilder')
			{
				$views[$name] = [$name];
			}
		}

		return $views;
	}
}
