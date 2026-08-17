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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\SelectionTranslation;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\SelectionTranslationMethod;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomList;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SelectionTranslation as SelectionTranslationData;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Creator\CustomFieldTypeFileInterface;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;


/**
 * Generated linked view getter contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedLinkedViewListQueryTest extends ArchitectureTestCase
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
	public function testTheUserAndDatabaseLookupFollowTheTarget(string $version, int $major): void
	{
		$code = $this->query($version);

		if ($major === 3)
		{
			$this->assertStringContainsString('___Power::getUser();', $code);
			$this->assertStringContainsString('___Power::getDBO();', $code);
			$this->assertStringNotContainsString('getIdentity()', $code);
			$this->assertStringNotContainsString('$this->getDatabase()', $code);

			return;
		}

		$this->assertStringContainsString(
			'___Power::getApplication()->getIdentity();', $code
		);
		$this->assertStringContainsString('$db = $this->getDatabase();', $code);
		$this->assertStringNotContainsString('::getDBO()', $code);
	}

	/**
	 * The getter is named after the linked view and loads its own items.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheGetterIsNamedAfterTheLinkedView(string $version, int $major): void
	{
		$code = $this->query($version);

		$this->assertStringContainsString('public function getComments()', $code);
		$this->assertStringContainsString("\$query->select('a.*');", $code);
		$this->assertStringContainsString(
			"\$query->from(\$db->quoteName('#__demo_comment', 'a'));",
			$code
		);
		$this->assertStringContainsString('$db->setQuery($query);', $code);
	}

	/**
	 * A plain key filters the linked items on the parent's value.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAPlainKeyFiltersOnTheParentValue(): void
	{
		$code = $this->query('JoomlaSix');

		$this->assertStringContainsString('// Filter by article_id global.', $code);
		$this->assertStringContainsString('$article_id = $this->article_id;', $code);
		$this->assertStringContainsString(
			"\$query->where('a.article = ' . (int) \$article_id );",
			$code
		);
		// a non numeric, non string value can never match
		$this->assertStringContainsString("\$query->where('a.article = -5');", $code);
	}

	/**
	 * A repeatable field key filters the loaded items instead of the query.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testARepeatableKeyFiltersTheLoadedItems(): void
	{
		$code = $this->query('JoomlaSix', 'article-R>ids');

		$this->assertStringContainsString(
			'// Filter by article_id in this Repetable Field',
			$code
		);
		$this->assertStringContainsString('$tmpArray = json_decode($item->article,true);', $code);
		$this->assertStringContainsString('unset($items[$nr]);', $code);
	}

	/**
	 * An array field key decodes the field before matching against it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnArrayKeyDecodesTheFieldBeforeMatching(): void
	{
		$code = $this->query('JoomlaSix', 'article-A>ids');

		$this->assertStringContainsString('// Filter by article_id Array Field', $code);
		$this->assertStringContainsString('$item->ids = json_decode($item->ids, true);', $code);
		$this->assertStringContainsString('if (!in_array($article_id,$item->ids))', $code);
	}

	/**
	 * An OR key matches the parent value against every column it names.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnOrKeyMatchesEveryColumnItNames(): void
	{
		$code = $this->query('JoomlaSix', 'article-OR>author');

		$this->assertStringContainsString("' OR ", $code);
		$this->assertStringContainsString(", ' OR');", $code);
		$this->assertStringContainsString('a.article = ', $code);
		$this->assertStringContainsString('a.author = ', $code);
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
		$accessswitch->set('comment', true);

		$code = $this->query('JoomlaSix', 'article', ['accessswitch' => $accessswitch]);

		$this->assertStringContainsString('// Join over the asset groups.', $code);
		$this->assertStringContainsString(
			"\$query->join('LEFT', '#__viewlevels AS ag ON ag.id = a.access');",
			$code
		);
		$this->assertStringContainsString('// Implement View Level Access', $code);
		$this->assertStringContainsString("\$user->authorise('core.options', 'com_demo')", $code);
	}

	/**
	 * A categorised view selects and joins the category title.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACategorisedViewJoinsItsCategoryTitle(): void
	{
		$category = new Category();
		$category->set('comments.code', 'catid');

		$code = $this->query('JoomlaSix', 'article', ['category' => $category]);

		$this->assertStringContainsString(
			"\$query->select(\$db->quoteName('c.title','category_title'));",
			$code
		);
		$this->assertStringContainsString("\$db->quoteName('a.catid')", $code);
	}

	/**
	 * Without a linked ordering the results fall back to publish and ordering.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDefaultOrderingIsPublishedThenOrdering(): void
	{
		$code = $this->query('JoomlaSix');

		$this->assertStringContainsString("\$query->order('a.published  ASC');", $code);
		$this->assertStringContainsString("\$query->order('a.ordering  ASC');", $code);
	}

	/**
	 * Build the linked view getter of one target.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   string  $key        The key tying the two views together.
	 * @param   array   $overrides  Constructor dependency overrides.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function query(string $version, string $key = 'article',
		array $overrides = []): string
	{
		// only Joomla 3 takes its user and database from the global factory
		$class = $this->targetClass(
			$version, 'LinkedView\\ListQuery', ['JoomlaThree']
		);

		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		// these collaborators are final, so they are built for real
		$subject = $this->renderer($class, $overrides + [
			'contentone' => $contentone,
			'customquery' => new CustomQuery(
				new CustomField(),
				new CustomList(),
				$this->createStub(CustomFieldTypeFileInterface::class)
			),
			'selectiontranslation' => new SelectionTranslation(
				new SelectionTranslationData()
			),
			'selectiontranslationmethod' => new SelectionTranslationMethod(
				new SelectionTranslationData()
			),
		]);

		return $subject->get(
			'comment', 'comments', 'Comments', $key, 'ids',
			'id', 'id', 'article_id'
		);
	}
}
