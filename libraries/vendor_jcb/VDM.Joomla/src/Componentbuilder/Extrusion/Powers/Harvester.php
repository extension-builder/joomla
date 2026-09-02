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

namespace VDM\Joomla\Componentbuilder\Extrusion\Powers;


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Scanner;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Reader\ClassFile;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Existing;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Namespacer;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Harvest;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;


/**
 * Walks the given library folders and gathers every class as a power candidate.
 *
 * Harvesting is the whole first step of the two-step run: everything here is
 * gathered, identified and grouped, and nothing is written. The tree this
 * builds in the Harvest registry -- library, then sub-folder bundle, then
 * class, each candidate carrying its derived identity and whether it already
 * exists -- is deliberately the shape a caller presents for approval, so the
 * eventual interface only has to render it, never reorganise it.
 *
 * Identity is settled here and never revisited: a candidate that resolves to
 * an existing power carries that power's guid, and a new one derives a stable
 * version 5 guid from its class name, so a second harvest of the same library
 * lands on the same identities.
 *
 * @since 6.1.7
 */
final class Harvester
{
	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.7
	 */
	protected Config $config;

	/**
	 * The Scanner Class.
	 *
	 * @var    Scanner
	 * @since  6.1.7
	 */
	protected Scanner $scanner;

	/**
	 * The Class File Reader.
	 *
	 * @var    ClassFile
	 * @since  6.1.7
	 */
	protected ClassFile $reader;

	/**
	 * The Namespacer Resolver.
	 *
	 * @var    Namespacer
	 * @since  6.1.7
	 */
	protected Namespacer $namespacer;

	/**
	 * The Existing Power Resolver.
	 *
	 * @var    Existing
	 * @since  6.1.7
	 */
	protected Existing $existing;

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.7
	 */
	protected Guid $guid;

	/**
	 * The Harvest Registry.
	 *
	 * @var    Harvest
	 * @since  6.1.7
	 */
	protected Harvest $harvest;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.7
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Config      $config      The extrusion configuration.
	 * @param   Scanner     $scanner     The bounded tree scanner.
	 * @param   ClassFile   $reader      The class file reader.
	 * @param   Namespacer  $namespacer  The namespace conversion resolver.
	 * @param   Existing    $existing    The existing power resolver.
	 * @param   Guid        $guid        The identity resolver.
	 * @param   Harvest     $harvest     The harvest registry.
	 * @param   Report      $report      The run report registry.
	 *
	 * @since   6.1.7
	 */
	public function __construct(
		Config $config,
		Scanner $scanner,
		ClassFile $reader,
		Namespacer $namespacer,
		Existing $existing,
		Guid $guid,
		Harvest $harvest,
		Report $report
	)
	{
		$this->config = $config;
		$this->scanner = $scanner;
		$this->reader = $reader;
		$this->namespacer = $namespacer;
		$this->existing = $existing;
		$this->guid = $guid;
		$this->harvest = $harvest;
		$this->report = $report;
	}

	/**
	 * Harvest every library folder the run was given, once per identity context.
	 *
	 * A harvest settles identities under the placeholder values of its moment,
	 * so it only stands while those values do: naming another component after
	 * harvesting quietly regathers, rather than extruding identities the new
	 * component would never recognise.
	 *
	 * @return  int  The number of class candidates harvested.
	 * @since   6.1.7
	 */
	public function harvest(): int
	{
		$signature = $this->namespacer->signature();

		if ((bool) $this->harvest->get('harvested', false)
			&& $this->harvest->get('signature') === $signature)
		{
			return (int) $this->report->get('counts.powers.classes', 0);
		}

		// a fresh gather must see the table as it stands now, and witness the
		// placeholder values of its own classes rather than an earlier run's
		$this->harvest->clear();
		$this->existing->refresh();
		$this->namespacer->forget();

		$found = 0;
		$existing = 0;

		foreach ((array) $this->config->get('libraries', []) as $path)
		{
			$counts = $this->library((string) $path);
			$found += $counts[0];
			$existing += $counts[1];
		}

		$this->harvest->set('harvested', true);
		$this->harvest->set('signature', $signature);
		$this->report->set('counts.powers.classes', $found);
		$this->report->set('counts.powers.existing', $existing);
		$this->report->set('counts.powers.new', $found - $existing);

		return $found;
	}

	/**
	 * Harvest one library folder.
	 *
	 * @param   string  $path  The library folder path as given.
	 *
	 * @return  array{0: int, 1: int}  How many candidates were found, and how many already exist.
	 * @since   6.1.7
	 */
	protected function library(string $path): array
	{
		$root = $this->scanner->root($path);

		if ($root === null)
		{
			$this->report->set('powers.failed.library.' . md5($path), $path);

			return [0, 0];
		}

		$found = 0;
		$existing = 0;

		foreach ($this->vendors($root) as $folder => $source)
		{
			$counts = $this->vendor($root, (string) $folder, $source);
			$found += $counts[0];
			$existing += $counts[1];
		}

		return [$found, $existing];
	}

	/**
	 * The vendor folders one library path holds, and where each one's classes start.
	 *
	 * A Joomla library extension is a folder of vendor folders: the extension
	 * folder is what Joomla installs, and inside it each vendor folder names a
	 * namespace head in its own dotted name and keeps its classes under src.
	 * Reading the extension folder as though it were the vendor would lose that
	 * name, which is the only place the convention records the head -- so every
	 * folder holding a src is its own library here, and a path that is already
	 * one answers for itself.
	 *
	 * @param   string  $root  The resolved library path.
	 *
	 * @return  array<string, string>  Vendor folder name keyed to its source root.
	 * @since   6.1.8
	 */
	protected function vendors(string $root): array
	{
		if (is_dir($root . '/src'))
		{
			return [basename($root) => $root . '/src'];
		}

		$found = [];
		$handle = @opendir($root);

		if ($handle !== false)
		{
			while (($entry = readdir($handle)) !== false)
			{
				if ($entry === '.' || $entry === '..')
				{
					continue;
				}

				if (is_dir($root . '/' . $entry . '/src'))
				{
					$found[$entry] = $root . '/' . $entry . '/src';
				}
			}

			closedir($handle);
		}

		if ($found === [])
		{
			// nothing states a vendor, so the path speaks for itself
			return [basename($root) => $root];
		}

		ksort($found);

		return $found;
	}

	/**
	 * Harvest one vendor folder.
	 *
	 * @param   string  $root    The library path the vendor sits in.
	 * @param   string  $folder  The vendor folder name, which states its head.
	 * @param   string  $source  The root the dots count from.
	 *
	 * @return  array{0: int, 1: int}  How many candidates were found, and how many already exist.
	 * @since   6.1.8
	 */
	protected function vendor(string $root, string $folder, string $source): array
	{
		$key = $base = $this->key($folder);
		$tail = 1;

		// two libraries may share a folder name without sharing an entry
		while ($this->harvest->exists('libraries.' . $key))
		{
			$key = $base . '_' . ++$tail;
		}

		$found = 0;
		$existing = 0;
		$bundles = [];

		foreach ($this->scanner->files($source, ['php']) as $file)
		{
			$candidate = $this->candidate($file, $source, $key, $folder);

			if ($candidate === null)
			{
				continue;
			}

			$found++;

			if ($candidate['exists'])
			{
				$existing++;
			}

			$bundle = $candidate['bundle'];
			$bundles[$bundle] ??= ['folder' => $bundle, 'count' => 0, 'classes' => []];
			$bundles[$bundle]['count']++;
			$bundles[$bundle]['classes'][] = $candidate['guid'];
		}

		$this->harvest->set('libraries.' . $key, [
			'path' => $root,
			'folder' => $folder,
			'source' => $source,
			'count' => $found
		]);

		foreach ($bundles as $bundle => $details)
		{
			$this->harvest->set(
				'libraries.' . $key . '.bundles.'
				. ($bundle === '' ? '_root' : $this->key($bundle)),
				$details
			);
		}

		return [$found, $existing];
	}

	/**
	 * Read one file into a harvest candidate.
	 *
	 * @param   string  $file     The absolute file path.
	 * @param   string  $source   The source root the dots count from.
	 * @param   string  $library  The library key the candidate belongs to.
	 * @param   string  $folder   The library's own folder name, which states its head.
	 *
	 * @return  array{guid: string, exists: bool, bundle: string}|null  The stored candidate essentials, or null.
	 * @since   6.1.7
	 */
	protected function candidate(string $file, string $source, string $library, string $folder): ?array
	{
		$code = $this->scanner->read($file);

		if ($code === null)
		{
			$this->report->set('powers.skipped.unreadable.' . md5($file), $file);

			return null;
		}

		$parts = $this->reader->read($code);

		if ($parts === null)
		{
			$this->report->set('powers.skipped.noclass.' . md5($file), $file);

			return null;
		}

		if ($parts['type'] === null)
		{
			$this->report->set('powers.skipped.unsupported.' . md5($file), $file);

			return null;
		}

		if ($parts['namespace'] === '')
		{
			$this->report->set('powers.skipped.nonamespace.' . md5($file), $file);

			return null;
		}

		if ($parts['body'] === null)
		{
			// writing a power with a silently lost body would be far worse
			$this->report->set('powers.skipped.unparsable.' . md5($file), $file);

			return null;
		}

		$relative = ltrim(substr($file, strlen($source)), '/');
		$bundle = str_contains($relative, '/') ? dirname($relative) : '';
		$folders = $bundle === '' ? [] : explode('/', $bundle);
		$fqn = $parts['namespace'] . '\\' . $parts['class'];

		if (basename($file, '.php') !== $parts['class'])
		{
			$this->report->set('powers.mismatch.filename.' . md5($file), $file);
		}

		// the source root's own folders may mirror more of the namespace than
		// the folders below it, when the run was aimed below the real root
		$stored = $this->namespacer->stored(
			$parts['namespace'], $parts['class'], $folders, $folder,
			explode('/', trim($source, '/'))
		);

		if ($stored === null)
		{
			$stored = $this->namespacer->conventional($parts['namespace'], $parts['class']);
			$this->report->set('powers.derived.convention.' . md5($file), $fqn);
		}

		$placeholder = $this->namespacer->placeholderize($stored);

		// a power is the same power when it folds to the same stored
		// namespace, whatever prefix the library it came out of was built
		// with -- and failing that, when it compiles to the very class name
		$matched = '';
		$existing = $this->existing->match($placeholder);

		if ($existing !== null)
		{
			$matched = 'identity';
		}
		elseif (($existing = $this->existing->find($fqn)) !== null)
		{
			$matched = 'class';
		}

		$guid = $existing['guid'] ?? $this->guid->derive(['power', $placeholder]);

		if ($this->harvest->exists('classes.' . $guid))
		{
			$this->report->set('powers.skipped.duplicate.' . md5($file), $fqn);

			return null;
		}

		$this->harvest->set('classes.' . $guid, [
			'guid' => $guid,
			'library' => $library,
			'file' => $file,
			'relative' => $relative,
			'bundle' => $bundle,
			'class' => $parts['class'],
			'type' => $parts['type'],
			'namespace' => $parts['namespace'],
			'fqn' => $fqn,
			'stored' => $stored,
			'placeholder' => $placeholder,
			'exists' => $existing !== null,
			'id' => $existing['id'] ?? 0,
			'matched' => $matched,
			'standing' => (string) ($existing['namespace'] ?? ''),
			'action' => $existing === null ? 'create'
				: ($this->config->get('onExisting', 'update') === 'skip' ? 'skip' : 'update'),
			'docblock' => $parts['docblock'],
			'license' => $parts['license'],
			'extends' => $parts['extends'],
			'implements' => $parts['implements'],
			'uses' => $parts['uses'],
			'body' => (string) $parts['body']
		]);

		return [
			'guid' => $guid,
			'exists' => $existing !== null,
			'bundle' => $bundle
		];
	}

	/**
	 * Sanitise one registry path segment.
	 *
	 * @param   string  $segment  The raw segment.
	 *
	 * @return  string  A segment safe to use in a dotted registry path.
	 * @since   6.1.7
	 */
	protected function key(string $segment): string
	{
		return preg_replace('/[^A-Za-z0-9_]/', '_', $segment) ?? $segment;
	}
}
