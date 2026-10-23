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


use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ViewBody as SharedViewBody;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;


/**
 * Render Joomla 6 native ordering controls and draggable rows.
 *
 * @since  6.2.0
 */
final class ViewBody extends SharedViewBody
{
	/**
	 * Register the sidebar ordering handler through Web Asset Manager.
	 *
	 * @return  array<int, string>  The generated script registration lines.
	 * @since   6.2.0
	 */
	protected function getOrderingScript(): array
	{
		$name = 'com_' . $this->config->component_code_name . '.ordering';
		$script = "Joomla.orderTable = function () {\n"
			. "\tconst table = document.getElementById('sortTable');\n"
			. "\tconst direction = document.getElementById('directionTable');\n"
			. "\tconst order = table.options[table.selectedIndex].value;\n"
			. "\tconst current = Joomla.getOptions(" . json_encode($name) . ").order;\n"
			. "\tconst dirn = order !== current ? 'asc' : direction.options[direction.selectedIndex].value;\n"
			. "\tJoomla.tableOrdering(order, dirn, '');\n};";

		return [
			'<?php',
			'$this->getDocument()->addScriptOptions(' . var_export($name, true) . ", ['order' => \$this->listOrder]);",
			'$this->getDocument()->getWebAssetManager()->addInlineScript('
				. var_export($script, true) . ", ['name' => " . var_export($name, true) . "], [], ['core']);",
			'?>',
		];
	}

	/**
	 * Render native draggable metadata on the ordering container.
	 *
	 * @param   string  $nameSingleCode  The single view code name.
	 *
	 * @return  array<int, string>  The generated table lines.
	 * @since   6.2.0
	 */
	protected function getTable(string $nameSingleCode): array
	{
		$lines = parent::getTable($nameSingleCode);
		$lines[3] = Indent::_(2)
			. '<tbody<?php if ($this->saveOrder && isset($saveOrderingUrl)): ?> class="js-draggable" data-url="<?php echo htmlspecialchars($saveOrderingUrl, ENT_QUOTES, \'UTF-8\'); ?>" data-direction="<?php echo htmlspecialchars(strtolower($this->listDirn), ENT_QUOTES, \'UTF-8\'); ?>"<?php endif; ?>><?php echo $this->loadTemplate(\'body\'); ?></tbody>';

		return $lines;
	}
}
