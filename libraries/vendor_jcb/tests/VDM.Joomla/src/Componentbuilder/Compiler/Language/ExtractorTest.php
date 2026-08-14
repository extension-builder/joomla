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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Language;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Language\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\GetHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * PHP, JavaScript, script-registration, and raw-key extraction contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Extractor::class)]
#[UsesClass(Config::class)]
#[UsesClass(Language::class)]
#[UsesClass(Placeholder::class)]
#[UsesClass(Placefix::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(GetHelper::class)]
#[UsesClass(StringHelper::class)]
final class ExtractorTest extends CompilerDomainTestCase
{
	/**
	 * Return content untouched when none of the configured call patterns occur.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEngineLeavesUnrelatedContentAndStateUntouched(): void
	{
		[$subject, $language] = $this->subject();
		$content = 'echo "Nothing to translate";';

		$this->assertSame($content, $subject->engine($content));
		$this->assertSame([], $language->getTarget('admin'));
		$this->assertSame([], $subject->langKeys);
		$this->assertSame([], $subject->langMismatch);
		$this->assertSame([], $subject->langMatch);
	}

	/**
	 * Replace all supported literal forms and store their source strings once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEngineExtractsAndRewritesPhpJavascriptAndRawKeys(): void
	{
		[$subject, $language] = $this->subject();
		$content = <<<'PHP'
Text::_('Hello world');
Joomla.Text._("Browser title");
Text::script('Browser title');
$key = JustTEXT::_('Array label');
PHP;

		$result = $subject->engine($content);

		$this->assertStringContainsString("Text::_('COM_EXAMPLE_HELLO_WORLD')", $result);
		$this->assertStringContainsString('Joomla.Text._("COM_EXAMPLE_BROWSER_TITLE")', $result);
		$this->assertStringContainsString("Text::script('COM_EXAMPLE_BROWSER_TITLE')", $result);
		$this->assertStringContainsString("\$key = 'COM_EXAMPLE_ARRAY_LABEL'", $result);
		$this->assertCount(3, $language->getTarget('admin'));
		$this->assertSame('Hello world', $language->get('admin', 'COM_EXAMPLE_HELLO_WORLD'));
		$this->assertSame('Browser title', $language->get('admin', 'COM_EXAMPLE_BROWSER_TITLE'));
		$this->assertSame('Array label', $language->get('admin', 'COM_EXAMPLE_ARRAY_LABEL'));
		$this->assertContains('Browser title', $subject->langMismatch);
		$this->assertContains('Browser title', $subject->langMatch);
	}

	/**
	 * Expand compiler placeholders before scanning source literals.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEngineExtractsLanguageFromExpandedPlaceholderContent(): void
	{
		[$subject, $language, $placeholder] = $this->subject();
		$placeholder->set_('TRANSLATABLE', "Text::_('Expanded label')");

		$result = $subject->engine('/* Text::_( */ [[[TRANSLATABLE]]]');

		$this->assertStringContainsString("Text::_('COM_EXAMPLE_EXPANDED_LABEL')", $result);
		$this->assertSame(
			'Expanded label',
			$language->get('admin', 'COM_EXAMPLE_EXPANDED_LABEL')
		);
	}

	/**
	 * Create the extractor and its observable language/placeholder stores.
	 *
	 * @return  array{Extractor, Language, Placeholder}
	 * @since   6.1.6
	 */
	private function subject(): array
	{
		$config = $this->compilerConfig([
			'lang_prefix' => 'COM_EXAMPLE',
			'lang_target' => 'admin',
			'remove_line_breaks' => false,
			'lang_string_targets' => [
				'Joomla.Text._(',
				'Text::_(',
				'Text::script(',
				'JustTEXT::_('
			]
		]);
		$language = new Language($config);
		$placeholder = new Placeholder($config);

		return [new Extractor($config, $language, $placeholder), $language, $placeholder];
	}
}
