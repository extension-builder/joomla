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

namespace VDM\Tests\Contract;


use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\CoversNothing;
use VDM\Tests\Support\FilesystemTestCase;
use VDM\Tests\Support\SourceInventory;


/**
 * Guards the production source inventory and explicit test ownership ledgers.
 *
 * @since  1.0.0
 */
#[CoversNothing]
final class SourceInventoryTest extends FilesystemTestCase
{
	/**
	 * Ensure every in-scope production declaration is explicitly accounted for.
	 *
	 * Baseline entries are pending test debt and must never be interpreted as
	 * tested subjects. Tested subjects belong exclusively in test-ownership.php.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testEveryProductionDeclarationHasExactlyOneOwnershipState(): void
	{
		$testsRoot = dirname(__DIR__);
		$inventory = SourceInventory::discover();
		$baseline = require $testsRoot . '/coverage-baseline.php';
		$ownership = require $testsRoot . '/test-ownership.php';

		$this->assertIsArray($baseline, 'coverage-baseline.php must return an array.');
		$this->assertIsArray($ownership, 'test-ownership.php must return an array.');

		$errors = SourceInventory::validate($inventory, $baseline, $ownership, $testsRoot);

		$this->assertSame(
			[],
			$errors,
			sprintf(
				"Source ownership validation failed.\nInventory: %d; pending baseline: %d; tested ownership: %d.\n- %s",
				count($inventory),
				count($baseline),
				count($ownership),
				implode("\n- ", $errors)
			)
		);
	}

	/**
	 * Ensure malformed ownership states are rejected rather than normalized.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testInvalidOwnershipStatesAreRejected(): void
	{
		$testsRoot = dirname(__DIR__);
		$ownedSubject = 'VDM.Joomla/src/Owned.php';
		$missingSubject = 'VDM.Joomla/src/Missing.php';
		$staleBaseline = 'VDM.Joomla/src/StaleBaseline.php';
		$staleOwnership = 'VDM.Joomla/src/StaleOwnership.php';
		$inventory = [
			$ownedSubject => [
				'declarations' => [['kind' => 'class', 'name' => 'VDM\\Joomla\\Owned']]
			],
			$missingSubject => [
				'declarations' => [['kind' => 'class', 'name' => 'VDM\\Joomla\\Missing']]
			]
		];
		$baseline = [$ownedSubject, $staleBaseline, $staleBaseline];
		$ownership = [
			$ownedSubject => [
				'mode' => 'smoke',
				'owner' => 'VDM.Joomla/src/DoesNotExistTest.php'
			],
			$staleOwnership => [
				'mode' => 'contract',
				'owner' => 'Contract/SourceInventoryTest.php'
			]
		];

		$errors = SourceInventory::validate($inventory, $baseline, $ownership, $testsRoot);

		$this->assertContains(
			'Production source cannot be both untested and owned: ' . $ownedSubject,
			$errors
		);
		$this->assertContains(
			'Production source is missing from both ownership ledgers: ' . $missingSubject,
			$errors
		);
		$this->assertContains('Duplicate untested baseline entry: ' . $staleBaseline, $errors);
		$this->assertContains('Stale untested baseline entry: ' . $staleBaseline, $errors);
		$this->assertContains('Stale test ownership subject: ' . $staleOwnership, $errors);
		$this->assertContains(
			'Invalid test ownership mode for ' . $ownedSubject
			. '. Allowed modes: unit, contract, provider, characterization, integration.',
			$errors
		);
		$this->assertContains(
			'Test owner does not exist for ' . $ownedSubject
			. ': VDM.Joomla/src/DoesNotExistTest.php',
			$errors
		);
	}

	/**
	 * Reject owners outside a suite, without blocking tests, or without coverage.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testOwnerMustBeBlockingSuiteMemberWithMatchingCoverage(): void
	{
		$testsRoot = dirname(__DIR__);
		$outsideSubject = 'VDM.Joomla/src/OutsideOwner.php';
		$defectSubject = 'VDM.Joomla.Gitea/src/Repository/Mirror.php';
		$uncoveredSubject = 'VDM.Joomla/src/Data/Guid.php';
		$inventory = [
			$outsideSubject => [
				'declarations' => [['kind' => 'class', 'name' => 'VDM\Joomla\OutsideOwner']]
			],
			$defectSubject => [
				'declarations' => [[
					'kind' => 'class',
					'name' => 'VDM\Joomla\Gitea\Repository\Mirror'
				]]
			],
			$uncoveredSubject => [
				'declarations' => [['kind' => 'trait', 'name' => 'VDM\Joomla\Data\Guid']]
			]
		];
		$ownership = [
			$outsideSubject => [
				'mode' => 'contract',
				'owner' => 'Contract/SourceInventoryTest.php'
			],
			$defectSubject => [
				'mode' => 'contract',
				'owner' => 'VDM.Joomla.Gitea/src/Contract/KnownDefectContractsTest.php'
			],
			$uncoveredSubject => [
				'mode' => 'unit',
				'owner' => 'VDM.Joomla/src/Utilities/DateHelperTest.php'
			]
		];

		$errors = SourceInventory::validate($inventory, [], $ownership, $testsRoot);

		$this->assertContains(
			'Invalid test owner for ' . $outsideSubject
			. ': owner must be inside one of the six configured package test suites',
			$errors
		);
		$this->assertContains(
			'Test owner has no blocking test outside the known-defect group: '
			. 'VDM.Joomla.Gitea/src/Contract/KnownDefectContractsTest.php',
			$errors
		);
		$this->assertContains(
			'Test owner coverage metadata does not include VDM\Joomla\Data\Guid: '
			. 'VDM.Joomla/src/Utilities/DateHelperTest.php',
			$errors
		);
	}

	/**
	 * Ensure only the three named legacy Helper classes are excluded.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testOnlyTheExactLegacyHelperFilesAreExcluded(): void
	{
		$this->assertFalse(
			SourceInventory::isInScope(
				'VDM.Joomla/src/Componentbuilder/Compiler/Helper/Fields.php'
			)
		);
		$this->assertFalse(
			SourceInventory::isInScope(
				'VDM.Joomla/src/Componentbuilder/Compiler/Helper/Infusion.php'
			)
		);
		$this->assertFalse(
			SourceInventory::isInScope(
				'VDM.Joomla/src/Componentbuilder/Compiler/Helper/Interpretation.php'
			)
		);
		$this->assertTrue(
			SourceInventory::isInScope(
				'VDM.Joomla/src/Componentbuilder/Compiler/Helper/FutureExtraction.php'
			)
		);
	}

	/**
	 * Keep validator roots and exclusions identical to the PHPUnit programme.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testInventoryConfigurationMatchesPhpUnitConfiguration(): void
	{
		$document = new DOMDocument();
		$this->assertTrue($document->load(dirname(__DIR__) . '/phpunit.xml.dist'));
		$configuration = $document->documentElement;
		$this->assertNotNull($configuration);
		$this->assertSame('true', $configuration->getAttribute('requireCoverageMetadata'));
		$this->assertSame('false', $configuration->getAttribute('beStrictAboutCoverageMetadata'));
		$this->assertSame('true', $configuration->getAttribute('failOnWarning'));
		$this->assertSame('true', $configuration->getAttribute('failOnPhpunitWarning'));
		$this->assertSame('true', $configuration->getAttribute('failOnRisky'));
		$xpath = new DOMXPath($document);
		$suiteDirectories = [];

		foreach ($xpath->query('/phpunit/testsuites/testsuite/directory') as $directory)
		{
			$value = trim($directory->textContent);

			if ($value !== 'Contract')
			{
				$suiteDirectories[] = $value;
			}
		}

		$testRoots = SourceInventory::testRoots();
		sort($suiteDirectories, SORT_STRING);
		sort($testRoots, SORT_STRING);
		$this->assertSame($testRoots, $suiteDirectories);

		$sourceDirectories = [];

		foreach ($xpath->query('/phpunit/source/include/directory') as $directory)
		{
			$value = trim($directory->textContent);
			$this->assertStringStartsWith('../', $value);
			$sourceDirectories[] = substr($value, 3);
		}

		$sourceRoots = SourceInventory::sourceRoots();
		sort($sourceDirectories, SORT_STRING);
		sort($sourceRoots, SORT_STRING);
		$this->assertSame($sourceRoots, $sourceDirectories);

		$excludedPaths = [];

		foreach ($xpath->query('/phpunit/source/exclude/file') as $file)
		{
			$value = trim($file->textContent);
			$this->assertStringStartsWith('../', $value);
			$excludedPaths[] = substr($value, 3);
		}

		$inventoryExclusions = SourceInventory::excludedPaths();
		sort($excludedPaths, SORT_STRING);
		sort($inventoryExclusions, SORT_STRING);
		$this->assertSame($inventoryExclusions, $excludedPaths);
	}

	/**
	 * Reject a class that looks like a test but has no PHPUnit inheritance chain.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testOwnerMustHaveStaticPhpUnitTestCaseInheritanceChain(): void
	{
		$testsRoot = $this->createTemporaryTestsRoot();

		try
		{
			$this->writeFixture(
				$testsRoot,
				'Support/BrokenTestCase.php',
				<<<'PHP'
<?php
namespace Fixture\Support;
abstract class BrokenTestCase
{
}
PHP
			);
			$this->writeFixture(
				$testsRoot,
				'Support/ValidTestCase.php',
				<<<'PHP'
<?php
namespace Fixture\Support;
abstract class ValidTestCase extends \PHPUnit\Framework\TestCase
{
}
PHP
			);
			$this->writeFixture(
				$testsRoot,
				'VDM.Joomla/src/BrokenOwnerTest.php',
				<<<'PHP'
<?php
namespace Fixture\Tests;
use Fixture\Support\BrokenTestCase;
#[\PHPUnit\Framework\Attributes\CoversClass(\VDM\Joomla\BrokenSubject::class)]
final class BrokenOwnerTest extends BrokenTestCase
{
	public function testSomething(): void
	{
	}
}
PHP
			);
			$this->writeFixture(
				$testsRoot,
				'VDM.Joomla/src/ValidOwnerTest.php',
				<<<'PHP'
<?php
namespace Fixture\Tests;
use Fixture\Support\ValidTestCase;
#[\PHPUnit\Framework\Attributes\CoversClass(\VDM\Joomla\ValidSubject::class)]
final class ValidOwnerTest extends ValidTestCase
{
	public function testSomething(): void
	{
	}
}
PHP
			);

			$inventory = [
				'VDM.Joomla/src/BrokenSubject.php' => [
					'declarations' => [[
						'kind' => 'class',
						'name' => 'VDM\Joomla\BrokenSubject'
					]]
				],
				'VDM.Joomla/src/ValidSubject.php' => [
					'declarations' => [[
						'kind' => 'class',
						'name' => 'VDM\Joomla\ValidSubject'
					]]
				]
			];
			$ownership = [
				'VDM.Joomla/src/BrokenSubject.php' => [
					'mode' => 'unit',
					'owner' => 'VDM.Joomla/src/BrokenOwnerTest.php'
				],
				'VDM.Joomla/src/ValidSubject.php' => [
					'mode' => 'unit',
					'owner' => 'VDM.Joomla/src/ValidOwnerTest.php'
				]
			];

			$errors = SourceInventory::validate($inventory, [], $ownership, $testsRoot);

			$this->assertContains(
				'Test owner is not a PHPUnit TestCase: VDM.Joomla/src/BrokenOwnerTest.php',
				$errors
			);
			$this->assertNotContains(
				'Test owner is not a PHPUnit TestCase: VDM.Joomla/src/ValidOwnerTest.php',
				$errors
			);
		}
		finally
		{
			$this->removeFixtureTree($testsRoot);
		}
	}

	/**
	 * Require trait declarations to use CoversTrait or namespace coverage.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testTraitOwnerCannotSubstituteCoversClassForCoversTrait(): void
	{
		$testsRoot = $this->createTemporaryTestsRoot();

		try
		{
			$this->writeFixture(
				$testsRoot,
				'VDM.Joomla/src/TraitOwnerTest.php',
				<<<'PHP'
<?php
namespace Fixture\Tests;
#[\PHPUnit\Framework\Attributes\CoversClass(\VDM\Joomla\ExampleTrait::class)]
final class TraitOwnerTest extends \PHPUnit\Framework\TestCase
{
	public function testSomething(): void
	{
	}
}
PHP
			);

			$subject = 'VDM.Joomla/src/ExampleTrait.php';
			$errors = SourceInventory::validate(
				[
					$subject => [
						'declarations' => [[
							'kind' => 'trait',
							'name' => 'VDM\Joomla\ExampleTrait'
						]]
					]
				],
				[],
				[
					$subject => [
						'mode' => 'unit',
						'owner' => 'VDM.Joomla/src/TraitOwnerTest.php'
					]
				],
				$testsRoot
			);

			$this->assertContains(
				'Test owner coverage metadata does not include VDM\Joomla\ExampleTrait: '
				. 'VDM.Joomla/src/TraitOwnerTest.php',
				$errors
			);
		}
		finally
		{
			$this->removeFixtureTree($testsRoot);
		}
	}

	/**
	 * Allow contract interfaces to use aggregate metadata without executable targets.
	 *
	 * Interfaces contain no executable lines for PHPUnit to target. Their exact
	 * ownership is therefore the contract ledger plus the structural assertions,
	 * while classes and traits continue to require type-correct coverage targets.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testContractInterfaceCanUseExecutablelessCoverageMetadata(): void
	{
		$testsRoot = $this->createTemporaryTestsRoot();

		try
		{
			$this->writeFixture(
				$testsRoot,
				'VDM.Joomla/src/StructuralOwnerTest.php',
				<<<'PHP'
<?php
namespace Fixture\Tests;
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class StructuralOwnerTest extends \PHPUnit\Framework\TestCase
{
	public function testSomething(): void
	{
	}
}
PHP
			);
			$this->writeFixture(
				$testsRoot,
				'VDM.Joomla/src/UnmarkedOwnerTest.php',
				<<<'PHP'
<?php
namespace Fixture\Tests;
final class UnmarkedOwnerTest extends \PHPUnit\Framework\TestCase
{
	public function testSomething(): void
	{
	}
}
PHP
			);
			$this->writeFixture(
				$testsRoot,
				'VDM.Joomla/src/InvalidInterfaceTargetOwnerTest.php',
				<<<'PHP'
<?php
namespace Fixture\Tests;
#[\PHPUnit\Framework\Attributes\CoversClass(\VDM\Joomla\InvalidTargetInterface::class)]
final class InvalidInterfaceTargetOwnerTest extends \PHPUnit\Framework\TestCase
{
	public function testSomething(): void
	{
	}
}
PHP
			);

			$owner = 'VDM.Joomla/src/StructuralOwnerTest.php';
			$unmarkedOwner = 'VDM.Joomla/src/UnmarkedOwnerTest.php';
			$invalidTargetOwner = 'VDM.Joomla/src/InvalidInterfaceTargetOwnerTest.php';
			$contractInterface = 'VDM.Joomla/src/ContractInterface.php';
			$unmarkedInterface = 'VDM.Joomla/src/UnmarkedInterface.php';
			$invalidTargetInterface = 'VDM.Joomla/src/InvalidTargetInterface.php';
			$unitInterface = 'VDM.Joomla/src/UnitInterface.php';
			$contractClass = 'VDM.Joomla/src/ContractClass.php';
			$contractTrait = 'VDM.Joomla/src/ContractTrait.php';
			$inventory = [
				$contractInterface => [
					'declarations' => [[
						'kind' => 'interface',
						'name' => 'VDM\Joomla\ContractInterface'
					]]
				],
				$unmarkedInterface => [
					'declarations' => [[
						'kind' => 'interface',
						'name' => 'VDM\Joomla\UnmarkedInterface'
					]]
				],
				$invalidTargetInterface => [
					'declarations' => [[
						'kind' => 'interface',
						'name' => 'VDM\Joomla\InvalidTargetInterface'
					]]
				],
				$unitInterface => [
					'declarations' => [[
						'kind' => 'interface',
						'name' => 'VDM\Joomla\UnitInterface'
					]]
				],
				$contractClass => [
					'declarations' => [[
						'kind' => 'class',
						'name' => 'VDM\Joomla\ContractClass'
					]]
				],
				$contractTrait => [
					'declarations' => [[
						'kind' => 'trait',
						'name' => 'VDM\Joomla\ContractTrait'
					]]
				]
			];
			$ownership = [
				$contractInterface => ['mode' => 'contract', 'owner' => $owner],
				$unmarkedInterface => ['mode' => 'contract', 'owner' => $unmarkedOwner],
				$invalidTargetInterface => [
					'mode' => 'contract',
					'owner' => $invalidTargetOwner
				],
				$unitInterface => ['mode' => 'unit', 'owner' => $owner],
				$contractClass => ['mode' => 'contract', 'owner' => $owner],
				$contractTrait => ['mode' => 'contract', 'owner' => $owner]
			];

			$errors = SourceInventory::validate($inventory, [], $ownership, $testsRoot);

			$this->assertNotContains(
				'Test owner coverage metadata does not include VDM\Joomla\ContractInterface: '
				. $owner,
				$errors
			);
			$this->assertContains(
				'Test owner coverage metadata does not include VDM\Joomla\UnmarkedInterface: '
				. $unmarkedOwner,
				$errors
			);
			$this->assertContains(
				'Test owner coverage metadata does not include VDM\Joomla\InvalidTargetInterface: '
				. $invalidTargetOwner,
				$errors
			);
			$this->assertContains(
				'Test owner coverage metadata does not include VDM\Joomla\UnitInterface: '
				. $owner,
				$errors
			);
			$this->assertContains(
				'Test owner coverage metadata does not include VDM\Joomla\ContractClass: '
				. $owner,
				$errors
			);
			$this->assertContains(
				'Test owner coverage metadata does not include VDM\Joomla\ContractTrait: '
				. $owner,
				$errors
			);
		}
		finally
		{
			$this->removeFixtureTree($testsRoot);
		}
	}

	/**
	 * Create an isolated ownership-validator fixture tree.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private function createTemporaryTestsRoot(): string
	{
		$testsRoot = $this->createTemporaryDirectory('source-inventory');
		$this->createTemporaryDirectory('source-inventory/VDM.Joomla/src');
		$this->createTemporaryDirectory('source-inventory/Support');
		$this->writeFixture(
			$testsRoot,
			'composer.json',
			json_encode(
				[
					'autoload-dev' => [
						'psr-4' => [
							'Fixture\\Tests\\' => 'VDM.Joomla/src/',
							'Fixture\\Support\\' => 'Support/'
						]
					]
				],
				JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
			)
		);

		return $testsRoot;
	}

	/**
	 * Write one isolated ownership-validator fixture.
	 *
	 * @param   string  $testsRoot    Fixture root.
	 * @param   string  $relativePath Fixture-relative path.
	 * @param   string  $source       Fixture source.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function writeFixture(string $testsRoot, string $relativePath, string $source): void
	{
		$this->assertNotFalse(file_put_contents($testsRoot . '/' . $relativePath, $source));
	}

	/**
	 * Remove an isolated ownership-validator fixture tree.
	 *
	 * @param   string  $path  Fixture root.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function removeFixtureTree(string $path): void
	{
		if (!is_dir($path))
		{
			return;
		}

		$entries = array_diff(scandir($path) ?: [], ['.', '..']);

		foreach ($entries as $entry)
		{
			$entryPath = $path . '/' . $entry;

			if (is_dir($entryPath))
			{
				$this->removeFixtureTree($entryPath);
				continue;
			}

			unlink($entryPath);
		}

		rmdir($path);
	}
}
