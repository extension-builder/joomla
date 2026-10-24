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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Model;


use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Model\Sqldump;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Tests\Support\CompilerUtilityTestCase;
use VDM\Tests\Support\SqldumpFixture;


/**
 * SQL-dump mapping, quoting, batching, and generated-output contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Sqldump::class)]
#[UsesClass(Indent::class)]
#[UsesClass(Line::class)]
final class SqldumpTest extends CompilerUtilityTestCase
{
	/**
	 * Parse selected aliases and join relationships while ignoring malformed rows.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMappingsSeparateSelectedFieldsAndJoinRelationships(): void
	{
		$subject = $this->subject();

		$this->assertSame([
			'select' => ['b.title', 'b.created'],
			'alias' => ['category_title', 'created_on'],
			'joins' => [['from' => 'category_id', 'to' => 'id']],
		], $subject->mappings(
			"title => category_title\ncategory_id == id\nignored\ncreated => created_on",
			'b'
		));
	}

	/**
	 * Preserve scalar types that need no quoting and quote ambiguous strings safely.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEscapePreservesTypesAndQuotesAmbiguousStrings(): void
	{
		$subject = $this->subject();

		$this->assertSame(42, $subject->escaped(42));
		$this->assertSame(42, $subject->escaped('42'));
		$this->assertSame(4.2, $subject->escaped('4.2'));
		$this->assertSame("'007'", $subject->escaped('007'));
		$this->assertSame('TRUE', $subject->escaped(true));
		$this->assertSame('NULL', $subject->escaped(null));
		$this->assertSame("'O''Reilly'", $subject->escaped("O'Reilly"));
	}

	/**
	 * Emit a reviewed insert statement with placeholders, fields, rows, and a terminal newline.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDumpRendersACompleteSingleBatchInsert(): void
	{
		$output = $this->subject()->dump('article', [
			(object) ['id' => 1, 'title' => 'First'],
			(object) ['id' => 2, 'title' => "O'Reilly"],
		]);

		$this->assertStringContainsString('Dumping data for table `#__[[[component]]]_article`', $output);
		$this->assertStringContainsString(
			"INSERT INTO `#__[[[component]]]_article` (`id`, `title`) VALUES\n",
			$output
		);
		$this->assertStringContainsString("-- Batch 1 of 1 (2 rows)\n", $output);
		$this->assertStringContainsString("(1, 'First'),\n(2, 'O''Reilly');\n\n", $output);
		$this->assertStringEndsWith(";\n\n", $output);
	}

	/**
	 * Formatting applies to emitted SQL without rewriting stored values.
	 *
	 * @param   string  $indent  Configured indentation unit.
	 * @param   bool    $debug   Whether source markers are generated.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('formattingModes')]
	public function testDumpFormattingPreservesQuotedValuesAndBatchBoundaries(string $indent, bool $debug): void
	{
		(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, $indent);
		(new ReflectionProperty(Indent::class, 'bucket'))->setValue(null, []);
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, $debug);
		$subject = $this->subject();
		$value = "First\n\tO'Reilly\r\n  End";
		$data = array_fill(0, 1001, (object) ['id' => 1, 'title' => $value]);
		$output = $subject->dump('article', $data);
		$empty = $subject->dump('article', []);
		$marker = '/ \\[' . preg_quote(Sqldump::class, '/') . ' [0-9]+\\]/';

		$this->assertSame($debug ? 7 : 0, preg_match_all($marker, $output));
		$this->assertSame($debug ? 1 : 0, preg_match($marker, $empty));
		$this->assertSame('-- No data available for `article`' . PHP_EOL, preg_replace($marker, '', $empty));
		$this->assertSame(1001, substr_count($output, "(1, 'First\n\tO''Reilly\r\n  End')"));
		$this->assertSame(4, substr_count($output, 'INSERT INTO `#__[[[component]]]_article` (`id`, `title`) VALUES' . PHP_EOL));
		$this->assertSame(3, substr_count($output, ';' . PHP_EOL . PHP_EOL . '--'));
		$this->assertStringContainsString('-- Batch 4 of 4 (101 rows)' . PHP_EOL, preg_replace($marker, '', $output));
		$this->assertStringEndsWith(';' . PHP_EOL . PHP_EOL, $output);
	}

	/**
	 * Exercise compiler-owned formatting independently of authored SQL values.
	 *
	 * @return  array<string, array{string, bool}>  Formatting configurations.
	 * @since   6.1.7
	 */
	public static function formattingModes(): array
	{
		return [
			'tabs' => ["\t", false],
			'spaces' => ['    ', false],
			'tabs with comments' => ["\t", true],
			'spaces with comments' => ['    ', true],
		];
	}

	/**
	 * Choose bounded batch sizes at every documented dataset threshold.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testChunkSizeChangesAtDatasetThresholds(): void
	{
		$subject = $this->subject();

		$this->assertSame(1000, $subject->chunkSize(1000));
		$this->assertSame(300, $subject->chunkSize(1001));
		$this->assertSame(500, $subject->chunkSize(10001));
		$this->assertSame(1000, $subject->chunkSize(100001));
	}

	/**
	 * Build a deterministic SQL-dump subject.
	 *
	 * @return  SqldumpFixture
	 * @since   6.1.6
	 */
	private function subject(): SqldumpFixture
	{
		$database = $this->createStub(DatabaseInterface::class);
		$database->method('quoteName')->willReturnCallback(
			static fn(string $name): string => '`' . $name . '`'
		);
		$database->method('quote')->willReturnCallback(
			static fn(mixed $value): string => "'" . str_replace("'", "''", (string) $value) . "'"
		);

		return new SqldumpFixture(new Registry(), $database);
	}
}
