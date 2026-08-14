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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Language-key normalization, storage, and line-break policy contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Language::class)]
#[UsesClass(Config::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(StringHelper::class)]
final class LanguageTest extends CompilerDomainTestCase
{
	/**
	 * Generate a stable prefixed key and store its source in the active target.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testKeyGeneratesAndStoresLanguageContent(): void
	{
		$subject = $this->subject([
			'lang_prefix' => 'COM_EXAMPLE',
			'lang_target' => 'admin',
			'remove_line_breaks' => false
		]);

		$this->assertSame('COM_EXAMPLE_HELLO_WORLD', $subject->key('Hello world'));
		$this->assertTrue($subject->exist('admin'));
		$this->assertTrue($subject->exist('admin', 'COM_EXAMPLE_HELLO_WORLD'));
		$this->assertSame('Hello world', $subject->get('admin', 'COM_EXAMPLE_HELLO_WORLD'));
		$this->assertSame([
			'COM_EXAMPLE_HELLO_WORLD' => 'Hello world'
		], $subject->getTarget('admin'));
	}

	/**
	 * Keep the first translation for a key and optionally add the configured prefix.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetPreservesFirstWriteAndSupportsPrefixing(): void
	{
		$subject = $this->subject([
			'lang_prefix' => 'COM_EXAMPLE',
			'remove_line_breaks' => false
		]);

		$subject->set('site', 'TITLE', ' First title ');
		$subject->set('site', 'TITLE', 'Replacement');
		$subject->set('site', 'DESCRIPTION', ' Description ', true);

		$this->assertSame('First title', $subject->get('site', 'TITLE'));
		$this->assertSame('Description', $subject->get('site', 'COM_EXAMPLE_DESCRIPTION'));
		$this->assertSame('', $subject->get('site', 'MISSING'));
		$this->assertFalse($subject->exist('missing'));
	}

	/**
	 * Replace a complete target and normalize null or empty targets on read.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTargetReplacementIsIsolatedByTarget(): void
	{
		$subject = $this->subject();
		$subject->setTarget('admin', ['A' => 'Alpha']);
		$subject->setTarget('site', null);

		$this->assertSame(['A' => 'Alpha'], $subject->getTarget('admin'));
		$this->assertSame([], $subject->getTarget('site'));
		$this->assertSame([], $subject->getTarget('missing'));
		$this->assertFalse($subject->exist('site'));
		$this->assertFalse($subject->exist('site', 'A'));
	}

	/**
	 * Apply the configured single-line policy across platform line endings.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFixHonorsLineBreakConfiguration(): void
	{
		$singleLine = $this->subject(['remove_line_breaks' => true]);
		$multiline = $this->subject(['remove_line_breaks' => false]);
		$value = "  first\r\nsecond\nthird\rfourth  ";

		$this->assertSame('firstsecondthirdfourth', $singleLine->fix($value));
		$this->assertSame("first\r\nsecond\nthird\rfourth", $multiline->fix($value));
	}

	/**
	 * Preserve the legacy false sentinel promised by existing extractor call sites.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testKeyReturnsFalseForAnExistingUppercaseLanguageKey(): void
	{
		$this->assertFalse($this->subject()->key('COM_EXAMPLE_EXISTING_KEY'));
	}

	/**
	 * Create a language store with deterministic compiler configuration.
	 *
	 * @param   array<string, mixed>  $values  Configuration values.
	 *
	 * @return  Language
	 * @since   6.1.6
	 */
	private function subject(array $values = []): Language
	{
		return new Language($this->compilerConfig(array_merge([
			'lang_prefix' => 'COM_EXAMPLE',
			'lang_target' => 'admin',
			'remove_line_breaks' => false
		], $values)));
	}
}
