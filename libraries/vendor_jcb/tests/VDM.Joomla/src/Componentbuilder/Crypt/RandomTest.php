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

namespace VDM\Joomla\Tests\Componentbuilder\Crypt;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Crypt\Random;
use VDM\Tests\Support\TestCase;


/**
 * Cryptographic random-byte adapter tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Random::class)]
final class RandomTest extends TestCase
{
	/**
	 * Return the requested number of non-reused random bytes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStringReturnsRequestedRandomByteLength(): void
	{
		$first = Random::string(32);
		$second = Random::string(32);

		$this->assertSame(32, strlen($first));
		$this->assertSame(32, strlen($second));
		$this->assertNotSame($first, $second);
		$this->assertSame('', Random::string(0));
	}
}
