<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    10th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Customcode;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\RequiredFields;
use VDM\Minify\Abstraction\Minify;
use VDM\Minify\JavaScript;


/**
 * Protect authored required-field discovery at the JavaScript lexical boundary.
 *
 * @since  6.2.1
 */
#[CoversClass(RequiredFields::class)]
#[UsesClass(JavaScript::class)]
#[UsesClass(Minify::class)]
final class RequiredFieldsTest extends TestCase
{
	/**
	 * Discover only literal unqualified helper targets in supported JavaScript.
	 *
	 * @param   string        $script    The authored source.
	 * @param   list<string>  $expected  The permitted field names in source order.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('scriptProvider')]
	public function testDiscoversOnlyLiteralUnqualifiedHelperTargets(string $script, array $expected): void
	{
		$this->assertSame($expected, (new RequiredFields())->get($script));
	}

	/**
	 * Cover literal targets, lexical lookalikes, PHP templates and call boundaries.
	 *
	 * @return  array<string, array{string, list<string>}>
	 * @since   6.2.1
	 */
	public static function scriptProvider(): array
	{
		return [
			'empty source' => ['', []],
			'plain calls retain order and remove duplicates' => [
				'updateFieldRequired("datatype", 1); updateFieldRequired(\'store\', 0);'
					. ' updateFieldRequired("datatype", 0); updateFieldRequired("_field-2", 1);',
				['datatype', 'store', '_field-2'],
			],
			'whitespace and comment trivia in calls' => [
				"updateFieldRequired /* reason */ (\n 'datatype' /* reason */ , 1);"
					. "\nupdateFieldRequired // reason\n (\"store\", 0);",
				['datatype', 'store'],
			],
			'preserved comment trivia in calls' => [
				'updateFieldRequired /*! @license MIT */ ("datatype", 1);',
				['datatype'],
			],
			'comments preserve the boundary after a keyword' => [
				'function fieldChange() { return/**/updateFieldRequired("datatype", 1); }',
				['datatype'],
			],
			'two divisions do not hide a real call' => [
				'const ratio = total / count; updateFieldRequired("datatype", 1); const half = 6 / 2;',
				['datatype'],
			],
			'division does not expose a later regular expression' => [
				'const ratio = total / count; const sample = /(?:updateFieldRequired(\'example\',1))/;'
					. ' updateFieldRequired("datatype", 1);',
				['datatype'],
			],
			'ordinary array-index division remains supported' => [
				'const ratio = values[0] / count; updateFieldRequired("datatype", 1);',
				['datatype'],
			],
			'ambiguous parenthesized division skips the whole fragment' => [
				'updateFieldRequired("datatype", 1); const ratio = (total + extra) / count;',
				[],
			],
			'comments are not code' => [
				"// updateFieldRequired('line_comment', 1);\n"
					. '/* updateFieldRequired("block_comment", 1); */'
					. '/*! updateFieldRequired("license_comment", 1); */',
				[],
			],
			'quoted examples are not code' => [
				'const first = "updateFieldRequired(\'quoted\', 1)";'
					. ' const second = \'updateFieldRequired("quoted_again", 1)\';',
				[],
			],
			'escaped quotes keep examples inside their string' => [
				'const example = "An escaped \\" quote; updateFieldRequired(\'example\', 1)";'
					. ' updateFieldRequired("datatype", 1);',
				['datatype'],
			],
			'template examples and interpolation remain unsupported' => [
				'const example = `updateFieldRequired("example", 1)`;'
					. ' const dynamic = `${updateFieldRequired("interpolation", 1)}`;',
				[],
			],
			'simple template substitutions stay opaque beside real calls' => [
				'const template = `Example updateFieldRequired("example", 1) ${target[0]} ${params.toString()}`;'
					. ' updateFieldRequired("datatype", 1);',
				['datatype'],
			],
			'nested templates skip the whole fragment' => [
				'const outer = `header ${`updateFieldRequired("example", 1)`}`;'
					. ' updateFieldRequired("datatype", 1);',
				[],
			],
			'complex interpolation skips surrounding declarations too' => [
				'updateFieldRequired("datatype", 1); const template = `${name || fallback}`;'
					. ' updateFieldRequired("store", 1);',
				[],
			],
			'regular expressions are not code' => [
				'const first = /updateFieldRequired("example", 1)/;'
					. ' const escaped = /\\/updateFieldRequired("escaped", 1)/;'
					. ' const bracketed = /[a/b]updateFieldRequired("bracketed", 1)/;',
				[],
			],
			'regular expression argument is not a quoted field name' => [
				'updateFieldRequired(/datatype/, 1);',
				[],
			],
			'dotall regex flag skips the whole fragment' => [
				'const pattern = /updateFieldRequired("example", 1)/s; updateFieldRequired("datatype", 1);',
				[],
			],
			'indices regex flag skips the whole fragment' => [
				'const pattern = /updateFieldRequired("example", 1)/d; updateFieldRequired("datatype", 1);',
				[],
			],
			'arrow-returned regex skips the whole fragment' => [
				'const pattern = () => /updateFieldRequired("example", 1)/; updateFieldRequired("datatype", 1);',
				[],
			],
			'control-statement regex skips the whole fragment' => [
				'if (ready) /updateFieldRequired("example", 1)/; updateFieldRequired("datatype", 1);',
				[],
			],
			'keyword cannot disguise an unsupported regex as division' => [
				'function pattern() { return /updateFieldRequired("example", 1)/s; }'
					. ' updateFieldRequired("datatype", 1);',
				[],
			],
			'dynamic and concatenated arguments are not declarations' => [
				'updateFieldRequired(fieldName, 1); updateFieldRequired("prefix_" + suffix, 1);'
					. ' updateFieldRequired(`datatype`, 1); updateFieldRequired("datatype".trim(), 1);',
				[],
			],
			'escaped and invalid field names are rejected' => [
				'updateFieldRequired("data\\u0074ype", 1); updateFieldRequired("", 1);'
					. ' updateFieldRequired("field.name", 1); updateFieldRequired("0field", 1);'
					. ' updateFieldRequired("field name", 1); updateFieldRequired("naïve", 1);',
				[],
			],
			'property calls with optional whitespace are excluded' => [
				'other.updateFieldRequired("member", 1); other . updateFieldRequired("spaced", 1);'
					. ' other?.updateFieldRequired("optional", 1);'
					. ' updateFieldRequired("datatype", 1);',
				['datatype'],
			],
			'comments cannot disguise property calls' => [
				'other /* comment */ . /* comment */ updateFieldRequired("member", 1);'
					. ' other . /*! @preserve reason */ updateFieldRequired("preserved", 1);'
					. " other . // comment\n updateFieldRequired(\"line\", 1);",
				[],
			],
			'private property calls are excluded' => [
				'class Example { #updateFieldRequired(name, status) {}'
					. ' run() { this.#updateFieldRequired("example", 1); } }'
					. ' updateFieldRequired("datatype", 1);',
				['datatype'],
			],
			'identifier suffixes are not the global helper' => [
				'øupdateFieldRequired("unicode", 1); $updateFieldRequired("dollar", 1);'
					. ' prefix_updateFieldRequired("prefixed", 1); updateFieldRequiredExtra("suffix", 1);'
					. ' updateFieldRequired("datatype", 1);',
				['datatype'],
			],
			'escaped identifiers skip the whole fragment' => [
				'\\u{61}updateFieldRequired("example", 1); updateFieldRequired("datatype", 1);',
				[],
			],
			'mixed PHP and JavaScript skip the whole fragment' => [
				'<?php $example = "updateFieldRequired(\'php_string\', 1)"; ?>'
					. 'updateFieldRequired("datatype", 1);'
					. '<?php if (true) { ?>updateFieldRequired("store", 0);<?php } ?>',
				[],
			],
			'PHP cannot manufacture an apparently literal first argument' => [
				'updateFieldRequired(<?php echo \'prefix + \'; ?>"example", 1);',
				[],
			],
			'PHP cannot manufacture an apparently unqualified call' => [
				'<?php echo \'object.\'; ?>updateFieldRequired("example", 1);',
				[],
			],
			'PHP markers in strings also skip the whole fragment conservatively' => [
				'const marker = "<?php"; updateFieldRequired("datatype", 1);',
				[],
			],
			'PHP-only source has no executable JavaScript' => [
				'<?php echo "updateFieldRequired(\'rendered\', 1)";',
				[],
			],
		];
	}

	/**
	 * The real Note/Spacer handler and its neighboring authored code remain discoverable.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testDiscoversTheActualFieldScriptAndDbCheckerTargets(): void
	{
		$source = file_get_contents(dirname(__DIR__, 8) . '/media/js/admin/field.js');
		$this->assertIsString($source);
		$dbChecker = strstr($source, 'function dbChecker(');
		$this->assertIsString($dbChecker);
		$subject = new RequiredFields();

		$this->assertSame(['datatype', 'null_switch', 'store'], $subject->get($dbChecker));
		$this->assertSame(
			[
				'datalenght_other',
				'datadefault_other',
				'indexes',
				'datalenght',
				'on_get_model_field',
				'on_save_model_field',
				'datatype',
				'datadefault',
				'null_switch',
				'store',
			],
			$subject->get($source)
		);
	}

	/**
	 * A shared compiler service never carries extracted literals into another view.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testRepeatedCallsDoNotRetainLexicalState(): void
	{
		$subject = new RequiredFields();

		$this->assertSame(['datatype'], $subject->get('updateFieldRequired("datatype", 1);'));
		$this->assertSame([], $subject->extracted);
		$this->assertSame([], $subject->get('const example = "updateFieldRequired(\'example\', 1)";'));
		$this->assertSame([], $subject->get(''));
		$this->assertSame([], $subject->get('<?php echo "dynamic"; ?>updateFieldRequired("example", 1);'));
		$this->assertSame([], $subject->get('const unsupported = /updateFieldRequired("example", 1)/s;'));
		$this->assertSame(['store'], $subject->get('updateFieldRequired("store", 0);'));
		$this->assertSame([], $subject->extracted);
	}
}
