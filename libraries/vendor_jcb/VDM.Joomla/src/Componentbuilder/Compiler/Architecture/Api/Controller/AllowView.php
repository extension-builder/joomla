<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    1st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller;


use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Permission;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;


/**
 * Api Controller Allow View Class.
 *
 * Builds the allowView method of the item API controller from the same
 * access permission the admin list uses to remove items a user may not see.
 *
 * @since 6.1.7
 */
final class AllowView
{
	/**
	 * The Component code name.
	 *
	 * @var   string
	 * @since 6.1.7
	 */
	protected string $component;

	/**
	 * The Permission Class.
	 *
	 * @var   Permission
	 * @since 6.1.7
	 */
	protected Permission $permission;

	/**
	 * Constructor.
	 *
	 * @param Config       $config       The Config Class.
	 * @param Permission   $permission   The Permission Class.
	 *
	 * @since 6.1.7
	 */
	public function __construct(Config $config, Permission $permission)
	{
		$this->component = $config->component_code_name;
		$this->permission = $permission;
	}

	/**
	 * Get the allow view code of the item API controller.
	 *
	 * @param   string  $nameSingleCode  The single code name of the view.
	 *
	 * @return  string  The allow view method body.
	 * @since   6.1.7
	 */
	public function get(string $nameSingleCode): string
	{
		$allow = [];

		if ($this->permission->actionExist($nameSingleCode, 'core.access'))
		{
			$action = $this->permission->getAction($nameSingleCode, 'core.access');

			$allow[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__LINE__, __CLASS__)
				. " Get user object.";
			$allow[] = Indent::_(2) . "\$user = \$this->app->getIdentity();";
			$allow[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__LINE__, __CLASS__)
				. " Access check.";
			$allow[] = Indent::_(2) . "return (\$user->authorise('" . $action
				. "', 'com_" . $this->component . "." . $nameSingleCode
				. ".' . \$id) && \$user->authorise('" . $action . "', 'com_"
				. $this->component . "'));";
		}
		else
		{
			$allow[] = PHP_EOL . Indent::_(2) . "//" . Line::_(__LINE__, __CLASS__)
				. " In the absence of an access permission, every authenticated user may view.";
			$allow[] = Indent::_(2) . "return true;";
		}

		return implode(PHP_EOL, $allow);
	}
}
