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

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix;


use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomButtons as BaseCustomButtons;


/**
 * Joomla 6 custom buttons renderer.
 *
 * @since  6.2.0
 */
final class CustomButtons extends BaseCustomButtons
{

	/**
	 * Register the toolbar submit handler through Joomla's asset manager.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	public function javascript(): string
	{
		return PHP_EOL . "\t\t" . '$this->getDocument()->getWebAssetManager()->useScript(\'core\')'
			. '->addInlineScript('
			. var_export("Joomla.submitbutton = function(task){if (task == ''){ return false; } else { Joomla.submitform(task); return true; }}", true)
			. ", ['name' => 'com_" . $this->config->component_code_name . ".toolbar-buttons'], [], ['core']);";
	}

	/**
	 * Render a button on the existing document toolbar without legacy arguments.
	 *
	 * @param   string  $task            Controller task.
	 * @param   string  $icon            Button icon and additional classes.
	 * @param   string  $label           Translation key.
	 * @param   bool    $selection       Whether a list selection is required.
	 * @param   bool    $quoteSelection  Earlier-target formatting hint, unused here.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function button(
		string $task, string $icon, string $label, bool $selection, bool $quoteSelection = false
	): string
	{
		$icon = preg_replace('#\.[^.]*$#', '', $icon);

		return '$this->getDocument()->getToolbar()->standardButton('
			. var_export($icon, true) . ', ' . var_export($label, true) . ', ' . var_export($task, true)
			. ')->listCheck(' . ($selection ? 'true' : 'false') . ');';
	}
}
