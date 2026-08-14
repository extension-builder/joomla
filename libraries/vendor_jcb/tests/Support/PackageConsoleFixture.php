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
use VDM\Joomla\Componentbuilder\Abstraction\Console\Package;


/**
 * Test-owned Package console command exposing the abstract lifecycle boundary.
 *
 * @since  1.0.0
 */
final class PackageConsoleFixture extends Package
{
	/**
	 * Status returned by the action hook.
	 *
	 * @var    int
	 * @since  1.0.0
	 */
	public int $status = 0;

	/**
	 * Optional exception thrown by the action hook.
	 *
	 * @var    \Throwable|null
	 * @since  1.0.0
	 */
	public ?\Throwable $exception = null;

	/**
	 * Entity services keyed by their factory alias.
	 *
	 * @var    array<string, mixed>
	 * @since  1.0.0
	 */
	public array $services = [];

	/**
	 * Ordered entity-service resolutions.
	 *
	 * @var    array<int, array{alias: string, entity: string|null}>
	 * @since  1.0.0
	 */
	public array $serviceCalls = [];

	/**
	 * Configure the two common item input options.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function configure(): void
	{
		$this->addSharedOptions();
	}

	/**
	 * Return the configured status or throw the configured failure.
	 *
	 * @param   InputInterface   $input   Bound command input.
	 * @param   OutputInterface  $output  Command output.
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	protected function doExecuteAction(InputInterface $input, OutputInterface $output): int
	{
		if ($this->exception !== null)
		{
			throw $this->exception;
		}

		return $this->status;
	}

	/**
	 * Resolve a fixture-owned entity service without opening a static factory.
	 *
	 * @param   string       $alias   Entity service alias.
	 * @param   string|null  $entity  Optional entity override.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	protected function getEntityClass(string $alias, ?string $entity = null): mixed
	{
		$this->serviceCalls[] = [
			'alias' => $alias,
			'entity' => $entity,
		];

		return $this->services[$alias] ?? null;
	}
}
