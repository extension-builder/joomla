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
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Links every written site view to the JCB component being built.
 *
 * A site view that belongs to no component exists but is compiled into nothing, so
 * this is what makes the front end half of the run mean anything. It mirrors the
 * admin link exactly, because the two tables differ only in which view they name.
 *
 * @since 6.1.6
 */
final class ComponentSiteViews extends Writer
{
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
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 * @param   Guid           $guid      The identity resolver.
	 * @param   Source         $source    The source identity registry.
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		Guid $guid,
		Source $source
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->guid = $guid;
		$this->source = $source;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.6
	 */
	protected function table(): string
	{
		return 'component_site_views';
	}

	/**
	 * Link every written site view to the target component.
	 *
	 * @return  int  The number of views linked.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		$component = (int) $this->config->get('component', 0);
		$views = (array) $this->resolved->get('site_view', []);

		if ($views === [])
		{
			return 0;
		}

		if ($component <= 0)
		{
			$this->report->set('failed.component_site_views.no_component', true);

			return 0;
		}

		$subform = [];
		$number = 0;

		foreach ($views as $entry)
		{
			$guid = (string) (((array) $entry)['guid'] ?? '');

			if ($guid === '')
			{
				continue;
			}

			// The first view found is offered as the component's default, because a
			// component with no default front end view has no reachable front end at
			// all. Which one it should really be is a decision only the author can
			// make, so it is recorded rather than left implicit.
			$subform['addsite_views' . $number] = [
				'siteview' => $guid,
				'menu' => 1,
				'metadata' => 1,
				'access' => 1,
				'public_access' => 1,
				'default_view' => $number === 0 ? 1 : 0
			];
			$number++;
		}

		if ($subform === [])
		{
			return 0;
		}

		$definition = new \stdClass();
		$definition->guid = $this->guid->derive(
			[$this->option(), 'component_site_views', (string) $component]
		);
		$definition->joomla_component = $component;
		$definition->addsite_views = json_encode($subform, JSON_FORCE_OBJECT);
		$definition->published = 1;

		if (!$this->store($definition))
		{
			return 0;
		}

		$this->report->set('counts.component_site_views', $number);
		$this->report->set(
			'site_view.default',
			(string) (((array) reset($views))['name'] ?? '')
		);

		return $number;
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
