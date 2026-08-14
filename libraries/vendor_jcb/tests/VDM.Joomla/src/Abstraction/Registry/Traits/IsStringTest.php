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
use VDM\Joomla\Abstraction\Registry\Traits\IsString;
use VDM\Tests\Support\RegistryTraitsFixture;


/**
 * Registry string-type trait tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(IsString::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class IsStringTest extends TestCase
{
	/**
	 * Accept non-empty strings but reject empty strings and other types.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testIsStringChecksTheResolvedPathTypeAndContent(): void
	{
		$subject = new RegistryTraitsFixture([
			'valid' => 'value',
			'empty' => '',
			'number' => 7,
			'array' => ['value']
		]);

		$this->assertTrue($subject->isString('valid'));
		$this->assertFalse($subject->isString('empty'));
		$this->assertFalse($subject->isString('number'));
		$this->assertFalse($subject->isString('array'));
		$this->assertFalse($subject->isString('missing'));
		$this->assertFalse($subject->isString(''));
	}
}
