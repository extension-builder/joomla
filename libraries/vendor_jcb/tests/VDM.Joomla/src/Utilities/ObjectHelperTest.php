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

namespace VDM\Joomla\Tests\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use VDM\Joomla\Utilities\ObjectHelper;


/**
 * Object population and structural-equality contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(ObjectHelper::class)]
final class ObjectHelperTest extends TestCase
{
	/**
	 * Distinguish populated objects from empty objects and non-object values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCheckRequiresAnObjectWithAtLeastOneProperty(): void
	{
		$populated = new stdClass();
		$populated->value = null;

		$this->assertTrue(ObjectHelper::check($populated));
		$this->assertFalse(ObjectHelper::check(new stdClass()));
		$this->assertFalse(ObjectHelper::check(['value' => 1]));
		$this->assertFalse(ObjectHelper::check(null));
	}

	/**
	 * Compare nested object structure independently of property insertion order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEqualNormalizesNestedPropertyOrder(): void
	{
		$first = (object) [
			'name' => 'JCB',
			'metadata' => (object) ['version' => 6, 'stable' => true]
		];
		$second = (object) [
			'metadata' => (object) ['stable' => true, 'version' => 6],
			'name' => 'JCB'
		];

		$this->assertTrue(ObjectHelper::equal($first, $second));
	}

	/**
	 * Remove ignored keys recursively before comparing remaining structure.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEqualAppliesIgnoredKeysAtEveryDepth(): void
	{
		$first = (object) [
			'id' => 10,
			'name' => 'JCB',
			'nested' => (object) ['id' => 11, 'value' => 'same']
		];
		$second = (object) [
			'id' => 20,
			'name' => 'JCB',
			'nested' => (object) ['id' => 21, 'value' => 'same']
		];

		$this->assertFalse(ObjectHelper::equal($first, $second));
		$this->assertTrue(ObjectHelper::equal($first, $second, ['id']));
	}

	/**
	 * Preserve strict scalar types and reject null operands.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEqualRejectsTypeDifferencesAndNullOperands(): void
	{
		$this->assertFalse(ObjectHelper::equal((object) ['value' => 1], (object) ['value' => '1']));
		$this->assertFalse(ObjectHelper::equal(null, (object) ['value' => 1]));
		$this->assertFalse(ObjectHelper::equal((object) ['value' => 1], null));
		$this->assertFalse(ObjectHelper::equal(null, null));
	}
}
