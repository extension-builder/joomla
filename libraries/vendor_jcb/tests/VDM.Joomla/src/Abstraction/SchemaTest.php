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

namespace VDM\Joomla\Tests\Abstraction;

use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use VDM\Joomla\Abstraction\Schema;
use VDM\Joomla\Interfaces\TableInterface;
use VDM\Tests\Support\JoomlaTestCase;
use VDM\Tests\Support\SchemaFixture;

/**
 * Schema scan, SQL type, and default-value contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Schema::class)]
final class SchemaTest extends JoomlaTestCase
{
	/**
	 * Complete an existing-table scan without emitting unnecessary updates.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdateReportsCleanExistingSchema(): void
	{
		$database = $this->database(['jos_fixture_records'], mock: true);
		$database->expects($this->once())
			->method('getTableColumns')
			->with('jos_fixture_records', false)
			->willReturn(['id' => (object) ['Type' => 'INT(11)']]);
		$table = $this->createMock(TableInterface::class);
		$table->expects($this->once())->method('tables')->willReturn(['records']);
		$table->expects($this->once())->method('fields')->with('records', true)->willReturn(['id']);
		$table->expects($this->once())->method('get')->with('records', 'id', 'db')->willReturn(null);

		$this->assertSame(
			[
				'Success: scan of the component tables started.',
				'Success: scan of the component tables completed with no update needed.',
			],
			$this->subject($database, $table)->update()
		);
	}

	/**
	 * Create a missing table with column, primary, unique, and ordinary keys.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdateCreatesMissingTableWithConfiguredKeys(): void
	{
		$database = $this->database([], mock: true);
		$database->expects($this->exactly(4))
			->method('quoteName')
			->willReturnCallback(static fn (string $name): string => '`' . $name . '`');
		$database->expects($this->once())
			->method('setQuery')
			->with(
				'CREATE TABLE IF NOT EXISTS `jos_fixture_records` '
				. '(`id` INT AUTO_INCREMENT, `guid` VARCHAR(36), `state` TINYINT DEFAULT 1, '
				. 'PRIMARY KEY  (`id`), UNIQUE KEY `idx_record_guid` (`guid`), KEY `idx_state` (`state`))'
			);
		$database->expects($this->once())->method('execute')->willReturn(true);
		$table = $this->createMock(TableInterface::class);
		$table->expects($this->once())->method('tables')->willReturn(['records']);
		$table->expects($this->once())
			->method('fields')
			->with('records', true)
			->willReturn(['id', 'guid', 'state']);
		$table->expects($this->exactly(3))
			->method('get')
			->willReturnMap(
				[
					['records', 'id', 'db', ['type' => 'INT', 'auto_increment' => true]],
					[
						'records',
						'guid',
						'db',
						['type' => 'VARCHAR(36)', 'unique_key' => true, 'unique_key_name' => 'record_guid'],
					],
					['records', 'state', 'db', ['type' => 'TINYINT', 'default' => '1', 'key' => true]],
				]
			);

		$this->assertSame(
			[
				'Success: scan of the component tables started.',
				'Success: created missing  records table.',
				'Success: scan of the component tables completed.',
			],
			$this->subject($database, $table)->update()
		);
	}

	/**
	 * Distinguish irrelevant integer display widths from contract-bearing types.
	 *
	 * @param   string  $current   Current type.
	 * @param   string  $expected  Expected type.
	 * @param   bool    $changed   Expected significance.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideTypeChanges')]
	public function testTypeChangePolicy(string $current, string $expected, bool $changed): void
	{
		$subject = $this->subject(
			$this->database([]),
			$this->createStub(TableInterface::class)
		);

		$this->assertSame($changed, $subject->significantTypeChange($current, $expected));
	}

	/**
	 * Supply significant and insignificant SQL type changes.
	 *
	 * @return  iterable<string, array{string, string, bool}>
	 * @since   6.1.6
	 */
	public static function provideTypeChanges(): iterable
	{
		yield 'integer display width' => ['INT(11)', 'int', false];
		yield 'integer unsigned modifier' => ['INT(11) UNSIGNED', 'int', false];
		yield 'integer to bigint' => ['INT', 'BIGINT', true];
		yield 'varchar size' => ['VARCHAR(50)', 'varchar(100)', true];
		yield 'decimal precision' => ['DECIMAL(10,2)', 'decimal(12,2)', true];
	}

	/**
	 * Ignore insignificant whitespace inside decimal precision declarations.
	 *
	 * The method documentation promises normalized precision whitespace, but
	 * its early decimal comparison currently happens before normalization.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testDecimalPrecisionWhitespaceIsNotSignificant(): void
	{
		$subject = $this->subject(
			$this->database([]),
			$this->createStub(TableInterface::class)
		);

		$this->assertFalse(
			$subject->significantTypeChange('DECIMAL(10, 2)', 'decimal(10,2)')
		);
	}

	/**
	 * Apply database-version-specific date defaults and quote ordinary values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDefaultValuePolicyUsesDatabaseCapabilities(): void
	{
		$subject = $this->subject(
			$this->database([], 'mysql', '8.4.0'),
			$this->createStub(TableInterface::class)
		);

		$this->assertSame(' DEFAULT CURRENT_TIMESTAMP', $subject->defaultValue('DATETIME', 'CURRENT_TIMESTAMP'));
		$this->assertSame('CURRENT_TIMESTAMP', $subject->defaultValue('datetime', 'CURRENT_TIMESTAMP', true));
		$this->assertSame(" DEFAULT 'draft'", $subject->defaultValue('VARCHAR(20)', 'draft'));
		$this->assertSame('', $subject->defaultValue('TEXT', null));
		$this->assertSame('', $subject->defaultValue('TEXT', 'EMPTY'));
	}

	/**
	 * Install the database and construct a schema fixture.
	 *
	 * @param   DatabaseInterface  $database  Database boundary.
	 * @param   TableInterface     $table     Schema metadata boundary.
	 *
	 * @return  SchemaFixture
	 * @since   6.1.6
	 */
	private function subject(DatabaseInterface $database, TableInterface $table): SchemaFixture
	{
		$this->setJoomlaFactoryProperty('database', $database);

		return new SchemaFixture($table);
	}

	/**
	 * Build a deterministic database mock for schema initialization.
	 *
	 * @param   array<int, string>  $tables   Existing tables.
	 * @param   string              $type     Database server type.
	 * @param   string              $version  Database version.
	 * @param   bool                $mock     Build an expectation-capable mock.
	 *
	 * @return  DatabaseInterface
	 * @since   6.1.6
	 */
	private function database(
		array $tables,
		string $type = 'mysql',
		string $version = '8.0.0',
		bool $mock = false
	): DatabaseInterface
	{
		$database = $mock
			? $this->createMock(DatabaseInterface::class)
			: $this->createStub(DatabaseInterface::class);
		$database->method('getVersion')->willReturn($version);
		$database->method('getServerType')->willReturn($type);
		$database->method('getTableList')->willReturn($tables);
		$database->method('getPrefix')->willReturn('jos_');
		$database->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);

		return $database;
	}
}
