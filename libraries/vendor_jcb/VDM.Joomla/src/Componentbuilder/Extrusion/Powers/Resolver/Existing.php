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


/**
 * Knows every power that already exists, by the class it resolves to.
 *
 * A built class carries no power identity, so recognition works through the
 * namespace: every stored power namespace is unfolded into the real class name
 * it would compile to under this run's placeholder values, and a harvested
 * class that lands on the same name IS that power. The whole catalogue is read
 * once and held as a map, because every harvested class asks this question and
 * every use statement asks it again.
 *
 * A power made for a different component resolves to a different class name
 * under this run's values, so it simply never matches -- which is exactly the
 * decoupling the placeholders exist to provide.
 *
 * @since 6.1.7
 */
final class Existing
{
	/**
	 * The Database Loader.
	 *
	 * @var    LoadInterface
	 * @since  6.1.7
	 */
	protected LoadInterface $load;

	/**
	 * The Namespacer Resolver.
	 *
	 * @var    Namespacer
	 * @since  6.1.7
	 */
	protected Namespacer $namespacer;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.7
	 */
	protected Report $report;

	/**
	 * The catalogue, resolved class name keyed to its power identity.
	 *
	 * @var    array<string, array{guid: string, id: int, name: string}>|null
	 * @since  6.1.7
	 */
	protected ?array $index = null;

	/**
	 * Constructor.
	 *
	 * @param   LoadInterface  $load        The database loader.
	 * @param   Namespacer     $namespacer  The namespace conversion resolver.
	 * @param   Report         $report      The run report registry.
	 *
	 * @since   6.1.7
	 */
	public function __construct(
		LoadInterface $load,
		Namespacer $namespacer,
		Report $report
	)
	{
		$this->load = $load;
		$this->namespacer = $namespacer;
		$this->report = $report;
	}

	/**
	 * The existing power one fully qualified class name resolves to.
	 *
	 * @param   string  $fqn  The fully qualified class name.
	 *
	 * @return  array{guid: string, id: int, name: string}|null  The power, or null when none matches.
	 * @since   6.1.7
	 */
	public function find(string $fqn): ?array
	{
		return $this->index()[$this->namespacer->key($fqn)] ?? null;
	}

	/**
	 * How many existing powers the catalogue resolved.
	 *
	 * @return  int  The number of matchable powers.
	 * @since   6.1.7
	 */
	public function count(): int
	{
		return count($this->index());
	}

	/**
	 * Read and resolve the whole power catalogue, once.
	 *
	 * @return  array<string, array{guid: string, id: int, name: string}>  The catalogue.
	 * @since   6.1.7
	 */
	protected function index(): array
	{
		if ($this->index !== null)
		{
			return $this->index;
		}

		$this->index = [];
		$rows = $this->load->items(
			[
				'a.id' => 'id',
				'a.guid' => 'guid',
				'a.name' => 'name',
				'a.namespace' => 'namespace'
			],
			['a' => 'power']
		);

		foreach ((array) $rows as $row)
		{
			$row = (array) $row;
			$guid = trim((string) ($row['guid'] ?? ''));
			$namespace = trim((string) ($row['namespace'] ?? ''));

			if ($guid === '' || $namespace === '')
			{
				continue;
			}

			$fqn = $this->namespacer->resolve($namespace);

			if ($fqn === '')
			{
				// a placeholder this run has no value for cannot be matched
				$this->report->set(
					'powers.unresolved.namespace.' . $this->key($guid),
					$namespace
				);

				continue;
			}

			$key = $this->namespacer->key($fqn);

			if (isset($this->index[$key]))
			{
				$this->report->set(
					'powers.duplicate.namespace.' . $this->key($guid),
					$namespace
				);

				continue;
			}

			$this->index[$key] = [
				'guid' => $guid,
				'id' => (int) ($row['id'] ?? 0),
				'name' => trim((string) ($row['name'] ?? ''))
			];
		}

		return $this->index;
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
