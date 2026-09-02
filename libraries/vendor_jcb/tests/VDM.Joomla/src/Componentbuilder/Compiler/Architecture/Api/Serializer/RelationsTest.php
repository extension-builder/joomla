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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Serializer;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Serializer\Relations;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\View\Relationships;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ComponentFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Tags;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The relationship methods of a resource serializer.
 *
 * @since 6.1.7
 */
#[CoversClass(Relations::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Abstraction')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class RelationsTest extends ArchitectureTestCase
{
	/**
	 * The methods of a view with a linked field and tags, its user columns overridden.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED = <<<'GEN'

	use TagApiSerializerTrait;

	/**
	 * Build the author relationship.
	 *
	 * @param   \stdClass  $item  The item.
	 *
	 * @return  Relationship
	 *
	 * @since   4.0.0
	 */
	public function author($item)
	{
		// Relate the author to the authors resource.
		return $this->related($item->author ?? null, 'authors');
	}
GEN;

	/**
	 * A view relating to nothing gets no method.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewRelatingToNothingGetsNoMethod(): void
	{
		$names = new FieldNames();
		$names->set('demo.created_by', 'created_by');
		$names->set('demo.modified_by', 'modified_by');

		$subject = new Relations($this->renderer(Relationships::class, ['fieldnames' => $names]));

		$this->assertSame('', $subject->get('demo', 'demos'));
	}

	/**
	 * A linked field gets its method and the tags come from Joomla's trait.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testALinkedFieldGetsItsMethodAndTheTagsComeFromTheTrait(): void
	{
		$names = new FieldNames();
		$names->set('demo.created_by', 'created_by');
		$names->set('demo.modified_by', 'modified_by');

		$fields = new ComponentFields();
		$fields->set('demo.author', ['name' => 'author', 'type' => 'list', 'link' => [
			'type' => 1, 'table' => '#__demo_author', 'component' => 'com_demo',
			'entity' => 'author', 'value' => 'name', 'key' => 'id',
		]]);

		$tags = new Tags();
		$tags->set('demo', true);

		$component = new Component(
			(new ReflectionClass(Data::class))->newInstanceWithoutConstructor(),
			$this->createStub(EventInterface::class)
		);
		$component->set('admin_views', [
			['settings' => (object) ['name_single_code' => 'author', 'name_list_code' => 'authors']],
		]);

		$subject = new Relations($this->renderer(Relationships::class, [
			'fieldnames' => $names,
			'componentfields' => $fields,
			'tags' => $tags,
			'component' => $component,
		]));

		$this->assertSame(self::EXPECTED, $subject->get('demo', 'demos'));
	}

	/**
	 * The method is named the way Joomla's serializer looks it up.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheMethodIsNamedTheWayTheSerializerLooksItUp(): void
	{
		$fields = new ComponentFields();
		$fields->set('demo.main_author', ['name' => 'main_author', 'type' => 'user']);

		$subject = new Relations($this->renderer(Relationships::class, ['componentfields' => $fields]));
		$code = $subject->get('demo', 'demos');

		$this->assertStringContainsString('public function mainAuthor($item)', $code);
		$this->assertStringContainsString("return \$this->related(\$item->main_author ?? null, 'users');", $code);
		$this->assertStringContainsString('// Relate the main_author to the users resource.', $code);
		$this->assertStringContainsString('public function createdBy($item)', $code);
		$this->assertStringContainsString('public function modifiedBy($item)', $code);
		$this->assertStringNotContainsString('TagApiSerializerTrait', $code);
	}
}
