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
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Links every written field to its admin view with its display behaviour.
 *
 * The list, sort, search and filter flags come from the resolved roles rather
 * than from a positional guess, which is what removes the long standing defect
 * where the first configured list field silently lost every flag.
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
		Source $source
	)
	{
		parent::__construct($config, $resolved, $item, $report);

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

		foreach ($fields as $key => $properties)
		{
			$properties = (array) $properties;
			$column = (string) $this->value($properties, 'name', (string) $key);
			$fieldGuid = (string) $this->resolved->get(
				$path . '.written.' . $this->key($column) . '.guid',
				''
			);

			if ($fieldGuid === '')
			{
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

			$subform['addfields' . $number] = [
				'field' => $fieldGuid,
				'list' => $isList ? 1 : 0,
				'order_list' => $isList ? ++$listOrder : 0,
				'title' => $isTitle ? 1 : 0,
				'alias' => $isAlias ? 1 : 0,
				'sort' => $isList ? 1 : 0,
				'search' => $isList ? 1 : 0,
				'filter' => $isList ? 1 : 0,
				'link' => $isLink ? 1 : 0,
				'tab' => (int) ($properties['tab_index'] ?? 1),
				'alignment' => $isTitle || $isAlias ? 4 : (($number % 2 === 0) ? 2 : 1),
				'order_edit' => $number,
				'permission' => 0
			];
			$number++;
		}

		if ($subform === [])
		{
			return false;
		}

		$definition = new \stdClass();
		$definition->admin_view = $viewGuid;
		$definition->addfields = $subform;
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
