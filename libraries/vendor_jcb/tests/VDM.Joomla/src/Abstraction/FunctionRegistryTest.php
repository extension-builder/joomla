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

namespace VDM\Joomla\Tests\Abstraction;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\FunctionRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Utilities\String\ClassfunctionHelper;
use VDM\Tests\Support\FunctionRegistryFixture;
use VDM\Tests\Support\TestCase;


/**
 * Dynamic function-registry lookup and caching tests.
 *
 * @since  6.1.6
 */
#[CoversClass(FunctionRegistry::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ClassfunctionHelper::class)]
final class FunctionRegistryTest extends TestCase
{
	/**
	 * Resolve a missing path through its getter once and cache the result.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDynamicGetterIsInvokedOnceAndCached(): void
	{
		$subject = new FunctionRegistryFixture();

		$this->assertSame('derived:fallback', $subject->get('dynamic_value', 'fallback'));
		$this->assertSame(1, $subject->dynamicCalls);
		$this->assertSame('derived:fallback', $subject->get('dynamic_value', 'different'));
		$this->assertSame('derived:fallback', $subject->dynamic_value);
		$this->assertSame(1, $subject->dynamicCalls);
	}

	/**
	 * Prefer explicitly stored state and return defaults for excluded core getters.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStoredAndExcludedGetterPathsDoNotDispatchDynamically(): void
	{
		$subject = new FunctionRegistryFixture();
		$subject->set('dynamic_value', 'explicit');

		$this->assertSame('explicit', $subject->get('dynamic_value', 'fallback'));
		$this->assertSame(0, $subject->dynamicCalls);
		$this->assertSame('fallback', $subject->get('iterator', 'fallback'));
	}

	/**
	 * Append scalar values into an array path in insertion order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAppendArrayAccumulatesValues(): void
	{
		$subject = new FunctionRegistryFixture();

		$this->assertSame(['alpha'], $subject->appendArray('queue.items', 'alpha'));
		$this->assertSame(['alpha', 'beta'], $subject->appendArray('queue.items', 'beta'));
	}

	/**
	 * Reject magic-property access when neither state nor getter exists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMissingMagicPropertyThrowsUsefulException(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Argument missing_value could not be found as function or path.');

		(new FunctionRegistryFixture())->missing_value;
	}
}
