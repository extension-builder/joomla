<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    28th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Powers\Writer;


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Placeholders;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Records the placeholder values the harvested library was built with.
 *
 * A power's stored namespace defers its vendor prefix and component segment,
 * and compiling the component must resolve them back to the very values the
 * library carries, or every class lands in a different folder than the one it
 * was harvested from. The classes witnessed those values on the way in --
 * this writer records them where the compiler reads them: the vendor prefix
 * onto the component row, and the component segment, when its casing differs
 * from what the code name derives, as a ComponentNamespace override in the
 * component's own placeholder table, base64 encoded exactly as the compiler
 * decodes it.
 *
 * What a person already set always stands: a standing prefix or a standing
 * override is never overwritten, only reported when the library disagrees
 * with it.
 *
 * @since 6.1.9
 */
final class Vendor
{
	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.9
	 */
	protected Config $config;

	/**
	 * The Database Loader.
	 *
	 * @var    LoadInterface
	 * @since  6.1.9
	 */
	protected LoadInterface $load;

	/**
	 * The Data Item Class.
	 *
	 * @var    ItemInterface
	 * @since  6.1.9
	 */
	protected ItemInterface $item;

	/**
	 * The Placeholders Resolver.
	 *
	 * @var    Placeholders
	 * @since  6.1.9
	 */
	protected Placeholders $placeholders;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.9
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config        The extrusion configuration.
	 * @param   LoadInterface  $load          The database loader.
	 * @param   ItemInterface  $item          The JCB data item writer.
	 * @param   Placeholders   $placeholders  The placeholder value resolver.
	 * @param   Report         $report        The run report registry.
	 *
	 * @since   6.1.9
	 */
	public function __construct(
		Config $config,
		LoadInterface $load,
		ItemInterface $item,
		Placeholders $placeholders,
		Report $report
	)
	{
		$this->config = $config;
		$this->load = $load;
		$this->item = $item;
		$this->placeholders = $placeholders;
		$this->report = $report;
	}

	/**
	 * Record what the harvested classes witnessed onto the paired component.
	 *
	 * @return  int  How many values were recorded.
	 * @since   6.1.9
	 */
	public function write(): int
	{
		$witnessed = $this->placeholders->witnessed();
		$id = (int) $this->config->get('component', 0);

		if ($witnessed === [])
		{
			return 0;
		}

		if ($id <= 0)
		{
			// no component row stands to carry the values, so the component
			// segment is remembered as a global placeholder instead -- the
			// system's own memory of the name, which every later run
			// recognises and every compile overrides with its own component
			return $this->remember($witnessed[0]);
		}

		if (count($witnessed) > 1)
		{
			// a split chorus is named, never guessed at -- the most
			// spoken-for pair is recorded and the rest reported
			$this->report->set('powers.vendor.also_witnessed', array_slice($witnessed, 1));
		}

		$row = $this->load->item(
			[
				'a.guid' => 'guid',
				'a.add_namespace_prefix' => 'add_namespace_prefix',
				'a.namespace_prefix' => 'namespace_prefix'
			],
			['a' => 'joomla_component'],
			['a.id' => $id]
		);

		if ($row === null || trim((string) ($row->guid ?? '')) === '')
		{
			$this->report->set('powers.vendor.failed.component', $id);

			return 0;
		}

		return $this->prefix($row, (string) $witnessed[0]['prefix'])
			+ $this->override($row, (string) $witnessed[0]['component']);
	}

	/**
	 * Remember the witnessed component segment as a global placeholder.
	 *
	 * @param   array{prefix: string, component: string, count: int}  $pair  The witnessed pair.
	 *
	 * @return  int  One when recorded, zero otherwise.
	 * @since   6.1.9
	 */
	protected function remember(array $pair): int
	{
		$component = trim((string) ($pair['component'] ?? ''));
		$prefix = trim((string) ($pair['prefix'] ?? ''));

		if ($component === '')
		{
			return 0;
		}

		if ($prefix !== '')
		{
			// with no component row there is no place the prefix belongs;
			// it is named so the run's record still carries it
			$this->report->set('powers.vendor.unplaced.namespace_prefix', $prefix);
		}

		$rows = $this->load->items(
			['a.target' => 'target', 'a.value' => 'value'],
			['a' => 'placeholder']
		);

		foreach ((array) $rows as $row)
		{
			$row = (array) $row;

			if ($this->placeholders->target((string) ($row['target'] ?? '')) !== 'ComponentNamespace')
			{
				continue;
			}

			$standing = trim(base64_decode((string) ($row['value'] ?? '')));

			if ($standing !== $component)
			{
				$this->report->set(
					'powers.vendor.kept.global_component_namespace',
					$standing . ' (the library was built with ' . $component . ')'
				);
			}

			return 0;
		}

		if ($this->config->get('dryRun', false))
		{
			$this->report->set('powers.vendor.dryrun.global_component_namespace', $component);

			return 1;
		}

		$definition = new \stdClass();
		$definition->target = '[[[ComponentNamespace]]]';
		// the value travels raw: the placeholder table's own storage encoding
		// is applied by the Data pipeline, exactly as every writer passes it
		$definition->value = $component;
		$definition->published = 1;

		if (!$this->item->table('placeholder')->set($definition, 'target'))
		{
			$this->report->set('powers.vendor.failed.global_component_namespace', $component);

			return 0;
		}

		$this->report->set('powers.vendor.global_component_namespace', $component);

		return 1;
	}

	/**
	 * Record the vendor prefix the library states, where none stands.
	 *
	 * @param   object  $row     The component row.
	 * @param   string  $prefix  The witnessed vendor prefix.
	 *
	 * @return  int  One when recorded, zero otherwise.
	 * @since   6.1.9
	 */
	protected function prefix(object $row, string $prefix): int
	{
		if ($prefix === '')
		{
			return 0;
		}

		$standing = trim((string) ($row->namespace_prefix ?? ''));

		if ((int) ($row->add_namespace_prefix ?? 0) === 1 && $standing !== '')
		{
			if (strcasecmp($standing, $prefix) !== 0)
			{
				// the person's own prefix stands; the disagreement is named
				$this->report->set(
					'powers.vendor.kept.namespace_prefix',
					$standing . ' (the library was built with ' . $prefix . ')'
				);
			}

			return 0;
		}

		if ($this->config->get('dryRun', false))
		{
			$this->report->set('powers.vendor.dryrun.namespace_prefix', $prefix);

			return 1;
		}

		$definition = new \stdClass();
		$definition->guid = trim((string) $row->guid);
		$definition->add_namespace_prefix = 1;
		$definition->namespace_prefix = $prefix;

		if (!$this->item->table('joomla_component')->set($definition, 'guid'))
		{
			$this->report->set('powers.vendor.failed.namespace_prefix', $prefix);

			return 0;
		}

		$this->report->set('powers.vendor.namespace_prefix', $prefix);

		return 1;
	}

	/**
	 * Record the component segment's own casing, where the derivation differs.
	 *
	 * @param   object  $row        The component row.
	 * @param   string  $component  The witnessed component segment.
	 *
	 * @return  int  One when recorded, zero otherwise.
	 * @since   6.1.9
	 */
	protected function override(object $row, string $component): int
	{
		if ($component === ''
			|| $this->placeholders->component() === $component)
		{
			// what resolves already is what the library carries
			return 0;
		}

		$guid = trim((string) $row->guid);
		$stored = $this->load->value(
			['a.addplaceholders' => 'addplaceholders'],
			['a' => 'component_placeholders'],
			['a.joomla_component' => $guid]
		);
		$rows = is_string($stored) && trim($stored) !== ''
			? json_decode($stored, true)
			: [];
		$rows = is_array($rows) ? $rows : [];

		foreach ($rows as $standing)
		{
			$standing = (array) $standing;

			if ($this->placeholders->target((string) ($standing['target'] ?? '')) === 'ComponentNamespace')
			{
				$value = base64_decode((string) ($standing['value'] ?? ''));

				if ($value !== $component)
				{
					// the person's own override stands; the disagreement is named
					$this->report->set(
						'powers.vendor.kept.component_namespace',
						$value . ' (the library was built with ' . $component . ')'
					);
				}

				return 0;
			}
		}

		if ($this->config->get('dryRun', false))
		{
			$this->report->set('powers.vendor.dryrun.component_namespace', $component);

			return 1;
		}

		$next = 0;

		while (isset($rows['addplaceholders' . $next]))
		{
			$next++;
		}

		$rows['addplaceholders' . $next] = [
			'target' => '[[[ComponentNamespace]]]',
			'value' => base64_encode($component)
		];

		$definition = new \stdClass();
		$definition->joomla_component = $guid;
		$definition->addplaceholders = $rows;
		$definition->published = 1;

		if (!$this->item->table('component_placeholders')->set($definition, 'joomla_component'))
		{
			$this->report->set('powers.vendor.failed.component_namespace', $component);

			return 0;
		}

		$this->report->set('powers.vendor.component_namespace', $component);

		return 1;
	}
}
