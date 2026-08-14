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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Customcode;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Hash;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\LockBase;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Tests\Support\CompilerUtilityTestCase;


/**
 * Inline hashing and Base64-lock code-generation contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Hash::class)]
#[CoversClass(LockBase::class)]
#[UsesClass(Placeholder::class)]
final class HashAndLockBaseTest extends CompilerUtilityTestCase
{
	/**
	 * Replace every string-hash token while preserving surrounding source.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHashReplacesInlineStringTokensWithMd5Values(): void
	{
		$placeholder = new Placeholder($this->createStub(Config::class));
		$subject = new Hash($placeholder);

		$this->assertSame(
			'before ' . md5('alpha') . ' and ' . md5('beta') . ' after',
			$subject->set('before HASHSTRING((((alpha)))) and HASHSTRING((((beta)))) after')
		);
	}

	/**
	 * Emit a whitespace-tolerant Base64 decoder using compiler indentation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLockBaseEmitsTheRuntimeDecoderAndRemovesTheSourceSecret(): void
	{
		$placeholder = new Placeholder($this->createStub(Config::class));
		$subject = new LockBase($placeholder);

		$output = $subject->set('return LOCKBASE64((((secret value))));');

		$this->assertStringNotContainsString('LOCKBASE64', $output);
		$this->assertStringNotContainsString('secret value', $output);
		$this->assertStringContainsString("base64_decode( preg_replace('/\\s+/', '',", $output);
		$this->assertStringContainsString(base64_encode('secret value'), $output);
		$this->assertStringContainsString(PHP_EOL . "\t\t'", $output);
	}

	/**
	 * Leave source without a supported token byte-for-byte unchanged.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHashAndLockAreNoOpsWithoutTokens(): void
	{
		$placeholder = new Placeholder($this->createStub(Config::class));
		$source = 'return $value;';

		$this->assertSame($source, (new Hash($placeholder))->set($source));
		$this->assertSame($source, (new LockBase($placeholder))->set($source));
	}
}
