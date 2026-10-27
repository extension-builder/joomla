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

namespace VDM\Joomla\Tests\Componentbuilder\Utilities;


use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Utilities\FilterHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Database-backed component-builder filter projection tests.
 *
 * @since  6.1.6
 */
#[CoversClass(FilterHelper::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
final class FilterHelperTest extends JoomlaTestCase
{
	/**
	 * Return GUID-to-name options and preserve the empty-result contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNamesProjectsPublishedRowsByGuid(): void
	{
		[$database] = $this->database(2);
		$database->expects($this->once())
			->method('loadAssocList')
			->with('guid', 'system_name')
			->willReturn(['guid-one' => 'Alpha', 'guid-two' => 'Beta']);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame(
			['guid-one' => 'Alpha', 'guid-two' => 'Beta'],
			FilterHelper::names('joomla_component')
		);
	}

	/**
	 * Match translation records only within the reviewed extension column.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTranslationFiltersJsonMembershipAndRejectsUnknownTypes(): void
	{
		$this->assertNull(FilterHelper::translation('extension-guid', 'unsupported'));

		[$database] = $this->database(3);
		$database->expects($this->once())->method('loadAssocList')->willReturn([
			['id' => '4', 'components' => '["other-guid"]'],
			['id' => '7', 'components' => '["extension-guid","other-guid"]'],
			['id' => '9', 'components' => '["extension-guid"]'],
			['id' => '10', 'components' => 'invalid-json'],
			['id' => '11', 'components' => 'null'],
			['id' => '12', 'components' => '[0]']
		]);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame([7, 9], FilterHelper::translation('extension-guid', 'joomla_component'));
	}

	/**
	 * Retain only powers whose decoded approved-path list contains the exact path.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPathsRequiresExactDecodedRepositoryMembership(): void
	{
		[$database] = $this->database(4);
		$database->expects($this->once())
			->method('loadAssocList')
			->with('id', 'approved_paths')
			->willReturn([
				3 => '["owner/repository","owner/other"]',
				4 => '["owner/repository-extra"]',
				5 => 'not-json',
				6 => '[]'
			]);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame([3 => 3], FilterHelper::paths('owner/repository'));
	}

	/**
	 * Build unique organisation/repository option identities in database order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRepositoriesMapsObjectsToStablePathOptions(): void
	{
		[$database] = $this->database(3);
		$database->expects($this->once())->method('loadObjectList')->willReturn([
			(object) ['organisation' => 'alpha', 'repository' => 'one'],
			(object) ['organisation' => 'beta', 'repository' => 'two'],
			(object) ['organisation' => 'alpha', 'repository' => 'one']
		]);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame(
			['alpha/one' => 'alpha/one', 'beta/two' => 'beta/two'],
			FilterHelper::repositories(2)
		);
	}

	/**
	 * Return the published language option map and null for an empty query.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLanguagesPreservesTagToNameProjection(): void
	{
		[$database] = $this->database(2);
		$database->expects($this->once())
			->method('loadAssocList')
			->with('langtag', 'name')
			->willReturn(['en-GB' => 'English', 'af-ZA' => 'Afrikaans']);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame(
			['en-GB' => 'English', 'af-ZA' => 'Afrikaans'],
			FilterHelper::languages()
		);
	}

	/**
	 * Return namespace prefixes as both option values and labels.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNamespacesPreservesTrimmedNamespaceProjection(): void
	{
		[$database] = $this->database(2);
		$database->expects($this->once())
			->method('loadAssocList')
			->with('trimmed_namespace', 'trimmed_namespace')
			->willReturn(['VDM\\Joomla' => 'VDM\\Joomla', 'Acme\\Tools' => 'Acme\\Tools']);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame(
			['VDM\\Joomla' => 'VDM\\Joomla', 'Acme\\Tools' => 'Acme\\Tools'],
			FilterHelper::namespaces()
		);
	}

	/**
	 * Return matching power identifiers for every namespace segment.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNamegroupReturnsPowerIdentifiers(): void
	{
		[$database] = $this->database(2);
		$database->expects($this->once())->method('loadColumn')->willReturn([17, 23]);
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame([17, 23], FilterHelper::namegroup('VDM\\Joomla'));
	}

	/**
	 * Classify real subform values without confusing language names and prose.
	 *
	 * @param   string      $language    Language selection.
	 * @param   bool        $translated  Requested translation state.
	 * @param   array|null  $expected    Matching fixture identifiers.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('translationSelections')]
	public function testTranslationsMatchSubformEntries(string $language, bool $translated, ?array $expected): void
	{
		$query = $this->createStub(DatabaseQuery::class);
		foreach (['select', 'from', 'where', 'order'] as $method)
		{
			$query->method($method)->willReturnSelf();
		}
		$database = $this->createMock(DatabaseDriver::class);
		$database->method('getQuery')->willReturn($query);
		$database->method('quoteName')->willReturnArgument(0);
		$database->method('setQuery')->willReturnSelf();
		$database->method('execute')->willReturn(true);
		$database->method('getNumRows')->willReturn(2);
		$database->method('loadAssocList')->willReturn(['en-GB' => 'English', 'af-ZA' => 'Afrikaans']);
		$values = [
			1 => null,
			2 => '',
			3 => '[]',
			4 => '{}',
			5 => 'invalid-json',
			6 => '{"translation0":{"language":"af-ZA","translation":""}}',
			7 => '[{"language":"af-ZA","translation":"  \\n "}]',
			8 => '[{"language":"en-GB","translation":"Instructions for af-ZA"}]',
			9 => '[{"language":"af-ZA","translation":"Vertaling"}]',
			10 => '[{"language":"af-ZA","translation":"0"}]',
			11 => '[{"language":"en-GB","translation":"English"},{"language":"af-ZA","translation":"Afrikaans"}]',
			12 => '[{"language":"af-ZA-extra","translation":"Other"}]',
			13 => '[{"language":"af-ZA","translation":null},{"language":[],"translation":"text"},{"language":"  ","translation":"text"},"bad"]',
			14 => '[{"language":"af-ZA","translation":"Valid"},{"language":"af-ZA","translation":""}]',
			15 => '"scalar"',
			16 => '[{"language":"fr-FR","translation":"Unpublished"}]',
		];
		$rows = [];
		foreach ($values as $id => $value)
		{
			$rows[] = (object) ['id' => $id, 'translation' => $value];
		}
		$database->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator($rows));
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertSame($expected, FilterHelper::translations($language, $translated));
	}

	/**
	 * Supply exact sets for individual, combined, missing and empty translations.
	 *
	 * @return  array<string, array{string, bool, array|null}>
	 * @since   6.2.1
	 */
	public static function translationSelections(): array
	{
		return [
			'translated exact tag' => ['af-ZA', true, [9, 10, 11, 14]],
			'untranslated exact tag' => ['af-ZA', false, [1, 2, 3, 4, 5, 6, 7, 8, 12, 13, 15, 16]],
			'English with incidental language tag' => ['en-GB', true, [8, 11]],
			'all published languages' => ['all', true, [11]],
			'no usable translations in any language' => ['none', false, [1, 2, 3, 4, 5, 6, 7, 13, 15]],
			'no matching language' => ['de-DE', true, null],
		];
	}

	/**
	 * A stale all-language selection cannot turn into an unfiltered result.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testAllLanguagesReturnsNoMatchesWithoutPublishedLanguages(): void
	{
		[$database] = $this->database(0);
		$database->expects($this->never())->method('getIterator');
		$this->setJoomlaFactoryProperty('database', $database);

		$this->assertNull(FilterHelper::translations('all'));
	}

	/**
	 * Reject incompatible sentinel selections before touching the database.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testInvalidTranslationSelectionsReturnNoMatches(): void
	{
		$this->assertNull(FilterHelper::translations(''));
		$this->assertNull(FilterHelper::translations('none'));
		$this->assertNull(FilterHelper::translations('all', false));
	}

	/**
	 * Reject unknown dynamic link methods without invoking a database boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLinkedRejectsUnknownRelationshipMethod(): void
	{
		$this->assertNull(FilterHelper::linked('guid-one', 'unknown_relationship'));
	}

	/**
	 * Build a fluent database/query pair with deterministic row cardinality.
	 *
	 * @param   int  $rows  Reported row count.
	 *
	 * @return  array{0: DatabaseDriver, 1: DatabaseQuery}  Database and query mocks.
	 * @since   6.1.6
	 */
	private function database(int $rows): array
	{
		$query = $this->createStub(DatabaseQuery::class);

		foreach (['select', 'from', 'where', 'order', 'setLimit', 'bind'] as $method)
		{
			$query->method($method)->willReturnSelf();
		}

		$database = $this->createMock(DatabaseDriver::class);
		$database->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$database->method('quoteName')->willReturnCallback(
			static function (mixed $name): mixed
			{
				if (is_array($name))
				{
					return array_map(static fn (string $item): string => '`' . $item . '`', $name);
				}

				return '`' . $name . '`';
			}
		);
		$database->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);
		$database->expects($this->once())->method('setQuery')->with($query)->willReturnSelf();
		$database->expects($this->once())->method('execute')->willReturn(true);
		$database->expects($this->once())->method('getNumRows')->willReturn($rows);

		return [$database, $query];
	}
}
