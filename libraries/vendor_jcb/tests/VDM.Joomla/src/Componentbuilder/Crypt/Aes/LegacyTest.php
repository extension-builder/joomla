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

namespace VDM\Joomla\Tests\Componentbuilder\Crypt\Aes;


use phpseclib3\Crypt\AES as BaseAes;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Crypt\Aes\Legacy;
use VDM\Tests\Support\TestCase;


/**
 * Legacy deterministic AES envelope compatibility tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Legacy::class)]
final class LegacyTest extends TestCase
{
	/**
	 * Retain deterministic legacy ciphertext and round-trip content.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEncryptAndDecryptRoundTripDeterministically(): void
	{
		$subject = new Legacy(new BaseAes('cbc'));
		$first = $subject->encrypt('legacy-content', 'legacy-key');
		$second = $subject->encrypt('legacy-content', 'legacy-key');

		$this->assertSame($first, $second);
		$this->assertSame('legacy-content', $subject->decrypt($first, 'legacy-key'));
		$this->assertNull($subject->decrypt($first, 'wrong-key'));
	}
}
