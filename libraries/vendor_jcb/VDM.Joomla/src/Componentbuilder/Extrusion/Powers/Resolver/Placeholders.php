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


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Utilities\String\NamespaceHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Resolves the namespace placeholder values one run works against.
 *
 * A power's stored namespace defers its vendor prefix and component segment to
 * placeholders, so recognising a harvested class as an existing power means
 * knowing what those placeholders resolve to right now. The values come from
 * exactly the places the compiler takes them: the component row when one was
 * named (its own prefix only when add_namespace_prefix allows it), the global
 * configuration otherwise, and the component placeholder overrides last, so
 * they outrank both -- mirroring Compiler\Component\Placeholder.
 *
 * @since 6.1.7
 */
final class Placeholders
{
	/**
	 * The placeholder that defers the vendor prefix.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	public const PREFIX = '[[[NamespacePrefix]]]';

	/**
	 * The placeholder that defers the component segment.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	public const COMPONENT = '[[[ComponentNamespace]]]';

	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.7
	 */
	protected Config $config;

	/**
	 * The Database Loader.
	 *
	 * @var    LoadInterface
	 * @since  6.1.7
	 */
	protected LoadInterface $load;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.7
	 */
	protected Report $report;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.8
	 */
	protected Source $source;

	/**
	 * The prefix to fall back on when nothing else names one.
	 *
	 * @var    string|null
	 * @since  6.1.7
	 */
	protected ?string $fallback;

	/**
	 * The resolved values, cached per component the run speaks for.
	 *
	 * @var    array<string, array{prefix: string, component: string, recognise: array<string>}>
	 * @since  6.1.7
	 */
	protected array $resolved = [];

	/**
	 * The vendor prefix and component segment the harvested classes witnessed.
	 *
	 * @var    array<string, array{prefix: string, component: string, count: int}>
	 * @since  6.1.9
	 */
	protected array $witnessed = [];

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   LoadInterface  $load      The database loader.
	 * @param   Report         $report    The run report registry.
	 * @param   Source         $source    The source identity registry.
	 * @param   string|null    $fallback  The prefix to use when none is configured.
	 *
	 * @since   6.1.7
	 */
	public function __construct(
		Config $config,
		LoadInterface $load,
		Report $report,
		Source $source,
		?string $fallback = null
	)
	{
		$this->config = $config;
		$this->load = $load;
		$this->report = $report;
		$this->source = $source;
		$this->fallback = $fallback;
	}

	/**
	 * The vendor prefix the run's placeholders resolve to.
	 *
	 * @return  string  The namespace prefix value.
	 * @since   6.1.7
	 */
	public function prefix(): string
	{
		return $this->values()['prefix'];
	}

	/**
	 * The component segment the run's placeholders resolve to.
	 *
	 * @return  string  The component namespace value, or an empty string.
	 * @since   6.1.7
	 */
	public function component(): string
	{
		return $this->values()['component'];
	}

	/**
	 * Whether one namespace segment answers to a component this run knows.
	 *
	 * A namespace is case-insensitive to PHP, and the segment's identity is
	 * the word, not its casing -- SermonDistributor and Sermondistributor are
	 * one component area. The set answered against holds every component
	 * namespace the run can know: the component being extruded, the component
	 * being paired against, and what either one's overrides resolve to.
	 *
	 * @param   string  $segment  The namespace segment.
	 *
	 * @return  bool  True when the segment is a known component namespace.
	 * @since   6.1.9
	 */
	public function answers(string $segment): bool
	{
		$segment = strtolower(trim($segment));

		return $segment !== ''
			&& in_array($segment, $this->values()['recognise'], true);
	}

	/**
	 * Witness the concrete values one component-owned class actually carries.
	 *
	 * The stored namespace defers these to placeholders; compiling the
	 * component again must resolve them back to the very values the library
	 * was built with, or every class lands in a different folder. What the
	 * classes witnessed is what a run can record onto the component.
	 *
	 * @param   string  $prefix     The vendor prefix the class carried.
	 * @param   string  $component  The component segment the class carried.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function witness(string $prefix, string $component): void
	{
		$prefix = trim($prefix);
		$component = trim($component);
		$key = $prefix . '|' . $component;

		$this->witnessed[$key] ??= [
			'prefix' => $prefix,
			'component' => $component,
			'count' => 0
		];
		$this->witnessed[$key]['count']++;
	}

	/**
	 * What the harvested classes witnessed, most spoken-for first.
	 *
	 * @return  array<int, array{prefix: string, component: string, count: int}>  The witnessed pairs.
	 * @since   6.1.9
	 */
	public function witnessed(): array
	{
		$witnessed = array_values($this->witnessed);

		usort(
			$witnessed,
			static fn (array $one, array $two): int => $two['count'] <=> $one['count']
		);

		return $witnessed;
	}

	/**
	 * Drop everything witnessed and resolved, so a fresh run reads fresh.
	 *
	 * The resolved cache goes with the witnesses, because a run that has just
	 * recorded an override onto the component has changed what these very
	 * placeholders resolve to.
	 *
	 * @return  void
	 * @since   6.1.9
	 */
	public function forget(): void
	{
		$this->witnessed = [];
		$this->resolved = [];
	}

	/**
	 * Every placeholder and the value it resolves to.
	 *
	 * @return  array<string, string>  Placeholder keyed to its value.
	 * @since   6.1.7
	 */
	public function map(): array
	{
		$values = $this->values();
		$map = [self::PREFIX => $values['prefix']];

		if ($values['component'] !== '')
		{
			$map[self::COMPONENT] = $values['component'];
		}

		return $map;
	}

	/**
	 * Resolve the values for the configured component, once.
	 *
	 * @return  array{prefix: string, component: string}  The resolved values.
	 * @since   6.1.7
	 */
	protected function values(): array
	{
		$id = (int) $this->config->get('component', 0);
		$named = trim((string) $this->config->get('componentCode', ''));

		if ($named === '')
		{
			// a run harvesting a component and its library together has already
			// discovered what that component is called, and the library's
			// component-owned classes are that component's -- so the two halves
			// of one run resolve the same segment without being told twice.
			// The source names it as Joomla does, com_ and all; the segment is
			// derived from the code name alone, exactly as the compiler does
			$named = (string) preg_replace(
				'/^com_/i',
				'',
				trim((string) $this->source->get('code_name', ''))
			);
		}

		$key = $id . '|' . $named;

		if (isset($this->resolved[$key]))
		{
			// a fresh run reads a fresh report, which must still carry the
			// values that drive every namespace conversion
			$this->report->set('powers.placeholders', $this->resolved[$key]);

			return $this->resolved[$key];
		}

		$prefix = '';
		$component = '';
		$guid = '';
		$code = '';

		if ($id > 0)
		{
			$row = $this->load->item(
				[
					'a.guid' => 'guid',
					'a.name_code' => 'name_code',
					'a.add_namespace_prefix' => 'add_namespace_prefix',
					'a.namespace_prefix' => 'namespace_prefix'
				],
				['a' => 'joomla_component'],
				['a.id' => $id]
			);

			if ($row !== null)
			{
				$guid = trim((string) ($row->guid ?? ''));
				$code = $this->code((string) ($row->name_code ?? ''));
				$component = $this->segment($code);

				if ((int) ($row->add_namespace_prefix ?? 0) === 1)
				{
					$prefix = trim((string) ($row->namespace_prefix ?? ''));
				}
			}
			else
			{
				$this->report->set('powers.failed.component', $id);
			}
		}

		if ($code === '' && $named !== '')
		{
			// a run harvesting a library for a component it is about to create
			// has no row to ask, but it does know what the component is called,
			// and that is the same thing the compiler derives the segment from
			$code = $this->code($named);
			$component = $this->segment($code);
		}

		if ($prefix === '')
		{
			$prefix = $this->fallbackPrefix();
		}

		$derived = $component;
		[$prefix, $component] = $this->override($guid, $prefix, $component, $code);

		// a built class only ever carries the namespace-safe form of these
		// values, so that form is the one every comparison runs against
		$prefix = NamespaceHelper::safe($prefix);
		$component = $component === ''
			? ''
			: NamespaceHelper::safeSegment($component);

		// every component namespace this run can know answers for a harvested
		// segment: the paired component's own value, the value it derives
		// without its overrides, the component the source itself names --
		// and every component JCB already holds, because a library harvested
		// on its own still belongs to a component the system knows by name
		$recognise = [];

		foreach (array_merge(
			[$component, $derived, $this->segment($this->code($named))],
			$this->catalogue()
		) as $known)
		{
			$known = strtolower(trim((string) $known));

			if ($known !== '' && !in_array($known, $recognise, true))
			{
				$recognise[] = $known;
			}
		}

		$this->report->set('powers.placeholders', [
			'prefix' => $prefix,
			'component' => $component,
			'recognise' => $recognise
		]);

		return $this->resolved[$key] = [
			'prefix' => $prefix,
			'component' => $component,
			'recognise' => $recognise
		];
	}

	/**
	 * Every component namespace the whole system knows.
	 *
	 * Each component's code name derives its segment the way the compiler
	 * does, and each ComponentNamespace override -- base64 encoded, exactly
	 * as the compiler decodes it -- states the value a person chose instead.
	 * Together they are every value the placeholder has ever resolved to on
	 * this system, which is what lets a library harvested on its own still
	 * recognise the component area its classes carry.
	 *
	 * @return  array<string>  The known component namespace values.
	 * @since   6.1.9
	 */
	protected function catalogue(): array
	{
		$known = [];
		$rows = $this->load->items(
			['a.name_code' => 'name_code'],
			['a' => 'joomla_component']
		);

		foreach ((array) $rows as $row)
		{
			$known[] = $this->segment($this->code(
				(string) (((array) $row)['name_code'] ?? '')
			));
		}

		$overrides = $this->load->values(
			['a.addplaceholders' => 'addplaceholders'],
			['a' => 'component_placeholders']
		);

		foreach ((array) $overrides as $stored)
		{
			if (!is_string($stored) || trim($stored) === '')
			{
				continue;
			}

			$rows = json_decode($stored, true);

			if (!is_array($rows))
			{
				continue;
			}

			foreach ($rows as $row)
			{
				$row = (array) $row;

				if ($this->target((string) ($row['target'] ?? '')) !== 'ComponentNamespace')
				{
					continue;
				}

				$value = trim(base64_decode((string) ($row['value'] ?? '')));

				if ($value !== '')
				{
					$known[] = NamespaceHelper::safeSegment($value);
				}
			}
		}

		$globals = $this->load->items(
			['a.target' => 'target', 'a.value' => 'value'],
			['a' => 'placeholder']
		);

		foreach ((array) $globals as $row)
		{
			$row = (array) $row;

			if ($this->target((string) ($row['target'] ?? '')) !== 'ComponentNamespace')
			{
				continue;
			}

			$value = trim(base64_decode((string) ($row['value'] ?? '')));

			if ($value !== '')
			{
				$known[] = NamespaceHelper::safeSegment($value);
			}
		}

		return array_values(array_filter($known, 'strlen'));
	}

	/**
	 * Apply the component's own placeholder overrides, which outrank the rest.
	 *
	 * An override value may itself lean on the core placeholders -- the
	 * compiler substitutes what it already knows into every override before
	 * using it, so the same substitution happens here.
	 *
	 * @param   string  $guid       The component identity, or an empty string.
	 * @param   string  $prefix     The resolved prefix so far.
	 * @param   string  $component  The resolved component segment so far.
	 * @param   string  $code       The component's safe code name.
	 *
	 * @return  array{0: string, 1: string}  The prefix and component after overrides.
	 * @since   6.1.7
	 */
	protected function override(string $guid, string $prefix, string $component, string $code): array
	{
		if ($guid === '')
		{
			return [$prefix, $component];
		}

		$stored = $this->load->value(
			['a.addplaceholders' => 'addplaceholders'],
			['a' => 'component_placeholders'],
			['a.joomla_component' => $guid]
		);

		if (!is_string($stored) || trim($stored) === '')
		{
			return [$prefix, $component];
		}

		$rows = json_decode($stored, true);

		if (!is_array($rows))
		{
			return [$prefix, $component];
		}

		$known = $this->known($code, $prefix, $component);

		foreach ($rows as $row)
		{
			$row = (array) $row;
			$target = $this->target((string) ($row['target'] ?? ''));
			// an override value travels base64 encoded, exactly as the
			// compiler's applyComponentOverrides decodes it before use
			$value = trim(str_replace(
				array_keys($known),
				array_values($known),
				base64_decode((string) ($row['value'] ?? ''))
			));

			if ($value === '')
			{
				continue;
			}

			if ($target === 'NamespacePrefix')
			{
				$prefix = $value;
			}
			elseif ($target === 'ComponentNamespace')
			{
				$component = $value;
			}
		}

		return [$prefix, $component];
	}

	/**
	 * The core placeholders an override value may lean on.
	 *
	 * @param   string  $code       The component's safe code name.
	 * @param   string  $prefix     The resolved prefix so far.
	 * @param   string  $component  The resolved component segment so far.
	 *
	 * @return  array<string, string>  Placeholder keyed to its value, both wrapper forms.
	 * @since   6.1.7
	 */
	protected function known(string $code, string $prefix, string $component): array
	{
		$values = [
			'component' => $code,
			'Component' => ucfirst($code),
			'COMPONENT' => strtoupper($code),
			'ComponentNamespace' => $component,
			'NamespacePrefix' => $prefix,
			'NAMESPACEPREFIX' => $prefix
		];
		$known = [];

		foreach ($values as $target => $value)
		{
			$known['[[[' . $target . ']]]'] = $value;
			$known['###' . $target . '###'] = $value;
		}

		return $known;
	}

	/**
	 * The prefix the global configuration falls back on.
	 *
	 * The global value is read from the extension parameters through the same
	 * database boundary everything else in this engine uses, because the
	 * static parameter helpers require a running application this engine
	 * never assumes.
	 *
	 * @return  string  The configured prefix, or the platform default.
	 * @since   6.1.7
	 */
	protected function fallbackPrefix(): string
	{
		if ($this->fallback !== null && trim($this->fallback) !== '')
		{
			return trim($this->fallback);
		}

		$params = $this->load->value(
			['a.params' => 'params'],
			['a' => '#__extensions'],
			['a.element' => 'com_componentbuilder', 'a.type' => 'component']
		);

		if (is_string($params) && trim($params) !== '')
		{
			$params = json_decode($params, true);

			if (is_array($params))
			{
				$prefix = trim((string) ($params['namespace_prefix'] ?? ''));

				if ($prefix !== '')
				{
					return $prefix;
				}
			}
		}

		return 'JCB';
	}

	/**
	 * The safe code name a raw component code name derives.
	 *
	 * This replicates the compiler's safe lower name -- number words, the
	 * stripped characters, the underscored spaces -- without the
	 * transliteration step, because that step needs a running application
	 * and a code name is plain ASCII by its own convention.
	 *
	 * @param   string  $codeName  The component's raw code name.
	 *
	 * @return  string  The safe lower code name.
	 * @since   6.1.7
	 */
	protected function code(string $codeName): string
	{
		if (trim($codeName) === '')
		{
			return '';
		}

		$code = trim((string) StringHelper::numbers($codeName));
		$code = (string) preg_replace('/_+/', ' ', $code);
		$code = (string) preg_replace('/\s+/', ' ', $code);
		$code = (string) preg_replace('/[^A-Za-z ]/', '', $code);

		return strtolower((string) preg_replace('/\s+/', '_', trim($code)));
	}

	/**
	 * The component segment a safe code name derives, as the compiler derives it.
	 *
	 * @param   string  $code  The component's safe code name.
	 *
	 * @return  string  The namespace-safe component segment.
	 * @since   6.1.7
	 */
	protected function segment(string $code): string
	{
		if ($code === '')
		{
			return '';
		}

		return NamespaceHelper::safeSegment(ucfirst($code));
	}

	/**
	 * A placeholder target with its wrapper stripped, when it carries one.
	 *
	 * @param   string  $target  The raw target.
	 *
	 * @return  string  The bare target name.
	 * @since   6.1.7
	 */
	public function target(string $target): string
	{
		$target = trim($target);

		if (strlen($target) >= 6
			&& ((str_starts_with($target, '[[[') && str_ends_with($target, ']]]'))
				|| (str_starts_with($target, '###') && str_ends_with($target, '###'))))
		{
			return substr($target, 3, -3);
		}

		return $target;
	}
}
