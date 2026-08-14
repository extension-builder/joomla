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


use Closure;


/**
 * Recording Package Grep capability used by orchestration tests.
 *
 * @since  1.0.0
 */
final class RecordingPackageGrepHandler
{
	/**
	 * Raw identifier batches sent for GUID resolution.
	 *
	 * @var    array<int, array>
	 * @since  1.0.0
	 */
	private array $guidCalls = [];

	/**
	 * Repositories sent for validation.
	 *
	 * @var    array<int, object>
	 * @since  1.0.0
	 */
	private array $repoCalls = [];

	/**
	 * Optional callback for GUID resolution.
	 *
	 * @var    Closure|null
	 * @since  1.0.0
	 */
	private ?Closure $onGuids;

	/**
	 * Optional callback for repository validation.
	 *
	 * @var    Closure|null
	 * @since  1.0.0
	 */
	private ?Closure $onRepo;

	/**
	 * Create a recording capability.
	 *
	 * @param   Closure|null  $onGuids  Callback receiving raw identifiers.
	 * @param   Closure|null  $onRepo   Callback receiving a repository.
	 *
	 * @since   1.0.0
	 */
	public function __construct(?Closure $onGuids = null, ?Closure $onRepo = null)
	{
		$this->onGuids = $onGuids;
		$this->onRepo = $onRepo;
	}

	/**
	 * Resolve a raw identifier batch.
	 *
	 * @param   array  $items  Raw identifiers.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	public function getValidGuids(array $items): array
	{
		$this->guidCalls[] = $items;

		return $this->onGuids !== null ? ($this->onGuids)($items) : $items;
	}

	/**
	 * Validate a repository.
	 *
	 * @param   object  $repository  Repository settings.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function validRepo(object $repository): bool
	{
		$this->repoCalls[] = $repository;

		return $this->onRepo !== null ? (bool) ($this->onRepo)($repository) : true;
	}

	/**
	 * Return GUID-resolution calls.
	 *
	 * @return  array<int, array>
	 * @since   1.0.0
	 */
	public function guidCalls(): array
	{
		return $this->guidCalls;
	}

	/**
	 * Return repository-validation calls.
	 *
	 * @return  array<int, object>
	 * @since   1.0.0
	 */
	public function repoCalls(): array
	{
		return $this->repoCalls;
	}
}
