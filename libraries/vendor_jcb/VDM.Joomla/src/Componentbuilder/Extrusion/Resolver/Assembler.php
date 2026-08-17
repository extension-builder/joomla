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

namespace VDM\Joomla\Componentbuilder\Extrusion\Resolver;


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Schema;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Table;


/**
 * Turns everything the readers gathered into one resolved definition set.
 *
 * The assembler is the seam between reading and writing: it walks the tables the
 * schema and table-definition registries know about, asks the precedence engine
 * for each column, and then lets the role, tab, condition and relation resolvers
 * derive the structure around those fields. Writers read only what lands here, so
 * nothing downstream has to understand the source tree.
 *
 * @since 6.1.6
 */
final class Assembler
{
	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.6
	 */
	protected Config $config;

	/**
	 * The Schema Registry.
	 *
	 * @var    Schema
	 * @since  6.1.6
	 */
	protected Schema $schema;

	/**
	 * The Table Registry.
	 *
	 * @var    Table
	 * @since  6.1.6
	 */
	protected Table $table;

	/**
	 * The Resolved Registry.
	 *
	 * @var    Resolved
	 * @since  6.1.6
	 */
	protected Resolved $resolved;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	protected Source $source;

	/**
	 * The Precedence Resolver.
	 *
	 * @var    Precedence
	 * @since  6.1.6
	 */
	protected Precedence $precedence;

	/**
	 * The ViewName Resolver.
	 *
	 * @var    ViewName
	 * @since  6.1.6
	 */
	protected ViewName $viewname;

	/**
	 * The Role Resolver.
	 *
	 * @var    Role
	 * @since  6.1.6
	 */
	protected Role $role;

	/**
	 * The Tab Resolver.
	 *
	 * @var    Tab
	 * @since  6.1.6
	 */
	protected Tab $tab;

	/**
	 * The Condition Resolver.
	 *
	 * @var    Condition
	 * @since  6.1.6
	 */
	protected Condition $condition;

	/**
	 * The Relation Resolver.
	 *
	 * @var    Relation
	 * @since  6.1.6
	 */
	protected Relation $relation;

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.6
	 */
	protected Guid $guid;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Config      $config      The extrusion configuration.
	 * @param   Schema      $schema      The parsed schema registry.
	 * @param   Table       $table       The table definition registry.
	 * @param   Resolved    $resolved    The resolved definition registry.
	 * @param   Source      $source      The source identity registry.
	 * @param   Precedence  $precedence  The precedence engine.
	 * @param   ViewName    $viewname    The view name resolver.
	 * @param   Role        $role        The display role resolver.
	 * @param   Tab         $tab         The tab resolver.
	 * @param   Condition   $condition   The condition resolver.
	 * @param   Relation    $relation    The relationship resolver.
	 * @param   Guid        $guid        The identity resolver.
	 * @param   Report      $report      The run report registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Schema $schema,
		Table $table,
		Resolved $resolved,
		Source $source,
		Precedence $precedence,
		ViewName $viewname,
		Role $role,
		Tab $tab,
		Condition $condition,
		Relation $relation,
		Guid $guid,
		Report $report
	)
	{
		$this->config = $config;
		$this->schema = $schema;
		$this->table = $table;
		$this->resolved = $resolved;
		$this->source = $source;
		$this->precedence = $precedence;
		$this->viewname = $viewname;
		$this->role = $role;
		$this->tab = $tab;
		$this->condition = $condition;
		$this->relation = $relation;
		$this->guid = $guid;
		$this->report = $report;
	}

	/**
	 * Assemble every selected table into the resolved registry.
	 *
	 * @return  int  The number of views assembled.
	 * @since   6.1.6
	 */
	public function assemble(): int
	{
		$views = [];

		foreach ($this->tables() as $key => $name)
		{
			if (!$this->config->selected($name))
			{
				$this->report->set('skipped.filtered.' . $key, $name);

				continue;
			}

			$view = $this->one($key, $name);

			if ($view !== null)
			{
				$views[] = $view;
			}
		}

		$this->resolved->set('views', $views);
		$this->reconcile($views);

		return count($views);
	}

	/**
	 * Every source table both registries know about.
	 *
	 * @return  array<string, string>  Registry key mapped to the true table name.
	 * @since   6.1.6
	 */
	public function tables(): array
	{
		$tables = [];

		foreach (['schema', 'table'] as $which)
		{
			$registry = $which === 'schema' ? $this->schema : $this->table;
			$block = $registry->get('table');

			if ($block === null)
			{
				continue;
			}

			foreach ((array) $block as $key => $entry)
			{
				$entry = (array) $entry;
				$name = (string) ($entry['name'] ?? $key);

				if ($name !== '' && !isset($tables[(string) $key]))
				{
					$tables[(string) $key] = $name;
				}
			}
		}

		return $tables;
	}

	/**
	 * Assemble one table into a view definition.
	 *
	 * @param   string  $key   The registry key of the table.
	 * @param   string  $name  The true table name.
	 *
	 * @return  string|null  The view name, or null when nothing usable was found.
	 * @since   6.1.6
	 */
	protected function one(string $key, string $name): ?string
	{
		$view = $this->viewname->single($name);
		$fields = [];

		foreach ($this->columns($key) as $column)
		{
			$properties = $this->precedence->resolve($view, $name, $column);

			if ($properties !== null)
			{
				$fields[$column] = $properties;
			}
		}

		if ($fields === [])
		{
			$this->report->set('skipped.empty.' . $key, $name);

			return null;
		}

		$path = 'view.' . $this->precedence->key($view);
		$tabs = $this->tab->names($view, $fields);
		$roles = $this->role->assign($view, $fields);
		$relations = [];

		foreach ($fields as $column => $properties)
		{
			$relation = $this->relation->resolve($view, $column, $properties);

			if ($relation !== null)
			{
				$relations[] = $relation;
			}

			$this->resolved->set(
				$path . '.field.' . $this->precedence->key($column),
				$properties
			);
			$this->resolved->set(
				$path . '.field.' . $this->precedence->key($column) . '.tab_index',
				$this->tab->index($this->tab->nameFor($view, $properties), $tabs)
			);
		}

		$this->resolved->set($path . '.name_single', $view);
		$this->resolved->set($path . '.name_list', $this->viewname->plural($view));
		$this->resolved->set($path . '.system_name', $this->viewname->title($view));
		$this->resolved->set($path . '.table', $name);
		$this->resolved->set($path . '.key', $key);
		$this->resolved->set($path . '.tabs', $tabs);
		$this->resolved->set($path . '.roles', $roles);
		$this->resolved->set($path . '.relations', $relations);
		$this->resolved->set(
			$path . '.conditions',
			array_values($this->condition->build($view, $fields))
		);
		$this->resolved->set(
			$path . '.guid',
			$this->guid->derive([$this->option(), 'admin_view', $name])
		);

		$seed = $this->schema->get('seed.' . $key . '.sql');

		if (is_string($seed) && $seed !== '')
		{
			$this->resolved->set($path . '.seed', $seed);
		}

		return $view;
	}

	/**
	 * Every column of one table, in declaration order where it is known.
	 *
	 * @param   string  $key  The registry key of the table.
	 *
	 * @return  array<string>  The true column names.
	 * @since   6.1.6
	 */
	public function columns(string $key): array
	{
		$columns = [];
		$block = $this->schema->get('table.' . $key . '.column');

		foreach ((array) $block as $columnKey => $entry)
		{
			$entry = (array) $entry;
			$columns[(string) ($entry['name'] ?? $columnKey)] = (int) ($entry['ordinal'] ?? 0);
		}

		$fields = $this->table->get('table.' . $key . '.field');
		$next = count($columns);

		foreach ((array) $fields as $fieldKey => $entry)
		{
			$entry = (array) $entry;
			$name = (string) ($entry['name'] ?? $fieldKey);

			if ($name !== '' && !isset($columns[$name]))
			{
				$columns[$name] = $next++;
			}
		}

		asort($columns, SORT_NUMERIC);

		return array_keys($columns);
	}

	/**
	 * Mark which relationships point outside the assembled set.
	 *
	 * @param   array<string>  $views  Every assembled view name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function reconcile(array $views): void
	{
		foreach ($views as $view)
		{
			$path = 'view.' . $this->precedence->key($view) . '.relations';
			$relations = $this->resolved->get($path);

			if (!is_array($relations) || $relations === [])
			{
				continue;
			}

			$this->resolved->set($path, $this->relation->reconcile($relations, $views));
		}
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
