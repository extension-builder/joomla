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

use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\FadeInEffect as SharedFadeInEffect;

/**
 * Joomla six loading overlay registration and document readiness.
 *
 * @since 6.2.0
 */
final class FadeInEffect extends SharedFadeInEffect
{
	/**
	 * Register the overlay through WAM without touching the body before it exists.
	 *
	 * @param   string  $script     Generated overlay JavaScript.
	 * @param   string  $component  Component code name.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	protected function renderScript(string $script, string $component): string
	{
		$loader = json_encode($component . '_loader', JSON_THROW_ON_ERROR);
		$name = var_export('com_' . $component . '.loading-overlay', true);
		$script = <<<JS
(function () {
	const initialise = function () {
		if (document.readyState === 'complete') {
			const container = document.getElementById({$loader});
			if (container) { container.style.display = 'block'; }
			return;
		}
{$script}
	};
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initialise, {once: true}); }
	else { initialise(); }
})();
JS;

		return '<?php' . PHP_EOL
			. '$this->getDocument()->getWebAssetManager()->addInlineScript(' . PHP_EOL
			. "<<<'JCB_LOADING_OVERLAY'" . PHP_EOL . $script . PHP_EOL . 'JCB_LOADING_OVERLAY'
			. PHP_EOL . ", ['name' => {$name}], [], ['core']);" . PHP_EOL . '?>';
	}
}
