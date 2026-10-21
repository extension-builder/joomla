<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    21st October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Interfaces\Component;


/**
 * Resolve component template sources without changing their output names.
 *
 * @since  6.2.0
 */
interface TemplateInterface
{
	/**
	 * Get the source path for a compiler template.
	 *
	 * @param   string  $file  The template name from component settings.
	 *
	 * @return  string  The source path to copy.
	 * @since   6.2.0
	 */
	public function get(string $file): string;
}
