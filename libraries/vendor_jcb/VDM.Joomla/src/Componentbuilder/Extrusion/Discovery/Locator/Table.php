<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Discovery\Locator;


use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\LocatorInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;


/**
 * Finds a JCB table definition class purely by signature.
 *
 * Every other artifact has a placement the compiler's own move map can invert,
 * so it can be asked for by path. This one cannot: a JCB-built component puts
 * its table definition class wherever that project's power namespace resolves
 * to, which is different for every component. There is therefore no tier one
 * and no tier two shortcut here, only the content signature.
 *
 * The signature is a class with an extends clause that also declares a $tables
 * array property. A file that additionally names TableInterface is ranked first,
 * because that confirms the family rather than merely resembling it. Deciding
 * whether the map is actually usable is not this class's job: the literal-only
 * parser in the reader is the gate, and this locator only offers candidates.
 *
 * The walk is bounded and contained. The tree may be an unzipped upload, so a
 * symbolic link is refused rather than followed, every candidate is proven by
 * realpath to sit below the source root, and the depth and file-count caps keep
 * a pathological tree from turning discovery into a denial of service. Note that
 * vendor is deliberately not pruned: a component's table definition class very
 * often lives inside its vendored power namespace.
 *
 * @since 6.1.6
 */
final class Table implements LocatorInterface
{
	/**
	 * Directory names that cannot hold a table definition class.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const PRUNE = ['.git', 'node_modules'];

	/**
	 * The most files the walk will look at.
	 *
	 * @var    int
	 * @since  6.1.6
	 */
	private const MAX_FILES = 20000;

	/**
	 * The deepest directory level the walk will descend to.
	 *
	 * @var    int
	 * @since  6.1.6
	 */
	private const MAX_DEPTH = 12;

	/**
	 * How much of one candidate is read for the signature test.
	 *
	 * Both halves of the signature are part of a class declaration and its first
	 * property, so the head of the file always carries them, and a bounded read
	 * keeps an enormous file from being pulled into memory whole.
	 *
	 * @var    int
	 * @since  6.1.6
	 */
	private const MAX_BYTES = 1048576;

	/**
	 * Matches the declaration of a $tables array property.
	 *
	 * The type declaration is optional and may be nullable, and both the short
	 * and the long array form count as a match here. Whether the literal is
	 * actually readable is the reader's decision, not this locator's, so the
	 * signature is deliberately the looser of the two tests.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const PROPERTY = '/(?:private|protected|public)\s+(?:static\s+)?(?:readonly\s+)?'
		. '(?:\?\s*)?(?:array\s+)?\$tables\s*=\s*(?:\[|array\s*\()/';

	/**
	 * Matches a class declaration carrying an extends clause.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const INHERITS = '/\bclass\s+\w+\s+extends\s+/';

	/**
	 * Matches an implements clause naming the table interface family.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const FAMILY = '/\bimplements\b[^{;]*\bTableInterface\b/';

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Report  $report  The run report registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(Report $report)
	{
		$this->report = $report;
	}

	/**
	 * The artifact kind this locator is responsible for.
	 *
	 * @return  string  The artifact kind key.
	 * @since   6.1.6
	 */
	public function kind(): string
	{
		return 'table_class';
	}

	/**
	 * Locate every table definition class below a source root.
	 *
	 * @param   string  $root  The absolute, contained source root.
	 *
	 * @return  array<int, array{path: string, tier: string, name: string|null}>  Located artifacts.
	 * @since   6.1.6
	 */
	public function locate(string $root): array
	{
		$resolved = $this->root($root);

		if ($resolved === null)
		{
			return $this->recorded([]);
		}

		$family = [];
		$other = [];

		foreach ($this->walk($resolved) as $path)
		{
			$source = $this->head($path);

			if (!$this->matches($source))
			{
				continue;
			}

			if (preg_match(self::FAMILY, $source) === 1)
			{
				$family[] = $path;

				continue;
			}

			$other[] = $path;
		}

		sort($family, SORT_STRING);
		sort($other, SORT_STRING);
		$found = [];

		foreach (array_merge($family, $other) as $path)
		{
			$found[] = ['path' => $path, 'tier' => 'signature', 'name' => null];
		}

		return $this->recorded($found);
	}

	/**
	 * Whether one candidate's text carries the table definition signature.
	 *
	 * @param   string  $source  The candidate's leading text.
	 *
	 * @return  bool  True when both halves of the signature are present.
	 * @since   6.1.6
	 */
	private function matches(string $source): bool
	{
		if ($source === '')
		{
			return false;
		}

		return preg_match(self::PROPERTY, $source) === 1
			&& preg_match(self::INHERITS, $source) === 1;
	}

	/**
	 * Walk the tree once, collecting contained PHP files.
	 *
	 * @param   string  $root  The resolved source root.
	 *
	 * @return  array<string>  Absolute, contained file paths.
	 * @since   6.1.6
	 */
	private function walk(string $root): array
	{
		$found = [];
		$queue = [[$root, 0]];
		$seen = 0;

		while ($queue !== [])
		{
			$current = array_shift($queue);
			$entries = @scandir($current[0]);

			if ($entries === false)
			{
				continue;
			}

			foreach ($entries as $entry)
			{
				if ($entry === '.' || $entry === '..' || in_array($entry, self::PRUNE, true))
				{
					continue;
				}

				$path = $current[0] . '/' . $entry;

				if (is_link($path))
				{
					$this->report->set('skipped.symlink.' . md5($path), $path);

					continue;
				}

				if (is_dir($path))
				{
					if ($current[1] < self::MAX_DEPTH)
					{
						$queue[] = [$path, $current[1] + 1];

						continue;
					}

					$this->report->set('skipped.depth.' . md5($path), $path);

					continue;
				}

				if (!is_file($path))
				{
					continue;
				}

				$seen++;

				if ($seen > self::MAX_FILES)
				{
					$this->report->set('skipped.maxfiles', self::MAX_FILES);

					return $found;
				}

				if (strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) !== 'php')
				{
					continue;
				}

				$contained = $this->contain($root, $path);

				if ($contained !== null)
				{
					$found[] = $contained;
				}
			}
		}

		return $found;
	}

	/**
	 * Resolve a source root to a real, existing directory.
	 *
	 * @param   string  $root  The candidate source root.
	 *
	 * @return  string|null  The resolved root, or null when it is unusable.
	 * @since   6.1.6
	 */
	private function root(string $root): ?string
	{
		if ($root === '')
		{
			return null;
		}

		$resolved = realpath($root);

		if ($resolved === false || !is_dir($resolved))
		{
			return null;
		}

		return rtrim(str_replace('\\', '/', $resolved), '/');
	}

	/**
	 * Prove one absolute candidate sits below the source root.
	 *
	 * @param   string  $root       The resolved source root.
	 * @param   string  $candidate  The absolute candidate path.
	 *
	 * @return  string|null  The contained absolute path, or null.
	 * @since   6.1.6
	 */
	private function contain(string $root, string $candidate): ?string
	{
		$resolved = realpath($candidate);

		if ($resolved === false)
		{
			return null;
		}

		$resolved = rtrim(str_replace('\\', '/', $resolved), '/');

		if (!str_starts_with($resolved, $root . '/'))
		{
			$this->report->set('skipped.uncontained.' . md5($resolved), $resolved);

			return null;
		}

		return $resolved;
	}

	/**
	 * Read the head of one candidate for the signature test.
	 *
	 * @param   string  $path  Absolute file path.
	 *
	 * @return  string  The leading bytes, or an empty string.
	 * @since   6.1.6
	 */
	private function head(string $path): string
	{
		$handle = @fopen($path, 'rb');

		if ($handle === false)
		{
			return '';
		}

		$content = @fread($handle, self::MAX_BYTES);
		@fclose($handle);

		return $content === false ? '' : $content;
	}

	/**
	 * Record what this locator found, or that it found nothing.
	 *
	 * @param   array<int, array{path: string, tier: string, name: string|null}>  $found  Located artifacts.
	 *
	 * @return  array<int, array{path: string, tier: string, name: string|null}>  The same list.
	 * @since   6.1.6
	 */
	private function recorded(array $found): array
	{
		if ($found === [])
		{
			$this->report->set('located.' . $this->kind() . '.missing', true);

			return $found;
		}

		foreach ($found as $index => $entry)
		{
			$this->report->set('located.' . $this->kind() . '.' . $index . '.path', $entry['path']);
			$this->report->set('located.' . $this->kind() . '.' . $index . '.tier', $entry['tier']);
		}

		return $found;
	}
}
