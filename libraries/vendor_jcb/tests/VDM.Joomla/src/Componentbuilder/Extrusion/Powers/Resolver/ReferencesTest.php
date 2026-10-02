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


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry as JoomlaRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Config as CompilerConfig;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\External;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder as CompilerPlaceholder;
use VDM\Joomla\Componentbuilder\Compiler\Power\Selection;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\References;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Powers;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Component usage comes from stored references, not matching namespace words.
 *
 * @since  6.2.0
 */
#[CoversClass(References::class)]
#[CoversClass(Powers::class)]
#[UsesClass(External::class)]
#[UsesClass(CompilerPlaceholder::class)]
final class ReferencesTest extends JoomlaTestCase
{
	/**
	 * Stored external code remains missing evidence without being fetched.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testExternalCodeIsReportedWithoutResolvingItsRemoteContents(): void
	{
		$load = $this->fixture();
		$load->record('joomla_component', 1, [
			'guid' => $this->guid('component-a'),
			'add_php_preflight_install' => 1,
			'php_preflight_install' => base64_encode('[EXTERNALCODE=https://example.invalid/private.php]')
		]);
		$context = $this->graph($load)->context(1);
		$this->assertFalse($context['complete']);
		$this->assertSame(['external code unavailable during read-only discovery'], array_values($context['gaps']));
		$this->assertArrayNotHasKey($this->guid('power-a'), $context['powers']);
	}

	/**
	 * Compiling the resolver's own source must not consume its runtime detector.
	 *
	 * The real external-code pass scans plain stored PHP before PHP evaluates
	 * its concatenations. Its detector belongs to extrusion's runtime graph.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testCompilerExternalPassPreservesTheResolverSource(): void
	{
		$application = $this->createMock(CMSApplicationInterface::class);
		$application->method('getIdentity')->willReturn($this->createStub(User::class));
		$application->expects($this->never())->method('enqueueMessage');
		$this->setJoomlaApplication($application);
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->never())->method('getQuery');
		$placeholder = new CompilerPlaceholder(new CompilerConfig(new Input(), new JoomlaRegistry(), new JoomlaRegistry()));
		$external = new External($placeholder, $database);
		$source = file_get_contents((new ReflectionClass(References::class))->getFileName());
		$this->assertIsString($source);
		$this->assertSame($source, $external->set($source));
		$this->assertSame(0, $external->count(), 'Extrusion marker detection never becomes a compiler external-code request.');
	}

	/**
	 * Real metadata traverses owned children, token references and Power cycles.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testMetadataAndCompilerTokensDistinguishScopedAndSharedPowers(): void
	{
		foreach ([false, true] as $reverse)
		{
			$load = $this->fixture($reverse);
			$graph = $this->graph($load);
			$a = $graph->context(1);
			$b = $graph->context(2);
			$this->assertSame($this->guid('component-a'), $a['guid']);
			$this->assertTrue($a['complete']);
			$this->assertTrue($b['complete']);
			$this->assertTrue($a['powers'][$this->guid('power-a')]['direct']);
			$this->assertTrue($b['powers'][$this->guid('power-b')]['direct']);
			$this->assertArrayNotHasKey($this->guid('power-b'), $a['powers']);
			$this->assertArrayNotHasKey($this->guid('power-a'), $b['powers']);
			$this->assertFalse($b['powers'][$this->guid('shared')]['direct']);
			$this->assertFalse($b['powers'][$this->guid('leaf')]['direct']);
			$this->assertSame([1, 2], array_keys($graph->consumers($this->guid('shared'))));
			$this->assertSame([2], array_keys($graph->consumers($this->guid('power-b'))));
			$this->assertCount(10, $a['powers']);
			$this->assertCount(11, $b['powers']);
			$this->assertSame([], $graph->consumers($this->guid('joomla-only')));
			$this->assertStringContainsString('admin_view:', implode(',', array_keys($b['powers'][$this->guid('power-b')]['via'])));
		}
	}

	/**
	 * Cache resets see changed links even when component namespace values match.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testRefreshRebuildsConsumersAndFingerprints(): void
	{
		$load = $this->fixture();
		$graph = $this->graph($load);
		$graph->context(1);
		$graph->context(2);
		$before = $graph->fingerprint();
		$this->assertSame([1], array_keys($graph->consumers($this->guid('power-a'))));
		$load->record('admin_view', 6, [
			'guid' => $this->guid('view'),
			'php_getitem' => base64_encode($this->token('power-a'))
		]);
		$graph->refresh();
		$this->assertSame([], $graph->consumers($this->guid('power-a')));
		$graph->context(1);
		$graph->context(2);
		$this->assertSame([1, 2], array_keys($graph->consumers($this->guid('power-a'))));
		$this->assertSame([], $graph->consumers($this->guid('power-b')));
		$this->assertNotSame($before, $graph->fingerprint());
		$this->assertSame($graph->context(1), $graph->context(1));
	}

	/**
	 * Missing references and malformed stored relationships remain visible gaps.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testIncompleteReferencesAreNotReportedAsExclusiveOwnership(): void
	{
		$load = $this->fixture();
		$load->record('power', 11, [
			'guid' => $this->guid('power-a'), 'name' => 'Factory',
			'namespace' => 'Acme\\Library.Factory',
			'implements' => '{broken',
			'main_class_code' => base64_encode($this->token('missing'))
		]);
		$graph = $this->graph($load);
		$this->assertFalse($graph->context(1)['complete']);
		$this->assertCount(2, $graph->context(1)['gaps']);
		$this->assertArrayNotHasKey($this->guid('missing'), $graph->context(1)['powers']);
		$this->assertFalse($graph->context(999)['complete']);
		$this->assertTrue($graph->context(0)['complete']);
	}

	/**
	 * Invalid selector values are missing evidence, not absent relationships.
	 *
	 * @param   string  $selection  The stored Power selector value.
	 * @param   bool    $complete   Whether it represents a supported empty choice.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('selectorValues')]
	public function testMalformedPowerSelectorsDoNotProveCompleteUsage(string $selection, bool $complete): void
	{
		$load = $this->fixture();
		$load->record('power', 11, [
			'guid' => $this->guid('power-a'), 'name' => 'Factory',
			'namespace' => 'Acme\\Library.Factory',
			'use_selection' => json_encode([['use' => $selection, 'as' => 'Service']])
		]);
		$graph = $this->graph($load);
		$context = $graph->context(1);
		$this->assertSame($complete, $context['complete']);
		$this->assertFalse($graph->complete(), 'Selected-root evidence is never complete installation-wide consumer coverage.');
		$graph->contexts();
		$this->assertSame($complete, $graph->complete());
		$this->assertArrayHasKey($this->guid('power-a'), $context['powers']);
		$this->assertSame($complete ? [] : ['invalid reference'], array_values($context['gaps']));
		$this->assertTrue($graph->context(2)['complete']);
	}

	/**
	 * Consumers and approval evidence never bootstrap an installation audit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testColdFingerprintAndConsumersOnlyUseExplicitlyRequestedRoots(): void
	{
		$load = $this->fixture();
		$graph = $this->graph($load);
		$empty = $graph->fingerprint();
		$this->assertSame([], $graph->observed());
		$this->assertSame([], $graph->consumers($this->guid('shared')));
		$this->assertFalse($graph->complete());
		$this->assertSame([], $load->queries);
		$graph->context(2);
		$this->assertSame([2], array_keys($graph->observed()));
		$this->assertSame([2], array_keys($graph->consumers($this->guid('shared'))));
		$this->assertNotSame($empty, $graph->fingerprint());
		$queries = $load->queries;
		$this->assertSame($graph->context(2), $graph->context(2));
		$this->assertSame($graph->fingerprint(), $graph->fingerprint());
		$this->assertSame($queries, $load->queries);

		foreach ($queries as $query)
		{
			$this->assertNotSame([], $query['where']);

			if ($query['table'] === 'joomla_component')
			{
				$this->assertSame([2], $query['ids']);
			}
		}
	}

	/**
	 * Shared dependency edges, cycles and misses do not multiply node work.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testDiamondEdgesReuseDecodedNodesAndNegativeGuidReads(): void
	{
		$load = $this->fixture();
		$roots = [];

		for ($id = 20; $id < 60; $id++)
		{
			$roots[] = $this->token('parent-' . $id);
			$load->record('power', $id, [
				'guid' => $this->guid('parent-' . $id),
				'use_selection' => json_encode([
					['use' => $this->guid('shared')],
					['use' => $this->guid('missing')]
				])
			]);
		}

		$load->record('joomla_component', 1, [
			'guid' => $this->guid('component-a'),
			'php_preflight_install' => base64_encode(implode('\n', $roots))
		]);
		$graph = $this->graph($load);
		$context = $graph->context(1);
		$this->assertCount(49, $context['powers']);
		$this->assertCount(40, $context['gaps']);
		$this->assertCount(41, $context['powers'][$this->guid('shared')]['via']);
		$this->assertFalse($context['powers'][$this->guid('shared')]['direct']);
		$this->assertSame(50, $graph->diagnostics()['records']);
		$this->assertSame(82, $graph->diagnostics()['edges']);
		$powerReads = array_values(array_filter($load->queries, static fn (array $query): bool => $query['table'] === 'power'));
		$this->assertCount(50, $powerReads);
		$misses = array_values(array_filter($powerReads, static fn (array $query): bool => $query['ids'] === []));
		$this->assertCount(1, $misses);
		$this->assertSame(['a.guid' => $this->guid('missing')], $misses[0]['where']);
		$before = $graph->diagnostics();
		$this->assertSame($context, $graph->context(1));
		$this->assertSame($before, $graph->diagnostics());
	}

	/**
	 * Core custom-code and nested template routes resolve by their local keys.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNestedCoreCodeRoutesRemainBoundedAndExposeAliasCoverage(): void
	{
		$load = $this->fixture();
		$load->record('joomla_component', 1, [
			'guid' => $this->guid('component-a'),
			'add_php_preflight_install' => 1,
			'php_preflight_install' => base64_encode('[CUSTOMCODE=shared_function+example]'),
			'add_php_postflight_install' => 0,
			'php_postflight_install' => base64_encode($this->token('inactive'))
		]);
		$load->record('custom_code', 80, [
			'target' => 2, 'published' => 1, 'function_name' => 'shared_function',
			'code' => base64_encode('[CUSTOMCODE=81]')
		]);
		$load->record('custom_code', 81, [
			'target' => 2, 'published' => 1,
			'code' => base64_encode('$this->loadTemplate(\'details\')')
		]);
		$load->record('template', 90, [
			'guid' => $this->guid('details-template'), 'alias' => 'details',
			'template' => base64_encode($this->token('power-a'))
		]);
		$graph = $this->graph($load);
		$context = $graph->context(1);
		$this->assertArrayHasKey($this->guid('power-a'), $context['powers']);
		$this->assertArrayNotHasKey($this->guid('inactive'), $context['powers']);
		$this->assertSame(['normalized alias index unavailable'], array_values($context['gaps']));
		$this->assertFalse($context['complete']);

		foreach ($load->queries as $query)
		{
			$this->assertNotSame([], $query['where']);
		}
	}

	/**
	 * Generated-target changes replace injected-code evidence and approval data.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testLateComponentInjectionUsesGeneratedTargetAndInvalidatesOnChange(): void
	{
		$load = $this->fixture();
		$load->record('custom_code', 80, [
			'component' => $this->guid('component-a'), 'target' => 1,
			'published' => 1, 'joomla_version' => 5,
			'code' => base64_encode($this->token('power-b'))
		]);
		$config = new Config(['layout' => 'j5']);
		$graph = $this->graph($load, $config);
		$this->assertArrayHasKey($this->guid('power-b'), $graph->context(1)['powers']);
		$before = $graph->fingerprint();
		$config->set('layout', 'j6');
		$this->assertArrayNotHasKey($this->guid('power-b'), $graph->context(1)['powers']);
		$this->assertNotSame($before, $graph->fingerprint());
		$config->set('layout', 'auto');
		$this->assertFalse($graph->context(1)['complete']);
		$this->assertContains('generated Joomla target unavailable', $graph->context(1)['gaps']);
	}

	/**
	 * Disabled component Powers stay unloaded until an explicit build override.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testComponentPowerSwitchAndExplicitOverrideUseTheCompilerGate(): void
	{
		$load = $this->fixture();
		$load->record('joomla_component', 1, [
			'guid' => $this->guid('component-a'), 'add_powers' => 0,
			'php_preflight_install' => base64_encode($this->token('power-a'))
		]);
		$config = new Config(['powers' => 2]);
		$graph = $this->graph($load, $config);
		$this->assertSame(array_keys((new Selection())->utilityPowers()), array_keys(array_intersect_key((new Selection())->utilityPowers(), $graph->context(1)['powers'])));
		$this->assertCount(7, array_values(array_filter($load->queries, static fn (array $query): bool => $query['table'] === 'power')));
		$config->set('powers', 1);
		$this->assertArrayHasKey($this->guid('power-a'), $graph->context(1)['powers']);
		$config->set('powers', 0);
		$this->assertCount(7, $graph->context(1)['powers']);
	}

	/**
	 * Active inheritance and short custom fields follow the compiler branches.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testPowerInheritanceAndShortCodeFieldsUseSharedSelection(): void
	{
		$load = $this->fixture();
		$load->record('power', 11, [
			'guid' => $this->guid('power-a'), 'type' => 'class',
			'extends' => '-1', 'extends_custom' => $this->token('power-b'),
			'extendsinterfaces' => json_encode([$this->guid('inactive')]),
			'add_head' => 0, 'head' => base64_encode($this->token('inactive'))
		]);
		$graph = $this->graph($load);
		$this->assertArrayHasKey($this->guid('power-b'), $graph->context(1)['powers']);
		$this->assertArrayNotHasKey($this->guid('inactive'), $graph->context(1)['powers']);
		$this->assertTrue($graph->context(1)['complete']);
		$load->record('power', 11, [
			'guid' => $this->guid('power-a'), 'type' => 'interface',
			'extends' => $this->guid('inactive'),
			'extendsinterfaces' => json_encode([$this->guid('power-b')]),
			'implements' => json_encode(['-1']), 'implements_custom' => $this->token('shared')
		]);
		$graph->refresh();
		$this->assertArrayHasKey($this->guid('power-b'), $graph->context(1)['powers']);
		$this->assertArrayHasKey($this->guid('shared'), $graph->context(1)['powers']);
		$this->assertArrayNotHasKey($this->guid('inactive'), $graph->context(1)['powers']);
		$this->assertTrue($graph->context(1)['complete']);
	}

	/**
	 * First import still loads forced compiler utilities without a saved root.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFirstImportIncludesForcedUtilityRootsWithoutConsumerClaims(): void
	{
		$load = $this->fixture();
		$graph = $this->graph($load, new Config(['powers' => 0]));
		$context = $graph->context(0);
		$this->assertTrue($context['complete']);
		$this->assertCount(7, $context['powers']);

		foreach ((new Selection())->utilityPowers() as $guid => $force)
		{
			$this->assertTrue($context['powers'][$guid]['direct']);
			$this->assertSame(['compiler:utility' => true], $context['powers'][$guid]['via']);
			$this->assertSame([], $graph->consumers($guid));
		}

		foreach ($load->queries as $query)
		{
			$this->assertSame('power', $query['table']);
			$this->assertNotSame([], $query['where']);
		}
	}

	/**
	 * Explicit binding closures share work without inventing stored ownership.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testExplicitBindingClosureIsIncrementalAndPreservesMissingEvidence(): void
	{
		$load = $this->fixture();
		$graph = $this->graph($load);
		$first = $graph->power($this->guid('power-b'));
		$this->assertCount(3, $first['powers']);
		$this->assertTrue($first['complete']);
		$this->assertSame([], $graph->observed());
		$this->assertSame([], $graph->consumers($this->guid('power-b')));
		$before = $graph->diagnostics();
		$this->assertSame([], $graph->power($this->guid('shared'))['powers']);
		$this->assertSame($before, $graph->diagnostics());
		$missing = $graph->power($this->guid('missing'));
		$this->assertFalse($missing['complete']);
		$this->assertContains('missing', $missing['gaps']);
		$this->assertFalse($graph->power($this->guid('power-b'))['complete']);
		$fingerprint = $graph->fingerprint();
		$this->assertSame($fingerprint, $graph->fingerprint(true));
		$load->power(99, $this->guid('missing'), 'Missing', 'Acme\\Library.Missing');
		$this->assertNotSame($fingerprint, $graph->fingerprint(true));
		$this->assertSame($fingerprint, $graph->fingerprint());
	}

	/**
	 * Late compiler-generated dependencies retain their target and view gates.
	 *
	 * @param   int   $target     The requested generated Joomla major.
	 * @param   bool  $adminView  Whether the selected root includes an admin view.
	 * @param   bool  $enabled    Whether normal Power emission is enabled.
	 * @param   bool  $expected   Whether the compiler emits its Actions token.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('lateUtilityCases')]
	public function testLateGeneratedDependenciesFollowActualCompilerRoutes(int $target, bool $adminView, bool $enabled, bool $expected): void
	{
		$load = $this->fixture();
		$graph = $this->graph($load, new Config(['joomla_version' => $target, 'powers' => (int) $enabled]));
		$context = $graph->context($adminView ? 2 : 1);
		$actions = '7d95ce74-53dc-4672-bd8a-3b71cdacabea';
		$this->assertSame($expected, isset($context['powers'][$actions]));
		$this->assertTrue($context['complete']);

		if ($expected)
		{
			$this->assertTrue($context['powers'][$actions]['direct']);
			$this->assertStringContainsString(':compiler-generated', implode(',', array_keys($context['powers'][$actions]['via'])));
		}

		$this->assertSame($target >= 4 && $enabled, isset($graph->context(0)['powers'][$actions]));
	}

	/**
	 * Modern helper bridges are unconditional; Joomla 3 uses selected views.
	 *
	 * @return  array<string, array{int, bool, bool, bool}>  Compiler branch cases.
	 * @since   6.2.0
	 */
	public static function lateUtilityCases(): array
	{
		return [
			'j3-empty' => [3, false, true, false],
			'j3-admin-view' => [3, true, true, true],
			'j4-helper' => [4, false, true, true],
			'j5-helper' => [5, false, true, true],
			'j6-helper' => [6, false, true, true],
			'j3-disabled' => [3, true, false, false],
			'j6-disabled' => [6, true, false, false]
		];
	}

	/**
	 * Empty and custom selectors are valid; malformed identities are not.
	 *
	 * @return  array<string, array{string, bool}>  Independent selector cases.
	 * @since   6.2.0
	 */
	public static function selectorValues(): array
	{
		return [
			'malformed-guid' => ['not-a-guid', false],
			'truncated-guid' => ['aaaaaaaa-1111-4111-8111', false],
			'unsupported-negative' => ['-2', false],
			'fractional-id' => ['1.5', false],
			'empty' => ['', true],
			'none' => ['0', true],
			'custom' => ['-1', true]
		];
	}

	/**
	 * Build the real provider's graph with only its external database mocked.
	 *
	 * @param   ExtrusionPowerLoadFixture  $load  The declared raw records.
	 * @param   Config|null  $config  Optional generated-target context.
	 *
	 * @return  References  The actual production graph.
	 * @since   6.2.0
	 */
	protected function graph(ExtrusionPowerLoadFixture $load, ?Config $config = null): References
	{
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');
		$container = new Container();
		$container->set('Table', new Table());
		$container->set('Load', $load, true);
		$container->set('Joomla.Database', $db, true);

		if ($config !== null)
		{
			$container->set('Extrusion.Config', $config, true);
		}
		$container->registerServiceProvider(new Powers());

		return $container->get('Extrusion.Powers.Resolver.References');
	}

	/**
	 * Describe independent component-specific Powers with one shared cycle.
	 *
	 * @param   bool  $reverse  Whether to reverse declaration order.
	 *
	 * @return  ExtrusionPowerLoadFixture  The controlled database boundary.
	 * @since   6.2.0
	 */
	protected function fixture(bool $reverse = false): ExtrusionPowerLoadFixture
	{
		$load = new ExtrusionPowerLoadFixture();
		$id = 10000;

		foreach ((new Selection())->utilityPowers() as $guid => $force)
		{
			$load->record('power', ++$id, ['guid' => $guid, 'name' => 'Utility' . $id]);
		}

		foreach ((new Selection())->lateUtilityPowers(6) as $guid => $force)
		{
			$load->record('power', ++$id, ['guid' => $guid, 'name' => 'GeneratedUtility' . $id]);
		}

		foreach ($reverse ? [2, 1] : [1, 2] as $id)
		{
			$load->record('joomla_component', $id, [
				'guid' => $this->guid($id === 1 ? 'component-a' : 'component-b'),
				'name_code' => 'same',
				'php_preflight_install' => $id === 1 ? base64_encode($this->token('power-a')) : ''
			]);
		}

		$load->record('component_admin_views', 5, [
			'joomla_component' => $this->guid('component-b'),
			'addadmin_views' => json_encode([['adminview' => 6]])
		]);
		$load->record('admin_view', 6, [
			'guid' => $this->guid('view'),
			'php_getitem' => base64_encode($this->token('power-b') . '\n' . str_replace('Super', 'Joomla', $this->token('joomla-only')))
		]);
		$powers = [11 => 'power-a', 12 => 'power-b', 13 => 'shared', 14 => 'leaf'];

		foreach ($reverse ? array_reverse($powers, true) : $powers as $id => $name)
		{
			$next = $name === 'shared' ? 'leaf' : 'shared';
			$load->record('power', $id, [
				'guid' => $this->guid($name), 'name' => 'Factory',
				'namespace' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory',
				'use_selection' => json_encode([['use' => $this->guid($next), 'as' => 'Service']])
			]);
		}

		return $load;
	}

	/**
	 * Create deterministic valid identities for fixture records.
	 *
	 * @param   string  $name  The fixture identity name.
	 *
	 * @return  string  The version-five GUID.
	 * @since   6.2.0
	 */
	protected function guid(string $name): string
	{
		return (new Guid())->derive(['reference-test', $name]);
	}

	/**
	 * Encode the compiler's actual Power-token wrapper.
	 *
	 * @param   string  $name  The fixture Power name.
	 *
	 * @return  string  The supported token.
	 * @since   6.2.0
	 */
	protected function token(string $name): string
	{
		return 'Super___' . str_replace('-', '_', $this->guid($name)) . '___Power';
	}
}
