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

namespace VDM\Joomla\Componentbuilder\Compiler\Component\JoomlaSix;


use VDM\Joomla\Componentbuilder\Compiler\Component\Template as SharedTemplate;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;


/**
 * Overlay native Joomla 6 templates on the built-in shared source directory.
 *
 * Explicit compiler directories remain authoritative, including their missing-file
 * behavior. Custom templates never silently fall back to the built-in overlay.
 *
 * @since  6.1.7
 */
final class Template extends SharedTemplate
{
	/**
	 * Directory containing Joomla 6 template source overrides.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	protected string $overlayPath;

	/**
	 * Built-in template directory eligible for the Joomla 6 overlay.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	protected string $defaultTemplatePath;

	/**
	 * Constructor.
	 *
	 * @param   Paths   $paths                Compiler source and output paths.
	 * @param   string  $overlayPath          Directory containing source overrides.
	 * @param   string  $defaultTemplatePath  Built-in shared template directory.
	 *
	 * @since   6.1.7
	 */
	public function __construct(Paths $paths, string $overlayPath, string $defaultTemplatePath)
	{
		parent::__construct($paths);
		$this->overlayPath = rtrim($overlayPath, '/\\');
		$this->defaultTemplatePath = rtrim(str_replace('\\', '/', $defaultTemplatePath), '/');
	}

	/**
	 * Get a Joomla 6 override or preserve the configured template source.
	 *
	 * Only ordinary file names participate in the overlay. Relative directories,
	 * absolute paths, and custom compiler roots keep their existing resolution.
	 * The .tpl suffix belongs only to the input and never to generated file names.
	 *
	 * @param   string  $file  The template name from component settings.
	 *
	 * @return  string  The source path to copy.
	 * @since   6.1.7
	 */
	public function get(string $file): string
	{
		$templatePath = rtrim(str_replace('\\', '/', (string) $this->paths->template_path), '/');

		if ($templatePath === $this->defaultTemplatePath
			&& $file !== '' && $file !== '.' && $file !== '..'
			&& basename(str_replace('\\', '/', $file)) === $file)
		{
			$source = $this->overlayPath . '/' . $file . '.tpl';

			if (is_file($source))
			{
				return $source;
			}
		}

		return parent::get($file);
	}
}
