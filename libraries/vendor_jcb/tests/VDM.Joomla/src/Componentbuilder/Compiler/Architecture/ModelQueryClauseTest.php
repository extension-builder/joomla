<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use Joomla\DI\Container;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\CustomQuery;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\FilterQuery;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\SearchQuery;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomList;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Creator\CustomFieldTypeFileInterface;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Filter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Search;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel;


/**
 * Generated list model search and filter clause contracts.
 *
 * Neither clause differs between Joomla targets, so each is one class with
 * no target variants at all.
 *
 * @since  6.1.7
 */
#[CoversClass(SearchQuery::class)]
#[CoversClass(FilterQuery::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelQueryClauseTest extends ArchitectureTestCase
{
	/**
	 * A view with nothing searchable produces no search clause.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutSearchableFieldsProducesNothing(): void
	{
		$this->assertSame('', (new SearchQuery(new Search(), new CustomField()))->get('articles'));
	}

	/**
	 * The first searchable field opens the clause and the rest are ORed on.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEverySearchableFieldIsOredIntoOneClause(): void
	{
		$search = new Search();
		$search->set('articles', [
			['type' => 'text', 'code' => 'title', 'custom' => null, 'list' => 0],
			['type' => 'text', 'code' => 'alias', 'custom' => null, 'list' => 0],
		]);

		$code = (new SearchQuery($search, $this->customFields()))->get('articles');

		$this->assertStringContainsString("a.title LIKE '.\$search.'", $code);
		$this->assertStringContainsString("OR a.alias LIKE '.\$search.'", $code);
	}

	/**
	 * A joined custom field also searches the text column it displays.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJoinedCustomFieldAlsoSearchesItsTextColumn(): void
	{
		$search = new Search();
		$search->set('articles', [
			[
				'type' => 'user',
				'code' => 'created_by',
				'custom' => ['db' => 'g', 'text' => 'name'],
				'list' => 1,
			],
		]);

		$code = (new SearchQuery($search, $this->customFields()))->get('articles');

		$this->assertStringContainsString("a.created_by LIKE '.\$search.'", $code);
		$this->assertStringContainsString("OR g.name LIKE '.\$search.'", $code);
	}

	/**
	 * A custom field that is not joined into the list is not searched.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomFieldOutsideTheListIsNotSearched(): void
	{
		$search = new Search();
		$search->set('articles', [
			[
				'type' => 'user',
				'code' => 'created_by',
				'custom' => ['db' => 'g', 'text' => 'name'],
				'list' => 0,
			],
		]);

		$code = (new SearchQuery($search, $this->customFields()))->get('articles');

		$this->assertStringContainsString("a.created_by LIKE '.\$search.'", $code);
		$this->assertStringNotContainsString('g.name', $code);
	}

	/**
	 * The provider shares both registries with the logical search renderer alias.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testProviderWiresSharedCustomFieldDefinitions(): void
	{
		$search = new Search();
		$search->set('articles', [['code' => 'created_by', 'list' => 1]]);
		$customfield = $this->customFields();
		$container = new Container();
		$container->share('Compiler.Builder.Search', $search);
		$container->share('Compiler.Builder.Custom.Field', $customfield);
		(new ArchitectureModel())->register($container);
		$subject = $container->get('Architecture.Model.SearchQuery');

		$this->assertSame($subject, $container->get(SearchQuery::class));
		$this->assertStringContainsString(' OR g.name LIKE ', $subject->get('articles'));
		$customfield->set('articles', []);
		$this->assertStringNotContainsString(' OR g.name LIKE ', $subject->get('articles'));
		$this->assertStringContainsString('a.created_by LIKE ', $subject->get('articles'));
	}

	/**
	 * Folder selectors remain searchable through their stored local values.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFolderSelectorsKeepLocalSearchWithoutPhantomJoins(): void
	{
		$search = new Search();
		$fields = [
			['type' => 'text', 'code' => 'title', 'custom' => null, 'list' => 1],
			['type' => 'list', 'code' => 'type', 'custom' => null, 'list' => 1],
			['type' => 'list', 'code' => 'location', 'custom' => null, 'list' => 1],
			['type' => 'adminviews', 'code' => 'admin_view', 'custom' => ['db' => 'g', 'text' => '', 'table' => ''], 'list' => 1],
			['type' => 'siteviews', 'code' => 'site_view', 'custom' => ['db' => 'h', 'text' => '', 'table' => ''], 'list' => 1],
		];
		$search->set('articles', $fields);
		$customfield = new CustomField();
		$customfield->set('articles', array_map(
			static fn (array $field): array => $field + ['method' => 0], array_slice($fields, 3)
		));
		$customlist = new CustomList();
		$customlist->set('article.admin_view', true);
		$customlist->set('article.site_view', true);
		$joins = new CustomQuery($customfield, $customlist, $this->createStub(CustomFieldTypeFileInterface::class));
		$code = (new SearchQuery($search, $customfield))->get('articles');

		$this->assertSame('', $joins->get('articles', 'article'));
		$this->assertStringContainsString(
			"(a.title LIKE '.\$search.' OR a.type LIKE '.\$search.' OR a.location LIKE '.\$search.'"
			. " OR a.admin_view LIKE '.\$search.' OR a.site_view LIKE '.\$search.')", $code
		);
		$this->assertStringNotContainsString(' OR g.', $code);
		$this->assertStringNotContainsString(' OR h.', $code);
	}

	/**
	 * Incomplete metadata and nonjoined storage modes never add alias predicates.
	 *
	 * @param   array  $changes  Changes to otherwise valid custom-field metadata.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('unjoinedDefinitions')]
	public function testUnjoinedDefinitionsKeepOnlyTheirLocalSearch(array $changes): void
	{
		$customfield = $this->customFields();
		$definition = array_replace_recursive($customfield->get('articles')[0], $changes);
		$customfield->set('articles', [$definition]);
		$search = new Search();
		$search->set('articles', [['code' => 'created_by', 'custom' => $definition['custom'], 'list' => 1]]);
		$code = (new SearchQuery($search, $customfield))->get('articles');

		$this->assertStringContainsString("a.created_by LIKE '.\$search.'", $code);
		$this->assertStringNotContainsString(' OR ', $code);
	}

	/**
	 * Invalid and nonrelational custom metadata requiring no display-column search.
	 *
	 * @return  array<string, array{array}>
	 * @since   6.2.0
	 */
	public static function unjoinedDefinitions(): array
	{
		return [
			'no table' => [['custom' => ['table' => '']]],
			'no alias' => [['custom' => ['db' => '']]],
			'no display column' => [['custom' => ['text' => '']]],
			'no foreign key' => [['custom' => ['id' => '']]],
			'encoded storage' => [['method' => 1]],
			'custom storage' => [['method' => 6]],
		];
	}

	/**
	 * Execute generated search code with ID, empty, quote and wildcard input.
	 *
	 * @param   string       $term       Search filter value.
	 * @param   string|null  $predicate  Expected additional query predicate.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('searchInputs')]
	public function testGeneratedSearchPreservesEscapingAndExistingAccessPredicates(
		string $term, ?string $predicate
	): void
	{
		$search = new Search();
		$search->set('articles', [['code' => 'created_by', 'custom' => ['db' => 'g', 'text' => 'name'], 'list' => 1]]);
		$customfield = $this->customFields();
		$customlist = new CustomList();
		$customlist->set('article.created_by', true);
		$joins = new CustomQuery($customfield, $customlist, $this->createStub(CustomFieldTypeFileInterface::class));
		$this->assertStringContainsString("\$db->quoteName('#__users', 'g')", $joins->get('articles', 'article'));
		$code = (new SearchQuery($search, $customfield))->get('articles');
		$clauses = ['a.access IN (1,2)'];
		$query = $this->createMock(QueryInterface::class);
		$query->expects($predicate === null ? $this->never() : $this->once())
			->method('where')->willReturnCallback(
				static function (string $clause) use (&$clauses, $query): QueryInterface
				{
					$clauses[] = $clause;

					return $query;
				}
			);
		$database = $this->createStub(DatabaseInterface::class);
		$database->method('escape')->willReturnCallback(static fn (string $value): string => str_replace("'", "''", $value));
		$database->method('quote')->willReturnCallback(static fn (string $value): string => "'" . $value . "'");
		$state = new class($term)
		{
			/**
			 * Search input supplied to generated model code.
			 *
			 * @var    string
			 * @since  6.2.0
			 */
			private string $term;

			/**
			 * Capture the model filter value.
			 *
			 * @param   string  $term  Search filter.
			 *
			 * @since   6.2.0
			 */
			public function __construct(string $term)
			{
				$this->term = $term;
			}

			/**
			 * Supply the state requested by generated model code.
			 *
			 * @param   string  $key  Model state key.
			 *
			 * @return  string
			 * @since   6.2.0
			 */
			public function getState(string $key): string
			{
				return $this->term;
			}
		};
		$execute = eval('return function ($db, $query): void {' . $code . '};');
		$execute->call($state, $database, $query);

		$this->assertSame($predicate === null ? ['a.access IN (1,2)'] : ['a.access IN (1,2)', $predicate], $clauses);
	}

	/**
	 * Search inputs retain the existing query interpretation and quoting.
	 *
	 * @return  array<string, array{string, string|null}>
	 * @since   6.2.0
	 */
	public static function searchInputs(): array
	{
		return [
			'empty' => ['', null],
			'numeric identifier' => ['id:42', 'a.id = 42'],
			'case insensitive identifier' => ['ID:42 suffix', 'a.id = 42'],
			'ordinary text' => ['Alice', "(a.created_by LIKE '%Alice%' OR g.name LIKE '%Alice%')"],
			'no matching text' => ['unmatched', "(a.created_by LIKE '%unmatched%' OR g.name LIKE '%unmatched%')"],
			'quote' => ["O'Brien", "(a.created_by LIKE '%O''Brien%' OR g.name LIKE '%O''Brien%')"],
			'wildcards' => ['A_%', "(a.created_by LIKE '%A_%%' OR g.name LIKE '%A_%%')"],
		];
	}

	/**
	 * Build the same relation metadata consumed by CustomQuery.
	 *
	 * @return  CustomField
	 * @since   6.2.0
	 */
	private function customFields(): CustomField
	{
		$customfield = new CustomField();
		$customfield->set('articles', [[
			'code' => 'created_by',
			'method' => 0,
			'custom' => ['table' => '#__users', 'db' => 'g', 'text' => 'name', 'id' => 'id'],
		]]);

		return $customfield;
	}

	/**
	 * A view with nothing to filter produces no filter clause.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutFiltersProducesNothing(): void
	{
		$this->assertSame('', $this->filterQuery(new Filter())->get('articles'));
	}

	/**
	 * A plain field filter reads its state and guards the value type.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAPlainFilterReadsItsStateAndGuardsTheValue(): void
	{
		$code = $this->filter([['type' => 'text', 'code' => 'status']]);

		$this->assertStringContainsString('// Filter by Status.', $code);
		$this->assertStringContainsString(
			"\$_status = \$this->getState('filter.status');", $code
		);
		$this->assertStringContainsString('if (is_numeric($_status))', $code);
		$this->assertStringContainsString('if (is_float($_status))', $code);
	}

	/**
	 * A category filter is left to the list query itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACategoryFilterIsSkipped(): void
	{
		$code = $this->filter([['type' => 'category', 'code' => 'catid']]);

		$this->assertSame('', $code);
	}

	/**
	 * A multi select top bar filter accepts a list of values.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATopBarMultiSelectFilterAcceptsAList(): void
	{
		// 2 is the top bar filter type, and multi 2 turns multi select on
		$adminfiltertype = new AdminFilterType();
		$adminfiltertype->set('articles', 2);

		$code = $this->filter(
			[['type' => 'text', 'code' => 'status', 'multi' => 2]],
			$adminfiltertype
		);

		$this->assertStringContainsString('// Filter by Status.', $code);
		// a list of values is secured item by item and folded into an IN test
		$this->assertStringContainsString('// Filter by the Status Array.', $code);
		$this->assertStringContainsString(
			"\$query->where('a.status IN (' . implode(',', \$_status) . ')');",
			$code
		);
	}

	/**
	 * Without the top bar filter type a multi field stays single valued.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithoutTheTopBarTypeAMultiFieldStaysSingle(): void
	{
		// the default filter type is 1, the sidebar, which has no multi select
		$code = $this->filter([['type' => 'text', 'code' => 'status', 'multi' => 2]]);

		$this->assertStringContainsString('if (is_numeric($_status))', $code);
		$this->assertStringNotContainsString('// Filter by the Status Array.', $code);
	}

	/**
	 * Every filtered field contributes its own clause.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryFilteredFieldContributesItsOwnClause(): void
	{
		$code = $this->filter([
			['type' => 'text', 'code' => 'status'],
			['type' => 'text', 'code' => 'kind'],
		]);

		$this->assertStringContainsString('// Filter by Status.', $code);
		$this->assertStringContainsString('// Filter by Kind.', $code);
	}

	/**
	 * Build the filter clause of one view.
	 *
	 * @param   array                 $filters          The filter definitions.
	 * @param   AdminFilterType|null  $adminfiltertype  The filter type registry.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function filter(array $filters, ?AdminFilterType $adminfiltertype = null): string
	{
		$filter = new Filter();
		$filter->set('articles', $filters);

		return $this->filterQuery($filter, $adminfiltertype)->get('articles');
	}

	/**
	 * Create the filter clause builder with real registries.
	 *
	 * @param   Filter                $filter           The filter registry.
	 * @param   AdminFilterType|null  $adminfiltertype  The filter type registry.
	 *
	 * @return  FilterQuery
	 * @since   6.1.7
	 */
	private function filterQuery(Filter $filter,
		?AdminFilterType $adminfiltertype = null): FilterQuery
	{
		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		return new FilterQuery(
			$filter,
			$adminfiltertype ?? new AdminFilterType(),
			$contentone
		);
	}
}
