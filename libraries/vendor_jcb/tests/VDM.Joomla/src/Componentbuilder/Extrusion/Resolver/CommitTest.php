<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    21st September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Resolver;


use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Commit;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Plan;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Writer\Vendor;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Writer\Power;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Harvester;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Discovery;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Extrusion;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Powers;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Reader;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Registry;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Resolver;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Writer;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Tests\Support\ExtrusionItemFixture;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Effective complete-operation preflight and persistence through real services.
 *
 * @since  6.2.0
 */
#[CoversClass(Commit::class)]
#[CoversClass(Plan::class)]
#[CoversClass(Vendor::class)]
#[CoversClass(Power::class)]
final class CommitTest extends FilesystemTestCase
{
	/**
	 * Repair identifies existing classes and stages only their changed namespace.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testNamespaceRepairPreviewPreservesCodeRelationshipsAndNewClasses(): void
	{
		[$container, $load, $item, $db] = $this->repairPrepared();
		$db->expects($this->never())->method('transactionStart');
		$engine = $container->get('Extrusion.Powers.Extruder');
		$report = $engine->repairNamespaces()->dryRun()->extrude();
		$this->assertSame('preview', $report->get('plan.status'), json_encode($report->get('plan')));
		$writes = $container->get('Extrusion.Registry.Plan')->writes();
		$this->assertCount(1, $writes, 'The unmatched Consumer must never become a new Power during repair.');
		$this->assertSame('power', $writes[0]['table']);
		$this->assertSame($this->guid('power-b'), $writes[0]['identity']);
		$this->assertSame([
			'guid' => $this->guid('power-b'),
			'namespace' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory',
		], $writes[0]['payload'], 'Code, licence, version and dependency columns cannot enter the repair payload.');
		$factory = $this->source($container->get('Extrusion.Registry.Harvest')->get('classes'), 'Factory');
		$this->assertSame('matched', $factory['resolution']['status']);
		$this->assertSame($this->guid('power-b'), $factory['matched_guid']);
		$this->assertTrue($factory['resolution']['namespace']['round_trip']);
		$this->assertSame([], $item->records(), 'Repair preview never writes a Power or component configuration.');
	}

	/**
	 * Approved repair changes the existing namespace and a second run is a no-op.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testNamespaceRepairCommitsOneFieldAndIsIdempotent(): void
	{
		[$container, $load, $item, $db] = $this->repairPrepared();
		$db->expects($this->once())->method('transactionStart')->with(true);
		$db->expects($this->once())->method('transactionCommit')->with(true);
		$db->expects($this->never())->method('transactionRollback');
		$engine = $container->get('Extrusion.Powers.Extruder');
		$report = $engine->repairNamespaces()->dryRun()->extrude();
		$expected = $container->get('Extrusion.Registry.Plan')->writes()[0]['payload'];
		$config = $container->get('Extrusion.Config');
		$config->set('approvedPlan', $report->get('plan.fingerprint'))->set('acknowledgeUnknown', true);
		$report = $engine->dryRun(false)->extrude();
		$this->assertSame('committed', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertCount(1, $item->records());
		$this->assertEquals((object) $expected, $item->definition('power', $this->guid('power-b')));
		$this->assertSame(['power'], $item->sequence(), 'Namespace repair does not update the selected component or placeholders.');

		// The fixture records rather than mutates Data, so expose the committed
		// namespace at both database-read boundaries before the independent run.
		$row = clone $item->table('power')->get($this->guid('power-b'));
		$row->namespace = $expected['namespace'];
		$item->serve('power', $row->guid, $row);
		$load->record('power', 12, get_object_vars($row));
		$config->set('approvedPlan', '');
		$report = $engine->extrude();
		$this->assertSame('unchanged', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertSame([], $container->get('Extrusion.Registry.Plan')->writes());
		$this->assertCount(1, $item->records(), 'A repeated repair must not touch database metadata.');
	}

	/**
	 * Existing update keeps its original literal namespace unless repair is chosen.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testOrdinaryUpdateDoesNotOptIntoNamespaceRepair(): void
	{
		[$container, $load, $item] = $this->repairPrepared();
		$report = $container->get('Extrusion.Powers.Extruder')->dryRun()->extrude();
		$this->assertSame('preview', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertFalse((bool) $container->get('Extrusion.Config')->get('repairNamespaces'));
		$factory = $this->source($container->get('Extrusion.Registry.Harvest')->get('classes'), 'Factory');
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\Beta.Factory', $factory['placeholder']);
		$writes = $container->get('Extrusion.Registry.Plan')->writes();
		$this->assertCount(2, $writes, 'Ordinary extrusion still imports the new Consumer.');
		$existing = array_values(array_filter($writes, fn (array $entry): bool => $entry['identity'] === $this->guid('power-b')));
		$this->assertCount(1, $existing);
		$this->assertArrayNotHasKey('namespace', $existing[0]['payload']);
		$this->assertArrayHasKey('main_class_code', $existing[0]['payload']);
		$this->assertSame([], $item->records());
	}

	/**
	 * Repair keeps the existing shared-scope acknowledgement requirement.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testNamespaceRepairCannotBypassScopeReview(): void
	{
		[$container, $load, $item, $db] = $this->repairPrepared();
		$db->expects($this->never())->method('transactionStart');
		$load->record('joomla_component', 1, $this->component('alpha', 'component-a', ['power-b']));
		$engine = $container->get('Extrusion.Powers.Extruder');
		$report = $engine->repairNamespaces()->dryRun()->extrude();
		$this->assertSame(['unknown'], $report->get('plan.required_approvals'));
		$container->get('Extrusion.Config')->set('approvedPlan', $report->get('plan.fingerprint'));
		$report = $engine->dryRun(false)->extrude();
		$this->assertSame('blocked', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertSame([], $item->records(), 'A namespace repair is still a shared definition mutation.');
	}

	/**
	 * Review exclusions remove a namespace repair without proposing another Power.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testNamespaceRepairRespectsIgnoredExistingRows(): void
	{
		[$container, $load, $item, $db] = $this->repairPrepared();
		$db->expects($this->never())->method('transactionStart');
		$engine = $container->get('Extrusion.Powers.Extruder');
		$engine->repairNamespaces()->dryRun()->extrude();
		$factory = $this->source($container->get('Extrusion.Registry.Harvest')->get('classes'), 'Factory');
		$container->get('Extrusion.Resolver.Pairing')->load(['power' => [
			$factory['source_key'] => ['action' => 'ignore'],
		]]);
		$report = $engine->extrude();
		$this->assertSame([], $container->get('Extrusion.Registry.Plan')->writes());
		$this->assertSame([], $item->records());
		$this->assertNotSame('blocked', $report->get('plan.status'), json_encode($report->get('plan')));
	}

	/**
	 * The combined extrusion entry point bypasses component harvesting in repair mode.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testCombinedExtruderRepairsLibrariesWithoutHarvestingComponentArtifacts(): void
	{
		[$container, $load, $item, $db] = $this->repairPrepared();
		$db->expects($this->never())->method('transactionStart');
		$engine = $container->get('Extruder');
		$engine->path($this->temporaryPath('absent-component'))
			->dump('CREATE TABLE #__beta_widgets (id INTEGER, title VARCHAR(255));')
			->repairNamespaces()->dryRun();
		$report = $engine->harvest();
		$this->assertTrue($report->get('powers.completed'));
		$this->assertSame([], $container->get('Extrusion.Registry.Harvest')->get('views', []));
		$report = $engine->extrude();
		$this->assertSame('preview', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertSame(['power'], array_column($container->get('Extrusion.Registry.Plan')->writes(), 'table'));
		$this->assertSame([], $container->get('Extrusion.Registry.Harvest')->get('views', []));
		$this->assertSame([], $item->records());
	}

	/**
	 * Final commit rejects any write that escapes the reviewed namespace-only plan.
	 *
	 * @param   string  $mutation  A malformed writer payload or action.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	#[DataProvider('invalidRepairWrites')]
	public function testNamespaceRepairRejectsUnreviewedOrNonNamespaceWrites(string $mutation): void
	{
		[$container, $load, $item, $db] = $this->repairPrepared();
		$db->expects($this->never())->method('transactionStart');
		$plan = $container->get('Extrusion.Registry.Plan');
		$plan->begin();
		$container->get('Extrusion.Powers.Extruder')->repairNamespaces()->dryRun()->extrude();
		$entries = $plan->get('writes');
		$this->assertCount(1, $entries);
		$key = array_key_first($entries);

		if ($mutation === 'other-table')
		{
			$entries[$key]['table'] = 'joomla_component';
		}
		elseif ($mutation === 'insert')
		{
			$entries[$key]['action'] = 'create';
		}
		elseif ($mutation === 'class-body')
		{
			$entries[$key]['payload']['main_class_code'] = 'A writer tried to replace curated code.';
		}
		elseif ($mutation === 'different-guid')
		{
			$entries[$key]['payload']['guid'] = $this->guid('power-a');
		}
		else
		{
			$entries[$key]['payload']['namespace'] = '[[[NamespacePrefix]]]\\Joomla\\Elsewhere.Factory';
		}

		$plan->set('writes', $entries);
		$this->assertFalse($container->get('Extrusion.Resolver.Commit')->apply());
		$keys = array_column($plan->blockers(), 'key');
		$this->assertNotEmpty(array_filter($keys, static fn (string $key): bool => str_starts_with($key, 'repair.')));
		$this->assertSame([], $item->records(), 'Even a faulty writer cannot widen the namespace repair operation.');
	}

	/**
	 * Malformed writer changes that must never become a namespace repair mutation.
	 *
	 * @return  array<string, array{string}>  Invalid write cases.
	 * @since   6.2.2
	 */
	public static function invalidRepairWrites(): array
	{
		return [
			'component settings' => ['other-table'],
			'new Power' => ['insert'],
			'class body' => ['class-body'],
			'different identity' => ['different-guid'],
			'unreviewed namespace' => ['unreviewed-namespace'],
		];
	}

	/**
	 * A vanished existing row cannot be silently inserted by the maintenance action.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testNamespaceRepairCannotInsertAMissingExistingRecord(): void
	{
		[$container, $load, $item, $db] = $this->repairPrepared();
		$db->expects($this->never())->method('transactionStart');
		$item->identity('power', $this->guid('power-b'), 0);
		$report = $container->get('Extrusion.Powers.Extruder')->repairNamespaces()->dryRun()->extrude();
		$this->assertSame('blocked', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertSame([], $container->get('Extrusion.Registry.Plan')->writes());
		$this->assertSame([], $item->records());
	}

	/**
	 * An unnamed target cannot authorize a namespace-only existing Power repair.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testNamespaceRepairRequiresAnExistingSelectedComponent(): void
	{
		[$container, $load, $item, $db] = $this->repairPrepared();
		$db->expects($this->never())->method('transactionStart');
		$container->get('Extrusion.Config')->set('component', 0)->set('sourceComponent', 0);
		$report = $container->get('Extrusion.Powers.Extruder')->repairNamespaces()->dryRun()->extrude();
		$this->assertSame('blocked', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertContains('repair.component', array_column($report->get('plan.blockers'), 'key'));
		$this->assertSame([], $item->records());
	}

	/**
	 * Preview and persistence choose B and apply exactly its effective payload.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testApprovedPlanWritesBAndItsDependantsWithoutChangingA(): void
	{
		[$container, $load, $item, $db] = $this->prepared();
		$db->expects($this->once())->method('transactionStart')->with(true);
		$db->expects($this->once())->method('transactionCommit')->with(true);
		$db->expects($this->never())->method('transactionRollback');
		$engine = $container->get('Extrusion.Powers.Extruder');
		$report = $engine->dryRun()->extrude();
		$this->assertSame('preview', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertSame([], $item->records());
		$this->assertSame(['unknown'], $report->get('plan.required_approvals'));
		$expected = $container->get('Extrusion.Registry.Plan')->writes();
		$this->assertCount(2, $expected);
		$config = $container->get('Extrusion.Config');
		$config->set('approvedPlan', $report->get('plan.fingerprint'));
		$config->set('acknowledgeUnknown', true);
		$report = $engine->dryRun(false)->extrude();
		$this->assertSame('committed', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertCount(2, $item->records());
		$this->assertNull($item->definition('power', $this->guid('power-a')));
		$this->assertNotNull($item->definition('power', $this->guid('power-b')));
		foreach ($expected as $index => $entry)
		{
			$this->assertEquals((object) $entry['payload'], $item->records()[$index]['item']);
		}
		$this->assertFalse(property_exists($item->definition('power', $this->guid('power-b')), 'namespace'));
	}

	/**
	 * An included ambiguous Power prevents component and auxiliary writes as well.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testBlockedPowerPreventsEveryStagedMutation(): void
	{
		[$container, $load, $item, $db] = $this->prepared();
		$db->expects($this->never())->method('transactionStart');
		$load->record('joomla_component', 2, $this->component('beta', 'component-b', ['power-a', 'power-b']));
		$plan = $container->get('Extrusion.Registry.Plan');
		$plan->begin();
		$container->get('Extrusion.Resolver.Delta')->weigh('joomla_component', 'guid', $this->guid('component-b'),
			(object) ['guid' => $this->guid('component-b'), 'name' => 'A proposed component change'], true, 'joomla_component|component');
		$container->get('Extrusion.Powers.Extruder')->extrude();
		$this->assertFalse($container->get('Extrusion.Resolver.Commit')->apply());
		$this->assertNotEmpty($plan->blockers());
		$this->assertSame([], $item->records());
		$this->assertSame('blocked', $container->get('Extrusion.Registry.Report')->get('plan.status'));
	}

	/**
	 * Unobserved shared mutation still requires a fingerprint-bound acknowledgement.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSharedMutationRequiresMatchingReviewAndOneScopeAcknowledgement(): void
	{
		[$container, $load, $item, $db] = $this->prepared();
		$db->expects($this->once())->method('transactionStart');
		$db->expects($this->once())->method('transactionCommit');
		$load->record('joomla_component', 1, $this->component('alpha', 'component-a', ['power-b']));
		$config = $container->get('Extrusion.Config');
		$engine = $container->get('Extrusion.Powers.Extruder');
		$report = $engine->dryRun()->extrude();
		$this->assertSame('preview', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertSame(['unknown'], $report->get('plan.required_approvals'));
		$fingerprint = $report->get('plan.fingerprint');
		$config->set('approvedPlan', $fingerprint);
		$this->assertSame('blocked', $engine->dryRun(false)->extrude()->get('plan.status'));
		$this->assertSame([], $item->records());
		$config->set('acknowledgeUnknown', true);
		$this->assertSame('committed', $engine->extrude()->get('plan.status'), json_encode($report->get('plan')));
		$this->assertNotNull($item->definition('power', $this->guid('power-b')));
		$this->assertNull($item->definition('power', $this->guid('power-a')));
	}

	/**
	 * A changed source after review must be shown again before any persistence.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSourceChangesInvalidateApprovalBeforeAnyWrites(): void
	{
		[$container, $load, $item, $db] = $this->prepared();
		$db->expects($this->never())->method('transactionStart');
		$engine = $container->get('Extrusion.Powers.Extruder');
		$fingerprint = $engine->dryRun()->extrude()->get('plan.fingerprint');
		$this->writeTemporaryFile('lib/Acme.Joomla/src/Beta/Factory.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nclass Factory { public function value(): int { return 3; } }\n");
		$container->get('Extrusion.Config')->set('approvedPlan', $fingerprint);
		$report = $engine->dryRun(false)->extrude();
		$this->assertSame('blocked', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertNotSame($fingerprint, $report->get('plan.fingerprint'));
		$this->assertSame([], $item->records());
	}

	/**
	 * Re-read affected records inside the transaction before its first mutation.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testInterveningRecordEditRollsBackBeforeTheFirstWrite(): void
	{
		[$container, $load, $item, $db] = $this->prepared();
		$db->expects($this->once())->method('transactionStart');
		$db->expects($this->once())->method('transactionRollback');
		$db->expects($this->never())->method('transactionCommit');
		$plan = $container->get('Extrusion.Registry.Plan');
		$plan->begin();
		$container->get('Extrusion.Powers.Extruder')->extrude();
		$config = $container->get('Extrusion.Config');
		$config->set('dryRun', true);
		$this->assertTrue($container->get('Extrusion.Resolver.Commit')->apply());
		$config->set('approvedPlan', $container->get('Extrusion.Registry.Report')->get('plan.fingerprint'));
		$config->set('acknowledgeUnknown', true)->set('dryRun', false);
		$row = clone $item->table('power')->get($this->guid('power-b'));
		$row->main_class_code = 'An intervening developer edit';
		$item->serve('power', $this->guid('power-b'), $row);
		$this->assertFalse($container->get('Extrusion.Resolver.Commit')->apply());
		$this->assertSame([], $item->records());
		$this->assertSame('blocked', $container->get('Extrusion.Registry.Report')->get('plan.status'));
	}

	/**
	 * New indexed candidates invalidate approval inside the transaction boundary.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testInterveningCandidateInsertRollsBackBeforeTheFirstWrite(): void
	{
		[$container, $load, $item, $db] = $this->prepared();
		$db->expects($this->once())->method('transactionStart')->willReturnCallback(function () use ($load): void
		{
			$load->record('power', 99, [
				'guid' => $this->guid('intervening'), 'name' => 'Factory', 'type' => 'class',
				'namespace' => 'Acme\\Joomla\\Beta.Factory'
			]);
		});
		$db->expects($this->once())->method('transactionRollback');
		$db->expects($this->never())->method('transactionCommit');
		$engine = $container->get('Extrusion.Powers.Extruder');
		$report = $engine->dryRun()->extrude();
		$container->get('Extrusion.Config')->set('approvedPlan', $report->get('plan.fingerprint'))->set('acknowledgeUnknown', true);
		$report = $engine->dryRun(false)->extrude();
		$this->assertSame('blocked', $report->get('plan.status'));
		$this->assertContains('stale.context', array_column($report->get('plan.blockers'), 'key'));
		$this->assertSame([], $item->records());
	}

	/**
	 * A database refusal is reported as rollback, not success or a partial import.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testDataFailureRequestsRollbackAndNeverReportsCommittedWrites(): void
	{
		[$container, $load, $item, $db] = $this->prepared();
		$db->expects($this->once())->method('transactionStart');
		$db->expects($this->once())->method('transactionRollback');
		$db->expects($this->never())->method('transactionCommit');
		$item->refuse('power', $this->guid('power-b'));
		$engine = $container->get('Extrusion.Powers.Extruder');
		$report = $engine->dryRun()->extrude();
		$container->get('Extrusion.Config')->set('approvedPlan', $report->get('plan.fingerprint'))->set('acknowledgeUnknown', true);
		$report = $engine->dryRun(false)->extrude();
		$this->assertSame('rolled-back', $report->get('plan.status'), json_encode($report->get('plan')));
		$this->assertSame([], $report->get('plan.writes'));
		$this->assertSame([], $report->get('written', []));
		$this->assertFalse($report->get('powers.completed'));
	}

	/**
	 * Preserved fields are absent from the effective payload, not just the diff.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testDeferredFieldsAndNoOpWritesAreActuallyPreserved(): void
	{
		[$container, $load, $item, $db] = $this->prepared();
		$plan = $container->get('Extrusion.Registry.Plan');
		$plan->begin();
		$row = (object) ['guid' => $this->guid('power-b'), 'main_class_code' => '[[[CompiledOnlyCode]]]', 'description' => 'old'];
		$item->serve('power', $this->guid('power-b'), $row);
		$delta = $container->get('Extrusion.Resolver.Delta')->weigh('power', 'guid', $this->guid('power-b'),
			(object) ['guid' => $this->guid('power-b'), 'main_class_code' => 'compiled code', 'description' => 'new'], true, 'power|source');
		$this->assertSame(['description'], array_keys($delta['columns']));
		$this->assertSame(['description' => 'new', 'guid' => $this->guid('power-b')], $plan->writes()[0]['payload']);
		$plan->finish();
		$this->assertTrue($plan->begin());
		$container->get('Extrusion.Resolver.Delta')->weigh('power', 'guid', $this->guid('power-b'), clone $row, true, 'power|source');
		$this->assertSame([], $plan->writes());
		$this->assertCount(1, $plan->get('reads'));
		$this->assertSame([], $item->records());
		$container->get('Extrusion.Scope')->reset();
		$this->assertSame([], $plan->toArray(), 'Reset includes private approval and effective-write state.');
	}

	/**
	 * Two effective mutations of the same field cannot silently overwrite.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCompetingWritesToOneNaturalRecordRemainBlocked(): void
	{
		$plan = new Plan();
		$this->assertTrue($plan->begin());
		$this->assertFalse($plan->begin());
		$standing = (object) ['id' => 2, 'guid' => 'existing', 'name' => 'standing'];
		$delta = ['changed' => true, 'action' => 'update', 'origin' => 'source-a'];
		$plan->stage('joomla_component', 'guid', 'existing', (object) ['guid' => 'existing', 'name' => 'first'], $delta, $standing, 2);
		$delta['origin'] = 'source-b';
		$plan->stage('joomla_component', 'id', '2', (object) ['id' => 2, 'name' => 'second'], $delta, $standing, 2);
		$this->assertCount(1, $plan->writes());
		$this->assertCount(1, $plan->blockers());
		$this->assertSame('first', $plan->writes()[0]['payload']['name']);
		$this->assertNotSame(Plan::digest(['number' => 2]), Plan::digest(['number' => '2']));
		$this->assertSame(Plan::digest(['a' => 2, 'b' => 3]), Plan::digest(['b' => 3, 'a' => 2]));
	}

	/**
	 * Provide realistic native Data rows and a changed B source.
	 *
	 * @return  array  Container, raw loader, native Data fixture and database boundary.
	 * @since   6.2.0
	 */
	protected function prepared(): array
	{
		[$container, $load, $item, $db] = $this->engine();
		$this->sources();
		$this->writeTemporaryFile('lib/Acme.Joomla/src/Beta/Factory.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nclass Factory { public function value(): int { return 2; } }\n");
		foreach ([1 => 'a', 2 => 'b'] as $id => $key)
		{
			$native = (object) ['id' => 10 + $id, 'guid' => $this->guid('power-' . $key), 'name' => 'Factory',
				'type' => 'class', 'system_name' => 'Factory ' . strtoupper($key),
				'namespace' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory',
				'main_class_code' => 'public function value(): int { return 1; }', 'add_licensing_template' => 1];
			$item->identity('power', $native->guid, $native->id)->serve('power', $native->guid, $native);
			$component = (object) (['id' => $id] + $this->component($id === 1 ? 'alpha' : 'beta', 'component-' . $key, ['power-' . $key]));
			$component->php_preflight_install = base64_decode($component->php_preflight_install);
			$item->identity('joomla_component', $component->guid, $id)->serve('joomla_component', $component->guid, $component);
		}
		$container->get('Extrusion.Config')->set('libraries', [$this->temporaryPath('lib')]);

		return [$container, $load, $item, $db];
	}

	/**
	 * An old extrusion has literal namespace data and curated non-namespace fields.
	 *
	 * @return  array  Composed graph and its recorded external boundaries.
	 * @since   6.2.2
	 */
	protected function repairPrepared(): array
	{
		[$container, $load, $item, $db] = $this->prepared();
		$row = clone $item->table('power')->get($this->guid('power-b'));
		$row->namespace = '[[[NamespacePrefix]]]\\Joomla\\Beta.Factory';
		$row->description = 'A curated description that source code must not replace.';
		$row->licensing_template = 'A curated licence.';
		$row->add_licensing_template = 2;
		$row->power_version = '9.2.1';
		$row->use_selection = [['use' => $this->guid('curated-dependency'), 'as' => 'CuratedDependency']];
		$load->record('power', 30, [
			'guid' => $this->guid('curated-dependency'), 'name' => 'Dependency', 'type' => 'class',
			'namespace' => 'Acme\\Joomla\\Other.Dependency',
		]);
		$item->serve('power', $row->guid, $row);
		$load->record('power', 12, get_object_vars($row));
		$container->get('Extrusion.Config')->set('mode', 'update');

		return [$container, $load, $item, $db];
	}

	/**
	 * Compose the production graph with external I/O recorded.
	 *
	 * @param   bool  $reverse  Reverse catalogue insertion order.
	 *
	 * @return  array  Container, loader and Data pipeline fixture.
	 * @since   6.2.0
	 */
	protected function engine(bool $reverse = false): array
	{
		$load = new ExtrusionPowerLoadFixture();
		$item = new ExtrusionItemFixture();

		foreach ($reverse ? [2, 1] : [1, 2] as $id)
		{
			$key = $id === 1 ? 'a' : 'b';
			$load->record('joomla_component', $id, $this->component($id === 1 ? 'alpha' : 'beta', 'component-' . $key, ['power-' . $key]));
			$load->record('power', 10 + $id, [
				'guid' => $this->guid('power-' . $key), 'name' => 'Factory', 'type' => 'class',
				'system_name' => 'Factory ' . strtoupper($key),
				'namespace' => '[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Factory'
			]);
		}

		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');
		$container = new Container();
		$container->set('Load', $load, true);
		$container->set('Data.Item', $item, true);
		$container->set('Joomla.Database', $db, true);
		$container->set('Table', new Table(), true);
		$container->registerServiceProvider(new Registry());
		$container->registerServiceProvider(new Discovery());
		$container->registerServiceProvider(new Reader());
		$container->registerServiceProvider(new Resolver());
		$container->registerServiceProvider(new Writer());
		$container->registerServiceProvider(new Powers());
		$container->registerServiceProvider(new Extrusion());
		$container->get('Extrusion.Config')->set('component', 2)->set('sourceComponent', 2)->set('onExisting', 'update');

		return [$container, $load, $item, $db];
	}

	/**
	 * Declare a component with its actual compiler-token Power references.
	 *
	 * @param   string  $code    Concrete namespace code.
	 * @param   string  $name    Stable fixture identity.
	 * @param   array   $powers  Direct Power identities.
	 *
	 * @return  array  Raw database columns.
	 * @since   6.2.0
	 */
	protected function component(string $code, string $name, array $powers): array
	{
		return [
			'guid' => $this->guid($name), 'name_code' => $code,
			'add_namespace_prefix' => 1, 'namespace_prefix' => 'Acme',
			'php_preflight_install' => base64_encode(implode(';', array_map(fn (string $power): string =>
				'Super___' . str_replace('-', '_', $this->guid($power)) . '___Power', $powers)))
		];
	}

	/**
	 * Write the exact source layout the compiler's namespace placement describes.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function sources(): void
	{
		$this->writeTemporaryFile('lib/Acme.Joomla/src/Beta/Factory.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nclass Factory {}\n");
		$this->writeTemporaryFile('lib/Acme.Joomla/src/Beta/Consumer.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nuse Acme\\Joomla\\Beta\\Factory as BaseFactory;\nclass Consumer extends BaseFactory {}\n");
	}

	/**
	 * Find a source for an assertion without relying on its target or discovery order.
	 *
	 * @param   array   $classes  Source candidates keyed by stable source identity.
	 * @param   string  $name     The declared class name.
	 *
	 * @return  array  The matching source candidate.
	 * @since   6.2.0
	 */
	protected function source(array $classes, string $name): array
	{
		$matches = array_values(array_filter($classes, static fn (array $source): bool => $source['class'] === $name));
		$this->assertCount(1, $matches);

		return $matches[0];
	}

	/**
	 * Derive fixture identities with the production GUID contract.
	 *
	 * @param   string  $name  Stable fixture name.
	 *
	 * @return  string  A valid deterministic GUID.
	 * @since   6.2.0
	 */
	protected function guid(string $name): string
	{
		return (new Guid())->derive(['scoped-pipeline', $name]);
	}
}
