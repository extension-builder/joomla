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
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Writes the front end views a component's site templates describe.
 *
 * A site view is the front end counterpart of an admin view, but it is not built
 * from a database table: its body is the default template of its own folder. That
 * makes it recoverable from any component with a site folder, whether or not JCB
 * built it and whether or not the run ever saw a schema.
 *
 * What cannot be recovered is the view's data source. Reconstructing a Joomla query
 * back into JCB's dynamic get structure is guesswork, so main_get is left unset and
 * said to be unset. The view arrives with its body, its name and its context, which
 * is the part a person would otherwise have had to copy by hand.
 *
 * @since 6.1.6
 */
final class SiteView extends Writer
{
	/**
	 * The View Registry.
	 *
	 * @var    View
	 * @since  6.1.6
	 */
	protected View $view;

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
	 * @param   View           $view      The classified view registry.
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
		View $view,
		Guid $guid,
		Source $source,
		Pairing $pairing
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->view = $view;
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
		return 'site_view';
	}

	/**
	 * Write every site view the reader recovered.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		if (!$this->config->get('siteViews', true))
		{
			return 0;
		}

		$written = 0;
		$without = 0;

		foreach ((array) $this->view->get('site_view') as $key => $entry)
		{
			$entry = (array) $entry;
			$name = (string) ($entry['name'] ?? $key);

			if ($name === '')
			{
				continue;
			}

			if ($this->one($name, $entry))
			{
				$written++;
				$without++;
			}
		}

		$this->report->set('counts.site_view', $written);

		if ($without > 0)
		{
			$this->report->set(
				'site_view.without_get',
				$without . ' site view(s) carry no data source, because a Joomla query '
				. 'cannot be turned back into a dynamic get without guessing'
			);
		}

		return $written;
	}

	/**
	 * Write one site view.
	 *
	 * @param   string                $name   The view code name.
	 * @param   array<string, mixed>  $entry  What the reader recovered.
	 *
	 * @return  bool  True when the definition was written.
	 * @since   6.1.6
	 */
	protected function one(string $name, array $entry): bool
	{
		$guid = $this->pairing->guid(
			'site_view',
			$this->key($name),
			$this->guid->derive([$this->option(), 'site_view', $name])
		);

		if ($guid === null)
		{
			return false;
		}

		$readable = (string) ($entry['system_name'] ?? $name);

		$definition = new \stdClass();
		$definition->guid = $guid;
		$definition->name = $name;
		$definition->codename = $name;
		$definition->context = (string) ($entry['context'] ?? $name);
		$definition->system_name = $readable;
		$definition->description = (string) ($entry['description'] ?? $readable);
		$definition->default = (string) ($entry['default'] ?? '');
		$definition->php_view = (string) ($entry['php_view'] ?? '');
		$definition->add_php_view = (int) ($entry['add_php_view'] ?? 0);
		$definition->published = 1;

		if (!$this->store($definition))
		{
			return false;
		}

		$this->resolved->set('site_view.' . $this->key($name) . '.guid', $guid);
		$this->resolved->set('site_view.' . $this->key($name) . '.name', $name);

		return true;
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
