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
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Existing;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Harvest;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Constants;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;


/**
 * Assembles the approved harvest candidates into power definitions.
 *
 * This is where a class's relationships become identities. Every parent,
 * interface and import is resolved to the class it names under PHP's own
 * rules, then to a power guid -- first among the candidates being assembled,
 * then among the powers that already exist -- so classes link by identity the
 * way the powers engine expects. What resolves to a power is dropped from the
 * head entirely, because the compiler reintroduces those imports itself; what
 * does not stays as written, in the head, so nothing a class needs is lost.
 *
 * The two passes matter: every selected candidate claims its identity before
 * any relationship is resolved, so classes may reference each other in either
 * order and still link.
 *
 * @since 6.1.7
 */
final class Assembler
{
	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.7
	 */
	protected Config $config;

	/**
	 * The Harvest Registry.
	 *
	 * @var    Harvest
	 * @since  6.1.7
	 */
	protected Harvest $harvest;

	/**
	 * The Existing Power Resolver.
	 *
	 * @var    Existing
	 * @since  6.1.7
	 */
	protected Existing $existing;

	/**
	 * The Pairing Resolver.
	 *
	 * @var    Pairing
	 * @since  6.1.7
	 */
	protected Pairing $pairing;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.7
	 */
	protected Report $report;

	/**
	 * The Constants Resolver.
	 *
	 * @var    Constants
	 * @since  6.1.8
	 */
	protected Constants $constants;

	/**
	 * The selected candidates' identities, class name keyed to guid.
	 *
	 * @var    array<string, string>
	 * @since  6.1.7
	 */
	protected array $local = [];

	/**
	 * Constructor.
	 *
	 * @param   Config     $config     The extrusion configuration.
	 * @param   Harvest    $harvest    The harvest registry.
	 * @param   Existing   $existing   The existing power resolver.
	 * @param   Pairing    $pairing    The pairing resolver.
	 * @param   Report     $report     The run report registry.
	 * @param   Constants  $constants  The language constant resolver.
	 *
	 * @since   6.1.7
	 */
	public function __construct(
		Config $config,
		Harvest $harvest,
		Existing $existing,
		Pairing $pairing,
		Report $report,
		Constants $constants
	)
	{
		$this->config = $config;
		$this->harvest = $harvest;
		$this->existing = $existing;
		$this->pairing = $pairing;
		$this->report = $report;
		$this->constants = $constants;
	}

	/**
	 * Assemble every selected candidate into a power definition.
	 *
	 * @return  int  The number of definitions assembled.
	 * @since   6.1.7
	 */
	public function assemble(): int
	{
		// a second assembly may select differently, so nothing lingers
		$this->harvest->remove('resolved');

		$candidates = (array) $this->harvest->get('classes', []);
		$selected = [];
		$skipped = 0;
		$this->local = [];

		foreach ($candidates as $candidate)
		{
			$candidate = (array) $candidate;
			$guid = (string) ($candidate['guid'] ?? '');

			if ($guid === '')
			{
				continue;
			}

			if (!$this->selected($candidate))
			{
				$this->report->set('powers.skipped.filtered.' . $this->key($guid), true);

				continue;
			}

			// the caller's pairing verdict outranks the harvested identity
			$decided = $this->pairing->guid('power', $guid, $guid);

			if ($decided === null)
			{
				continue;
			}

			$verdict = $this->pairing->verdict('power', $guid);
			$action = (string) ($candidate['action'] ?? 'create');

			if ($verdict !== null)
			{
				$candidate['guid'] = $decided;
				$candidate['exists'] = $verdict['action'] === 'update';
				$action = (string) $verdict['action'];
			}

			// every candidate claims its identity before any linking, a dropped
			// one included: the power it stands for is still what the classes
			// beside it refer to, and they must reach it
			$this->local[strtolower((string) $candidate['fqn'])] = $decided;

			if ($action === 'skip')
			{
				// the caller asked to be told about what already exists, not to
				// have this library's copy written over it. Deciding that here
				// rather than at the write is what "just drops it" means, and
				// the key is the one every writer reports a skip under, so a
				// caller reads one thing whichever layer settled it
				$this->report->set('skipped.existing.power.' . $decided, true);
				$skipped++;

				continue;
			}

			$selected[$decided] = $candidate;
		}

		$assembled = 0;

		foreach ($selected as $guid => $candidate)
		{
			$this->harvest->set(
				'resolved.' . $guid,
				$this->definition($candidate)
			);
			$assembled++;
		}

		$this->report->set('counts.powers.assembled', $assembled);
		$this->report->set('counts.powers.skipped', $skipped);

		return $assembled;
	}

	/**
	 * Whether one candidate passes the include and exclude filters.
	 *
	 * A candidate answers to several names -- its guid, its class, its real
	 * and stored namespaces, and its file below the library -- and a caller's
	 * list may use any of them.
	 *
	 * @param   array<string, mixed>  $candidate  The harvest candidate.
	 *
	 * @return  bool  True when the candidate should be assembled.
	 * @since   6.1.7
	 */
	protected function selected(array $candidate): bool
	{
		$include = (array) $this->config->get('include', []);
		$exclude = (array) $this->config->get('exclude', []);
		$names = array_filter([
			(string) ($candidate['guid'] ?? ''),
			(string) ($candidate['class'] ?? ''),
			(string) ($candidate['fqn'] ?? ''),
			(string) ($candidate['stored'] ?? ''),
			(string) ($candidate['placeholder'] ?? ''),
			(string) ($candidate['relative'] ?? '')
		], 'strlen');

		foreach ($names as $name)
		{
			if (in_array($name, $exclude, true))
			{
				return false;
			}
		}

		return $include === [] || array_intersect($names, $include) !== [];
	}

	/**
	 * Build the definition one candidate is written as.
	 *
	 * @param   array<string, mixed>  $candidate  The harvest candidate.
	 *
	 * @return  object  The power definition, carrying its guid.
	 * @since   6.1.7
	 */
	protected function definition(array $candidate): object
	{
		$guid = (string) $candidate['guid'];
		$type = (string) $candidate['type'];
		$namespace = (string) $candidate['namespace'];
		$imports = $this->imports((array) ($candidate['uses'] ?? []), $namespace);

		$definition = new \stdClass();
		$definition->guid = $guid;
		$definition->name = (string) $candidate['class'];
		$definition->namespace = (string) $candidate['placeholder'];
		$definition->type = $type;
		// JCB stores code speaking text and lets its compiler make the
		// constant, so a class harvested out of a compiled component has to
		// speak text again -- otherwise the compiler builds a key from a key
		// and the component shows a constant to its users
		$definition->main_class_code = $this->constants->reverse(
			(string) $candidate['body']
		);
		$definition->description = $this->constants->reverse(
			(string) $candidate['docblock']
		);

		if (!(bool) ($candidate['exists'] ?? false))
		{
			$definition->system_name = $this->systemName((string) $candidate['stored']);
			$definition->power_version = '1.0.0';
			$definition->published = 1;
		}

		// every derived column is stated, empty included, so an update also
		// clears what the class no longer has instead of keeping stale links
		$definition->extends = '';
		$definition->extends_custom = '';
		$definition->implements = [];
		$definition->implements_custom = '';
		$definition->extendsinterfaces = [];
		$definition->extendsinterfaces_custom = '';
		$definition->use_selection = [];
		$definition->head = '';
		$definition->add_head = 0;
		$definition->licensing_template = '';
		$definition->add_licensing_template = 1;

		$license = (string) ($candidate['license'] ?? '');

		if ($license !== '')
		{
			$definition->licensing_template = $license;
			$definition->add_licensing_template = 2;
		}

		$this->relations($definition, $candidate, $imports);
		$this->selections($definition, $guid, $imports);

		return $definition;
	}

	/**
	 * Resolve the parent and interface relationships into identities.
	 *
	 * @param   object                $definition  The definition being built.
	 * @param   array<string, mixed>  $candidate   The harvest candidate.
	 * @param   array<string, mixed>  $imports     The resolved import table.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function relations(object $definition, array $candidate, array &$imports): void
	{
		$type = (string) $candidate['type'];
		$namespace = (string) $candidate['namespace'];
		$guid = (string) $candidate['guid'];
		$extends = (array) ($candidate['extends'] ?? []);
		$implements = (array) ($candidate['implements'] ?? []);

		if ($type === 'interface')
		{
			// an interface extends interfaces, and implements nothing
			[$linked, $custom] = $this->identities($extends, $namespace, $imports);

			if ($custom !== [])
			{
				$linked[] = '-1';
				$definition->extendsinterfaces_custom = implode(', ', $custom);
				$this->report->set('powers.custom.extendsinterfaces.' . $this->key($guid), $custom);
			}

			if ($linked !== [])
			{
				$definition->extendsinterfaces = $linked;
			}

			return;
		}

		if ($type === 'trait')
		{
			return;
		}

		if ($extends !== [])
		{
			$parent = (string) $extends[0];
			$identity = $this->identity($parent, $namespace, $imports);

			if ($identity !== null)
			{
				$definition->extends = $identity;
			}
			else
			{
				$definition->extends = '-1';
				$definition->extends_custom = $this->written($parent);
				$this->report->set('powers.custom.extends.' . $this->key($guid), $parent);

				// the column holds 64 characters, and silence would corrupt
				if (strlen($definition->extends_custom) > 64)
				{
					$this->report->set('powers.overflow.extends_custom.' . $this->key($guid), $parent);
				}
			}
		}

		if ($implements === [])
		{
			return;
		}

		[$linked, $custom] = $this->identities($implements, $namespace, $imports);

		if ($custom !== [])
		{
			$linked[] = '-1';
			$definition->implements_custom = implode(', ', $custom);
			$this->report->set('powers.custom.implements.' . $this->key($guid), $custom);
		}

		if ($linked !== [])
		{
			$definition->implements = $linked;
		}
	}

	/**
	 * Turn the import table into the use selection and the head.
	 *
	 * @param   object                $definition  The definition being built.
	 * @param   string                $guid        The candidate's identity.
	 * @param   array<string, mixed>  $imports     The resolved import table.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function selections(object $definition, string $guid, array $imports): void
	{
		$selection = [];
		$head = [];
		$number = 0;

		foreach ($imports as $import)
		{
			if ($import['claimed'])
			{
				// the compiler reintroduces this import from the relationship
				continue;
			}

			if ($import['guid'] !== null)
			{
				$selection['use_selection' . $number++] = [
					'use' => $import['guid'],
					'as' => $import['alias'] ?? 'default'
				];

				continue;
			}

			$head[] = $import['raw'];
			$this->report->set(
				'powers.unmatched.use.' . $this->key($guid) . '.' . md5($import['raw']),
				$import['name']
			);
		}

		if ($selection !== [])
		{
			$definition->use_selection = $selection;
		}

		if ($head !== [])
		{
			$definition->head = implode("\n", $head);
			$definition->add_head = 1;
		}
	}

	/**
	 * The system name one stored namespace derives.
	 *
	 * JCB's own powers speak this convention -- VDM.Data.Action.Load for the
	 * class stored as [[[NamespacePrefix]]]\Joomla\Data.Action.Load -- the
	 * vendor prefix, then the dotted tail with the class, and none of the
	 * connecting head between them.
	 *
	 * @param   string  $stored  The stored form with concrete values.
	 *
	 * @return  string  The system name.
	 * @since   6.1.9
	 */
	protected function systemName(string $stored): string
	{
		$sections = explode('\\', trim($stored, '\\'));

		if (count($sections) < 2)
		{
			return str_replace('\\', '.', $stored);
		}

		return $sections[0] . '.' . end($sections);
	}

	/**
	 * Resolve every import into the identity it refers to, when it has one.
	 *
	 * @param   array<int, mixed>  $uses       The candidate's imports as read.
	 * @param   string             $namespace  The candidate's own namespace.
	 *
	 * @return  array<string, array{raw: string, name: string, alias: string|null, guid: string|null, claimed: bool}>  The import table, keyed by bound name.
	 * @since   6.1.7
	 */
	protected function imports(array $uses, string $namespace): array
	{
		$imports = [];

		foreach ($uses as $use)
		{
			$use = (array) $use;
			$name = trim((string) ($use['name'] ?? ''), '\\');

			if ($name === '')
			{
				continue;
			}

			$alias = $use['alias'] ?? null;
			$kind = (string) ($use['kind'] ?? 'class');
			$guid = null;

			if ($kind === 'class')
			{
				$guid = $this->find($name);
			}

			// class, function and const imports live in separate symbol
			// spaces, so only a class import binds under the bare name
			$bound = $alias ?? $this->short($name);

			if ($kind !== 'class')
			{
				$bound = $kind . ' ' . $bound;
			}

			$imports[$bound] = [
				'raw' => (string) ($use['raw'] ?? ''),
				'name' => $name,
				'alias' => $alias,
				'guid' => $guid,
				'claimed' => false
			];
		}

		return $imports;
	}

	/**
	 * Resolve a list of written names into identities and leftovers.
	 *
	 * @param   array<int, mixed>     $names      The names as written.
	 * @param   string                $namespace  The referencing class's namespace.
	 * @param   array<string, mixed>  $imports    The resolved import table.
	 *
	 * @return  array{0: array<string>, 1: array<string>}  The linked identities, and the unresolved names.
	 * @since   6.1.7
	 */
	protected function identities(array $names, string $namespace, array &$imports): array
	{
		$linked = [];
		$custom = [];

		foreach ($names as $name)
		{
			$identity = $this->identity((string) $name, $namespace, $imports);

			if ($identity !== null)
			{
				$linked[] = $identity;
			}
			else
			{
				$custom[] = $this->written((string) $name);
			}
		}

		return [$linked, $custom];
	}

	/**
	 * Resolve one written name into the identity it refers to.
	 *
	 * The name resolves as PHP resolves it: a leading backslash is absolute,
	 * a first segment bound by an import continues from that import, and
	 * anything else sits in the referencing class's own namespace. An import
	 * that supplied the identity is claimed, so it is never also emitted as a
	 * use selection the compiler would then duplicate.
	 *
	 * @param   string                $name       The name as written.
	 * @param   string                $namespace  The referencing class's namespace.
	 * @param   array<string, mixed>  $imports    The resolved import table.
	 *
	 * @return  string|null  The identity, or null when no power answers to the name.
	 * @since   6.1.7
	 */
	protected function identity(string $name, string $namespace, array &$imports): ?string
	{
		$name = trim($name);

		if ($name === '')
		{
			return null;
		}

		if (str_starts_with($name, '\\'))
		{
			return $this->find(ltrim($name, '\\'));
		}

		if (str_starts_with(strtolower($name), 'namespace\\'))
		{
			return $this->find($namespace . substr($name, 9));
		}

		$segments = explode('\\', $name);
		$first = $segments[0];

		if (isset($imports[$first]))
		{
			// an aliased import stays a use selection, because the class body
			// may lean on the alias -- the power still links through the
			// selection, and the declaration keeps the alias as its custom name
			if (count($segments) === 1 && $imports[$first]['alias'] !== null)
			{
				return null;
			}

			$resolved = count($segments) === 1
				? $imports[$first]['name']
				: $imports[$first]['name'] . '\\' . implode('\\', array_slice($segments, 1));
			$guid = $this->find($resolved);

			if ($guid !== null && count($segments) === 1)
			{
				$imports[$first]['claimed'] = true;
			}

			return $guid;
		}

		return $this->find($namespace . '\\' . $name);
	}

	/**
	 * The identity one fully qualified class name resolves to.
	 *
	 * @param   string  $fqn  The fully qualified class name.
	 *
	 * @return  string|null  The identity, or null when no power answers to it.
	 * @since   6.1.7
	 */
	protected function find(string $fqn): ?string
	{
		$key = strtolower(trim($fqn, '\\'));

		if (isset($this->local[$key]))
		{
			return $this->local[$key];
		}

		return $this->existing->find($fqn)['guid'] ?? null;
	}

	/**
	 * The short class name of one written name.
	 *
	 * @param   string  $name  The name as written.
	 *
	 * @return  string  The final segment.
	 * @since   6.1.7
	 */
	protected function short(string $name): string
	{
		$segments = explode('\\', trim($name, '\\'));

		return (string) end($segments);
	}

	/**
	 * The custom name an unresolved reference is stored under.
	 *
	 * A qualified name stays exactly as written, because the compiler emits
	 * the custom name verbatim into the declaration -- truncating
	 * \Exception to Exception would make the built class extend a class in
	 * its own namespace instead.
	 *
	 * @param   string  $name  The name as written.
	 *
	 * @return  string  The name to store.
	 * @since   6.1.7
	 */
	protected function written(string $name): string
	{
		return trim($name);
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
