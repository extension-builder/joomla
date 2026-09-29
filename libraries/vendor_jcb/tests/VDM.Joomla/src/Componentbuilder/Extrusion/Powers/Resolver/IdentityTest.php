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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Powers\Resolver;


use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Identity;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Namespacer;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Placeholders;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Powers;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
use VDM\Tests\Support\TestCase;


/**
 * Identity, ownership roles and write eligibility are independent decisions.
 *
 * @since  6.2.0
 */
#[CoversClass(Identity::class)]
#[CoversClass(Namespacer::class)]
#[CoversClass(Placeholders::class)]
final class IdentityTest extends TestCase
{
	/**
	 * Catalogue order and equal placeholder values do not erase component scope.
	 *
	 * @param   bool  $reverse  Reverse catalogue declaration order.
	 * @param   bool  $same     Give both components the same namespace values.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('catalogueOrders')]
	public function testReferencedFactoryWinsWithoutHidingOtherCandidates(bool $reverse, bool $same): void
	{
		[$identity, $load, $config, $names] = $this->engine($reverse, $same);
		$source = $this->source($same ? 'Same' : 'Beta');
		$result = $identity->resolve($source);
		$this->assertSame('matched', $result['status']);
		$this->assertSame($this->guid('power-b'), $result['matched_guid']);
		$this->assertSame('component-reference', $result['reason']);
		$this->assertSame('approval', $result['write_eligibility']);
		$this->assertSame('unestablished', $result['write_scope'], 'A selected root does not prove exclusive ownership.');
		$this->assertSame(2, count($result['candidates']));
		$this->assertTrue($result['namespace']['round_trip']);
		$this->assertTrue($result['namespace']['preserved']);
		$this->assertSame($this->template(), $result['namespace']['value']);
		$this->assertSame([], $identity->resolve($source)['blockers']);
		$this->assertArrayNotHasKey('map', $result['source_component']);
		$this->assertArrayNotHasKey('context', $result['namespace']);

		$config->set('component', 1);
		$identity->refresh();
		$a = $identity->resolve($this->source($same ? 'Same' : 'Alpha'));
		$this->assertSame($this->guid('power-a'), $a['matched_guid']);
		$this->assertNotSame($a['context_fingerprint'], $result['context_fingerprint']);
		$config->set('component', 2);
		$identity->refresh();
		$this->assertSame($result['matched_guid'], $identity->resolve($source)['matched_guid']);
	}

	/**
	 * Independent parameter cases ensure a failed assertion does not hide orders.
	 *
	 * @return  array<string, array{bool, bool}>  Catalogue and context permutations.
	 * @since   6.2.0
	 */
	public static function catalogueOrders(): array
	{
		return [
			'forward' => [false, false], 'reverse' => [true, false],
			'same-values-forward' => [false, true], 'same-values-reverse' => [true, true]
		];
	}

	/**
	 * A single generic foreign/unknown row never authorises a new replacement.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testGenericCandidatesWithoutTargetReferencesRequireARealDecision(): void
	{
		[$identity, $load] = $this->engine();
		$load->record('joomla_component', 2, $this->component('beta', 'component-b'));
		$identity->refresh();
		$result = $identity->resolve($this->source());
		$this->assertSame('ambiguous', $result['status']);
		$this->assertNull($result['matched_guid']);
		$this->assertNull($result['write_guid']);
		$this->assertSame('blocked', $result['write_eligibility']);
		$this->assertCount(2, $result['candidates']);
	}

	/**
	 * Incomplete foreign references cannot establish an exclusive mutation scope.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testMalformedForeignRelationshipRequiresScopeApprovalWithoutChangingTheTarget(): void
	{
		[$identity, $load] = $this->engine();
		$load->record('power', 11, $this->power('power-a', $this->template(), 'Factory A') + [
			'use_selection' => json_encode([['use' => 'unreadable-identity', 'as' => 'Unknown']])
		]);
		$identity->refresh();
		$result = $identity->resolve($this->source());
		$this->assertSame('matched', $result['status']);
		$this->assertSame($this->guid('power-b'), $result['matched_guid']);
		$this->assertSame('component-reference', $result['reason']);
		$this->assertSame('unestablished', $result['write_scope']);
		$this->assertSame('approval', $result['write_eligibility']);
	}

	/**
	 * Conflicting GUID metadata cannot bypass the target component's references.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSuppliedIdentityIsEvidenceToValidateRatherThanAnOverwriteBypass(): void
	{
		[$identity] = $this->engine();
		$source = $this->source() + ['source_guid' => $this->guid('power-a')];
		$result = $identity->resolve($source);
		$this->assertSame('conflict', $result['status']);
		$this->assertNull($result['write_guid']);
		$manual = $identity->resolve($source, ['action' => 'update', 'target' => $this->guid('power-b')]);
		$this->assertSame('matched', $manual['status']);
		$this->assertSame($this->guid('power-b'), $manual['write_guid']);
		$this->assertSame('explicit-pairing', $manual['reason']);
	}

	/**
	 * Global dependency permission remains separate from foreign-write scope.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testExplicitForeignPairingCarriesItsScopeAndNeverHidesTheActualTarget(): void
	{
		[$identity, $load, $config, $names, $references] = $this->engine();
		$references->context(1);
		$result = $identity->resolve($this->source(), ['action' => 'update', 'target' => $this->guid('power-a')]);
		$this->assertSame('matched', $result['status']);
		$this->assertSame($this->guid('power-a'), $result['target']['guid']);
		$this->assertSame('Factory A', $result['target']['system_name']);
		$this->assertSame('foreign', $result['write_scope']);
		$this->assertSame('approval', $result['write_eligibility']);
		$this->assertSame([1], array_keys($result['consumers']));
		$dependency = $identity->resolve($this->source('Alpha'), null, true);
		$this->assertSame($this->guid('power-a'), $dependency['matched_guid']);
		$this->assertNull($dependency['write_guid']);
		$this->assertSame('reference-only', $dependency['write_eligibility']);
	}

	/**
	 * A shared Power keeps its GUID and identifies the mutation's shared scope.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSharedDefinitionIsNotClonedPerComponent(): void
	{
		[$identity, $load, $config, $names, $references] = $this->engine();
		$load->record('joomla_component', 1, $this->component('alpha', 'component-a', 'power-b'));
		$identity->refresh();
		$references->context(1);
		$result = $identity->resolve($this->source());
		$this->assertSame($this->guid('power-b'), $result['matched_guid']);
		$this->assertSame('shared', $result['write_scope']);
		$this->assertSame('approval', $result['write_eligibility']);
		$this->assertSame([1, 2], array_keys($result['consumers']));
	}

	/**
	 * Mixed literal and custom-alias rows participate alongside generic rows.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCustomRootsUseTheirOwningContextsAndPreserveStoredRepresentations(): void
	{
		[$identity, $load] = $this->engine();
		$load->overrides($this->guid('component-a'), [['target' => 'Root', 'value' => 'Acme\\Other\\Alpha']]);
		$load->overrides($this->guid('component-b'), [['target' => 'Root', 'value' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]]']]);
		$load->record('power', 12, $this->power('power-b', '###Root###.Factory', 'Factory B'));
		$identity->refresh();
		$result = $identity->resolve($this->source());
		$this->assertSame($this->guid('power-b'), $result['matched_guid']);
		$this->assertSame('###Root###.Factory', $result['namespace']['value']);
		$this->assertTrue($result['namespace']['preserved']);
		$this->assertTrue($result['namespace']['round_trip']);
	}

	/**
	 * Valid relocation needs observed file-placement evidence independent of lookup.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testRoundTripFailureBlocksUntilARealRelocationIsEstablished(): void
	{
		[$identity] = $this->engine();
		$source = $this->source();
		$source['stored'] = 'Acme\\Joomla\\Beta\\Factory';
		$source['placement_evidence'] = '';
		$blocked = $identity->resolve($source);
		$this->assertSame('conflict', $blocked['status']);
		$this->assertNull($blocked['write_guid']);
		$source['placement_evidence'] = 'Acme.Joomla.Beta/src/Factory.php';
		$resolved = $identity->resolve($source);
		$this->assertSame('matched', $resolved['status']);
		$this->assertTrue($resolved['namespace']['relocation']);
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]]\\Factory', $resolved['namespace']['value']);
	}

	/**
	 * Component consumption alone does not parameterise a literal same-word branch.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testLiteralLibraryBranchRemainsLiteralEvenForItsNamesakeComponent(): void
	{
		[$identity, $load] = $this->engine();
		$load->record('joomla_component', 2, $this->component('registry', 'component-b', 'power-b'));
		$load->record('power', 12, $this->power('power-b', '[[[NamespacePrefix]]]\\Joomla\\Abstraction.Registry.Factory', 'Literal Factory'));
		$identity->refresh();
		$source = $this->source('Registry');
		$source['fqn'] = 'Acme\\Joomla\\Abstraction\\Registry\\Factory';
		$source['stored'] = 'Acme\\Joomla\\Abstraction.Registry.Factory';
		$result = $identity->resolve($source);
		$this->assertSame('matched', $result['status']);
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\Abstraction.Registry.Factory', $result['namespace']['value']);
		$this->assertFalse($result['candidates'][$this->guid('power-b')]['generic']);
	}

	/**
	 * Source-root evidence can recover portable new siblings without word matching.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testVerifiedRootBindingIsStructuralAndSourceUnitScoped(): void
	{
		[$identity, $load, $config, $names] = $this->engine();
		$source = $this->source();
		$context = $identity->sourceContext($source);
		$binding = $names->binding($source, $this->template(), $context, $this->guid('power-b'));
		$this->assertNotNull($binding);
		$new = $source;
		$new['source_key'] = 'source_new';
		$new['fqn'] = 'Acme\\Joomla\\Beta\\Nested\\Service';
		$new['stored'] = 'Acme\\Joomla\\Beta.Nested.Service';
		$new['binding'] = $binding;
		$result = $identity->resolve($new);
		$this->assertSame('new', $result['status']);
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Nested.Service', $result['namespace']['value']);
		$new['source_unit'] = 'unrelated_library';
		$this->assertSame('conflict', $identity->resolve($new)['status']);
		unset($new['binding']);
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\Beta.Nested.Service', $identity->resolve($new)['namespace']['value']);
	}

	/**
	 * Creation identity is stable by source and target scope, not namespace template.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNewDefinitionIdentityIsScopedAndExplicitCreateIsIdempotent(): void
	{
		[$identity, $load, $config] = $this->engine();
		$source = $this->source();
		$source['source_key'] = 'stable_new_declaration';
		$source['fqn'] = 'Acme\\Joomla\\Data\\Service';
		$source['stored'] = 'Acme\\Joomla\\Data.Service';
		$a = $identity->resolve($source);
		$this->assertSame('new', $a['status']);
		$this->assertSame($a['write_guid'], $identity->resolve($source)['write_guid']);
		$source['body'] = 'different contents';
		$this->assertSame($a['write_guid'], $identity->resolve($source)['write_guid']);
		$config->set('component', 1);
		$identity->refresh();
		$this->assertNotSame($a['write_guid'], $identity->resolve($source)['write_guid']);
		$config->set('component', 2);
		$load->record('power', 99, [
			'guid' => $a['write_guid'], 'name' => 'Service', 'type' => 'class',
			'namespace' => $a['namespace']['value']
		]);
		$identity->refresh();
		$again = $identity->resolve($source, ['action' => 'create']);
		$this->assertSame('matched', $again['status']);
		$this->assertSame($a['write_guid'], $again['write_guid']);
	}

	/**
	 * Same-name sources use FQN buckets rather than rescanning their dependency set.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCandidateWorkTracksReachableRecordsWithoutSourceCrossProducts(): void
	{
		$counts = [];

		foreach ([0, 400] as $unrelated)
		{
			[$identity, $load] = $this->engine();
			$tokens = [];
			$sources = [];

			for ($number = 0; $number < 64; $number++)
			{
				$guid = $this->guid('worker-' . $number);
				$namespace = 'Acme\\Joomla\\Area' . $number . '.Worker';
				$load->record('power', 100 + $number, [
					'guid' => $guid, 'name' => 'Worker', 'type' => 'class', 'namespace' => $namespace
				]);
				$tokens[] = 'Super___' . str_replace('-', '_', $guid) . '___Power';
				$sources[] = [
					'source_key' => 'worker-' . $number, 'source_unit' => 'Acme.Joomla',
					'fqn' => str_replace('.', '\\', $namespace), 'stored' => $namespace,
					'placement_valid' => true, 'type' => 'class'
				];
			}

			$component = $this->component('beta', 'component-b');
			$component['php_preflight_install'] = base64_encode(implode(';', $tokens));
			$load->record('joomla_component', 2, $component);

			for ($number = 0; $number < $unrelated; $number++)
			{
				$load->record('power', 1000 + $number, [
					'guid' => $this->guid('unrelated-' . $number), 'name' => 'Unrelated' . $number,
					'type' => 'class', 'namespace' => 'Other\\Library.Unrelated' . $number
				]);
				$load->record('joomla_component', 1000 + $number, $this->component('other' . $number, 'other-' . $number));
			}

			foreach ($sources as $number => $source)
			{
				$result = $identity->resolve($source);
				$this->assertSame($this->guid('worker-' . $number), $result['matched_guid']);
				$this->assertSame($result, $identity->resolve($source + ['action' => 'update', 'resolution' => $result]));
			}

			$this->assertSame(64, $identity->work()['indexed_records']);
			$this->assertSame(64, $identity->work()['candidate_evaluations']);
			$this->assertSame(64, $identity->work()['resolution_cache_hits']);
			$counts[] = count($load->queries);

			foreach ($load->queries as $query)
			{
				if (in_array($query['table'], ['power', 'joomla_component'], true))
				{
					$this->assertNotSame([], $query['where'], 'A cold lookup cannot scan either catalogue.');
				}
			}
		}

		$this->assertSame($counts[0], $counts[1], 'Unrelated records add no resolver queries.');
	}

	/**
	 * Missing fallback indexes require review and negative lookups remain fresh.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testUnknownAliasCoverageRequiresReviewAndNewCandidatesInvalidateFingerprint(): void
	{
		[$identity, $load] = $this->engine();
		$source = $this->source();
		$source['source_key'] = 'unlinked';
		$source['fqn'] = 'Acme\\Joomla\\Beta\\NewService';
		$source['stored'] = 'Acme\\Joomla\\Beta.NewService';
		$load->record('power', 55, [
			'guid' => $this->guid('opaque-alias'), 'name' => '[[[HiddenName]]]',
			'namespace' => '[[[HiddenRoot]]].[[[HiddenName]]]', 'type' => 'class'
		]);
		$result = $identity->resolve($source);
		$this->assertSame('new', $result['status']);
		$this->assertSame('approval', $result['write_eligibility']);
		$this->assertSame('unestablished', $result['write_scope']);
		$this->assertSame('selected-context-and-indexed-fallback', $result['candidate_coverage']);
		$before = $identity->fingerprint();
		$this->assertSame($before, $identity->revalidateFingerprint());
		$load->record('power', 57, [
			'guid' => $this->guid('unrelated-after-review'), 'name' => 'Unrelated',
			'namespace' => 'Other\\Library.Unrelated', 'type' => 'class'
		]);
		$this->assertSame($before, $identity->revalidateFingerprint(), 'Unrelated records are outside the approved read set.');

		$load->record('power', 56, [
			'guid' => $this->guid('new-collision'), 'name' => 'NewService',
			'namespace' => $source['stored'], 'type' => 'class'
		]);
		$this->assertNotSame($before, $identity->revalidateFingerprint());
		$this->assertSame($this->guid('new-collision'), $identity->resolve($source)['matched_guid']);
	}

	/**
	 * Only relevant template occupants and conventional literal records collide.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testOccupantBucketsRetainSelectedGuidWithoutForeignTemplateFalseCollision(): void
	{
		[$identity, $load, $config, $names] = $this->engine();
		$output = $names->output($this->template());
		$this->assertSame([$this->guid('power-b')], array_keys($identity->occupants($output)));
		$before = $identity->fingerprint();
		$this->assertSame($before, $identity->revalidateFingerprint());
		$load->record('power', 56, [
			'guid' => $this->guid('literal-collision'), 'name' => 'Factory',
			'namespace' => 'Acme\\Joomla\\Beta.Factory', 'type' => 'class'
		]);
		$this->assertNotSame($before, $identity->revalidateFingerprint());
		$this->assertCount(2, $identity->occupants($output));
	}

	/**
	 * Different namespaces under a corrupt duplicate GUID cannot look absent.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testDuplicateGuidVariantsRemainVisibleToIndexedNameLookups(): void
	{
		foreach ([false, true] as $reverse)
		{
			[$identity, $load] = $this->engine();
			$guid = $this->guid('duplicate');
			$rows = [
				['guid' => $guid, 'name' => 'Elsewhere', 'namespace' => 'Other\\Library.Elsewhere', 'type' => 'class'],
				['guid' => $guid, 'name' => 'Service', 'namespace' => 'Acme\\Joomla\\Beta.Service', 'type' => 'class']
			];

			foreach ($reverse ? array_reverse($rows) : $rows as $number => $row)
			{
				$load->record('power', 50 + $number, $row);
			}

			$source = $this->source();
			$source['source_key'] = 'service';
			$source['fqn'] = 'Acme\\Joomla\\Beta\\Service';
			$source['stored'] = 'Acme\\Joomla\\Beta.Service';
			$result = $identity->resolve($source);
			$this->assertSame('conflict', $result['status']);
			$this->assertFalse($result['candidates'][$guid]['compatible']);
			$this->assertNull($result['write_guid']);
		}
	}

	/**
	 * A newly assigned component identity invalidates an unsaved creation cache.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testUnsavedComponentGuidIsPartOfCreationIdentityAndReview(): void
	{
		[$identity, $load, $config] = $this->engine();
		$config->set('component', 0)->set('targetComponentGuid', $this->guid('unsaved-first'));
		$source = $this->source();
		$source['source_key'] = 'new';
		$source['fqn'] = 'Acme\\Library\\NewService';
		$source['stored'] = 'Acme\\Library.NewService';
		$first = $identity->resolve($source);
		$fingerprint = $identity->fingerprint();
		$config->set('targetComponentGuid', $this->guid('unsaved-second'));
		$second = $identity->resolve($source);
		$this->assertSame('new', $first['status']);
		$this->assertSame('new', $second['status']);
		$this->assertNotSame($first['write_guid'], $second['write_guid']);
		$this->assertNotSame($fingerprint, $identity->fingerprint());
	}

	/**
	 * Another component's unobserved alias cannot silently become a local alias.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testUnobservedCustomAliasRequiresExplicitPairing(): void
	{
		[$identity, $load] = $this->engine();
		$load->record('joomla_component', 2, $this->component('beta', 'component-b'));
		$load->overrides($this->guid('component-b'), [['target' => 'Root', 'value' => 'Acme\\Joomla\\Beta']]);
		$load->record('power', 11, $this->power('power-a', '###Root###.Factory', 'Unknown alias owner'));
		$load->record('power', 12, ['guid' => $this->guid('power-b'), 'name' => 'Other', 'namespace' => 'Other\\Library.Other']);
		$result = $identity->resolve($this->source());
		$this->assertSame('conflict', $result['status']);
		$this->assertSame('custom-alias-context-not-established', $result['candidates'][$this->guid('power-a')]['reason']);
		$reviewed = $identity->resolve($this->source(), ['action' => 'update', 'target' => $this->guid('power-a')]);
		$this->assertSame('matched', $reviewed['status']);
		$this->assertSame('approval', $reviewed['write_eligibility']);
	}

	/**
	 * Selected unlinked roots expose their transitive aliases without ownership claims.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFirstImportSelectedRootExpandsAliasDependenciesAndResetsOnNewDecisions(): void
	{
		[$identity, $load, $config, $names, $references] = $this->engine();
		$config->set('component', 0)->set('targetComponentGuid', $this->guid('first-import'));
		$load->placeholder(1, 'DependencyRoot', 'Acme\\Library');
		$load->record('power', 51, [
			'guid' => $this->guid('root'), 'name' => 'Root', 'type' => 'class',
			'namespace' => 'Acme\\Library.Root',
			'use_selection' => json_encode([['use' => $this->guid('child'), 'as' => 'Child']])
		]);
		$load->record('power', 52, [
			'guid' => $this->guid('child'), 'name' => '[[[HiddenName]]]', 'type' => 'class',
			'namespace' => '###DependencyRoot###.Child',
			'use_selection' => json_encode([['use' => $this->guid('root'), 'as' => 'Root']])
		]);
		$root = [
			'source_key' => 'root', 'source_unit' => 'Acme.Library', 'type' => 'class',
			'fqn' => 'Acme\\Library\\Root', 'stored' => 'Acme\\Library.Root', 'placement_valid' => true
		];
		$child = array_replace($root, [
			'source_key' => 'child', 'fqn' => 'Acme\\Library\\Child', 'stored' => 'Acme\\Library.Child'
		]);
		$this->assertNotSame('matched', $identity->resolve($child)['status']);
		$this->assertSame($this->guid('root'), $identity->resolve($root)['matched_guid']);
		$matched = $identity->resolve($child);
		$this->assertSame($this->guid('child'), $matched['matched_guid']);
		$this->assertSame('effective-reference', $matched['reason']);
		$this->assertSame('unestablished', $matched['write_scope']);
		$this->assertFalse($matched['candidates'][$this->guid('child')]['in_target']);
		$this->assertTrue($matched['candidates'][$this->guid('child')]['in_effective']);
		$this->assertSame([], $references->consumers($this->guid('child')));
		$this->assertSame(2, $identity->effectiveRevision());
		$this->assertSame(2, $identity->work()['indexed_records']);
		$output = $names->output('Acme\\Library.Child');
		$this->assertSame([$this->guid('child')], array_keys($identity->occupants($output)));
		$before = $identity->fingerprint();
		$this->assertSame($before, $identity->fingerprint(true));

		$identity->prepareDecisions(['root' => ['action' => 'ignore']]);
		$this->assertSame('ignored', $identity->resolve($root, ['action' => 'ignore'])['status']);
		$this->assertNotSame('matched', $identity->resolve($child)['status']);
		$this->assertSame(0, $identity->effectiveRevision());
		$this->assertSame([], $identity->occupants($output));
	}

	/**
	 * Growing effective root sets append to indexes instead of rebuilding each prefix.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFirstImportRootGrowthDoesNotReindexEarlierSelectedDefinitions(): void
	{
		[$identity, $load, $config] = $this->engine();
		$config->set('component', 0)->set('targetComponentGuid', $this->guid('first-import'));

		for ($number = 0; $number < 48; $number++)
		{
			$name = 'Service' . $number;
			$load->record('power', 100 + $number, [
				'guid' => $this->guid($name), 'name' => $name, 'type' => 'class',
				'namespace' => 'Acme\\Library.' . $name
			]);
		}

		for ($number = 0; $number < 48; $number++)
		{
			$name = 'Service' . $number;
			$result = $identity->resolve([
				'source_key' => $name, 'source_unit' => 'Acme.Library', 'type' => 'class',
				'fqn' => 'Acme\\Library\\' . $name, 'stored' => 'Acme\\Library.' . $name,
				'placement_valid' => true
			]);
			$this->assertSame($this->guid($name), $result['matched_guid']);
		}

		$this->assertSame(48, $identity->effectiveRevision());
		$this->assertSame(48, $identity->work()['indexed_records']);
		$this->assertSame(96, $identity->work()['candidate_evaluations']);
	}

	/**
	 * Build the actual services with a controlled database boundary.
	 *
	 * @param   bool  $reverse  Reverse database row order.
	 * @param   bool  $same     Use identical component namespace values.
	 *
	 * @return  array  Resolver, raw fixture, configuration and namespace service.
	 * @since   6.2.0
	 */
	protected function engine(bool $reverse = false, bool $same = false): array
	{
		$load = new ExtrusionPowerLoadFixture();

		foreach ($reverse ? [2, 1] : [1, 2] as $id)
		{
			$name = $id === 1 ? 'a' : 'b';
			$load->record('joomla_component', $id, $this->component($same ? 'same' : ($id === 1 ? 'alpha' : 'beta'), 'component-' . $name, 'power-' . $name));
			$load->record('power', 10 + $id, $this->power('power-' . $name, $this->template(), 'Factory ' . strtoupper($name)));
		}

		$config = new Config(['component' => 2]);
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');
		$container = new Container();
		$container->set('Load', $load, true);
		$container->set('Joomla.Database', $db, true);
		$container->set('Extrusion.Config', $config, true);
		$container->set('Extrusion.Registry.Report', new Report(), true);
		$container->set('Extrusion.Registry.Source', new Source(), true);
		$container->set('Extrusion.Resolver.Guid', new Guid(), true);
		$container->registerServiceProvider(new Powers());

		return [$container->get('Extrusion.Powers.Resolver.Identity'), $load, $config,
			$container->get('Extrusion.Powers.Resolver.Namespacer'), $container->get('Extrusion.Powers.Resolver.References')];
	}

	/**
	 * Describe one raw component and its real compiler Power token.
	 *
	 * @param   string       $code   The component code name.
	 * @param   string       $name   Its fixture identity.
	 * @param   string|null  $power  Optional referenced Power identity.
	 *
	 * @return  array  Raw component columns.
	 * @since   6.2.0
	 */
	protected function component(string $code, string $name, ?string $power = null): array
	{
		return [
			'guid' => $this->guid($name), 'name_code' => $code,
			'add_namespace_prefix' => 1, 'namespace_prefix' => 'Acme',
			'php_preflight_install' => $power === null ? '' : base64_encode('Super___' . str_replace('-', '_', $this->guid($power)) . '___Power')
		];
	}

	/**
	 * Describe a real existing Power without encoding identity in its name.
	 *
	 * @param   string  $name       The fixture GUID name.
	 * @param   string  $namespace  The stored namespace representation.
	 * @param   string  $label      The visible system name.
	 *
	 * @return  array  Raw Power columns.
	 * @since   6.2.0
	 */
	protected function power(string $name, string $namespace, string $label): array
	{
		return ['guid' => $this->guid($name), 'name' => 'Factory', 'type' => 'class', 'namespace' => $namespace, 'system_name' => $label];
	}

	/**
	 * Describe one raw file independently of a selected target Power GUID.
	 *
	 * @param   string  $component  The concrete component namespace segment.
	 *
	 * @return  array  The stable source descriptor.
	 * @since   6.2.0
	 */
	protected function source(string $component = 'Beta'): array
	{
		return [
			'source_key' => 'source_factory', 'source_unit' => 'acme_joomla',
			'fqn' => 'Acme\\Joomla\\' . $component . '\\Factory',
			'stored' => 'Acme\\Joomla\\' . $component . '.Factory',
			'placement_valid' => true, 'placement_evidence' => $component . '/Factory.php', 'type' => 'class'
		];
	}

	/**
	 * The deliberately colliding namespace template.
	 *
	 * @return  string  The reusable template, not a record identity.
	 * @since   6.2.0
	 */
	protected function template(): string
	{
		return '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory';
	}

	/**
	 * Derive independent fixture GUIDs.
	 *
	 * @param   string  $name  The fixture identity name.
	 *
	 * @return  string  A valid version-five GUID.
	 * @since   6.2.0
	 */
	protected function guid(string $name): string
	{
		return (new Guid())->derive(['identity-test', $name]);
	}
}
