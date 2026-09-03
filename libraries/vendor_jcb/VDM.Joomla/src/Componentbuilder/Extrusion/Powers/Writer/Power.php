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

namespace VDM\Joomla\Componentbuilder\Extrusion\Powers\Writer;


use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Writer;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Harvest;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Delta;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Persists every assembled power definition into JCB.
 *
 * All the shared writing mechanics apply unchanged: the Data pipeline resolves
 * insert against update from the guid and applies the storage encoding the
 * power table declares, a dry run stops before anything is touched, and the
 * skip policy leaves an existing power exactly as it stands while the report
 * still names it -- which is the whole "mention it, do not touch it" switch.
 *
 * @since 6.1.7
 */
final class Power extends Writer
{
	/**
	 * The Harvest Registry.
	 *
	 * @var    Harvest
	 * @since  6.1.7
	 */
	protected Harvest $harvest;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 * @param   Delta          $delta     The change weigher.
	 * @param   Harvest        $harvest   The harvest registry.
	 *
	 * @since   6.1.7
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		Delta $delta,
		Harvest $harvest
	)
	{
		parent::__construct($config, $resolved, $item, $report, $delta);

		$this->harvest = $harvest;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.7
	 */
	protected function table(): string
	{
		return 'power';
	}

	/**
	 * Write every assembled power definition.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.7
	 */
	public function write(): int
	{
		$written = 0;

		foreach ((array) $this->harvest->get('resolved', []) as $definition)
		{
			if (!is_object($definition))
			{
				$definition = (object) $definition;
			}

			// a power's board row is the identity the harvest gave the class,
			// which a verdict may have replaced with the one it is written under
			$guid = (string) ($definition->guid ?? '');
			$row = 'power|' . (string) $this->harvest->get('rows.' . $guid, $guid);

			if ($this->store($definition, [], null, $row))
			{
				$written++;
			}
		}

		$this->report->set('counts.power', $written);

		return $written;
	}
}
