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


use VDM\Joomla\Abstraction\Model;


/**
 * Model fixture with observable normalization and validation boundaries.
 *
 * @since  1.0.0
 */
final class ModelFixture extends Model
{
	/**
	 * Normalize strings to trimmed uppercase values.
	 *
	 * @param   mixed        $value  Source value.
	 * @param   string       $field  Field name.
	 * @param   string|null  $table  Table name.
	 *
	 * @return  mixed  Normalized value.
	 * @since   1.0.0
	 */
	public function value($value, string $field, ?string $table = null): mixed
	{
		return is_string($value) ? strtoupper(trim($value)) : $value;
	}

	/**
	 * Return the active fixture table.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function activeTable(): string
	{
		return $this->getTable();
	}

	/**
	 * Return the empty-value policy.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function allowsEmpty(): bool
	{
		return $this->getAllowEmpty();
	}

	/**
	 * Reject an explicit marker and optionally reject empty values.
	 *
	 * @param   mixed        $value  Source value.
	 * @param   string|null  $field  Field name.
	 * @param   string|null  $table  Table name.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	protected function validateBefore(&$value, ?string $field = null, ?string $table = null): bool
	{
		if ($value === 'before-reject')
		{
			return false;
		}

		return $this->getAllowEmpty() || ($value !== null && $value !== '');
	}

	/**
	 * Reject an explicit normalized marker.
	 *
	 * @param   mixed        $value  Normalized value.
	 * @param   string|null  $field  Field name.
	 * @param   string|null  $table  Table name.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	protected function validateAfter(&$value, ?string $field = null, ?string $table = null): bool
	{
		return $value !== 'AFTER-REJECT';
	}
}
