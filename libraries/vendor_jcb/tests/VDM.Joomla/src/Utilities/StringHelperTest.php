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

namespace VDM\Joomla\Tests\Utilities;


use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * String normalization, formatting, and sanitization contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(StringHelper::class)]
final class StringHelperTest extends JoomlaTestCase
{
	/**
	 * Original active language tag restored after each test.
	 *
	 * @var    mixed
	 * @since  6.1.6
	 */
	private mixed $originalLanguageTag;

	/**
	 * Snapshot language state before installing isolated test collaborators.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalLanguageTag = StringHelper::$langTag;
	}

	/**
	 * Restore language state so random execution cannot leak it between tests.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		StringHelper::$langTag = $this->originalLanguageTag;

		parent::tearDown();
	}

	/**
	 * Accept only non-empty strings containing at least one non-whitespace character.
	 *
	 * @param   mixed  $input     Candidate value.
	 * @param   bool   $expected  Expected validity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('stringValidityProvider')]
	public function testCheckRequiresMeaningfulStringContent(mixed $input, bool $expected): void
	{
		$this->assertSame($expected, StringHelper::check($input));
	}

	/**
	 * Provide valid and invalid string-like values.
	 *
	 * @return  iterable<string, array{mixed, bool}>
	 * @since   6.1.6
	 */
	public static function stringValidityProvider(): iterable
	{
		yield 'word' => ['value', true];
		yield 'surrounding whitespace' => ["\t value \n", true];
		yield 'zero string' => ['0', true];
		yield 'empty' => ['', false];
		yield 'whitespace only' => [" \t\n", false];
		yield 'integer' => [42, false];
		yield 'null' => [null, false];
		yield 'object' => [(object) ['value' => 'text'], false];
	}

	/**
	 * Preserve short values and truncate long values at a word boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testShortenPreservesWordsAndOptionallyAddsEscapedTooltip(): void
	{
		$this->assertSame('short text', StringHelper::shorten('short text', 40));
		$this->assertSame(23, StringHelper::shorten(23, 4));
		$this->assertSame(
			'Alpha beta...',
			StringHelper::shorten('Alpha beta gamma delta', 12, false)
		);
		$this->assertSame(
			'<span class="hasTip" title="Alpha beta &quot;gamma&quot; delta" style="cursor:help">Alpha beta...</span>',
			StringHelper::shorten('Alpha beta "gamma" delta', 12, true)
		);
		$this->assertSame('Unbroken...', StringHelper::shorten('UnbrokenLongValue', 8, false));
	}

	/**
	 * Apply every public case mode after deterministic transliteration.
	 *
	 * @param   string  $type      Requested formatting mode.
	 * @param   string  $expected  Expected normalized value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('safeModeProvider')]
	public function testSafeAppliesDocumentedCaseModes(string $type, string $expected): void
	{
		$this->installPassThroughLanguage();

		$this->assertSame($expected, StringHelper::safe('  Hello WORLD  ', $type));
	}

	/**
	 * Provide every documented case-mode family.
	 *
	 * @return  iterable<string, array{string, string}>
	 * @since   6.1.6
	 */
	public static function safeModeProvider(): iterable
	{
		yield 'lower identifier' => ['L', 'hello_world'];
		yield 'lower alias' => ['strtolower', 'hello_world'];
		yield 'title words' => ['W', 'Hello World'];
		yield 'lower words' => ['w', 'hello world'];
		yield 'sentence words' => ['Ww', 'Hello world'];
		yield 'upper words' => ['WW', 'HELLO WORLD'];
		yield 'upper identifier' => ['U', 'HELLO_WORLD'];
		yield 'first letter' => ['F', 'Hello_world'];
		yield 'camel case' => ['cA', 'helloWorld'];
	}

	/**
	 * Control number retention, separators, unknown modes, and filename cleaning.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSafeHonorsSanitizationOptionsAndFilenameContract(): void
	{
		$this->installPassThroughLanguage();

		$this->assertSame('a-six-code', StringHelper::safe('A 6 code', 'L', '-', true, true));
		$this->assertSame('a-6-code', StringHelper::safe('A 6 code', 'L', '-', false, false));
		$this->assertSame('A code', StringHelper::safe('A. code!', 'unknown'));
		$this->assertSame(
			'vDm Report six (draft)',
			StringHelper::safe('VDM Report @ 6 (draft)', 'filename')
		);
		$this->assertSame('', StringHelper::safe(null));
	}

	/**
	 * Resolve the configured language and return its transliterated result.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTransliterateUsesConfiguredLanguageFactory(): void
	{
		$language = $this->createMock(Language::class);
		$language->expects($this->once())
			->method('transliterate')
			->with('Grüße')
			->willReturn('Gruesse');
		$factory = $this->createMock(LanguageFactoryInterface::class);
		$factory->expects($this->once())
			->method('createLanguage')
			->with('de-DE')
			->willReturn($language);
		$container = new Container();
		$container->share(LanguageFactoryInterface::class, $factory, true);
		$this->setJoomlaContainer($container);
		StringHelper::$langTag = 'de-DE';

		$this->assertSame('Gruesse', StringHelper::transliterate('Grüße'));
	}

	/**
	 * Escape markup instead of deleting it, and shorten the escaped result.
	 *
	 * @return  void
	 * @since   6.1.6
	 * @since   6.1.7  html() escapes rather than filters.
	 */
	public function testHtmlEscapesMarkupAndCanShortenTheResult(): void
	{
		$this->assertSame(
			'&lt;script&gt;alert(1)&lt;/script&gt;&lt;b&gt;Safe &amp;amp; sound&lt;/b&gt;',
			StringHelper::html('<script>alert(1)</script><b>Safe &amp; sound</b>')
		);
		$this->assertSame(
			'Alpha beta...',
			StringHelper::html('Alpha beta gamma delta', 'UTF-8', true, 12, false)
		);
		$this->assertSame('', StringHelper::html(null));
	}

	/**
	 * Never lose text that only looks like markup.
	 *
	 * A tag blacklist deletes anything shaped like a tag, so routing ordinary
	 * values through one silently destroyed data: "3 < 5" was stored, and "3"
	 * was displayed. Escaping is lossless.
	 *
	 * @param   string  $stored    The value as stored.
	 * @param   string  $expected  What the page must show.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('losslessValues')]
	public function testHtmlNeverDropsTextThatLooksLikeATag(string $stored, string $expected): void
	{
		$this->assertSame($expected, StringHelper::html($stored));
	}

	/**
	 * Ordinary values whose text a tag blacklist would eat.
	 *
	 * @return  array<string, array{string,string}>
	 * @since   6.1.7
	 */
	public static function losslessValues(): array
	{
		return [
			'company name in angle brackets' => ['Widgets <Pty> Ltd', 'Widgets &lt;Pty&gt; Ltd'],
			'comparison' => ['3 < 5', '3 &lt; 5'],
			'two comparisons' => ['A < B and B > C', 'A &lt; B and B &gt; C'],
			'literal that is not a tag' => ['<not-a-tag> literal', '&lt;not-a-tag&gt; literal'],
		];
	}

	/**
	 * Keep renderable markup while removing what can execute.
	 *
	 * This is the objective html() used to carry as a side effect, and it is
	 * only correct for a field that genuinely holds authored HTML.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSanitizeKeepsRenderableMarkupAndDropsTheExecutable(): void
	{
		$this->assertSame(
			'alert(1)<b>bold</b>',
			StringHelper::sanitize('<script>alert(1)</script><b>bold</b>')
		);
		$this->assertSame('<img src="x" />', StringHelper::sanitize('<img src="x" onerror="alert(1)">'));
		$this->assertSame('<a>click</a>', StringHelper::sanitize('<a href="javascript:alert(1)">click</a>'));
		$this->assertSame('', StringHelper::sanitize('<iframe src="//evil"></iframe>'));
		$this->assertSame('', StringHelper::sanitize(null));
	}

	/**
	 * Encode the characters that let a value escape its HTML context.
	 *
	 * The generated HtmlView::escape() delegates here. Without encoding, a
	 * value carrying a quote breaks straight out of an HTML attribute.
	 *
	 * @param   bool  $shorten  Whether the shortening branch is exercised.
	 * @param   int   $length   The shortening length.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('htmlEncodingBranches')]
	public function testHtmlEncodesAttributeBreakingCharactersOnEveryBranch(bool $shorten, int $length): void
	{
		$this->assertSame(
			'&quot; autofocus onfocus=&quot;alert(1)',
			StringHelper::html('" autofocus onfocus="alert(1)', 'UTF-8', $shorten, $length)
		);
		$this->assertSame(
			'&#039; autofocus onfocus=&#039;alert(1)',
			StringHelper::html("' autofocus onfocus='alert(1)", 'UTF-8', $shorten, $length)
		);
		$this->assertSame(
			'Smith &amp; Sons',
			StringHelper::html('Smith & Sons', 'UTF-8', $shorten, $length)
		);

		// the tables store decoded text, so a stored literal entity is text
		// and must reach the page as that literal
		$this->assertSame(
			'Smith &amp;amp; Sons',
			StringHelper::html('Smith &amp; Sons', 'UTF-8', $shorten, $length)
		);
	}

	/**
	 * The branches html() can take before it returns.
	 *
	 * @return  array<string, array{bool,int}>
	 * @since   6.1.7
	 */
	public static function htmlEncodingBranches(): array
	{
		return [
			'no shortening' => [false, 40],
			'shortening, value already short enough' => [true, 200],
		];
	}

	/**
	 * Keep the tooltip branch encoded exactly once.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testHtmlEncodesTheShortenedTooltipBranchExactlyOnce(): void
	{
		$this->assertSame(
			'<span class="hasTip" title="Smith &amp; Sons &quot;Ltd&quot; of London"'
				. ' style="cursor:help">Smith &amp;...</span>',
			StringHelper::html('Smith & Sons "Ltd" of London', 'UTF-8', true, 10)
		);
	}

	/**
	 * Convert numeric values across every magnitude and sign branch.
	 *
	 * @param   mixed   $input     Number or pass-through value.
	 * @param   mixed   $expected  Expected English representation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('numberProvider')]
	public function testNumberConvertsWholeValuesToEnglish(mixed $input, mixed $expected): void
	{
		$this->assertSame($expected, StringHelper::number($input));
	}

	/**
	 * Provide units, tens, hundreds, thousands, millions, and pass-throughs.
	 *
	 * @return  iterable<string, array{mixed, mixed}>
	 * @since   6.1.6
	 */
	public static function numberProvider(): iterable
	{
		yield 'zero' => [0, 'zero'];
		yield 'teen' => [19, 'nineteen'];
		yield 'tens' => [42, 'forty two'];
		yield 'hundreds' => [317, 'three hundred and seventeen'];
		yield 'thousands' => [12042, 'twelve thousand and forty two'];
		yield 'millions' => [2000003, 'two million and three'];
		yield 'negative' => [-12, 'minus twelve'];
		yield 'fractional pass through' => [1.5, 1.5];
		yield 'non numeric pass through' => ['word', 'word'];
	}

	/**
	 * Replace every digit group in mixed text and terminate recursion cleanly.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNumbersReplacesEveryNumericGroupInText(): void
	{
		$this->assertSame(
			'Version twelve has three fixes',
			StringHelper::numbers('Version 12 has 3 fixes')
		);
		$this->assertSame('No digits', StringHelper::numbers('No digits'));
		$this->assertNull(StringHelper::numbers(null));
	}

	/**
	 * Produce the requested number of characters from the documented letter bag.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRandomProducesOnlyLettersAtTheRequestedLength(): void
	{
		$this->assertSame('', StringHelper::random(0));

		$value = StringHelper::random(128);

		$this->assertSame(128, strlen($value));
		$this->assertMatchesRegularExpression('/^[A-Za-z]+$/', $value);
	}

	/**
	 * Install a language boundary that returns input unchanged.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function installPassThroughLanguage(): void
	{
		$language = $this->createStub(Language::class);
		$language->method('transliterate')
			->willReturnCallback(static fn (string $value): string => $value);
		$factory = $this->createStub(LanguageFactoryInterface::class);
		$factory->method('createLanguage')->willReturn($language);
		$container = new Container();
		$container->share(LanguageFactoryInterface::class, $factory, true);
		$this->setJoomlaContainer($container);
		StringHelper::$langTag = 'en-GB';
	}
}
