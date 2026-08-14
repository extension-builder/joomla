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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Builder;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AssetsRules;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ConfigFieldsets;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseKeys;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUninstall;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DynamicButtons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ExtensionsParams;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Request;
use VDM\Tests\Support\BuilderRegistryProvider;


/**
 * Focused tests for Builder leaves with class-level addition policies.
 *
 * @since  6.1.6
 */
#[CoversClass(AssetsRules::class)]
#[CoversClass(ConfigFieldsets::class)]
#[CoversClass(DatabaseKeys::class)]
#[CoversClass(DatabaseUninstall::class)]
#[CoversClass(DynamicButtons::class)]
#[CoversClass(ExtensionsParams::class)]
#[CoversClass(Request::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class BuilderPolicyTest extends TestCase
{
	/**
	 * Use array addition when callers defer to the class policy.
	 *
	 * @param   class-string  $class        Builder class.
	 * @param   bool          $uniqueArray  Whether duplicate values are suppressed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProviderExternal(BuilderRegistryProvider::class, 'arrayPolicyBuilders')]
	public function testNullAdditionModeUsesTheLeafArrayPolicy(
		string $class,
		bool $uniqueArray
	): void
	{
		/** @var Registry $subject */
		$subject = new $class();

		$subject->add('items', 'first');
		$subject->add('items', 'second');
		$subject->add('items', 'second');

		$this->assertSame(
			$uniqueArray ? ['first', 'second'] : ['first', 'second', 'second'],
			$subject->get('items')
		);
	}

	/**
	 * Allow a caller to override each class-level array policy explicitly.
	 *
	 * @param   class-string  $class        Builder class.
	 * @param   bool          $uniqueArray  Whether duplicate values are suppressed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProviderExternal(BuilderRegistryProvider::class, 'arrayPolicyBuilders')]
	public function testExplicitStringModeOverridesTheLeafArrayPolicy(
		string $class,
		bool $uniqueArray
	): void
	{
		/** @var Registry $subject */
		$subject = new $class();

		$subject->add('content', 'first', false);
		$subject->add('content', '-second', false);

		$this->assertSame('first-second', $subject->get('content'));
	}
}
