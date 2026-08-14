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


use VDM\Joomla\Abstraction\Database;


/**
 * Fixture exposing shared database table and quoting boundaries.
 *
 * @since  1.0.0
 */
final class DatabaseFixture extends Database
{
	/**
	 * Resolve a logical or already-prefixed table name.
	 *
	 * @param   string  $table  Table name.
	 *
	 * @return  string  Resolved table name.
	 * @since   1.0.0
	 */
	public function tableName(string $table): string
	{
		return $this->getTable($table);
	}

	/**
	 * Quote a value with the shared database policy.
	 *
	 * @param   mixed  $value  Value to quote.
	 *
	 * @return  mixed  Quoted or native value.
	 * @since   1.0.0
	 */
	public function quoted(mixed $value): mixed
	{
		return $this->quote($value);
	}
}
