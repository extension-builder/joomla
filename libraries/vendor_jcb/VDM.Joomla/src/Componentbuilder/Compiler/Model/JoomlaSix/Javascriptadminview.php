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

namespace VDM\Joomla\Componentbuilder\Compiler\Model\JoomlaSix;


use Joomla\CMS\Application\CMSApplicationInterface;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Model\Javascriptadminview as SharedJavascriptadminview;


/**
 * Collect Joomla 6 view scripts and explain changed footer execution timing.
 *
 * The existing dispenser retains script content, GUI metadata, and global scope.
 *
 * @since  6.2.0
 */
final class Javascriptadminview extends SharedJavascriptadminview
{
	/**
	 * Compiler application's diagnostic channel.
	 *
	 * @var    CMSApplicationInterface
	 * @since  6.2.0
	 */
	protected CMSApplicationInterface $app;

	/**
	 * Inject script collection and compiler diagnostics.
	 *
	 * @param   Dispenser                $dispenser  Existing custom-code collector.
	 * @param   CMSApplicationInterface  $app        Compiler application.
	 *
	 * @since   6.2.0
	 */
	public function __construct(Dispenser $dispenser, CMSApplicationInterface $app)
	{
		parent::__construct($dispenser);
		$this->app = $app;
	}

	/**
	 * Explain the head placement of an enabled, nonempty footer script.
	 *
	 * @param   object  $item      The view containing the decoded script.
	 * @param   string  $scripter  The original JavaScript field name.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function reportScriptPlacement(object $item, string $scripter): void
	{
		if (!in_array($scripter, ['javascript_view_footer', 'javascript_views_footer'], true)
			|| trim((string) $item->{$scripter}) === '')
		{
			return;
		}

		$this->app->enqueueMessage(sprintf(
			'Joomla 6 loads %s for the %s view through the Web Asset Manager in the document head. '
			. 'The JavaScript remains unchanged and in global scope. Immediate DOM access must use DOMContentLoaded.',
			$scripter,
			htmlspecialchars((string) $item->name_single_code, ENT_QUOTES, 'UTF-8')
		), 'warning');
	}
}
