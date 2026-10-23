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

namespace VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaSix;

use Joomla\CMS\Application\CMSApplicationInterface;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Field\Customcode as SharedCustomcode;

/**
 * Explain native footer placement for field scripts collected by any extension.
 *
 * @since 6.2.0
 */
final class Customcode extends SharedCustomcode
{
	/**
	 * Compiler application's diagnostic channel.
	 *
	 * @var    CMSApplicationInterface
	 * @since  6.2.0
	 */
	protected CMSApplicationInterface $app;

	/**
	 * Inject the existing collector and application diagnostics.
	 *
	 * @param   Dispenser                $dispenser  Custom-code collection service.
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
	 * Warn without wrapping authored source or changing its global bindings.
	 *
	 * @param   int     $id        Field identifier.
	 * @param   object  $field     Field definition carrying the decoded source.
	 * @param   string  $scripter  Original footer source field.
	 * @param   string  $target    View or extension collection key.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function reportScriptPlacement(int $id, object $field, string $scripter, string $target): void
	{
		if (!in_array($scripter, ['javascript_view_footer', 'javascript_views_footer'], true)
			|| trim((string) $field->{$scripter}) === '')
		{
			return;
		}

		$this->app->enqueueMessage(sprintf(
			'Joomla 6 loads %s for field %d in %s through the Web Asset Manager in the document head. '
			. 'The JavaScript remains unchanged and in global scope. Immediate DOM access must use DOMContentLoaded.',
			$scripter,
			$id,
			htmlspecialchars($target, ENT_QUOTES, 'UTF-8')
		), 'warning');
	}
}
