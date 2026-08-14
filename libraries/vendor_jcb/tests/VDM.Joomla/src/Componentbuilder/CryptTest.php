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

namespace VDM\Joomla\Tests\Componentbuilder;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Crypt;
use VDM\Joomla\Componentbuilder\Crypt\Aes;
use VDM\Joomla\Componentbuilder\Crypt\Aes\Legacy;
use VDM\Joomla\Componentbuilder\Crypt\FOF;
use VDM\Joomla\Componentbuilder\Crypt\Password;
use VDM\Tests\Support\TestCase;


/**
 * Cryptography facade method selection, key caching, and fallback tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Crypt::class)]
final class CryptTest extends TestCase
{
	/**
	 * Route option aliases and explicit implementations to their selected engines.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEncryptRoutesAliasesAndExplicitEngineNames(): void
	{
		$fof = $this->createMock(FOF::class);
		$fof->expects($this->once())
			->method('encrypt')
			->with('alpha', 'basic-key')
			->willReturn('fof-alpha');
		$aes = $this->createMock(Aes::class);
		$aes->expects($this->once())
			->method('encrypt')
			->with('beta', 'explicit-key')
			->willReturn('aes-beta');
		$legacy = $this->createMock(Legacy::class);
		$legacy->expects($this->once())
			->method('encrypt')
			->with('gamma', 'basic-key')
			->willReturn('legacy-gamma');
		$password = $this->createMock(Password::class);
		$password->expects($this->once())->method('get')->with('basic')->willReturn('basic-key');
		$subject = new Crypt($fof, $aes, $legacy, $password);

		$this->assertSame('fof-alpha', $subject->encrypt('alpha', 'basic'));
		$this->assertSame('aes-beta', $subject->encrypt('beta', 'basic.aes', 'explicit-key'));
		$this->assertSame('legacy-gamma', $subject->encrypt('gamma', 'basic.legacy'));
	}

	/**
	 * Cache a resolved key across encryption and decryption for the same key family.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPasswordResolutionIsCachedByMethodPrefix(): void
	{
		$fof = $this->createMock(FOF::class);
		$fof->expects($this->once())->method('encrypt')->with('plain', 'cached-key')->willReturn('cipher');
		$fof->expects($this->once())->method('decrypt')->with('cipher', 'cached-key')->willReturn('plain');
		$password = $this->createMock(Password::class);
		$password->expects($this->once())->method('get')->with('medium')->willReturn('cached-key');
		$subject = $this->subject(fof: $fof, password: $password);

		$this->assertSame('cipher', $subject->encrypt('plain', 'medium'));
		$this->assertSame('plain', $subject->decrypt('cipher', 'medium'));
	}

	/**
	 * Leave plaintext unchanged for encryption and return null for unsupported decryption.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUnsupportedOrUnkeyedMethodsUseSafeFallbacks(): void
	{
		$password = $this->createMock(Password::class);
		$password->expects($this->exactly(2))->method('get')->with('unsupported')->willReturn(null);
		$subject = $this->subject(password: $password);

		$this->assertSame('plain', $subject->encrypt('plain', 'unsupported'));
		$this->assertNull($subject->decrypt('cipher', 'unsupported'));
		$this->assertFalse($subject->exist('unsupported'));
		$this->assertTrue($subject->exist('basic'));
		$this->assertTrue($subject->exist('local.aes'));
	}

	/**
	 * Construct the facade with defaults for engines not under assertion.
	 *
	 * @param   FOF|null       $fof       FOF engine.
	 * @param   Aes|null       $aes       AES engine.
	 * @param   Legacy|null    $legacy    Legacy engine.
	 * @param   Password|null  $password  Password provider.
	 *
	 * @return  Crypt
	 * @since   6.1.6
	 */
	private function subject(
		?FOF $fof = null,
		?Aes $aes = null,
		?Legacy $legacy = null,
		?Password $password = null
	): Crypt
	{
		return new Crypt(
			$fof ?? $this->createStub(FOF::class),
			$aes ?? $this->createStub(Aes::class),
			$legacy ?? $this->createStub(Legacy::class),
			$password ?? $this->createStub(Password::class)
		);
	}
}
