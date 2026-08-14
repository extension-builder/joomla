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

use VDM\Joomla\Abstraction\Versioning;

/**
 * Concrete fixture exposing shared history resolution behavior.
 *
 * @since  1.0.0
 */
final class VersioningFixture extends Versioning
{
	/**
	 * Select the active history entity.
	 *
	 * @param   string|null  $entity  Logical entity name.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function entity(?string $entity): void
	{
		$this->entity = $entity;
	}

	/**
	 * Return the current history switch.
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function historyState(): int
	{
		return $this->history;
	}

	/**
	 * Resolve a logical entity from a table name.
	 *
	 * @param   string  $table  Table name.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	public function tableEntityName(string $table): ?string
	{
		return $this->getTableEntityName($table);
	}

	/**
	 * Resolve the Joomla table class for the active entity.
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	public function tableClass(): ?string
	{
		return $this->getTableClass();
	}

	/**
	 * Attempt to save one history record.
	 *
	 * @param   int  $id  Entity ID.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function saveHistory(int $id): bool
	{
		return $this->setHistory($id);
	}

	/**
	 * Attempt to save several history records.
	 *
	 * @param   array<int, int>  $ids  Entity IDs.
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function saveMultipleHistory(array $ids): int
	{
		return $this->setMultipleHistory($ids);
	}
}
