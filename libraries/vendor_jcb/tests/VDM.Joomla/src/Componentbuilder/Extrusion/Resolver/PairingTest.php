<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    23rd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Decision;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;
use VDM\Tests\Support\TestCase;


/**
 * The layer that applies a person's pairing verdicts to settled identities.
 *
 * These verdicts decide what is written where, so the contract is strict:
 * anything without a verdict keeps the harvest's answer, a malformed verdict
 * is reported and dropped rather than trusted, and every honoured verdict is
 * deterministic -- the same verdicts always land on the same identities.
 *
 * @since  6.1.7
 */
#[CoversClass(Pairing::class)]
#[UsesClass(Guid::class)]
#[UsesClass(Report::class)]
final class PairingTest extends TestCase
{
	/**
	 * A valid identity to use as an update target.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const TARGET = 'aaaaaaaa-1111-4111-8111-111111111111';

	/**
	 * The decision registry under the resolver.
	 *
	 * @var    Decision
	 * @since  6.1.7
	 */
	private Decision $decision;

	/**
	 * The run report.
	 *
	 * @var    Report
	 * @since  6.1.7
	 */
	private Report $report;

	/**
	 * The resolver under test.
	 *
	 * @var    Pairing
	 * @since  6.1.7
	 */
	private Pairing $pairing;

	/**
	 * Compose fresh state for every test.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->decision = new Decision();
		$this->report = new Report();
		$this->pairing = new Pairing($this->decision, new Guid(), $this->report);
	}

	/**
	 * Without a verdict the derived identity stands untouched.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithoutAVerdictTheDerivedIdentityStands(): void
	{
		$this->assertSame(
			'derived-guid',
			$this->pairing->guid('admin_view', 'item', 'derived-guid')
		);
		$this->assertNull($this->pairing->verdict('admin_view', 'item'));
	}

	/**
	 * An ignore verdict answers null and says so in the report.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnIgnoreVerdictLeavesTheCandidateOut(): void
	{
		$this->pairing->load(['field' => ['item.title' => ['action' => 'ignore']]]);

		$this->assertNull($this->pairing->guid('field', 'item.title', 'derived-guid'));
		$this->assertTrue(
			(bool) $this->report->get('skipped.decision.field.item_title')
		);
	}

	/**
	 * An update verdict redirects the candidate onto its target.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnUpdateVerdictRedirectsOntoItsTarget(): void
	{
		$this->pairing->load([
			'admin_view' => ['item' => ['action' => 'update', 'target' => self::TARGET]]
		]);

		$this->assertSame(
			self::TARGET,
			$this->pairing->guid('admin_view', 'item', 'derived-guid')
		);
	}

	/**
	 * A create verdict forces a fresh, stable identity off the derived one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACreateVerdictForcesAFreshStableIdentity(): void
	{
		$this->pairing->load(['power' => [self::TARGET => ['action' => 'create']]]);

		$forced = $this->pairing->guid('power', self::TARGET, self::TARGET);

		$this->assertNotNull($forced);
		$this->assertNotSame(self::TARGET, $forced, 'Forcing new must never reuse the matched identity.');
		$this->assertSame(
			$forced,
			$this->pairing->guid('power', self::TARGET, self::TARGET),
			'The forced identity is deterministic, so a re-run updates in place.'
		);
	}

	/**
	 * Malformed verdicts are reported and dropped, never trusted.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testMalformedVerdictsAreReportedAndDropped(): void
	{
		$loaded = $this->pairing->load([
			'admin_view' => [
				'good' => ['action' => 'ignore'],
				'nonsense' => ['action' => 'destroy'],
				'aimless' => ['action' => 'update', 'target' => 'not-a-guid']
			]
		]);

		$this->assertSame(1, $loaded);
		$this->assertNotNull($this->pairing->verdict('admin_view', 'good'));
		$this->assertNull($this->pairing->verdict('admin_view', 'nonsense'));
		$this->assertSame(
			'malformed verdict',
			$this->report->get('failed.decision.admin_view.nonsense')
		);
		$this->assertSame(
			'update verdict without a valid target',
			$this->report->get('failed.decision.admin_view.aimless')
		);
	}

	/**
	 * Keys are sanitised the same on the way in and the way out.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testKeysSanitiseTheSameInBothDirections(): void
	{
		$this->pairing->load(['power' => [self::TARGET => ['action' => 'ignore']]]);

		$this->assertNull(
			$this->pairing->guid('power', self::TARGET, self::TARGET),
			'A guid key carries dashes, and both directions must fold them alike.'
		);
	}
}
