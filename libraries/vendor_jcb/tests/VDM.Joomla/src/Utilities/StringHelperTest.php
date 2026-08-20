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
	 * Strip every tag and encode what is left.
	 *
	 * The objective is plain text: not a subset of markup, and not markup
	 * shown literally. Every tag goes, the text those tags wrapped stays, and
	 * the result is encoded so it is safe in a text node and in an attribute.
	 *
	 * @param   string  $stored    The value as stored.
	 * @param   string  $expected  What the page must receive.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('sanitizedValues')]
	public function testSanitizeReducesAValueToEncodedPlainText(string $stored, string $expected): void
	{
		$this->assertSame($expected, StringHelper::sanitize($stored));
	}

	/**
	 * Values and the plain text they must reduce to.
	 *
	 * @return  array<string, array{string,string}>
	 * @since   6.1.7
	 */
	public static function sanitizedValues(): array
	{
		return [
			'script tag' => ['<script>alert(1)</script>bold', 'alert(1)bold'],
			'formatting tag is removed too' => ['<b>bold</b>', 'bold'],
			'block tags are removed' => ['<p>para</p><div>block</div>', 'parablock'],
			'anchor is removed, text kept' => ['<a href="#x">link</a>', 'link'],
			'event handler attribute' => ['<img src=x onerror=alert(1)>', ''],
			'ampersand and quotes are encoded' => ['Smith & Sons "Ltd"', 'Smith &amp; Sons &quot;Ltd&quot;'],
			'attribute breakout is encoded' => ['" autofocus onfocus="x', '&quot; autofocus onfocus=&quot;x'],
			'single quote is encoded' => ["it's", 'it&#039;s'],
			'plain text is untouched' => ['Acme Trading', 'Acme Trading'],
		];
	}

	/**
	 * Keep html() working as an alias of sanitize().
	 *
	 * html() is called in many places and has always been a sanitiser, so it
	 * has to keep the same signature and return the same value.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testHtmlIsAnAliasOfSanitize(): void
	{
		foreach (self::sanitizedValues() as $case)
		{
			$this->assertSame(
				StringHelper::sanitize($case[0]),
				StringHelper::html($case[0])
			);
		}

		$this->assertSame(
			StringHelper::sanitize('Alpha beta gamma delta', 'UTF-8', true, 12, false),
			StringHelper::html('Alpha beta gamma delta', 'UTF-8', true, 12, false)
		);
		$this->assertSame('', StringHelper::html(null));
	}

	/**
	 * Encode on the shortening branches too.
	 *
	 * @param   bool  $shorten  Whether the shortening branch is exercised.
	 * @param   int   $length   The shortening length.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('sanitizeBranches')]
	public function testSanitizeEncodesOnEveryBranch(bool $shorten, int $length): void
	{
		$this->assertSame(
			'&quot; autofocus onfocus=&quot;alert(1)',
			StringHelper::sanitize('" autofocus onfocus="alert(1)', 'UTF-8', $shorten, $length)
		);
		$this->assertSame(
			'Smith &amp; Sons',
			StringHelper::sanitize('Smith & Sons', 'UTF-8', $shorten, $length)
		);
	}

	/**
	 * The branches sanitize() can take before it returns.
	 *
	 * @return  array<string, array{bool,int}>
	 * @since   6.1.7
	 */
	public static function sanitizeBranches(): array
	{
		return [
			'no shortening' => [false, 40],
			'shortening, value already short enough' => [true, 200],
		];
	}

	/**
	 * Encode the tooltip branch exactly once.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSanitizeEncodesTheShortenedTooltipBranchExactlyOnce(): void
	{
		$this->assertSame(
			'<span class="hasTip" title="Smith &amp; Sons &quot;Ltd&quot; of London"'
				. ' style="cursor:help">Smith &amp;...</span>',
			StringHelper::sanitize('Smith & <b>Sons</b> "Ltd" of London', 'UTF-8', true, 10)
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
