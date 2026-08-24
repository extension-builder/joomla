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
			$isTitle = !empty($role['title']);
			$isAlias = !empty($role['alias']);
			$isList = !empty($role['list']);
			$isLink = $isTitle || ($isList && !$linked);

			if ($isLink)
			{
				$linked = true;
			}

			// the row speaks the admin_fields form's own value conventions:
			// checkbox flags are '1' or absent, the list and filter selections
			// are the form's option values, and the edit order counts from one
			// within each tab, exactly as a person lays a view out by hand
			$tab = (int) ($properties['tab_index'] ?? 1);
			$editOrder[$tab] = (int) ($editOrder[$tab] ?? 0) + 1;

			$row = [
				'field' => $fieldGuid,
				'list' => $isList ? '1' : '',
				'order_list' => (string) ($isList ? ++$listOrder : 0),
				'filter' => $isList ? '1' : '',
				'tab' => (string) $tab,
				'alignment' => $isTitle || $isAlias ? 4 : (($number % 2 === 0) ? 2 : 1),
				'order_edit' => (string) $editOrder[$tab]
			];

			if ($isTitle)
			{
				$row['title'] = '1';
			}

			if ($isAlias)
			{
				$row['alias'] = '1';
			}

			if ($isList)
			{
				$row['sort'] = '1';
				$row['search'] = '1';
			}

			if ($isLink)
			{
				$row['link'] = '1';
			}

			$subform['addfields' . $number] = $row;
			$number++;
		}

		if ($subform === [])
		{
			return false;
		}

		$definition = new \stdClass();
		$definition->admin_view = $viewGuid;
		$definition->addfields = $this->merge($viewGuid, $subform);
		$definition->published = 1;

		return $this->store($definition);
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
