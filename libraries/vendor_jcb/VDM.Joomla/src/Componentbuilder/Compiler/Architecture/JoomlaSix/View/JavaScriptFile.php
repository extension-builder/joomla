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

	/**
	 * Build and replace every optional script slot for the current view.
	 *
	 * Optional view_script_paths and view_script_dependencies are string lists.
	 * Missing lists are empty; entries remain local to this view.
	 *
	 * @param   array   $view    The current view definition.
	 * @param   string  $TARGET  The upper-case build target.
	 *
	 * @return  void
	 * @throws  \InvalidArgumentException  When a supplied input is not a string list.
	 * @since   6.2.1
	 */
	public function set(array &$view, string $TARGET): void
	{
		$paths = $this->entries($view, 'view_script_paths');
		$dependencies = $this->entries($view, 'view_script_dependencies');
		$prefix = $view['settings']->code . '|' . $TARGET;

		$this->contentmulti->set($prefix . '_VIEW_SCRIPT_ENTRY',
			$this->get($view, $TARGET)
		);
		$this->contentmulti->set($prefix . '_VIEW_SCRIPT_PATHS', $paths);
		$this->contentmulti->set($prefix . '_VIEW_SCRIPT_DEPENDENCIES', $dependencies);
	}

	/**
	 * Expand and escape an explicitly supplied ordered list.
	 *
	 * @param   array   $view      The current view definition.
	 * @param   string  $property  The scoped settings property to read.
	 *
	 * @return  string  PHP array entries, or an empty string for absent input.
	 * @throws  \InvalidArgumentException  When the input contains invalid values.
	 * @since   6.2.1
	 */
	private function entries(array $view, string $property): string
	{
		$values = $view['settings']->{$property} ?? [];

		if (!is_array($values) || !array_is_list($values))
		{
			throw new \InvalidArgumentException($property . ' must be an ordered list of strings.');
		}

		$entries = '';

		foreach ($values as $value)
		{
			if (!is_string($value) || trim($value) === '')
			{
				throw new \InvalidArgumentException($property . ' must contain non-empty strings.');
			}

			$value = $this->placeholder->update_($value);

			if (trim($value) === '')
			{
				throw new \InvalidArgumentException($property . ' expanded to an empty value.');
			}

			$entries .= PHP_EOL . Indent::_(3) . var_export($value, true) . ',';
		}

		return $entries;
	}
}
