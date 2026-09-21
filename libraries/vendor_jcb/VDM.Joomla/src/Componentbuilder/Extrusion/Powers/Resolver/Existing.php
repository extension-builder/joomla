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


use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Utilities\GuidHelper;


/**
 * Retains Power definitions by GUID and namespace lookup candidate sets.
 *
 * A compiled namespace is evidence, not a globally unique definition identity.
 * These lookups never discard a GUID because another definition has the same
 * namespace. Choosing an update target additionally requires scoped evidence.
 *
 * @since 6.1.7
 */
final class Existing
{
	/**
	 * The database loader.
	 *
	 * @var    LoadInterface
	 * @since  6.1.7
	 */
	protected LoadInterface $load;

	/**
	 * The namespace conversion resolver.
	 *
	 * @var    Namespacer
	 * @since  6.1.7
	 */
	protected Namespacer $namespacer;

	/**
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.1.7
	 */
	protected Report $report;

	/**
	 * GUID records and namespace/FQN maps of GUID-keyed candidate records.
	 *
	 * @var    array<string, array>|null
	 * @since  6.1.7
	 */
	protected ?array $index = null;

	/**
	 * The context in which the secondary indexes were resolved.
	 *
	 * @var    string|null
	 * @since  6.1.7
	 */
	protected ?string $under = null;

	/**
	 * Constructor.
	 *
	 * @param   LoadInterface  $load        The database loader.
	 * @param   Namespacer     $namespacer  The namespace conversion resolver.
	 * @param   Report         $report      The run report registry.
	 *
	 * @since   6.1.7
	 */
	public function __construct(LoadInterface $load, Namespacer $namespacer, Report $report)
	{
		$this->load = $load;
		$this->namespacer = $namespacer;
		$this->report = $report;
	}

	/**
	 * Read an unambiguous concrete-name lookup, without granting write scope.
	 *
	 * @param   string  $fqn  The fully qualified class name.
	 *
	 * @return  array|null  The only candidate, or null for no match or a collision.
	 * @since   6.1.7
	 */
	public function find(string $fqn): ?array
	{
		return $this->unique($this->index()['class'][$this->namespacer->key($fqn)] ?? []);
	}

	/**
	 * Read an unambiguous namespace lookup, without treating a template as identity.
	 *
	 * @param   string  $namespace  The stored namespace.
	 *
	 * @return  array|null  The only candidate, or null for no match or a collision.
	 * @since   6.1.8
	 */
	public function match(string $namespace): ?array
	{
		return $this->unique($this->index()['namespace'][$this->identity($namespace)] ?? []);
	}

	/**
	 * Retrieve a definition even when its namespace collides or cannot resolve.
	 *
	 * @param   string  $guid  The Power identity.
	 *
	 * @return  array|null  The record, including its eligibility, or null.
	 * @since   6.1.9
	 */
	public function power(string $guid): ?array
	{
		return $this->index()['guid'][strtolower(trim($guid))] ?? null;
	}

	/**
	 * Read every valid GUID, not just the first record under each namespace.
	 *
	 * @return  array<string, array>  Records keyed and sorted by GUID.
	 * @since   6.2.0
	 */
	public function records(): array
	{
		return $this->index()['guid'];
	}

	/**
	 * Gather both lookup sets before any scoped decision is made.
	 *
	 * @param   string  $namespace  Optional stored namespace.
	 * @param   string  $fqn        Optional concrete class name.
	 *
	 * @return  array<string, array>  All competing records, sorted by GUID.
	 * @since   6.2.0
	 */
	public function candidates(string $namespace = '', string $fqn = ''): array
	{
		$index = $this->index();
		$candidates = $namespace === '' ? [] : ($index['namespace'][$this->identity($namespace)] ?? []);

		if ($fqn !== '')
		{
			$candidates += $index['class'][$this->namespacer->key($fqn)] ?? [];
		}

		ksort($candidates);

		return $candidates;
	}

	/**
	 * Gather every conventional placement of a reference before returning one.
	 *
	 * This compatibility lookup does not establish component association. An
	 * ambiguous set remains ambiguous even when its first placement is unique.
	 *
	 * @param   string  $fqn  The written fully qualified class name.
	 *
	 * @return  array|null  The only lookup candidate, or null.
	 * @since   6.1.9
	 */
	public function fold(string $fqn): ?array
	{
		$segments = array_values(array_filter(explode('\\', trim($fqn, '\\')), 'strlen'));

		if (count($segments) < 2)
		{
			return null;
		}

		$class = (string) array_pop($segments);
		$forms = [$this->namespacer->conventional(implode('\\', $segments), $class)];

		for ($keep = 1; $keep <= count($segments); $keep++)
		{
			$forms[] = implode('\\', array_slice($segments, 0, $keep)) . '\\'
				. implode('.', array_merge(array_slice($segments, $keep), [$class]));
		}

		$candidates = [];

		foreach (array_unique($forms) as $form)
		{
			$candidates += $this->candidates($this->namespacer->placeholderize($form, false));
		}

		return $this->unique($candidates);
	}

	/**
	 * Canonical lookup key; this is not a Power GUID or ownership proof.
	 *
	 * @param   string  $namespace  The stored namespace.
	 *
	 * @return  string  The case-folded lookup key, retaining placement separators.
	 * @since   6.1.9
	 */
	public function identity(string $namespace): string
	{
		return $this->namespacer->key($this->namespacer->canonical($namespace));
	}

	/**
	 * Count retained GUIDs, including collisions and unresolved namespaces.
	 *
	 * @return  int  The number of distinct valid GUIDs.
	 * @since   6.1.7
	 */
	public function count(): int
	{
		return count($this->index()['guid']);
	}

	/**
	 * Invalidate the complete catalogue at a fresh run boundary.
	 *
	 * @return  self  For method chaining.
	 * @since   6.1.7
	 */
	public function refresh(): self
	{
		$this->index = null;
		$this->under = null;

		return $this;
	}

	/**
	 * Build GUID records first; only then build the secondary candidate sets.
	 *
	 * @return  array<string, array>  The complete catalogue and lookup indexes.
	 * @since   6.1.7
	 */
	protected function index(): array
	{
		$under = $this->namespacer->signature();

		if ($this->index !== null && $this->under === $under)
		{
			return $this->index;
		}

		$this->under = $under;
		$this->index = ['namespace' => [], 'class' => [], 'guid' => []];
		$rows = $this->load->items([
			'a.id' => 'id', 'a.guid' => 'guid', 'a.name' => 'name',
			'a.namespace' => 'namespace', 'a.type' => 'type', 'a.system_name' => 'system_name'
		], ['a' => 'power']);

		foreach ((array) $rows as $row)
		{
			$row = (array) $row;
			$guid = strtolower(trim((string) ($row['guid'] ?? '')));

			if (!GuidHelper::valid($guid))
			{
				$this->report->set('powers.invalid.guid.' . (int) ($row['id'] ?? 0), true);

				continue;
			}

			if (isset($this->index['guid'][$guid]))
			{
				$this->index['guid'][$guid]['selectable'] = false;
				$this->report->set('powers.duplicate.guid.' . $this->key($guid), true);

				continue;
			}

			$this->index['guid'][$guid] = [
				'guid' => $guid,
				'id' => (int) ($row['id'] ?? 0),
				'name' => trim((string) ($row['name'] ?? '')),
				'system_name' => trim((string) ($row['system_name'] ?? '')),
				'type' => trim((string) ($row['type'] ?? '')),
				'namespace' => trim((string) ($row['namespace'] ?? '')),
				'selectable' => trim((string) ($row['namespace'] ?? '')) !== ''
			];
		}

		ksort($this->index['guid']);

		foreach ($this->index['guid'] as $guid => $power)
		{
			$namespace = $power['namespace'];

			if ($namespace === '')
			{
				$this->report->set('powers.unresolved.namespace.' . $this->key($guid), '');

				continue;
			}

			$this->index['namespace'][$this->identity($namespace)][$guid] = $power;
			$fqn = $this->namespacer->resolve($namespace);

			if ($fqn === '')
			{
				$this->report->set('powers.unresolved.namespace.' . $this->key($guid), $namespace);

				continue;
			}

			$this->index['class'][$this->namespacer->key($fqn)][$guid] = $power;
		}

		foreach (['namespace', 'class'] as $kind)
		{
			foreach ($this->index[$kind] as $key => $candidates)
			{
				if (count($candidates) < 2)
				{
					continue;
				}

				$this->report->set('powers.collisions.' . $kind . '.' . hash('sha256', $key), array_keys($candidates));

				foreach ($candidates as $guid => $power)
				{
					$this->report->set('powers.duplicate.' . $kind . '.' . $this->key($guid),
						$kind === 'namespace' ? $power['namespace'] : $this->namespacer->resolve($power['namespace']));
				}
			}
		}

		return $this->index;
	}

	/**
	 * Return a single usable candidate, never a first-row tie breaker.
	 *
	 * @param   array<string, array>  $candidates  The complete candidate set.
	 *
	 * @return  array|null  The unique usable record.
	 * @since   6.2.0
	 */
	protected function unique(array $candidates): ?array
	{
		if (count($candidates) !== 1)
		{
			return null;
		}

		$power = reset($candidates);

		return $power['selectable'] ? $power : null;
	}

	/**
	 * Sanitise one registry path segment without changing stored output.
	 *
	 * @param   string  $segment  The raw segment.
	 *
	 * @return  string  A registry-safe segment.
	 * @since   6.1.7
	 */
	protected function key(string $segment): string
	{
		return preg_replace('/[^A-Za-z0-9_]/', '_', $segment) ?? $segment;
	}
}
