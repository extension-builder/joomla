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

namespace VDM\Joomla\Componentbuilder\Compiler\Component;


use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Component\TemplateInterface;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;


/**
 * Resolve templates from the configured compiler source directory.
 *
 * @since  6.2.0
 */
class Template implements TemplateInterface
{
	/**
	 * Compiler source and output paths.
	 *
	 * @var    Paths
	 * @since  6.2.0
	 */
	protected Paths $paths;

	/**
	 * Constructor.
	 *
	 * @param   Paths  $paths  Compiler source and output paths.
	 *
	 * @since   6.2.0
	 */
	public function __construct(Paths $paths)
	{
		$this->paths = $paths;
	}

	/**
	 * Get the source path for a compiler template.
	 *
	 * @param   string  $file  The template name from component settings.
	 *
	 * @return  string  The source path to copy.
	 * @since   6.2.0
	 */
	public function get(string $file): string
	{
		return $this->paths->template_path . '/' . $file;
	}
}
