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
	 * The catalogue, held under the canonical namespace, the class name, and the guid.
	 *
	 * @var    array{namespace: array<string, array{guid: string, id: int, name: string, namespace: string}>, class: array<string, array{guid: string, id: int, name: string, namespace: string}>, guid: array<string, array{guid: string, id: int, name: string, namespace: string}>}|null
	 * @since  6.1.7
	 */
	protected ?array $index = null;

	/**
	 * The placeholder values the catalogue was resolved under.
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
		return $this->index()['class'][$this->namespacer->key($fqn)] ?? null;
	}

	/**
	 * The existing power one stored namespace names.
	 *
	 * This is what identity means here. A power's stored namespace defers its
	 * vendor prefix and component segment, so the same class serves components
	 * whose prefixes differ -- and two classes are the same power exactly when
	 * they fold to the same stored namespace, whatever they were compiled as.
	 * Resolving both sides to concrete names instead would make every library
	 * whose prefix differs from this run's look new.
	 *
	 * The namespace is matched in its canonical form, so a power a person
	 * stored through a placeholder of their own is the same power as the
	 * long form the placeholder stands for.
	 *
	 * @param   string  $namespace  The stored, placeholder-carrying namespace.
	 *
	 * @return  array{guid: string, id: int, name: string, namespace: string}|null  The power, or null when none matches.
	 * @since   6.1.8
	 */
	public function match(string $namespace): ?array
	{
		return $this->index()['namespace'][$this->identity($namespace)] ?? null;
	}

	/**
	 * The existing power one identity names.
	 *
	 * @param   string  $guid  The power identity.
	 *
	 * @return  array{guid: string, id: int, name: string, namespace: string}|null  The power, or null when none stands.
	 * @since   6.1.9
	 */
	public function power(string $guid): ?array
	{
		return $this->index()['guid'][strtolower(trim($guid))] ?? null;
	}

	/**
	 * The existing power one written reference folds to.
	 *
	 * An import or a parent written under another component's prefix or
	 * casing is still the same power: the reference is folded to its stored
	 * form and matched by that identity. The convention every power JCB
	 * ships follows -- two head segments, then dots -- is tried first, and
	 * then every other seam the written name allows, because a power that
	 * lives in a component's own source folder keeps a longer head. Nothing
	 * is witnessed on the way: a reference merely refers.
	 *
	 * @param   string  $fqn  The fully qualified class name as written.
	 *
	 * @return  array{guid: string, id: int, name: string, namespace: string}|null  The power, or null when none matches.
	 * @since   6.1.9
	 */
	public function fold(string $fqn): ?array
	{
		$segments = array_values(array_filter(
			explode('\\', trim($fqn, '\\')),
			'strlen'
		));

		if (count($segments) < 2)
		{
			return null;
		}

		$class = (string) array_pop($segments);
		$namespace = implode('\\', $segments);
		$stored = [$this->namespacer->conventional($namespace, $class)];

		for ($keep = 3; $keep <= count($segments); $keep++)
		{
			$stored[] = implode('\\', array_slice($segments, 0, $keep)) . '\\'
				. implode('.', array_merge(array_slice($segments, $keep), [$class]));
		}

		foreach (array_unique($stored) as $form)
		{
			$power = $this->match($this->namespacer->placeholderize($form, false));

			if ($power !== null)
			{
				return $power;
			}
		}

		return null;
	}

	/**
	 * The key one stored namespace is held under: its canonical form, case folded.
	 *
	 * @param   string  $namespace  The stored namespace.
	 *
	 * @return  string  The identity key.
	 * @since   6.1.9
	 */
	public function identity(string $namespace): string
	{
		return $this->namespacer->key($this->namespacer->canonical($namespace));
	}

	/**
	 * How many existing powers the catalogue holds.
	 *
	 * @return  int  The number of matchable powers.
	 * @since   6.1.7
	 */
	public function count(): int
	{
		return count($this->index()['namespace']);
	}

	/**
	 * Drop the catalogue, so the next question reads the table again.
	 *
	 * A harvest calls this as it starts gathering: within one run the single
	 * snapshot is exactly right, but a fresh run must see what the previous
	 * run's own writes put into the table.
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
	 * Read the whole power catalogue, once, under both of its names.
	 *
	 * A power answers to two questions. Identity asks whether a harvested
	 * class is already this power, and that is its stored namespace. Linking
	 * asks which power a name written in someone's code refers to, and that is
	 * the concrete class the stored namespace resolves to for this run.
	 *
	 * @return  array{namespace: array<string, array{guid: string, id: int, name: string}>, class: array<string, array{guid: string, id: int, name: string}>}  The catalogue.
	 * @since   6.1.7
	 */
	protected function index(): array
	{
		// a second run may resolve under other placeholder values, so the
		// catalogue is only reused while those values still hold
		$under = $this->namespacer->signature();

		if ($this->index !== null && $this->under === $under)
		{
			return $this->index;
		}

		$this->under = $under;
		$this->index = ['namespace' => [], 'class' => [], 'guid' => []];
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

			$power = [
				'guid' => $guid,
				'id' => (int) ($row['id'] ?? 0),
				'name' => trim((string) ($row['name'] ?? '')),
				'namespace' => $namespace
			];
			$identity = $this->identity($namespace);

			if (isset($this->index['namespace'][$identity]))
			{
				$this->report->set(
					'powers.duplicate.namespace.' . $this->key($guid),
					$namespace
				);

				continue;
			}

			$this->index['namespace'][$identity] = $power;
			$this->index['guid'][strtolower($guid)] = $power;

			$fqn = $this->namespacer->resolve($namespace);

			if ($fqn === '')
			{
				// a placeholder this run has no value for still identifies the
				// power; it just cannot say which written name reaches it
				$this->report->set(
					'powers.unresolved.namespace.' . $this->key($guid),
					$namespace
				);

				continue;
			}

			$name = $this->namespacer->key($fqn);

			if (isset($this->index['class'][$name]))
			{
				// two identities reaching one class name cannot both answer
				// for a written reference, so the first keeps it
				$this->report->set(
					'powers.duplicate.class.' . $this->key($guid),
					$fqn
				);

				continue;
			}

			$this->index['class'][$name] = $power;
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
