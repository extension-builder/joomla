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


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use SimpleXMLElement;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Xml;
use VDM\Joomla\Utilities\FormHelper;
use VDM\Tests\Support\TestCase;


/**
 * Compiler XML construction, formatting, and indentation contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Xml::class)]
#[UsesClass(Config::class)]
#[UsesClass(FormHelper::class)]
final class XmlTest extends TestCase
{
	/**
	 * Delegate field construction and node mutation to the shared form XML contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldAndMutationMethodsProduceExactXml(): void
	{
		$subject = $this->createSubject(false);
		$xml = $subject->get(
			['name' => 'state', 'type' => 'list'],
			['1' => 'Published']
		);

		$this->assertInstanceOf(SimpleXMLElement::class, $xml);
		$subject->attributes($xml, ['label' => 'State']);
		$subject->options($xml, ['0' => 'Unpublished']);
		$subject->comment($xml, 'Generated choices');
		$wrapper = new stdClass();
		$wrapper->fieldXML = new SimpleXMLElement('<note name="help"/>');
		$subject->append($xml, $wrapper);

		$this->assertSame('state', (string) $xml['name']);
		$this->assertSame('list', (string) $xml['type']);
		$this->assertSame('State', (string) $xml['label']);
		$this->assertSame('1', (string) $xml->option[0]['value']);
		$this->assertSame('Published', (string) $xml->option[0]);
		$this->assertSame('0', (string) $xml->option[1]['value']);
		$this->assertSame('Unpublished', (string) $xml->option[1]);
		$this->assertSame('help', (string) $xml->note['name']);
		$this->assertStringContainsString('<!--Generated choices-->', (string) $xml->asXML());
		$this->assertNull($subject->get([]));
	}

	/**
	 * Indent lines with exact first/last-line skip semantics.
	 *
	 * @param   string  $input      Source lines.
	 * @param   string  $character  Repeated indentation unit.
	 * @param   int     $depth      Repetition depth.
	 * @param   bool    $skipFirst  Whether to leave the first line untouched.
	 * @param   bool    $skipLast   Whether to leave the final line untouched.
	 * @param   string  $expected   Exact result.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('indentProvider')]
	public function testIndentHasExactBoundarySemantics(
		string $input,
		string $character,
		int $depth,
		bool $skipFirst,
		bool $skipLast,
		string $expected
	): void
	{
		$this->assertSame(
			$expected,
			$this->createSubject(false)->indent(
				$input,
				$character,
				$depth,
				$skipFirst,
				$skipLast
			)
		);
	}

	/**
	 * Provide multiline, single-line, trailing-newline, and zero-depth cases.
	 *
	 * @return  iterable<string, array{string, string, int, bool, bool, string}>
	 * @since   6.1.6
	 */
	public static function indentProvider(): iterable
	{
		yield 'all lines' => ["one\ntwo", ' ', 2, false, false, "  one\n  two"];
		yield 'skip first' => ["one\ntwo", "\t", 1, true, false, "one\n\ttwo"];
		yield 'skip last' => ["one\ntwo", '-', 2, false, true, "--one\ntwo"];
		yield 'trailing line retained' => ["one\ntwo\n", '.', 2, false, true, "..one\n..two\n"];
		yield 'single line both skipped' => ['one', '.', 3, true, true, 'one'];
		yield 'zero depth' => ["one\ntwo", '.', 0, false, false, "one\ntwo"];
	}

	/**
	 * Pretty-print only the selected node and preserve generated element content.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPrettyPrintsSelectedNodeWithoutXmlDeclaration(): void
	{
		$xml = new SimpleXMLElement(
			'<document><fieldset name="main"><field name="title" type="text"/></fieldset></document>'
		);

		$result = $this->createSubject(false)->pretty($xml, 'fieldset');

		$this->assertSame(
			"<fieldset name=\"main\">\n  <field name=\"title\" type=\"text\"/>\n</fieldset>",
			trim($result)
		);
		$this->assertStringNotContainsString('<?xml', $result);
		$this->assertStringNotContainsString('<document>', $result);
	}

	/**
	 * Emit the missing-Tidy diagnostics once and clear the build warning switch.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPrettyEmitsMissingTidyWarningOnlyOnce(): void
	{
		$config = $this->createConfig(false, true);
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(2))
			->method('enqueueMessage')
			->with($this->isString(), 'Error');
		$subject = new Xml($config, $app);
		$xml = new SimpleXMLElement('<root><child/></root>');

		$subject->pretty($xml, 'root');
		$this->assertFalse($config->get('set_tidy_warning'));

		$subject->pretty($xml, 'root');
		$this->assertFalse($config->get('set_tidy_warning'));
	}

	/**
	 * Construct a compiler XML utility with no static factory dependencies.
	 *
	 * @param   bool  $warning  Whether missing-Tidy warnings are enabled.
	 *
	 * @return  Xml
	 * @since   6.1.6
	 */
	private function createSubject(bool $warning): Xml
	{
		return new Xml(
			$this->createConfig(false, $warning),
			$this->createStub(CMSApplicationInterface::class)
		);
	}

	/**
	 * Construct explicit compiler configuration for XML formatting tests.
	 *
	 * @param   bool  $tidy     Whether Tidy formatting is requested.
	 * @param   bool  $warning  Whether the one-time warning is enabled.
	 *
	 * @return  Config
	 * @since   6.1.6
	 */
	private function createConfig(bool $tidy, bool $warning): Config
	{
		$config = new Config(
			new Input(),
			new Registry(),
			new Registry()
		);
		$config->set('tidy', $tidy);
		$config->set('set_tidy_warning', $warning);

		return $config;
	}
}
