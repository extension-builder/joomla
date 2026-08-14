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

namespace VDM\Joomla\Tests\Abstraction\Registry\Traits;


use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Abstraction\Registry\Traits\InArray;
use VDM\Tests\Support\RegistryTraitsFixture;


/**
 * Registry array-membership trait tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(InArray::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class InArrayTest extends TestCase
{
	/**
	 * Search either the root registry values or a selected array node.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInArraySupportsRootAndNestedMembershipChecks(): void
	{
		$subject = new RegistryTraitsFixture([
			'rootValue' => 'needle',
			'nested' => ['first', 'second'],
			'scalar' => 'not-an-array'
		]);

		$this->assertTrue($subject->inArray('needle'));
		$this->assertTrue($subject->inArray('second', 'nested'));
		$this->assertFalse($subject->inArray('missing'));
		$this->assertFalse($subject->inArray('needle', 'nested'));
		$this->assertFalse($subject->inArray('not-an-array', 'scalar'));
		$this->assertFalse($subject->inArray('anything', 'missing'));
	}
}
