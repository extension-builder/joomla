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
use PHPUnit\Framework\Attributes\Group;
use VDM\Joomla\Componentbuilder\Crypt\FOF;
use VDM\Joomla\FOF\Encrypt\AES;
use VDM\Tests\Support\TestCase;


/**
 * FOF-compatible encryption adapter tests.
 *
 * @since  6.1.6
 */
#[CoversClass(FOF::class)]
final class FOFTest extends TestCase
{
	/**
	 * Decrypt ciphertext produced by the underlying FOF AES implementation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDecryptReadsFofCiphertext(): void
	{
		$cipher = (new AES('shared-key', 128))->encryptString('component-data!!');

		$this->assertSame('component-data!!', (new FOF())->decrypt($cipher, 'shared-key'));
	}

	/**
	 * Encryption must produce ciphertext that the same adapter can recover.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testEncryptProducesRoundTrippableCiphertext(): void
	{
		$subject = new FOF();
		$cipher = $subject->encrypt('component-data', 'shared-key');

		$this->assertNotSame('', $cipher);
		$this->assertSame('component-data', $subject->decrypt($cipher, 'shared-key'));
	}
}
