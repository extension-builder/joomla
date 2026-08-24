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

			$subform['addadmin_views' . $number] = $this->settings(
				$view,
				$viewGuid,
				$number + 1
			);
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
	 * The link settings one view's own source states.
	 *
	 * Each switch on this link decides real structure, so each is read from
	 * the component itself rather than assumed. Joomla's own columns say what
	 * a view supports -- a table carrying checked_out and checked_out_time
	 * checks its records in, one carrying version keeps their history, one
	 * carrying metakey and metadesc has metadata, one carrying access has an
	 * access level -- and the manifest's administration menu says which views
	 * the component puts in its menu, and under what icon. Where the source
	 * says nothing, the form's own declared default stands, which is exactly
	 * what a person adding the link by hand would get.
	 *
	 * @param   string  $view      The view name.
	 * @param   string  $viewGuid  The written view's identity.
	 * @param   int     $order     The order this link takes.
	 *
	 * @return  array<string, mixed>  The link row.
	 * @since   6.1.8
	 */
	protected function settings(string $view, string $viewGuid, int $order): array
	{
		$columns = array_map(
			'strtolower',
			(array) $this->resolved->get($this->path($view) . '.columns', [])
		);
		$menu = (array) $this->source->get('menu', []);
		$single = strtolower((string) $this->resolved->get(
			$this->path($view) . '.name_single',
			$view
		));
		$list = strtolower((string) $this->resolved->get(
			$this->path($view) . '.name_list',
			$single . 's'
		));
		$entry = $menu[$list] ?? ($menu[$single] ?? null);
		$has = static fn (string ...$wanted): string
			=> array_intersect($wanted, $columns) === $wanted ? '1' : '';

		return [
			'adminview' => $viewGuid,
			'icomoon' => (string) (($entry['icon'] ?? '') !== ''
				? $this->icon((string) $entry['icon']) : ''),
			// the manifest's own menu says which views it lists
			'mainmenu' => $entry !== null ? '1' : '',
			'dashboard_add' => '',
			'dashboard_list' => $entry !== null ? '1' : '',
			'submenu' => '1',
			'checkin' => $has('checked_out', 'checked_out_time'),
			'history' => $has('version'),
			'joomla_fields' => '',
			'metadata' => $has('metakey', 'metadesc'),
			'access' => $has('access'),
			'port' => '',
			'add_api' => '0',
			'filter' => '2',
			'edit_create_site_view' => '',
			'order' => (string) $order
		];
	}

	/**
	 * The icon slug a manifest menu entry names, when it names one plainly.
	 *
	 * A manifest states its icon as a Joomla image reference, which is not
	 * the icon set this link chooses from. Only the trailing token is worth
	 * carrying, and only when it reads as a plain slug; anything else is left
	 * for the person to choose, rather than written as a guess.
	 *
	 * @param   string  $icon  The manifest's stated icon.
	 *
	 * @return  string  The slug, or an empty string.
	 * @since   6.1.8
	 */
	protected function icon(string $icon): string
	{
		$slug = strtolower(trim((string) strrchr('/' . str_replace(
			['class:', '\\'],
			['', '/'],
			$icon
		), '/'), '/'));

		return preg_match('/^[a-z][a-z0-9\-]{1,30}$/', $slug) === 1 ? $slug : '';
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
