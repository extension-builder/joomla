<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use ErrorException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use VDM\Joomla\FOF\Encrypt\AES;
use VDM\Joomla\FOF\Encrypt\AES\AesInterface;
use VDM\Joomla\FOF\Encrypt\AES\Mcrypt;
use VDM\Joomla\FOF\Encrypt\AES\Openssl;
use VDM\Joomla\FOF\Utils\Phpfunc;


/**
 * Preserve legacy AES ciphertext and optional function-adapter contracts.
 *
 * FOF is distributed with JCB but is outside the coverage ownership roots.
 *
 * @since  6.2.1
 */
#[CoversNothing]
final class FofAesCompatibilityTest extends TestCase
{
	/**
	 * Default and explicit null adapters retain interoperable legacy ciphertext.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testDefaultAndNullAdaptersPreserveEncryptionRoundTrips(): void
	{
		$this->loadDeclarationsWithoutPhpDeprecations();
		$this->assertTrue(AES::isSupported());
		$this->assertTrue(AES::isSupported(null));
		$this->assertTrue((new Openssl())->isSupported());
		$this->assertTrue((new Openssl())->isSupported(null));
		$default = new AES('legacy compatibility key');
		$explicitNull = new AES('legacy compatibility key', 128, 'cbc', null);
		$plaintext = "JCB\0legacy data";

		foreach ([true, false] as $base64)
		{
			$ciphertext = $default->encryptString($plaintext, $base64);
			$this->assertNotSame($plaintext, $ciphertext);
			$this->assertSame(
				$plaintext,
				substr($explicitNull->decryptString($ciphertext, $base64), 0, strlen($plaintext))
			);
			$ciphertext = $explicitNull->encryptString($plaintext, $base64);
			$this->assertSame(
				$plaintext,
				substr($default->decryptString($ciphertext, $base64), 0, strlen($plaintext))
			);
		}
	}

	/**
	 * Injected feature detection still rejects a missing encryption primitive.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testInjectedFeatureDetectionRejectsMissingOpenSslEncryption(): void
	{
		$functions = $this->createMock(Phpfunc::class);
		$functions->expects($this->exactly(4))->method('__call')
			->willReturnCallback(static function (string $name, array $arguments): bool
			{
				self::assertSame('function_exists', $name);

				return $arguments[0] !== 'openssl_encrypt';
			});

		$this->assertFalse((new Openssl())->isSupported($functions));
	}

	/**
	 * Every backend keeps the interface's nullable optional adapter contract.
	 *
	 * Loading the optional Mcrypt backend must not need the removed extension.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testOptionalFunctionAdapterContractIsConsistentAcrossBackends(): void
	{
		foreach ([AES::class, AesInterface::class, Openssl::class, Mcrypt::class] as $class)
		{
			$parameter = (new ReflectionMethod($class, 'isSupported'))->getParameters()[0];
			$this->assertTrue($parameter->isOptional());
			$this->assertNull($parameter->getDefaultValue());
			$this->assertTrue($parameter->allowsNull());
			$this->assertSame(Phpfunc::class, $parameter->getType()->getName());
		}
	}

	/**
	 * Treat native FOF declaration deprecations as failures outside the source filter.
	 *
	 * The isolated caller ensures the autoloader parses every adapter afresh.
	 *
	 * @return  void
	 * @throws  ErrorException  When PHP deprecates a bundled FOF declaration.
	 * @since   6.2.1
	 */
	private function loadDeclarationsWithoutPhpDeprecations(): void
	{
		set_error_handler(static function (int $severity, string $message, string $file, int $line): never
		{
			throw new ErrorException($message, 0, $severity, $file, $line);
		}, E_DEPRECATED);

		try
		{
			foreach ([AES::class, Openssl::class, Mcrypt::class] as $class)
			{
				class_exists($class);
			}
		}
		finally
		{
			restore_error_handler();
		}
	}
}
