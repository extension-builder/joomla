<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Model;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Field;
use VDM\Joomla\Componentbuilder\Compiler\Field\Groups;
use VDM\Joomla\Componentbuilder\Compiler\Field\Name;
use VDM\Joomla\Componentbuilder\Compiler\Field\TypeName;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HistoryInterface;
use VDM\Joomla\Componentbuilder\Compiler\Model\Conditions;
use VDM\Joomla\Componentbuilder\Compiler\Model\Fields;
use VDM\Joomla\Componentbuilder\Compiler\Model\Updatesql;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Field-list and conditional-rule model contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Conditions::class)]
#[CoversClass(Fields::class)]
#[UsesClass(Groups::class)]
final class FieldDefinitionModelsTest extends CompilerDomainTestCase
{
	/**
	 * Resolve target and match field metadata, including required/filter/extends values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConditionsResolveStoredFieldReferencesIntoRuntimeMetadata(): void
	{
		$typeName = $this->createStub(TypeName::class);
		$typeName->method('get')->willReturnCallback(
			static fn(array $field): string => $field['field'] === 11 ? 'text' : 'customtype'
		);
		$fieldName = $this->createStub(Name::class);
		$fieldName->method('get')->willReturnCallback(
			static fn(array $field): string => $field['field'] === 11 ? 'title' : 'category_code'
		);
		$item = (object) [
			'name_list_code' => 'articles',
			'fields' => [
				['field' => 11, 'settings' => (object) ['xml' => '<field required="true" filter="string" />']],
				['field' => 22, 'settings' => (object) ['xml' => '<field extends="list" />']],
			],
			'addconditions' => json_encode([[
				'target_field' => [11],
				'match_field' => 22,
			]], JSON_THROW_ON_ERROR),
		];
		$subject = new Conditions(
			$typeName,
			$fieldName,
			new Groups($this->createStub(DatabaseInterface::class))
		);

		$subject->set($item);

		$this->assertSame([
			'name' => 'title',
			'type' => 'text',
			'required' => 'yes',
			'filter' => 'string',
		], $item->conditions[0]['target_field'][0]);
		$this->assertSame('category_code', $item->conditions[0]['match_name']);
		$this->assertSame('customtype', $item->conditions[0]['match_type']);
		$this->assertSame('list', $item->conditions[0]['match_extends']);
		$this->assertObjectNotHasProperty('addconditions', $item);
	}

	/**
	 * Enrich, sort, and expose fields while consuming the serialized association source.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldsEnrichAndSortListOrderBeforeHousekeeping(): void
	{
		$config = $this->compilerConfig(['default_fields' => []]);
		$fieldService = $this->createStub(Field::class);
		$fieldService->method('set')->willReturnCallback(
			static function (array &$field): void
			{
				$field['base_name'] = 'field_' . $field['field'];
				$field['type_name'] = 'text';
				$field['settings'] = (object) [
					'history' => null,
					'type_name' => 'text',
				];
			}
		);
		$fieldName = $this->createStub(Name::class);
		$fieldName->method('get')->willReturnCallback(
			static fn(array $field): string => $field['base_name']
		);
		$history = $this->createMock(HistoryInterface::class);
		$history->expects($this->once())->method('get')->with('admin_fields', 93)->willReturn(null);
		$updates = $this->createMock(Updatesql::class);
		$updates->expects($this->never())->method('set');
		$item = (object) [
			'name_single_code' => 'article',
			'name_list_code' => 'articles',
			'addfields_id' => 93,
			'addfields' => json_encode([
				['field' => 11, 'order_list' => 0],
				['field' => 22, 'order_list' => 2],
				['field' => 33, 'order_list' => 1],
			], JSON_THROW_ON_ERROR),
		];
		$subject = new Fields(
			$config,
			new Registry(),
			$history,
			$this->createStub(Customcode::class),
			$fieldService,
			$fieldName,
			new Groups($this->createStub(DatabaseInterface::class)),
			$updates,
			$this->createStub(CMSApplicationInterface::class)
		);

		$subject->set($item);

		$this->assertSame([33, 22, 11], array_column($item->fields, 'field'));
		$this->assertSame(['field_33', 'field_22', 'field_11'], array_column($item->fields, 'base_name'));
		$this->assertObjectNotHasProperty('addfields', $item);
	}
}
