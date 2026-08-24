<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    24th August, 2026
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
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Writes the dynamic get every recovered front end and custom view feeds from.
 *
 * In JCB a view without a dynamic get displays nothing: the main_get column is
 * the view's whole data source. A recovered view named after an admin view of
 * this same run gets the real relationship -- a back end source aimed at that
 * admin view, an item get for the single name and a list query for the plural.
 * A view no admin view answers for still gets its dynamic get, as a custom-get
 * scaffold that names the method the author completes; what it can never get
 * is nothing, because a view left without a source is a view left unrelated.
 *
 * @since 6.1.8
 */
final class DynamicGet extends Writer
{
	/**
	 * The View Registry.
	 *
	 * @var    View
	 * @since  6.1.8
	 */
	protected View $view;

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.8
	 */
	protected Guid $guid;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.8
	 */
	protected Source $source;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 * @param   View           $view      The classified view registry.
	 * @param   Guid           $guid      The identity resolver.
	 * @param   Source         $source    The source identity registry.
	 *
	 * @since   6.1.8
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		View $view,
		Guid $guid,
		Source $source
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->view = $view;
		$this->guid = $guid;
		$this->source = $source;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.8
	 */
	protected function table(): string
	{
		return 'dynamic_get';
	}

	/**
	 * Write one dynamic get for every recovered view that needs a source.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.8
	 */
	public function write(): int
	{
		$written = 0;

		foreach (['site_view', 'custom_admin_view'] as $kind)
		{
			foreach ((array) $this->view->get($kind) as $key => $entry)
			{
				$entry = (array) $entry;
				$name = (string) ($entry['name'] ?? $key);

				if ($name === '')
				{
					continue;
				}

				$answered = $this->answered($name);

				// a custom candidate a table view answers for -- by an editor
				// beside its template or by name -- is that view's own
				// generated output, so no custom view and no get is owed
				if ($kind === 'custom_admin_view'
					&& ($answered !== null || !empty($entry['crud'])))
				{
					continue;
				}

				if ($this->one($kind, (string) $key, $name, $answered))
				{
					$written++;
				}
			}
		}

		if ($written > 0)
		{
			$this->report->set('counts.dynamic_get', $written);
		}

		return $written;
	}

	/**
	 * Write one view's dynamic get and record it for the view to link.
	 *
	 * @param   string      $kind      The view kind the get belongs to.
	 * @param   string      $key       The view's registry key.
	 * @param   string      $name      The view's code name.
	 * @param   array|null  $answered  The admin view that answers, when one does.
	 *
	 * @return  bool  True when the definition was written.
	 * @since   6.1.8
	 */
	protected function one(string $kind, string $key, string $name, ?array $answered): bool
	{
		$guid = $this->guid->derive([$this->option(), 'dynamic_get', $name]);
		$readable = ucwords(str_replace(['_', '-'], ' ', $name));

		$definition = new \stdClass();
		$definition->guid = $guid;
		$definition->name = $readable . ' Data';
		$definition->pagination = '1';
		$definition->published = 1;

		if ($answered !== null)
		{
			// the real relationship: this view feeds from the admin view the
			// run itself wrote, item get for the single name, list query for
			// the plural -- exactly how JCB wires a front end to its table
			$definition->main_source = '1';
			$definition->gettype = (string) $answered['gettype'];
			$definition->view_table_main = (string) $answered['guid'];
			$definition->select_all = '1';
		}
		else
		{
			// no admin view answers, so the source cannot be reconstructed
			// without guessing -- a custom get scaffold names the method the
			// author completes, and the report says so in plain words
			$definition->main_source = '3';
			$definition->gettype = '3';
			$definition->getcustom = 'get' . str_replace(' ', '', $readable);
			$this->report->set(
				'dynamic_get.custom.' . $this->key($name),
				'no admin view answers for this view, so its get is a custom '
				. 'scaffold awaiting its method body'
			);
		}

		if (!$this->store($definition))
		{
			return false;
		}

		$this->resolved->set('dynamic_get.' . $kind . '.' . $this->key($key) . '.guid', $guid);

		return true;
	}

	/**
	 * The resolved admin view that answers for one view name, when one does.
	 *
	 * @param   string  $name  The recovered view's code name.
	 *
	 * @return  array{guid: string, gettype: int}|null  The source, or null.
	 * @since   6.1.8
	 */
	protected function answered(string $name): ?array
	{
		$name = strtolower(trim($name));

		foreach ($this->views() as $view)
		{
			$path = $this->path($view);
			$single = strtolower((string) $this->resolved->get($path . '.name_single', $view));
			$list = strtolower((string) $this->resolved->get($path . '.name_list', $single . 's'));
			$written = (string) $this->resolved->get($path . '.written.view.guid', '');

			if ($written === '')
			{
				continue;
			}

			if ($name === $single || $name === strtolower($view))
			{
				return ['guid' => $written, 'gettype' => 1];
			}

			if ($name === $list)
			{
				return ['guid' => $written, 'gettype' => 2];
			}
		}

		return null;
	}

	/**
	 * The component option, when it is known.
	 *
	 * @return  string  The com_ prefixed option, or an empty string.
	 * @since   6.1.8
	 */
	protected function option(): string
	{
		return (string) $this->source->get('code_name', '');
	}
}
