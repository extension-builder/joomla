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
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Constants;
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
	 * The Constants Resolver.
	 *
	 * @var    Constants
	 * @since  6.1.8
	 */
	protected Constants $constants;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config     The extrusion configuration.
	 * @param   Resolved       $resolved   The resolved definition registry.
	 * @param   ItemInterface  $item       The JCB data item writer.
	 * @param   Report         $report     The run report registry.
	 * @param   View           $view       The classified view registry.
	 * @param   Guid           $guid       The identity resolver.
	 * @param   Source         $source     The source identity registry.
	 * @param   Constants      $constants  The language constant resolver.
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
		Source $source,
		Constants $constants
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->view = $view;
		$this->guid = $guid;
		$this->source = $source;
		$this->constants = $constants;
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
				// beside its template, by a resolved view's name, or by one of
				// the component's own admin views in the database -- is that
				// view's own generated output: no custom view and no get is owed
				if ($kind === 'custom_admin_view'
					&& ($answered !== null || !empty($entry['crud'])
						|| !$this->named($name)
						|| array_key_exists(
							strtolower(trim($name)),
							(array) $this->resolved->get('screen.list_views', [])
						)
						|| in_array(
							strtolower(trim($name)),
							(array) $this->resolved->get('existing.admin_view_names', []),
							true
						)))
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

		// every get JCB holds carries its containers, empty or not: the
		// compiler reads each of them and a record without them is a record
		// the interface cannot open on
		$definition->join_view_table = [];
		$definition->join_db_table = [];
		$definition->filter = [];
		$definition->where = [];
		$definition->order = [];
		$definition->group = [];
		$definition->global = [];

		if ($answered !== null)
		{
			// the real relationship: this view feeds from the admin view the
			// run itself wrote, item get for the single name, list query for
			// the plural -- exactly how JCB wires a front end to its table
			$definition->main_source = '1';
			$definition->gettype = (string) $answered['gettype'];
			$definition->view_table_main = (string) $answered['guid'];
			$definition->select_all = '1';
			$definition->view_selection = 'a.*';

			// an item get without this filter has no where clause at all, and
			// the generated getItem then answers with the first row of the
			// table for every id (Compiler\Dynamicget\QueryFilter writes the
			// clause from filter_type 1, and nothing else does)
			if ((int) $answered['gettype'] === 1)
			{
				$definition->filter = [
					'filter0' => [
						'filter_type' => '1',
						'state_key' => 'id',
						'operator' => '1',
						'table_key' => 'a.id'
					]
				];
			}

			// the view's own query states which records it keeps and in what
			// order, and JCB holds both beside the get rather than in code
			$stated = $this->query($name);

			if ($stated !== null)
			{
				foreach ($this->clauses($stated['code']) as $clause => $rows)
				{
					if ($rows !== [])
					{
						$definition->{$clause} = $rows;
					}
				}
			}
		}
		else
		{
			// no admin view answers, so the data comes from custom code --
			// which is how JCB's own screens without a table are built, and
			// the component already wrote that code: its model builds the
			// very query a custom get holds. The shape follows the method
			// recovered, and stays an item or a list get either way, because
			// the compiler writes a view's files only for those two
			$definition->main_source = '3';
			$definition->gettype = '1';
			$recovered = $this->query($name);

			if ($recovered !== null)
			{
				$definition->gettype = $recovered['gettype'];
				$backing = $this->backing($recovered['code']);

				if ($backing !== null)
				{
					// the query itself names the table it reads, and this run
					// wrote the admin view that owns it -- so the get is the
					// real relationship rather than code standing in for one
					$definition->main_source = '1';
					$definition->view_table_main = $backing;
					$definition->select_all = '1';
					$definition->view_selection = 'a.*';

					if ((int) $definition->gettype === 1)
					{
						$definition->filter = [
							'filter0' => [
								'filter_type' => '1',
								'state_key' => 'id',
								'operator' => '1',
								'table_key' => 'a.id'
							]
						];
					}

					// the query states which records it keeps and in what order,
					// and JCB holds both beside the source rather than in code
					foreach ($this->clauses($recovered['code']) as $clause => $rows)
					{
						if ($rows !== [])
						{
							$definition->{$clause} = $rows;
						}
					}

					$this->report->set(
						'dynamic_get.related.' . $this->key($name),
						'reads the table of an admin view this run wrote'
					);
				}
				else
				{
					$definition->php_custom_get = $recovered['code'];
					$this->report->set(
						'dynamic_get.recovered.' . $this->key($name),
						$recovered['method'] . ' of the source model'
					);
				}
			}
			else
			{
				$this->report->set(
					'dynamic_get.custom.' . $this->key($name),
					'no admin view answers for this view and its model states no '
					. 'query, so its get awaits a method body'
				);
			}
		}

		if (!$this->store($definition))
		{
			return false;
		}

		$this->resolved->set('dynamic_get.' . $kind . '.' . $this->key($key) . '.guid', $guid);

		return true;
	}

	/**
	 * Whether the component itself names one screen.
	 *
	 * @param   string  $name  The folder's code name.
	 *
	 * @return  bool  True when the component names it.
	 * @since   6.1.8
	 */
	protected function named(string $name): bool
	{
		$name = strtolower(trim($name));
		$menu = (array) $this->source->get('menu', []);
		$screens = (array) $this->source->get('access_screens', []);

		if (isset($menu[$name]) || !empty($screens[$name]))
		{
			return true;
		}

		return $menu === [] && $screens === [];
	}

	/**
	 * The conditions and the ordering one query states.
	 *
	 * A query keeps its records by plain comparisons and orders them by plain
	 * columns, and JCB holds each as a row of its own beside the get. Only the
	 * comparisons a row can hold are read: a value the query computes is left
	 * for the person, because storing half of it would misstate the source.
	 *
	 * @param   string  $code  The query's code.
	 *
	 * @return  array{where: array<string, array<string, string>>, order: array<string, array<string, string>>}  The clauses.
	 * @since   6.1.8
	 */
	protected function clauses(string $code): array
	{
		$clauses = ['where' => [], 'order' => []];
		$number = 0;

		if (preg_match_all(
			'/->where\(\s*[\'"]\s*(a\.[A-Za-z0-9_]+)\s*=\s*([0-9]+)\s*[\'"]\s*\)/',
			$code,
			$found,
			PREG_SET_ORDER
		) !== false)
		{
			foreach ($found as $match)
			{
				$clauses['where']['where' . $number] = [
					'table_key' => strtolower($match[1]),
					'operator' => '1',
					'value_key' => $match[2]
				];
				$number++;
			}
		}

		$number = 0;

		if (preg_match_all(
			'/->order\(\s*[\'"]\s*(a\.[A-Za-z0-9_]+)\s+(ASC|DESC)\s*[\'"]\s*\)/i',
			$code,
			$found,
			PREG_SET_ORDER
		) !== false)
		{
			foreach ($found as $match)
			{
				$clauses['order']['order' . $number] = [
					'table_key' => strtolower($match[1]),
					'direction' => strtoupper($match[2])
				];
				$number++;
			}
		}

		return $clauses;
	}

	/**
	 * The admin view whose table one query reads, when this run wrote it.
	 *
	 * A query names its table outright, and a table this run turned into an
	 * admin view is that view -- which is what JCB stores as the get's own
	 * source instead of the code that would read it by hand.
	 *
	 * @param   string  $code  The query's code.
	 *
	 * @return  string|null  The admin view's identity, or null.
	 * @since   6.1.8
	 */
	protected function backing(string $code): ?string
	{
		if (preg_match('/->from\(.*?[\'"]#__([A-Za-z0-9_]+)[\'"]/s', $code, $found) !== 1)
		{
			return null;
		}

		$table = strtolower(trim((string) $found[1]));

		foreach ($this->views() as $view)
		{
			$path = $this->path($view);
			$stated = strtolower(trim(preg_replace(
				'/^#__/',
				'',
				(string) $this->resolved->get($path . '.table', '')
			) ?? ''));

			if ($stated !== '' && $stated === $table)
			{
				$guid = (string) $this->resolved->get($path . '.written.view.guid', '');

				return $guid === '' ? null : $guid;
			}
		}

		return null;
	}

	/**
	 * The query one view's own model builds, when it builds one.
	 *
	 * @param   string  $name  The view's code name.
	 *
	 * @return  array{code: string, gettype: string, method: string}|null  The query, or null.
	 * @since   6.1.8
	 */
	protected function query(string $name): ?array
	{
		$name = strtolower(trim($name));

		// a list query and a set of items are both a list; a single item is
		// an item get, which is the shape JCB stores for each
		foreach ([
			'query' => ['gettype' => '2', 'method' => 'getListQuery()'],
			'items' => ['gettype' => '2', 'method' => 'getItems()'],
			'item' => ['gettype' => '1', 'method' => 'getItem()']
		] as $key => $shape)
		{
			$code = trim((string) $this->source->get('mvc.' . $name . '.' . $key, ''));

			if ($code === '')
			{
				continue;
			}

			// the code speaks text again, because JCB's compiler is what makes
			// the constant, and a constant stored here compiles into a key
			// built from a key
			return [
				'code' => $this->constants->reverse($code),
				'gettype' => $shape['gettype'],
				'method' => $shape['method']
			];
		}

		return null;
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
