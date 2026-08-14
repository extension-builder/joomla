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

use VDM\Joomla\Abstraction\Schema;

/**
 * Concrete schema fixture exposing deterministic SQL policy helpers.
 *
 * @since  1.0.0
 */
final class SchemaFixture extends Schema
{
	/**
	 * Determine whether a database type change needs an ALTER operation.
	 *
	 * @param   string  $current   Current database type.
	 * @param   string  $expected  Expected database type.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function significantTypeChange(string $current, string $expected): bool
	{
		return $this->isDataTypeChangeSignificant($current, $expected);
	}

	/**
	 * Build the database-specific default expression.
	 *
	 * @param   string       $type     Column type.
	 * @param   string|null  $default  Configured default.
	 * @param   bool         $pure     Return only the value.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function defaultValue(string $type, ?string $default, bool $pure = false): string
	{
		return $this->getDefaultValue($type, $default, $pure);
	}

	/**
	 * Get the fixture component code.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function getCode(): string
	{
		return 'fixture';
	}
}
