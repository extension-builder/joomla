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

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View;


use InvalidArgumentException;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\DocumentScripts as SharedDocumentScripts;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\JavaScriptFile;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;


/**
 * Supply ordered, escaped script entries to Joomla 6 view templates.
 *
 * Build hooks may supply view_script_paths and view_script_dependencies on the
 * current settings object as lists of non-empty strings. These are transient
 * compiler inputs, not inferred PHP or implicitly persisted authoring fields.
 *
 * @since  6.2.1
 */
final class DocumentScripts extends SharedDocumentScripts
{
	/**
	 * The current build's placeholder expander.
	 *
	 * @var   Placeholder
	 * @since 6.2.1
	 */
	protected Placeholder $placeholder;

	/**
	 * Constructor.
	 *
	 * @param   JavaScriptFile  $javascript    The selected view file builder.
	 * @param   ContentMulti    $contentmulti  The view-scoped content registry.
	 * @param   Placeholder     $placeholder   The build placeholder expander.
	 *
	 * @since   6.2.1
	 */
	public function __construct(JavaScriptFile $javascript, ContentMulti $contentmulti,
		Placeholder $placeholder)
	{
		parent::__construct($javascript, $contentmulti);
		$this->placeholder = $placeholder;
	}

	/**
	 * Build and replace every optional script slot for the current view.
	 *
	 * @param   array   $view    The current view definition.
	 * @param   string  $target  The upper-case build target.
	 *
	 * @return  void
	 * @throws  InvalidArgumentException  When a supplied input is not a string list.
	 * @since   6.2.1
	 */
	public function set(array &$view, string $target): void
	{
		$paths = $this->entries($view, 'view_script_paths');
		$dependencies = $this->entries($view, 'view_script_dependencies');
		$prefix = $view['settings']->code . '|' . $target;

		$this->contentmulti->set($prefix . '_VIEW_SCRIPT_ENTRY',
			$this->javascript->get($view, $target)
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
	 * @throws  InvalidArgumentException  When the input contains invalid values.
	 * @since   6.2.1
	 */
	private function entries(array $view, string $property): string
	{
		$values = $view['settings']->{$property} ?? [];

		if (!is_array($values) || !array_is_list($values))
		{
			throw new InvalidArgumentException($property . ' must be an ordered list of strings.');
		}

		$entries = '';

		foreach ($values as $value)
		{
			if (!is_string($value) || trim($value) === '')
			{
				throw new InvalidArgumentException($property . ' must contain non-empty strings.');
			}

			$value = $this->placeholder->update_($value);

			if (trim($value) === '')
			{
				throw new InvalidArgumentException($property . ' expanded to an empty value.');
			}

			$entries .= PHP_EOL . Indent::_(3) . var_export($value, true) . ',';
		}

		return $entries;
	}
}
