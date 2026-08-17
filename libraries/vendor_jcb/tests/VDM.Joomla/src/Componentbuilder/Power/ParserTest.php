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

namespace VDM\Joomla\Tests\Componentbuilder\Power;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Tests\Support\TestCase;


/**
 * Structural parsing contracts for the power class parser.
 *
 * The parser is the only reader between a stored power and every consumer that
 * reasons about its members, so each test here protects one structural promise:
 * a member is found, its parts are read from its own declaration, and code that
 * merely looks like a declaration is never treated as one.
 *
 * @since  6.1.6
 */
#[CoversClass(Parser::class)]
final class ParserTest extends TestCase
{
	/**
	 * Protect structured parsing of properties, methods, types, versions, and bodies.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserExtractsClassMetadataAndSupportingSections(): void
	{
		$subject = new Parser();
		$code = <<<'PHP'
<?php
/** License text */
namespace Demo;

use Alpha\One;
use Beta\Two as Two;

final class Widget
{
	use FirstTrait, SecondTrait;

	/** Property docs */
	protected ?string $name = 'demo';

	/**
	 * Run.
	 * @param int $count Count.
	 * @return string
	 * @since 2.3.4
	 */
	final public function run(int $count = 2): string
	{
		return 'done';
	}
}
PHP;
		$parsed = $subject->code($code);

		$this->assertSame('$name', $parsed['properties'][0]['name']);
		$this->assertSame('protected', $parsed['properties'][0]['access']);
		$this->assertSame('?string', $parsed['properties'][0]['type']);
		$this->assertSame("'demo'", $parsed['properties'][0]['default']);
		$this->assertSame('Property docs', $parsed['properties'][0]['comment']);
		$this->assertSame('run', $parsed['methods'][0]['name']);
		$this->assertTrue($parsed['methods'][0]['final']);
		$this->assertSame('string', $parsed['methods'][0]['return_type']);
		$this->assertSame('2.3.4', $parsed['methods'][0]['since']);
		$this->assertSame(['name' => '$count', 'type' => 'int', 'default' => '2'], $parsed['methods'][0]['arguments']['$count']);
		$this->assertSame("\n\t\treturn 'done';\n\t", $parsed['methods'][0]['body']);
		$this->assertSame('* License text', $subject->getClassLicense($code));
		$this->assertSame(['use Alpha\\One;', 'use Beta\\Two as Two;'], $subject->getUseStatements($code));
		$this->assertSame(['FirstTrait', 'SecondTrait'], $subject->getTraits($subject->getClassCode($code)));
	}

	/**
	 * Read member modifiers in either legal order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserRecognizesStandardStaticTypedPropertyOrdering(): void
	{
		$parsed = (new Parser())->code('class Demo { protected static ?string $name = null; }');

		$this->assertSame('$name', $parsed['properties'][0]['name']);
		$this->assertTrue($parsed['properties'][0]['static']);
		$this->assertSame('protected', $parsed['properties'][0]['access']);
		$this->assertSame('?string', $parsed['properties'][0]['type']);
		$this->assertSame('null', $parsed['properties'][0]['default']);
	}

	/**
	 * Give every method the body that belongs to its own declaration.
	 *
	 * A signature wrapped over several lines reads no differently from one
	 * written on a single line, and neither method may be given the other's body.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserGivesEachMethodItsOwnBodyAcrossWrappedSignatures(): void
	{
		$code = <<<'PHP'
<?php
class Demo
{
	public function first(): string
	{
		return 'first';
	}

	public function __construct(
		string $alpha,
		int $beta
	)
	{
		$this->alpha = $alpha;
	}
}
PHP;
		$methods = (new Parser())->code($code)['methods'];

		$this->assertSame(['first', '__construct'], array_column($methods, 'name'));
		$this->assertSame("\n\t\treturn 'first';\n\t", $methods[0]['body']);
		$this->assertSame("\n\t\t\$this->alpha = \$alpha;\n\t", $methods[1]['body']);
		$this->assertSame(
			'public function __construct( string $alpha, int $beta )',
			$methods[1]['declaration']
		);
		$this->assertSame(
			['$alpha' => '$alpha', '$beta' => '$beta'],
			array_column($methods[1]['arguments'], 'name', 'name')
		);
	}

	/**
	 * Read structure from code, never from the text inside a literal.
	 *
	 * A brace, a semicolon, or a whole declaration written inside a string is
	 * data. Treating it as structure truncates bodies and moves them between
	 * methods, so every one of these shapes must be ignored.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserIgnoresDeclarationsAndBracesInsideLiterals(): void
	{
		$code = <<<'PHP'
<?php
class Demo
{
	/**
	 * The first method.
	 * @since 1.0.0
	 */
	public function first(): string
	{
		$brace = '}';

		return $brace . "public function target(): int";
	}

	/*
	public function commentedOut(): void
	{
	}
	*/

	/**
	 * The target method.
	 * @since 9.9.9
	 */
	public function target(): int
	{
		return 42;
	}
}
PHP;
		$methods = (new Parser())->code($code)['methods'];

		$this->assertSame(['first', 'target'], array_column($methods, 'name'));
		$this->assertSame(
			"\n\t\t\$brace = '}';\n\n\t\treturn \$brace . \"public function target(): int\";\n\t",
			$methods[0]['body']
		);
		$this->assertSame('1.0.0', $methods[0]['since']);
		$this->assertSame("\n\t\treturn 42;\n\t", $methods[1]['body']);
		$this->assertSame('9.9.9', $methods[1]['since']);
	}

	/**
	 * Ignore a declaration that only appears inside a heredoc template.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserIgnoresDeclarationsInsideHeredocTemplates(): void
	{
		$code = "<?php\n"
			. "class Demo\n"
			. "{\n"
			. "\tpublic function render(): string\n"
			. "\t{\n"
			. "\t\treturn <<<'TPL'\n"
			. "\tpublic function ghost(): void\n"
			. "\t{\n"
			. "\t}\n"
			. "TPL;\n"
			. "\t}\n"
			. "}\n";
		$methods = (new Parser())->code($code)['methods'];

		$this->assertSame(['render'], array_column($methods, 'name'));
		$this->assertStringContainsString('public function ghost(): void', $methods[0]['body']);
	}

	/**
	 * Keep the whole declaration when an argument default contains brackets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserReadsArgumentListsContainingBracketsAndCommas(): void
	{
		$code = <<<'PHP'
<?php
class Demo
{
	public function set($key, $value = array(), ?Registry $registry = null, array $map = ['a' => 1, 'b' => 2]): bool
	{
		return true;
	}
}
PHP;
		$method = (new Parser())->code($code)['methods'][0];

		$this->assertSame(
			"public function set(\$key, \$value = array(), ?Registry \$registry = null, array \$map = ['a' => 1, 'b' => 2]): bool",
			$method['declaration']
		);
		$this->assertSame('bool', $method['return_type']);
		$this->assertSame(['$key', '$value', '$registry', '$map'], array_keys($method['arguments']));
		$this->assertSame('array()', $method['arguments']['$value']['default']);
		$this->assertSame('?Registry', $method['arguments']['$registry']['type']);
		$this->assertSame('null', $method['arguments']['$registry']['default']);
		$this->assertSame("['a' => 1, 'b' => 2]", $method['arguments']['$map']['default']);
	}

	/**
	 * Read a variadic argument's type, and a zero default as the value it is.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserReadsVariadicTypesAndZeroDefaults(): void
	{
		$code = <<<'PHP'
<?php
class Demo
{
	private function join(string ...$parts): bool
	{
		return true;
	}

	public function update(string $string, int $debug = 0, ?array $extra = null): string
	{
		return $string;
	}
}
PHP;
		$methods = (new Parser())->code($code)['methods'];

		$this->assertSame('string', $methods[0]['arguments']['$parts']['type']);
		$this->assertNull($methods[0]['arguments']['$parts']['default']);
		$this->assertSame('int', $methods[1]['arguments']['$debug']['type']);
		$this->assertSame('0', $methods[1]['arguments']['$debug']['default']);
		$this->assertSame('null', $methods[1]['arguments']['$extra']['default']);
	}
	/**
	 * Keep property names and defaults that span underscores and nested arrays.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserReadsUnderscoredNamesAndNestedArrayDefaults(): void
	{
		$code = <<<'PHP'
<?php
class Demo
{
	protected string $prefix_key = '';

	protected $_pattern = 'Super';

	protected array $bucket = ['install' => [], 'update' => []];

	private readonly int|string $mixed;
}
PHP;
		$properties = (new Parser())->code($code)['properties'];

		$this->assertSame(
			['$prefix_key', '$_pattern', '$bucket', '$mixed'],
			array_column($properties, 'name')
		);
		$this->assertSame("''", $properties[0]['default']);
		$this->assertSame("'Super'", $properties[1]['default']);
		$this->assertSame("['install' => [], 'update' => []]", $properties[2]['default']);
		$this->assertSame('int|string', $properties[3]['type']);
		$this->assertNull($properties[3]['default']);
	}

	/**
	 * Never read a promoted constructor argument or a method-local static as a property.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserSeparatesPropertiesFromArgumentsAndLocalStatics(): void
	{
		$code = <<<'PHP'
<?php
class Demo
{
	protected string $real = 'yes';

	public function __construct(
		private string $promoted,
		protected int $counted = 0
	)
	{
		static $cache = [];
	}
}
PHP;
		$properties = (new Parser())->code($code)['properties'];

		$this->assertSame(['$real'], array_column($properties, 'name'));
	}

	/**
	 * Read by-reference returns, intersection types, and declared access levels.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserReadsReferenceReturnsIntersectionTypesAndModifiers(): void
	{
		$code = <<<'PHP'
<?php
abstract class Demo
{
	public function &reference(): array
	{
		return $this->store;
	}

	public function intersection(): Countable&Traversable
	{
		return $this->store;
	}

	abstract protected static function hop(int $step): void;

	function bare()
	{
		return 1;
	}
}
PHP;
		$methods = (new Parser())->code($code)['methods'];

		$this->assertSame(['reference', 'intersection', 'hop', 'bare'], array_column($methods, 'name'));
		$this->assertSame('array', $methods[0]['return_type']);
		$this->assertSame('Countable&Traversable', $methods[1]['return_type']);
		$this->assertSame('protected', $methods[2]['access']);
		$this->assertTrue($methods[2]['static']);
		$this->assertTrue($methods[2]['abstract']);
		$this->assertNull($methods[2]['body']);
		$this->assertSame('public', $methods[3]['access']);
	}

	/**
	 * Report no body for a method that declares none.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserReportsNoBodyForInterfaceMethods(): void
	{
		$code = <<<'PHP'
<?php
interface Demo
{
	public function first(): string;

	public function second(): int;
}
PHP;
		$methods = (new Parser())->code($code)['methods'];

		$this->assertSame(['first', 'second'], array_column($methods, 'name'));
		$this->assertNull($methods[0]['body']);
		$this->assertNull($methods[1]['body']);
	}

	/**
	 * Find the class body behind every declaration form a power may use.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetClassCodeFindsEveryDeclarationForm(): void
	{
		$subject = new Parser();

		$this->assertSame(
			'public $code = 1;',
			$subject->getClassCode("<?php\nclass Demo extends \\InvalidArgumentException\n{\n\tpublic \$code = 1;\n}\n")
		);
		$this->assertSame(
			'public function go();',
			$subject->getClassCode("<?php\ninterface Demo extends First, Second\n{\n\tpublic function go();\n}\n")
		);
		$this->assertSame(
			'public $code = 1;',
			$subject->getClassCode(
				"<?php\nabstract class Demo extends Base implements \\JsonSerializable, \\Countable\n{\n\tpublic \$code = 1;\n}\n"
			)
		);
		$this->assertSame(
			"case Draft = 'draft';",
			$subject->getClassCode("<?php\nenum Demo: string\n{\n\tcase Draft = 'draft';\n}\n")
		);
		$this->assertSame(
			'public $code = 1;',
			$subject->getClassCode("<?php\n#[Attribute]\nreadonly class Demo\n{\n\tpublic \$code = 1;\n}\n")
		);
		$this->assertNull($subject->getClassCode("<?php\n\$value = 1;\n"));
	}

	/**
	 * Stop the class body at its own closing brace.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetClassCodeExcludesAnythingAfterTheClass(): void
	{
		$code = "<?php\nclass Demo\n{\n\tpublic \$code = 1;\n}\n\n// trailing note\n";

		$this->assertSame('public $code = 1;', (new Parser())->getClassCode($code));
	}

	/**
	 * Return every import a caller has to know about before adding one.
	 *
	 * Import groups separated by a blank line are still imports: a caller that
	 * only sees the first group re-imports a bound name and breaks the class it
	 * is writing.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetUseStatementsReturnsEveryImportGroupWithoutTraits(): void
	{
		$code = <<<'PHP'
<?php
namespace Demo;

use Joomla\CMS\Factory;

use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\StringHelper as Strings;

class Demo
{
	use FirstTrait;

	public function go(): void
	{
	}
}
PHP;

		$this->assertSame(
			[
				'use Joomla\\CMS\\Factory;',
				'use VDM\\Joomla\\Utilities\\ArrayHelper;',
				'use VDM\\Joomla\\Utilities\\StringHelper as Strings;'
			],
			(new Parser())->getUseStatements($code)
		);
		$this->assertNull((new Parser())->getUseStatements("<?php\nclass Demo\n{\n}\n"));
	}

	/**
	 * Read trait imports, including one that resolves a conflict.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetTraitsReadsConflictResolutionBlocks(): void
	{
		$subject = new Parser();
		$code = <<<'PHP'
<?php
class Demo
{
	use FirstTrait, SecondTrait {
		FirstTrait::go insteadof SecondTrait;
	}

	use ThirdTrait;

	public function template(): string
	{
		return 'use GhostTrait;';
	}
}
PHP;

		$this->assertSame(
			['FirstTrait', 'SecondTrait', 'ThirdTrait'],
			$subject->getTraits($subject->getClassCode($code))
		);
	}

	/**
	 * Produce the same structure whatever line endings the source arrived with.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserIsIndependentOfIncomingLineEndings(): void
	{
		$subject = new Parser();
		$code = "<?php\nclass Demo\n{\n\tprotected ?string \$name = 'demo';\n\n\tpublic function go(): int\n\t{\n\t\treturn 1;\n\t}\n}\n";

		$this->assertSame(
			$subject->code($code),
			$subject->code(str_replace("\n", "\r\n", $code))
		);
		$this->assertSame(
			$subject->code($code),
			$subject->code("\xEF\xBB\xBF" . str_replace("\n", "\r", $code))
		);
		$this->assertSame("\n\t\treturn 1;\n\t", $subject->code($code)['methods'][0]['body']);
	}

	/**
	 * Report nothing rather than something wrong for code that declares no members.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserReportsNothingForCodeWithoutMembers(): void
	{
		$subject = new Parser();
		$parsed = $subject->code("<?php\nclass Demo\n{\n\tpublic const NAME = 'demo';\n}\n");

		$this->assertNull($parsed['properties']);
		$this->assertNull($parsed['methods']);
		$this->assertNull($subject->getTraits("<?php\nclass Demo\n{\n}\n"));
		$this->assertNull($subject->getClassLicense("<?php\nclass Demo\n{\n}\n"));
		$this->assertNull($subject->getClassLicense("namespace Demo;\n"));
	}
}
