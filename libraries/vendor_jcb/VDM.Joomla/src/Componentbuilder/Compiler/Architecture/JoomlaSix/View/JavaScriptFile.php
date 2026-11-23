<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    23rd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View;


use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\JavaScriptFile as SharedJavaScriptFile;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;


/**
 * Place Joomla 6 view scripts inside the component media package.
 * 
 * @since  6.2.0
 */
final class JavaScriptFile extends SharedJavaScriptFile
{
	/**
	 * Render one entry in the template-owned view script list.
	 *
	 * @param   string  $path  The installed asset path.
	 *
	 * @return  string  The escaped PHP array entry.
	 * @since   6.2.0
	 */
	protected function render(string $path): string
	{
		return PHP_EOL . Indent::_(3) . "//" . Line::_(__LINE__, __CLASS__)
			. " Add View JavaScript File" . PHP_EOL
			. Indent::_(3) . var_export(ltrim($path, '/'), true) . ',';
	}

	/**
	 * Resolve the public script path for this view.
	 *
	 * @param   string  $name  The view code name.
	 *
	 * @return  string  The installed asset path.
	 * @since   6.2.0
	 */
	protected function getPath(string $name): string
	{
		$client = $this->config->build_target === 'site' ? 'site' : 'admin';

		return '/media/com_' . $this->config->component_code_name
			. '/js/' . $client . '/' . $name . '.js';
	}
}
