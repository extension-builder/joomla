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
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DoNotEscape;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListFieldClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Field\Attributes;
use VDM\Joomla\Componentbuilder\Compiler\Field\Groups;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Field XML-to-attribute modelling contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Attributes::class)]
#[UsesClass(Groups::class)]
#[UsesClass(Placeholder::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ListFieldClass::class)]
#[UsesClass(DoNotEscape::class)]
final class AttributesTest extends CompilerDomainTestCase
{
	/**
	 * Plain field XML is modeled into attributes and linked builder state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetModelsAttributesReferencesAndViewTypeOverrides(): void
	{
		$listFieldClass = new ListFieldClass();
		$doNotEscape = new DoNotEscape();
		$subject = new Attributes(
			$this->compilerConfig(['lang_target' => 'admin']),
			new Registry(),
			$listFieldClass,
			$doNotEscape,
			new Placeholder($this->compilerConfig()),
			$this->createStub(Customcode::class),
			$this->inertCompilerCollaborator(Language::class),
			new Groups($this->createStub(DatabaseInterface::class))
		);
		$field = [
			'settings' => (object) [
				'xml' => '<field name="title" type="text" label="Article Title" multiple="true" readonly="false" listclass="highlight" escape="false" display="both" validate="email" default="" />',
				'properties' => [
					['name' => 'name', 'example' => 'title', 'translatable' => 0, 'mandatory' => 1],
					['name' => 'type', 'example' => 'text', 'translatable' => 0, 'mandatory' => 1],
					['name' => 'label', 'example' => 'Title', 'translatable' => 0, 'mandatory' => 1],
					['name' => 'multiple', 'example' => 'false', 'translatable' => 0, 'mandatory' => 0],
					['name' => 'readonly', 'example' => 'false', 'translatable' => 0, 'mandatory' => 0],
					['name' => 'default', 'example' => 'fallback', 'translatable' => 0, 'mandatory' => 0]
				]
			]
		];
		$multiple = false;
		$langLabel = 'OLD_LABEL';

		$attributes = $subject->set(
			$field,
			2,
			'title',
			'text',
			$multiple,
			$langLabel,
			'COM_DEMO_ARTICLE',
			'articles',
			'article',
			[]
		);

		$this->assertSame('title', $attributes['name']);
		$this->assertSame('text', $attributes['type']);
		$this->assertSame('Article Title', $attributes['label']);
		$this->assertSame('true', $attributes['multiple']);
		$this->assertSame('true', $attributes['readonly']);
		$this->assertSame('', $attributes['default']);
		$this->assertSame('both', $attributes['display']);
		$this->assertSame('email', $attributes['validate']);
		$this->assertTrue($multiple);
		$this->assertSame('Article Title', $langLabel);
		$this->assertSame('highlight', $listFieldClass->get('articles.title'));
		$this->assertTrue($doNotEscape->get('articles.title'));
	}

	/**
	 * Missing settings and missing properties remain explicit empty definitions.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRejectsDefinitionsWithoutSettingsOrProperties(): void
	{
		$subject = new Attributes(
			$this->compilerConfig(),
			new Registry(),
			new ListFieldClass(),
			new DoNotEscape(),
			new Placeholder($this->compilerConfig()),
			$this->createStub(Customcode::class),
			$this->inertCompilerCollaborator(Language::class),
			new Groups($this->createStub(DatabaseInterface::class))
		);
		$multiple = false;
		$langLabel = '';

		$this->assertSame([], $subject->set([], 1, 'title', 'text', $multiple, $langLabel, '', '', '', []));
		$this->assertSame(
			[],
			$subject->set(['settings' => (object) []], 1, 'title', 'text', $multiple, $langLabel, '', '', '', [])
		);
	}
}
