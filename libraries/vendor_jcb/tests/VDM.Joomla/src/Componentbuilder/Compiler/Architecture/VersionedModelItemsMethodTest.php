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
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomList;
use VDM\Joomla\Componentbuilder\Compiler\Builder\EximportView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SelectionTranslation as SelectionTranslationData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ViewsDefaultOrdering;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Creator\CustomFieldTypeFileInterface;


/**
 * Generated list model items method contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedModelItemsMethodTest extends ArchitectureTestCase
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
		$code = $this->method($version);

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
	 * The export method is named and documented from the given config.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheExportMethodIsNamedFromTheConfig(): void
	{
		$code = $this->method('JoomlaSix');

		$this->assertStringContainsString('public function getExportData(', $code);
		$this->assertStringContainsString('Method to get list export data.', $code);
	}

	/**
	 * A getItems config builds the list getter instead of the export getter.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAGetItemsConfigBuildsTheListGetter(): void
	{
		$code = $this->method('JoomlaSix', [], [
			'functionName' => 'getItems',
			'docDesc' => 'Method to get an array of data items.',
			'type' => 'items',
		]);

		$this->assertStringContainsString('public function getItems(', $code);
		$this->assertStringContainsString('Method to get an array of data items.', $code);
		$this->assertStringNotContainsString('getExportData', $code);
	}

	/**
	 * The query is built and run against the view's own table.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheQueryRunsAgainstTheViewTable(): void
	{
		$code = $this->method('JoomlaSix');

		$this->assertStringContainsString('$query = $db->getQuery(true);', $code);
		$this->assertStringContainsString(
			"\$query->from(\$db->quoteName('#__demo_article', 'a'));", $code
		);
		$this->assertStringContainsString('$db->setQuery($query);', $code);
	}

	/**
	 * A view with an access switch guards the export on view levels.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAccessSwitchGuardsTheExport(): void
	{
		$accessswitch = new AccessSwitch();
		$accessswitch->set('article', true);

		$code = $this->method('JoomlaSix', ['accessswitch' => $accessswitch]);

		$this->assertStringContainsString('// Implement View Level Access', $code);
		$this->assertStringContainsString(
			"if (!\$user->authorise('core.options', 'com_demo'))", $code
		);
		$this->assertStringContainsString(
			"\$query->where('a.access IN (' . \$groups . ')');", $code
		);
	}

	/**
	 * Export text only folds the selection translations into the method.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testExportTextOnlyFoldsInTheSelectionTranslations(): void
	{
		$this->config()->set('export_text_only', 1);

		$data = new SelectionTranslationData();
		$data->set('articles', ['status' => ['1' => 'Published']]);

		$code = $this->method('JoomlaSix', [
			'selectiontranslation' => new SelectionTranslation($data),
		]);

		$this->assertStringContainsString(
			'// set selection value to a translatable value', $code
		);
		$this->assertStringContainsString(
			"\$item->status = \$this->selectionTranslation(\$item->status, 'status');",
			$code
		);
	}

	/**
	 * Without export text only no selection translations are folded in.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithoutExportTextOnlyNoTranslationsAreFoldedIn(): void
	{
		$data = new SelectionTranslationData();
		$data->set('articles', ['status' => ['1' => 'Published']]);

		$code = $this->method('JoomlaSix', [
			'selectiontranslation' => new SelectionTranslation($data),
		]);

		$this->assertStringNotContainsString(
			'// set selection value to a translatable value', $code
		);
	}

	/**
	 * Build the items method of one target.
	 *
	 * @param   string      $version    Target namespace segment.
	 * @param   array       $overrides  Constructor dependency overrides.
	 * @param   array|null  $config     The method config, or the export default.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function method(string $version, array $overrides = [],
		?array $config = null): string
	{
		// only Joomla 3 takes its user and database from the global factory
		$class = $this->targetClass(
			$version, 'Model\\ItemsMethod', ['JoomlaThree']
		);

		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		// the export method only builds for a view that carries the feature
		$eximportview = new EximportView();
		$eximportview->set('articles', true);

		$subject = $this->renderer($class, $overrides + [
			'contentone' => $contentone,
			'eximportview' => $eximportview,
			'viewsdefaultordering' => new ViewsDefaultOrdering(),
			// these collaborators are final, so they are built for real
			'customquery' => new CustomQuery(
				new CustomField(),
				new CustomList(),
				$this->createStub(CustomFieldTypeFileInterface::class)
			),
			'selectiontranslation' => new SelectionTranslation(
				new SelectionTranslationData()
			),
		]);

		$nameSingleCode = 'article';
		$nameListCode = 'articles';

		$config ??= [
			'functionName' => 'getExportData',
			'docDesc' => 'Method to get list export data.',
			'type' => 'export',
		];

		return $subject->get($nameSingleCode, $nameListCode, $config);
	}
}
