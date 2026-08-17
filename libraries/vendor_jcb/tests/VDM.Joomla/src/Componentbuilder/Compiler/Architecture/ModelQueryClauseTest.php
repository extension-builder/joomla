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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\FilterQuery;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\SearchQuery;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Filter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Search;


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
		$this->assertSame('', (new SearchQuery(new Search()))->get('articles'));
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

		$code = (new SearchQuery($search))->get('articles');

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

		$code = (new SearchQuery($search))->get('articles');

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

		$code = (new SearchQuery($search))->get('articles');

		$this->assertStringContainsString("a.created_by LIKE '.\$search.'", $code);
		$this->assertStringNotContainsString('g.name', $code);
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
