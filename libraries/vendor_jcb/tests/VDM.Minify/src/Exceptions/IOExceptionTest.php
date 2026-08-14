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

namespace VDM\Minify\Tests\Exceptions;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use VDM\Minify\Abstraction\BasicException;
use VDM\Minify\Exceptions\IOException;


/**
 * Minify input/output exception contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(IOException::class)]
final class IOExceptionTest extends TestCase
{
	/**
	 * Remain catchable at the Minify boundary without losing I/O diagnostics.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFailureIsCatchableAsBasicExceptionWithOriginalContext(): void
	{
		$previous = new RuntimeException('disk full');
		$exception = new IOException('Unable to write bundle', 28, $previous);

		$this->assertInstanceOf(BasicException::class, $exception);
		$this->assertSame('Unable to write bundle', $exception->getMessage());
		$this->assertSame(28, $exception->getCode());
		$this->assertSame($previous, $exception->getPrevious());
	}
}
