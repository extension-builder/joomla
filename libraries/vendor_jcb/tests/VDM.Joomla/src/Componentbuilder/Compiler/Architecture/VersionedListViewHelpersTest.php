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
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Filter;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;


/**
 * Generated list view sidebar filter and batch option contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedListViewHelpersTest extends ArchitectureTestCase
{
	/**
	 * The sidebar filters of a plain list view, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SIDEBAR_PLAIN = <<<'GEN'


		// Only load publish filter if state change is allowed
		if ($this->canState)
		{
			Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(
				Text::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				Html::_('select.options', Html::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
		}
GEN;

	/**
	 * The sidebar filters of a view with an access field, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SIDEBAR_ACCESS = <<<'GEN'


		// Only load publish filter if state change is allowed
		if ($this->canState)
		{
			Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(
				Text::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				Html::_('select.options', Html::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
		}

		Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(
			Text::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			Html::_('select.options', Html::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);
GEN;

	/**
	 * The sidebar filters of a view with categories, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SIDEBAR_CATEGORY = <<<'GEN'


		// Only load publish filter if state change is allowed
		if ($this->canState)
		{
			Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(
				Text::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				Html::_('select.options', Html::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
		}

		// Category Filter.
		Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(
			Text::_('JOPTION_SELECT_CATEGORY'),
			'filter_category_id',
			Html::_('select.options', Html::_('category.options', 'com_demo.demo'), 'value', 'text', $this->state->get('filter.category_id'))
		);
GEN;

	/**
	 * The sidebar filters of a view with a filter field, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_SIDEBAR_FILTER = <<<'GEN'


		// Only load publish filter if state change is allowed
		if ($this->canState)
		{
			Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(
				Text::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				Html::_('select.options', Html::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
		}

		// Set Status Name Selection
		$this->statusNameOptions = FormHelper::loadFieldType('List')->options;
		// We do some sanitation for Status Name filter
		if (Super___0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check($this->statusNameOptions) &&
			isset($this->statusNameOptions[0]->value) &&
			!Super___1f28cb53_60d9_4db1_b517_3c7dc6b429ef___Power::check($this->statusNameOptions[0]->value))
		{
			unset($this->statusNameOptions[0]);
		}
		// Only load Status Name filter if it has values
		if (Super___0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::check($this->statusNameOptions))
		{
			// Status Name Filter
			Joomla___ca5456e1_552c_45fb_bf4c_b751ba6e9fa1___Power::addFilter(
				'- Select ' . Text::_('COM_DEMO_STATUS') . ' -',
				'filter_status',
				Html::_('select.options', $this->statusNameOptions, 'value', 'text', $this->state->get('filter.status'))
			);
		}
GEN;

	/**
	 * The batch options of a plain list view, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_BATCH_PLAIN = <<<'GEN'


		// Only load published batch if state and batch is allowed
		if ($this->canState && $this->canBatch)
		{
			JHtmlBatch_::addListSelection(
				Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_KEEP_ORIGINAL_STATE'),
				'batch[published]',
				Html::_('select.options', Html::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)
			);
		}
GEN;

	/**
	 * The batch options of a view with categories, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_BATCH_CATEGORY = <<<'GEN'


		// Only load published batch if state and batch is allowed
		if ($this->canState && $this->canBatch)
		{
			JHtmlBatch_::addListSelection(
				Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_KEEP_ORIGINAL_STATE'),
				'batch[published]',
				Html::_('select.options', Html::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)
			);
		}

		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			// Category Batch selection.
			JHtmlBatch_::addListSelection(
				Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_KEEP_ORIGINAL_CATEGORY'),
				'batch[category]',
				Html::_('select.options', Html::_('category.options', 'com_demo.demo'), 'value', 'text')
			);
		}
GEN;

	/**
	 * The targets that build neither a sidebar filter nor a batch helper.
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
	 * What the compiler knows about the demo list view.
	 *
	 * @param   bool  $withFilter    Whether the view was given a filter field.
	 * @param   bool  $withCategory  Whether the view has categories.
	 * @param   bool  $withAccess    Whether the view has an access field.
	 * @param   int   $filterType    Where the view puts its filters.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function knowledge(bool $withFilter = false, bool $withCategory = false,
		bool $withAccess = false, int $filterType = 1): array
	{
		$adminfiltertype = new AdminFilterType();
		$adminfiltertype->set('demos', $filterType);

		$filter = new Filter();
		if ($withFilter)
		{
			$filter->set('demos', [[
				'type' => 'text',
				'code' => 'status',
				'lang' => 'COM_DEMO_STATUS',
				'function' => 'Status',
				'filter_type' => 'list',
				'custom' => ['text' => 'name', 'type' => 'list', 'extends' => 'demo'],
			]]);
		}

		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		$accessswitch = new AccessSwitch();
		if ($withAccess)
		{
			$accessswitch->set('demo', true);
		}

		$category = new Category();
		if ($withCategory)
		{
			$category->set('demos.extension', 'com_demo.demo');
			$category->set('demos.filter', 1);
		}

		return [
			'adminfiltertype' => $adminfiltertype,
			'filter' => $filter,
			'contentone' => $contentone,
			'accessswitch' => $accessswitch,
			'fieldnames' => new FieldNames(),
			'category' => $category,
			'component' => $this->component(),
		];
	}

	/**
	 * The demo component, as the compiler read it.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function component(): Component
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));
		$component->set('name_code', 'demo');

		return $component;
	}

	/**
	 * Build the sidebar filter writer of a target.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $knowledge  What the compiler knows.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function sidebar(string $version, array $knowledge = []): object
	{
		return $this->renderer(
			$this->targetClass($version, 'AdminViews\\SidebarFilters', ['JoomlaThree']),
			$knowledge
		);
	}

	/**
	 * Build the batch option writer of a target.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $knowledge  What the compiler knows.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function batch(string $version, array $knowledge = []): object
	{
		return $this->renderer(
			$this->targetClass($version, 'AdminViews\\BatchOptions', ['JoomlaThree']),
			$knowledge
		);
	}

	/**
	 * A modern target builds no sidebar filters, whatever the view asks for.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernTargetBuildsNoSidebarFilters(string $version): void
	{
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(
			'', $this->sidebar($version, $this->knowledge(true, true, true))->get($single, $list)
		);
	}

	/**
	 * A modern target builds no batch helper, whatever the view asks for.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernTargetBuildsNoBatchHelper(string $version): void
	{
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(
			'', $this->batch($version, $this->knowledge(true, true, true))->get($single, $list)
		);
	}

	/**
	 * A Joomla 3 list view is given the published filter every view has.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeGivesEveryListViewThePublishedFilter(): void
	{
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(
			self::EXPECTED_SIDEBAR_PLAIN,
			$this->sidebar('JoomlaThree', $this->knowledge())->get($single, $list)
		);
	}

	/**
	 * A view with an access field of its own is given the access filter too.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAnAccessFieldIsGivenTheAccessFilter(): void
	{
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(
			self::EXPECTED_SIDEBAR_ACCESS,
			$this->sidebar('JoomlaThree', $this->knowledge(false, false, true))->get($single, $list)
		);
	}

	/**
	 * A view with categories is given the category filter.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithCategoriesIsGivenTheCategoryFilter(): void
	{
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(
			self::EXPECTED_SIDEBAR_CATEGORY,
			$this->sidebar('JoomlaThree', $this->knowledge(false, true))->get($single, $list)
		);
	}

	/**
	 * A view given a filter field is given a filter for it, sanitised first.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAFilterFieldIsGivenAFilterForIt(): void
	{
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(
			self::EXPECTED_SIDEBAR_FILTER,
			$this->sidebar('JoomlaThree', $this->knowledge(true))->get($single, $list)
		);
	}

	/**
	 * A view whose filters live in the top bar is given no sidebar filters.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithTopBarFiltersIsGivenNoSidebarFilters(): void
	{
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(
			'',
			$this->sidebar('JoomlaThree', $this->knowledge(true, true, true, 2))->get($single, $list)
		);
	}

	/**
	 * A Joomla 3 list view is given the state and access batch options.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeGivesEveryListViewTheDefaultBatchOptions(): void
	{
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(
			self::EXPECTED_BATCH_PLAIN,
			$this->batch('JoomlaThree', $this->knowledge())->get($single, $list)
		);
	}

	/**
	 * A view with categories is given the category batch option.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithCategoriesIsGivenTheCategoryBatchOption(): void
	{
		$single = 'demo';
		$list = 'demos';

		$this->assertSame(
			self::EXPECTED_BATCH_CATEGORY,
			$this->batch('JoomlaThree', $this->knowledge(false, true))->get($single, $list)
		);
	}
}
