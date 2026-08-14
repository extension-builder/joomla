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
use VDM\Joomla\Abstraction\Registry\Traits\IsArray;
use VDM\Tests\Support\RegistryTraitsFixture;


/**
 * Registry array-type trait tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(IsArray::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class IsArrayTest extends TestCase
{
	/**
	 * Accept empty and populated arrays but reject all non-array paths.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testIsArrayChecksTheResolvedPathType(): void
	{
		$subject = new RegistryTraitsFixture([
			'empty' => [],
			'populated' => ['value'],
			'scalar' => 'value'
		]);

		$this->assertTrue($subject->isArray('empty'));
		$this->assertTrue($subject->isArray('populated'));
		$this->assertFalse($subject->isArray('scalar'));
		$this->assertFalse($subject->isArray('missing'));
		$this->assertFalse($subject->isArray(''));
	}
}
