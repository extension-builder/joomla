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
 * Recording Package remote-get capability used by orchestration tests.
 *
 * @since  1.0.0
 */
final class RecordingPackageGetHandler
{
	/**
	 * Calls made to the capability in execution order.
	 *
	 * @var    array<int, array{method: string, arguments: array}>
	 * @since  1.0.0
	 */
	private array $calls = [];

	/**
	 * Optional callback for an init operation.
	 *
	 * @var    Closure|null
	 * @since  1.0.0
	 */
	private ?Closure $onInit;

	/**
	 * Optional callback for a reset operation.
	 *
	 * @var    Closure|null
	 * @since  1.0.0
	 */
	private ?Closure $onReset;

	/**
	 * Create a recording capability.
	 *
	 * @param   Closure|null  $onInit   Callback receiving items, repository, and force.
	 * @param   Closure|null  $onReset  Callback receiving reset items.
	 *
	 * @since   1.0.0
	 */
	public function __construct(?Closure $onInit = null, ?Closure $onReset = null)
	{
		$this->onInit = $onInit;
		$this->onReset = $onReset;
	}

	/**
	 * Record and perform a remote initialization.
	 *
	 * @param   array        $items  Items being retrieved.
	 * @param   object|null  $repo   Explicit repository, when supplied.
	 * @param   bool         $force  Whether local values may be replaced.
	 *
	 * @return  array{local: array, not_found: array, added: array}
	 * @since   1.0.0
	 */
	public function init(array $items, ?object $repo = null, bool $force = false): array
	{
		$this->calls[] = [
			'method' => 'init',
			'arguments' => [$items, $repo, $force],
		];

		if ($this->onInit !== null)
		{
			return ($this->onInit)($items, $repo, $force);
		}

		return [
			'local' => [],
			'not_found' => [],
			'added' => [],
		];
	}

	/**
	 * Record and perform a reset.
	 *
	 * @param   array  $items  Items being reset.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function reset(array $items): bool
	{
		$this->calls[] = [
			'method' => 'reset',
			'arguments' => [$items],
		];

		if ($this->onReset !== null)
		{
			return (bool) ($this->onReset)($items);
		}

		return true;
	}

	/**
	 * Return all recorded calls.
	 *
	 * @return  array<int, array{method: string, arguments: array}>
	 * @since   1.0.0
	 */
	public function calls(): array
	{
		return $this->calls;
	}
}
