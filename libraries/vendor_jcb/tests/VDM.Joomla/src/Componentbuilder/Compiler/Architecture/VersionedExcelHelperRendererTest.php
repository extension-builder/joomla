<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    15th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;


/**
 * Generated component-helper spreadsheet contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedExcelHelperRendererTest extends ArchitectureTestCase
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
	 * Without the import/export option every target produces no helper code.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testExcelMethodsAreEmptyWithoutImportExport(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version));

		$this->assertSame('', $subject->get());
	}

	/**
	 * The generated spreadsheet methods keep their shared structure per target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testExcelMethodsPreserveSharedStructure(string $version, int $major): void
	{
		$this->config()->set('add_eximport', true);

		$contentone = new ContentOne();
		$contentone->set('COMPANYNAME', 'Demo Company');

		$subject = $this->renderer(
			$this->rendererClass($version),
			['contentone' => $contentone]
		);
		$code = $subject->get();

		$this->assertStringStartsWith(PHP_EOL . PHP_EOL . "\t/**", $code);
		$this->assertStringContainsString(
			"\tpublic static function xls(\$rows, \$fileName = null, \$title = null, "
			. "\$subjectTab = null, \$creator = 'Demo Company', \$description = null, "
			. "\$category = null,\$keywords = null, \$modified = null)",
			$code
		);
		$this->assertStringContainsString("\t\t\t->setCompany('Demo Company')", $code);
		$this->assertStringContainsString(
			"self::composerAutoload('phpspreadsheet');",
			$code
		);
		$this->assertStringContainsString(
			'Super_' . '__0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check($rows)',
			$code
		);
		$this->assertStringContainsString(
			"\tpublic static function getFileHeaders(\$dataType)",
			$code
		);
		$this->assertStringContainsString(
			"\tprotected static function composephpspreadsheet()",
			$code
		);
		$this->assertStringContainsString(
			"require_once JPATH_SITE . '/libraries/phpspreadsheet/vendor/autoload.php';",
			$code
		);
		$this->assertStringEndsWith("\t}", $code);
	}

	/**
	 * The user lookup is target-specific: only Joomla 3 emits its assignment.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testExcelMethodsUserLookupFollowsTheTarget(string $version, int $major): void
	{
		$this->config()->set('add_eximport', true);

		$subject = $this->renderer($this->rendererClass($version));
		$code = $subject->get();

		$this->assertStringContainsString('// set the user', $code);

		if ($major === 3)
		{
			$this->assertStringContainsString(
				"\t\t\$user = Joomla__" . "_39403062_84fb_46e0_bac4_0023f766e827___Power::getUser();",
				$code
			);
			$this->assertStringNotContainsString('getIdentity()', $code);

			return;
		}

		// the legacy generator loses the Joomla 4+ identity line to an
		// unused buffer; that current output is preserved during extraction
		$this->assertStringNotContainsString('$user = Joomla__', $code);
		$this->assertStringNotContainsString('getIdentity()', $code);
	}

	/**
	 * Joomla 4+ targets should assign the user their generated `xls()` reads.
	 *
	 * The generated method consumes `\$user->name`, so dropping the identity
	 * assignment produces broken generated code. The legacy generator sent
	 * the assignment to an unused buffer; the desired assignment is asserted
	 * here and documented in the known-defect ledger.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	#[Group('known-defect')]
	public function testModernExcelMethodsAssignTheUserTheyRead(string $version, int $major): void
	{
		$this->config()->set('add_eximport', true);

		$subject = $this->renderer($this->rendererClass($version));
		$code = $subject->get();

		$this->assertStringContainsString('$modified = $user->name;', $code);
		$this->assertStringContainsString(
			"\t\t\$user = Joomla__" . "_39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->getIdentity();",
			$code
		);
	}

	/**
	 * Joomla targets whose generated code uses the application identity.
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
		return 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\'
			. $version . '\\ComHelperClass\\ExcelMethods';
	}
}
