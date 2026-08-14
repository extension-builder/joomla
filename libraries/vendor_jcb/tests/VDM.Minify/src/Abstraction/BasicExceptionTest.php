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

namespace VDM\Minify\Tests\Abstraction;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use VDM\Minify\Abstraction\BasicException;


/**
 * Shared Minify exception contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(BasicException::class)]
final class BasicExceptionTest extends TestCase
{
	/**
	 * Preserve the standard exception diagnostic chain for specialized failures.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSpecializationPreservesMessageCodeAndPreviousFailure(): void
	{
		$previous = new RuntimeException('origin');
		$exception = new class('minification failed', 37, $previous) extends BasicException
		{
		};

		$this->assertSame('minification failed', $exception->getMessage());
		$this->assertSame(37, $exception->getCode());
		$this->assertSame($previous, $exception->getPrevious());
	}
}
