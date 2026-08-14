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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Extension;


use Generator;
use ReflectionProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ExtensionCustomFields;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaFive\MoveFieldsRules as JoomlaFiveMoveFieldsRules;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaFour\MoveFieldsRules as JoomlaFourMoveFieldsRules;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaSix\MoveFieldsRules as JoomlaSixMoveFieldsRules;
use VDM\Joomla\Componentbuilder\Compiler\Extension\JoomlaThree\MoveFieldsRules as JoomlaThreeMoveFieldsRules;
use VDM\Joomla\Componentbuilder\Compiler\Field;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\MoveFieldsRulesInterface;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Joomla-version extension field/rule relocation contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeMoveFieldsRules::class)]
#[CoversClass(JoomlaFourMoveFieldsRules::class)]
#[CoversClass(JoomlaFiveMoveFieldsRules::class)]
#[CoversClass(JoomlaSixMoveFieldsRules::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ExtensionCustomFields::class)]
final class MoveFieldsRulesTest extends CompilerDomainTestCase
{
	/**
	 * Custom field and linked-rule work is de-duplicated by destination on every target.
	 *
	 * @param   class-string<MoveFieldsRulesInterface>  $class  Target implementation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('targetProvider')]
	public function testMoveTracksCustomFieldAndLinkedRuleOnce(string $class): void
	{
		$registry = new Registry();
		$registry->set('validation.linked.7', 'slug');
		$customFields = new ExtensionCustomFields();
		$customFields->set('Fancy', true);
		$paths = $this->createMock(Paths::class);
		$paths->method('__get')->with('component_path')->willReturn('/not/a/component');
		$subject = new $class(
			$registry,
			$this->createStub(Field::class),
			$customFields,
			$paths
		);
		$field = ['id' => 7, 'type_name' => 'Fancy'];

		$subject->move($field, '/not/an/extension');
		$first = (new ReflectionProperty($subject, 'extensionTrackingFilesMoved'))->getValue($subject);
		$subject->move($field, '/not/an/extension');
		$second = (new ReflectionProperty($subject, 'extensionTrackingFilesMoved'))->getValue($subject);

		$this->assertCount(2, $first);
		$this->assertSame($first, $second);
		$this->assertContains(true, $second, true);
	}

	/**
	 * Fields without a custom type or validation link are a clean no-op.
	 *
	 * @param   class-string<MoveFieldsRulesInterface>  $class  Target implementation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('targetProvider')]
	public function testOrdinaryUnlinkedFieldDoesNotCreateTrackingEntries(string $class): void
	{
		$paths = $this->createMock(Paths::class);
		$paths->expects($this->never())->method('__get');
		$subject = new $class(
			new Registry(),
			$this->createStub(Field::class),
			new ExtensionCustomFields(),
			$paths
		);

		$subject->move(['id' => 9, 'type_name' => 'text'], '/not/an/extension');

		$this->assertSame(
			[],
			(new ReflectionProperty($subject, 'extensionTrackingFilesMoved'))->getValue($subject)
		);
	}

	/**
	 * Relocation implementation matrix.
	 *
	 * @return  Generator<string, array{class-string<MoveFieldsRulesInterface>}>
	 * @since   6.1.6
	 */
	public static function targetProvider(): Generator
	{
		yield 'Joomla 3' => [JoomlaThreeMoveFieldsRules::class];
		yield 'Joomla 4' => [JoomlaFourMoveFieldsRules::class];
		yield 'Joomla 5' => [JoomlaFiveMoveFieldsRules::class];
		yield 'Joomla 6' => [JoomlaSixMoveFieldsRules::class];
	}
}
