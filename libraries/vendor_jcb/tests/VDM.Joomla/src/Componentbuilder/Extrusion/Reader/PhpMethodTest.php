<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Reader;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Php\MethodMap;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Php\Methods;
use VDM\Tests\Support\TestCase;


/**
 * Lifting method bodies out of an untrusted class and placing them in JCB.
 *
 * The extractor is a security boundary: it lexes, and never includes, requires,
 * or evaluates the source it reads. Every case below is therefore a string, and
 * the brace matching it exercises is the part a character scan gets wrong.
 *
 * @since  6.1.6
 */
#[CoversClass(Methods::class)]
#[CoversClass(MethodMap::class)]
final class PhpMethodTest extends TestCase
{
	/**
	 * A model class shaped like the ones an extrusion run really meets.
	 *
	 * It carries every trap at once: an interface declared ahead of the class, a
	 * brace inside a string, a heredoc holding a brace, an interpolated string, a
	 * nested closure, an anonymous class, a body written on one line, an abstract
	 * declaration, and a second class that must be ignored.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const MODEL = <<<'PHP'
<?php
namespace Example\Model;

interface Marker
{
	public function ignored(): void;
}

abstract class ItemModel extends BaseModel
{
	protected $config = ['brace' => '}'];

	/**
	 * Get one item.
	 */
	public function getItem($pk = null)
	{
		$brace = 'a } inside a string';
		$block = <<<SQL
			SELECT } FROM #__example_item
SQL;
		$closure = function ($row) {
			return $row->id;
		};
		$label = "item {$pk} }";

		return $closure((object) ['id' => $brace . $block . $label]);
	}

	protected static function &rows(array $rows): array
	{
		return $rows;
	}

	public function oneLiner(): int { return 42; }

	abstract public function later(): void;
}

class SecondModel
{
	public function neverSeen(): void
	{
	}
}
PHP;

	/**
	 * A trait whose only method hides another declaration inside its body.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const TRAIT_SOURCE = <<<'PHP'
<?php
trait Helper
{
	public function greet(string $name): string
	{
		$anon = new class {
			public function hidden(): int
			{
				return 1;
			}
		};

		return $name . get_class($anon);
	}
}
PHP;

	/**
	 * Every top level method of the first carrier is described in full.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMethodsDescribesEveryTopLevelMethodOfTheFirstCarrier(): void
	{
		$parsed = (new Methods())->parse(self::MODEL);

		$this->assertSame(['getItem', 'rows', 'oneLiner', 'later'], array_keys($parsed));
		$this->assertSame([
			'name' => 'rows',
			'body' => "\treturn \$rows;",
			'signature' => 'protected static function &rows(array $rows): array',
			'visibility' => 'protected',
			'static' => true,
			'line' => 30,
			'lines' => 1
		], $parsed['rows']);

		$this->assertSame('getItem', $parsed['getItem']['name']);
		$this->assertSame('public function getItem($pk = null)', $parsed['getItem']['signature']);
		$this->assertSame('public', $parsed['getItem']['visibility']);
		$this->assertFalse($parsed['getItem']['static']);
		$this->assertSame(16, $parsed['getItem']['line']);
		$this->assertSame(10, $parsed['getItem']['lines']);

		$this->assertSame('public function oneLiner(): int', $parsed['oneLiner']['signature']);
		$this->assertSame('return 42;', $parsed['oneLiner']['body']);
		$this->assertSame(1, $parsed['oneLiner']['lines']);

		$this->assertSame('abstract public function later(): void', $parsed['later']['signature']);
		$this->assertSame('public', $parsed['later']['visibility']);
		$this->assertSame('', $parsed['later']['body']);
		$this->assertSame(0, $parsed['later']['lines']);
		$this->assertSame(37, $parsed['later']['line']);
	}

	/**
	 * The closing brace is found through strings, heredocs, and closures.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMethodsFindsTheMatchingBraceThroughEveryFalseBrace(): void
	{
		$body = (new Methods())->parse(self::MODEL)['getItem']['body'];

		$this->assertSame(
			"\t\$brace = 'a } inside a string';\n"
			. "\t\$block = <<<SQL\n"
			. "\t\tSELECT } FROM #__example_item\n"
			. "SQL;\n"
			. "\t\$closure = function (\$row) {\n"
			. "\t\treturn \$row->id;\n"
			. "\t};\n"
			. "\t\$label = \"item {\$pk} }\";\n"
			. "\n"
			. "\treturn \$closure((object) ['id' => \$brace . \$block . \$label]);",
			$body
		);
		$this->assertSame(5, substr_count($body, '}'));
		$this->assertStringContainsString('SELECT } FROM #__example_item', $body);
		$this->assertStringNotContainsString('protected static function', $body);
	}

	/**
	 * The body is dedented by its own indent and keeps no outer brace.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMethodsDedentsTheBodyAndDropsTheOuterBraces(): void
	{
		$parsed = (new Methods())->parse(self::MODEL);

		foreach ($parsed as $method)
		{
			$this->assertStringStartsNotWith('{', $method['body']);
			$this->assertStringEndsNotWith('}', $method['body']);
			$this->assertStringEndsNotWith("\n", $method['body']);
		}

		$this->assertStringStartsWith("\t\$brace", $parsed['getItem']['body']);
		$this->assertStringEndsWith('$label]);', $parsed['getItem']['body']);
		$this->assertSame("\treturn \$rows;", $parsed['rows']['body']);

		$deep = (new Methods())->parse(
			"<?php\nclass D\n{\n\t\t\tpublic function deep(): int\n\t\t\t{\n\t\t\t\treturn 1;\n\t\t\t}\n}"
		);

		$this->assertSame("\treturn 1;", $deep['deep']['body']);
	}

	/**
	 * Only the first named class or trait in the file yields methods.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMethodsReadsOnlyTheFirstNamedClassOrTrait(): void
	{
		$methods = new Methods();
		$parsed = $methods->parse(self::MODEL);

		$this->assertArrayNotHasKey('ignored', $parsed);
		$this->assertArrayNotHasKey('neverSeen', $parsed);

		$fromTrait = $methods->parse(self::TRAIT_SOURCE);

		$this->assertSame(['greet'], array_keys($fromTrait));
		$this->assertArrayNotHasKey('hidden', $fromTrait);
		$this->assertStringContainsString('public function hidden(): int', $fromTrait['greet']['body']);

		$this->assertSame(
			['real'],
			array_keys($methods->parse(
				"<?php\n\$a = new class { public function inAnon(): void {} };\n\n"
				. "class Named\n{\n\tpublic function real(): void\n\t{\n\t}\n}"
			))
		);
		$this->assertSame(
			['only'],
			array_keys($methods->parse(
				"<?php\n\$x = Foo::class;\n\nclass Real\n{\n\tpublic function only(): void\n\t{\n\t}\n}"
			))
		);
	}

	/**
	 * A truncated body, an empty file, and a file with no carrier yield nothing.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMethodsRefusesWhatItCannotReadWhole(): void
	{
		$methods = new Methods();

		$this->assertSame([], $methods->parse("<?php\nclass B\n{\n\tpublic function open()\n\t{\n\t\t\$a = 1;\n"));
		$this->assertSame([], $methods->parse(''));
		$this->assertSame([], $methods->parse('<?php $a = 1;'));
		$this->assertSame([], $methods->parse("<?php\ninterface I { public function m(): void; }"));
		$this->assertSame([], $methods->parse("<?php\nclass Bodyless;"));

		$duplicate = $methods->parse(
			"<?php\nclass D\n{\n\tpublic function list(): int\n\t{\n\t\treturn 1;\n\t}\n\n"
			. "\tpublic function list(): int\n\t{\n\t\treturn 2;\n\t}\n}"
		);

		$this->assertSame(['list'], array_keys($duplicate));
		$this->assertSame("\treturn 1;", $duplicate['list']['body']);
	}

	/**
	 * Every documented Joomla method names its admin view column.
	 *
	 * Two methods share php_document because the view method that column compiles
	 * into was renamed between Joomla 3 and Joomla 4, and a tree offered for
	 * extrusion may be of either vintage.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMethodMapNamesTheColumnOfEveryDocumentedMethod(): void
	{
		$map = new MethodMap();
		$expected = [
			'getItem' => 'php_getitem',
			'getItems' => 'php_getitems',
			'getListQuery' => 'php_getlistquery',
			'save' => 'php_save',
			'postSaveHook' => 'php_postsavehook',
			'getForm' => 'php_getform',
			'allowAdd' => 'php_allowadd',
			'allowEdit' => 'php_allowedit',
			'batchCopy' => 'php_batchcopy',
			'batchMove' => 'php_batchmove',
			'publish' => 'php_before_publish',
			'delete' => 'php_before_delete',
			'cancel' => 'php_before_cancel',
			'_prepareDocument' => 'php_document',
			'setDocument' => 'php_document'
		];

		$this->assertSame($expected, $map->columns());

		foreach ($expected as $method => $column)
		{
			$this->assertSame($column, $map->column($method));
			$this->assertSame($column, $map->column(strtoupper($method)));
			$this->assertSame($column, $map->column(strtolower($method)));
			$this->assertSame($column, $map->column('  ' . $method . "\t"));
			$this->assertIsString($map->toggle($column));
		}
	}

	/**
	 * A method JCB compiles no column for has no column.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMethodMapAnswersNullForAMethodWithoutAColumn(): void
	{
		$map = new MethodMap();

		$this->assertNull($map->column('getListQueryHelper'));
		$this->assertNull($map->column('__construct'));
		$this->assertNull($map->column('populateState'));
		$this->assertNull($map->column('php_getitem'));
		$this->assertNull($map->column('document'));
		$this->assertNull($map->column(''));
		$this->assertNull($map->column('   '));
		$this->assertCount(15, $map->columns());
		$this->assertSame('php_document', $map->column('_prepareDocument'));
		$this->assertSame('php_document', $map->column('setDocument'));
	}

	/**
	 * A column's switch is named only when the column really has one.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMethodMapToggleNamesTheSwitchOfEveryGuardedColumn(): void
	{
		$map = new MethodMap();

		$this->assertSame([
			'php_getitem' => 'add_php_getitem',
			'php_getitems' => 'add_php_getitems',
			'php_getitems_after_all' => 'add_php_getitems_after_all',
			'php_getlistquery' => 'add_php_getlistquery',
			'php_getform' => 'add_php_getform',
			'php_before_save' => 'add_php_before_save',
			'php_save' => 'add_php_save',
			'php_postsavehook' => 'add_php_postsavehook',
			'php_allowadd' => 'add_php_allowadd',
			'php_allowedit' => 'add_php_allowedit',
			'php_before_cancel' => 'add_php_before_cancel',
			'php_after_cancel' => 'add_php_after_cancel',
			'php_batchcopy' => 'add_php_batchcopy',
			'php_batchmove' => 'add_php_batchmove',
			'php_before_publish' => 'add_php_before_publish',
			'php_after_publish' => 'add_php_after_publish',
			'php_before_delete' => 'add_php_before_delete',
			'php_after_delete' => 'add_php_after_delete',
			'php_document' => 'add_php_document',
			'php_ajaxmethod' => 'add_php_ajax'
		], $map->toggles());

		$this->assertSame('add_php_getitem', $map->toggle('php_getitem'));
		$this->assertSame('add_php_getitem', $map->toggle(' PHP_GETITEM '));
		$this->assertSame('add_php_ajax', $map->toggle('php_ajaxmethod'));
		$this->assertNull($map->toggle('php_model'));
		$this->assertNull($map->toggle('php_controller'));
		$this->assertNull($map->toggle('add_php_getitem'));
		$this->assertNull($map->toggle('getItem'));
		$this->assertNull($map->toggle(''));
	}
}
