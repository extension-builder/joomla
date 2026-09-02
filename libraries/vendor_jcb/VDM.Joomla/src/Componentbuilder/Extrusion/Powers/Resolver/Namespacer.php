<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    22nd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver;


use VDM\Joomla\Utilities\String\ClassfunctionHelper;
use VDM\Joomla\Utilities\String\NamespaceHelper;


/**
 * Converts between a class's real namespace and the form a power row stores.
 *
 * A stored power namespace is both an identity and a placement instruction: its
 * backslash segments name the vendor library folder, the dots in its last
 * segment name the folders below src, and its final dot part is the class
 * itself -- the exact inverse of what Compiler\Power::setNamespace unfolds.
 * Reversing a built class is therefore two independent conversions: fold the
 * file's location back into the dot form, and defer the resolved prefix and
 * component segments back to their placeholders. Matching an existing power
 * runs the same conversions the other way.
 *
 * @since 6.1.7
 */
final class Namespacer
{
	/**
	 * The Placeholders Resolver.
	 *
	 * @var    Placeholders
	 * @since  6.1.7
	 */
	protected Placeholders $placeholders;

	/**
	 * Constructor.
	 *
	 * @param   Placeholders  $placeholders  The placeholder value resolver.
	 *
	 * @since   6.1.7
	 */
	public function __construct(Placeholders $placeholders)
	{
		$this->placeholders = $placeholders;
	}

	/**
	 * Fold a class's declared namespace and location into the stored dot form.
	 *
	 * The library's own folder name is the first authority: a dotted name such
	 * as VDM.Joomla states, in the only place the convention states it, how
	 * many leading namespace segments were folded into that one folder. Those
	 * segments stay backslashed as the head; everything below them mirrors the
	 * folders under src and becomes dots. Where the folder name says nothing,
	 * the path decides -- the segments the folders mirror become dot parts, and
	 * the rest stays the head. A file whose path contradicts its namespace has
	 * no seam to read, so null says the convention must decide instead.
	 *
	 * The folders below the source root are only the part of the path the
	 * run was aimed at. A person may aim it at a folder deeper than the real
	 * source root -- a component's own Engine folder, say -- and the folders
	 * above that root then mirror more of the namespace than the folders
	 * below it. The seam is where the mirroring stops, wherever the run was
	 * aimed, because that is where the compiler put the class.
	 *
	 * @param   string         $namespace  The declared namespace, without the class.
	 * @param   string         $class      The class name.
	 * @param   array<string>  $folders    The folder names below the source root.
	 * @param   string         $library    The library's own folder name, when it has one.
	 * @param   array<string>  $above      The folder names of the source root itself, outermost first.
	 *
	 * @return  string|null  The stored form, or null when path and namespace disagree.
	 * @since   6.1.7
	 */
	public function stored(string $namespace, string $class, array $folders, string $library = '', array $above = []): ?string
	{
		$segments = $this->segments($namespace);
		$folders = array_values(array_filter(array_map('strval', $folders), 'strlen'));
		$above = array_values(array_filter(array_map('strval', $above), 'strlen'));
		$count = count($folders);

		if ($segments === [] || $count > count($segments))
		{
			return null;
		}

		if ($folders !== array_slice($segments, count($segments) - $count))
		{
			return null;
		}

		$mirrored = $count + $this->mirrored(
			array_slice($segments, 0, count($segments) - $count),
			$above
		);

		$keep = $this->head($library, $segments, count($segments) - $mirrored);
		$head = array_slice($segments, 0, $keep);
		$tail = implode('.', array_merge(array_slice($segments, $keep), [$class]));

		return implode('\\', $head) . '\\' . $tail;
	}

	/**
	 * How many trailing namespace segments the trailing folders mirror.
	 *
	 * Counted from the innermost folder outward, segment for segment and
	 * name for name, until the first folder that is not the segment above it
	 * -- the source root itself, in every layout the compiler writes.
	 *
	 * @param   array<string>  $segments  The namespace segments still unaccounted for.
	 * @param   array<string>  $folders   The folder names, outermost first.
	 *
	 * @return  int  The number of mirrored segments.
	 * @since   6.1.9
	 */
	protected function mirrored(array $segments, array $folders): int
	{
		$found = 0;
		$stop = min(count($segments), count($folders));

		while ($found < $stop
			&& $segments[count($segments) - 1 - $found]
				=== $folders[count($folders) - 1 - $found])
		{
			$found++;
		}

		return $found;
	}

	/**
	 * How many leading namespace segments the head keeps.
	 *
	 * A dotted library folder names its own head, segment for segment, so when
	 * the namespace opens with exactly those segments the folder has answered.
	 * Otherwise the path's own seam stands, and a head the path leaves empty
	 * falls back to the convention every power JCB ships follows.
	 *
	 * @param   string         $library   The library's own folder name.
	 * @param   array<string>  $segments  The namespace segments.
	 * @param   int            $seam      The head length the path implies.
	 *
	 * @return  int  The number of segments the head keeps.
	 * @since   6.1.8
	 */
	protected function head(string $library, array $segments, int $seam): int
	{
		$stated = $this->vendor($library);
		$length = count($stated);

		if ($length > 0 && $length <= count($segments)
			&& $stated === array_slice($segments, 0, $length))
		{
			return $length;
		}

		// a stored namespace needs a backslash head to be a namespace at all,
		// and the convention keeps two segments as the vendor folder name
		return max($seam, min(2, count($segments)));
	}

	/**
	 * The namespace segments one library folder name states.
	 *
	 * The dots in a library's folder name are the convention's own record of
	 * the segments that were folded into it, so a name carrying none states
	 * nothing and is left to the path to answer for.
	 *
	 * @param   string  $library  The library's own folder name.
	 *
	 * @return  array<string>  The stated segments, or none.
	 * @since   6.1.8
	 */
	public function vendor(string $library): array
	{
		$library = trim($library);

		if ($library === '' || !str_contains($library, '.'))
		{
			return [];
		}

		return array_values(array_filter(explode('.', $library), 'strlen'));
	}

	/**
	 * Fold a namespace into the stored form by convention alone.
	 *
	 * Every power JCB ships keeps its first two segments as the vendor folder
	 * and dots the rest, so when a file's path cannot say where the seam sits,
	 * that convention is the best available answer.
	 *
	 * @param   string  $namespace  The declared namespace, without the class.
	 * @param   string  $class     The class name.
	 *
	 * @return  string  The stored form.
	 * @since   6.1.7
	 */
	public function conventional(string $namespace, string $class): string
	{
		$segments = $this->segments($namespace);

		if (count($segments) <= 2)
		{
			return implode('\\', array_merge($segments, [$class]));
		}

		$head = array_slice($segments, 0, 2);
		$tail = array_merge(array_slice($segments, 2), [$class]);

		return implode('\\', $head) . '\\' . implode('.', $tail);
	}

	/**
	 * Defer the resolved prefix and component segments back to placeholders.
	 *
	 * The vendor prefix is the first segment: that is what the convention
	 * means, and every power JCB ships carries the placeholder there -- so
	 * the first segment is ALWAYS deferred, whatever it reads. Deferring it
	 * is the whole point: one class then serves components whose prefixes
	 * differ, and a harvested library folds onto the very powers it was
	 * compiled from.
	 *
	 * A segment that answers, case aside, to a component namespace this run
	 * knows -- the component being extruded, the component being paired, or
	 * either one's placeholder overrides -- is that component's own segment,
	 * and is deferred the same way. What it actually read is witnessed with
	 * the vendor prefix beside it, so the run can record the values the
	 * placeholders must resolve back to.
	 *
	 * @param   string  $stored   The stored form with concrete values.
	 * @param   bool    $witness  Whether a recognised component segment is witnessed.
	 *
	 * @return  string  The stored form as a power row carries it.
	 * @since   6.1.7
	 */
	public function placeholderize(string $stored, bool $witness = true): string
	{
		$sections = explode('\\', $stored);
		$last = count($sections) - 1;
		$vendor = (string) $sections[0];
		$component = null;

		foreach ($sections as $index => $section)
		{
			if ($index === 0 && $last > 0)
			{
				$sections[0] = Placeholders::PREFIX;

				continue;
			}

			if ($index < $last && $this->placeholders->answers($section))
			{
				$component ??= $section;
				$sections[$index] = Placeholders::COMPONENT;

				continue;
			}

			if ($index === $last && str_contains($section, '.'))
			{
				$parts = explode('.', $section);

				// the final part is the class itself, never a placeholder
				foreach (array_slice(array_keys($parts), 0, -1) as $key)
				{
					if ($this->placeholders->answers($parts[$key]))
					{
						$component ??= $parts[$key];
						$parts[$key] = Placeholders::COMPONENT;
					}
				}

				$sections[$index] = implode('.', $parts);
			}
		}

		if ($witness && $component !== null)
		{
			// a class carrying the component's segment is the component's own,
			// so its library states the very values the placeholders must
			// resolve back to when the component is compiled again -- an
			// import merely refers, so it never testifies
			$this->placeholders->witness($vendor, $component);
		}

		return implode('\\', $sections);
	}

	/**
	 * The one form a stored namespace has, whatever placeholders it was written through.
	 *
	 * A person may store a namespace through a placeholder of their own --
	 * [[[ComponentEngineNamespace]]].Team, where the placeholder stands for
	 * the whole head -- and the compiler resolves it to the very class the
	 * long form names. Identity is the same on both forms, so both fold to
	 * this one: every placeholder the person defined is substituted in the
	 * compiler's own order, the core placeholders stay standing, and the two
	 * wrapper forms become one.
	 *
	 * @param   string  $stored  The stored form, placeholders included.
	 *
	 * @return  string  The canonical stored form.
	 * @since   6.1.9
	 */
	public function canonical(string $stored): string
	{
		$stored = $this->placeholders->expand(trim($stored, '\\'));

		return (string) preg_replace('/###([A-Za-z0-9_]+)###/', '[[[$1]]]', $stored);
	}

	/**
	 * Express a stored namespace through the placeholders the system holds.
	 *
	 * The inverse of canonical: where a person has defined a placeholder
	 * that stands for a namespace head -- the whole of it or a leading run
	 * of it -- a class that sits under that head is stored the way the person
	 * stores everything else under it. The placeholder covering the longest
	 * leading run of segments wins, its value read as the compiler would
	 * resolve it, and the joiner that stood after the covered run is kept:
	 * a dot where a folder follows, a backslash where the head continues.
	 * Only a value that is itself a namespace fragment can stand for one.
	 *
	 * @param   string  $stored  The canonical stored form.
	 *
	 * @return  string  The stored form as the person would write it.
	 * @since   6.1.9
	 */
	public function express(string $stored): string
	{
		$stored = $this->canonical($stored);
		[$segments, $joiners] = $this->split($stored);
		$total = count($segments);
		$map = $this->placeholders->map();
		$best = null;
		$covered = 0;

		foreach ($this->placeholders->custom() as $placeholder => $value)
		{
			if (!str_contains($value, '\\'))
			{
				continue;
			}

			[$parts, $joins] = $this->split($this->canonical($value));
			$length = count($parts);

			if ($length < 2 || $length >= $total || $length <= $covered
				|| !$this->opens($segments, $joiners, $parts, $joins, $map))
			{
				continue;
			}

			$best = $placeholder;
			$covered = $length;
		}

		if ($best === null)
		{
			return $stored;
		}

		return $best . $this->join(
			array_slice($segments, $covered),
			array_slice($joiners, $covered - 1)
		);
	}

	/**
	 * Whether one namespace opens with the given segments, joined the same way.
	 *
	 * Two segments are the same when they resolve to the same word under the
	 * run's placeholder values -- a concrete VDM and [[[NamespacePrefix]]]
	 * agree when that is what the prefix resolves to -- and case aside, as
	 * PHP reads namespaces.
	 *
	 * @param   array<string>          $segments  The namespace segments.
	 * @param   array<string>          $joiners   The joiner after each segment but the last.
	 * @param   array<string>          $parts     The leading segments to test for.
	 * @param   array<string>          $joins     The joiner after each part but the last.
	 * @param   array<string, string>  $map       Placeholder keyed to its value.
	 *
	 * @return  bool  True when the namespace opens with the parts.
	 * @since   6.1.9
	 */
	protected function opens(array $segments, array $joiners, array $parts, array $joins, array $map): bool
	{
		$search = array_keys($map);
		$replace = array_values($map);

		foreach ($parts as $index => $part)
		{
			$part = str_replace($search, $replace, $part);
			$segment = str_replace($search, $replace, (string) ($segments[$index] ?? ''));

			if ($part === '' || strcasecmp($part, $segment) !== 0)
			{
				return false;
			}

			if ($index < count($parts) - 1
				&& ($joins[$index] ?? '') !== ($joiners[$index] ?? ''))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Split a stored namespace into its segments and the joiners between them.
	 *
	 * @param   string  $stored  The stored form.
	 *
	 * @return  array{0: array<string>, 1: array<string>}  The segments, and the joiner after each but the last.
	 * @since   6.1.9
	 */
	protected function split(string $stored): array
	{
		$segments = [''];
		$joiners = [];
		$length = strlen($stored);

		for ($i = 0; $i < $length; $i++)
		{
			$char = $stored[$i];

			if ($char === '\\' || $char === '.')
			{
				$joiners[] = $char;
				$segments[] = '';

				continue;
			}

			$segments[count($segments) - 1] .= $char;
		}

		return [$segments, $joiners];
	}

	/**
	 * Join segments back, each preceded by the joiner that stood before it.
	 *
	 * @param   array<string>  $segments  The segments.
	 * @param   array<string>  $joiners   The joiner before each segment.
	 *
	 * @return  string  The joined text.
	 * @since   6.1.9
	 */
	protected function join(array $segments, array $joiners): string
	{
		$joined = '';

		foreach (array_values($segments) as $index => $segment)
		{
			$joined .= ($joiners[$index] ?? '\\') . $segment;
		}

		return $joined;
	}

	/**
	 * Drop everything the conversions witnessed, so a fresh run reads fresh.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.9
	 */
	public function forget(): self
	{
		$this->placeholders->forget();

		return $this;
	}

	/**
	 * Unfold a stored namespace into the real fully qualified class name.
	 *
	 * @param   string  $stored  The stored form, with or without placeholders.
	 *
	 * @return  string  The fully qualified class name, or an empty string when
	 *                  a placeholder in it has no value to resolve to.
	 * @since   6.1.7
	 */
	public function resolve(string $stored): string
	{
		$stored = $this->placeholders->substitute($stored, $this->placeholders->map());

		if (str_contains($stored, '[[[') || str_contains($stored, '###'))
		{
			return '';
		}

		// the compiler cleans namespace segments harder than the class name:
		// a segment keeps only letters and digits, while the class keeps its
		// underscores -- so the same asymmetry applies here, or a class with
		// an underscore would never recognise its own power
		$segments = array_values(array_filter(
			explode('\\', str_replace('.', '\\', trim($stored, '\\'))),
			'strlen'
		));

		if ($segments === [])
		{
			return '';
		}

		$class = ClassfunctionHelper::safe(array_pop($segments));

		return implode('\\', array_merge(
			array_map([NamespaceHelper::class, 'safeSegment'], $segments),
			[$class]
		));
	}

	/**
	 * The index key one fully qualified class name matches under.
	 *
	 * @param   string  $fqn  The fully qualified class name.
	 *
	 * @return  string  The case-folded key.
	 * @since   6.1.7
	 */
	public function key(string $fqn): string
	{
		return strtolower(trim($fqn, '\\'));
	}

	/**
	 * A stable signature of the placeholder values conversions run under.
	 *
	 * Anything derived from a conversion is only reusable while this stays
	 * the same, because a new component or a reset can change what every
	 * placeholder resolves to.
	 *
	 * @return  string  The signature.
	 * @since   6.1.7
	 */
	public function signature(): string
	{
		return (string) json_encode($this->placeholders->map());
	}

	/**
	 * The clean segments of one backslashed namespace.
	 *
	 * @param   string  $namespace  The namespace to split.
	 *
	 * @return  array<string>  The non-empty segments.
	 * @since   6.1.7
	 */
	protected function segments(string $namespace): array
	{
		return array_values(array_filter(
			explode('\\', trim($namespace, '\\')),
			'strlen'
		));
	}
}
