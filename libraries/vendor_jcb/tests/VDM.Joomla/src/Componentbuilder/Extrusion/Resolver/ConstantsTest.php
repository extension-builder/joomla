<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    24th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Language as LanguageRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source as SourceRegistry;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Constants;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Language;
use VDM\Tests\Support\TestCase;


/**
 * Harvested code speaks text again, because JCB's compiler makes the constant.
 *
 * @since  6.1.8
 */
#[CoversClass(Constants::class)]
final class ConstantsTest extends TestCase
{
	/**
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.1.8
	 */
	private Report $report;

	/**
	 * The resolver under test.
	 *
	 * @var    Constants
	 * @since  6.1.8
	 */
	private Constants $constants;

	/**
	 * Compose the resolver over a catalogue holding three strings.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$catalogue = new LanguageRegistry();
		$catalogue->set('constant.COM_DEMO_SAVE_FAILED', 'Can not save without an email');
		$catalogue->set('constant.COM_DEMO_QUOTED', "It's saved");
		$catalogue->set('constant.COM_DEMO_BOTH', "It's \"saved\" now");
		$catalogue->set('constant.COM_DEMO_LISTCLASS', 'The list class');
		$this->report = new Report();
		$this->constants = new Constants(
			new Language($catalogue, $this->report, new SourceRegistry()),
			$this->report
		);
	}

	/**
	 * A constant the catalogue knows becomes the English it stands for.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testEveryTextCallSpeaksItsOwnStringAgain(): void
	{
		$code = "<?php\n"
			. "\$a = Text:" . ":_('COM_DEMO_SAVE_FAILED');\n"
			. "\$b = Text:" . ':sprintf("COM_DEMO_SAVE_FAILED", $name);' . "\n"
			. "\$c = JustTEXT:" . ":_('COM_DEMO_LISTCLASS');\n";

		$reversed = $this->constants->reverse($code);

		$this->assertStringContainsString(
			"_('Can not save without an email')",
			$reversed,
			'The compiler builds the constant from the string, so the string is stored.'
		);
		$this->assertStringContainsString('sprintf("Can not save without an email"', $reversed);
		$this->assertStringContainsString("_('The list class')", $reversed);
		$this->assertStringNotContainsString('COM_DEMO', $reversed);
	}

	/**
	 * A string carrying a quote is written in the quoting it does not carry.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testAQuoteInsideTheTextDecidesTheQuotingItIsWrittenIn(): void
	{
		$reversed = $this->constants->reverse(
			"\$a = Text:" . ":_('COM_DEMO_QUOTED');"
		);

		$this->assertSame(
			"\$a = Text:" . ":_(\"It's saved\");",
			$reversed,
			'The compiler\'s extractor takes what stands between the call\'s own '
			. 'quotes and knows nothing of escaping, so an escaped quote would '
			. 'cut the string short and mint a key from the fragment.'
		);
		$this->assertNotFalse(
			@eval('return true; ' . str_replace('Text:' . ':_', 'strval', $reversed)),
			'The reversed code has to remain valid PHP.'
		);
	}

	/**
	 * A string carrying both quote marks keeps the constant it stood as.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testTextCarryingBothQuoteMarksKeepsItsConstant(): void
	{
		$code = "\$a = Text:" . ":_('COM_DEMO_BOTH');";
		$reversed = $this->constants->reverse($code);

		$this->assertSame(
			$code,
			$reversed,
			'Neither quoting can be read back by the extractor, so inventing one '
			. 'would mint a key from a fragment of the text.'
		);
		$this->assertStringContainsString(
			'both quote marks',
			(string) $this->report->get('unquotable.code_language.COM_DEMO_BOTH')
		);
	}

	/**
	 * A call form the compiler never re-keys keeps its constant.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testACallTheCompilerNeverReKeysKeepsItsConstant(): void
	{
		$code = "\$a = Text:" . ":plural('COM_DEMO_QUOTED', 2);";

		$this->assertSame(
			$code,
			$this->constants->reverse($code),
			'The language extractor reads Text::_, Text::sprintf, Text::script '
			. 'and JustTEXT::_ only (Compiler\\Config::getLangstringkeytargets), '
			. 'so turning any other call into text would leave a string the '
			. 'compiler never makes a constant of again.'
		);
	}

	/**
	 * A constant nothing can resolve is left alone, and said to be.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testAnUnknownConstantIsKeptExactlyAsTheSourceStatedIt(): void
	{
		$code = "\$a = Text:" . ":_('COM_DEMO_NOT_IN_THE_CATALOGUE');";

		$this->assertSame($code, $this->constants->reverse($code));
		$this->assertStringContainsString(
			'the catalogue holds no text',
			(string) $this->report->get('unresolved.code_language.COM_DEMO_NOT_IN_THE_CATALOGUE')
		);
	}

	/**
	 * Text that is already text, and code with no call at all, are untouched.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testCodeThatAlreadySpeaksTextIsLeftExactlyAsItIs(): void
	{
		$spoken = "\$a = Text:" . ":_('Can not save without an email');";

		$this->assertSame($spoken, $this->constants->reverse($spoken));
		$this->assertSame('', $this->constants->reverse(''));
		$this->assertSame(
			'$plain = 1;',
			$this->constants->reverse('$plain = 1;'),
			'Code holding no call is returned untouched.'
		);
	}

	/**
	 * The JavaScript side names the same strings, and is reversed too.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testTheJavascriptTextObjectIsReversedAsWell(): void
	{
		$reversed = $this->constants->reverse(
			"var a = Joomla" . ".JText._('COM_DEMO_SAVE_FAILED');"
		);

		$this->assertSame(
			"var a = Joomla" . ".JText._('Can not save without an email');",
			$reversed
		);
	}
}
