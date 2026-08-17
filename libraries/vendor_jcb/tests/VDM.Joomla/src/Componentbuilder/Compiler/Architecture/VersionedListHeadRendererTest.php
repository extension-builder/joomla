<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    16th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListColumnNumber;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListHeadOverride;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Lists;


/**
 * Generated admin list-view head contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[CoversClass(ListColumnNumber::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedListHeadRendererTest extends ArchitectureTestCase
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
	 * A view without list fields produces no table head.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testListHeadIsEmptyWithoutListedFields(string $version, int $major): void
	{
		$columns = new ListColumnNumber();
		$subject = $this->listHead($version, new Lists(), $columns);

		$this->assertSame('', $subject->get('article', 'articles'));
		$this->assertNull($columns->get('articles'));
	}

	/**
	 * The sorting guard excludes modal layouts from Joomla 4 onwards.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testListHeadSortingGuardFollowsTheTarget(string $version, int $major): void
	{
		$subject = $this->listHead($version, $this->listsWithOneField());
		$head = $subject->get('article', 'articles');

		if ($major === 3)
		{
			$this->assertStringContainsString(
				"<?php if (\$this->canEdit && \$this->canState): ?>",
				$head
			);
			$this->assertStringNotContainsString('isModal', $head);

			return;
		}

		$this->assertStringContainsString(
			"<?php if (!\$this->isModal && \$this->canEdit && \$this->canState): ?>",
			$head
		);
	}

	/**
	 * The rendered column count is recorded for the matching list footer.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testListHeadCountsItsRenderedColumns(string $version, int $major): void
	{
		$columns = new ListColumnNumber();
		$lists = new Lists();
		$lists->set('articles', [
			$this->field('title', 'COM_DEMO_ARTICLE_TITLE'),
			$this->field('alias', 'COM_DEMO_ARTICLE_ALIAS'),
			// a site-only field is not rendered in the admin list head
			['name' => 'note', 'code' => 'note', 'lang' => 'COM_DEMO_ARTICLE_NOTE', 'title' => 0,
				'type' => 'text', 'target' => 2, 'sort' => 0, 'link' => 0, 'custom' => [],
				'guid' => 'guid-note'],
		]);

		$subject = $this->listHead($version, $lists, $columns);
		$subject->get('article', 'articles');

		// four fixed columns plus the two admin-targeted fields
		$this->assertSame(6, $columns->get('articles'));
	}

	/**
	 * The top-bar filter type switches every heading to the search tools.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testListHeadUsesSearchToolsForTopBarFilters(): void
	{
		$filterType = new AdminFilterType();
		$filterType->set('articles', 2);

		$subject = $this->listHead(
			'JoomlaSix',
			$this->listsWithOneField(),
			new ListColumnNumber(),
			$filterType
		);
		$head = $subject->get('article', 'articles');

		$this->assertStringContainsString("Html::_('searchtools.sort', ''", $head);
		$this->assertStringContainsString("'JGRID_HEADING_ORDERING', 'icon-menu-2');", $head);
		$this->assertStringNotContainsString("grid.sort", $head);
	}

	/**
	 * Grid sorting keeps the legacy icon markup when no top-bar filter is used.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testListHeadUsesGridSortByDefault(): void
	{
		$subject = $this->listHead('JoomlaSix', $this->listsWithOneField());
		$head = $subject->get('article', 'articles');

		$this->assertStringContainsString(
			"<?php echo Html::_('grid.sort', '<i class=\"icon-menu-2\"></i>', 'a.ordering', "
			. "\$this->listDirn, \$this->listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>",
			$head
		);
		$this->assertStringContainsString("<?php echo Html::_('grid.checkall'); ?>", $head);
	}

	/**
	 * Sortable headings honour category, custom and plain column sources.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testListHeadRendersEachSortableHeadingSource(): void
	{
		$lists = new Lists();
		$lists->set('articles', [
			$this->field('title', 'COM_DEMO_ARTICLE_TITLE'),
			['name' => 'category', 'code' => 'catid', 'lang' => 'COM_DEMO_ARTICLE_CATEGORY',
				'type' => 'category', 'target' => 1, 'sort' => 1, 'link' => 1, 'custom' => [],
				'guid' => 'guid-cat'],
			['name' => 'owner', 'code' => 'owner', 'lang' => 'COM_DEMO_ARTICLE_OWNER',
				'type' => 'user', 'target' => 1, 'sort' => 1, 'link' => 0,
				'custom' => ['db' => 'g', 'text' => 'name'], 'guid' => 'guid-owner'],
			['name' => 'hits', 'code' => 'hits', 'lang' => 'COM_DEMO_ARTICLE_HITS',
				'type' => 'text', 'target' => 1, 'sort' => 0, 'link' => 0, 'custom' => [],
				'guid' => 'guid-hits'],
		]);

		$subject = $this->listHead('JoomlaSix', $lists);
		$head = $subject->get('article', 'articles');

		$this->assertStringContainsString(
			"Html::_('grid.sort', 'COM_DEMO_ARTICLE_TITLE', 'a.title', \$this->listDirn, \$this->listOrder); ?>",
			$head
		);
		$this->assertStringContainsString(
			"Html::_('grid.sort', 'COM_DEMO_ARTICLE_CATEGORY', 'category_title', \$this->listDirn, \$this->listOrder); ?>",
			$head
		);
		$this->assertStringContainsString(
			"Html::_('grid.sort', 'COM_DEMO_ARTICLE_OWNER', 'g.name', \$this->listDirn, \$this->listOrder); ?>",
			$head
		);
		$this->assertStringContainsString(
			"<?php echo Joomla__" . "_ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_('COM_DEMO_ARTICLE_HITS'); ?>",
			$head
		);
		// a linked column drops the responsive hiding class
		$this->assertStringContainsString('<th class="nowrap" >', $head);
		$this->assertStringContainsString('<th class="nowrap hidden-phone" >', $head);
	}

	/**
	 * A configured head override replaces the field language key.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testListHeadAppliesConfiguredOverride(): void
	{
		$override = new ListHeadOverride();
		$override->set('articles.guid-title', 'COM_DEMO_ARTICLE_CUSTOM_HEAD');

		$subject = $this->listHead(
			'JoomlaSix',
			$this->listsWithOneField(),
			new ListColumnNumber(),
			null,
			null,
			$override
		);
		$head = $subject->get('article', 'articles');

		$this->assertStringContainsString('COM_DEMO_ARTICLE_CUSTOM_HEAD', $head);
		$this->assertStringNotContainsString('COM_DEMO_ARTICLE_TITLE', $head);
	}

	/**
	 * Status and id headings are omitted when the view declares those fields.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testListHeadSkipsStatusAndIdWhenTheViewOwnsThem(): void
	{
		$fieldNames = new FieldNames();
		$fieldNames->set('article.published', 'published');
		$fieldNames->set('article.id', 'id');
		$fieldNames->set('article.ordering', 'ordering');

		$subject = $this->listHead(
			'JoomlaSix',
			$this->listsWithOneField(),
			new ListColumnNumber(),
			null,
			$fieldNames
		);
		$head = $subject->get('article', 'articles');

		$this->assertStringNotContainsString('COM_DEMO_ARTICLE_STATUS', $head);
		$this->assertStringNotContainsString('COM_DEMO_ARTICLE_ID', $head);
		$this->assertStringNotContainsString("'a.ordering'", $head);
		$this->assertStringEndsWith(PHP_EOL . '</tr>', $head);
	}

	/**
	 * The status and id headings register their own language strings.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testListHeadRegistersStatusAndIdLanguageStrings(): void
	{
		$subject = $this->listHead('JoomlaSix', $this->listsWithOneField());
		$head = $subject->get('article', 'articles');

		$this->assertSame('Status', $this->language()->get('admin', 'COM_DEMO_ARTICLE_STATUS'));
		$this->assertSame('Id', $this->language()->get('admin', 'COM_DEMO_ARTICLE_ID'));
		$this->assertStringContainsString("<?php if (\$this->canState): ?>", $head);
		$this->assertStringContainsString("'COM_DEMO_ARTICLE_STATUS', 'a.published'", $head);
		$this->assertStringContainsString("'COM_DEMO_ARTICLE_ID', 'a.id'", $head);
	}

	/**
	 * Create one admin-targeted sortable list field.
	 *
	 * @param   string  $code  The field code.
	 * @param   string  $lang  The field language key.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.7
	 */
	private function field(string $code, string $lang): array
	{
		return [
			'name' => $code,
			'code' => $code,
			'lang' => $lang,
			'type' => 'text',
			'target' => 1,
			'sort' => 1,
			'link' => 0,
			'custom' => [],
			'guid' => 'guid-' . $code,
		];
	}

	/**
	 * Create a list registry holding one admin-targeted field.
	 *
	 * @return  Lists
	 * @since   6.1.7
	 */
	private function listsWithOneField(): Lists
	{
		$lists = new Lists();
		$lists->set('articles', [$this->field('title', 'COM_DEMO_ARTICLE_TITLE')]);

		return $lists;
	}

	/**
	 * Create a versioned list-head renderer with real registries.
	 *
	 * @param   string                 $version           Target namespace segment.
	 * @param   Lists                  $lists             The list-field registry.
	 * @param   ListColumnNumber|null  $columns           The column-count registry.
	 * @param   AdminFilterType|null   $filterType        The filter-type registry.
	 * @param   FieldNames|null        $fieldNames        The field-name registry.
	 * @param   ListHeadOverride|null  $override          The head-override registry.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function listHead(
		string $version,
		Lists $lists,
		?ListColumnNumber $columns = null,
		?AdminFilterType $filterType = null,
		?FieldNames $fieldNames = null,
		?ListHeadOverride $override = null
	): object
	{
		return $this->renderer(
			'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\'
				. $version . '\\AdminViews\\ListHead',
			[
				'lists' => $lists,
				'listcolumnnumber' => $columns ?? new ListColumnNumber(),
				'adminfiltertype' => $filterType ?? new AdminFilterType(),
				'fieldnames' => $fieldNames ?? new FieldNames(),
				'listheadoverride' => $override ?? new ListHeadOverride(),
			]
		);
	}
}
