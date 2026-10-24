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

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminViews;


use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListView as SharedListView;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;


/**
 * Register generated Joomla 6 list footer scripts with Web Asset Manager.
 *
 * @since  6.2.0
 */
final class ListView extends SharedListView
{
	/**
	 * Capture dynamic PHP output before registering the inline script.
	 *
	 * @param   string  $script  The JavaScript body, possibly containing PHP.
	 * @param   string  $name    The list view code name.
	 *
	 * @return  string  The generated template fragment.
	 * @since   6.2.0
	 */
	protected function getFooterScript(string $script, string $name): string
	{
		$assetName = 'com_' . $this->config->component_code_name . '.admin.' . $name . '.footer';
		$opening = Indent::_(0) . '<?php'
			. PHP_EOL . Indent::_(0) . '$jcbFooterBufferLevel = ob_get_level();'
			. PHP_EOL . Indent::_(0) . 'ob_start();'
			. PHP_EOL . Indent::_(0) . 'try'
			. PHP_EOL . Indent::_(0) . '{'
			. PHP_EOL . Indent::_(0) . '?>';
		$closing = Indent::_(0) . '<?php'
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
			. PHP_EOL . Indent::_(0) . '$jcbFooterDependencies = array_values(array_diff(array_unique([\'core\', ...array_keys($jcbFooterAssets->getAssets(\'script\'))]), [$jcbFooterName]));'
			. PHP_EOL . Indent::_(0) . '$jcbFooterAssets->addInlineScript($jcbFooterScript, [\'name\' => $jcbFooterName], [], $jcbFooterDependencies);'
			. PHP_EOL . Indent::_(0) . '?>';

		return PHP_EOL . Indent::_(0) . '<?php $jcbFooterName = ' . var_export($assetName, true) . '; ?>'
			. PHP_EOL . $opening . $script . $closing;
	}
}
