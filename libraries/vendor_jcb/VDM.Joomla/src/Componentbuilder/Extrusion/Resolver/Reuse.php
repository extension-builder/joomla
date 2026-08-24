<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    24th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Resolver;


use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;


/**
 * Makes every matched candidate reuse what JCB already holds.
 *
 * Everything in JCB is linked by guid, so only a guid in common says two
 * definitions are the same thing. This step turns those identity matches
 * into standing verdicts before anything is written: a guid-matched
 * candidate the caller left undecided updates the definition it already is,
 * and a guid-matched field records that identity so its view links it even
 * when the field itself is left untouched. A candidate that merely shares a
 * name stays a fresh creation -- the resemblance is offered on the board,
 * never acted on, because linking a lookalike would misstate identity.
 *
 * An explicit verdict from the pairing board always outranks these defaults;
 * this layer only speaks where the caller stayed silent.
 *
 * @since 6.1.8
 */
final class Reuse
{
	/**
	 * The Candidates Resolver.
	 *
	 * @var    Candidates
	 * @since  6.1.8
	 */
	protected Candidates $candidates;

	/**
	 * The Pairing Resolver.
	 *
	 * @var    Pairing
	 * @since  6.1.8
	 */
	protected Pairing $pairing;

	/**
	 * The Resolved Registry.
	 *
	 * @var    Resolved
	 * @since  6.1.8
	 */
	protected Resolved $resolved;

	/**
	 * The Report Registry.
	 *
	 * @var    Report
	 * @since  6.1.8
	 */
	protected Report $report;

	/**
	 * The Config.
	 *
	 * @var    Config
	 * @since  6.1.8
	 */
	protected Config $config;

	/**
	 * Constructor.
	 *
	 * @param   Candidates  $candidates  The candidates resolver.
	 * @param   Pairing     $pairing     The pairing resolver.
	 * @param   Resolved    $resolved    The resolved definition registry.
	 * @param   Report      $report      The run report registry.
	 * @param   Config      $config      The extrusion configuration.
	 *
	 * @since   6.1.8
	 */
	public function __construct(
		Candidates $candidates,
		Pairing $pairing,
		Resolved $resolved,
		Report $report,
		Config $config
	)
	{
		$this->candidates = $candidates;
		$this->pairing = $pairing;
		$this->resolved = $resolved;
		$this->report = $report;
		$this->config = $config;
	}

	/**
	 * Record reuse verdicts for every matched candidate of this run.
	 *
	 * @return  int  How many matched candidates were set to reuse their match.
	 * @since   6.1.8
	 */
	public function apply(): int
	{
		$component = (int) $this->config->get('component', 0);
		$reused = 0;

		foreach ($this->candidates->candidates($component) as $entries)
		{
			foreach ((array) $entries as $entry)
			{
				$reused += $this->one((array) $entry);
			}
		}

		if ($reused > 0)
		{
			$this->report->set('counts.reused', $reused);
		}

		return $reused;
	}

	/**
	 * Record one candidate's reuse, and walk into its field candidates.
	 *
	 * @param   array<string, mixed>  $entry  The candidate entry.
	 *
	 * @return  int  How many reuse verdicts this entry and its fields yielded.
	 * @since   6.1.8
	 */
	protected function one(array $entry): int
	{
		$kind = (string) ($entry['kind'] ?? '');
		$key = (string) ($entry['key'] ?? '');
		$match = $entry['match'] ?? null;
		$reused = 0;

		if ($kind !== '' && $key !== '' && is_array($match)
			&& ($match['by'] ?? '') === 'guid')
		{
			$target = (string) ($match['guid'] ?? '');

			if ($this->pairing->reuse($kind, $key, $target))
			{
				$reused++;
			}

			// a matched field records the identity it matched, so the view
			// that relates to it links the field that already stands -- the
			// link is owed whether or not the field itself is rewritten
			if ($kind === 'field' && $target !== ''
				&& ($dot = strpos($key, '.')) !== false)
			{
				$this->resolved->set(
					'view.' . substr($key, 0, $dot) . '.linked.'
					. substr($key, $dot + 1) . '.guid',
					strtolower($target)
				);
			}
		}

		foreach ((array) ($entry['fields'] ?? []) as $field)
		{
			$reused += $this->one((array) $field);
		}

		return $reused;
	}
}
