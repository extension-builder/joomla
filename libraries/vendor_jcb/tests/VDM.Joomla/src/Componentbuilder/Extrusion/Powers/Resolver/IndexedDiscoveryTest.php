<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    29th September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Powers\Resolver;


use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Compiler\Power\Selection;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Existing;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Identity;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\References;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Powers;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
use VDM\Tests\Support\TestCase;


/**
 * Selected-component work is independent of unrelated database rows.
 *
 * @since  6.2.0
 */
#[CoversClass(References::class)]
#[CoversClass(Existing::class)]
#[CoversClass(Identity::class)]
final class IndexedDiscoveryTest extends TestCase
{
	/**
	 * Cold matching must not fetch even one unrelated component or Power.
	 *
	 * @param   int  $unrelated  The unrelated catalogue size.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('catalogueSizes')]
	public function testSelectedRootDoesNotReadUnrelatedCatalogue(int $unrelated): void
	{
		[$container, $load] = $this->engine($unrelated);
		$identity = $container->get('Extrusion.Powers.Resolver.Identity');
		$result = $identity->resolve($this->source());
		$this->assertSame('matched', $result['status']);
		$this->assertSame($this->guid('selected-power'), $result['matched_guid']);
		$this->assertTrue($result['namespace']['round_trip']);
		$this->assertSame([], $result['blockers']);

		// Computing approval evidence is part of this same bounded operation.
		$identity->fingerprint();
		$seen = ['joomla_component' => [], 'power' => []];

		foreach ($load->queries as $query)
		{
			if (!array_key_exists($query['table'], $seen))
			{
				continue;
			}

			$this->assertNotSame([], $query['where'], 'Selected-root discovery performed a full ' . $query['table'] . ' read.');
			$seen[$query['table']] = array_merge($seen[$query['table']], $query['ids']);
		}

		$this->assertSame([1], array_values(array_unique($seen['joomla_component'])));
		$powerIds = array_values(array_unique($seen['power']));
		sort($powerIds);
		$this->assertSame([1, 10001, 10002, 10003, 10004, 10005, 10006, 10007], $powerIds);
	}

	/**
	 * More unrelated rows must not change the selected work set.
	 *
	 * @return  array<string, array{int}>  Independent catalogue sizes.
	 * @since   6.2.0
	 */
	public static function catalogueSizes(): array
	{
		return ['small' => [5], 'medium' => [50], 'large' => [500]];
	}

	/**
	 * An explicitly requested missing GUID is one bounded, negative-cached read.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testMissingGuidUsesOneDirectReadAndRetainsNegativeEvidence(): void
	{
		[$container, $load] = $this->engine(50);
		$existing = $container->get('Extrusion.Powers.Resolver.Existing');
		$missing = $this->guid('missing');
		$this->assertNull($existing->power($missing));
		$this->assertNull($existing->power($missing));
		$queries = array_values(array_filter($load->queries, static fn (array $query): bool => $query['table'] === 'power'));
		$this->assertCount(1, $queries);
		$this->assertArrayHasKey('a.guid', $queries[0]['where']);
		$this->assertSame([], $queries[0]['ids']);
	}

	/**
	 * Missing consumer coverage cannot silently trigger an installation audit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testConsumerEvidenceDoesNotScanUnselectedComponents(): void
	{
		[$container, $load] = $this->engine(50);
		$references = $container->get('Extrusion.Powers.Resolver.References');
		$references->context(1);
		$this->assertSame([1], array_keys($references->consumers($this->guid('selected-power'))));
		$this->assertFalse($references->complete(), 'One selected root cannot establish complete global consumer coverage.');

		foreach ($load->queries as $query)
		{
			if ($query['table'] === 'joomla_component')
			{
				$this->assertNotSame([], $query['where']);
				$this->assertSame([1], $query['ids']);
			}
		}
	}

	/**
	 * Two real candidates in the same selected bucket must remain ambiguous.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCandidateBucketsDoNotOverwriteCompetingGuids(): void
	{
		[$container, $load] = $this->engine(50);
		$load->record('power', 1001, [
			'guid' => $this->guid('competing-power'), 'name' => 'Factory', 'type' => 'class',
			'namespace' => 'Acme\\Joomla\\Target.Factory'
		]);
		$load->record('joomla_component', 1, [
			'guid' => $this->guid('selected-component'), 'name_code' => 'target',
			'add_namespace_prefix' => 1, 'namespace_prefix' => 'Acme',
			'add_php_preflight_install' => 1,
			'php_preflight_install' => base64_encode($this->token('selected-power') . '\n' . $this->token('competing-power'))
		]);
		$result = $container->get('Extrusion.Powers.Resolver.Identity')->resolve($this->source());
		$this->assertSame('ambiguous', $result['status']);
		$this->assertNull($result['write_guid']);
		$this->assertArrayHasKey($this->guid('selected-power'), $result['candidates']);
		$this->assertArrayHasKey($this->guid('competing-power'), $result['candidates']);
		$this->assertCount(2, $result['candidates']);
	}

	/**
	 * Bounded matching retains conservative mutation scope without a global audit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testLimitedConsumerCoverageRequiresReviewedMutation(): void
	{
		[$container] = $this->engine(50);
		$result = $container->get('Extrusion.Powers.Resolver.Identity')->resolve($this->source());
		$this->assertSame('matched', $result['status']);
		$this->assertSame('unestablished', $result['write_scope']);
		$this->assertSame('approval', $result['write_eligibility']);
		$this->assertFalse($result['consumer_coverage_complete']);
		$this->assertSame([1], array_keys($result['consumers']));
	}

	/**
	 * Review revalidation repeats the bounded reads without accepting a new snapshot.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFreshFingerprintDetectsNewCandidatesAndPreservesReviewedEvidence(): void
	{
		[$container, $load] = $this->engine(50);
		$identity = $container->get('Extrusion.Powers.Resolver.Identity');
		$identity->resolve($this->source());
		$before = $identity->fingerprint();
		$this->assertSame($before, $identity->fingerprint(true));
		$load->record('power', 1001, [
			'guid' => $this->guid('new-candidate'), 'name' => 'Factory', 'type' => 'class',
			'namespace' => 'Acme\\Joomla\\Target.Factory'
		]);
		$this->assertNotSame($before, $identity->fingerprint(true));
		$this->assertSame($before, $identity->fingerprint(), 'Revalidation must not replace the approved read snapshot.');

		foreach ($load->queries as $query)
		{
			if (in_array($query['table'], ['power', 'joomla_component'], true))
			{
				$this->assertNotSame([], $query['where'], 'Fresh approval validation must not rebuild a global catalogue.');
			}
		}
	}

	/**
	 * A previously absent identity is evidence that must be rechecked before writes.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFreshFingerprintDetectsNegativeGuidBecomingPresent(): void
	{
		[$container, $load] = $this->engine(50);
		$existing = $container->get('Extrusion.Powers.Resolver.Existing');
		$missing = $this->guid('missing');
		$this->assertNull($existing->power($missing));
		$before = $existing->fingerprint();
		$this->assertSame($before, $existing->fingerprint(true));
		$load->power(1001, $missing, 'Other', 'Acme\\Library.Other');
		$this->assertNotSame($before, $existing->fingerprint(true));
		$this->assertNull($existing->power($missing));
		$existing->refresh();
		$this->assertSame($missing, $existing->power($missing)['guid']);
	}

	/**
	 * The root's stored code changing invalidates all approved dependency evidence.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFreshFingerprintDetectsChangedRootReferences(): void
	{
		[$container, $load] = $this->engine(50);
		$identity = $container->get('Extrusion.Powers.Resolver.Identity');
		$identity->resolve($this->source());
		$before = $identity->fingerprint();
		$load->record('joomla_component', 1, [
			'guid' => $this->guid('selected-component'), 'name_code' => 'target',
			'add_namespace_prefix' => 1, 'namespace_prefix' => 'Acme',
			'php_preflight_install' => base64_encode($this->token('missing'))
		]);
		$this->assertNotSame($before, $identity->fingerprint(true));
		$identity->refresh();
		$this->assertFalse($container->get('Extrusion.Powers.Resolver.References')->context(1)['complete']);
	}

	/**
	 * Namespace variants of an invalid duplicate GUID cannot disappear from lookup.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testDuplicateGuidAtAnotherNamespaceBlocksInsteadOfCreatingReplacement(): void
	{
		[$container, $load] = $this->engine(50);
		$load->record('power', 1001, [
			'guid' => $this->guid('selected-power'), 'name' => 'Factory', 'type' => 'class',
			'namespace' => 'Acme\\Joomla\\Elsewhere.Factory'
		]);
		$result = $container->get('Extrusion.Powers.Resolver.Identity')->resolve($this->source());
		$this->assertSame('conflict', $result['status']);
		$this->assertNull($result['write_guid']);
		$this->assertArrayHasKey($this->guid('selected-power'), $result['candidates']);
		$this->assertFalse($result['candidates'][$this->guid('selected-power')]['compatible']);
	}

	/**
	 * A nested form value is evaluated only among the selected view's owned rows.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNestedChildSelectionsNeverReadForeignOwners(): void
	{
		[$container, $load] = $this->engine(50);
		$load->record('component_admin_views', 10, [
			'joomla_component' => $this->guid('selected-component'),
			'addadmin_views' => json_encode([['adminview' => 6]])
		]);
		$load->record('admin_view', 6, ['guid' => $this->guid('selected-view')]);
		$load->record('admin_fields', 7, [
			'admin_view' => $this->guid('foreign-view'),
			'addfields' => json_encode([['tab' => 6, 'field' => $this->guid('missing-field')]])
		]);
		$references = $container->get('Extrusion.Powers.Resolver.References');
		$this->assertTrue($references->context(1)['complete']);
		$this->assertSame([], $references->context(1)['gaps']);
		$this->assertSame($references->fingerprint(), $references->fingerprint(true));

		foreach ($load->queries as $query)
		{
			$this->assertNotSame([], $query['where']);

			if ($query['table'] === 'admin_fields')
			{
				$this->assertSame(['a.admin_view' => $this->guid('selected-view')], $query['where']);
				$this->assertSame([], $query['ids']);
			}
		}
	}

	/**
	 * Repeated same-context resolution does not perform additional database work.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testWarmResolutionReusesBothMatchesAndMissingEvidence(): void
	{
		[$container, $load] = $this->engine(500);
		$identity = $container->get('Extrusion.Powers.Resolver.Identity');
		$first = $identity->resolve($this->source());
		$count = count($load->queries);
		$this->assertSame($first, $identity->resolve($this->source()));
		$this->assertCount($count, $load->queries);
		$identity->refresh();
		$this->assertSame($first, $identity->resolve($this->source()));
		$this->assertGreaterThan($count, count($load->queries));
	}
	/**
	 * Compose real production providers above a passive database boundary.
	 *
	 * @param   int  $unrelated  Number of unrelated components and Powers.
	 *
	 * @return  array{Container, ExtrusionPowerLoadFixture}  The isolated operation.
	 * @since   6.2.0
	 */
	protected function engine(int $unrelated): array
	{
		$load = new ExtrusionPowerLoadFixture();
		$id = 10000;

		foreach ((new Selection())->utilityPowers() as $guid => $force)
		{
			$id++;
			$load->power($id, $guid, 'Utility' . $id, 'Compiler\\Utility.Utility' . $id);
		}

		foreach ((new Selection())->lateUtilityPowers(6) as $guid => $force)
		{
			$id++;
			$load->power($id, $guid, 'GeneratedUtility' . $id, 'Compiler\\Utility.GeneratedUtility' . $id);
		}

		$load->record('joomla_component', 1, [
			'guid' => $this->guid('selected-component'), 'name_code' => 'target',
			'add_namespace_prefix' => 1, 'namespace_prefix' => 'Acme',
			'add_php_preflight_install' => 1,
			'php_preflight_install' => base64_encode($this->token('selected-power'))
		]);
		$load->power(1, $this->guid('selected-power'), 'Factory', 'Acme\\Joomla\\Target.Factory');

		for ($id = 2; $id <= $unrelated + 1; $id++)
		{
			$load->component($id, $this->guid('component-' . $id), 'unrelated' . $id, 1, 'Other');
			$load->power($id, $this->guid('power-' . $id), 'Other' . $id, 'Other\\Library.Other' . $id);
		}

		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');
		$container = new Container();
		$container->set('Load', $load, true);
		$container->set('Joomla.Database', $db, true);
		$container->set('Extrusion.Config', new Config(['component' => 1]), true);
		$container->set('Extrusion.Registry.Report', new Report(), true);
		$container->set('Extrusion.Registry.Source', new Source(), true);
		$container->set('Extrusion.Resolver.Guid', new Guid(), true);
		$container->registerServiceProvider(new Powers());

		return [$container, $load];
	}

	/**
	 * One declaration whose source stays fixed while the catalogue grows.
	 *
	 * @return  array  The source identity and placement evidence.
	 * @since   6.2.0
	 */
	protected function source(): array
	{
		return [
			'source_key' => 'selected_factory', 'source_unit' => 'acme_joomla',
			'fqn' => 'Acme\\Joomla\\Target\\Factory', 'stored' => 'Acme\\Joomla\\Target.Factory',
			'placement_valid' => true, 'placement_evidence' => 'Target/Factory.php', 'type' => 'class'
		];
	}

	/**
	 * Stable fixture identities, independent of array order and row ids.
	 *
	 * @param   string  $name  The fixture identity.
	 *
	 * @return  string  A valid GUID.
	 * @since   6.2.0
	 */
	protected function guid(string $name): string
	{
		return (new Guid())->derive(['indexed-discovery', $name]);
	}

	/**
	 * Use the real compiler token spelling in the raw stored component.
	 *
	 * @param   string  $name  The fixture Power identity.
	 *
	 * @return  string  The compiler Power token.
	 * @since   6.2.0
	 */
	protected function token(string $name): string
	{
		return 'Super___' . str_replace('-', '_', $this->guid($name)) . '___Power';
	}
}
