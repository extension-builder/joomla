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


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\CustomQuery;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\FilterQuery;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\SearchQuery;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomList;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Filter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Search;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ViewsDefaultOrdering;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Creator\CustomFieldTypeFileInterface;


/**
 * Generated admin list view model query contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedModelListQueryTest extends ArchitectureTestCase
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
	 * Each target takes its user and database from its own place.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheUserAndDatabaseLookupFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->query($version);

		if ($major === 3)
		{
			$this->assertStringContainsString('___Power::getUser();', $code);
			$this->assertStringContainsString('___Power::getDBO();', $code);
			$this->assertStringNotContainsString('$this->getCurrentUser()', $code);
			$this->assertStringNotContainsString('$this->getDatabase()', $code);

			return;
		}

		$this->assertStringContainsString('$user = $this->getCurrentUser();', $code);
		$this->assertStringContainsString('$db = $this->getDatabase();', $code);
		$this->assertStringNotContainsString('___Power::getDBO();', $code);
	}

	/**
	 * The query selects from the view's own table and returns itself.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheQuerySelectsFromTheViewTable(string $version, int $major): void
	{
		$code = $this->query($version);

		$this->assertStringContainsString('$query = $db->getQuery(true);', $code);
		$this->assertStringContainsString(
			"\$query->from(\$db->quoteName('#__demo_article', 'a'));", $code
		);
		$this->assertStringContainsString('return $query;', $code);
	}

	/**
	 * The published filter is applied from the model state.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testThePublishedStateIsFilteredFromTheModelState(): void
	{
		$code = $this->query('JoomlaSix');

		$this->assertStringContainsString(
			"\$published = \$this->getState('filter.published');", $code
		);
	}

	/**
	 * A view with an access switch joins the view levels and guards them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAccessSwitchJoinsTheViewLevels(): void
	{
		$accessswitch = new AccessSwitch();
		$accessswitch->set('article', true);

		$code = $this->query('JoomlaSix', ['accessswitch' => $accessswitch]);

		$this->assertStringContainsString('// Join over the asset groups.', $code);
		$this->assertStringContainsString(
			"\$query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');", $code
		);
		$this->assertStringContainsString('// Implement View Level Access', $code);
	}

	/**
	 * A categorised view filters on the category state.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACategorisedViewFiltersOnItsCategory(): void
	{
		// a filter of 0 joins the category but adds no filter of its own
		$category = new Category();
		$category->set('articles.code', 'catid');
		$category->set('articles.filter', 1);

		$code = $this->query('JoomlaSix', ['category' => $category]);

		$this->assertStringContainsString(
			'// Filter by a single or group of categories.', $code
		);
		$this->assertStringContainsString(
			"\$categoryId = \$this->getState('filter.category_id');", $code
		);
	}

	/**
	 * A categorised view with no category filter still joins the category.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACategoryWithoutAFilterOnlyJoins(): void
	{
		$category = new Category();
		$category->set('articles.code', 'catid');
		$category->set('articles.filter', 0);

		$code = $this->query('JoomlaSix', ['category' => $category]);

		$this->assertStringContainsString("\$db->quoteName('#__categories', 'c')", $code);
		$this->assertStringNotContainsString('filter.category_id', $code);
	}

	/**
	 * Without a configured ordering the query falls back to the id column.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDefaultOrderingFallsBackToTheId(): void
	{
		$code = $this->query('JoomlaSix');

		$this->assertStringContainsString(
			"\$orderCol = \$this->getState('list.ordering', 'a.id');", $code
		);
		$this->assertStringContainsString(
			"\$orderDirn = \$this->getState('list.direction', 'desc');", $code
		);
	}

	/**
	 * The search and filter clauses are folded into the query.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheSearchAndFilterClausesAreFoldedIn(): void
	{
		$search = new Search();
		$search->set('articles', [
			['type' => 'text', 'code' => 'title', 'custom' => null, 'list' => 0],
		]);

		$filter = new Filter();
		$filter->set('articles', [['type' => 'text', 'code' => 'status']]);

		$code = $this->query('JoomlaSix', [
			'searchquery' => new SearchQuery($search, new CustomField()),
			'filterquery' => new FilterQuery(
				$filter, new AdminFilterType(), $this->contentOne()
			),
		]);

		$this->assertStringContainsString("a.title LIKE '.\$search.'", $code);
		$this->assertStringContainsString(
			"\$_status = \$this->getState('filter.status');", $code
		);
	}

	/**
	 * Every target keeps local, relation, access and default ordering behavior together.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('versions')]
	public function testFolderSelectorsDoNotBreakJoinedOrDefaultSearches(string $version, int $major): void
	{
		$relation = ['table' => '#__users', 'db' => 'g', 'text' => 'name', 'id' => 'id'];
		$folder = ['table' => '', 'db' => 'h', 'text' => '', 'id' => ''];
		$search = new Search();
		$search->set('articles', [
			['type' => 'text', 'code' => 'title', 'custom' => null, 'list' => 1],
			['type' => 'user', 'code' => 'created_by', 'custom' => $relation, 'list' => 1],
			['type' => 'folderlist', 'code' => 'admin_view', 'custom' => $folder, 'list' => 1],
		]);
		$customfield = new CustomField();
		$customfield->set('articles', [
			['code' => 'created_by', 'method' => 0, 'custom' => $relation],
			['code' => 'admin_view', 'method' => 0, 'custom' => $folder],
		]);
		$customlist = new CustomList();
		$customlist->set('article.created_by', true);
		$customlist->set('article.admin_view', true);
		$accessswitch = new AccessSwitch();
		$accessswitch->set('article', true);
		$code = $this->query($version, [
			'searchquery' => new SearchQuery($search, $customfield),
			'customquery' => new CustomQuery($customfield, $customlist, $this->createStub(CustomFieldTypeFileInterface::class)),
			'accessswitch' => $accessswitch,
		]);

		$this->assertStringContainsString("\$db->quoteName('#__users', 'g')", $code);
		$this->assertStringContainsString(
			"(a.title LIKE '.\$search.' OR a.created_by LIKE '.\$search.' OR g.name LIKE '.\$search.'"
			. " OR a.admin_view LIKE '.\$search.')", $code
		);
		$this->assertStringNotContainsString(' OR h.', $code);
		$this->assertStringContainsString('// Implement View Level Access', $code);
		$this->assertStringContainsString("\$orderCol = \$this->getState('list.ordering', 'a.id');", $code);
		$this->assertStringContainsString("\$orderDirn = \$this->getState('list.direction', 'desc');", $code);
	}

	/**
	 * Build the list query of one target.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $overrides  Constructor dependency overrides.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function query(string $version, array $overrides = []): string
	{
		// only Joomla 3 takes its user and database from the global factory
		$class = $this->targetClass(
			$version, 'Model\\ListQuery', ['JoomlaThree']
		);

		$subject = $this->renderer($class, $overrides + [
			'contentone' => $this->contentOne(),
			'viewsdefaultordering' => new ViewsDefaultOrdering(),
			// these collaborators are final, so they are built for real
			'customquery' => new CustomQuery(
				new CustomField(),
				new CustomList(),
				$this->createStub(CustomFieldTypeFileInterface::class)
			),
			'searchquery' => new SearchQuery(new Search(), new CustomField()),
			'filterquery' => new FilterQuery(
				new Filter(), new AdminFilterType(), $this->contentOne()
			),
		]);

		$nameSingleCode = 'article';
		$nameListCode = 'articles';

		return $subject->get($nameSingleCode, $nameListCode);
	}

	/**
	 * Create the component content registry the clauses read from.
	 *
	 * @return  ContentOne
	 * @since   6.1.7
	 */
	private function contentOne(): ContentOne
	{
		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		return $contentone;
	}
}
