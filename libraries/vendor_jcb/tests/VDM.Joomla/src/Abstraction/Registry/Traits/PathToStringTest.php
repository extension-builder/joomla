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
use VDM\Joomla\Abstraction\Registry\Traits\PathToString;
use VDM\Tests\Support\RegistryTraitsFixture;


/**
 * Registry path-to-string trait tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(PathToString::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class PathToStringTest extends TestCase
{
	/**
	 * Join arrays, preserve strings, and reject unsupported or empty values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPathToStringNormalizesOnlyArraysAndStrings(): void
	{
		$subject = new RegistryTraitsFixture([
			'array' => ['first', 'second'],
			'emptyArray' => [],
			'string' => 'value',
			'emptyString' => '',
			'number' => 7
		]);

		$this->assertSame('first|second', $subject->pathToString('array', '|'));
		$this->assertSame('value', $subject->pathToString('string', '|'));
		$this->assertSame('', $subject->pathToString('emptyArray', '|'));
		$this->assertSame('', $subject->pathToString('emptyString', '|'));
		$this->assertSame('', $subject->pathToString('number', '|'));
		$this->assertSame('', $subject->pathToString('missing', '|'));
		$this->assertSame('', $subject->pathToString('', '|'));
	}
}
