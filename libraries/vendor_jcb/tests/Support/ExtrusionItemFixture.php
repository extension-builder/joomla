<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use VDM\Joomla\Componentbuilder\Table;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * The JCB data pipeline boundary, recorded instead of executed.
 *
 * Extrusion writes every definition through this one service, so a fake that
 * records each set() together with the table that was active is enough to hold a
 * writer to its contract: what table it wrote into, under what identity, and
 * with exactly what values. Nothing is encoded here, because the real pipeline
 * applies the storage a JCB table declares -- which is precisely the obligation
 * these recordings are used to prove.
 *
 * A recorded definition is cloned on the way in, so a later mutation of the same
 * object cannot rewrite history.
 *
 * The JCB Table class is the source of truth for what every table holds, and
 * this boundary enforces it: an unknown table, a key column the table does not
 * define, or a written property the Table class does not know all throw --
 * loudly, where the real pipeline would silently drop the value or let the
 * database refuse the query on a live site.
 *
 * @since  6.1.6
 */
final class ExtrusionItemFixture implements ItemInterface
{
	/**
	 * The table the caller made active.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private string $active = '';

	/**
	 * Every recorded write, in the order it happened.
	 *
	 * @var    array<int, array{table: string, key: string, action: string|null, item: object}>
	 * @since  6.1.6
	 */
	private array $records = [];

	/**
	 * The identities that already exist, as table and guid to row id.
	 *
	 * @var    array<string, int>
	 * @since  6.1.6
	 */
	private array $identities = [];

	/**
	 * The identities whose write must be refused, as table and guid.
	 *
	 * @var    array<string, bool>
	 * @since  6.1.6
	 */
	private array $refused = [];

	/**
	 * Every identity lookup, in the order it happened.
	 *
	 * @var    array<int, string>
	 * @since  6.1.6
	 */
	private array $lookups = [];

	/**
	 * The real JCB table definitions, the source of truth for every column.
	 *
	 * @var    Table|null
	 * @since  6.1.7
	 */
	private static ?Table $tables = null;

	/**
	 * Declare that one identity already exists in a table.
	 *
	 * @param   string  $table  The table name without its prefix.
	 * @param   string  $guid   The existing identity.
	 * @param   int     $id     The row id to report.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function identity(string $table, string $guid, int $id): self
	{
		$this->identities[$table . ':' . $guid] = $id;

		return $this;
	}

	/**
	 * Declare that one identity's write must fail.
	 *
	 * @param   string  $table  The table name without its prefix.
	 * @param   string  $guid   The identity to refuse.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function refuse(string $table, string $guid): self
	{
		$this->refused[$table . ':' . $guid] = true;

		return $this;
	}

	/**
	 * Every recorded write, optionally limited to one table.
	 *
	 * @param   string|null  $table  The table to filter by, or null for all.
	 *
	 * @return  array<int, array{table: string, key: string, action: string|null, item: object}>  The records.
	 * @since   6.1.6
	 */
	public function records(?string $table = null): array
	{
		if ($table === null)
		{
			return $this->records;
		}

		return array_values(array_filter(
			$this->records,
			static fn (array $record): bool => $record['table'] === $table
		));
	}

	/**
	 * Every definition written into one table, in order.
	 *
	 * @param   string  $table  The table name without its prefix.
	 *
	 * @return  array<int, object>  The written definitions.
	 * @since   6.1.6
	 */
	public function definitions(string $table): array
	{
		return array_column($this->records($table), 'item');
	}

	/**
	 * The definition written into one table under one identity.
	 *
	 * @param   string  $table  The table name without its prefix.
	 * @param   string  $guid   The identity written under.
	 *
	 * @return  object|null  The written definition, or null when it was never written.
	 * @since   6.1.6
	 */
	public function definition(string $table, string $guid): ?object
	{
		foreach ($this->records($table) as $record)
		{
			if ((string) ($record['item']->guid ?? '') === $guid)
			{
				return $record['item'];
			}
		}

		return null;
	}

	/**
	 * The table of every recorded write, in the order the writes happened.
	 *
	 * @return  array<int, string>  The table names, with repeats.
	 * @since   6.1.6
	 */
	public function sequence(): array
	{
		return array_column($this->records, 'table');
	}

	/**
	 * Every identity lookup, as table, key, value and wanted column.
	 *
	 * @return  array<int, string>  The lookups.
	 * @since   6.1.6
	 */
	public function lookups(): array
	{
		return $this->lookups;
	}

	/**
	 * Get the first ID of the most recent action.
	 *
	 * @return  int  The entity ID, or 0 if unavailable.
	 * @since   6.1.6
	 */
	public function id(): int
	{
		return 0;
	}

	/**
	 * Set the current active table.
	 *
	 * @param   string  $table  The table that should be active.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.6
	 */
	public function table(string $table): self
	{
		if (self::tables()->get($table) === null)
		{
			throw new \RuntimeException(
				"The JCB Table class defines no table '" . $table
				. "'. A live site holds no such table."
			);
		}

		$this->active = $table;

		return $this;
	}

	/**
	 * Get an item.
	 *
	 * @param   string  $value  The item key value.
	 * @param   string  $key    The item key.
	 *
	 * @return  object|null  The item object or null.
	 * @since   6.1.6
	 */
	public function get(string $value, string $key = 'guid'): ?object
	{
		return null;
	}

	/**
	 * Get one value of an item.
	 *
	 * @param   string  $value  The item key value.
	 * @param   string  $key    The item key.
	 * @param   string  $get    The key of the value wanted back.
	 *
	 * @return  mixed  The declared row id, or null when the identity is new.
	 * @since   6.1.6
	 */
	public function value(string $value, string $key = 'guid', string $get = 'id')
	{
		$this->column($key);
		$this->column($get);

		$this->lookups[] = $this->active . ':' . $key . ':' . $value . ':' . $get;

		return $this->identities[$this->active . ':' . $value] ?? null;
	}

	/**
	 * Set an item.
	 *
	 * @param   object       $item    The item.
	 * @param   string       $key     The item key.
	 * @param   string|null  $action  The action to load power.
	 *
	 * @return  bool  True unless the identity was declared refused.
	 * @since   6.1.6
	 */
	public function set(object $item, string $key = 'guid', ?string $action = null): bool
	{
		$this->column($key);

		foreach (get_object_vars($item) as $property => $value)
		{
			$this->column($property, 'writes');
			$this->capacity($property, $value);
		}

		$identity = (string) ($item->{$key} ?? '');

		if (isset($this->refused[$this->active . ':' . $identity]))
		{
			return false;
		}

		$this->records[] = [
			'table' => $this->active,
			'key' => $key,
			'action' => $action,
			'item' => clone $item
		];

		return true;
	}

	/**
	 * Delete an item.
	 *
	 * @param   string  $value  The item key value.
	 * @param   string  $key    The item key.
	 *
	 * @return  bool  Always true; extrusion never deletes.
	 * @since   6.1.6
	 */
	public function delete(string $value, string $key = 'guid'): bool
	{
		return true;
	}

	/**
	 * Get the current active table.
	 *
	 * @return  string  The active table name.
	 * @since   6.1.6
	 */
	public function getTable(): string
	{
		return $this->active;
	}

	/**
	 * Refuse any column the active table does not define.
	 *
	 * @param   string  $column  The column to validate.
	 * @param   string  $what    What the caller is doing, for the message.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function column(string $column, string $what = 'keys by'): void
	{
		$fields = (array) (self::tables()->fields($this->active, true) ?? []);

		if (!in_array($column, $fields, true))
		{
			throw new \RuntimeException(
				'The writer ' . $what . " column '" . $column . "' on '"
				. $this->active . "', but the JCB Table class defines no such "
				. 'column there. On a live site the value would be silently '
				. 'dropped or the query refused.'
			);
		}
	}

	/**
	 * Refuse any value longer than the Table class says its column holds.
	 *
	 * A strict live database refuses an over-long value outright, so this
	 * boundary does the same -- the CHAR and VARCHAR capacities come from
	 * the Table class's own db types.
	 *
	 * @param   string  $column  The column the value is written into.
	 * @param   mixed   $value   The written value.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function capacity(string $column, $value): void
	{
		if (!is_string($value))
		{
			return;
		}

		$type = (string) (self::tables()->get($this->active, $column, 'db')['type'] ?? '');

		if (preg_match('/^(?:VAR)?CHAR\((\d+)\)/i', $type, $size)
			&& strlen($value) > (int) $size[1])
		{
			throw new \RuntimeException(
				'The writer stores ' . strlen($value) . " characters into '"
				. $column . "' on '" . $this->active . "', but the JCB Table "
				. 'class declares it ' . $type . '. A strict live database '
				. 'refuses this outright.'
			);
		}
	}

	/**
	 * The real JCB table definitions, loaded once per process.
	 *
	 * @return  Table  The Table class, the source of truth.
	 * @since   6.1.7
	 */
	private static function tables(): Table
	{
		return self::$tables ??= new Table();
	}
}
