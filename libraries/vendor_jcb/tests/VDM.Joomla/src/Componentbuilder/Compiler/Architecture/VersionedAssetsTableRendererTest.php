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


/**
 * Generated assets table intelligent fix contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedAssetsTableRendererTest extends ArchitectureTestCase
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
	 * Assets table fix options that do not carry the intelligent treatment.
	 *
	 * The intelligent treatment only renders on option 2; no option and the
	 * SQL fix option quietly produce nothing.
	 *
	 * @return  array<string, array{int}>
	 * @since   6.1.7
	 */
	public static function inactiveOptions(): array
	{
		return [
			'no fix' => [0],
			'sql fix' => [1],
		];
	}

	/**
	 * Without the intelligent fix option every target produces no code.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTreatmentIsEmptyWithoutTheIntelligentOption(string $version, int $major): void
	{
		foreach ([0, 1] as $option)
		{
			$this->config()->set('add_assets_table_fix', $option);

			$subject = $this->renderer($this->rendererClass($version));

			$this->assertSame('', $subject->install());
			$this->assertSame('', $subject->uninstall());
		}
	}

	/**
	 * Joomla 4+ installs hand the fix to the script.php helper method.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernInstallCallsTheScriptHelperFix(string $version, int $major): void
	{
		$this->config()->set('add_assets_table_fix', 2);
		$this->config()->set('access_worse_case', 4800);

		$subject = $this->renderer($this->rendererClass($version));

		$this->assertSame(
			PHP_EOL . "\t\t\t// Fix the assets table rules column size."
			. PHP_EOL . "\t\t\t\$this->setDatabaseAssetsRulesFix(4800, \"TEXT\");",
			$subject->install()
		);
	}

	/**
	 * The column data type follows the worst case over the 64000 boundary.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernInstallDataTypeFollowsTheWorstCase(string $version, int $major): void
	{
		$this->config()->set('add_assets_table_fix', 2);

		$subject = $this->renderer($this->rendererClass($version));

		$this->config()->set('access_worse_case', 64000);
		$this->assertStringContainsString(
			'$this->setDatabaseAssetsRulesFix(64000, "TEXT");',
			$subject->install()
		);

		$this->config()->set('access_worse_case', 64001);
		$this->assertStringContainsString(
			'$this->setDatabaseAssetsRulesFix(64001, "MEDIUMTEXT");',
			$subject->install()
		);
	}

	/**
	 * Without a gathered worst case the fix still renders, at zero.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernInstallDefaultsTheWorstCaseToZero(string $version, int $major): void
	{
		$this->config()->set('add_assets_table_fix', 2);

		$subject = $this->renderer($this->rendererClass($version));

		$this->assertStringContainsString(
			'$this->setDatabaseAssetsRulesFix(0, "TEXT");',
			$subject->install()
		);
	}

	/**
	 * Joomla 4+ uninstalls hand the reversal to the script.php helper method.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernUninstallCallsTheScriptHelperRemoval(string $version, int $major): void
	{
		$this->config()->set('add_assets_table_fix', 2);

		$subject = $this->renderer($this->rendererClass($version));

		$this->assertSame(
			PHP_EOL . "\t\t// Revert the assets table rules column back to the default."
			. PHP_EOL . "\t\t\$this->removeDatabaseAssetsRulesFix();",
			$subject->uninstall()
		);
	}

	/**
	 * The Joomla 3 install emits the whole column check and ALTER itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeInstallEmitsTheColumnFix(): void
	{
		$this->config()->set('add_assets_table_fix', 2);
		$this->config()->set('access_worse_case', 4800);

		$subject = $this->renderer($this->rendererClass('JoomlaThree'));
		$code = $subject->install();

		$this->assertStringStartsWith(
			PHP_EOL . "\t\t\t// Get the biggest rule column in the assets table at this point.",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\t\$get_rule_length = \"SELECT CHAR_LENGTH(`rules`) as rule_size "
			. "FROM #__assets ORDER BY rule_size DESC LIMIT 1\";",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\t\tif (\$rule_length <= 4800)",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\t\t\t\$fix_rules_size = \"ALTER TABLE `#__assets` CHANGE `rules` `rules` "
			. "TEXT NOT NULL COMMENT 'JSON encoded access control. Enlarged to TEXT by JCB';\";",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\t\t\t\$app->enqueueMessage(Joomla__" . "_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_("
			. "'The <b>#__assets</b> table rules column was resized to the TEXT datatype "
			. "for the components possible large permission rules.'));",
			$code
		);

		// the install side carries no B part, so no else block renders
		$this->assertStringNotContainsString('else', $code);
		$this->assertStringEndsWith("\t\t\t}", $code);
	}

	/**
	 * The Joomla 3 install enlarges past 64000 to MEDIUMTEXT.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeInstallDataTypeFollowsTheWorstCase(): void
	{
		$this->config()->set('add_assets_table_fix', 2);
		$this->config()->set('access_worse_case', 70000);

		$subject = $this->renderer($this->rendererClass('JoomlaThree'));
		$code = $subject->install();

		$this->assertStringContainsString("if (\$rule_length <= 70000)", $code);
		$this->assertStringContainsString(
			'MEDIUMTEXT NOT NULL COMMENT \'JSON encoded access control. '
			. 'Enlarged to MEDIUMTEXT by JCB\';";',
			$code
		);
	}

	/**
	 * The Joomla 3 uninstall reverts the column and reports both outcomes.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeUninstallEmitsTheRevertAndItsElse(): void
	{
		$this->config()->set('add_assets_table_fix', 2);

		$subject = $this->renderer($this->rendererClass('JoomlaThree'));
		$code = $subject->uninstall();

		$this->assertStringStartsWith(
			PHP_EOL . "\t\t// Get the biggest rule column in the assets table at this point.",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\tif (\$rule_length < 5120)",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\t\t\$revert_rule = \"ALTER TABLE `#__assets` CHANGE `rules` `rules` "
			. "varchar(5120) NOT NULL COMMENT 'JSON encoded access control.';\";",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\t\t\$app->enqueueMessage(Text::_("
			. "'COM_COMPONENTBUILDER_REVERTED_THE_B_ASSETSB_TABLE_RULES_COLUMN_BACK_TO_ITS_"
			. "DEFAULT_SIZE_OF_VARCHARFIVE_THOUSAND_ONE_HUNDRED_AND_TWENTY'));",
			$code
		);

		// the empty B code still renders its slot, as a blank line in the else
		$this->assertStringContainsString(
			"\t\t\telse" . PHP_EOL . "\t\t\t{" . PHP_EOL . PHP_EOL
			. "\t\t\t\t\$app->enqueueMessage(Joomla__" . "_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_("
			. "'Could not revert the <b>#__assets</b> table rules column back to its default "
			. "size of varchar(5120), since there is still one or more components that still "
			. "requires the column to be larger.'));",
			$code
		);
		$this->assertStringEndsWith("\t\t}", $code);
	}

	/**
	 * Joomla targets that share the script.php helper treatment.
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
	 * Build a versioned renderer class name.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  class-string
	 * @since   6.1.7
	 */
	private function rendererClass(string $version): string
	{
		// only Joomla 3 carries the whole treatment in the generated script.php
		return $this->targetClass(
			$version, 'Component\\AssetsTable', ['JoomlaThree']
		);
	}
}
