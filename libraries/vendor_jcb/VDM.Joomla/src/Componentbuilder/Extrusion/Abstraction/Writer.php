<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Abstraction;


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\WriterInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Shared mechanics for every writer that persists into JCB.
 *
 * Writing goes through the shared Data pipeline, which resolves insert against
 * update from the GUID and applies the storage encoding declared in JCB's own
 * table definition class. Two consequences shape every subclass: a writer must
 * pass raw values, because encoding here would double-encode; and idempotency is
 * a property of the identity supplied, not of anything the writer does.
 *
 * A dry run stops at exactly this boundary, so a caller can see the whole report
 * before any definition table is touched.
 *
 * @since 6.1.6
 */
abstract class Writer implements WriterInterface
{
	/**
	 * The Config Class.
	 *
	 * @var    Config
	 * @since  6.1.6
	 */
	protected Config $config;

	/**
	 * The Resolved Registry.
	 *
	 * @var    Resolved
	 * @since  6.1.6
	 */
	protected Resolved $resolved;

	/**
	 * The Data Item Class.
	 *
	 * @var    ItemInterface
	 * @since  6.1.6
	 */
	protected ItemInterface $item;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	protected Report $report;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report
	)
	{
		$this->config = $config;
		$this->resolved = $resolved;
		$this->item = $item;
		$this->report = $report;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.6
	 */
	abstract protected function table(): string;

	/**
	 * Every view name in the resolved set.
	 *
	 * @return  array<string>  The view names.
	 * @since   6.1.6
	 */
	protected function views(): array
	{
		$views = $this->resolved->get('views', []);

		return is_array($views) ? array_values(array_filter($views, 'is_string')) : [];
	}

	/**
	 * The resolved registry path of one view.
	 *
	 * @param   string  $view  The view name.
	 *
	 * @return  string  The registry path.
	 * @since   6.1.6
	 */
	protected function path(string $view): string
	{
		return 'view.' . $this->key($view);
	}

	/**
	 * Persist one definition through the Data pipeline.
	 *
	 * @param   object  $definition  The definition, carrying its guid.
	 *
	 * @return  bool  True when the definition was written, or a dry run skipped it.
	 * @since   6.1.6
	 */
	protected function store(object $definition): bool
	{
		$key = $this->linkKey();
		$identity = (string) ($definition->{$key} ?? '');

		if ($identity === '')
		{
			$this->report->set('failed.' . $this->table() . '.missing_' . $key, true);

			return false;
		}

		if ($this->config->get('dryRun', false))
		{
			$this->report->set('dryrun.' . $this->table() . '.' . $identity, true);

			return true;
		}

		$existing = $this->item->table($this->table())->value($identity, $key, 'id');
		$policy = (string) $this->config->get('onExisting', 'update');

		if ($existing !== null && $existing > 0 && $policy === 'skip')
		{
			$this->report->set('skipped.existing.' . $this->table() . '.' . $identity, true);

			return true;
		}

		if (!$this->item->table($this->table())->set($definition, $key))
		{
			$this->report->set('failed.' . $this->table() . '.' . $identity, true);

			return false;
		}

		$this->report->set('written.' . $this->table() . '.' . $identity, true);

		return true;
	}

	/**
	 * The column this writer's table is keyed by.
	 *
	 * The entity tables carry their own guid, so guid is the default. A
	 * linked-map table (admin_fields, component_admin_views, and their kin)
	 * holds no guid at all -- the Table class defines none for it -- and is
	 * keyed by the column that names its parent, exactly as JCB's Package
	 * import declares a key field per area. A writer for such a table
	 * declares that column by overriding this method.
	 *
	 * @return  string  The key column of this writer's table.
	 * @since   6.1.7
	 */
	protected function linkKey(): string
	{
		return 'guid';
	}

	/**
	 * The value of one resolved field property.
	 *
	 * @param   array<string, mixed>  $properties  The resolved properties.
	 * @param   string                $property    The property name.
	 * @param   mixed                 $default     A value to use when unresolved.
	 *
	 * @return  mixed  The resolved value, or the default.
	 * @since   6.1.6
	 */
	protected function value(array $properties, string $property, $default = null)
	{
		$entry = $properties[$property] ?? null;

		if (!is_array($entry) && !is_object($entry))
		{
			return $default;
		}

		$entry = (array) $entry;
		$value = $entry['value'] ?? null;

		return $value === null || $value === '' ? $default : $value;
	}

	/**
	 * Sanitise one registry path segment.
	 *
	 * @param   string  $segment  The raw segment.
	 *
	 * @return  string  A segment safe to use in a dotted registry path.
	 * @since   6.1.6
	 */
	public function key(string $segment): string
	{
		return preg_replace('/[^A-Za-z0-9_]/', '_', $segment) ?? $segment;
	}
}
