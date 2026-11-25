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


/**
 * Reads two texts line by line and says what changed between them.
 * 
 * The line is the unit a person reads a change in, so it is the unit here: how
 * many lines a change adds, how many it takes away, and where. What comes back
 * is the shape of a unified diff -- hunks of changed lines with a little of the
 * unchanged text around them -- because that is the shape every developer
 * already knows how to read.
 * 
 * The review counts additions and deletions. Opening a change builds its hunks
 * only when the changed middle fits the preview limits.
 * 
 * @since 6.2.0
 */
final class Diff
{
	/**
	 * How many unchanged lines are shown around a change.
	 *
	 * @var    int
	 * @since  6.2.0
	 */
	private const CONTEXT = 3;

	/**
	 * The largest middle this will line up exactly, in lines.
	 *
	 * @var    int
	 * @since  6.2.0
	 */
	private const LIMIT = 1500;

	/**
	 * Return the requested hunks, or null if the preview is too large to display.
	 *
	 * @param   string  $before  The existing text.
	 * @param   string  $after   The proposed text.
	 *
	 * @return  array{additions: int, deletions: int, hunks: array}|null
	 * @since   6.2.0
	 */
	public function compare(string $before, string $after): ?array
	{
		return $this->changes($before, $after, true);
	}

	/**
	 * Count changes without building display hunks or retaining an alignment table.
	 *
	 * @param   string  $before  The existing text.
	 * @param   string  $after   The proposed text.
	 *
	 * @return  array{additions: int, deletions: int}
	 * @since   6.2.0
	 */
	public function counts(string $before, string $after): array
	{
		return $this->changes($before, $after, false);
	}

	/**
	 * Trim shared lines and align only the changed middle. A middle above the
	 * alignment limit is counted as a replacement, as in earlier reviews.
	 *
	 * @param   string  $before   The existing text.
	 * @param   string  $after    The proposed text.
	 * @param   bool    $details  Whether to retain the edit path for display.
	 *
	 * @return  array|null  The counts and any requested hunks.
	 * @since   6.3.0
	 */
	private function changes(string $before, string $after, bool $details): ?array
	{
		$before = str_replace("\r\n", "\n", $before);
		$after = str_replace("\r\n", "\n", $after);

		if ($before === $after)
		{
			return ['additions' => 0, 'deletions' => 0] + ($details ? ['hunks' => []] : []);
		}

		$oldCount = $before === '' ? 0 : substr_count($before, "\n") + 1;
		$newCount = $after === '' ? 0 : substr_count($after, "\n") + 1;
		$old = $this->lines($before);
		$new = $this->lines($after);
		$context = [];
		$head = 0;

		while ($old->valid() && $new->valid() && $old->current() === $new->current())
		{
			if ($details)
			{
				$context[$head % self::CONTEXT] = $old->current();
			}

			$head++;
			$old->next();
			$new->next();
		}

		$oldCount -= $head;
		$newCount -= $head;
		$oldEnd = $this->lines($before, true);
		$newEnd = $this->lines($after, true);
		$tail = 0;

		while ($oldCount > 0 && $newCount > 0 && $oldEnd->current() === $newEnd->current())
		{
			$tail++;
			$oldCount--;
			$newCount--;
			$oldEnd->next();
			$newEnd->next();
		}

		if (max($oldCount, $newCount) > self::LIMIT)
		{
			return $details ? null : ['additions' => $newCount, 'deletions' => $oldCount];
		}

		if ($details)
		{
			// Budget for packed PHP array rows (including unused capacity), line
			// operations and JSON encoding before allocating the alignment table.
			$limit = ini_parse_quantity(ini_get('memory_limit'));
			$estimate = 32 * ($oldCount + 1) * ($newCount + 1)
				+ 1024 * ($oldCount + $newCount + 2 * self::CONTEXT)
				+ 6 * (strlen($before) + strlen($after));

			if ($limit > 0 && $estimate > $limit - memory_get_usage(true))
			{
				return null;
			}
		}

		$oldMiddle = $this->take($old, $oldCount);
		$newMiddle = $this->take($new, $newCount);
		$table = $details ? [] : null;
		$shared = $this->lengths($oldMiddle, $newMiddle, $table);
		$result = ['additions' => $newCount - $shared, 'deletions' => $oldCount - $shared];

		if ($details)
		{
			$operations = [];

			for ($line = max(0, $head - self::CONTEXT); $line < $head; $line++)
			{
				$operations[] = $this->operation('keep', $line, $line, $context[$line % self::CONTEXT]);
			}

			$operations = array_merge($operations, $this->aligned($oldMiddle, $newMiddle, $head, $table));

			foreach ($this->take($old, min(self::CONTEXT, $tail)) as $index => $text)
			{
				$operations[] = $this->operation('keep', $head + $oldCount + $index, $head + $newCount + $index, $text);
			}

			$result['hunks'] = $this->hunks($operations);
		}

		return $result;
	}

	/**
	 * Read lines in either direction without expanding the entire text into an array.
	 *
	 * @param   string  $text     Text with normalized line endings.
	 * @param   bool    $reverse  Whether to start at the final line.
	 *
	 * @return  \Generator<int, string>
	 * @since   6.3.0
	 */
	private function lines(string $text, bool $reverse = false): \Generator
	{
		$length = strlen($text);

		if ($length === 0)
		{
			return;
		}

		$offset = $reverse ? $length : 0;

		do
		{
			if ($reverse)
			{
				$break = $offset === 0 ? false : strrpos($text, "\n", $offset - $length - 1);
				$start = $break === false ? 0 : $break + 1;
				yield substr($text, $start, $offset - $start);
				$offset = $break;
			}
			else
			{
				$break = strpos($text, "\n", $offset);
				yield substr($text, $offset, ($break === false ? $length : $break) - $offset);
				$offset = $break + 1;
			}
		}
		while ($break !== false);
	}

	/**
	 * Consume a bounded number of lines from the current position.
	 *
	 * @param   \Generator  $lines  The remaining lines.
	 * @param   int         $count  How many lines to consume.
	 *
	 * @return  array<int, string>
	 * @since   6.3.0
	 */
	private function take(\Generator $lines, int $count): array
	{
		$result = [];

		for ($i = 0; $i < $count; $i++)
		{
			$result[] = $lines->current();
			$lines->next();
		}

		return $result;
	}

	/**
	 * Line up two middles by their longest common run of lines.
	 *
	 * @param   array<int, string>  $old   The differing lines as they stand.
	 * @param   array<int, string>  $new   The differing lines as they would be.
	 * @param   int                 $head     How many shared lines came first.
	 * @param   array               $lengths  The alignment lengths for these lines.
	 *
	 * @return  array<int, array{op: string, old: int|null, new: int|null, text: string}>  The operations in order.
	 * @since   6.2.0
	 */
	protected function aligned(array $old, array $new, int $head, array $lengths): array
	{
		$operations = [];
		$oldIndex = 0;
		$newIndex = 0;
		$oldCount = count($old);
		$newCount = count($new);

		while ($oldIndex < $oldCount && $newIndex < $newCount)
		{
			if ($old[$oldIndex] === $new[$newIndex])
			{
				$operations[] = $this->operation(
					'keep', $head + $oldIndex, $head + $newIndex, $old[$oldIndex]
				);
				$oldIndex++;
				$newIndex++;

				continue;
			}

			// where the two runs are equally long the removal is shown first,
			// which is the order every diff a person has read puts them in
			if ($lengths[$oldIndex + 1][$newIndex] >= $lengths[$oldIndex][$newIndex + 1])
			{
				$operations[] = $this->operation('remove', $head + $oldIndex, null, $old[$oldIndex]);
				$oldIndex++;

				continue;
			}

			$operations[] = $this->operation('add', null, $head + $newIndex, $new[$newIndex]);
			$newIndex++;
		}

		while ($oldIndex < $oldCount)
		{
			$operations[] = $this->operation('remove', $head + $oldIndex, null, $old[$oldIndex]);
			$oldIndex++;
		}

		while ($newIndex < $newCount)
		{
			$operations[] = $this->operation('add', null, $head + $newIndex, $new[$newIndex]);
			$newIndex++;
		}

		return $operations;
	}

	/**
	 * Compute the common subsequence length with one row of working storage.
	 * A detail request also retains the rows needed to reconstruct the edit.
	 *
	 * @param   array<int, string>  $old    The existing lines.
	 * @param   array<int, string>  $new    The proposed lines.
	 * @param   array|null          $table  Optional table for reconstructing hunks.
	 *
	 * @return  int  Number of common lines in the changed middle.
	 * @since   6.2.0
	 */
	protected function lengths(array $old, array $new, ?array &$table): int
	{
		$width = count($new);
		$row = array_fill(0, $width + 1, 0);

		if ($table !== null)
		{
			$table[count($old)] = $row;
		}

		for ($i = count($old) - 1; $i >= 0; $i--)
		{
			$diagonal = 0;

			for ($j = $width - 1; $j >= 0; $j--)
			{
				$below = $row[$j];
				$row[$j] = $old[$i] === $new[$j] ? $diagonal + 1 : max($below, $row[$j + 1]);
				$diagonal = $below;
			}

			if ($table !== null)
			{
				$table[$i] = $row;
			}
		}

		return $row[0];
	}

	/**
	 * One operation on one line.
	 *
	 * @param   string    $op    The operation.
	 * @param   int|null  $old   The line number as it stands, when it has one.
	 * @param   int|null  $new   The line number as it would be, when it has one.
	 * @param   string    $text  The line.
	 *
	 * @return  array{op: string, old: int|null, new: int|null, text: string}  The operation.
	 * @since   6.2.0
	 */
	protected function operation(string $op, ?int $old, ?int $new, string $text): array
	{
		return [
			'op' => $op,
			'old' => $old === null ? null : $old + 1,
			'new' => $new === null ? null : $new + 1,
			'text' => $text
		];
	}

	/**
	 * Gather the operations into hunks of changed lines with their context.
	 *
	 * Two changes close enough that the unchanged lines between them would all
	 * be shown as context anyway are read together, in one hunk; only a gap
	 * wider than that is left out and marked as left out.
	 *
	 * @param   array<int, array{op: string, old: int|null, new: int|null, text: string}>  $operations  The operations in order.
	 *
	 * @return  array<int, array<string, mixed>>  The hunks.
	 * @since   6.2.0
	 */
	protected function hunks(array $operations): array
	{
		$groups = [];
		$group = [];
		$previous = null;

		foreach ($operations as $index => $operation)
		{
			if ($operation['op'] === 'keep')
			{
				continue;
			}

			if ($previous !== null && $index - $previous - 1 > 2 * self::CONTEXT)
			{
				$groups[] = $group;
				$group = [];
			}

			$group[] = $index;
			$previous = $index;
		}

		if ($group !== [])
		{
			$groups[] = $group;
		}

		$hunks = [];
		$last = count($operations) - 1;

		foreach ($groups as $changes)
		{
			$first = max(0, $changes[0] - self::CONTEXT);
			$final = min($last, end($changes) + self::CONTEXT);

			$hunks[] = $this->hunk(array_slice($operations, $first, $final - $first + 1));
		}

		return $hunks;
	}

	/**
	 * One hunk, told where it starts on each side.
	 *
	 * @param   array<int, array{op: string, old: int|null, new: int|null, text: string}>  $lines  The hunk's lines.
	 *
	 * @return  array<string, mixed>  The hunk.
	 * @since   6.2.0
	 */
	protected function hunk(array $lines): array
	{
		$old = null;
		$new = null;

		foreach ($lines as $line)
		{
			$old = $old ?? $line['old'];
			$new = $new ?? $line['new'];
		}

		return [
			'old' => $old ?? 0,
			'new' => $new ?? 0,
			'lines' => array_values($lines)
		];
	}
}

