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
	 * @param   string         $namespace  The declared namespace, without the class.
	 * @param   string         $class      The class name.
	 * @param   array<string>  $folders    The folder names below the source root.
	 * @param   string         $library    The library's own folder name, when it has one.
	 *
	 * @return  string|null  The stored form, or null when path and namespace disagree.
	 * @since   6.1.7
	 */
	public function stored(string $namespace, string $class, array $folders, string $library = ''): ?string
	{
		$segments = $this->segments($namespace);
		$folders = array_values(array_filter(array_map('strval', $folders), 'strlen'));
		$count = count($folders);

		if ($segments === [] || $count > count($segments))
		{
			return null;
		}

		if ($folders !== array_slice($segments, count($segments) - $count))
		{
			return null;
		}

		$keep = $this->head($library, $segments, count($segments) - $count);
		$head = array_slice($segments, 0, $keep);
		$tail = implode('.', array_merge(array_slice($segments, $keep), [$class]));

		return implode('\\', $head) . '\\' . $tail;
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
		foreach ($this->placeholders->map() as $placeholder => $value)
		{
			$stored = str_replace(
				[$placeholder, '###' . substr($placeholder, 3, -3) . '###'],
				$value,
				$stored
			);
		}

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
