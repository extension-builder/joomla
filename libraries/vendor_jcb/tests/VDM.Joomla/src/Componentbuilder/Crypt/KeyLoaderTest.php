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


use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Crypt\KeyLoader;
use VDM\Tests\Support\TestCase;


/**
 * Public-key loader compatibility tests.
 *
 * @since  6.1.6
 */
#[CoversClass(KeyLoader::class)]
final class KeyLoaderTest extends TestCase
{
	/**
	 * Load matching RSA private and public keys and preserve signing capability.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLoadPreservesPrivateAndPublicKeyCapabilities(): void
	{
		$generated = RSA::createKey(1024);
		$private = KeyLoader::load($generated->toString('PKCS8'));
		$public = KeyLoader::load($generated->getPublicKey()->toString('PKCS8'));
		$signature = $private->sign('JCB payload');

		$this->assertInstanceOf(PrivateKey::class, $private);
		$this->assertInstanceOf(PublicKey::class, $public);
		$this->assertTrue($public->verify('JCB payload', $signature));
		$this->assertFalse($public->verify('changed payload', $signature));
	}
}
