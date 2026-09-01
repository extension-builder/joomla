<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Controller;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\DisplayList;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Filter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Sort;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The list state mapping of the list API controller.
 *
 * @since 6.1.7
 */
#[CoversClass(DisplayList::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class DisplayListTest extends ArchitectureTestCase
{
	private const EXPECTED_BARE = <<<'GEN'

		// Map the request filters onto the list model state.
		$filters = $this->input->get('filter', [], 'array');
		$this->modelState->set('filter.search', $this->cleanFilter($filters['search'] ?? ''));
		$this->modelState->set('filter.published', $this->cleanFilter($filters['published'] ?? ''));

		// Map the requested ordering onto the list model state.
		$list = $this->input->get('list', [], 'array');
		$ordering = [
			'id' => 'a.id',
			'published' => 'a.published',
			'ordering' => 'a.ordering',
			'created_by' => 'a.created_by',
			'modified_by' => 'a.modified_by',
		];

		if (isset($list['ordering'], $ordering[$list['ordering']]))
		{
			$this->modelState->set('list.ordering', $ordering[$list['ordering']]);
		}

		if (isset($list['direction']) && in_array(strtolower((string) $list['direction']), ['asc', 'desc'], true))
		{
			$this->modelState->set('list.direction', strtolower((string) $list['direction']));
		}

		return parent::displayList();
GEN;

	private const EXPECTED_STATUS_FILTER = <<<'GEN'

		if (isset($filters['status']))
		{
			$this->modelState->set('filter.status', $this->cleanFilter($filters['status']));
		}
GEN;

	private const EXPECTED_ORDERING = <<<'GEN'
		$ordering = [
			'id' => 'a.id',
			'published' => 'a.published',
			'ordering' => 'a.ordering',
			'created_by' => 'a.created_by',
			'modified_by' => 'a.modified_by',
			'access' => 'a.access',
			'name' => 'a.name',
			'author' => 'g.name',
			'catid' => 'category_title',
		];
GEN;

	public function testAViewWithoutFiltersMapsSearchPublishedAndTheDefaultOrdering(): void
	{
		$subject = $this->renderer(DisplayList::class);

		$this->assertSame(self::EXPECTED_BARE, $subject->get('demo', 'demos'));
	}

	public function testEveryFilterOfTheViewIsMappedAndCategoriesAreReadById(): void
	{
		$code = $this->subject()->get('demo', 'demos');

		$this->assertStringContainsString("if (isset(\$filters['access']))", $code);
		$this->assertStringContainsString("\$this->modelState->set('filter.access', \$this->cleanFilter(\$filters['access']));", $code);
		$this->assertStringContainsString("if (isset(\$filters['category_id']))", $code);
		$this->assertStringContainsString(self::EXPECTED_STATUS_FILTER, $code);
		$this->assertStringNotContainsString("filter.catid", $code);
		$this->assertStringNotContainsString("isset(\$filters['search'])", $code);
	}

	public function testTheOrderingOffersWhatTheAdminSortFieldOffers(): void
	{
		$code = $this->subject()->get('demo', 'demos');

		$this->assertStringContainsString(self::EXPECTED_ORDERING, $code);
	}

	public function testAnOverriddenAccessFieldIsNotFilteredAsTheDefaultOne(): void
	{
		$names = new FieldNames();
		$names->set('demo.access', 'access');

		$code = $this->subject(['fieldnames' => $names])->get('demo', 'demos');

		$this->assertStringNotContainsString("filter.access", $code);
		$this->assertStringContainsString("'access' => 'a.access',", $code);
	}

	/**
	 * A view with access control, a category, a filter and three ways to sort.
	 *
	 * @param   array  $overrides  Collaborators to replace.
	 *
	 * @return  DisplayList
	 * @since   6.1.7
	 */
	private function subject(array $overrides = []): DisplayList
	{
		$access = new AccessSwitch();
		$access->set('demo', true);

		$category = new Category();
		$category->set('demos', ['code' => 'catid', 'name' => 'category']);

		$filter = new Filter();
		$filter->add('demos', ['type' => 'list', 'code' => 'status', 'multi' => 2], true);
		$filter->add('demos', ['type' => 'category', 'code' => 'catid', 'multi' => 1], true);
		$filter->add('demos', ['type' => 'text', 'code' => 'search', 'multi' => 1], true);

		$sort = new Sort();
		$sort->add('demos', ['type' => 'text', 'code' => 'name'], true);
		$sort->add('demos', ['type' => 'custom', 'code' => 'author', 'custom' => ['db' => 'g', 'text' => 'name']], true);
		$sort->add('demos', ['type' => 'category', 'code' => 'catid'], true);

		return $this->renderer(DisplayList::class, $overrides + [
			'accessswitch' => $access,
			'category' => $category,
			'filter' => $filter,
			'sort' => $sort,
		]);
	}
}
