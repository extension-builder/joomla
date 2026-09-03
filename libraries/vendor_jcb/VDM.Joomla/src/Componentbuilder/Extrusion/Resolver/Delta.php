<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    3rd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Resolver;


use VDM\Joomla\Componentbuilder\Extrusion\Registry\Proposal;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\TableInterface;


/**
 * Weighs the record a writer composed against the record that stands.
 *
 * Only the columns a write would carry are weighed. A column the record holds
 * and the write never names is untouched by that write, so it is not a
 * difference -- the question this answers is "what would this write change",
 * never "how do these two records differ".
 *
 * Two forms of every value are kept, and they are not the same form. Whether a
 * value changed is decided on what would land in the column, encoded exactly as
 * the storage pipeline encodes it, because that is what a write compares
 * against. What a person is shown is the value read back out -- the text of a
 * power, the pretty print of a subform -- because that is what a person reads.
 *
 * @since 6.2.0
 */
final class Delta
{
	/**
	 * The Data Item Class.
	 *
	 * @var    ItemInterface
	 * @since  6.2.0
	 */
	protected ItemInterface $item;

	/**
	 * The JCB Table Definitions.
	 *
	 * @var    TableInterface
	 * @since  6.2.0
	 */
	protected TableInterface $tables;

	/**
	 * The Diff Resolver.
	 *
	 * @var    Diff
	 * @since  6.2.0
	 */
	protected Diff $diff;

	/**
	 * The Proposal Registry.
	 *
	 * @var    Proposal
	 * @since  6.2.0
	 */
	protected Proposal $proposal;

	/**
	 * Constructor.
	 *
	 * @param   ItemInterface   $item      The JCB data item reader.
	 * @param   TableInterface  $tables    The JCB table definitions.
	 * @param   Diff            $diff      The line comparison.
	 * @param   Proposal        $proposal  The proposal registry.
	 *
	 * @since   6.2.0
	 */
	public function __construct(
		ItemInterface $item,
		TableInterface $tables,
		Diff $diff,
		Proposal $proposal
	)
	{
		$this->item = $item;
		$this->tables = $tables;
		$this->diff = $diff;
		$this->proposal = $proposal;
	}

	/**
	 * Weigh one composed definition against what stands, and propose it.
	 *
	 * @param   string       $table       The table the record belongs to.
	 * @param   string       $key         The column the table is keyed by.
	 * @param   string       $identity    The record's identity.
	 * @param   object       $definition  The definition a write would carry.
	 * @param   bool         $exists      Whether a record already stands under that identity.
	 * @param   string|null  $origin      The pairing board row this record belongs to.
	 *
	 * @return  array<string, mixed>  What the write would change.
	 * @since   6.2.0
	 */
	public function weigh(
		string $table,
		string $key,
		string $identity,
		object $definition,
		bool $exists,
		?string $origin = null
	): array
	{
		$standing = $exists ? $this->item->table($table)->get($identity, $key) : null;
		$columns = [];

		foreach (get_object_vars($definition) as $column => $after)
		{
			// the identity is how the record is found, never something the
			// write changes about it
			if ($column === $key)
			{
				continue;
			}

			$before = is_object($standing) ? ($standing->{$column} ?? null) : null;

			if ($standing !== null && $this->same($table, $column, $before, $after))
			{
				continue;
			}

			$columns[$column] = $this->change($before, $after);
		}

		$delta = [
			'table' => $table,
			'identity' => $identity,
			'origin' => $origin,
			'action' => $standing === null ? 'create' : 'update',
			'changed' => $columns !== [],
			'additions' => 0,
			'deletions' => 0,
			'columns' => $columns
		];

		foreach ($columns as $change)
		{
			$delta['additions'] += $change['additions'];
			$delta['deletions'] += $change['deletions'];
		}

		$this->proposal->propose($table, $identity, $delta);

		return $delta;
	}

	/**
	 * Whether two values would land in the column as the same thing.
	 *
	 * @param   string  $table   The table the column belongs to.
	 * @param   string  $column  The column.
	 * @param   mixed   $before  The value as it stands.
	 * @param   mixed   $after   The value the write would carry.
	 *
	 * @return  bool  True when the write would change nothing.
	 * @since   6.2.0
	 */
	protected function same(string $table, string $column, $before, $after): bool
	{
		return $this->stored($table, $column, $before) === $this->stored($table, $column, $after);
	}

	/**
	 * One value as the column would hold it.
	 *
	 * @param   string  $table   The table the column belongs to.
	 * @param   string  $column  The column.
	 * @param   mixed   $value   The value.
	 *
	 * @return  string  The value as it would be stored.
	 * @since   6.2.0
	 */
	protected function stored(string $table, string $column, $value): string
	{
		if ($value === null)
		{
			return '';
		}

		// the same encoding the storage pipeline applies, so a value read back
		// out of the column and a value on its way in are compared as equals
		switch ((string) $this->tables->get($table, $column, 'store'))
		{
			case 'base64':
				return base64_encode((string) $value);

			case 'json':
				return (string) json_encode($value, JSON_FORCE_OBJECT);
		}

		if (is_array($value) || is_object($value))
		{
			return (string) json_encode($value);
		}

		return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
	}

	/**
	 * One column's change, in the form a person reads and weighed by line.
	 *
	 * @param   mixed  $before  The value as it stands.
	 * @param   mixed  $after   The value the write would carry.
	 *
	 * @return  array<string, mixed>  The change.
	 * @since   6.2.0
	 */
	protected function change($before, $after): array
	{
		$old = $this->readable($before);
		$new = $this->readable($after);
		$counts = $this->diff->counts($old, $new);

		return [
			'before' => $old,
			'after' => $new,
			'shape' => str_contains($old, "\n") || str_contains($new, "\n") ? 'text' : 'value',
			'additions' => $counts['additions'],
			'deletions' => $counts['deletions']
		];
	}

	/**
	 * One value as a person reads it.
	 *
	 * @param   mixed  $value  The value.
	 *
	 * @return  string  The readable value.
	 * @since   6.2.0
	 */
	protected function readable($value): string
	{
		if ($value === null)
		{
			return '';
		}

		if (is_bool($value))
		{
			return $value ? '1' : '0';
		}

		if (is_array($value) || is_object($value))
		{
			// a subform read as one long line is a subform nobody can read: the
			// entries are laid out so a change lands on the line it belongs to
			return (string) json_encode(
				$value,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);
		}

		return (string) $value;
	}
}
