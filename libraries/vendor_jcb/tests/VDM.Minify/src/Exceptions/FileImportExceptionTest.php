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
use VDM\Minify\Exceptions\FileImportException;


/**
 * Minify stylesheet import exception contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(FileImportException::class)]
final class FileImportExceptionTest extends TestCase
{
	/**
	 * Remain catchable at the Minify boundary with the import-chain diagnostic.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFailureIsCatchableAsBasicExceptionWithImportContext(): void
	{
		$previous = new RuntimeException('nested import');
		$exception = new FileImportException('Circular stylesheet import', 0, $previous);

		$this->assertInstanceOf(BasicException::class, $exception);
		$this->assertSame('Circular stylesheet import', $exception->getMessage());
		$this->assertSame($previous, $exception->getPrevious());
	}
}
