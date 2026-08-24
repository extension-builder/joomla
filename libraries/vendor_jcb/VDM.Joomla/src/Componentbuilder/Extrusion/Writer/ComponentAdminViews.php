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

			// every option the component_admin_views form offers is stated,
			// with the form's own defaults, so the person who opens the link
			// finds a completely laid-out view rather than blank switches
			$subform['addadmin_views' . $number] = [
				'adminview' => $viewGuid,
				'icomoon' => 'joomla',
				'mainmenu' => '1',
				'dashboard_add' => '1',
				'dashboard_list' => '1',
				'submenu' => '1',
				'checkin' => '1',
				'history' => '1',
				'joomla_fields' => '1',
				'metadata' => '1',
				'access' => '1',
				'port' => '1',
				'add_api' => '0',
				'filter' => '2',
				'edit_create_site_view' => '',
				'order' => (string) ($number + 1)
			];
			$number++;
		}

		if ($subform === [])
		{
			return 0;
		}

		$definition = new \stdClass();
		$definition->joomla_component = $componentGuid;
		$definition->addadmin_views = $this->merge($componentGuid, $subform);
		$definition->published = 1;

		if (!$this->store($definition))
		{
			return 0;
		}

		$this->report->set('counts.component_admin_views', $number);

		return $number;
	}

	/**
	 * Merge the harvested view links into what the component already links.
	 *
	 * The existing rows are the person's own settings -- the icons, dashboard
	 * and menu switches they chose per view -- and are kept exactly as they
	 * stand. A view already linked adds nothing; one the component does not
	 * yet link is appended, its order counted on from what already stands.
	 *
	 * @param   string                               $componentGuid  The component the links belong to.
	 * @param   array<string, array<string, mixed>>  $subform        The harvested view links.
	 *
	 * @return  array<string, array<string, mixed>>  The merged subform.
	 * @since   6.1.8
	 */
	protected function merge(string $componentGuid, array $subform): array
	{
		$stored = $this->load->value(
			['a.addadmin_views' => 'addadmin_views'],
			['a' => 'component_admin_views'],
			['a.joomla_component' => $componentGuid]
		);
		$existing = is_string($stored) ? json_decode($stored, true) : null;

		if (!is_array($existing) || $existing === [])
		{
			return $subform;
		}

		$linked = [];
		$order = 0;

		foreach ($existing as $row)
		{
			$row = (array) $row;
			$view = strtolower(trim((string) ($row['adminview'] ?? '')));

			if ($view !== '')
			{
				$linked[$view] = true;
			}

			$order = max($order, (int) ($row['order'] ?? 0));
		}

		$merged = $existing;
		$number = 0;

		foreach ($subform as $row)
		{
			if (isset($linked[strtolower(trim((string) $row['adminview']))]))
			{
				continue;
			}

			$row['order'] = (string) (++$order);

			while (isset($merged['addadmin_views' . $number]))
			{
				$number++;
			}

			$merged['addadmin_views' . $number] = $row;
		}

		return $merged;
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
