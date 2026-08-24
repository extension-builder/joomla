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
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Links every written admin view to the JCB component being built.
 *
 * Without this the extruded views exist but belong to nothing, which is the state
 * the run must never be left in. An update run merges with the links the
 * component already has rather than replacing them.
 *
 * @since 6.1.6
 */
final class ComponentAdminViews extends Writer
{
	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	protected Source $source;

	/**
	 * The database load boundary.
	 *
	 * @var    LoadInterface
	 * @since  6.1.7
	 */
	protected LoadInterface $load;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 * @param   Source         $source    The source identity registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		Source $source,
		LoadInterface $load
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->source = $source;
		$this->load = $load;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.6
	 */
	protected function table(): string
	{
		return 'component_admin_views';
	}

	/**
	 * The column this writer's table is keyed by.
	 *
	 * @return  string  The key column of this writer's table.
	 * @since   6.1.7
	 */
	protected function linkKey(): string
	{
		return 'joomla_component';
	}

	/**
	 * Link every written view to the target component.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		$component = (int) $this->config->get('component', 0);

		// the link column holds the component's guid -- the Table class
		// defines it as the joomla_component entity's guid key. A component
		// this run created recorded its guid; a targeted one resolves its
		// configured id to the identity the link speaks
		$componentGuid = trim((string) $this->resolved->get('component.guid', ''));

		if ($componentGuid === '' && $component > 0)
		{
			$componentGuid = trim((string) ($this->load->value(
				['a.guid' => 'guid'],
				['a' => 'joomla_component'],
				['a.id' => $component]
			) ?? ''));
		}

		if ($componentGuid === '')
		{
			$this->report->set('failed.component_admin_views.no_component', true);

			return 0;
		}

		$subform = [];
		$number = 0;

		foreach ($this->views() as $view)
		{
			$viewGuid = (string) $this->resolved->get(
				$this->path($view) . '.written.view.guid',
				''
			);

			if ($viewGuid === '')
			{
				continue;
			}

			$subform['addadmin_views' . $number] = [
				'adminview' => $viewGuid,
				'icomoon' => 'joomla',
				'mainmenu' => 1,
				'dashboard_add' => 1,
				'dashboard_list' => 1,
				'submenu' => 1,
				'checkin' => 1,
				'history' => 1,
				'metadata' => 1,
				'access' => 1,
				'port' => 1,
				'edit_create_site_view' => 0,
				'order' => $number + 1
			];
			$number++;
		}

		if ($subform === [])
		{
			return 0;
		}

		$definition = new \stdClass();
		$definition->joomla_component = $componentGuid;
		$definition->addadmin_views = $subform;
		$definition->published = 1;

		if (!$this->store($definition))
		{
			return 0;
		}

		$this->report->set('counts.component_admin_views', $number);

		return $number;
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
