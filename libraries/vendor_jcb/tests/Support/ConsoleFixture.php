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

namespace VDM\Tests\Support;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VDM\Joomla\Abstraction\Console;

/**
 * Concrete console fixture recording Joomla command lifecycle hooks.
 *
 * @since  1.0.0
 */
final class ConsoleFixture extends Console
{
	/**
	 * Default command name.
	 *
	 * @var    string|null
	 * @since  1.0.0
	 */
	protected static $defaultName = 'jcb:fixture';

	/**
	 * Ordered lifecycle calls.
	 *
	 * @var    array<int, string>
	 * @since  1.0.0
	 */
	public array $calls = [];

	/**
	 * Exit code returned by execution.
	 *
	 * @var    int
	 * @since  1.0.0
	 */
	public int $exitCode = 0;

	/**
	 * Execute the command body.
	 *
	 * @param   InputInterface   $input   Bound input.
	 * @param   OutputInterface  $output  Command output.
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$command = $input->hasArgument('command')
			? $input->getArgument('command')
			: $this->getName();
		$this->calls[] = 'execute:' . $command;
		$output->writeln('fixture executed');

		return $this->exitCode;
	}

	/**
	 * Record initialization after input binding and before execution.
	 *
	 * @param   InputInterface   $input   Bound input.
	 * @param   OutputInterface  $output  Command output.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function initialise(InputInterface $input, OutputInterface $output): void
	{
		$this->calls[] = 'initialise';
	}
}
