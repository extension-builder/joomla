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
		$this->assertSame([], $report->get('plan.required_approvals'));
		$expected = $container->get('Extrusion.Registry.Plan')->writes();
		$this->assertCount(2, $expected);
		$config = $container->get('Extrusion.Config');
		$config->set('approvedPlan', $report->get('plan.fingerprint'));
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
	 * Shared mutation requires one acknowledgement bound to the actual preview.
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
		$this->assertSame(['shared'], $report->get('plan.required_approvals'));
		$fingerprint = $report->get('plan.fingerprint');
		$config->set('approvedPlan', $fingerprint);
		$this->assertSame('blocked', $engine->dryRun(false)->extrude()->get('plan.status'));
		$this->assertSame([], $item->records());
		$config->set('acknowledgeShared', true);
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
		$row = clone $item->table('power')->get($this->guid('power-b'));
		$row->main_class_code = 'An intervening developer edit';
		$item->serve('power', $this->guid('power-b'), $row);
		$this->assertFalse($container->get('Extrusion.Resolver.Commit')->apply());
		$this->assertSame([], $item->records());
		$this->assertSame('blocked', $container->get('Extrusion.Registry.Report')->get('plan.status'));
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
		$report = $container->get('Extrusion.Powers.Extruder')->extrude();
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
