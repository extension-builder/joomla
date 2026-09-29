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
	 * Positive and negative GUID reads within this operation.
	 *
	 * @var    array<string, array|null>
	 * @since  6.2.0
	 */
	protected array $direct = [];

	/**
	 * Bounded query results and their approval digests, including missing rows.
	 *
	 * @var    array<string, array>
	 * @since  6.2.0
	 */
	protected array $reads = [];

	/**
	 * Context-specific concrete-name buckets for reached and queried records.
	 *
	 * @var    array<string, array<string, array>>
	 * @since  6.2.0
	 */
	protected array $buckets = [];

	/**
	 * Record and query indexing guards; a shared record is normalized once.
	 *
	 * @var    array<string, array<string, bool>>
	 * @since  6.2.0
	 */
	protected array $prepared = [];

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
		$guid = strtolower(trim($guid));

		if (!GuidHelper::valid($guid))
		{
			return null;
		}

		if (!array_key_exists($guid, $this->direct))
		{
			$rows = $this->query(['a.guid' => $guid]);
			$this->direct[$guid] = $rows === [] ? null : $this->definition($rows[0]);

			if (count($rows) > 1)
			{
				$this->direct[$guid]['selectable'] = false;
				$this->report->set('powers.duplicate.guid.' . $this->key($guid), true);
			}
		}

		return $this->direct[$guid];
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
		$this->direct = [];
		$this->reads = [];
		$this->buckets = [];
		$this->prepared = [];

		return $this;
	}

	/**
	 * Index the selected graph once, using the same namespace conversion as lookup.
	 *
	 * @param   array<string>  $guids    Reached Power identities, not a catalogue.
	 * @param   array          $context  The applicable source namespace context.
	 * @param   array|null     $origin   A separately established consumer namespace context.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function prime(array $guids, array $context, ?array $origin = null): void
	{
		$scope = hash('sha256', serialize($context));

		foreach ($guids as $guid)
		{
			$this->indexRecord((string) $guid, $context, $scope, $origin);
		}
	}

	/**
	 * Read only the concrete-name bucket and indexed fallback queries for one class.
	 *
	 * The installed schema indexes both name and namespace. Name reads are cached
	 * once and distributed into FQN buckets, so two unrelated namespaces sharing
	 * a short class name do not cause a repeated candidate walk for every source.
	 * GUID-directed priming also covers renamed or placeholder-derived class names.
	 *
	 * @param   string  $namespace  Source-observed stored placement.
	 * @param   string  $fqn        The concrete PHP class name.
	 * @param   array       $context    The source namespace context.
	 *
	 * @return  array<string, array>  All competing GUIDs in the relevant bucket.
	 * @since   6.2.0
	 */
	public function bounded(string $namespace, string $fqn, array $context): array
	{
		$scope = hash('sha256', serialize($context));
		$parts = explode('\\', trim($fqn, '\\'));
		$name = (string) end($parts);
		$filters = [['a.name' => $name]];

		foreach (array_unique([$namespace, $this->namespacer->placeholderize($namespace, false)]) as $stored)
		{
			if ($stored !== '')
			{
				$filters[] = ['a.namespace' => $stored];
			}
		}

		foreach ($filters as $where)
		{
			$key = 'query:' . serialize($where);

			if (isset($this->prepared[$scope][$key]))
			{
				continue;
			}

			$this->prepared[$scope][$key] = true;

			foreach ($this->query($where) as $row)
			{
				$this->indexRecord((string) ($row['guid'] ?? ''), $context, $scope);
			}
		}

		$candidates = $this->buckets[$scope][$this->namespacer->key($fqn)] ?? [];
		ksort($candidates);

		return $candidates;
	}

	/**
	 * Hash the exact bounded query set, optionally repeating it for approval checks.
	 *
	 * @param   bool  $fresh  Whether to re-read each recorded query.
	 *
	 * @return  string  A private snapshot digest, including negative lookups.
	 * @since   6.2.0
	 */
	public function fingerprint(bool $fresh = false): string
	{
		$digests = [];

		foreach ($this->reads as $key => $read)
		{
			$digests[$key] = $fresh
				? hash('sha256', serialize($this->read($read['where'])))
				: $read['digest'];
		}

		ksort($digests);

		return hash('sha256', serialize($digests));
	}

	/**
	 * Index one definition at most once per applicable namespace context.
	 *
	 * @param   string      $guid     A reached or indexed-query Power identity.
	 * @param   array       $context  The source namespace context.
	 * @param   string      $scope    Its stable operation-local index key.
	 * @param   array|null  $origin   A separately established consumer context.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function indexRecord(string $guid, array $context, string $scope, ?array $origin = null): void
	{
		$guid = strtolower(trim($guid));
		$origin ??= $context;
		$key = 'guid:' . $guid . ':' . hash('sha256', serialize($origin));

		if (isset($this->prepared[$scope][$key]))
		{
			return;
		}

		$this->prepared[$scope][$key] = true;
		$record = $this->power($guid);

		if ($record === null)
		{
			return;
		}

		// Every duplicate row contributes its occupied name, while the GUID remains
		// unselectable. Choosing the first namespace could hide a conflicting row.
		foreach ($this->query(['a.guid' => $guid]) as $row)
		{
			$namespace = trim((string) ($row['namespace'] ?? ''));
			$canonical = $this->namespacer->canonical($namespace, $origin);
			$names = [
				$this->namespacer->resolve($canonical, $context),
				$this->namespacer->resolve($namespace, $origin)
			];

			foreach (array_unique($names) as $fqn)
			{
				if ($fqn !== '')
				{
					$this->buckets[$scope][$this->namespacer->key($fqn)][$guid] = $record;
				}
			}
		}
	}

	/**
	 * Reuse a bounded, parameterised query, retaining both positive and negative reads.
	 *
	 * @param   array  $where  Indexed equality conditions.
	 *
	 * @return  array<int, array>  Raw lookup rows, deterministically ordered.
	 * @since   6.2.0
	 */
	protected function query(array $where): array
	{
		$key = serialize($where);

		if (!isset($this->reads[$key]))
		{
			$rows = $this->read($where);
			$this->reads[$key] = [
				'where' => $where, 'rows' => $rows,
				'digest' => hash('sha256', serialize($rows))
			];
		}

		return $this->reads[$key]['rows'];
	}

	/**
	 * Execute an indexed lookup without invoking the compatibility catalogue API.
	 *
	 * @param   array  $where  Indexed equality conditions.
	 *
	 * @return  array<int, array>  The identity and namespace lookup columns.
	 * @since   6.2.0
	 */
	protected function read(array $where): array
	{
		$rows = array_map(static fn ($row): array => (array) $row, (array) $this->load->items([
			'a.id' => 'id', 'a.guid' => 'guid', 'a.name' => 'name',
			'a.namespace' => 'namespace', 'a.type' => 'type', 'a.system_name' => 'system_name'
		], ['a' => 'power'], $where));
		usort($rows, static fn (array $a, array $b): int => strcmp(serialize($a), serialize($b)));

		return $rows;
	}

	/**
	 * Normalize the shared public record shape without changing stored representation.
	 *
	 * @param   array  $row  A raw lookup record.
	 *
	 * @return  array  The compact definition and its initial eligibility.
	 * @since   6.2.0
	 */
	protected function definition(array $row): array
	{
		return [
			'guid' => strtolower(trim((string) ($row['guid'] ?? ''))),
			'id' => (int) ($row['id'] ?? 0),
			'name' => trim((string) ($row['name'] ?? '')),
			'system_name' => trim((string) ($row['system_name'] ?? '')),
			'type' => trim((string) ($row['type'] ?? '')),
			'namespace' => trim((string) ($row['namespace'] ?? '')),
			'selectable' => trim((string) ($row['namespace'] ?? '')) !== ''
		];
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

			$this->index['guid'][$guid] = $this->definition($row);
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
