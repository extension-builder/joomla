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


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;


/**
 * One source-to-Power decision for harvest, relationships, preview and writes.
 *
 * Lookup candidates are gathered before any decision. Usage can disambiguate a
 * definition, but never turns all of its namespace words into component roles.
 *
 * @since  6.2.0
 */
final class Identity
{
	/**
	 * The run configuration.
	 *
	 * @var    Config
	 * @since  6.2.0
	 */
	protected Config $config;

	/**
	 * The complete GUID catalogue.
	 *
	 * @var    Existing
	 * @since  6.2.0
	 */
	protected Existing $existing;

	/**
	 * The namespace and placement validator.
	 *
	 * @var    Namespacer
	 * @since  6.2.0
	 */
	protected Namespacer $names;

	/**
	 * Read-only component reference evidence.
	 *
	 * @var    References
	 * @since  6.2.0
	 */
	protected References $references;

	/**
	 * Stable creation identity derivation.
	 *
	 * @var    Guid
	 * @since  6.2.0
	 */
	protected Guid $guid;

	/**
	 * Applicable placeholder maps, private to this run and never sent to the UI.
	 *
	 * @var    array<int, array>
	 * @since  6.2.0
	 */
	protected array $contexts = [];

	/**
	 * Constructor.
	 *
	 * @param   Config      $config      The run configuration.
	 * @param   Existing    $existing    The complete GUID catalogue.
	 * @param   Namespacer  $names       The namespace and placement validator.
	 * @param   References  $references  The reference graph.
	 * @param   Guid        $guid        The stable identity derivation service.
	 *
	 * @since   6.2.0
	 */
	public function __construct(Config $config, Existing $existing, Namespacer $names, References $references, Guid $guid)
	{
		$this->config = $config;
		$this->existing = $existing;
		$this->names = $names;
		$this->references = $references;
		$this->guid = $guid;
	}

	/**
	 * Start an explicit fresh record, context and namespace-evidence snapshot.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function refresh(): void
	{
		$this->contexts = [];
		$this->names->forget();
		$this->existing->refresh();
		$this->references->refresh();
	}

	/**
	 * Resolve all available identity evidence without a first-match fallback.
	 *
	 * @param   array       $source     Stable source identity and raw observations.
	 * @param   array|null  $decision   An explicit, source-keyed pairing verdict.
	 * @param   bool        $reference  Resolve a dependency without granting write scope.
	 *
	 * @return  array  The common resolution contract, including any blockers.
	 * @since   6.2.0
	 */
	public function resolve(array $source, ?array $decision = null, bool $reference = false): array
	{
		$target = $this->names->context();
		$sourceId = (int) ($source['source_component_id'] ?? $this->config->get('sourceComponent', $target['id']));
		$sourceContext = $sourceId === $target['id'] ? $target : $this->context($sourceId);
		$usage = $this->references->context((int) $target['id']);
		$result = [
			'source_key' => (string) ($source['source_key'] ?? ''),
			'source_unit' => (string) ($source['source_unit'] ?? ''),
			'status' => 'unresolved', 'matched_guid' => null, 'write_guid' => null,
			'source_component' => array_intersect_key($sourceContext, array_flip(['id', 'guid', 'code'])),
			'target_component' => array_intersect_key($target, array_flip(['id', 'guid', 'code'])),
			'candidates' => [], 'namespace' => null, 'target' => null,
			'write_eligibility' => 'blocked', 'write_scope' => 'unestablished',
			'dependencies' => [], 'blockers' => [], 'explicit' => $decision !== null,
			'context_fingerprint' => hash('sha256', serialize([$sourceContext, $target, $this->references->fingerprint()]))
		];

		if (($decision['action'] ?? '') === 'ignore')
		{
			$result['status'] = 'ignored';

			return $result;
		}

		if ($result['source_key'] === '' || empty($source['fqn']) || empty($source['stored'])
			|| ($target['id'] > 0 && $target['guid'] === '')
			|| ($sourceId > 0 && $sourceContext['guid'] === ''))
		{
			$result['blockers'][] = 'The source identity or component context is not established.';

			return $result;
		}

		if (!empty($source['source_error']))
		{
			return $this->block($result, 'conflict', (string) $source['source_error']);
		}

		$derived = $this->guid->derive([
			'power', 'scoped-source-v2', $target['guid'] ?: (string) $this->config->get('targetComponentGuid', $result['source_unit']), $result['source_key']
		]);
		$supplied = strtolower((string) ($source['source_guid'] ?? ''));
		$explicit = strtolower((string) ($decision['target'] ?? ''));
		$compatible = [];

		foreach ($this->existing->records() as $guid => $record)
		{
			$evidence = $this->candidate($source, $record, $sourceContext, $usage);

			if (!$evidence['plausible'] && !in_array($guid, [$supplied, $explicit, $derived], true))
			{
				continue;
			}

			$result['candidates'][$guid] = $evidence;

			if ($evidence['compatible'] && $record['selectable'])
			{
				$compatible[$guid] = $evidence;
			}
		}

		$scoped = array_filter($compatible, static fn (array $entry): bool => $entry['in_target']);
		$literal = array_filter($compatible, static fn (array $entry): bool => $entry['literal']);
		$chosen = null;
		$reason = '';
		$action = $decision['action'] ?? '';

		if ($action === 'update')
		{
			if (!isset($compatible[$explicit]))
			{
				return $this->block($result, 'conflict', 'The explicitly selected target is missing or structurally incompatible.');
			}

			$chosen = $explicit;
			$reason = 'explicit-pairing';
		}
		elseif ($supplied !== '' && ($this->existing->power($supplied) !== null || $result['candidates'] !== [] || !$this->guid->valid($supplied)))
		{
			if (!$this->guid->valid($supplied) || !isset($compatible[$supplied])
				|| ($scoped !== [] && !isset($scoped[$supplied])))
			{
				return $this->block($result, 'conflict', 'Source GUID evidence conflicts with the source structure or component references.');
			}

			$chosen = $supplied;
			$reason = 'validated-source-guid';
		}
		elseif ($action === 'create')
		{
			if (isset($compatible[$derived]))
			{
				$chosen = $derived;
				$reason = 'idempotent-explicit-create';
			}
			elseif ($this->existing->power($derived) !== null)
			{
				return $this->block($result, 'conflict', 'The stable creation GUID is already used by an incompatible definition.');
			}
		}
		elseif (isset($compatible[$derived]) && ($scoped === [] || isset($scoped[$derived])))
		{
			$chosen = $derived;
			$reason = 'stable-source-identity';
		}
		elseif (count($scoped) === 1)
		{
			$chosen = (string) array_key_first($scoped);
			$reason = 'component-reference';
		}
		elseif (count($scoped) > 1)
		{
			return $this->block($result, 'ambiguous', 'More than one source-compatible Power is referenced by the selected component.');
		}
		elseif (count($literal) === 1 && count($compatible) === 1)
		{
			$chosen = (string) array_key_first($literal);
			$reason = 'literal-source-identity';
		}
		elseif ($reference && count($compatible) === 1 && reset($compatible)['concrete'])
		{
			$chosen = (string) array_key_first($compatible);
			$reason = 'concrete-reference';
		}
		elseif ($result['candidates'] !== [])
		{
			return $this->block($result, $compatible === [] ? 'conflict' : 'ambiguous', 'Existing candidates have no unique, compatible source-and-component identity.');
		}

		if ($chosen === null)
		{
			if ($reference)
			{
				$result['status'] = 'external';

				return $result;
			}

			$namespace = $this->names->proposal($source, null, $sourceContext);

			if (isset($source['binding']))
			{
				$bound = $this->names->bind($source, (array) $source['binding'], $sourceContext);

				if ($bound === null)
				{
					return $this->block($result, 'conflict', 'The supplied source-root binding does not reconstruct this declaration.');
				}

				$namespace = $this->names->proposal($source, $this->names->express($bound, $sourceContext), $sourceContext);
				$namespace['preserved'] = false;
				$namespace['provenance'] = $source['binding']['provenance'] ?? 'explicit-source-root';
			}

			$result['namespace'] = $namespace;

			if (!$namespace['round_trip'])
			{
				return $this->block($result, 'unresolved', 'The source namespace or file placement cannot be safely reconstructed.');
			}

			$result['status'] = 'new';
			$result['write_guid'] = $supplied !== '' && $this->guid->valid($supplied) ? $supplied : $derived;
			$result['write_eligibility'] = 'automatic';
			$result['write_scope'] = 'new';

			return $this->remapping($result, $sourceId, $target['id']);
		}

		$record = $this->existing->power($chosen);
		$entry = $compatible[$chosen];
		$result['status'] = 'matched';
		$result['matched_guid'] = $chosen;
		$result['write_guid'] = $chosen;
		$result['target'] = $record;
		$result['reason'] = $reason;
		$result['consumers'] = $entry['consumers'];
		$result['write_scope'] = $entry['scope'];
		$result['write_eligibility'] = $entry['scope'] === 'component' ? 'automatic' : 'approval';
		$result['namespace'] = $this->names->proposal($source, $record['namespace'], $sourceContext);

		if ($reference)
		{
			// A dependency lookup cannot authorise mutation, auxiliary writes or
			// namespace relocation on the referenced definition.
			$result['write_guid'] = null;
			$result['write_eligibility'] = 'reference-only';

			return $result;
		}

		if (!$result['namespace']['round_trip'])
		{
			return $this->block($result, 'conflict', 'The identified definition does not reconstruct the source namespace and placement in its applicable context.');
		}

		if ($entry['scope'] === 'foreign' && $action !== 'update')
		{
			return $this->block($result, 'conflict', 'An automatic write to a foreign component-specific definition is not permitted.');
		}

		return $this->remapping($result, $sourceId, $target['id']);
	}

	/**
	 * Return source context without publishing its arbitrary placeholder values.
	 *
	 * @param   array  $source  The source descriptor.
	 *
	 * @return  array  The private source placeholder context.
	 * @since   6.2.0
	 */
	public function sourceContext(array $source): array
	{
		$id = (int) ($source['source_component_id'] ?? $this->config->get('sourceComponent', $this->config->get('component', 0)));

		return $id === (int) $this->config->get('component', 0) ? $this->names->context() : $this->context($id);
	}

	/**
	 * Read the current reference fingerprint for plan revalidation.
	 *
	 * @return  string  The read-set fingerprint.
	 * @since   6.2.0
	 */
	public function fingerprint(): string
	{
		return hash('sha256', $this->names->signature() . $this->references->fingerprint() . serialize($this->existing->records()));
	}

	/**
	 * Evaluate a record in its own applicable custom-placeholder contexts.
	 *
	 * @param   array  $source   The source descriptor.
	 * @param   array  $record   One existing Power.
	 * @param   array  $context  Verified source component context.
	 * @param   array  $usage    Selected component usage.
	 *
	 * @return  array  Bounded candidate evidence, not stored code or placeholder maps.
	 * @since   6.2.0
	 */
	protected function candidate(array $source, array $record, array $context, array $usage): array
	{
		$consumers = $this->references->consumers($record['guid']);
		$inTarget = isset($usage['powers'][$record['guid']]);
		$scopes = $consumers === [] ? [$context] : [];

		foreach ($consumers as $id => $consumer)
		{
			$scopes[] = $this->context($id);
		}

		$compatible = false;
		$literal = false;
		$concrete = false;
		$generic = false;
		$kind = static fn (string $type): string => str_ends_with($type, 'class') ? 'class' : $type;
		$type = (string) ($source['type'] ?? '');
		$kindFits = $type === '' || $record['type'] === '' || $kind($type) === $kind($record['type']);

		foreach ($scopes as $scope)
		{
			// Expand a component's custom aliases there, then evaluate the
			// remaining reusable core template against the source. Never read
			// A's locally named alias as though B defined it.
			$canonical = $this->names->canonical($record['namespace'], $scope);
			$isGeneric = $this->names->componentVariable($canonical, $scope);
			$sourceMap = $this->names->sourceContext($source['stored'], $context);
			$fits = $this->names->key($this->names->resolve($canonical, $sourceMap)) === $this->names->key($source['fqn']);
			$actual = $this->names->key($this->names->resolve($record['namespace'], $scope)) === $this->names->key($source['fqn']);
			$generic = $generic || $isGeneric;
			$concrete = $concrete || $actual;
			$compatible = $compatible || $fits || $actual;
			$literal = $literal || (!$isGeneric && $fits);
		}

		$scope = $inTarget && count($consumers) === 1 && $usage['complete'] && $this->references->complete() ? 'component'
			: (count($consumers) > 1 || ($literal && $consumers !== []) ? 'shared'
				: (!$inTarget && $generic && $consumers !== [] ? 'foreign' : 'unestablished'));

		return [
			'guid' => $record['guid'], 'id' => $record['id'],
			'system_name' => $record['system_name'], 'namespace' => $record['namespace'], 'type' => $record['type'],
			'plausible' => $compatible,
			'compatible' => $kindFits && $compatible && $record['selectable'],
			'in_target' => $inTarget, 'literal' => $literal, 'concrete' => $concrete,
			'generic' => $generic, 'scope' => $scope, 'consumers' => $consumers,
			'provenance' => $usage['powers'][$record['guid']] ?? [],
			'reason' => !$kindFits ? 'declaration-kind-conflict'
				: ($inTarget ? 'referenced-by-target' : ($consumers !== [] ? 'other-known-consumers' : 'usage-not-established'))
		];
	}

	/**
	 * Read a private, per-component namespace map once in this run.
	 *
	 * @param   int  $component  The component id.
	 *
	 * @return  array  The independent context.
	 * @since   6.2.0
	 */
	protected function context(int $component): array
	{
		return $this->contexts[$component] ??= $this->names->context($component);
	}

	/**
	 * Reject silent source-to-target component remapping.
	 *
	 * @param   array  $result  The resolved result.
	 * @param   int    $source  The source component id.
	 * @param   int    $target  The target component id.
	 *
	 * @return  array  The result with remapping approval requirements.
	 * @since   6.2.0
	 */
	protected function remapping(array $result, int $source, int $target): array
	{
		if ($source !== $target && $source > 0 && $target > 0)
		{
			$result['remapping'] = true;
			$result['write_eligibility'] = 'approval';
		}

		return $result;
	}

	/**
	 * Represent an unresolved decision without fabricating a write target.
	 *
	 * @param   array   $result  The collected evidence.
	 * @param   string  $status  The blocked status.
	 * @param   string  $reason  The bounded diagnostic reason.
	 *
	 * @return  array  The blocked result.
	 * @since   6.2.0
	 */
	protected function block(array $result, string $status, string $reason): array
	{
		$result['status'] = $status;
		$result['write_guid'] = null;
		$result['write_eligibility'] = 'blocked';
		$result['blockers'][] = $reason;

		return $result;
	}
}
