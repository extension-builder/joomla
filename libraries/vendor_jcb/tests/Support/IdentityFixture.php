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


use Joomla\CMS\User\User;
use VDM\Joomla\Componentbuilder\User\IdentityTrait;


/**
 * Fixture exposing the execution-identity trust boundary.
 *
 * @since  6.1.6
 */
final class IdentityFixture
{
	use IdentityTrait;

	/**
	 * Resolve the effective Joomla user.
	 *
	 * @return  User  Effective execution user.
	 * @since   6.1.6
	 */
	public function resolve(): User
	{
		return $this->getIdentity();
	}
}
