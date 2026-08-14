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
use VDM\Joomla\Abstraction\Registry\Traits\GetString;
use VDM\Tests\Support\RegistryTraitsFixture;


/**
 * Registry string accessor trait tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(GetString::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class GetStringTest extends TestCase
{
	/**
	 * Return only non-empty strings and otherwise preserve the supplied default.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetStringRejectsEmptyAndNonStringValues(): void
	{
		$subject = new RegistryTraitsFixture([
			'valid' => 'value',
			'empty' => '',
			'number' => 7,
			'array' => ['value']
		]);

		$this->assertSame('value', $subject->getString('valid', 'fallback'));
		$this->assertSame('fallback', $subject->getString('empty', 'fallback'));
		$this->assertSame('fallback', $subject->getString('number', 'fallback'));
		$this->assertSame('fallback', $subject->getString('array', 'fallback'));
		$this->assertSame('fallback', $subject->getString('missing', 'fallback'));
		$this->assertSame('fallback', $subject->getString('', 'fallback'));
		$this->assertNull($subject->getString('missing'));
	}
}
