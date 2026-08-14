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


use VDM\Joomla\Database\DefaultTrait;


/**
 * Fixture exposing the database default-field switch.
 *
 * @since  1.0.0
 */
final class DefaultTraitFixture
{
	use DefaultTrait;

	/**
	 * Return the active default-field switch.
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function defaultsEnabled(): bool
	{
		return $this->defaults;
	}
}
