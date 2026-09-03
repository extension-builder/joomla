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
 * value changed is decided on what would land in the column, encoded as the
 * storage pipeline encodes it, because that is what a write compares against.
 * What a person is shown is the value read back out -- the text of a power,
 * the pretty print of a subform -- because that is what a person reads.
 *
 * Both forms read a value the way a person does, never the way bytes do. A
 * line ends the same whichever way it was broken; a subform says the same
 * thing whichever order its keys were saved in, and whether a number was
 * posted as text through a form or composed as a number by a writer. A write
 * that differs only in those ways would change nothing anybody could see, so
 * it is not a change, and it is not made.
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
	 * A record nothing stands under comes into being, which is a change even
	 * when everything it carries is empty; what it shows is only what it fills
	 * in, because a column it leaves empty adds nothing a person could read. A
	 * record that stands is weighed column by column, and only what would move
	 * is kept.
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

			// a record coming into being adds only what it fills in
			if ($standing === null && $this->nothing($after))
			{
				continue;
			}

			$columns[$column] = $this->change($before, $after);
		}

		$delta = [
			'table' => $table,
			'identity' => $identity,
			'origin' => $origin,
			// the writer has already asked whether the record stands, and that
			// answer is the action, whatever the read of the record brought back
			'action' => $exists ? 'update' : 'create',
			'changed' => !$exists || $columns !== [],
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
		// a column that was never set, one set to nothing, and one holding an
		// empty list are the same nothing. They read back differently -- null,
		// an empty string, an empty list, an empty object -- and a write that
		// puts one where another stands changes nothing a person would see
		if ($this->nothing($value))
		{
			return '';
		}

		// the same encoding the storage pipeline applies, so a value read back
		// out of the column and a value on its way in are compared as equals
		switch ((string) $this->tables->get($table, $column, 'store'))
		{
			case 'base64':
				return base64_encode($this->text($value));

			case 'json':
				return (string) json_encode($this->canonical($value), JSON_FORCE_OBJECT);
		}

		if (is_array($value) || is_object($value))
		{
			return (string) json_encode($this->canonical($value));
		}

		return $this->text($value);
	}

	/**
	 * Whether a value says nothing at all.
	 *
	 * @param   mixed  $value  The value.
	 *
	 * @return  bool  True when the value holds nothing.
	 * @since   6.2.0
	 */
	protected function nothing($value): bool
	{
		if ($value === null || $value === '' || $value === [])
		{
			return true;
		}

		if (is_object($value))
		{
			return get_object_vars($value) === [];
		}

		// the stored forms of an empty list, read back out of the column as
		// the text they were written as
		return is_string($value) && in_array(trim($value), ['[]', '{}'], true);
	}

	/**
	 * One scalar as a person reads it.
	 *
	 * A switch is on or off whichever way it was written, and a line ends the
	 * same whichever way it was broken: the readers already fold every line
	 * ending of a source file to one, and what a form saved may carry the
	 * other, so neither is a difference.
	 *
	 * @param   mixed  $value  The scalar.
	 *
	 * @return  string  The text it reads as.
	 * @since   6.2.0
	 */
	protected function text($value): string
	{
		if (is_bool($value))
		{
			return $value ? '1' : '0';
		}

		return str_replace(["\r\n", "\r"], "\n", (string) $value);
	}

	/**
	 * One structure as a person reads it, whichever way it was saved.
	 *
	 * A subform saved through a form arrives with its keys in the form's order
	 * and every value as text; the same subform composed by a writer carries
	 * the writer's order and numbers where the form had text. A person reads
	 * both as the same subform, so both are laid out the same way: named keys
	 * in one order, and every scalar as the text it reads as. A list keeps its
	 * order, because there the position is the meaning.
	 *
	 * @param   mixed  $value  The structure, or a scalar inside one.
	 *
	 * @return  mixed  The structure laid out one way.
	 * @since   6.2.0
	 */
	protected function canonical($value)
	{
		if (is_object($value))
		{
			$value = get_object_vars($value);
		}

		if (!is_array($value))
		{
			return $value === null ? '' : $this->text($value);
		}

		$canonical = [];

		foreach ($value as $key => $entry)
		{
			$canonical[$key] = $this->canonical($entry);
		}

		if (!array_is_list($canonical))
		{
			ksort($canonical, SORT_STRING);
		}

		return $canonical;
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
	 * It is laid out exactly as it is weighed, so the lines a person is shown
	 * are the lines that moved and nothing else.
	 *
	 * @param   mixed  $value  The value.
	 *
	 * @return  string  The readable value.
	 * @since   6.2.0
	 */
	protected function readable($value): string
	{
		if ($this->nothing($value))
		{
			return '';
		}

		if (is_array($value) || is_object($value))
		{
			// a subform read as one long line is a subform nobody can read: the
			// entries are laid out so a change lands on the line it belongs to
			return (string) json_encode(
				$this->canonical($value),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);
		}

		return $this->text($value);
	}
}
