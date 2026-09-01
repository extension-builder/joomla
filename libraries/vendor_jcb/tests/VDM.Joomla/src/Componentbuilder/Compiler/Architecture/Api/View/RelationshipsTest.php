<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\View;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\View\Relationships;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ComponentFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Tags;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The relationships of a view's resource.
 *
 * @since 6.1.7
 */
#[CoversClass(Relationships::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Abstraction')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class RelationshipsTest extends ArchitectureTestCase
{
	/**
	 * The relationship entries of the item view of a well connected view.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ITEM = <<<'GEN'

		'author',
		'owner',
		'catid',
		'partner',
		'external',
		'created_by',
		'modified_by',
		'tags',
GEN;

	/**
	 * The relationship entries of the list view of the same view.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LIST = <<<'GEN'

		'author',
		'owner',
		'catid',
		'partner',
		'external',
		'created_by',
		'modified_by',
GEN;

	/**
	 * A view without fields still relates to the users who created and changed it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutFieldsRelatesToItsUsers(): void
	{
		$subject = $this->renderer(Relationships::class);

		$this->assertSame(
			[
				['name' => 'created_by', 'column' => 'created_by', 'type' => 'users', 'list' => true],
				['name' => 'modified_by', 'column' => 'modified_by', 'type' => 'users', 'list' => true],
			],
			$subject->map('demo', 'demos')
		);
	}

	/**
	 * Every linked, user and category field relates, the tags only for the item.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryLinkedUserAndCategoryFieldRelatesAndTheTagsOnlyForTheItem(): void
	{
		$subject = $this->subject();

		$this->assertSame(self::EXPECTED_ITEM, $subject->get('demo', 'demos'));
		$this->assertSame(self::EXPECTED_LIST, $subject->get('demo', 'demos', false));
	}

	/**
	 * A linked view of this component is typed by its list name, another by its own name, a table by its name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheRelatedTypeIsTheListNameOfTheLinkedView(): void
	{
		$types = array_column($this->subject()->map('demo', 'demos'), 'type', 'name');

		$this->assertSame('authors', $types['author']);
		$this->assertSame('users', $types['owner']);
		$this->assertSame('categories', $types['catid']);
		$this->assertSame('partner_company', $types['partner']);
		$this->assertSame('thing', $types['external']);
		$this->assertSame('tags', $types['tags']);
		$this->assertArrayNotHasKey('note', $types);
		$this->assertArrayNotHasKey('nolink', $types);
	}

	/**
	 * A default user column the view overrides is left to the field that overrides it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnOverriddenDefaultColumnIsNotRelatedTwice(): void
	{
		$names = new FieldNames();
		$names->set('demo.created_by', 'created_by');

		$fields = new ComponentFields();
		$fields->set('demo.created_by', ['name' => 'created_by', 'type' => 'text']);

		$subject = $this->renderer(Relationships::class, ['fieldnames' => $names, 'componentfields' => $fields]);

		$this->assertSame(
			[['name' => 'modified_by', 'column' => 'modified_by', 'type' => 'users', 'list' => true]],
			$subject->map('demo', 'demos')
		);
	}

	/**
	 * A view with linked, user, category, plain and unlinked fields, and tags.
	 *
	 * @return  Relationships
	 * @since   6.1.7
	 */
	private function subject(): Relationships
	{
		$fields = new ComponentFields();
		$fields->set('demo.author', ['name' => 'author', 'type' => 'list', 'link' => [
			'type' => 1, 'table' => '#__demo_author', 'component' => 'com_demo',
			'entity' => 'author', 'value' => 'name', 'key' => 'id',
		]]);
		$fields->set('demo.owner', ['name' => 'owner', 'type' => 'user']);
		$fields->set('demo.catid', ['name' => 'catid', 'type' => 'category']);
		$fields->set('demo.partner', ['name' => 'partner', 'type' => 'list', 'link' => [
			'type' => 1, 'table' => '#__demo_partner_company', 'component' => 'com_demo',
			'entity' => null, 'value' => 'name', 'key' => 'id',
		]]);
		$fields->set('demo.external', ['name' => 'external', 'type' => 'list', 'link' => [
			'type' => 1, 'table' => '#__other_thing', 'component' => 'com_other',
			'entity' => 'thing', 'value' => 'name', 'key' => 'id',
		]]);
		$fields->set('demo.note', ['name' => 'note', 'type' => 'textarea']);
		$fields->set('demo.nolink', ['name' => 'nolink', 'type' => 'list', 'link' => [
			'type' => 2, 'table' => null, 'component' => null, 'entity' => null, 'value' => null, 'key' => null,
		]]);

		$tags = new Tags();
		$tags->set('demo', true);

		$component = new Component(
			(new ReflectionClass(Data::class))->newInstanceWithoutConstructor(),
			$this->createStub(EventInterface::class)
		);
		$component->set('admin_views', [
			['settings' => (object) ['name_single_code' => 'demo', 'name_list_code' => 'demos']],
			['settings' => (object) ['name_single_code' => 'author', 'name_list_code' => 'authors']],
		]);

		return $this->renderer(Relationships::class, [
			'componentfields' => $fields,
			'tags' => $tags,
			'component' => $component,
		]);
	}
}
