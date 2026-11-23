<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    23rd November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\View;


use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;


/**
 * Place generated view scripts in the document hook for the selected target.
 *
 * Joomla 3–5 retain their library-loader include. The Joomla 6 variant places
 * script entries in the template-owned asset list instead.
 *
 * @since  6.2.1
 */
class DocumentScripts
{
	/**
	 * The generated view file builder.
	 *
	 * @var   JavaScriptFile
	 * @since 6.2.1
	 */
	protected JavaScriptFile $javascript;

	/**
	 * The view-scoped content registry.
	 *
	 * @var   ContentMulti
	 * @since 6.2.1
	 */
	protected ContentMulti $contentmulti;

	/**
	 * Constructor.
	 *
	 * @param   JavaScriptFile  $javascript    The selected view file builder.
	 * @param   ContentMulti    $contentmulti  The view-scoped content registry.
	 *
	 * @since   6.2.1
	 */
	public function __construct(JavaScriptFile $javascript, ContentMulti $contentmulti)
	{
		$this->javascript = $javascript;
		$this->contentmulti = $contentmulti;
	}

	/**
	 * Build the optional file and retain the legacy include position.
	 *
	 * @param   array   $view    The current view definition.
	 * @param   string  $target  The upper-case build target.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function set(array &$view, string $target): void
	{
		$this->contentmulti->add($view['settings']->code . '|' . $target . '_LIBRARIES_LOADER',
			$this->javascript->get($view, $target), false
		);
	}
}
