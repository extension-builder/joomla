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
use VDM\Joomla\Componentbuilder\Compiler\Model\Sqldump;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Tests\Support\SqldumpFixture;
use VDM\Tests\Support\TestCase;


/**
 * SQL-dump mapping, quoting, batching, and generated-output contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Sqldump::class)]
final class SqldumpTest extends TestCase
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
