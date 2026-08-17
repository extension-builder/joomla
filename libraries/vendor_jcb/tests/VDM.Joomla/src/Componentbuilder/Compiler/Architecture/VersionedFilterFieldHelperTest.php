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


use Joomla\CMS\Application\CMSApplicationInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\FilterFieldFile;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Field\CustomFieldCode;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Filter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SelectionTranslation;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * Generated list view filter helper contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedFilterFieldHelperTest extends ArchitectureTestCase
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
	 * A view with no filters generates no helper methods.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewWithoutFiltersGeneratesNothing(string $version, int $major): void
	{
		$this->assertSame('', $this->helper($version, new Filter()));
	}

	/**
	 * Each target opens its database connection its own way.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheDatabaseLookupFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->helper($version, $this->filter([
			$this->filterField('status'),
		]));

		if ($major === 3)
		{
			$this->assertStringContainsString('___Power::getDbo();', $code);
			$this->assertStringNotContainsString('getContainer()->get(Joomla__', $code);

			return;
		}

		$this->assertStringContainsString('___Power::getContainer()->get(', $code);
		$this->assertStringNotContainsString('___Power::getDbo();', $code);
	}

	/**
	 * Each target resolves a user filter's name its own way.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheUserNameLookupFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->helper($version, $this->filter([
			$this->filterField('created_by', 'user'),
		]));

		if ($major === 3)
		{
			$this->assertStringContainsString(
				'___Power::getUser($created_by)->name);', $code
			);
			$this->assertStringNotContainsString('loadUserById', $code);

			return;
		}

		$this->assertStringContainsString(
			'loadUserById((int) ($created_by ?? 0))->name', $code
		);
		$this->assertStringNotContainsString('___Power::getUser($created_by)', $code);
	}

	/**
	 * A filtered field gets a getter named after it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachFilteredFieldGetsItsOwnGetter(): void
	{
		$code = $this->helper('JoomlaSix', $this->filter([
			$this->filterField('status'),
			$this->filterField('kind'),
		]));

		$this->assertStringContainsString('protected function getTheStatusSelections()', $code);
		$this->assertStringContainsString('protected function getTheKindSelections()', $code);
	}

	/**
	 * The generated getter queries the view's own table for its values.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheGetterQueriesTheViewTable(): void
	{
		$code = $this->helper('JoomlaSix', $this->filter([
			$this->filterField('status'),
		]));

		$this->assertStringContainsString('$query = $db->getQuery(true);', $code);
		$this->assertStringContainsString(
			"\$query->select(\$db->quoteName('status'));", $code
		);
		$this->assertStringContainsString(
			"\$query->from(\$db->quoteName('#__demo_article'));", $code
		);
		$this->assertStringContainsString('$db->setQuery($query);', $code);
		$this->assertStringContainsString('$_results = $db->loadColumn();', $code);
	}

	/**
	 * Build the filter helper of one target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   Filter  $filter   The filter registry.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function helper(string $version, Filter $filter): string
	{
		// only Joomla 3 takes its database and user from the global factory
		$class = $this->targetClass(
			$version, 'AdminViews\\FilterFieldHelper', ['JoomlaThree']
		);

		$subject = $this->renderer($class, [
			'filter' => $filter,
			'adminfiltertype' => new AdminFilterType(),
			'selectiontranslation' => new SelectionTranslation(),
			// these collaborators are final, so they are built for real
			'customfieldcode' => new CustomFieldCode($this->placeholder()),
			'filterfieldfile' => new FilterFieldFile(
				new ContentMulti(),
				$this->createStub(Structure::class)
			),
			'app' => $this->createStub(CMSApplicationInterface::class),
		]);

		$nameSingleCode = 'article';
		$nameListCode = 'articles';

		return $subject->get($nameSingleCode, $nameListCode);
	}

	/**
	 * Build one complete filter definition, as the filter registry stores it.
	 *
	 * @param   string  $code  The field code name.
	 * @param   string  $type  The field type.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function filterField(string $code, string $type = 'text'): array
	{
		return [
			'id' => 1,
			'type' => $type,
			'code' => $code,
			'custom' => [],
			'multi' => 1,
			'function' => ucfirst($code),
			'database' => 'article',
			'lang' => 'COM_DEMO_ARTICLE_' . strtoupper($code),
			'lang_select' => 'COM_DEMO_FILTER_' . strtoupper($code),
			'filter_type' => $code,
		];
	}

	/**
	 * Create a filter registry carrying the given filters.
	 *
	 * @param   array  $filters  The filter definitions.
	 *
	 * @return  Filter
	 * @since   6.1.7
	 */
	private function filter(array $filters): Filter
	{
		$filter = new Filter();
		$filter->set('articles', $filters);

		return $filter;
	}
}
