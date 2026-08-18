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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\AssetsTable as SharedAssetsTable;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaThree\Component\AssetsTable as J3AssetsTable;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;


/**
 * Generated uninstall script contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedUninstallScriptRendererTest extends ArchitectureTestCase
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
	 * A component that registered nothing to remove produces no script.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testScriptIsEmptyWithNothingRegistered(string $version, int $major): void
	{
		$this->config()->set('add_assets_table_fix', 0);

		$subject = $this->renderer($this->rendererClass($version));

		$this->assertSame('', $subject->get([], []));
		$this->assertSame('', $subject->get());
	}

	/**
	 * Joomla 4+ removes each registered view through the script.php helpers.
	 *
	 * A view with field relations passes the removal its true flag; a view
	 * without does not.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernScriptRemovesEachRegisteredView(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version));

		$this->assertSame(
			PHP_EOL . "\t\t// Remove Related Component Data."
			. PHP_EOL . PHP_EOL . "\t\t// Remove Look Data"
			. PHP_EOL . "\t\t\$this->removeViewData(\"com_demo.look\", true);"
			. PHP_EOL . PHP_EOL . "\t\t// Remove Look category Data"
			. PHP_EOL . "\t\t\$this->removeViewData(\"com_demo.look.category\");"
			. PHP_EOL . PHP_EOL . "\t\t// Remove Asset Data."
			. PHP_EOL . "\t\t\$this->removeAssetData();"
			. PHP_EOL,
			$subject->get(
				['look' => 'com_demo.look', 'look category' => 'com_demo.look.category'],
				['look' => true]
			)
		);
	}

	/**
	 * Joomla 4+ appends the assets table reversal after the removals.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernScriptAppendsTheAssetsTableReversal(string $version, int $major): void
	{
		$this->config()->set('add_assets_table_fix', 2);

		$subject = $this->renderer(
			$this->rendererClass($version),
			['assetstable' => new SharedAssetsTable($this->config())]
		);

		$this->assertSame(
			PHP_EOL . "\t\t// Revert the assets table rules column back to the default."
			. PHP_EOL . "\t\t\$this->removeDatabaseAssetsRulesFix();",
			$subject->get([], [])
		);
	}

	/**
	 * The component's own uninstall script renders after everything else.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testCustomUninstallScriptRendersLast(string $version, int $major): void
	{
		$this->config()->set('add_assets_table_fix', 0);

		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->method('get')->willReturn('echo "bye";' . PHP_EOL);

		$subject = $this->renderer(
			$this->rendererClass($version),
			['dispenser' => $dispenser]
		);

		$this->assertSame('echo "bye";' . PHP_EOL, $subject->get([], []));
	}

	/**
	 * Joomla 3 still prepares its script objects for the reversal alone.
	 *
	 * With nothing registered but the intelligent fix active, the generated
	 * code must still create the application and database objects the
	 * reversal reads.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreePreparesTheObjectsTheReversalNeeds(): void
	{
		$this->config()->set('add_assets_table_fix', 2);

		$subject = $this->renderer(
			$this->rendererClass('JoomlaThree'),
			['assetstable' => new J3AssetsTable($this->config())]
		);
		$code = $subject->get([], []);

		$this->assertStringStartsWith(
			PHP_EOL . "\t\t// Get Application object"
			. PHP_EOL . "\t\t\$app = Joomla__" . "_39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication();"
			. PHP_EOL . PHP_EOL . "\t\t// Get The Database object"
			. PHP_EOL . "\t\t\$db = Joomla__" . "_39403062_84fb_46e0_bac4_0023f766e827___Power::getDbo();",
			$code
		);
		$this->assertStringContainsString('$revert_rule = "ALTER TABLE `#__assets`', $code);
	}

	/**
	 * Joomla 3 removes the registered types, fields and history itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeRemovesTypesFieldsAndHistory(): void
	{
		$subject = $this->renderer($this->rendererClass('JoomlaThree'));
		$code = $subject->get(
			['look' => 'com_demo.look', 'look category' => 'com_demo.look.category'],
			['look' => true]
		);

		// only the view with field relations clears the fields tables
		$this->assertStringContainsString(
			"\t\t\t\$query->delete(\$db->quoteName('#__fields'));",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\t\$look_condition = array( \$db->quoteName('field_id') . ' IN ('. implode(',', \$look_field_ids) .')');",
			$code
		);
		$this->assertStringNotContainsString('$look_category_field_ids', $code);

		// both views clear their content types and history
		$this->assertStringContainsString(
			"\$query->where( \$db->quoteName('type_alias') . ' = '. \$db->quote('com_demo.look') );",
			$code
		);
		$this->assertStringContainsString(
			"\$query->where( \$db->quoteName('type_alias') . ' = '. \$db->quote('com_demo.look.category') );",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\tforeach (\$look_ids as \$look_id)",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\tforeach (\$look_category_ids as \$look_category_id)",
			$code
		);
		$this->assertStringContainsString(
			"\$query->delete(\$db->quoteName('#__ucm_history'));",
			$code
		);

		// the assets removal reuses the last view's done variable, as the
		// legacy generator always has
		$this->assertStringContainsString(
			"\t\t\$demo_condition = array( \$db->quoteName('name') . ' LIKE ' . \$db->quote('com_demo%') );",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\$query->where(\$demo_condition);"
			. PHP_EOL . "\t\t\$db->setQuery(\$query);"
			. PHP_EOL . "\t\t\$look_category_done = \$db->execute();"
			. PHP_EOL . "\t\tif (\$look_category_done)",
			$code
		);
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
		// only Joomla 3 removes its registered content types, fields and history
		return $this->targetClass(
			$version, 'Component\\UninstallScript', ['JoomlaThree']
		);
	}
}
