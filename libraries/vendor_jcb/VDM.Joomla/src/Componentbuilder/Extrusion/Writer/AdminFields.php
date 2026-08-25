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
 * Links every written field to its admin view with its display behaviour.
 *
 * The list, sort, search and filter flags come from the resolved roles rather
 * than from a positional guess, which is what removes the long standing defect
 * where the first configured list field silently lost every flag.
 *
 * What the view already links is never replaced: an existing admin_fields row
 * is discovered and kept verbatim -- every field the person wired, on the tab
 * and in the order they chose -- and only fields not yet linked are appended.
 *
 * @since 6.1.6
 */
final class AdminFields extends Writer
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
	 * @since  6.1.8
	 */
	protected LoadInterface $load;

	/**
	 * How the system places each field, read once per run.
	 *
	 * @var    array<string, array{tab: int, alignment: int}>|null
	 * @since  6.1.8
	 */
	protected ?array $placements = null;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 * @param   Source         $source    The source identity registry.
	 * @param   LoadInterface  $load      The database load boundary.
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
		return 'admin_fields';
	}

	/**
	 * The column this writer's table is keyed by.
	 *
	 * @return  string  The key column of this writer's table.
	 * @since   6.1.7
	 */
	protected function linkKey(): string
	{
		return 'admin_view';
	}

	/**
	 * Write the field links for every resolved view.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		$written = 0;

		foreach ($this->views() as $view)
		{
			if ($this->one($view))
			{
				$written++;
			}
		}

		$this->report->set('counts.admin_fields', $written);

		return $written;
	}

	/**
	 * Write the field links of one resolved view.
	 *
	 * @param   string  $view  The view name.
	 *
	 * @return  bool  True when the definition was written.
	 * @since   6.1.6
	 */
	protected function one(string $view): bool
	{
		$path = $this->path($view);
		$viewGuid = (string) $this->resolved->get($path . '.written.view.guid', '');
		$fields = (array) $this->resolved->get($path . '.field', []);
		$roles = (array) $this->resolved->get($path . '.roles', []);

		if ($viewGuid === '' || $fields === [])
		{
			return false;
		}

		$subform = [];
		$number = 0;
		$linked = false;
		$listOrder = 0;
		$editOrder = [];

		// the component's own list screen says which fields it shows, in what
		// order, and which of them it filters on -- so a field is listed
		// because the source lists it, not because a name suggested it
		$listed = $this->listed($view);
		$permissions = $this->permissions($view);
		$searchable = (array) $this->source->get(
			'mvc.' . $listed['screen'] . '.search',
			[]
		);
		$ranks = [];

		foreach ($fields as $key => $properties)
		{
			$properties = (array) $properties;
			$column = (string) $this->value($properties, 'name', (string) $key);
			$columnKey = $this->key($column);

			// a field created or updated this run recorded its identity; one
			// that already stood in JCB and was left untouched recorded the
			// identity it was matched to -- either way the link is written,
			// because a field the source relates to its view stays related
			$fieldGuid = (string) $this->resolved->get(
				$path . '.written.' . $columnKey . '.guid',
				(string) $this->resolved->get($path . '.linked.' . $columnKey . '.guid', '')
			);

			if ($fieldGuid === '')
			{
				$this->report->set(
					'skipped.admin_fields.' . $this->key($view) . '.' . $columnKey,
					'no field identity to link'
				);

				continue;
			}

			$role = (array) ($roles[$column] ?? []);
			// the list screen opens the record on one column, which is the
			// component saying which field names its records
			$isTitle = ($listed['title'] ?? '') !== ''
				? strtolower($column) === $listed['title']
				: !empty($role['title']);
			$isAlias = !empty($role['alias']);
			$shown = $listed['columns'][strtolower($column)] ?? null;
			$isList = $listed['stated'] ? $shown !== null : !empty($role['list']);
			// the screen links its records on one cell: where it says which,
			// that cell is the only link; where it does not, the first field
			// the list shows carries it
			$isLink = ($listed['title'] ?? '') !== ''
				? $isTitle
				: ($isTitle || ($isList && !$linked));

			if ($isLink)
			{
				$linked = true;
			}

			// the row speaks the admin_fields form's own value conventions:
			// checkbox flags are '1' or absent, the list and filter selections
			// are the form's option values, and the edit order counts from one
			// within each tab, exactly as a person lays a view out by hand
			// a field the system already links elsewhere is placed the way
			// those views place it: JCB's own shared fields have a home --
			// the Globally Unique ID field sits on the publishing tab in
			// every view that links it -- and rediscovering that is truer
			// than putting a shared field somewhere new
			// where the component's own edit screen puts this field is truer
			// still: it is this view's own layout, stated by the component
			$stated = is_array($properties['placement'] ?? null)
				? (array) $properties['placement'] : null;
			$placed = $stated ?? $this->placement($fieldGuid);
			$tab = (int) ($placed['tab'] ?? ($properties['tab_index'] ?? 1));
			$editOrder[$tab] = (int) ($editOrder[$tab] ?? 0) + 1;
			$order = (int) ($stated['order'] ?? $editOrder[$tab]);

			// the list selection speaks the form's own options: 1 shows the
			// field in every list view and an empty value is the form's
			// default. Never 2 -- the compiler reads that as a field with no
			// database column at all (Compiler\Model\Fields, "2 = none
			// database"), which would drop the column from the table
			$row = [
				'field' => $fieldGuid,
				'list' => $isList ? '1' : '',
				'order_list' => (string) ($isList ? ++$listOrder : 0),
				'filter' => $listed['stated']
					? (string) ($listed['filters'][strtolower($column)] ?? '')
					: ($isList ? '1' : ''),
				'tab' => (string) $tab,
				'alignment' => (int) ($placed['alignment']
					?? ($isTitle || $isAlias ? 4 : (($number % 2 === 0) ? 2 : 1))),
				'order_edit' => (string) $order
			];

			if ($isTitle)
			{
				$row['title'] = '1';
			}

			if ($isAlias)
			{
				$row['alias'] = '1';
			}

			// the head of the list says which columns it sorts by, which is
			// not the same set as the columns the body shows
			if ($listed['stated']
				? !empty($listed['sortable'][strtolower($column)])
				: $isList)
			{
				$row['sort'] = '1';
			}

			// a field the component guards on its own has an access rule
			// naming it, which is where JCB keeps the field's permission
			if (isset($permissions[strtolower($column)]))
			{
				$row['permission'] = $permissions[strtolower($column)];
			}

			// the fields the search box matches are the ones its query compares
			if ($listed['stated'] ? !empty($searchable[strtolower($column)]) : $isList)
			{
				$row['search'] = '1';
			}

			if ($isLink)
			{
				$row['link'] = '1';
			}

			$subform['addfields' . $number] = $row;

			if ($isList && $shown !== null)
			{
				$ranks['addfields' . $number] = $shown;
			}
			$number++;
		}

		if ($subform === [])
		{
			return false;
		}

		// the list order is the order the component's own list screen shows
		// its columns in, counted from one over the fields it shows
		asort($ranks);
		$order = 0;

		foreach (array_keys($ranks) as $key)
		{
			$subform[$key]['order_list'] = (string) ++$order;
		}

		$definition = new \stdClass();
		$definition->admin_view = $viewGuid;
		$definition->addfields = $this->merge($viewGuid, $subform);
		$definition->published = 1;

		return $this->store($definition);
	}

	/**
	 * The permissions the component's access rules give one view's fields.
	 *
	 * The compiler writes a field's own permission as
	 * "view.<edit|access|view>.<field>" at both levels
	 * (Compiler\Creator\AccessSections), so an action of that shape whose
	 * tail is not one of the view's own standing actions names a field.
	 *
	 * @param   string  $view  The view name.
	 *
	 * @return  array<string, array<int, string>>  Column keyed to its permission ids.
	 * @since   6.1.8
	 */
	protected function permissions(string $view): array
	{
		$view = strtolower(trim($view));
		$options = ['edit' => '1', 'access' => '2', 'view' => '3'];
		$standing = ['own', 'state', 'created', 'created_by', 'access'];
		$found = [];

		foreach (['component', $view] as $section)
		{
			foreach ((array) $this->source->get('access.' . $section, []) as $action)
			{
				$parts = explode('.', strtolower(trim((string) $action)));

				if (count($parts) !== 3 || $parts[0] !== $view
					|| !isset($options[$parts[1]]) || in_array($parts[2], $standing, true))
				{
					continue;
				}

				$found[$parts[2]][$options[$parts[1]]] = true;
			}
		}

		$permissions = [];

		foreach ($found as $column => $ids)
		{
			$ids = array_keys($ids);
			sort($ids);
			$permissions[$column] = array_values($ids);
		}

		return $permissions;
	}

	/**
	 * What the component's own list screen shows for one view.
	 *
	 * @param   string  $view  The view name.
	 *
	 * @return  array{stated: bool, screen: string, title: string, columns: array<string, int>, filters: array<string, string>, sortable: array<string, bool>}  The listing.
	 * @since   6.1.8
	 */
	protected function listed(string $view): array
	{
		$view = strtolower(trim($view));
		$screen = strtolower(trim((string) $this->source->get('mvc_list.' . $view, '')));
		$listing = [
			'stated' => false,
			'screen' => $screen,
			'title' => '',
			'columns' => [],
			'filters' => [],
			'sortable' => []
		];

		if ($screen === '')
		{
			return $listing;
		}

		$columns = (array) $this->source->get('screen.' . $screen . '.list', []);
		$filters = (array) $this->source->get('screen.' . $screen . '.filter', []);
		$sortable = (array) $this->source->get('screen.' . $screen . '.sort', []);

		if ($columns === [])
		{
			return $listing;
		}

		$order = 0;

		foreach ($columns as $column => $position)
		{
			$listing['columns'][strtolower((string) $column)] = ++$order;
		}

		foreach ($filters as $column => $kind)
		{
			if ($kind)
			{
				$listing['filters'][strtolower((string) $column)] = (string) $kind;
			}
		}

		foreach ($sortable as $column => $on)
		{
			if ($on)
			{
				$listing['sortable'][strtolower((string) $column)] = true;
			}
		}

		$listing['title'] = strtolower(trim(
			(string) $this->source->get('screen.' . $screen . '.title', '')
		));
		$listing['stated'] = true;

		return $listing;
	}

	/**
	 * How the rest of the system already places one field, when it does.
	 *
	 * JCB's shared fields have a home their own links declare: every view
	 * that links the Globally Unique ID field places it on the same tab, and
	 * that is the system saying where such a field belongs. A field nothing
	 * links yet has no such testimony, and the harvest's own reading stands.
	 *
	 * @param   string  $guid  The field's identity.
	 *
	 * @return  array{tab?: int, alignment?: int}  The observed placement.
	 * @since   6.1.8
	 */
	protected function placement(string $guid): array
	{
		$guid = strtolower(trim($guid));

		if ($guid === '' || $this->placements === null)
		{
			$this->placements ??= $this->observed();
		}

		return $this->placements[$guid] ?? [];
	}

	/**
	 * Every field placement the system's own view links declare.
	 *
	 * @return  array<string, array{tab: int, alignment: int}>  Placement by field identity.
	 * @since   6.1.8
	 */
	protected function observed(): array
	{
		$stored = $this->load->values(
			['a.addfields' => 'addfields'],
			['a' => 'admin_fields']
		);

		if (!is_array($stored))
		{
			return [];
		}

		$counts = [];

		foreach ($stored as $subform)
		{
			$rows = is_string($subform) ? json_decode($subform, true) : null;

			if (!is_array($rows))
			{
				continue;
			}

			foreach ($rows as $row)
			{
				$row = (array) $row;
				$field = strtolower(trim((string) ($row['field'] ?? '')));
				$tab = (int) ($row['tab'] ?? 0);

				if ($field === '' || $tab <= 0)
				{
					continue;
				}

				$key = $field . '|' . $tab . '|' . (int) ($row['alignment'] ?? 0);
				$counts[$field][$key] = (int) ($counts[$field][$key] ?? 0) + 1;
			}
		}

		$placements = [];

		foreach ($counts as $field => $seen)
		{
			arsort($seen);
			$parts = explode('|', (string) array_key_first($seen));
			$placements[$field] = [
				'tab' => (int) ($parts[1] ?? 1),
				'alignment' => (int) ($parts[2] ?? 1)
			];
		}

		return $placements;
	}

	/**
	 * Merge the harvested field links into what the view already links.
	 *
	 * The existing rows are the person's own wiring -- the Globally Unique ID
	 * field on its publishing tab, the orders and flags they chose -- and are
	 * kept exactly as they stand. A harvested field whose identity is already
	 * linked adds nothing; one the view does not yet link is appended, its
	 * edit order counted on from what each tab already holds.
	 *
	 * @param   string                              $viewGuid  The view the links belong to.
	 * @param   array<string, array<string, mixed>>  $subform   The harvested field links.
	 *
	 * @return  array<string, array<string, mixed>>  The merged subform.
	 * @since   6.1.8
	 */
	protected function merge(string $viewGuid, array $subform): array
	{
		$stored = $this->load->value(
			['a.addfields' => 'addfields'],
			['a' => 'admin_fields'],
			['a.admin_view' => $viewGuid]
		);
		$existing = is_string($stored) ? json_decode($stored, true) : null;

		if (!is_array($existing) || $existing === [])
		{
			return $subform;
		}

		$linked = [];
		$orders = [];

		foreach ($existing as $row)
		{
			$row = (array) $row;
			$field = strtolower(trim((string) ($row['field'] ?? '')));

			if ($field !== '')
			{
				$linked[$field] = true;
			}

			$tab = (string) ((int) ($row['tab'] ?? 1));
			$orders[$tab] = max((int) ($orders[$tab] ?? 0), (int) ($row['order_edit'] ?? 0));
		}

		$merged = $existing;
		$number = 0;

		foreach ($subform as $row)
		{
			if (isset($linked[strtolower(trim((string) $row['field']))]))
			{
				continue;
			}

			$tab = (string) ((int) ($row['tab'] ?? 1));
			$orders[$tab] = (int) ($orders[$tab] ?? 0) + 1;
			$row['order_edit'] = (string) $orders[$tab];

			while (isset($merged['addfields' . $number]))
			{
				$number++;
			}

			$merged['addfields' . $number] = $row;
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
