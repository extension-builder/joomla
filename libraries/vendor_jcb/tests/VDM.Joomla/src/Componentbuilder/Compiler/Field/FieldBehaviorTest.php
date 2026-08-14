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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Field;


use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryOtherName;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Lists;
use VDM\Joomla\Componentbuilder\Compiler\Customcode as CompilerCustomcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Field\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Field\DatabaseName;
use VDM\Joomla\Componentbuilder\Compiler\Field\Groups;
use VDM\Joomla\Componentbuilder\Compiler\Field\Name;
use VDM\Joomla\Componentbuilder\Compiler\Field\Rule;
use VDM\Joomla\Componentbuilder\Compiler\Field\TypeName;
use VDM\Joomla\Componentbuilder\Compiler\Field\UniqueName;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Field\CoreRuleInterface;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Field naming, grouping, database-column, custom-code, and rule contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(UniqueName::class)]
#[CoversClass(TypeName::class)]
#[CoversClass(DatabaseName::class)]
#[CoversClass(Groups::class)]
#[CoversClass(Name::class)]
#[CoversClass(Customcode::class)]
#[CoversClass(Rule::class)]
#[UsesClass(Registry::class)]
#[UsesClass(Lists::class)]
#[UsesClass(CategoryOtherName::class)]
#[UsesClass(Placeholder::class)]
final class FieldBehaviorTest extends CompilerDomainTestCase
{
	/**
	 * Reused names are allocated monotonically within a view and isolated across views.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUniqueNameAllocatesStableViewScopedSuffixes(): void
	{
		$subject = new UniqueName(new Registry());

		$subject->set('title', 'articles');
		$subject->set('title', 'articles');

		$this->assertSame('title_1', $subject->get('title', 'articles'));
		$this->assertSame('title_2', $subject->get('title', 'articles'));
		$this->assertSame('title', $subject->get('title', 'categories'));
	}

	/**
	 * Type extraction honors custom XML types, declared examples, and the text fallback.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTypeNameModelsCustomRegularAndIncompleteDefinitions(): void
	{
		$subject = new TypeName();
		$custom = [
			'settings' => (object) [
				'type_name' => 'Acme@Modal',
				'xml' => '<field type="modal_select" />',
				'properties' => [['name' => 'type', 'example' => 'ignored']]
			]
		];
		$regular = [
			'settings' => (object) [
				'type_name' => 'List',
				'xml' => '<field type="fallback" />',
				'properties' => [['name' => 'type', 'example' => 'GroupedList']]
			]
		];

		$this->assertSame('modal_select', $subject->get($custom));
		$this->assertSame('Acme@Modal', $custom['settings']->own_custom);
		$this->assertSame('Custom', $custom['settings']->type_name);
		$this->assertSame('GroupedList', $subject->get($regular));

		$incomplete = [];
		$this->assertSame('text', $subject->get($incomplete));
	}

	/**
	 * Database-name resolution handles built-ins, categories, custom joins, IDs, and GUIDs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDatabaseNameResolvesCompilerFieldShapes(): void
	{
		$guid = '123e4567-e89b-12d3-a456-426614174000';
		$lists = new Lists();
		$lists->set('articles', [
			['id' => 7, 'guid' => $guid, 'type' => 'text', 'code' => 'title'],
			['id' => 8, 'guid' => '623e4567-e89b-12d3-a456-426614174000', 'type' => 'category', 'code' => 'catid'],
			['id' => 9, 'guid' => '723e4567-e89b-12d3-a456-426614174000', 'type' => 'list', 'custom' => ['db' => 'u', 'text' => 'name']]
		]);
		$subject = new DatabaseName($lists, new Registry());

		$this->assertSame('a.id', $subject->get('articles', -1));
		$this->assertSame('a.ordering', $subject->get('articles', -2));
		$this->assertSame('a.published', $subject->get('articles', -3));
		$this->assertSame('a.title', $subject->get('articles', 7));
		$this->assertSame('a.title', $subject->get('articles', $guid));
		$this->assertSame('c.title', $subject->get('articles', 8));
		$this->assertSame('u.name', $subject->get('articles', 9));
		$this->assertNull($subject->get('missing', 7));
		$this->assertNull($subject->get('articles', 'not-an-identifier'));
	}

	/**
	 * The field-group catalogue differentiates plain, option, spacer, and unknown types.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGroupsProtectSemanticFieldTypeMembership(): void
	{
		$subject = new Groups($this->createStub(DatabaseInterface::class));

		$this->assertTrue($subject->check('text'));
		$this->assertTrue($subject->check('text', 'plain'));
		$this->assertFalse($subject->check('text', 'option'));
		$this->assertTrue($subject->check('spacer', 'spacer'));
		$this->assertFalse($subject->check('spacer', 'search'));
		$this->assertFalse($subject->check('text', 'unknown'));
		$this->assertNull($subject->typesIds(['unknown']));
		$this->assertNull($subject->typesGuids([]));
	}

	/**
	 * Field names normalize aliases and category relationships and remain stable by hash.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testNameModelsCapitalizedCategoryAndRelationshipMetadata(): void
	{
		$config = $this->compilerConfig();
		$placeholder = new Placeholder($config);
		$categoryOtherName = new CategoryOtherName();
		$subject = new Name($placeholder, new UniqueName(new Registry()), $categoryOtherName);
		$category = [
			'hash' => 'category-field',
			'settings' => (object) [
				'type_name' => 'Category',
				'name' => 'Category',
				'xml' => '<field name="category" othername="Section" views="sections" view="section" />',
				'properties' => [['name' => 'name']]
			]
		];
		$alias = [
			'alias' => 1,
			'settings' => (object) [
				'type_name' => 'Text',
				'name' => 'Slug',
				'xml' => '<field name="slug" />',
				'properties' => [['name' => 'name']]
			]
		];

		$this->assertSame('catid', $subject->get($category, 'articles'));
		$this->assertSame('catid', $subject->get($category, 'articles'));
		$this->assertSame(
			['name' => 'section', 'views' => 'sections', 'view' => 'section'],
			$categoryOtherName->get('articles')
		);
		$this->assertSame('alias', $subject->get($alias));
	}

	/**
	 * Alias fields always use Joomla's canonical alias storage name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNameModelsAliasFields(): void
	{
		$config = $this->compilerConfig();
		$subject = new Name(
			new Placeholder($config),
			new UniqueName(new Registry()),
			new CategoryOtherName()
		);
		$field = [
			'alias' => 1,
			'settings' => (object) [
				'type_name' => 'text',
				'name' => 'Slug',
				'xml' => '<field name="slug" />',
				'properties' => [['name' => 'name']]
			]
		];

		$this->assertSame('alias', $subject->get($field));
	}

	/**
	 * Single-view custom code is decoded once per field/view and requests token support.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomcodeLoadsSingleViewScriptOnceAndMarksTokenRequirement(): void
	{
		$dispenser = $this->createMock(Dispenser::class);
		$dispenser->hub = [];
		$dispenser->expects($this->once())
			->method('set')
			->with(
				'tokenized javascript',
				'view_footer',
				'article',
				null,
				$this->callback(static fn(array $config): bool => $config['id'] === 17 && $config['field'] === 'javascript_view_footer'),
				true,
				true,
				true
			)
			->willReturn(true);
		$subject = new Customcode($dispenser);
		$field = $this->customCodeField();
		$field->add_javascript_view_footer = 1;
		$field->javascript_view_footer = 'tokenized javascript';

		$subject->update(17, $field, 'article', null);
		$subject->update(17, $field, 'article', null);

		$this->assertTrue($field->javascript_view_footer_decoded);
		$this->assertTrue($dispenser->hub['token']['article']);
	}

	/**
	 * List-view scripts must be stored under the list view, not the single-view key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testListViewCustomCodeUsesListViewRegistryKey(): void
	{
		$dispenser = $this->createMock(Dispenser::class);
		$dispenser->hub = [];
		$dispenser->expects($this->once())
			->method('set')
			->with(
				'list javascript',
				'views_footer',
				'articles',
				null,
				$this->isArray(),
				true,
				true,
				true
			)
			->willReturn(true);
		$subject = new Customcode($dispenser);
		$field = $this->customCodeField();
		$field->add_javascript_views_footer = 1;
		$field->javascript_views_footer = 'list javascript';

		$subject->update(18, $field, null, 'articles');
	}

	/**
	 * Core rules remain recorded on the field but are not linked as copied custom rules.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRuleRecordsCoreValidationWithoutCreatingCustomRule(): void
	{
		$coreRules = $this->createStub(CoreRuleInterface::class);
		$coreRules->method('get')->willReturn(['email', 'username']);
		$registry = new Registry();
		$subject = new Rule(
			$registry,
			$this->createStub(CompilerCustomcode::class),
			$this->inertCompilerCollaborator(Gui::class),
			new Placeholder($this->compilerConfig()),
			$coreRules
		);

		$subject->set(42, '<field name="email" validate="Email" />');

		$this->assertSame('email', $registry->get('validation.field.42'));
		$this->assertNull($registry->get('validation.linked.42'));
		$this->assertNull($registry->get('validation.rules.email'));
	}

	/**
	 * Create a complete no-op custom-code field payload.
	 *
	 * @return  object
	 * @since   6.1.6
	 */
	private function customCodeField(): object
	{
		return (object) [
			'add_javascript_view_footer' => 0,
			'javascript_view_footer' => '',
			'add_css_view' => 0,
			'css_view' => '',
			'add_javascript_views_footer' => 0,
			'javascript_views_footer' => '',
			'add_css_views' => 0,
			'css_views' => ''
		];
	}
}
