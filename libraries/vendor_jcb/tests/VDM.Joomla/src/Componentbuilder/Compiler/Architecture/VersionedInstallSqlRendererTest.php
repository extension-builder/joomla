<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    18th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ComponentFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseKeys;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseTables;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUninstall;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueKeys;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\MetaData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\MysqlTableSetting;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UpdateMysql;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;


/**
 * Generated install.sql contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedInstallSqlRendererTest extends ArchitectureTestCase
{
	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.7
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree', 3],
			'Joomla 4' => ['JoomlaFour', 4],
			'Joomla 5' => ['JoomlaFive', 5],
			'Joomla 6' => ['JoomlaSix', 6],
		];
	}

	/**
	 * Joomla targets that share the modern sql form.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		return [
			'Joomla 4' => ['JoomlaFour', 4],
			'Joomla 5' => ['JoomlaFive', 5],
			'Joomla 6' => ['JoomlaSix', 6],
		];
	}

	/**
	 * A component with no active database tables produces no sql.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testQuietlyProducesNothingWithoutTables(string $version, int $major): void
	{
		$this->fixOptions(0, false);

		$subject = $this->renderer($this->rendererClass($version));

		$this->assertSame('', $subject->get());
	}

	/**
	 * The modern create statement carries the fields and default columns.
	 *
	 * The fields sort alphabetically, the DATETIME default renders its
	 * keyword, and the drop statement lands in the uninstall builder.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernCreateTableCarriesFieldsAndDefaults(string $version, int $major): void
	{
		$this->fixOptions(0, false);

		$databaseuninstall = new DatabaseUninstall();
		$subject = $this->renderer(
			$this->rendererClass($version),
			[
				'databasetables' => $this->tables(),
				'databaseuninstall' => $databaseuninstall,
			]
		);
		$sql = $subject->get();

		$this->assertStringStartsWith(
			'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL
			. 'SET time_zone = "+00:00";' . PHP_EOL . PHP_EOL
			. 'CREATE TABLE IF NOT EXISTS `#__demo_look` (' . PHP_EOL
			. "\t`id` INT(11) NOT NULL AUTO_INCREMENT," . PHP_EOL
			. "\t`asset_id` INT(10) unsigned NULL DEFAULT 0 COMMENT 'FK to the #__assets table.'," . PHP_EOL
			. "\t`birth` DATETIME NULL DEFAULT DATETIME," . PHP_EOL
			. "\t`description` TEXT NULL," . PHP_EOL
			. "\t`name` VARCHAR(64) NULL DEFAULT 'cool'," . PHP_EOL
			. "\t`ordering_count` INT(11) NOT NULL DEFAULT 7," . PHP_EOL
			. "\t`params` TEXT NULL," . PHP_EOL
			. "\t`published` TINYINT(3) NULL DEFAULT 1," . PHP_EOL
			. "\t`created_by` INT unsigned NULL," . PHP_EOL
			. "\t`modified_by` INT unsigned," . PHP_EOL
			. "\t`created` DATETIME DEFAULT CURRENT_TIMESTAMP," . PHP_EOL
			. "\t`modified` DATETIME," . PHP_EOL
			. "\t`checked_out` int unsigned," . PHP_EOL
			. "\t`checked_out_time` DATETIME,",
			$sql
		);
		$this->assertStringContainsString("\tPRIMARY KEY  (`id`),", $sql);
		$this->assertStringContainsString("\tKEY `idx_state` (`published`)", $sql);
		$this->assertStringEndsWith(
			') ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8'
			. ' DEFAULT COLLATE=utf8_general_ci;' . PHP_EOL . PHP_EOL,
			$sql
		);
		$this->assertSame(
			['DROP TABLE IF EXISTS `#__demo_look`;'],
			$databaseuninstall->get('table')
		);
	}

	/**
	 * Joomla 3 keeps its column forms and carries no sql header.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeKeepsItsColumnForms(): void
	{
		$this->fixOptions(0, false);

		$subject = $this->renderer(
			$this->rendererClass('JoomlaThree'),
			['databasetables' => $this->tables()]
		);
		$sql = $subject->get();

		$this->assertStringStartsWith('CREATE TABLE IF NOT EXISTS `#__demo_look` (', $sql);
		$this->assertStringContainsString("\t`created_by` INT(10) unsigned NULL DEFAULT 0,", $sql);
		$this->assertStringContainsString("\t`modified_by` INT(10) unsigned NULL DEFAULT 0,", $sql);
		$this->assertStringContainsString("\t`created` DATETIME NULL DEFAULT '0000-00-00 00:00:00',", $sql);
		$this->assertStringContainsString("\t`modified` DATETIME NULL DEFAULT '0000-00-00 00:00:00',", $sql);
		$this->assertStringContainsString("\t`checked_out` int(11) unsigned NULL DEFAULT 0,", $sql);
		$this->assertStringContainsString("\t`checked_out_time` DATETIME NULL DEFAULT '0000-00-00 00:00:00',", $sql);
	}

	/**
	 * Access and metadata add their columns, keys and component fields.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAccessAndMetadataAddColumnsKeysAndFields(string $version, int $major): void
	{
		$this->fixOptions(0, false);

		$accessswitch = new AccessSwitch();
		$accessswitch->set('look', true);
		$metadata = new MetaData();
		$metadata->set('look', 'true');
		$databaseuniquekeys = new DatabaseUniqueKeys();
		$databaseuniquekeys->set('look', ['serial']);
		$databasekeys = new DatabaseKeys();
		$databasekeys->set('look', ['name']);
		$componentfields = new ComponentFields();

		$subject = $this->renderer(
			$this->rendererClass($version),
			[
				'databasetables' => $this->tables(),
				'accessswitch' => $accessswitch,
				'metadata' => $metadata,
				'databaseuniquekeys' => $databaseuniquekeys,
				'databasekeys' => $databasekeys,
				'componentfields' => $componentfields,
			]
		);
		$sql = $subject->get();

		$this->assertStringContainsString("\t`access` INT(10) unsigned NULL DEFAULT 0,", $sql);
		$this->assertStringContainsString("\t`metakey` TEXT,", $sql);
		$this->assertStringContainsString("\t`metadesc` TEXT,", $sql);
		$this->assertStringContainsString("\t`metadata` TEXT,", $sql);
		$this->assertStringContainsString("\tUNIQUE KEY `idx_serial` (`serial`),", $sql);
		$this->assertStringContainsString("\tKEY `idx_name` (`name`),", $sql);
		$this->assertStringContainsString("\tKEY `idx_access` (`access`),", $sql);
		$this->assertSame(
			['access', 'metakey', 'metadesc', 'metadata'],
			array_keys((array) $componentfields->get('look'))
		);
	}

	/**
	 * The gathered update sql lands in the update builder.
	 *
	 * A renamed table, a renamed field, a new field, a new view and a
	 * changed engine each write their own statement.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testUpdateSqlLandsInTheUpdateBuilder(string $version, int $major): void
	{
		$this->fixOptions(0, false);

		$registry = new Registry();
		$registry->set('builder.update_sql.table_name.look.old', 'gaze');
		$registry->set('builder.add_sql.field.look.bbb-2', true);
		$registry->set('builder.update_sql.field.name.look.name.old', 'title');
		$registry->set('builder.add_sql.adminview.look', true);
		$registry->set('builder.update_sql.table_engine.look', true);
		$updatemysql = new UpdateMysql();

		$subject = $this->renderer(
			$this->rendererClass($version),
			[
				'databasetables' => $this->tables(),
				'registry' => $registry,
				'updatemysql' => $updatemysql,
			]
		);
		$subject->get();
		$updates = $updatemysql->allActive();

		$this->assertSame(
			'RENAME TABLE `#__demo_gaze` to `#__demo_look`;',
			$updates['RENAMETABLE`#__demo_gaze`'] ?? null
		);
		$this->assertSame(
			"ALTER TABLE `#__demo_look` CHANGE `title` `name` VARCHAR(64) NULL DEFAULT 'cool';",
			$updates['ALTERTABLE`#__demo_look`CHANGE`title``name`'] ?? null
		);
		$this->assertSame(
			'ALTER TABLE `#__demo_look` ADD `ordering_count` INT(11) NOT NULL DEFAULT 7 AFTER `name`;',
			$updates['ALTERTABLE`#__demo_look`ADD`ordering_count`'] ?? null
		);
		$this->assertArrayHasKey('CREATETABLEIFNOTEXISTS`#__demo_look`', $updates);
		$this->assertSame(
			'ALTER TABLE `#__demo_look` ENGINE = MyISAM;',
			$updates['ALTERTABLE`#__demo_look`ENGINE=MyISAM'] ?? null
		);
	}

	/**
	 * The custom sql dump renders per view and is handed over.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testCustomSqlDumpRendersAndHandsOver(string $version, int $major): void
	{
		$this->fixOptions(0, false);

		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = ['sql' => [
			'look' => 'INSERT INTO `#__' . Placefix::_('component')
				. '_' . Placefix::_('view') . '` VALUES (1);',
		]];

		$subject = $this->renderer(
			$this->rendererClass($version),
			[
				'databasetables' => $this->tables(),
				'dispenser' => $dispenser,
			]
		);
		$sql = $subject->get();

		$this->assertStringEndsWith(
			'INSERT INTO `#__demo_look` VALUES (1);' . PHP_EOL . PHP_EOL,
			$sql
		);
		$this->assertArrayNotHasKey('sql', $dispenser->hub);
	}

	/**
	 * The sql fix option enlarges the assets table for the access size.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testSqlFixEnlargesTheRulesColumn(string $version, int $major): void
	{
		$this->fixOptions(1, false);

		$counter = (new ReflectionClass(Counter::class))->newInstanceWithoutConstructor();
		$counter->accessSize = 401;
		$subject = $this->renderer(
			$this->rendererClass($version),
			['databasetables' => $this->tables(), 'counter' => $counter]
		);

		$this->assertStringEndsWith(
			"ALTER TABLE `#__assets` CHANGE `rules` `rules` MEDIUMTEXT NOT NULL"
			. " COMMENT 'JSON encoded access control. Enlarged to MEDIUMTEXT by JCB';",
			$subject->get()
		);

		$counter->accessSize = 400;
		$this->assertStringEndsWith(
			"ALTER TABLE `#__assets` CHANGE `rules` `rules` TEXT NOT NULL"
			. " COMMENT 'JSON encoded access control. Enlarged to TEXT by JCB';",
			$subject->get()
		);
	}

	/**
	 * The name column enlargement only joins when its flag is set.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testSqlFixEnlargesTheNameColumnWhenSet(string $version, int $major): void
	{
		$this->fixOptions(1, true);

		$subject = $this->renderer(
			$this->rendererClass($version),
			['databasetables' => $this->tables()]
		);

		$this->assertStringEndsWith(
			"ALTER TABLE `#__assets` CHANGE `name` `name` VARCHAR(100) CHARACTER SET utf8mb4"
			. " COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The unique name for the asset.';",
			$subject->get()
		);
	}

	/**
	 * Overwritten defaults are skipped and table settings are honoured.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testOverwrittenDefaultsAreSkipped(string $version, int $major): void
	{
		$this->fixOptions(0, false);

		$fieldnames = new FieldNames();
		$fieldnames->set('look.id', 'id');
		$fieldnames->set('look.created_by', 'created_by');
		$fieldnames->set('look.metakey', 'metakey');
		$metadata = new MetaData();
		$metadata->set('look', 'true');
		$mysqltablesetting = new MysqlTableSetting();
		$mysqltablesetting->set('look.engine', 'InnoDB');
		$mysqltablesetting->set('look.row_format', 'DYNAMIC');

		$subject = $this->renderer(
			$this->rendererClass($version),
			[
				'databasetables' => $this->tables(),
				'fieldnames' => $fieldnames,
				'metadata' => $metadata,
				'mysqltablesetting' => $mysqltablesetting,
			]
		);
		$sql = $subject->get();

		$this->assertStringNotContainsString('AUTO_INCREMENT,', $sql);
		$this->assertStringNotContainsString("\t`created_by` INT unsigned NULL,", $sql);
		$this->assertStringNotContainsString('`metakey` TEXT,', $sql);
		$this->assertStringContainsString('`metadesc` TEXT,', $sql);

		// the default keys still render for the overwritten column, as the
		// legacy generator always has
		$this->assertStringContainsString("\tKEY `idx_createdby` (`created_by`),", $sql);
		$this->assertStringEndsWith(
			') ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8'
			. ' DEFAULT COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;' . PHP_EOL . PHP_EOL,
			$sql
		);
	}

	/**
	 * Set the assets table fix options the sql generation reads.
	 *
	 * @param   int   $fix      The assets table fix option.
	 * @param   bool  $namefix  The assets table name fix switch.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function fixOptions(int $fix, bool $namefix): void
	{
		$this->config()->set('add_assets_table_fix', $fix);
		$this->config()->set('add_assets_table_name_fix', $namefix);
	}

	/**
	 * Build the active database tables fixture.
	 *
	 * @return  DatabaseTables
	 * @since   6.1.7
	 */
	private function tables(): DatabaseTables
	{
		$databasetables = new DatabaseTables();
		$databasetables->set('look', [
			'name' => [
				'default' => 'Other', 'other' => 'cool', 'null_switch' => 'NULL',
				'type' => 'VARCHAR', 'lenght' => 'Other', 'lenght_other' => 64,
				'GUID' => 'aaa-1',
			],
			'ordering_count' => [
				'default' => 7, 'null_switch' => 'NOT NULL', 'type' => 'INT',
				'lenght' => 11, 'GUID' => 'bbb-2',
			],
			'description' => [
				'default' => 'EMPTY', 'null_switch' => 'NULL', 'type' => 'TEXT',
				'GUID' => 'ccc-3',
			],
			'birth' => [
				'default' => 'DATETIME', 'null_switch' => 'NULL', 'type' => 'DATETIME',
				'GUID' => 'ddd-4',
			],
		]);

		return $databasetables;
	}

	/**
	 * Build a versioned renderer class name.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  class-string
	 * @since   6.1.7
	 */
	private function rendererClass(string $version): string
	{
		// only Joomla 3 keeps the zero-date defaults and carries no sql header
		return $this->targetClass(
			$version, 'Component\\InstallSql', ['JoomlaThree']
		);
	}
}
