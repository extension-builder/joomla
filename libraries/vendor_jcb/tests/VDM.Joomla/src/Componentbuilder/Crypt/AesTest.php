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


use phpseclib3\Crypt\AES as BaseAes;
use phpseclib3\Exception\BadDecryptionException;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Crypt\Aes;
use VDM\Joomla\Componentbuilder\Crypt\Random;
use VDM\Tests\Support\TestCase;


/**
 * AES-CBC encryption envelope and decryption failure tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Aes::class)]
final class AesTest extends TestCase
{
	/**
	 * Round-trip UTF-8 and binary content with a random IV embedded in the envelope.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEncryptAndDecryptRoundTripWithRandomEnvelope(): void
	{
		$subject = new Aes(new BaseAes('cbc'), new Random());
		$plain = "JCB compiler — \0 binary";
		$first = $subject->encrypt($plain, 'strong-key');
		$second = $subject->encrypt($plain, 'strong-key');

		$this->assertNotSame($first, $second);
		$this->assertSame($plain, $subject->decrypt($first, 'strong-key'));
		$this->assertSame($plain, $subject->decrypt($second, 'strong-key'));
		$this->assertGreaterThan(16, strlen(base64_decode($first, true)));
	}

	/**
	 * Return null when the cipher rejects a payload during decryption.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDecryptReturnsNullWhenCipherRejectsPayload(): void
	{
		$cipher = $this->getMockBuilder(BaseAes::class)
			->disableOriginalConstructor()
			->onlyMethods([
				'setKeyLength',
				'enablePadding',
				'getBlockLength',
				'setIV',
				'setPassword',
				'decrypt'
			])
			->getMock();
		$cipher->expects($this->once())->method('setKeyLength')->with(256);
		$cipher->expects($this->once())->method('enablePadding');
		$cipher->expects($this->once())->method('getBlockLength')->willReturn(128);
		$cipher->expects($this->once())->method('setIV')->with('0123456789abcdef');
		$cipher->expects($this->once())
			->method('setPassword')
			->with('wrong-key', 'pbkdf2', 'sha256', 'VastDevelopmentMethod/salt')
			->willReturn(true);
		$cipher->expects($this->once())
			->method('decrypt')
			->with('ciphertext')
			->willThrowException(new BadDecryptionException('invalid padding'));
		$subject = new Aes($cipher, new Random());

		$this->assertNull(
			$subject->decrypt(base64_encode('0123456789abcdef' . 'ciphertext'), 'wrong-key')
		);
	}
}
