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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Field;
use VDM\Joomla\Componentbuilder\Compiler\Field\Data;
use VDM\Joomla\Componentbuilder\Compiler\Field\Name;
use VDM\Joomla\Componentbuilder\Compiler\Field\TypeName;
use VDM\Joomla\Componentbuilder\Compiler\Field\UniqueName;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Field enrichment and uniqueness-registration contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Field::class)]
#[UsesClass(Data::class)]
final class FieldTest extends CompilerDomainTestCase
{
	/**
	 * Enrich a loaded field, normalize numeric permissions, and lock its list-view name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetEnrichesOnlyMissingValuesAndRegistersTheResolvedName(): void
	{
		$name = $this->createMock(Name::class);
		$name->expects($this->once())->method('get')->willReturn('article_title');
		$typeName = $this->createMock(TypeName::class);
		$typeName->expects($this->once())->method('get')->willReturn('text');
		$uniqueName = $this->createMock(UniqueName::class);
		$uniqueName->expects($this->once())
			->method('set')
			->with('article_title', 'articles.edit');
		$settings = (object) ['type_name' => 'text'];
		$field = [
			'field' => 17,
			'hash' => 'stable-hash',
			'settings' => $settings,
			'permission' => '7',
		];
		$subject = new Field(
			$this->inertCompilerCollaborator(Data::class),
			$name,
			$typeName,
			$uniqueName
		);

		$subject->set($field, 'article', 'articles', '.edit');

		$this->assertSame('stable-hash', $field['hash']);
		$this->assertSame($settings, $field['settings']);
		$this->assertSame('article_title', $field['base_name']);
		$this->assertSame('text', $field['type_name']);
		$this->assertSame(['7'], $field['permission']);
	}
}
