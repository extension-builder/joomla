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

namespace VDM\Joomla\Tests\Componentbuilder\Utilities\Exception;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Utilities\Exception\NoUserIdFoundException;
use VDM\Tests\Support\TestCase;


/**
 * Missing-user identity exception contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(NoUserIdFoundException::class)]
final class NoUserIdFoundExceptionTest extends TestCase
{
	/**
	 * Preserve the invalid-argument taxonomy and diagnostic context.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExceptionPreservesInvalidArgumentContract(): void
	{
		$exception = new NoUserIdFoundException('identity mismatch', 409);

		$this->assertInstanceOf(InvalidArgumentException::class, $exception);
		$this->assertSame('identity mismatch', $exception->getMessage());
		$this->assertSame(409, $exception->getCode());
	}
}
