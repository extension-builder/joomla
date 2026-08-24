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

namespace VDM\Joomla\Componentbuilder\Extrusion\Writer;


use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Writer;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Writes one JCB admin view definition per resolved table.
 *
 * The seed data a source schema carried in its INSERT statements is passed
 * through raw, because the admin view's sql column declares base64 storage and
 * the Data pipeline applies it. Encoding here is exactly the mistake the legacy
 * extrusion helper makes.
 *
 * @since 6.1.6
 */
final class AdminView extends Writer
{
	/**
	 * The permission actions a generated view is given.
	 *
	 * Each becomes one subform row of action and implementation, which is the
	 * only shape the admin_view form renders -- and 3 implements the action
	 * on both the record and the whole view, as JCB's own demo data does.
	 *
	 * @var    array<int, string>
	 * @since  6.1.6
	 */
	private const PERMISSIONS = [
		'view.edit', 'view.edit.own', 'view.edit.state', 'view.edit.access',
		'view.edit.created_by', 'view.edit.created', 'view.create',
		'view.delete', 'view.access'
	];

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.6
	 */
	protected Guid $guid;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	protected Source $source;

	/**
	 * The Pairing Resolver.
	 *
	 * @var    Pairing
	 * @since  6.1.7
	 */
	protected Pairing $pairing;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 * @param   Guid           $guid      The identity resolver.
	 * @param   Source         $source    The source identity registry.
	 * @param   Pairing        $pairing   The pairing resolver.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		Guid $guid,
		Source $source,
		Pairing $pairing
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->guid = $guid;
		$this->source = $source;
		$this->pairing = $pairing;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.6
	 */
	protected function table(): string
	{
		return 'admin_view';
	}

	/**
	 * Write every resolved admin view.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		$written = 0;

		foreach ($this->views() as $view)
		{
			if ($this->one($view))
			{
				$written++;
			}
		}

		$this->report->set('counts.admin_view', $written);

		return $written;
	}

	/**
	 * Write one resolved admin view.
	 *
	 * @param   string  $view  The view name.
	 *
	 * @return  bool  True when the definition was written.
	 * @since   6.1.6
	 */
	protected function one(string $view): bool
	{
		$path = $this->path($view);
		$single = (string) $this->resolved->get($path . '.name_single', $view);
		$system = (string) $this->resolved->get($path . '.system_name', $single);
		$guid = (string) $this->resolved->get(
			$path . '.guid',
			$this->guid->derive([$this->option(), 'admin_view', $single])
		);

		// the caller's pairing verdict outranks the derived identity
		$guid = $this->pairing->guid('admin_view', $this->key($view), $guid);

		if ($guid === null)
		{
			return false;
		}

		$seed = $this->resolved->get($path . '.seed');

		$definition = new \stdClass();
		$definition->guid = $guid;
		$definition->system_name = $system;
		$definition->name_single = $single;
		$definition->name_list = (string) $this->resolved->get($path . '.name_list', $single . 's');
		$definition->short_description = $system . ' (extruded)';
		$definition->description = $system . ' (extruded)';
		$definition->type = 1;
		$definition->add_fadein = 1;
		$definition->addpermissions = $this->permissions();
		$definition->addtabs = $this->tabs($path);
		$definition->published = 1;

		if (is_string($seed) && $seed !== '')
		{
			$definition->add_sql = 1;
			$definition->source = 2;
			$definition->sql = $seed;
		}

		if (!$this->store($definition))
		{
			return false;
		}

		$this->resolved->set($path . '.written.view.guid', $guid);

		return true;
	}

	/**
	 * The permission rows a generated view is given.
	 *
	 * @return  array<string, array<string, mixed>>  The permission subform.
	 * @since   6.1.8
	 */
	protected function permissions(): array
	{
		$subform = [];

		foreach (self::PERMISSIONS as $number => $action)
		{
			$subform['addpermissions' . $number] = [
				'action' => $action,
				'implementation' => 3
			];
		}

		return $subform;
	}

	/**
	 * The tab subform a view's resolved tabs describe.
	 *
	 * @param   string  $path  The resolved registry path of the view.
	 *
	 * @return  array<string, array<string, mixed>>  The tab subform.
	 * @since   6.1.6
	 */
	protected function tabs(string $path): array
	{
		$tabs = $this->resolved->get($path . '.tabs', []);
		$subform = [];
		$number = 0;

		foreach ((array) $tabs as $name)
		{
			$subform['addtabs' . $number] = ['name' => (string) $name];
			$number++;
		}

		return $subform;
	}

	/**
	 * The component option, when it is known.
	 *
	 * @return  string  The com_ prefixed option, or an empty string.
	 * @since   6.1.6
	 */
	protected function option(): string
	{
		return (string) $this->source->get('code_name', '');
	}
}
