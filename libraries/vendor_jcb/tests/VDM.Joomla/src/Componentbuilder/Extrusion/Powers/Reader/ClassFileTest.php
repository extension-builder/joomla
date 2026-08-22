<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    22nd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Powers\Reader;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Reader\ClassFile;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Tests\Support\TestCase;


/**
 * The reader that turns one PHP file into the parts a power row stores.
 *
 * The declaration is located through the lexer, so these tests concentrate on
 * exactly the places a pattern would be fooled: class names inside strings,
 * ::class constants, anonymous classes, attributes standing between a docblock
 * and its subject, and every import form PHP allows.
 *
 * @since  6.1.7
 */
#[CoversClass(ClassFile::class)]
#[UsesClass(Parser::class)]
final class ClassFileTest extends TestCase
{
	/**
	 * The reader under test.
	 *
	 * @var    ClassFile
	 * @since  6.1.7
	 */
	private ClassFile $reader;

	/**
	 * Compose the reader over the real power parser.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->reader = new ClassFile(new Parser());
	}

	/**
	 * A complete file decomposes into every part a power row stores.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACompleteFileDecomposesIntoItsParts(): void
	{
		$code = <<<'PHP'
<?php
/**
 * @package    Demo.Library
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Demo\Joomla\Data;

use Demo\Joomla\Interfaces\LoaderInterface;
use Demo\Joomla\Abstraction\Base as Foundation;
use Joomla\CMS\Factory;

/**
 * Demo Loader Class
 *
 * @since 1.0.0
 */
final class Loader extends Foundation implements LoaderInterface
{
	/**
	 * The value.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected string $value = 'demo';
}
PHP;

		$parts = $this->reader->read($code);

		$this->assertNotNull($parts);
		$this->assertSame('Demo\Joomla\Data', $parts['namespace']);
		$this->assertSame('Loader', $parts['class']);
		$this->assertSame('final class', $parts['type']);
		$this->assertSame("Demo Loader Class\n\n@since 1.0.0", $parts['docblock']);
		$this->assertStringStartsWith('/**', $parts['license']);
		$this->assertStringEndsWith('*/', $parts['license']);
		$this->assertStringContainsString('@package    Demo.Library', $parts['license']);
		$this->assertSame(['Foundation'], $parts['extends']);
		$this->assertSame(['LoaderInterface'], $parts['implements']);
		$this->assertSame(
			[
				['raw' => 'use Demo\Joomla\Interfaces\LoaderInterface;',
					'name' => 'Demo\Joomla\Interfaces\LoaderInterface', 'alias' => null, 'kind' => 'class'],
				['raw' => 'use Demo\Joomla\Abstraction\Base as Foundation;',
					'name' => 'Demo\Joomla\Abstraction\Base', 'alias' => 'Foundation', 'kind' => 'class'],
				['raw' => 'use Joomla\CMS\Factory;',
					'name' => 'Joomla\CMS\Factory', 'alias' => null, 'kind' => 'class']
			],
			$parts['uses']
		);
		$this->assertStringContainsString("protected string \$value = 'demo';", $parts['body']);
		$this->assertStringNotContainsString('class Loader', $parts['body']);
	}

	/**
	 * Every declaration keyword maps to its own power type.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryDeclarationKeywordMapsToItsPowerType(): void
	{
		$cases = [
			['class Plain {}', 'class', 'Plain'],
			['abstract class Base {}', 'abstract class', 'Base'],
			['final class Sealed {}', 'final class', 'Sealed'],
			['readonly class Value {}', 'class', 'Value'],
			['interface Contract {}', 'interface', 'Contract'],
			['trait Shared {}', 'trait', 'Shared']
		];

		foreach ($cases as [$declaration, $type, $name])
		{
			$parts = $this->reader->read("<?php\nnamespace Demo;\n\n" . $declaration . "\n");

			$this->assertNotNull($parts, $declaration);
			$this->assertSame($type, $parts['type'], $declaration);
			$this->assertSame($name, $parts['class'], $declaration);
		}
	}

	/**
	 * An enum is read, but carries no power type to store it as.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnEnumIsReadButHasNoPowerType(): void
	{
		$parts = $this->reader->read("<?php\nnamespace Demo;\n\nenum Level: string {}\n");

		$this->assertNotNull($parts);
		$this->assertNull($parts['type']);
		$this->assertSame('Level', $parts['class']);
	}

	/**
	 * Nothing that only looks like a declaration is read as one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testLookalikesAreNeverReadAsTheDeclaration(): void
	{
		$code = <<<'PHP'
<?php
namespace Demo;

use Demo\Other\Widget;

/**
 * The real class.
 *
 * @since 1.0.0
 */
final class Real
{
	public function fake(): string
	{
		$name = Widget::class;
		$maker = new class {
			public function make(): string
			{
				return 'class Impostor {}';
			}
		};

		return $name . $maker->make();
	}
}
PHP;

		$parts = $this->reader->read($code);

		$this->assertNotNull($parts);
		$this->assertSame('Real', $parts['class']);
		$this->assertSame('final class', $parts['type']);
		$this->assertSame("The real class.\n\n@since 1.0.0", $parts['docblock']);
	}

	/**
	 * An attribute may stand between a docblock and the class it describes.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAttributeDoesNotSeparateADocblockFromItsClass(): void
	{
		$code = "<?php\nnamespace Demo;\n\n/**\n * Described.\n */\n#[\\Attribute]\nclass Marked {}\n";
		$parts = $this->reader->read($code);

		$this->assertNotNull($parts);
		$this->assertSame('Marked', $parts['class']);
		$this->assertSame('Described.', $parts['docblock']);
	}

	/**
	 * A docblock claimed by an earlier statement never describes the class.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testADocblockClaimedByAnotherStatementIsNotTheClassDocblock(): void
	{
		$code = "<?php\nnamespace Demo;\n\n/**\n * About the import.\n */\nuse Demo\\Other\\Widget;\n\nclass Bare {}\n";
		$parts = $this->reader->read($code);

		$this->assertNotNull($parts);
		$this->assertSame('Bare', $parts['class']);
		$this->assertSame('', $parts['docblock']);
	}

	/**
	 * Group, list, aliased, function and const imports all decompose.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryImportFormDecomposes(): void
	{
		$code = <<<'PHP'
<?php
namespace Demo;

use Demo\Widgets\{One, Two as Second};
use Demo\Alpha, Demo\Beta;
use function Demo\helper;
use const Demo\FLAG;

class Consumer {}
PHP;

		$parts = $this->reader->read($code);

		$this->assertNotNull($parts);
		$this->assertSame(
			[
				['raw' => 'use Demo\Widgets\One;', 'name' => 'Demo\Widgets\One', 'alias' => null, 'kind' => 'class'],
				['raw' => 'use Demo\Widgets\Two as Second;', 'name' => 'Demo\Widgets\Two', 'alias' => 'Second', 'kind' => 'class'],
				['raw' => 'use Demo\Alpha;', 'name' => 'Demo\Alpha', 'alias' => null, 'kind' => 'class'],
				['raw' => 'use Demo\Beta;', 'name' => 'Demo\Beta', 'alias' => null, 'kind' => 'class'],
				['raw' => 'use function Demo\helper;', 'name' => 'Demo\helper', 'alias' => null, 'kind' => 'function'],
				['raw' => 'use const Demo\FLAG;', 'name' => 'Demo\FLAG', 'alias' => null, 'kind' => 'const']
			],
			$parts['uses']
		);
	}

	/**
	 * An interface may extend several interfaces, all of them read.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnInterfaceExtendsListIsReadInFull(): void
	{
		$code = "<?php\nnamespace Demo;\n\ninterface Wide extends One, Two, \\Demo\\Three {}\n";
		$parts = $this->reader->read($code);

		$this->assertNotNull($parts);
		$this->assertSame(['One', 'Two', '\\Demo\\Three'], $parts['extends']);
		$this->assertSame([], $parts['implements']);
	}

	/**
	 * A file with no namespace still reads, saying so plainly.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFileWithoutANamespaceReadsWithAnEmptyOne(): void
	{
		$parts = $this->reader->read("<?php\nclass Rootless {}\n");

		$this->assertNotNull($parts);
		$this->assertSame('', $parts['namespace']);
		$this->assertSame('Rootless', $parts['class']);
	}

	/**
	 * A file declaring no type at all is not a candidate.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFileWithoutADeclarationIsNotRead(): void
	{
		$this->assertNull($this->reader->read("<?php\necho 'no class here';\n"));
		$this->assertNull($this->reader->read(''));
	}

	/**
	 * A readonly anonymous class is still anonymous, never the declaration.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAReadonlyAnonymousClassIsStillAnonymous(): void
	{
		$code = "<?php\nnamespace Demo;\n\n\$value = new readonly class {\n\tpublic function a(): int\n\t{\n\t\treturn 1;\n\t}\n};\n\nfinal class Genuine {}\n";
		$parts = $this->reader->read($code);

		$this->assertNotNull($parts);
		$this->assertSame('Genuine', $parts['class']);
		$this->assertSame('final class', $parts['type']);
	}

	/**
	 * A group import may mark single branches as function or const.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAGroupImportMayMarkSingleBranches(): void
	{
		$code = "<?php\nnamespace Demo;\n\nuse Demo\\Sub\\{Widget, function helper, const FLAG,};\n\nclass Consumer {}\n";
		$parts = $this->reader->read($code);

		$this->assertNotNull($parts);
		$this->assertSame(
			[
				['raw' => 'use Demo\Sub\Widget;', 'name' => 'Demo\Sub\Widget', 'alias' => null, 'kind' => 'class'],
				['raw' => 'use function Demo\Sub\helper;', 'name' => 'Demo\Sub\helper', 'alias' => null, 'kind' => 'function'],
				['raw' => 'use const Demo\Sub\FLAG;', 'name' => 'Demo\Sub\FLAG', 'alias' => null, 'kind' => 'const']
			],
			$parts['uses'],
			'Per-branch markers bind, and a trailing comma binds nothing.'
		);
	}

	/**
	 * A body the parser cannot find comes back as a fact, not as empty code.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testABodyTheParserCannotFindIsNullNotEmpty(): void
	{
		$single = "<?php namespace Demo; class Inline { public function a(): int { return 1; } }\n";
		$parts = $this->reader->read($single);

		$this->assertNotNull($parts);
		$this->assertSame('Inline', $parts['class']);
		$this->assertNull(
			$parts['body'],
			'A declaration mid-line is one the parser cannot slice, and silence would lose the code.'
		);

		$empty = $this->reader->read("<?php\nnamespace Demo;\n\ninterface Marker\n{\n}\n");

		$this->assertNotNull($empty);
		$this->assertSame('', $empty['body'], 'A genuinely empty body is real, not lost.');
	}

	/**
	 * A body sliced off something above the declaration is never stored.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testABodySlicedOffSomethingElseIsNeverStored(): void
	{
		// the line break puts an anonymous-class header at line start, which
		// is the one shape that fools the parser's line-anchored slicer
		$code = "<?php\nnamespace Demo;\n\n\$x = new\nclass extends Base {\n\tpublic function anon(): void {}\n};\n\nfinal class Real\n{\n\tpublic function own(): void {}\n}\n";
		$parts = $this->reader->read($code);

		$this->assertNotNull($parts);
		$this->assertSame('Real', $parts['class']);
		$this->assertNull(
			$parts['body'],
			'A body that does not follow the declaration belongs to something else.'
		);
	}

	/**
	 * Only a comment that opens the file states its license.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOnlyTheOpeningCommentIsTheLicense(): void
	{
		$parts = $this->reader->read(
			"<?php\nnamespace Demo;\n\n/**\n * Not a license.\n */\nclass Late {}\n"
		);

		$this->assertNotNull($parts);
		$this->assertSame('', $parts['license']);
		$this->assertSame('Not a license.', $parts['docblock']);
	}
}
