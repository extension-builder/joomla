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
 * Recording Package remote-set capability used by orchestration tests.
 *
 * @since  1.0.0
 */
final class RecordingPackageSetHandler
{
	/**
	 * Item batches received by the capability.
	 *
	 * @var    array<int, array>
	 * @since  1.0.0
	 */
	private array $calls = [];

	/**
	 * Optional callback for an item batch.
	 *
	 * @var    Closure|null
	 * @since  1.0.0
	 */
	private ?Closure $onItems;

	/**
	 * Create a recording capability.
	 *
	 * @param   Closure|null  $onItems  Callback receiving an item batch.
	 *
	 * @since   1.0.0
	 */
	public function __construct(?Closure $onItems = null)
	{
		$this->onItems = $onItems;
	}

	/**
	 * Record and perform a remote save.
	 *
	 * @param   array  $items  Items being saved.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function items(array $items): bool
	{
		$this->calls[] = $items;

		if ($this->onItems !== null)
		{
			return (bool) ($this->onItems)($items);
		}

		return true;
	}

	/**
	 * Return every received batch.
	 *
	 * @return  array<int, array>
	 * @since   1.0.0
	 */
	public function calls(): array
	{
		return $this->calls;
	}
}
