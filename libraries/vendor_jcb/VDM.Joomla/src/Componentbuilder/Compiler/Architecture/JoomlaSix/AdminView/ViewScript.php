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

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminView;

use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\ViewScript as SharedViewScript;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;

/**
 * Joomla six ViewScript renderer.
 *
 * @since 6.2.0
 */
final class ViewScript extends SharedViewScript
{
	/**
	 * Wait for the form before binding its generated listeners.
	 *
	 * @param   string  $listeners  The generated listener setup.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function prepareListeners(string $listeners): string
	{
		return 'jQuery(function () {' . PHP_EOL . $listeners . PHP_EOL . Indent::_(0) . '});';
	}

	/**
	 * Native media fields dispatch ordinary input change events.
	 *
	 * @return  bool
	 * @since   6.2.0
	 */
	protected function usesModalMediaListener(): bool
	{
		return false;
	}

	/**
	 * Get the media URI of the generated administration list script.
	 *
	 * @param   string  $name  The list view name.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function listScriptPath(string $name): string
	{
		return '/media/com_' . $this->config->component_code_name . '/js/admin/' . $name . '.js';
	}

	/**
	 * Capture dynamic footer JavaScript for the Web Asset Manager.
	 *
	 * @param   string  $script  The JavaScript including optional PHP.
	 * @param   string  $view    The administration view name.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function footerScript(string $script, string $view): string
	{
		$name = var_export('com_' . $this->config->component_code_name . '.admin.' . $view . '.footer', true);

		return PHP_EOL . Indent::_(0) . '<?php'
			. PHP_EOL . Indent::_(0) . '$jcbFooterBufferLevel = ob_get_level();'
			. PHP_EOL . Indent::_(0) . 'ob_start();'
			. PHP_EOL . Indent::_(0) . 'try'
			. PHP_EOL . Indent::_(0) . '{'
			. PHP_EOL . Indent::_(1) . '?>'
			. PHP_EOL . $script
			. PHP_EOL . Indent::_(0) . '<?php'
			. PHP_EOL . Indent::_(1) . '$jcbFooterScript = ob_get_contents();'
			. PHP_EOL . Indent::_(0) . '}'
			. PHP_EOL . Indent::_(0) . 'finally'
			. PHP_EOL . Indent::_(0) . '{'
			. PHP_EOL . Indent::_(1) . 'while (ob_get_level() > $jcbFooterBufferLevel)'
			. PHP_EOL . Indent::_(1) . '{'
			. PHP_EOL . Indent::_(2) . 'ob_end_clean();'
			. PHP_EOL . Indent::_(1) . '}'
			. PHP_EOL . Indent::_(0) . '}'
			. PHP_EOL . Indent::_(0) . '$jcbFooterAssets = $this->getDocument()->getWebAssetManager();'
			. PHP_EOL . Indent::_(0) . "\$jcbFooterAssets->addInlineScript(\$jcbFooterScript, ['name' => {$name}], [], array_values(array_unique(array_merge(['core', 'jquery'], array_diff(array_keys(\$jcbFooterAssets->getAssets('script')), [{$name}])))));"
			. PHP_EOL . Indent::_(0) . '?>';
	}
}
