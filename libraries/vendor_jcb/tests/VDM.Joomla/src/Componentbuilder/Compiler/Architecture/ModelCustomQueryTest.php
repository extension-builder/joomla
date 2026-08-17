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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\CustomQuery;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomList;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Creator\CustomFieldTypeFileInterface as CustomFieldTypeFile;


/**
 * Model custom field query contracts.
 *
 * @since  6.1.7
 */
#[CoversClass(CustomQuery::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelCustomQueryTest extends ArchitectureTestCase
{
	/**
	 * A view with no custom fields adds nothing to the query.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutCustomFieldsAddsNothing(): void
	{
		$subject = $this->subject(new CustomField(), new CustomList());

		$this->assertSame('', $subject->get('articles', 'article'));
	}

	/**
	 * A foreign key field selects its display text and joins its table.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAForeignKeyFieldSelectsItsTextAndJoinsItsTable(): void
	{
		$query = $this->subject(
			$this->customfield(), $this->customlist()
		)->get('articles', 'article');

		$this->assertStringContainsString('// From the categories table.', $query);
		$this->assertStringContainsString(
			"\$query->select(\$db->quoteName('g.name','catid_name'));",
			$query
		);
		$this->assertStringContainsString(
			"\$query->join('LEFT', \$db->quoteName('#__categories', 'g')"
			. " . ' ON (' . \$db->quoteName('a.catid') . ' = ' . \$db->quoteName('g.id') . ')');",
			$query
		);
	}

	/**
	 * Asking for just the text aliases the column to the field code itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJustTextAliasesTheColumnToTheFieldCode(): void
	{
		$query = $this->subject($this->customfield(), new CustomList())
			->get('articles', 'article', '', true);

		$this->assertStringContainsString(
			"\$query->select(\$db->quoteName('g.name','catid'));",
			$query
		);
		$this->assertStringNotContainsString("'catid_name'", $query);
	}

	/**
	 * A table keyed on something other than id also selects that key.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testANonIdKeyAlsoSelectsTheKeyColumn(): void
	{
		$customfield = new CustomField();
		$customfield->set('articles', [
			[
				'code' => 'catid',
				'method' => 0,
				'custom' => [
					'table' => '#__categories',
					'db' => 'g',
					'text' => 'name',
					'id' => 'alias',
				],
			],
		]);

		$query = $this->subject($customfield, $this->customlist())
			->get('articles', 'article');

		$this->assertStringContainsString(
			"\$query->select(\$db->quoteName(['g.name','g.id'],['catid_name','catid_id']));",
			$query
		);
		$this->assertStringContainsString("\$db->quoteName('g.alias')", $query);
	}

	/**
	 * A field the list does not use contributes nothing to the query.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFieldOutsideTheListIsNotQueried(): void
	{
		$query = $this->subject($this->customfield(), new CustomList())
			->get('articles', 'article');

		$this->assertSame('', $query);
	}

	/**
	 * A field whose method is not zero is never joined.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFieldWithAnotherMethodIsNotJoined(): void
	{
		$customfield = new CustomField();
		$customfield->set('articles', [
			[
				'code' => 'catid',
				'method' => 1,
				'custom' => [
					'table' => '#__categories',
					'db' => 'g',
					'text' => 'name',
					'id' => 'id',
				],
			],
		]);

		$this->assertSame(
			'',
			$this->subject($customfield, $this->customlist())->get('articles', 'article')
		);
	}

	/**
	 * Every custom field has its type file written, queried or not.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryCustomFieldStillGetsItsTypeFile(): void
	{
		$customfield = new CustomField();
		$customfield->set('articles', [
			['code' => 'catid', 'method' => 0, 'custom' => [
				'table' => '#__categories', 'db' => 'g', 'text' => 'name', 'id' => 'id',
			]],
			// no table, so it never reaches the query
			['code' => 'note', 'method' => 0, 'custom' => []],
		]);

		$written = [];
		$typefile = $this->createStub(CustomFieldTypeFile::class);
		$typefile->method('set')
			->willReturnCallback(
				static function (array $filter, string $list, string $single)
					use (&$written): void
				{
					$written[] = $filter['code'];
				}
			);

		$subject = new CustomQuery($customfield, $this->customlist(), $typefile);
		$subject->get('articles', 'article');

		$this->assertSame(['catid', 'note'], $written);
	}

	/**
	 * Build a custom field registry with one joinable foreign key.
	 *
	 * @return  CustomField
	 * @since   6.1.7
	 */
	private function customfield(): CustomField
	{
		$customfield = new CustomField();
		$customfield->set('articles', [
			[
				'code' => 'catid',
				'method' => 0,
				'custom' => [
					'table' => '#__categories',
					'db' => 'g',
					'text' => 'name',
					'id' => 'id',
				],
			],
		]);

		return $customfield;
	}

	/**
	 * Build a custom list registry that uses the foreign key field.
	 *
	 * @return  CustomList
	 * @since   6.1.7
	 */
	private function customlist(): CustomList
	{
		$customlist = new CustomList();
		$customlist->set('article.catid', true);

		return $customlist;
	}

	/**
	 * Create the custom query builder with real registries.
	 *
	 * @param   CustomField  $customfield  The custom field registry.
	 * @param   CustomList   $customlist   The custom list registry.
	 *
	 * @return  CustomQuery
	 * @since   6.1.7
	 */
	private function subject(CustomField $customfield, CustomList $customlist): CustomQuery
	{
		return new CustomQuery(
			$customfield,
			$customlist,
			$this->createStub(CustomFieldTypeFile::class)
		);
	}
}
