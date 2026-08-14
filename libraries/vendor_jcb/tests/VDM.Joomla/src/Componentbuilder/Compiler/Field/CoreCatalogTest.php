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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaFive\CoreField as JoomlaFiveCoreField;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaFive\CoreRule as JoomlaFiveCoreRule;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaFour\CoreField as JoomlaFourCoreField;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaFour\CoreRule as JoomlaFourCoreRule;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaSix\CoreField as JoomlaSixCoreField;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaSix\CoreRule as JoomlaSixCoreRule;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaThree\CoreField as JoomlaThreeCoreField;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaThree\CoreRule as JoomlaThreeCoreRule;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Target-version core field and validation-rule catalogue contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeCoreField::class)]
#[CoversClass(JoomlaFourCoreField::class)]
#[CoversClass(JoomlaFiveCoreField::class)]
#[CoversClass(JoomlaSixCoreField::class)]
#[CoversClass(JoomlaThreeCoreRule::class)]
#[CoversClass(JoomlaFourCoreRule::class)]
#[CoversClass(JoomlaFiveCoreRule::class)]
#[CoversClass(JoomlaSixCoreRule::class)]
final class CoreCatalogTest extends CompilerDomainTestCase
{
	/**
	 * Every target exposes its discovered field catalogue with optional normalization.
	 *
	 * @param   class-string  $class  Target implementation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('coreFieldProvider')]
	public function testCoreFieldsPreserveNamesAndOfferLowercaseView(string $class): void
	{
		$subject = new $class();
		$this->setCompilerProperty($subject, 'fields', ['Calendar', 'UserGroupList']);

		$this->assertSame(['Calendar', 'UserGroupList'], $subject->get());
		$this->assertSame(['calendar', 'usergrouplist'], $subject->get(true));
		$this->assertSame(['Calendar', 'UserGroupList'], $subject->get());
	}

	/**
	 * Joomla 3 retains the legacy form-fields fallback while later targets do not.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOnlyJoomlaThreeSearchesLegacyCoreFieldDirectory(): void
	{
		$legacy = new JoomlaThreeCoreField();
		$modern = [new JoomlaFourCoreField(), new JoomlaFiveCoreField(), new JoomlaSixCoreField()];
		$property = new ReflectionProperty(JoomlaThreeCoreField::class, 'paths');

		$this->assertSame(
			[JPATH_LIBRARIES . '/src/Form/Field', JPATH_LIBRARIES . '/joomla/form/fields'],
			$property->getValue($legacy)
		);

		foreach ($modern as $subject)
		{
			$this->assertSame(
				[JPATH_LIBRARIES . '/src/Form/Field'],
				(new ReflectionProperty($subject, 'paths'))->getValue($subject)
			);
		}
	}

	/**
	 * Every target exposes its discovered validation catalogue without mutating it.
	 *
	 * @param   class-string  $class  Target implementation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('coreRuleProvider')]
	public function testCoreRulesPreserveNamesAndOfferLowercaseView(string $class): void
	{
		$subject = new $class();
		$this->setCompilerProperty($subject, 'rules', ['Email', 'Username']);

		$this->assertSame(['Email', 'Username'], $subject->get());
		$this->assertSame(['email', 'username'], $subject->get(true));
		$this->assertSame(['Email', 'Username'], $subject->get());
	}

	/**
	 * Core field target implementations.
	 *
	 * @return  array<string, array{class-string}>
	 * @since   6.1.6
	 */
	public static function coreFieldProvider(): array
	{
		return [
			'Joomla 3' => [JoomlaThreeCoreField::class],
			'Joomla 4' => [JoomlaFourCoreField::class],
			'Joomla 5' => [JoomlaFiveCoreField::class],
			'Joomla 6' => [JoomlaSixCoreField::class]
		];
	}

	/**
	 * Core rule target implementations.
	 *
	 * @return  array<string, array{class-string}>
	 * @since   6.1.6
	 */
	public static function coreRuleProvider(): array
	{
		return [
			'Joomla 3' => [JoomlaThreeCoreRule::class],
			'Joomla 4' => [JoomlaFourCoreRule::class],
			'Joomla 5' => [JoomlaFiveCoreRule::class],
			'Joomla 6' => [JoomlaSixCoreRule::class]
		];
	}
}
