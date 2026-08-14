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


use VDM\Joomla\Componentbuilder\Utilities\RepoHelper;


/**
 * Fixture exposing repository record normalization boundaries.
 *
 * @since  1.0.0
 */
final class RepoHelperFixture extends RepoHelper
{
	/**
	 * Normalize one repository record.
	 *
	 * @param   object  $item  Mutable repository record.
	 *
	 * @return  object  Normalized repository record.
	 * @since   1.0.0
	 */
	public static function normalize(object $item): object
	{
		parent::modelRepoDetails($item);

		return $item;
	}

	/**
	 * Translate persisted placeholder rows into their keyed map.
	 *
	 * @param   string  $placeholders  JSON placeholder rows.
	 *
	 * @return  array  Placeholder map.
	 * @since   1.0.0
	 */
	public static function placeholders(string $placeholders): array
	{
		return parent::setPlaceholders($placeholders);
	}

	/**
	 * Map a repository type to its provider name.
	 *
	 * @param   int  $type  Repository type identifier.
	 *
	 * @return  string  Provider name.
	 * @since   1.0.0
	 */
	public static function target(int $type): string
	{
		return parent::setTarget($type);
	}
}
