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

namespace VDM\Joomla\Tests\Abstraction;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use VDM\Joomla\Abstraction\Console;
use VDM\Tests\Support\ConsoleFixture;
use VDM\Tests\Support\TestCase;

/**
 * Joomla console command lifecycle contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Console::class)]
final class ConsoleTest extends TestCase
{
	/**
	 * Bind the command argument, initialize first, and propagate body results.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExecuteRunsInitializationBeforeBodyAndReturnsExitCode(): void
	{
		$subject = new ConsoleFixture();
		$subject->exitCode = 17;
		$input = new ArrayInput([]);
		$output = new BufferedOutput();

		$this->assertSame(17, $subject->execute($input, $output));
		$this->assertSame('jcb:fixture', $subject->getName());
		$this->assertSame(['initialise', 'execute:jcb:fixture'], $subject->calls);
		$this->assertSame("fixture executed\n", $output->fetch());
	}

	/**
	 * Preserve an explicitly selected command instance name through execution.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExecutePreservesExplicitCommandName(): void
	{
		$subject = new ConsoleFixture('custom:fixture');
		$input = new ArrayInput([]);

		$this->assertSame(0, $subject->execute($input, new BufferedOutput()));
		$this->assertSame(['initialise', 'execute:custom:fixture'], $subject->calls);
	}
}
