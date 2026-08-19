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
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\FilterFields;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\PopulateState;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\SortFields;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\StoredId;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Filter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Sort;


/**
 * Generated list model state contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelListStateTest extends ArchitectureTestCase
{
	/**
	 * The stored id method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_STORED_ID = <<<'GEN'
// Compile the store id.
		$id .= ':' . $this->getState('filter.id');
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.ordering');
		$id .= ':' . $this->getState('filter.created_by');
		$id .= ':' . $this->getState('filter.modified_by');
		$id .= ':' . $this->getState('filter.status');
		$id .= ':' . $this->getState('filter.name');
GEN;

	/**
	 * The populate state statements this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_POPULATE_STATE = <<<'GEN'


		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$created_by = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
		$this->setState('filter.created_by', $created_by);

		$created = $this->getUserStateFromRequest($this->context . '.filter.created', 'filter_created');
		$this->setState('filter.created', $created);

		$sorting = $this->getUserStateFromRequest($this->context . '.filter.sorting', 'filter_sorting', 0, 'int');
		$this->setState('filter.sorting', $sorting);

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$status = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status');
		$this->setState('filter.status', $status);

		$name = $this->getUserStateFromRequest($this->context . '.filter.name', 'filter_name');
		$this->setState('filter.name', $name);
GEN;

	/**
	 * The sort fields method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SORT_FIELDS = <<<'GEN'
return array(
			'a.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'a.published' => Text::_('JSTATUS'),
			'a.name' => Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_NAME'),
			'a.id' => Text::_('JGRID_HEADING_ID')
		);
GEN;

	/**
	 * The filter fields array this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_FILTER_FIELDS = <<<'GEN'
'a.id','id',
				'a.published','published',
				'a.access','access',
				'a.ordering','ordering',
				'a.created_by','created_by',
				'a.modified_by','modified_by',
				'a.status','status',
				'a.name','name'
GEN;

	/**
	 * The sort fields method of a view that sorts by nothing of its own,
	 * captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SORT_FIELDS_NONE = <<<'GEN'
return array(
			'a.ordering' => Text::_('JGRID_HEADING_ORDERING'),
			'a.published' => Text::_('JSTATUS'),
			'a.id' => Text::_('JGRID_HEADING_ID')
		);
GEN;

	/**
	 * One filter, as the compiler collected it.
	 *
	 * @param   array  $over  What to say differently about it.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function filter(array $over = []): array
	{
		return array_merge([
			'type' => 'text',
			'code' => 'status',
			'lang' => 'COM_DEMO_STATUS',
			'function' => 'Status',
			'filter_type' => 'list',
			'name' => 'Status',
			'custom' => ['text' => 'name', 'type' => 'list', 'extends' => 'demo'],
		], $over);
	}

	/**
	 * What the compiler knows about the demo list view.
	 *
	 * @param   bool  $withFilter  Whether the view offers a filter.
	 * @param   bool  $withSort    Whether the view offers a sort.
	 * @param   bool  $withAccess  Whether the view carries an access field.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function knowledge(bool $withFilter = true, bool $withSort = true,
		bool $withAccess = true): array
	{
		$adminfiltertype = new AdminFilterType();
		$adminfiltertype->set('demos', 1);

		$filter = new Filter();
		if ($withFilter)
		{
			$filter->set('demos', [$this->filter()]);
		}

		$sort = new Sort();
		if ($withSort)
		{
			$sort->set('demos', [[
				'code' => 'name',
				'lang' => 'COM_DEMO_NAME',
				'name' => 'Name',
				'type' => 'text',
				'custom' => [],
			]]);
		}

		$accessswitch = new AccessSwitch();
		if ($withAccess)
		{
			$accessswitch->set('demo', true);
		}

		return [
			'adminfiltertype' => $adminfiltertype,
			'filter' => $filter,
			'sort' => $sort,
			'accessswitch' => $accessswitch,
			'fieldnames' => new FieldNames(),
		];
	}

	/**
	 * The stored id of a list model folds in every filter the view offers.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheStoredIdFoldsInEveryFilter(): void
	{
		$subject = $this->renderer(StoredId::class, $this->knowledge());
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(self::EXPECTED_STORED_ID, $subject->get($single, $list));
	}

	/**
	 * The populate state reads every filter the view offers off the request.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testThePopulateStateReadsEveryFilter(): void
	{
		$subject = $this->renderer(PopulateState::class, $this->knowledge());
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(self::EXPECTED_POPULATE_STATE, $subject->get($single, $list));
	}

	/**
	 * A view given something to sort by is given the method that names it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithSomethingToSortByIsGivenTheMethod(): void
	{
		$subject = $this->renderer(SortFields::class, $this->knowledge());
		$list = 'demos';

		$this->assertSame(self::EXPECTED_SORT_FIELDS, $subject->get($list));
	}

	/**
	 * A view that sorts by nothing of its own still orders by the fields every
	 * list model carries.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatSortsByNothingStillOrdersByTheDefaults(): void
	{
		$subject = $this->renderer(SortFields::class, $this->knowledge(true, false));
		$list = 'demos';

		$this->assertSame(self::EXPECTED_SORT_FIELDS_NONE, $subject->get($list));
	}

	/**
	 * The filter fields array names every field the list may be reached by.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheFilterFieldsArrayNamesEveryField(): void
	{
		$subject = $this->renderer(FilterFields::class, $this->knowledge());
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(self::EXPECTED_FILTER_FIELDS, $subject->get($single, $list));
	}
}
