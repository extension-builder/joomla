<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    23rd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use VDM\Joomla\Interfaces\Database\LoadInterface;


/**
 * A declarative database boundary: tables served from arrays.
 *
 * A test declares rows per table and the fixture answers the loader contract
 * over them -- equality and IN conditions on the columns the rows carry. The
 * select mapping's aliases are honoured, so a consumer reading
 * ['a.system_name' => 'name'] sees 'name' keys exactly as the real loader
 * would give it.
 *
 * @since  6.1.7
 */
final class ExtrusionDatabaseFixture implements LoadInterface
{
	/**
	 * The served tables, each a list of associative rows.
	 *
	 * @var    array<string, array<int, array<string, mixed>>>
	 * @since  6.1.7
	 */
	private array $tables = [];

	/**
	 * The real component schema, table to column list, parsed once.
	 *
	 * The install SQL is the ground truth of what a live site holds. Every
	 * declared row and every queried column is validated against it, so a
	 * test can never pass on a column a real database would refuse -- the
	 * exact failure a fabricated fixture schema once let through.
	 *
	 * @var    array<string, array<int, string>>|null
	 * @since  6.1.7
	 */
	private static ?array $schema = null;

	/**
	 * Declare the rows one table serves.
	 *
	 * @param   string                                $table  The table without its prefix.
	 * @param   array<int, array<string, mixed>>      $rows   The rows to serve.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.7
	 */
	public function table(string $table, array $rows): self
	{
		$rows = array_map(
			static fn ($row): array => (array) $row,
			array_values($rows)
		);

		foreach ($rows as $row)
		{
			$this->assertColumns($table, array_keys($row), 'declares');
		}

		$this->tables[$table] = $rows;

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
	 * @return  array|null  The matching rows, or null when none match.
	 * @since   6.1.7
	 */
	public function rows(array $select, array $tables, ?array $where = null,
		?array $order = null, ?int $limit = null): ?array
	{
		return $this->matches($select, $tables, $where);
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
	 * @return  array|null  The matching rows as objects, or null when none match.
	 * @since   6.1.7
	 */
	public function items(array $select, array $tables, ?array $where = null,
		?array $order = null, ?int $limit = null): ?array
	{
		$rows = $this->matches($select, $tables, $where);

		if ($rows === null)
		{
			return null;
		}

		return array_map(
			static fn (array $row): object => (object) $row,
			$rows
		);
	}

	/**
	 * Load one row as an array.
	 *
	 * @param   array       $select  The select mapping.
	 * @param   array       $tables  The table definitions.
	 * @param   array|null  $where   The where conditions.
	 * @param   array|null  $order   The ordering.
	 *
	 * @return  array|null  The first matching row, or null.
	 * @since   6.1.7
	 */
	public function row(array $select, array $tables, ?array $where = null, ?array $order = null): ?array
	{
		$rows = $this->matches($select, $tables, $where);

		return $rows[0] ?? null;
	}

	/**
	 * Load one row as an object.
	 *
	 * @param   array       $select  The select mapping.
	 * @param   array       $tables  The table definitions.
	 * @param   array|null  $where   The where conditions.
	 * @param   array|null  $order   The ordering.
	 *
	 * @return  object|null  The first matching row, or null.
	 * @since   6.1.7
	 */
	public function item(array $select, array $tables, ?array $where = null, ?array $order = null): ?object
	{
		$row = $this->row($select, $tables, $where, $order);

		return $row === null ? null : (object) $row;
	}

	/**
	 * Load the highest value of a field.
	 *
	 * @param   string|array  $field   The field to aggregate.
	 * @param   array         $tables  The table definitions.
	 * @param   array         $filter  The filter conditions.
	 *
	 * @return  int|null  Always null; the consumers under test never ask.
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
	 * @return  int|null  Always null; the consumers under test never ask.
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
	 * @return  mixed  The first selected value of the first match, or null.
	 * @since   6.1.7
	 */
	public function value(array $select, array $tables, ?array $where = null, ?array $order = null)
	{
		$row = $this->row($select, $tables, $where, $order);

		if ($row === null)
		{
			return null;
		}

		$first = array_key_first($row);

		return $first === null ? null : $row[$first];
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
	 * @return  array|null  The first selected value of every match, or null.
	 * @since   6.1.7
	 */
	public function values(array $select, array $tables, ?array $where = null,
		?array $order = null, ?int $limit = null): ?array
	{
		$rows = $this->matches($select, $tables, $where);

		if ($rows === null)
		{
			return null;
		}

		return array_map(
			static function (array $row)
			{
				$first = array_key_first($row);

				return $first === null ? null : $row[$first];
			},
			$rows
		);
	}

	/**
	 * The served rows one query matches, projected through its select mapping.
	 *
	 * @param   array       $select  The select mapping, column to alias.
	 * @param   array       $tables  The table definitions.
	 * @param   array|null  $where   The where conditions.
	 *
	 * @return  array<int, array<string, mixed>>|null  The projections, or null.
	 * @since   6.1.7
	 */
	private function matches(array $select, array $tables, ?array $where): ?array
	{
		$table = (string) ($tables['a'] ?? '');

		$this->assertColumns(
			$table,
			array_map(
				static fn ($column): string => str_replace('a.', '', (string) $column),
				array_merge(array_keys($select), array_keys((array) $where))
			),
			'queries'
		);

		$rows = $this->tables[$table] ?? null;

		if ($rows === null)
		{
			return null;
		}

		$found = [];

		foreach ($rows as $row)
		{
			if (!$this->accepts($row, $where))
			{
				continue;
			}

			$projection = [];

			foreach ($select as $column => $alias)
			{
				$name = str_replace('a.', '', (string) $column);
				$projection[(string) $alias] = $row[$name] ?? null;
			}

			$found[] = $projection;
		}

		return $found === [] ? null : $found;
	}

	/**
	 * Whether one row satisfies the where conditions.
	 *
	 * @param   array<string, mixed>  $row    The served row.
	 * @param   array|null            $where  The where conditions.
	 *
	 * @return  bool  True when the row matches.
	 * @since   6.1.7
	 */
	private function accepts(array $row, ?array $where): bool
	{
		foreach ((array) $where as $column => $condition)
		{
			$name = str_replace('a.', '', (string) $column);
			$value = $row[$name] ?? null;

			if (is_array($condition)
				&& isset($condition['operator'], $condition['value']))
			{
				$pool = array_map('strval', (array) $condition['value']);

				if (strtoupper((string) $condition['operator']) !== 'IN'
					|| !in_array((string) $value, $pool, true))
				{
					return false;
				}

				continue;
			}

			if ((string) $value !== (string) $condition)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Refuse any column the real component schema does not hold.
	 *
	 * Tables outside the component schema (Joomla core tables such as
	 * extensions) are not validated -- the install SQL says nothing about
	 * them.
	 *
	 * @param   string              $table    The table without its prefix.
	 * @param   array<int, string>  $columns  The columns to validate.
	 * @param   string              $what     What the caller is doing, for the message.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function assertColumns(string $table, array $columns, string $what): void
	{
		$schema = self::schema();
		$known = $schema[str_replace('#__componentbuilder_', '', $table)] ?? null;

		if ($known === null)
		{
			return;
		}

		foreach ($columns as $column)
		{
			if (!in_array((string) $column, $known, true))
			{
				throw new \RuntimeException(
					'The test ' . $what . " column '" . $column . "' on '"
					. $table . "', but admin/sql/install.mysql.utf8.sql defines "
					. 'no such column. A live database would refuse this.'
				);
			}
		}
	}

	/**
	 * The component schema, parsed from the install SQL, once per process.
	 *
	 * @return  array<string, array<int, string>>  Table (without prefix) to columns.
	 * @since   6.1.7
	 */
	private static function schema(): array
	{
		if (self::$schema !== null)
		{
			return self::$schema;
		}

		$sql = (string) file_get_contents(
			\dirname(__DIR__, 4) . '/admin/sql/install.mysql.utf8.sql'
		);
		self::$schema = [];

		preg_match_all(
			'/CREATE TABLE IF NOT EXISTS `#__componentbuilder_(\w+)` \((.*?)\)\s*ENGINE/s',
			$sql,
			$creates,
			PREG_SET_ORDER
		);

		foreach ($creates as $create)
		{
			preg_match_all('/^\t`(\w+)`/m', $create[2], $columns);
			self::$schema[$create[1]] = $columns[1];
		}

		return self::$schema;
	}
}
