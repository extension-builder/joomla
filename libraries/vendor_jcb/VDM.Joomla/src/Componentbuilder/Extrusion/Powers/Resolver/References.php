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
	 * @var    array<int, array>|null
	 * @since  6.2.0
	 */
	protected ?array $contexts = null;

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
		$this->contexts = null;
	}

	/**
	 * Read the component catalogue with complete reference provenance.
	 *
	 * @return  array<int, array>  Contexts with direct/transitive usage and gaps.
	 * @since   6.2.0
	 */
	public function contexts(): array
	{
		if ($this->contexts !== null)
		{
			return $this->contexts;
		}

		$this->contexts = [];

		foreach ($this->query('joomla_component') as $component)
		{
			$id = (int) ($component['id'] ?? 0);
			$guid = strtolower((string) ($component['guid'] ?? ''));

			if ($id < 1 || !GuidHelper::valid($guid))
			{
				$this->identified = false;

				continue;
			}

			$context = [
				'id' => $id, 'guid' => $guid,
				'name' => (string) ($component['name_code'] ?? ''),
				'powers' => [], 'gaps' => []
			];
			$queue = [['joomla_component', $component, 0, 'component:' . $guid]];
			$visited = [];

			for ($cursor = 0; $cursor < count($queue); $cursor++)
			{
				[$entity, $record, $depth, $via] = $queue[$cursor];
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

						$where = count($path) === 1 ? ['a.' . $path[0] => $value] : [];

						foreach ($this->query($link['entity'], $where) as $child)
						{
							$decoded = $this->decode($link['entity'], $child);

							if (in_array((string) $value, array_map('strval', $this->values($decoded, $path)), true))
							{
								$queue[] = [$link['entity'], $child, $depth, $key . '->' . $link['key']];
							}
						}
					}
				}
			}

			ksort($context['powers']);
			ksort($context['gaps']);
			$context['complete'] = $context['gaps'] === [];
			$this->contexts[$id] = $context;
		}

		ksort($this->contexts);

		return $this->contexts;
	}

	/**
	 * Read one context without treating missing information as ownership proof.
	 *
	 * @param   int  $component  The selected component id.
	 *
	 * @return  array  The context, or an explicitly unestablished context.
	 * @since   6.2.0
	 */
	public function context(int $component): array
	{
		return $this->contexts()[$component] ?? [
			'id' => $component, 'guid' => '', 'name' => '', 'powers' => [],
			'gaps' => [], 'complete' => $component === 0
		];
	}

	/**
	 * Return known consumers without asserting exclusive ownership.
	 *
	 * @param   string  $guid  The Power GUID.
	 *
	 * @return  array<int, array>  Public component identities and edge provenance.
	 * @since   6.2.0
	 */
	public function consumers(string $guid): array
	{
		$consumers = [];

		foreach ($this->contexts() as $id => $context)
		{
			if (isset($context['powers'][strtolower($guid)]))
			{
				$consumers[$id] = array_intersect_key($context, array_flip(['id', 'guid', 'name', 'complete']))
					+ $context['powers'][strtolower($guid)];
			}
		}

		return $consumers;
	}

	/**
	 * Whether consumer discovery has no known omissions across all components.
	 *
	 * @return  bool  True only when all observed reference contexts are complete.
	 * @since   6.2.0
	 */
	public function complete(): bool
	{
		$contexts = $this->contexts();

		return $this->identified && !in_array(false, array_column($contexts, 'complete'), true);
	}

	/**
	 * Fingerprint the exact read set, including empty relationship queries.
	 *
	 * @return  string  The private approval fingerprint, not record contents.
	 * @since   6.2.0
	 */
	public function fingerprint(): string
	{
		if ($this->snapshot !== null)
		{
			return $this->snapshot;
		}

		$this->contexts();
		$reads = $this->reads;
		ksort($reads);

		return $this->snapshot ??= hash('sha256', serialize($reads));
	}

	/**
	 * Add one validated forward reference and record unavailable destinations.
	 *
	 * @param   array   $queue    The pending records.
	 * @param   array   $context  The component provenance.
	 * @param   string  $entity   The metadata-selected entity.
	 * @param   mixed   $value    The referenced GUID or legacy numeric id.
	 * @param   int     $depth    Number of Power edges already crossed.
	 * @param   string  $via      The referring entity and field.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function enqueue(array &$queue, array &$context, string $entity, $value, int $depth, string $via): void
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

		$queue[] = [$entity, reset($rows), $depth + ($entity === 'power' ? 1 : 0), $via];
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
			$rows = array_map(static fn ($row): array => (array) $row,
				(array) $this->load->items(['all' => 'a.*'], ['a' => $entity], $where ?: null));
			usort($rows, static fn (array $a, array $b): int => strcmp(serialize($a), serialize($b)));
			$this->reads[$key] = $rows;
		}

		return $this->reads[$key];
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

		return $record;
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
