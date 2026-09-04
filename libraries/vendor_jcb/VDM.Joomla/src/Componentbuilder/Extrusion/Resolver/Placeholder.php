<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    4th September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Resolver;


use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Placeholders;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;


/**
 * Says a component's own name through the placeholder that stands for it.
 *
 * The compiler holds one map of placeholders and their values -- the system
 * wide rows, the values it derives from the component itself, and the
 * component's own overrides -- and substitutes it into everything it writes
 * with a bare string replacement. So a class, a screen or a form that a
 * component ships carries the component's name where the record it was built
 * from carried a placeholder, and reading that name back unchanged binds the
 * record to one component: a power lifted out of com_demo would say Demo
 * forever, wherever it is used next.
 *
 * The substitution is a plain replacement, so turning a value back into its
 * placeholder always compiles to the very same text. What it cannot tell on
 * its own is whether a run of characters is the component's name or a
 * coincidence, and that is the whole of what is decided here.
 *
 * @since 6.2.0
 */
final class Placeholder
{
	/**
	 * The placeholder targets the compiler derives from the component itself.
	 *
	 * These are the component's own name in the three shapes the compiler
	 * writes it, and its namespace segment. LANG_PREFIX is deliberately not
	 * among them: the compiler reassigns it while it builds a module or a
	 * plugin, so a power that carried it would say MOD_ or PLG_ there, while
	 * COM_ followed by the upper-case name says the same thing everywhere.
	 * That is also the pair the compiler's own custom code extractor writes.
	 *
	 * @var    array<string>
	 * @since  6.2.0
	 */
	private const IDENTITY = ['component', 'Component', 'COMPONENT', 'ComponentNamespace'];

	/**
	 * The shortest component name that can be told from a coincidence.
	 *
	 * @var    int
	 * @since  6.2.0
	 */
	private const NAME = 3;

	/**
	 * The shortest value of a person's own placeholder that can be told apart.
	 *
	 * A person's target stands for whatever they typed, so it carries no
	 * evidence of its own. Two characters of it are worth nothing: JCB ships
	 * placeholders standing for 60 and for VDM, and a run that trusted them
	 * would rewrite every namespace and every small number in the component.
	 *
	 * @var    int
	 * @since  6.2.0
	 */
	private const VALUE = 4;

	/**
	 * The Placeholders Resolver.
	 *
	 * @var    Placeholders
	 * @since  6.2.0
	 */
	protected Placeholders $placeholders;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.2.0
	 */
	protected Report $report;

	/**
	 * The value of each placeholder that may be said, longest value first.
	 *
	 * Held against a signature of the map it was read from, so a run that has
	 * changed what the placeholders resolve to reads the new values without
	 * anything having to remember to say so.
	 *
	 * @var    array<string, array<int, array{target: string, value: string}>>
	 * @since  6.2.0
	 */
	protected array $spoken = [];

	/**
	 * Constructor.
	 *
	 * @param   Placeholders  $placeholders  The placeholder value resolver.
	 * @param   Report        $report        The run report registry.
	 *
	 * @since   6.2.0
	 */
	public function __construct(Placeholders $placeholders, Report $report)
	{
		$this->placeholders = $placeholders;
		$this->report = $report;
	}

	/**
	 * One piece of harvested text, saying names through their placeholders.
	 *
	 * @param   string  $text  The text as the source stated it.
	 *
	 * @return  string  The text, deferring to placeholders where it named values.
	 * @since   6.2.0
	 */
	public function reverse(string $text): string
	{
		if ($text === '')
		{
			return $text;
		}

		$spoken = $this->spoken();

		if ($spoken === [])
		{
			return $text;
		}

		$replaced = preg_replace_callback(
			$this->pattern($spoken),
			function (array $found) use ($spoken): string
			{
				foreach ($spoken as $index => $entry)
				{
					if (($found[$index + 1] ?? '') !== '')
					{
						return '[[[' . $entry['target'] . ']]]';
					}
				}

				return $found[0];
			},
			$text
		);

		return is_string($replaced) ? $replaced : $text;
	}

	/**
	 * Every placeholder whose value this run may say, longest value first.
	 *
	 * Longest first is what lets a value that contains another settle before
	 * it, so the more particular of two overlapping names is the one written.
	 *
	 * @return  array<int, array{target: string, value: string}>  The placeholders.
	 * @since   6.2.0
	 */
	protected function spoken(): array
	{
		$signature = md5((string) json_encode([
			$this->placeholders->core(),
			$this->placeholders->custom(),
			$this->placeholders->component()
		]));

		if (isset($this->spoken[$signature]))
		{
			return $this->spoken[$signature];
		}

		$identity = $this->identity();
		$spoken = $identity + $this->owned($identity);

		uasort(
			$spoken,
			static fn (string $one, string $two): int => strlen($two) <=> strlen($one)
		);

		$this->spoken[$signature] = [];

		foreach ($spoken as $target => $value)
		{
			$this->spoken[$signature][] = ['target' => $target, 'value' => $value];
		}

		$this->announce($this->spoken[$signature]);

		return $this->spoken[$signature];
	}

	/**
	 * The component's own name, in every shape the compiler writes it.
	 *
	 * A name too short to tell from a coincidence is left unsaid, because a
	 * two letter name would claim every pair of those letters in the source.
	 *
	 * @return  array<string, string>  Bare target keyed to its value.
	 * @since   6.2.0
	 */
	protected function identity(): array
	{
		$core = $this->placeholders->core();
		$identity = [];

		foreach (self::IDENTITY as $target)
		{
			$value = $target === 'ComponentNamespace'
				? $this->placeholders->component()
				: (string) ($core[$this->placeholders->placeholder($target)] ?? '');

			if ($value === '' || in_array($value, $identity, true))
			{
				// the namespace segment is routinely the very same word as the
				// component's own name, and one word cannot be two placeholders
				continue;
			}

			if (strlen($value) < self::NAME)
			{
				$this->report->set(
					'unsaid.placeholder.' . $this->key($target),
					'the component is named too briefly to be told from a '
					. 'coincidence, so the source keeps it as it stated it'
				);

				continue;
			}

			$identity[$target] = $value;
		}

		return $identity;
	}

	/**
	 * The placeholders a person defined, where this run can tell them apart.
	 *
	 * The compiler replaces these as unconditionally as it replaces the
	 * component's own name, but it knows which is which and a reading of the
	 * finished source does not. A value two targets share names neither of
	 * them; a value of two or three characters, or one that is only a number,
	 * names nothing at all. Each one left unsaid is named in the report.
	 *
	 * @param   array<string, string>  $identity  What the component's own name is said as.
	 *
	 * @return  array<string, string>  Bare target keyed to its value.
	 * @since   6.2.0
	 */
	protected function owned(array $identity): array
	{
		$claims = [];

		foreach ($this->placeholders->custom() as $placeholder => $value)
		{
			$claims[$value][] = $this->placeholders->target($placeholder);
		}

		$owned = [];

		foreach ($claims as $value => $targets)
		{
			$value = (string) $value;
			$target = (string) $targets[0];
			$reason = $this->unsayable(
				$value, count($targets) + count(array_keys($identity, $value, true))
			);

			if ($reason !== null)
			{
				$this->report->set(
					'unsaid.placeholder.' . $this->key($target), $reason
				);

				continue;
			}

			$owned[$target] = $value;
		}

		return $owned;
	}

	/**
	 * Say in the report which placeholders this run may write.
	 *
	 * A person's own placeholder stands for whatever they typed, and what
	 * they typed may be an ordinary turn of phrase the source says for its
	 * own reasons. So every one this run is willing to write is named, and a
	 * reading of the report says exactly what was deferred to what.
	 *
	 * @param   array<int, array{target: string, value: string}>  $spoken  The placeholders.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function announce(array $spoken): void
	{
		foreach ($spoken as $entry)
		{
			$this->report->set(
				'said.placeholder.' . $this->key($entry['target']), $entry['value']
			);
		}
	}

	/**
	 * Why one of a person's placeholders cannot be said, when it cannot.
	 *
	 * @param   string  $value   The value the placeholder stands for.
	 * @param   int     $claims  How many targets stand for that same value.
	 *
	 * @return  string|null  The reason, or null when the value may be said.
	 * @since   6.2.0
	 */
	protected function unsayable(string $value, int $claims): ?string
	{
		if ($claims > 1)
		{
			return 'more than one placeholder stands for this same value, so '
				. 'reading it back names none of them';
		}


		if (strlen($value) < self::VALUE)
		{
			return 'the value is too short to be told from a coincidence in '
				. 'the source';
		}

		if (preg_match('/^[0-9]+$/', $value) === 1)
		{
			return 'the value is only a number, which the source says for its '
				. 'own reasons far more often than for this one';
		}

		return null;
	}

	/**
	 * Sanitise one registry path segment.
	 *
	 * @param   string  $segment  The raw segment.
	 *
	 * @return  string  A segment safe to use in a dotted registry path.
	 * @since   6.2.0
	 */
	protected function key(string $segment): string
	{
		return preg_replace('/[^A-Za-z0-9_]/', '_', $segment) ?? $segment;
	}

	/**
	 * One expression matching every value that may be said, and nothing else.
	 *
	 * Every value is matched in the one pass, so a placeholder just written
	 * is never read again as if it were source. What bounds a match is not a
	 * word boundary: a component is named in the middle of identifiers all
	 * day -- com_demo, DemoHelper, #__demo_address, COM_DEMO_SAVED -- and a
	 * word boundary finds none of them. What bounds it is the seam between
	 * one part of a name and the next: the edges of the text, anything that
	 * is not a letter or a digit, and the hump where a lower case run gives
	 * way to an upper case one. So Demo is found in DemoHelper and demo in
	 * com_demo, while demonstration, demoted and DEMOGRAPHIC are left alone.
	 *
	 * @param   array<int, array{target: string, value: string}>  $spoken  The placeholders.
	 *
	 * @return  string  The expression.
	 * @since   6.2.0
	 */
	protected function pattern(array $spoken): string
	{
		$parts = [];

		foreach ($spoken as $entry)
		{
			$value = $entry['value'];
			$parts[] = $this->opening($value[0])
				. '(' . preg_quote($value, '/') . ')'
				. $this->closing($value[strlen($value) - 1]);
		}

		return '/' . implode('|', $parts) . '/';
	}

	/**
	 * What has to stand before a value for it to begin a name.
	 *
	 * @param   string  $first  The value's first character.
	 *
	 * @return  string  The look behind, which may be empty.
	 * @since   6.2.0
	 */
	protected function opening(string $first): string
	{
		if (ctype_upper($first))
		{
			// an upper case letter begins a name after anything but another
			// one -- that is the hump, and it is also the start of a word
			return '(?<![A-Z])';
		}

		return ctype_alnum($first) ? '(?<![A-Za-z0-9])' : '';
	}

	/**
	 * What has to stand after a value for it to end a name.
	 *
	 * @param   string  $last  The value's last character.
	 *
	 * @return  string  The look ahead, which may be empty.
	 * @since   6.2.0
	 */
	protected function closing(string $last): string
	{
		if (ctype_upper($last))
		{
			// an upper case run has no hump inside it, so only something that
			// is not part of a name at all can end one
			return '(?![A-Za-z0-9])';
		}

		return ctype_alnum($last) ? '(?![a-z0-9])' : '';
	}
}
