<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    21st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver;


use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Power\ExtractorInterface;
use VDM\Joomla\Componentbuilder\Power\Table;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Utilities\GuidHelper;


/**
 * Reads component-to-Power references without compiling or evaluating code.
 *
 * Table relationships and the Package configs define the traversable records.
 * Compiler Power tokens and relationship selections define the Power edges.
 * A consumer is evidence of usage, never exclusive namespace ownership.
 *
 * @since  6.2.0
 */
final class References
{
	/**
	 * The raw, read-only database boundary.
	 *
	 * @var    LoadInterface
	 * @since  6.2.0
	 */
	protected LoadInterface $load;

	/**
	 * The authoritative relationship metadata.
	 *
	 * @var    Table
	 * @since  6.2.0
	 */
	protected Table $table;

	/**
	 * The compiler's token reader; only its pure get method is used.
	 *
	 * @var    ExtractorInterface
	 * @since  6.2.0
	 */
	protected ExtractorInterface $tokens;

	/**
	 * Direct child lists read from the existing Package configuration classes.
	 *
	 * @var    array<string, array>
	 * @since  6.2.0
	 */
	protected array $children;

	/**
	 * Raw query snapshots, private to one explicit run.
	 *
	 * @var    array<string, array>
	 * @since  6.2.0
	 */
	protected array $reads = [];

	/**
	 * Contexts keyed by component id, including their GUID and provenance.
	 *
	 * @var    array<int, array>
	 * @since  6.2.0
	 */
	protected array $contexts = [];

	/**
	 * Cached metadata shapes, independent of record snapshots.
	 *
	 * @var    array<string, array>
	 * @since  6.2.0
	 */
	protected array $shapes = [];

	/**
	 * Cached snapshot of the immutable per-run reference read set.
	 *
	 * @var    string|null
	 * @since  6.2.0
	 */
	protected ?string $snapshot = null;

	/**
	 * Whether every catalogue component could be identified for usage scanning.
	 *
	 * @var    bool
	 * @since  6.2.0
	 */
	protected bool $identified = true;

	/**
	 * Whether an installation-wide audit was explicitly requested this run.
	 *
	 * @var    bool
	 * @since  6.2.0
	 */
	protected bool $audited = false;

	/**
	 * Reverse edges for already observed roots, never inferred global coverage.
	 *
	 * @var    array<string, array<int, array>>
	 * @since  6.2.0
	 */
	protected array $consumers = [];

	/**
	 * Exact read descriptors and their digests, including empty result sets.
	 *
	 * @var    array<string, array>
	 * @since  6.2.0
	 */
	protected array $requests = [];

	/**
	 * Decoded records within the immutable operation snapshot.
	 *
	 * @var    array<string, array>
	 * @since  6.2.0
	 */
	protected array $decoded = [];

	/**
	 * Constructor.
	 *
	 * @param   LoadInterface       $load      The database read boundary.
	 * @param   Table               $table     The Power relationship metadata.
	 * @param   ExtractorInterface  $tokens    The pure compiler token reader.
	 * @param   array               $children  Package-owned direct child lists.
	 *
	 * @since   6.2.0
	 */
	public function __construct(LoadInterface $load, Table $table, ExtractorInterface $tokens, array $children)
	{
		$this->load = $load;
		$this->table = $table;
		$this->tokens = $tokens;
		$this->children = $children;
	}

	/**
	 * Discard record, reference and consumer snapshots at the run boundary.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function refresh(): void
	{
		$this->snapshot = null;
		$this->identified = true;
		$this->reads = [];
		$this->contexts = [];
		$this->consumers = [];
		$this->requests = [];
		$this->decoded = [];
		$this->audited = false;
	}

	/**
	 * Explicitly audit the installation; ordinary selected-root reads never call this.
	 *
	 * @return  array<int, array>  Audited contexts with reference provenance.
	 * @since   6.2.0
	 */
	public function contexts(): array
	{
		if (!$this->audited)
		{
			foreach ($this->query('joomla_component') as $component)
			{
				$id = (int) ($component['id'] ?? 0);

				if ($id < 1 || !GuidHelper::valid($component['guid'] ?? ''))
				{
					$this->identified = false;

					continue;
				}

				if (!isset($this->contexts[$id]))
				{
					$this->remember($this->walk($component));
				}
			}

			$this->audited = true;
			$this->snapshot = null;
		}

		ksort($this->contexts);

		return $this->contexts;
	}

	/**
	 * Read only the selected component and the records reachable from its root.
	 *
	 * @param   int  $component  The selected component id; zero is a new component.
	 *
	 * @return  array  The context, or an explicitly unestablished context.
	 * @since   6.2.0
	 */
	public function context(int $component): array
	{
		if (isset($this->contexts[$component]))
		{
			return $this->contexts[$component];
		}

		$rows = $component > 0 ? $this->query('joomla_component', ['a.id' => $component]) : [];

		if (count($rows) === 1 && GuidHelper::valid($rows[0]['guid'] ?? ''))
		{
			return $this->remember($this->walk($rows[0]));
		}

		return $this->remember([
			'id' => $component, 'guid' => '', 'name' => '', 'powers' => [],
			'gaps' => $component === 0 ? [] : ['component:' . $component => 'missing or invalid root'],
			'complete' => $component === 0
		]);
	}

	/**
	 * Return already observed roots without requesting additional components.
	 *
	 * @return  array<int, array>  The current selected or explicitly audited contexts.
	 * @since   6.2.0
	 */
	public function observed(): array
	{
		return $this->contexts;
	}

	/**
	 * Return known consumers in constant-time bucket access without an implicit audit.
	 *
	 * @param   string  $guid  The Power GUID.
	 *
	 * @return  array<int, array>  Observed identities and edge provenance only.
	 * @since   6.2.0
	 */
	public function consumers(string $guid): array
	{
		return $this->consumers[strtolower($guid)] ?? [];
	}

	/**
	 * Whether explicit global consumer discovery has no known omissions.
	 *
	 * A complete selected graph is not evidence of complete consumer coverage.
	 *
	 * @return  bool  True only after a complete, explicitly requested audit.
	 * @since   6.2.0
	 */
	public function complete(): bool
	{
		return $this->audited && $this->identified
			&& !in_array(false, array_column($this->contexts, 'complete'), true);
	}

	/**
	 * Fingerprint exactly the observed queries, including negative relationships.
	 *
	 * Revalidation repeats this bounded read set. A newly added dependency changes
	 * its referring record or an empty child query, so no global rebuild is needed.
	 *
	 * @param   bool  $fresh  Whether to re-read the recorded queries before hashing.
	 *
	 * @return  string  Private approval evidence, not stored record contents.
	 * @since   6.2.0
	 */
	public function fingerprint(bool $fresh = false): string
	{
		if (!$fresh && $this->snapshot !== null)
		{
			return $this->snapshot;
		}

		$digests = [];

		foreach ($this->requests as $key => $request)
		{
			$digests[$key] = $fresh
				? hash('sha256', serialize($this->read($request['entity'], $request['where'])))
				: $request['digest'];
		}

		ksort($digests);
		$digest = hash('sha256', serialize([$digests, $this->audited, $this->identified]));

		if (!$fresh)
		{
			$this->snapshot = $digest;
		}

		return $digest;
	}

	/**
	 * Retain one root and reverse-index only its actual Power edges.
	 *
	 * @param   array  $context  The resolved root context.
	 *
	 * @return  array  The retained context.
	 * @since   6.2.0
	 */
	protected function remember(array $context): array
	{
		$id = $context['id'];
		$this->contexts[$id] = $context;
		$this->snapshot = null;

		foreach ($context['powers'] as $guid => $edge)
		{
			$this->consumers[$guid][$id] = array_intersect_key($context, array_flip(['id', 'guid', 'name', 'complete'])) + $edge;
			ksort($this->consumers[$guid]);
		}

		return $context;
	}

	/**
	 * Traverse one root in zero/one Power-edge order, retaining bounded edge reasons.
	 *
	 * Zero-cost definition/child edges go to the front; Power edges go to the
	 * back. A record is expanded at its minimum Power depth, even across cycles.
	 * Incoming reasons are retained without enumerating root-to-node paths.
	 *
	 * @param   array  $component  The verified raw component record.
	 *
	 * @return  array  Reachable Powers, directness, provenance and gaps.
	 * @since   6.2.0
	 */
	protected function walk(array $component): array
	{
		$id = (int) $component['id'];
		$guid = strtolower((string) $component['guid']);
		$context = [
			'id' => $id, 'guid' => $guid,
			'name' => (string) ($component['name_code'] ?? ''),
			'powers' => [], 'gaps' => []
		];
		$queue = new \SplDoublyLinkedList();
		$queue->push(['joomla_component', $component, 0, 'component:' . $guid]);
		$visited = [];

		while (!$queue->isEmpty())
		{
			[$entity, $record, $depth, $via] = $queue->shift();
			$identity = (string) ($record['guid'] ?? $record['id'] ?? hash('sha256', serialize($record)));
			$key = $entity . ':' . $identity;

			if ($entity === 'power')
			{
				$power = strtolower($identity);
				$context['powers'][$power]['direct'] = ($context['powers'][$power]['direct'] ?? false) || $depth === 1;
				$context['powers'][$power]['via'][$via] = true;
			}

			if (isset($visited[$key]) && $visited[$key] <= $depth)
			{
				continue;
			}

			$visited[$key] = $depth;
			$record = $this->decode($entity, $record);

			foreach ($record['_reference_errors'] ?? [] as $field)
			{
				$context['gaps'][$key . '.' . $field] = 'invalid storage';
			}

			$shape = $this->shape($entity);

			foreach ($shape['parents'] as $path => $link)
			{
				$target = $link['entity'];

				// The graph never walks backwards into a different component.
				if ($target === 'joomla_component' || $target === 'joomla_power')
				{
					continue;
				}

				foreach ($this->values($record, explode('|', $path)) as $value)
				{
					$this->enqueue($queue, $context, $target, $value, $depth, $key . '.' . $path);
				}
			}

			foreach ($shape['code'] as $field)
			{
				if (!is_string($record[$field] ?? null))
				{
					continue;
				}

				foreach ((array) $this->tokens->get($record[$field]) as $power)
				{
					$this->enqueue($queue, $context, 'power', $power, $depth, $key . '.' . $field);
				}
			}

			foreach ($shape['children'] as $field => $links)
			{
				foreach ($links as $link)
				{
					$path = explode('|', $link['key']);
					$value = $record[$field] ?? null;

					if ($value === null || $value === '')
					{
						continue;
					}

					$where = count($path) === 1
						? ['a.' . $path[0] => $value]
						: $this->ownerFilter($shape['children'], $link['entity'], $record);

					if ($where === [])
					{
						$context['gaps'][$key . '->' . $link['key']] = 'unbounded relationship';

						continue;
					}

					foreach ($this->query($link['entity'], $where) as $child)
					{
						$decoded = $this->decode($link['entity'], $child);

						if (in_array((string) $value, array_map('strval', $this->values($decoded, $path)), true))
						{
							$queue->unshift([$link['entity'], $child, $depth, $key . '->' . $link['key']]);
						}
					}
				}
			}
		}

		ksort($context['powers']);
		ksort($context['gaps']);
		$context['complete'] = $context['gaps'] === [];

		return $context;
	}

	/**
	 * Constrain nested child selections to a metadata-established direct owner.
	 *
	 * Nested form values, such as tab numbers, are not installation-wide ownership
	 * keys. The direct child relationship bounds the rows before decoding them.
	 * An unsupported relationship remains a gap rather than a full table scan.
	 *
	 * @param   array   $children  The current entity's approved child relationships.
	 * @param   string  $entity    The nested child entity.
	 * @param   array   $record    The current root-owned record.
	 *
	 * @return  array  An indexed owner condition, or no established bound.
	 * @since   6.2.0
	 */
	protected function ownerFilter(array $children, string $entity, array $record): array
	{
		foreach ($children as $field => $links)
		{
			if (!isset($record[$field]) || $record[$field] === '')
			{
				continue;
			}

			foreach ($links as $link)
			{
				if ($link['entity'] === $entity && !str_contains($link['key'], '|'))
				{
					return ['a.' . $link['key'] => $record[$field]];
				}
			}
		}

		return [];
	}

	/**
	 * Add one validated forward reference and record unavailable destinations.
	 *
	 * @param   \SplDoublyLinkedList  $queue    The pending records.
	 * @param   array   $context  The component provenance.
	 * @param   string  $entity   The metadata-selected entity.
	 * @param   mixed   $value    The referenced GUID or legacy numeric id.
	 * @param   int     $depth    Number of Power edges already crossed.
	 * @param   string  $via      The referring entity and field.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function enqueue(\SplDoublyLinkedList $queue, array &$context, string $entity, $value, int $depth, string $via): void
	{
		if (!$this->table->exist($entity) || !is_scalar($value))
		{
			return;
		}

		$value = (string) $value;
		$key = GuidHelper::valid($value) ? 'guid' : (ctype_digit($value) && (int) $value > 0 ? 'id' : null);

		if ($key === null)
		{
			// Zero/empty selectors mean none, and -1 is the custom relationship
			// sentinel. Any other unresolvable Power selector leaves usage
			// unknown; silently dropping it could authorise an exclusive write.
			if ($entity === 'power' && !in_array($value, ['', '0', '-1'], true))
			{
				$context['gaps'][$via . '->power'] = 'invalid reference';
			}

			return;
		}

		$rows = $this->query($entity, ['a.' . $key => $value]);

		if (count($rows) !== 1)
		{
			$context['gaps'][$via . '->' . $entity . ':' . $value] = count($rows) === 0 ? 'missing' : 'duplicate';

			return;
		}

		if ($entity === 'power')
		{
			$queue->push([$entity, reset($rows), $depth + 1, $via]);
		}
		else
		{
			$queue->unshift([$entity, reset($rows), $depth, $via]);
		}
	}

	/**
	 * Read a metadata-bounded table through the existing loader and cache it.
	 *
	 * @param   string  $entity  The known entity.
	 * @param   array   $where   The parameterised equality conditions.
	 *
	 * @return  array<int, array>  Deterministically ordered raw records.
	 * @since   6.2.0
	 */
	protected function query(string $entity, array $where = []): array
	{
		$key = $entity . ':' . serialize($where);

		if (!array_key_exists($key, $this->reads))
		{
			$this->reads[$key] = $this->read($entity, $where);
			$this->requests[$key] = [
				'entity' => $entity, 'where' => $where,
				'digest' => hash('sha256', serialize($this->reads[$key]))
			];
			$this->snapshot = null;
		}

		return $this->reads[$key];
	}

	/**
	 * Execute one recorded read through the existing database boundary.
	 *
	 * @param   string  $entity  The metadata-approved table.
	 * @param   array   $where   Parameterised equality conditions.
	 *
	 * @return  array<int, array>  Deterministically ordered raw records.
	 * @since   6.2.0
	 */
	protected function read(string $entity, array $where): array
	{
		$rows = array_map(static fn ($row): array => (array) $row,
			(array) $this->load->items(['all' => 'a.*'], ['a' => $entity], $where ?: null));
		usort($rows, static fn (array $a, array $b): int => strcmp(serialize($a), serialize($b)));

		return $rows;
	}

	/**
	 * Cache only immutable relationship and storage metadata.
	 *
	 * @param   string  $entity  The entity name.
	 *
	 * @return  array  Parent paths, approved children, code fields and storage.
	 * @since   6.2.0
	 */
	protected function shape(string $entity): array
	{
		return $this->shapes[$entity] ??= [
			'parents' => $this->table->parents($entity),
			'children' => $this->table->children($entity, $this->children[$entity] ?? []),
			'code' => $this->table->search($entity, 'code'),
			'fields' => $this->table->get($entity) ?? []
		];
	}

	/**
	 * Decode declared storage without executing expressions or stored PHP.
	 *
	 * @param   string  $entity  The known entity.
	 * @param   array   $record  The raw record.
	 *
	 * @return  array  The decoded copy.
	 * @since   6.2.0
	 */
	protected function decode(string $entity, array $record): array
	{
		$key = $entity . ':' . ($record['id'] ?? $record['guid'] ?? hash('sha256', serialize($record)));

		if (isset($this->decoded[$key]))
		{
			return $this->decoded[$key];
		}

		foreach ($this->shape($entity)['fields'] as $name => $field)
		{
			if (!is_string($record[$name] ?? null))
			{
				continue;
			}

			if (($field['store'] ?? '') === 'base64')
			{
				$decoded = base64_decode($record[$name], true);
				if ($decoded === false)
				{
					$record['_reference_errors'][] = $name;
				}

				$record[$name] = $decoded === false ? '' : $decoded;
			}
			elseif (($field['store'] ?? '') === 'json')
			{
				$raw = $record[$name];
				$record[$name] = json_decode($raw, true);

				if ($raw !== '' && json_last_error() !== JSON_ERROR_NONE)
				{
					$record['_reference_errors'][] = $name;
				}
			}
		}

		return $this->decoded[$key] = $record;
	}

	/**
	 * Read declared nested/list fields, including numeric legacy references.
	 *
	 * @param   mixed  $value  The decoded node.
	 * @param   array  $path   The remaining metadata path.
	 *
	 * @return  array  Scalar leaf values at that path.
	 * @since   6.2.0
	 */
	protected function values($value, array $path): array
	{
		if ($path === [] && !is_array($value))
		{
			return is_scalar($value) ? [$value] : [];
		}

		if (!is_array($value))
		{
			return [];
		}

		if ($path !== [] && array_key_exists($path[0], $value))
		{
			return $this->values($value[$path[0]], array_slice($path, 1));
		}

		$result = [];

		foreach ($value as $item)
		{
			array_push($result, ...$this->values($item, $path));
		}

		return $result;
	}
}
