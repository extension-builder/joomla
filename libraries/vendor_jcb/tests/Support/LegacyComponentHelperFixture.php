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


/**
 * Legacy global component-helper fixture used to verify compatibility dispatch.
 *
 * @since  1.0.0
 */
final class LegacyComponentHelperFixture
{
	/**
	 * Resolve a deterministic component encryption key.
	 *
	 * @param   string       $type     Key type.
	 * @param   string|null  $default  Fallback value.
	 *
	 * @return  string|null  Fixture key or fallback.
	 * @since   1.0.0
	 */
	public static function getCryptKey(string $type, ?string $default = null): ?string
	{
		return match ($type)
		{
			'basic' => 'fixture-basic-key',
			'medium' => 'fixture-medium-key',
			default => $default,
		};
	}

	/**
	 * Combine arguments so call forwarding is observable.
	 *
	 * @param   string  $first   First value.
	 * @param   string  $second  Second value.
	 *
	 * @return  string  Combined fixture value.
	 * @since   1.0.0
	 */
	public static function combine(string $first, string $second): string
	{
		return $first . ':' . $second;
	}
}
