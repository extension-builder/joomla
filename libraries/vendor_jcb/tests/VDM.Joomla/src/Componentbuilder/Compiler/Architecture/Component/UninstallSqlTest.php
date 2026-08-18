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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Component;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\UninstallSql;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUninstall;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Generated uninstall.sql contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class UninstallSqlTest extends ArchitectureTestCase
{
	/**
	 * A component with nothing gathered quietly produces nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testQuietlyProducesNothing(): void
	{
		$this->config()->set('add_assets_table_fix', 0);
		$this->config()->set('add_assets_table_name_fix', false);

		$subject = $this->renderer(UninstallSql::class);

		$this->assertSame('', $subject->get());
	}

	/**
	 * Every gathered drop statement renders on its own line.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testDropStatementsRenderPerGatheredTable(): void
	{
		$this->config()->set('add_assets_table_fix', 0);
		$this->config()->set('add_assets_table_name_fix', false);

		$databaseuninstall = new DatabaseUninstall();
		$databaseuninstall->set('table', [
			'DROP TABLE IF EXISTS `#__demo_look`;',
			'DROP TABLE IF EXISTS `#__demo_style`;',
		]);

		$subject = $this->renderer(
			UninstallSql::class,
			['databaseuninstall' => $databaseuninstall]
		);

		$this->assertSame(
			'DROP TABLE IF EXISTS `#__demo_look`;' . PHP_EOL
			. 'DROP TABLE IF EXISTS `#__demo_style`;' . PHP_EOL,
			$subject->get()
		);
	}

	/**
	 * The custom sql uninstall dump renders once and is handed over.
	 *
	 * The legacy helper unset the dispenser's hub entry after reading it,
	 * so a second read must find nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomSqlDumpRendersAndHandsOver(): void
	{
		$this->config()->set('add_assets_table_fix', 0);
		$this->config()->set('add_assets_table_name_fix', false);

		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = ['sql_uninstall' => 'DELETE FROM `#__demo_look` WHERE 1;'];

		$subject = $this->renderer(
			UninstallSql::class,
			['dispenser' => $dispenser]
		);

		$this->assertSame(
			'DELETE FROM `#__demo_look` WHERE 1;' . PHP_EOL,
			$subject->get()
		);
		$this->assertArrayNotHasKey('sql_uninstall', $dispenser->hub);
		$this->assertSame('', $subject->get());
	}

	/**
	 * The sql fix option reverts the assets table rules column.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSqlFixRevertsTheRulesColumn(): void
	{
		$this->config()->set('add_assets_table_fix', 1);
		$this->config()->set('add_assets_table_name_fix', false);

		$subject = $this->renderer(UninstallSql::class);

		$this->assertSame(
			PHP_EOL
			. PHP_EOL . '--'
			. PHP_EOL . '-- Always insure this column rules is reversed to Joomla defaults on uninstall. (as on 1st Dec 2020)'
			. PHP_EOL . '--'
			. PHP_EOL . "ALTER TABLE `#__assets` CHANGE `rules` `rules` varchar(5120) NOT NULL COMMENT 'JSON encoded access control.';",
			$subject->get()
		);
	}

	/**
	 * The name column reversal only joins the rules reversal when set.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSqlFixRevertsTheNameColumnWhenSet(): void
	{
		$this->config()->set('add_assets_table_fix', 1);
		$this->config()->set('add_assets_table_name_fix', true);

		$subject = $this->renderer(UninstallSql::class);
		$sql = $subject->get();

		$this->assertStringContainsString(
			'-- Always insure this column name is reversed to Joomla defaults on uninstall. (as on 1st Dec 2020).',
			$sql
		);
		$this->assertStringEndsWith(
			"ALTER TABLE `#__assets` CHANGE `name` `name` VARCHAR(50) CHARACTER SET utf8mb4 "
			. "COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The unique name for the asset.';",
			$sql
		);
	}

	/**
	 * The intelligent fix option leaves the uninstall sql alone.
	 *
	 * The intelligent treatment lives in the script.php, so option 2
	 * renders nothing here.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testIntelligentFixLeavesTheSqlAlone(): void
	{
		$this->config()->set('add_assets_table_fix', 2);
		$this->config()->set('add_assets_table_name_fix', true);

		$subject = $this->renderer(UninstallSql::class);

		$this->assertSame('', $subject->get());
	}
}
