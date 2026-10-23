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
		$opening = <<<'PHP'
<?php
$jcbFooterBufferLevel = ob_get_level();
ob_start();
try
{
?>
PHP;
		$closing = <<<'PHP'
<?php
	$jcbFooterScript = ob_get_contents();
}
finally
{
	while (ob_get_level() > $jcbFooterBufferLevel)
	{
		ob_end_clean();
	}
}
$jcbFooterAssets = $this->getDocument()->getWebAssetManager();
$jcbFooterDependencies = array_values(array_diff(array_unique(['core', ...array_keys($jcbFooterAssets->getAssets('script'))]), [$jcbFooterName]));
$jcbFooterAssets->addInlineScript($jcbFooterScript, ['name' => $jcbFooterName], [], $jcbFooterDependencies);
?>
PHP;

		return PHP_EOL . '<?php $jcbFooterName = ' . var_export($assetName, true) . '; ?>'
			. PHP_EOL . $opening . $script . $closing;
	}
}
