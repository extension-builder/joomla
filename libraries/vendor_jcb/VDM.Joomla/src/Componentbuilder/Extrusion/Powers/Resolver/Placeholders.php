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
	 * The prefix to fall back on when nothing else names one.
	 *
	 * @var    string|null
	 * @since  6.1.7
	 */
	protected ?string $fallback;

	/**
	 * The resolved values, cached per component id.
	 *
	 * @var    array<int, array{prefix: string, component: string}>
	 * @since  6.1.7
	 */
	protected array $resolved = [];

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   LoadInterface  $load      The database loader.
	 * @param   Report         $report    The run report registry.
	 * @param   string|null    $fallback  The prefix to use when none is configured.
	 *
	 * @since   6.1.7
	 */
	public function __construct(
		Config $config,
		LoadInterface $load,
		Report $report,
		?string $fallback = null
	)
	{
		$this->config = $config;
		$this->load = $load;
		$this->report = $report;
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

		if (isset($this->resolved[$id]))
		{
			return $this->resolved[$id];
		}

		$prefix = '';
		$component = '';
		$guid = '';

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
				$component = $this->segment((string) ($row->name_code ?? ''));

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

		if ($prefix === '')
		{
			$prefix = $this->fallbackPrefix();
		}

		[$prefix, $component] = $this->override($guid, $prefix, $component);

		$this->report->set('powers.placeholders', [
			'prefix' => $prefix,
			'component' => $component
		]);

		return $this->resolved[$id] = [
			'prefix' => $prefix,
			'component' => $component
		];
	}

	/**
	 * Apply the component's own placeholder overrides, which outrank the rest.
	 *
	 * @param   string  $guid       The component identity, or an empty string.
	 * @param   string  $prefix     The resolved prefix so far.
	 * @param   string  $component  The resolved component segment so far.
	 *
	 * @return  array{0: string, 1: string}  The prefix and component after overrides.
	 * @since   6.1.7
	 */
	protected function override(string $guid, string $prefix, string $component): array
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

		foreach ($rows as $row)
		{
			$row = (array) $row;
			$target = $this->target((string) ($row['target'] ?? ''));
			$value = trim((string) ($row['value'] ?? ''));

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
	 * The component segment a code name derives, as the compiler derives it.
	 *
	 * This replicates the compiler's derivation -- number words, the safe
	 * lower name, the first letter raised, the segment stripped -- without
	 * the transliteration step, because that step needs a running
	 * application and a code name is plain ASCII by its own convention.
	 *
	 * @param   string  $codeName  The component's raw code name.
	 *
	 * @return  string  The namespace-safe component segment.
	 * @since   6.1.7
	 */
	protected function segment(string $codeName): string
	{
		if (trim($codeName) === '')
		{
			return '';
		}

		$code = trim((string) StringHelper::numbers($codeName));
		$code = (string) preg_replace('/_+/', ' ', $code);
		$code = (string) preg_replace('/\s+/', ' ', $code);
		$code = (string) preg_replace('/[^A-Za-z ]/', '', $code);
		$code = strtolower((string) preg_replace('/\s+/', '_', trim($code)));

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
	protected function target(string $target): string
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
