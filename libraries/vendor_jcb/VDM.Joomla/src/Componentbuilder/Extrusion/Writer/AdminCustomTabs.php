<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Writer;


use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Writer;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Writes the custom tab definitions a view's resolved tabs describe.
 *
 * A tab name that came from a table definition class or a form fieldset is a
 * stated intent, so it is preserved rather than collapsed into one tab.
 *
 * @since 6.1.6
 */
final class AdminCustomTabs extends Writer
{

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.6
	 */
	protected Guid $guid;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	protected Source $source;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 * @param   Guid           $guid      The identity resolver.
	 * @param   Source         $source    The source identity registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		Guid $guid,
		Source $source
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->guid = $guid;
		$this->source = $source;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.6
	 */
	protected function table(): string
	{
		return 'admin_custom_tabs';
	}

	/**
	 * Write the custom tabs for every resolved view.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		if (!$this->config->get('tabs', true))
		{
			return 0;
		}

		$written = 0;

		foreach ($this->views() as $view)
		{
			if ($this->one($view))
			{
				$written++;
			}
		}

		$this->report->set('counts.admin_custom_tabs', $written);

		return $written;
	}

	/**
	 * Write the custom tabs of one resolved view.
	 *
	 * @param   string  $view  The view name.
	 *
	 * @return  bool  True when a definition was written.
	 * @since   6.1.6
	 */
	protected function one(string $view): bool
	{
		$path = $this->path($view);
		$viewGuid = (string) $this->resolved->get($path . '.written.view.guid', '');
		$tabs = (array) $this->resolved->get($path . '.tabs', []);

		if ($viewGuid === '' || count($tabs) < 2)
		{
			return false;
		}

		$subform = [];
		$number = 0;

		foreach ($tabs as $name)
		{
			$subform['tabs' . $number] = [
				'name' => (string) $name,
				'html' => '',
				'php' => ''
			];
			$number++;
		}

		$definition = new \stdClass();
		$definition->guid = $this->guid->derive([$this->option(), 'admin_custom_tabs', $view]);
		$definition->admin_view = $viewGuid;
		$definition->tabs = json_encode($subform, JSON_FORCE_OBJECT);
		$definition->published = 1;

		return $this->store($definition);
	}

	/**
	 * The component option, when it is known.
	 *
	 * @return  string  The com_ prefixed option, or an empty string.
	 * @since   6.1.6
	 */
	protected function option(): string
	{
		return (string) $this->source->get('code_name', '');
	}
}
