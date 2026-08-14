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

namespace VDM\Joomla\Tests\Componentbuilder\Abstraction;

use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Abstraction\BaseRegistry;
use VDM\Tests\Support\BaseRegistryFixture;
use VDM\Tests\Support\TestCase;

/**
 * Legacy Component Builder registry iteration and type-policy tests.
 *
 * @since  6.1.6
 */
#[CoversClass(BaseRegistry::class)]
final class BaseRegistryTest extends TestCase
{
	/**
	 * Iterate an extracted subtree without leaking unrelated registry state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPathIteratorReturnsOnlyExtractedSubtree(): void
	{
		$subject = new BaseRegistryFixture(
			[
				'compiler' => [
					'targets' => ['admin' => 'administrator', 'site' => 'site'],
					'version' => 6,
				],
				'outside' => true,
			]
		);

		$this->assertSame(
			['admin' => 'administrator', 'site' => 'site'],
			iterator_to_array($subject->_('compiler.targets'))
		);
		$this->assertSame([], iterator_to_array($subject->_('compiler.missing')));
	}

	/**
	 * Initialize absent arrays, append in order, and preserve existing entries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAppendArrayAccumulatesValuesAtMissingAndExistingPaths(): void
	{
		$subject = new BaseRegistryFixture();

		$this->assertSame('first', $subject->appendArray('queue.items', 'first'));
		$this->assertSame('second', $subject->appendArray('queue.items', 'second'));
		$this->assertSame(['first', 'second'], $subject->get('queue.items'));
	}

	/**
	 * Distinguish non-empty array, string, and numeric registry values exactly.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTypePredicatesRejectEmptyAndWrongTypeValues(): void
	{
		$subject = new BaseRegistryFixture(
			[
				'array' => ['value'],
				'empty_array' => [],
				'string' => 'value',
				'empty_string' => '',
				'integer' => 42,
				'numeric_string' => '3.14',
			]
		);

		$this->assertTrue($subject->isArray('array'));
		$this->assertFalse($subject->isArray('empty_array'));
		$this->assertTrue($subject->isString('string'));
		$this->assertFalse($subject->isString('empty_string'));
		$this->assertTrue($subject->isNumeric('integer'));
		$this->assertTrue($subject->isNumeric('numeric_string'));
		$this->assertFalse($subject->isNumeric('string'));
		$this->assertFalse($subject->isArray(''));
	}
}
