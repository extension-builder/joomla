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
	 * The folders below src say where the dots begin: the namespace segments
	 * they mirror become dot parts, and whatever the path does not mirror stays
	 * backslashed as the vendor folder name. A file whose path does not follow
	 * its namespace has no such seam, so null says the convention must decide.
	 *
	 * @param   string         $namespace  The declared namespace, without the class.
	 * @param   string         $class      The class name.
	 * @param   array<string>  $folders    The folder names below the source root.
	 *
	 * @return  string|null  The stored form, or null when path and namespace disagree.
	 * @since   6.1.7
	 */
	public function stored(string $namespace, string $class, array $folders): ?string
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

		$head = array_slice($segments, 0, count($segments) - $count);
		$tail = implode('.', array_merge($folders, [$class]));

		return $head === [] ? $tail : implode('\\', $head) . '\\' . $tail;
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
	 * @param   string  $stored  The stored form with concrete values.
	 *
	 * @return  string  The stored form as a power row carries it.
	 * @since   6.1.7
	 */
	public function placeholderize(string $stored): string
	{
		$prefix = $this->placeholders->prefix();
		$component = $this->placeholders->component();
		$sections = explode('\\', $stored);
		$last = count($sections) - 1;

		foreach ($sections as $index => $section)
		{
			if ($index === 0 && $prefix !== '' && $section === $prefix)
			{
				$sections[0] = Placeholders::PREFIX;

				continue;
			}

			if ($component === '')
			{
				continue;
			}

			if ($index < $last && $section === $component)
			{
				$sections[$index] = Placeholders::COMPONENT;

				continue;
			}

			if ($index === $last && str_contains($section, '.'))
			{
				$parts = explode('.', $section);

				// the final part is the class itself, never a placeholder
				foreach (array_slice(array_keys($parts), 0, -1) as $key)
				{
					if ($parts[$key] === $component)
					{
						$parts[$key] = Placeholders::COMPONENT;
					}
				}

				$sections[$index] = implode('.', $parts);
			}
		}

		return implode('\\', $sections);
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

		return NamespaceHelper::safe(str_replace('.', '\\', $stored));
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
