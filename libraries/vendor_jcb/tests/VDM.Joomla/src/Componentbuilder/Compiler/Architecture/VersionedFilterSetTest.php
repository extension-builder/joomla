<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Adminview\DefaultOrdering;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Filter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Lists;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Sort;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ViewsDefaultOrdering;
use VDM\Joomla\Componentbuilder\Compiler\Field\DatabaseName;
use VDM\Joomla\Componentbuilder\Compiler\Registry;


/**
 * The filter fields a generated list view is searched and ordered by.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedFilterSetTest extends ArchitectureTestCase
{
	/**
	 * Every filter a modern list view is given, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SET_MODERN = <<<'GEN'
	<fields name="filter">
		<field
			type="text"
			name="search"
			inputmode="search"
			label="COM_DEMO_FILTER_SEARCH"
			description="COM_DEMO_FILTER_SEARCH_DEMOS"
			hint="JSEARCH_FILTER"
		/>
		<field
			type="status"
			name="published"
			label="COM_DEMO_FILTER_PUBLISHED"
			description="COM_DEMO_FILTER_PUBLISHED_DEMOS"
			class="js-select-submit-on-change"
		>
			<option value="">JOPTION_SELECT_PUBLISHED</option>
		</field>
		<field
			type="category"
			name="category_id"
			label="COM_DEMO_CATEGORY"
			description="JOPTION_FILTER_CATEGORY_DESC"
			multiple="true"
			class="js-select-submit-on-change"
			extension="com_demo"
			layout="joomla.form.field.list-fancy-select"
			published="0,1,2"
		/>
		<field
			type="accesslevel"
			name="access"
			label="JGRID_HEADING_ACCESS"
			hint="JOPTION_SELECT_ACCESS"
			multiple="true"
			class="js-select-submit-on-change"
			layout="joomla.form.field.list-fancy-select"
		/>
		<field
			type="demoStatus"
			name="status"
			label="COM_DEMO_STATUS"
			multiple="false"
			class="js-select-submit-on-change"
		/>
		<field
			type="demoOwner"
			name="owner"
			label="COM_DEMO_OWNER"
			layout="joomla.form.field.list-fancy-select"
			multiple="true"
			hint="COM_DEMO_SELECT_OWNER"
			class="js-select-submit-on-change"
		/>
		<field
			type="demoPick"
			sql_title_table="#__demo_pick"
			sql_title_column="title"
			sql_title_key="id"
			urlSelect="index.php?option=com_demo&view=picks&layout=modal"
			hint="COM_DEMO_SELECT_A_PICK"
			titleSelect="COM_DEMO_PICK_TITLE"
			iconSelect="pick"
			select="true"
			edit="false"
			clear="true"
			onchange="form.submit()"
			name="picked"
			label="COM_DEMO_PICKED"
			multiple="false"
			class="js-select-submit-on-change"
		/>
		<input type="hidden" name="form_submited" value="1"/>
	</fields>
GEN;

	/**
	 * Every filter a Joomla 3 list view is given, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SET_J3 = <<<'GEN'
	<fields name="filter">
		<field
			type="text"
			name="search"
			inputmode="search"
			label="COM_DEMO_FILTER_SEARCH"
			description="COM_DEMO_FILTER_SEARCH_DEMOS"
			hint="JSEARCH_FILTER"
		/>
		<field
			type="status"
			name="published"
			label="COM_DEMO_FILTER_PUBLISHED"
			description="COM_DEMO_FILTER_PUBLISHED_DEMOS"
			onchange="this.form.submit();"
		>
			<option value="">JOPTION_SELECT_PUBLISHED</option>
		</field>
		<field
			type="category"
			name="category_id"
			label="COM_DEMO_CATEGORY"
			description="JOPTION_FILTER_CATEGORY_DESC"
			multiple="true"
			class="multipleCategories"
			extension="com_demo"
			onchange="this.form.submit();"
			published="0,1,2"
		/>
		<field
			type="accesslevel"
			name="access"
			label="JFIELD_ACCESS_LABEL"
			description="JFIELD_ACCESS_DESC"
			multiple="true"
			class="multipleAccessLevels"
			onchange="this.form.submit();"
		/>
		<field
			type="demoStatus"
			name="status"
			label="COM_DEMO_STATUS"
			multiple="false"
			onchange="this.form.submit();"
		/>
		<field
			type="demoOwner"
			name="owner"
			label="COM_DEMO_OWNER"
			class="multipleDemoOwner"
			multiple="true"
			onchange="this.form.submit();"
		/>
		<field
			type="demoPick"
			name="picked"
			label="COM_DEMO_PICKED"
			multiple="false"
			onchange="this.form.submit();"
		/>
		<input type="hidden" name="form_submited" value="1"/>
	</fields>
GEN;

	/**
	 * The filters of a view that declared none, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SET_BARE = <<<'GEN'
	<fields name="filter">
		<field
			type="text"
			name="search"
			inputmode="search"
			label="COM_DEMO_FILTER_SEARCH"
			description="COM_DEMO_FILTER_SEARCH_DEMOS"
			hint="JSEARCH_FILTER"
		/>
		<input type="hidden" name="form_submited" value="1"/>
	</fields>
GEN;

	/**
	 * The ordering of a modern list view, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIST_MODERN = <<<'GEN'
	<fields name="list">
		<field
			name="fullordering"
			type="list"
			label="JGLOBAL_SORT_BY"
			class="js-select-submit-on-change"
			default="a.id DESC"
			validate="options"
		>
			<option value="">JGLOBAL_SORT_BY</option>
			<option value="a.ordering ASC">JGRID_HEADING_ORDERING_ASC</option>
			<option value="a.ordering DESC">JGRID_HEADING_ORDERING_DESC</option>
			<option value="a.published ASC">JSTATUS_ASC</option>
			<option value="a.published DESC">JSTATUS_DESC</option>
			<option value="a.name ASC">COM_DEMO_NAME_ASC</option>
			<option value="a.name DESC">COM_DEMO_NAME_DESC</option>
			<option value="category_title ASC">COM_DEMO_CAT_ASC</option>
			<option value="category_title DESC">COM_DEMO_CAT_DESC</option>
			<option value="#__demo_owner.name ASC">COM_DEMO_OWNER_ASC</option>
			<option value="#__demo_owner.name DESC">COM_DEMO_OWNER_DESC</option>
			<option value="a.id ASC">JGRID_HEADING_ID_ASC</option>
			<option value="a.id DESC">JGRID_HEADING_ID_DESC</option>
		</field>

		<field
			name="limit"
			type="limitbox"
			label="JGLOBAL_LIST_LIMIT"
			default="25"
			class="js-select-submit-on-change"
		/>
	</fields>
GEN;

	/**
	 * The ordering of a Joomla 3 list view, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIST_J3 = <<<'GEN'
	<fields name="list">
		<field
			name="fullordering"
			type="list"
			label="COM_CONTENT_LIST_FULL_ORDERING"
			description="COM_CONTENT_LIST_FULL_ORDERING_DESC"
			onchange="this.form.submit();"
			default="a.id DESC"
			validate="options"
		>
			<option value="">JGLOBAL_SORT_BY</option>
			<option value="a.ordering ASC">JGRID_HEADING_ORDERING_ASC</option>
			<option value="a.ordering DESC">JGRID_HEADING_ORDERING_DESC</option>
			<option value="a.published ASC">JSTATUS_ASC</option>
			<option value="a.published DESC">JSTATUS_DESC</option>
			<option value="a.name ASC">COM_DEMO_NAME_ASC</option>
			<option value="a.name DESC">COM_DEMO_NAME_DESC</option>
			<option value="category_title ASC">COM_DEMO_CAT_ASC</option>
			<option value="category_title DESC">COM_DEMO_CAT_DESC</option>
			<option value="#__demo_owner.name ASC">COM_DEMO_OWNER_ASC</option>
			<option value="#__demo_owner.name DESC">COM_DEMO_OWNER_DESC</option>
			<option value="a.id ASC">JGRID_HEADING_ID_ASC</option>
			<option value="a.id DESC">JGRID_HEADING_ID_DESC</option>
		</field>

		<field
			name="limit"
			type="limitbox"
			label="COM_CONTENT_LIST_LIMIT"
			description="COM_CONTENT_LIST_LIMIT_DESC"
			class="input-mini"
			default="25"
			onchange="this.form.submit();"
		/>
	</fields>
GEN;

	/**
	 * The ordering of a view that declared none, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIST_BARE = <<<'GEN'
	<fields name="list">
		<field
			name="fullordering"
			type="list"
			label="JGLOBAL_SORT_BY"
			class="js-select-submit-on-change"
			default="a.id DESC"
			validate="options"
		>
			<option value="">JGLOBAL_SORT_BY</option>
			<option value="a.ordering ASC">JGRID_HEADING_ORDERING_ASC</option>
			<option value="a.ordering DESC">JGRID_HEADING_ORDERING_DESC</option>
			<option value="a.id ASC">JGRID_HEADING_ID_ASC</option>
			<option value="a.id DESC">JGRID_HEADING_ID_DESC</option>
		</field>

		<field
			name="limit"
			type="limitbox"
			label="JGLOBAL_LIST_LIMIT"
			default="25"
			class="js-select-submit-on-change"
		/>
	</fields>
GEN;

	/**
	 * The targets that share one filter set.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		return [
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * A modern list view is given the filters the component declared for it.
	 *
	 * @param   string  $version  The target being built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernListViewIsGivenItsFilters(string $version): void
	{
		$subject = $this->filterSet($version, $this->everything());
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(self::EXPECTED_SET_MODERN, $subject->get($single, $list));
	}

	/**
	 * A Joomla 3 list view is given the same filters, styled its own way.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJoomlaThreeListViewIsGivenItsFilters(): void
	{
		$subject = $this->filterSet('JoomlaThree', $this->everything());
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(self::EXPECTED_SET_J3, $subject->get($single, $list));
	}

	/**
	 * The search field and its two strings are all a bare view is given.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testABareViewIsGivenOnlyItsSearchField(): void
	{
		$named = new FieldNames();
		$named->set('demo.published', 'published');

		$subject = $this->filterSet('JoomlaSix', [
			'fieldnames' => $named,
			'category' => new Category(),
			'accessswitch' => new AccessSwitch(),
			'filter' => new Filter()
		]);
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(self::EXPECTED_SET_BARE, $subject->get($single, $list));
		$this->assertSame(
			[
				'COM_DEMO_FILTER_SEARCH' => 'Searchdemos',
				'COM_DEMO_FILTER_SEARCH_DEMOS'
					=> 'Search the demo items. Prefix with ID: to search for an item by ID.'
			],
			$this->language()->getTarget('admin')
		);
	}

	/**
	 * A view that was not given the searchable filter is given no fields.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutTheSearchableFilterIsGivenNoFields(): void
	{
		$type = new AdminFilterType();
		$type->set('demos', 1);

		$subject = $this->filterSet('JoomlaSix', ['adminfiltertype' => $type]);
		$single = 'demo';
		$list = 'demos';

		$this->assertSame('', $subject->get($single, $list));
	}

	/**
	 * Joomla 3 keeps the css class of every filter it wrote.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeKeepsTheCssClassOfEveryFilter(): void
	{
		$filters = $this->declaredFilters();
		$subject = $this->filterSet('JoomlaThree', $this->everything($filters));
		$single = 'demo';
		$list = 'demos';

		$subject->get($single, $list);

		$this->assertSame(
			['DemoStatus', 'DemoOwner', 'DemoPick'],
			[
				$filters->get('demos.0.class'),
				$filters->get('demos.1.class'),
				$filters->get('demos.2.class')
			]
		);
	}

	/**
	 * A modern list view is ordered by what the component said it can be.
	 *
	 * @param   string  $version  The target being built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernListViewIsOrderedByWhatItCanBe(string $version): void
	{
		$subject = $this->filterListSet($version, ['sort' => $this->declaredSorts()]);
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(self::EXPECTED_LIST_MODERN, $subject->get($single, $list));
	}

	/**
	 * A Joomla 3 list view is ordered the same way, labelled its own way.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJoomlaThreeListViewIsOrderedByWhatItCanBe(): void
	{
		$subject = $this->filterListSet('JoomlaThree', ['sort' => $this->declaredSorts()]);
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(self::EXPECTED_LIST_J3, $subject->get($single, $list));
	}

	/**
	 * A view that can be ordered by nothing still orders by its own columns.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewOrderedByNothingStillOrdersByItsOwnColumns(): void
	{
		$named = new FieldNames();
		$named->set('demo.published', 'published');

		$subject = $this->filterListSet('JoomlaSix', [
			'fieldnames' => $named,
			'sort' => new Sort()
		]);
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(self::EXPECTED_LIST_BARE, $subject->get($single, $list));
	}

	/**
	 * A view without the searchable filter is given no ordering fields.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutTheSearchableFilterIsGivenNoOrdering(): void
	{
		$type = new AdminFilterType();
		$type->set('demos', 1);

		$subject = $this->filterListSet('JoomlaSix', ['adminfiltertype' => $type]);
		$single = 'demo';
		$list = 'demos';

		$this->assertSame('', $subject->get($single, $list));
	}

	/**
	 * The filter set renderer of one target.
	 *
	 * @param   string  $version    The target being built.
	 * @param   array   $overrides  What the compiler collected.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function filterSet(string $version, array $overrides = []): object
	{
		return $this->renderer(
			$this->targetClass($version, 'AdminViews\\FilterSet', ['JoomlaThree']),
			$overrides + ['adminfiltertype' => $this->searchable()]
		);
	}

	/**
	 * The list ordering renderer of one target.
	 *
	 * @param   string  $version    The target being built.
	 * @param   array   $overrides  What the compiler collected.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function filterListSet(string $version, array $overrides = []): object
	{
		return $this->renderer(
			$this->targetClass($version, 'AdminViews\\FilterListSet', ['JoomlaThree']),
			$overrides + [
				'adminfiltertype' => $this->searchable(),
				'fieldnames' => new FieldNames(),
				'defaultordering' => $this->ordering()
			]
		);
	}

	/**
	 * A registry that says the demos list view is searchable.
	 *
	 * @return  AdminFilterType
	 * @since   6.1.7
	 */
	private function searchable(): AdminFilterType
	{
		$type = new AdminFilterType();
		$type->set('demos', 2);

		return $type;
	}

	/**
	 * Everything the compiler can hand the filter set.
	 *
	 * @param   Filter|null  $filters  The filters, when the caller keeps them.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function everything(?Filter $filters = null): array
	{
		$category = new Category();
		$category->set('demos.extension', 'com_demo');
		$category->set('demos.filter', 1);
		$category->set('demos.name', 'COM_DEMO_CATEGORY');

		$access = new AccessSwitch();
		$access->set('demo', true);

		return [
			'fieldnames' => new FieldNames(),
			'category' => $category,
			'accessswitch' => $access,
			'filter' => $filters ?? $this->declaredFilters()
		];
	}

	/**
	 * One filter of each shape the compiler can declare.
	 *
	 * @return  Filter
	 * @since   6.1.7
	 */
	private function declaredFilters(): Filter
	{
		$filter = new Filter();
		$filter->set('demos.0', [
			'type' => 'list', 'filter_type' => 'demoStatus', 'code' => 'status',
			'label' => 'COM_DEMO_STATUS', 'multi' => 1, 'custom' => []
		]);
		$filter->set('demos.1', [
			'type' => 'demoOwner', 'filter_type' => 'demoOwner', 'code' => 'owner',
			'label' => 'COM_DEMO_OWNER', 'multi' => 2,
			'lang_select' => 'COM_DEMO_SELECT_OWNER',
			'custom' => ['db' => '#__demo_owner', 'text' => 'name', 'id' => 'id']
		]);
		$filter->set('demos.2', [
			'type' => 'demoPick', 'filter_type' => 'demoPick', 'code' => 'picked',
			'label' => 'COM_DEMO_PICKED', 'multi' => 1,
			'custom' => [
				'db' => '#__demo_pick', 'text' => 'title', 'id' => 'id',
				'table' => '#__demo_pick', 'modal_select' => 1,
				'urlSelect' => 'index.php?option=com_demo&view=picks&layout=modal',
				'hint' => 'COM_DEMO_SELECT_A_PICK', 'titleSelect' => 'COM_DEMO_PICK_TITLE',
				'iconSelect' => 'pick'
			]
		]);

		return $filter;
	}

	/**
	 * One sort of each shape, and one the list already orders by.
	 *
	 * @return  Sort
	 * @since   6.1.7
	 */
	private function declaredSorts(): Sort
	{
		$sort = new Sort();
		$sort->set('demos.0', ['code' => 'name', 'type' => 'text', 'custom' => [],
			'lang_asc' => 'COM_DEMO_NAME_ASC', 'lang_desc' => 'COM_DEMO_NAME_DESC']);
		$sort->set('demos.1', ['code' => 'catid', 'type' => 'category', 'custom' => [],
			'lang_asc' => 'COM_DEMO_CAT_ASC', 'lang_desc' => 'COM_DEMO_CAT_DESC']);
		$sort->set('demos.2', ['code' => 'owner', 'type' => 'list',
			'custom' => ['db' => '#__demo_owner', 'text' => 'name'],
			'lang_asc' => 'COM_DEMO_OWNER_ASC', 'lang_desc' => 'COM_DEMO_OWNER_DESC']);
		$sort->set('demos.3', ['code' => 'ordering', 'type' => 'text', 'custom' => [],
			'lang_asc' => 'NEVER_ASC', 'lang_desc' => 'NEVER_DESC']);

		return $sort;
	}

	/**
	 * The ordering of a view the component gave no ordering of its own.
	 *
	 * @return  DefaultOrdering
	 * @since   6.1.7
	 */
	private function ordering(): DefaultOrdering
	{
		return new DefaultOrdering(
			new ViewsDefaultOrdering(),
			new DatabaseName(new Lists(), new Registry())
		);
	}
}
