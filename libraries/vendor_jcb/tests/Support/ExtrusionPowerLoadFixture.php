<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    22nd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use VDM\Joomla\Interfaces\Database\LoadInterface;


/**
 * The database boundary of the powers extrusion pipeline, served without a database.
 *
 * The pipeline reads four things and nothing else: the power catalogue, one
 * component row, that component's placeholder overrides, and the global
 * extension parameters. Each is served from a declared value here, so a test
 * states exactly what the database would have said and the whole pipeline runs
 * for real above it.
 *
 * @since  6.1.7
 */
final class ExtrusionPowerLoadFixture implements LoadInterface
{
	/**
	 * The power catalogue rows to serve.
	 *
	 * @var    array<int, object>
	 * @since  6.1.7
	 */
	private array $powers = [];

	/**
	 * The component rows to serve, keyed by id.
	 *
	 * @var    array<int, object>
	 * @since  6.1.7
	 */
	private array $components = [];

	/**
	 * The placeholder override JSON to serve, keyed by component guid.
	 *
	 * @var    array<string, string>
	 * @since  6.1.7
	 */
	private array $overrides = [];

	/**
	 * The global extension parameter JSON to serve.
	 *
	 * @var    string|null
	 * @since  6.1.7
	 */
	private ?string $params = null;

	/**
	 * Declare one power row the catalogue holds.
	 *
	 * @param   int     $id         The row id.
	 * @param   string  $guid       The power identity.
	 * @param   string  $name       The class name.
	 * @param   string  $namespace  The stored namespace, placeholders included.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.7
	 */
	public function power(int $id, string $guid, string $name, string $namespace): self
	{
		$this->powers[] = (object) [
			'id' => $id,
			'guid' => $guid,
			'name' => $name,
			'namespace' => $namespace
		];

		return $this;
	}

	/**
	 * Declare one component row.
	 *
	 * @param   int     $id      The component id.
	 * @param   string  $guid    The component identity.
	 * @param   string  $code    The component code name.
	 * @param   int     $add     Whether the component adds its own prefix.
	 * @param   string  $prefix  The component's own prefix.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.7
	 */
	public function component(int $id, string $guid, string $code, int $add = 0, string $prefix = ''): self
	{
		$this->components[$id] = (object) [
			'guid' => $guid,
			'name_code' => $code,
			'add_namespace_prefix' => $add,
			'namespace_prefix' => $prefix
		];

		return $this;
	}

	/**
	 * Declare one component's placeholder overrides.
	 *
	 * @param   string             $guid  The component identity.
	 * @param   array<int, array>  $rows  The override rows, each with target and value.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.7
	 */
	public function overrides(string $guid, array $rows): self
	{
		$this->overrides[$guid] = (string) json_encode($rows);

		return $this;
	}

	/**
	 * Declare the global extension parameters.
	 *
	 * @param   array<string, mixed>  $params  The parameter values.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.7
	 */
	public function params(array $params): self
	{
		$this->params = (string) json_encode($params);

		return $this;
	}

	/**
	 * Load rows as arrays.
	 *
	 * @param   array       $select  The select mapping.
	 * @param   array       $tables  The table definitions.
	 * @param   array|null  $where   The where conditions.
	 * @param   array|null  $order   The ordering.
	 * @param   int|null    $limit   The row limit.
	 *
	 * @return  array|null  Always null; the pipeline never asks.
	 * @since   6.1.7
	 */
	public function rows(array $select, array $tables, ?array $where = null,
		?array $order = null, ?int $limit = null): ?array
	{
		return null;
	}

	/**
	 * Load rows as objects.
	 *
	 * @param   array       $select  The select mapping.
	 * @param   array       $tables  The table definitions.
	 * @param   array|null  $where   The where conditions.
	 * @param   array|null  $order   The ordering.
	 * @param   int|null    $limit   The row limit.
	 *
	 * @return  array|null  The power catalogue, or null.
	 * @since   6.1.7
	 */
	public function items(array $select, array $tables, ?array $where = null,
		?array $order = null, ?int $limit = null): ?array
	{
		if (($tables['a'] ?? '') === 'power')
		{
			return $this->powers === [] ? null : $this->powers;
		}

		return null;
	}

	/**
	 * Load one row as an array.
	 *
	 * @param   array       $select  The select mapping.
	 * @param   array       $tables  The table definitions.
	 * @param   array|null  $where   The where conditions.
	 * @param   array|null  $order   The ordering.
	 *
	 * @return  array|null  Always null; the pipeline never asks.
	 * @since   6.1.7
	 */
	public function row(array $select, array $tables, ?array $where = null, ?array $order = null): ?array
	{
		return null;
	}

	/**
	 * Load one row as an object.
	 *
	 * @param   array       $select  The select mapping.
	 * @param   array       $tables  The table definitions.
	 * @param   array|null  $where   The where conditions.
	 * @param   array|null  $order   The ordering.
	 *
	 * @return  object|null  The declared component row, or null.
	 * @since   6.1.7
	 */
	public function item(array $select, array $tables, ?array $where = null, ?array $order = null): ?object
	{
		if (($tables['a'] ?? '') === 'joomla_component')
		{
			return $this->components[(int) ($where['a.id'] ?? 0)] ?? null;
		}

		return null;
	}

	/**
	 * Load the highest value of a field.
	 *
	 * @param   string|array  $field   The field to aggregate.
	 * @param   array         $tables  The table definitions.
	 * @param   array         $filter  The filter conditions.
	 *
	 * @return  int|null  Always null; the pipeline never asks.
	 * @since   6.1.7
	 */
	public function max($field, array $tables, array $filter): ?int
	{
		return null;
	}

	/**
	 * Count the rows a filter matches.
	 *
	 * @param   array  $tables  The table definitions.
	 * @param   array  $filter  The filter conditions.
	 *
	 * @return  int|null  Always null; the pipeline never asks.
	 * @since   6.1.7
	 */
	public function count(array $tables, array $filter): ?int
	{
		return null;
	}

	/**
	 * Load one value.
	 *
	 * @param   array       $select  The select mapping.
	 * @param   array       $tables  The table definitions.
	 * @param   array|null  $where   The where conditions.
	 * @param   array|null  $order   The ordering.
	 *
	 * @return  mixed  The declared override or parameter JSON, or null.
	 * @since   6.1.7
	 */
	public function value(array $select, array $tables, ?array $where = null, ?array $order = null)
	{
		$table = $tables['a'] ?? '';

		if ($table === 'component_placeholders')
		{
			return $this->overrides[(string) ($where['a.joomla_component'] ?? '')] ?? null;
		}

		if ($table === '#__extensions')
		{
			return $this->params;
		}

		return null;
	}

	/**
	 * Load a list of values.
	 *
	 * @param   array       $select  The select mapping.
	 * @param   array       $tables  The table definitions.
	 * @param   array|null  $where   The where conditions.
	 * @param   array|null  $order   The ordering.
	 * @param   int|null    $limit   The row limit.
	 *
	 * @return  array|null  Always null; the pipeline never asks.
	 * @since   6.1.7
	 */
	public function values(array $select, array $tables, ?array $where = null,
		?array $order = null, ?int $limit = null): ?array
	{
		return null;
	}
}
