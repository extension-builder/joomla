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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Powers;


use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Assembler;
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
 * Real file harvesting, scoped mapping and relationship assembly as one graph.
 *
 * @since  6.2.0
 */
#[CoversClass(Harvester::class)]
#[CoversClass(Assembler::class)]
final class ScopedPipelineTest extends FilesystemTestCase
{
	/**
	 * New compiler Powers use the named component without any existing root seed.
	 *
	 * @param   bool  $update  Select an existing component instead of entering its code.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	#[DataProvider('componentModes')]
	public function testAllowDeleteRestoresComponentNamespaceWithoutExistingPowers(bool $update): void
	{
		[$container, $load, $item] = $this->engine(false, false);
		$load->component(3, $this->guid('componentbuilder'), 'componentbuilder', 1, 'VDM');
		$load->params(['namespace_prefix' => 'VDM']);
		$container->get('Extrusion.Config')
			->set('mode', $update ? 'update' : 'create')
			->set('component', $update ? 3 : 0)
			->set('sourceComponent', $update ? 3 : 0)
			->set('componentCode', $update ? '' : 'componentbuilder')
			->set('libraries', [$this->temporaryPath('lib/VDM.Joomla')]);
		$this->writeTemporaryFile(
			'lib/VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api/Controller/AllowDelete.php',
			"<?php\nnamespace VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\Api\\Controller;\nfinal class AllowDelete {}\n"
		);

		$this->assertSame(1, $container->get('Extrusion.Powers.Harvester')->harvest());
		$this->assertSame(1, $container->get('Extrusion.Powers.Assembler')->assemble());
		$harvest = $container->get('Extrusion.Registry.Harvest');
		$source = $this->source($harvest->get('classes'), 'AllowDelete');
		$namespace = '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Compiler.Architecture.Api.Controller.AllowDelete';
		$this->assertSame('new', $source['resolution']['status']);
		$this->assertNull($source['matched_guid']);
		$this->assertSame([], $source['resolution']['candidates']);
		$this->assertTrue($source['resolution']['namespace']['round_trip']);
		$this->assertSame('component-code-name', $source['resolution']['namespace']['provenance']);
		$this->assertSame($namespace, $source['placeholder']);
		$this->assertSame($namespace, $harvest->get('resolved.' . $source['source_key'])->namespace);
		$this->assertSame(
			[
				'fqn' => 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\Api\\Controller\\AllowDelete',
				'path' => 'library:VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Api/Controller/AllowDelete.php',
			],
			$container->get('Extrusion.Powers.Resolver.Namespacer')->output($namespace)
		);
		$report = $container->get('Extrusion.Registry.Report');
		$this->assertSame(0, $report->get('counts.powers.binding_checks'));
		$this->assertSame(0, $report->get('counts.powers.binding_applications'));
		$this->assertSame([], $item->records(), 'Harvest and assembly remain a preview.');
	}

	/**
	 * Both component identification paths used by the Extrusion admin view.
	 *
	 * @return  array<string, array{bool}>  Update and create selections.
	 * @since   6.2.2
	 */
	public static function componentModes(): array
	{
		return ['selected update' => [true], 'entered create' => [false]];
	}

	/**
	 * The named component outranks inferred roles even when a custom alias hides it.
	 *
	 * @param   bool  $alias  Express the new namespace through a custom root alias.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	#[DataProvider('namespaceAliases')]
	public function testNamedComponentProposalOutranksAnotherSeededCoreRole(bool $alias): void
	{
		[$container, $load] = $this->engine();
		$legacy = '[[[NamespacePrefix]]]\\Joomla\\[[[Component]]].Factory';
		$load->record('power', 12, [
			'guid' => $this->guid('power-b'), 'name' => 'Factory', 'type' => 'class',
			'namespace' => $legacy,
		]);

		if ($alias)
		{
			$load->placeholder(1, 'TargetRoot', '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]]');
		}

		$this->sources();
		$container->get('Extrusion.Config')->set('libraries', [$this->temporaryPath('lib')]);
		$this->assertSame(2, $container->get('Extrusion.Powers.Harvester')->harvest());
		$this->assertSame(2, $container->get('Extrusion.Powers.Assembler')->assemble());
		$harvest = $container->get('Extrusion.Registry.Harvest');
		$factory = $this->source($harvest->get('classes'), 'Factory');
		$consumer = $this->source($harvest->get('classes'), 'Consumer');
		$expected = $alias ? '[[[TargetRoot]]].Consumer' : '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Consumer';

		$this->assertSame('matched', $factory['resolution']['status']);
		$this->assertSame($legacy, $factory['placeholder'], 'The matched seed retains its stored core role.');
		$this->assertSame('component-code-name', $consumer['resolution']['namespace']['provenance']);
		$this->assertSame($expected, $consumer['placeholder']);
		$this->assertSame($expected, $harvest->get('resolved.' . $consumer['source_key'])->namespace);
		$this->assertSame(
			'[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Consumer',
			$container->get('Extrusion.Powers.Resolver.Namespacer')->canonical($consumer['placeholder'])
		);
		$report = $container->get('Extrusion.Registry.Report');
		$this->assertSame(1, $report->get('counts.powers.binding_checks'), 'An independently matched alternative root was checked.');
		$this->assertSame(0, $report->get('counts.powers.binding_applications'), 'An inferred root cannot replace the named component role.');
	}

	/**
	 * Both direct and custom-aliased namespace representations.
	 *
	 * @return  array<string, array{bool}>  Namespace expression cases.
	 * @since   6.2.2
	 */
	public static function namespaceAliases(): array
	{
		return ['direct component role' => [false], 'aliased component role' => [true]];
	}

	/**
	 * Mapping follows B through aliases and inheritance in either discovery order.
	 *
	 * @param   bool  $reverse  Reverse database catalogue order.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('orders')]
	public function testMappingsAndSourceKeysSurvivePairingAndDiscoveryOrder(bool $reverse): void
	{
		[$container, $load, $item] = $this->engine($reverse);
		$this->sources();
		$config = $container->get('Extrusion.Config');
		$config->set('libraries', [$this->temporaryPath('lib')]);
		$harvester = $container->get('Extrusion.Powers.Harvester');
		$assembler = $container->get('Extrusion.Powers.Assembler');
		$harvest = $container->get('Extrusion.Registry.Harvest');
		$this->assertSame(2, $harvester->harvest());
		$this->assertSame(2, $assembler->assemble());
		$candidates = $harvest->get('classes');
		$factory = $this->source($candidates, 'Factory');
		$consumer = $this->source($candidates, 'Consumer');
		$this->assertSame($this->guid('power-b'), $factory['matched_guid']);
		$this->assertSame('Factory B', $factory['resolution']['target']['system_name']);
		$this->assertCount(2, $factory['resolution']['candidates']);
		$this->assertSame('[[[NamespacePrefix]]]\Joomla\[[[ComponentNamespace]]].Consumer', $consumer['placeholder']);
		$definition = $harvest->get('resolved.' . $consumer['source_key']);
		$this->assertSame($this->guid('power-b'), $definition->use_selection['use_selection0']['use']);
		$this->assertSame('BaseFactory', $definition->use_selection['use_selection0']['as']);
		$this->assertSame('BaseFactory', $definition->extends_custom);
		$this->assertSame([], $item->records(), 'Harvest and assembly cannot write.');

		// Selecting the identical files from a deeper ancestor keeps source keys.
		$config->set('libraries', [$this->temporaryPath('lib/Acme.Joomla/src/Beta')]);
		$harvester->harvest();
		$assembler->assemble();
		$this->assertSame(array_keys($candidates), array_keys($harvest->get('classes')));

		$container->get('Extrusion.Resolver.Pairing')->load(['power' => [
			$factory['source_key'] => ['action' => 'update', 'target' => $this->guid('power-a')]
		]]);
		$assembler->assemble();
		$again = $harvest->get('classes.' . $factory['source_key']);
		$this->assertSame($this->guid('power-a'), $again['matched_guid']);
		$this->assertSame('unestablished', $again['resolution']['write_scope']);
		$this->assertFalse($again['resolution']['consumer_coverage_complete']);
		$this->assertSame('blocked', $again['resolution']['write_eligibility']);
		$this->assertContains(
			'An existing Power occupies the compiled class or file path: ' . $this->guid('power-b'),
			$again['resolution']['blockers']
		);
		$this->assertSame([], $harvest->get('resolved', []), 'The still-selected B definition occupies this destination.');

		// Removing the standing dependency is separate evidence; a manual GUID
		// must never implicitly erase an existing compiled occupant.
		$load->record('joomla_component', 2, $this->component('beta', 'component-b', []));
		$harvester->harvest();
		$assembler->assemble();
		$again = $harvest->get('classes.' . $factory['source_key']);
		$this->assertSame($this->guid('power-a'), $again['matched_guid']);
		$this->assertSame('approval', $again['resolution']['write_eligibility']);
		$this->assertSame($this->guid('power-a'), $harvest->get('resolved.' . $consumer['source_key'])->use_selection['use_selection0']['use']);
	}

	/**
	 * An unresolved local Factory blocks its dependants instead of becoming raw PHP.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAmbiguousDependenciesBlockEveryAffectedSource(): void
	{
		[$container, $load] = $this->engine();
		$this->sources();
		$load->record('joomla_component', 2, $this->component('beta', 'component-b', ['power-a', 'power-b']));
		$container->get('Extrusion.Config')->set('libraries', [$this->temporaryPath('lib')]);
		$container->get('Extrusion.Powers.Harvester')->harvest();
		$this->assertSame(0, $container->get('Extrusion.Powers.Assembler')->assemble());
		$harvest = $container->get('Extrusion.Registry.Harvest');
		$this->assertCount(2, $harvest->get('classes'));
		$this->assertSame([], $harvest->get('resolved', []));
		$this->assertCount(2, $container->get('Extrusion.Registry.Report')->get('powers.blocked'));
		$this->assertSame('ambiguous', $this->source($harvest->get('classes'), 'Factory')['resolution']['status']);
		$this->assertSame('conflict', $this->source($harvest->get('classes'), 'Consumer')['resolution']['status']);
	}

	/**
	 * Duplicate physical declarations remain visible and contradictory files block.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testDuplicateSourceObservationsCannotOverwriteEachOther(): void
	{
		[$container] = $this->engine();
		$this->sources();
		$this->writeTemporaryFile('copy/Acme.Joomla/src/Beta/Factory.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nclass Factory { public function different() {} }\n");
		$container->get('Extrusion.Config')->set('libraries', [$this->temporaryPath('lib'), $this->temporaryPath('copy')]);
		$container->get('Extrusion.Powers.Harvester')->harvest();
		$container->get('Extrusion.Powers.Assembler')->assemble();
		$classes = $container->get('Extrusion.Registry.Harvest')->get('classes');
		$this->assertCount(2, $classes);
		$factory = $this->source($classes, 'Factory');
		$this->assertCount(2, $factory['occurrences']);
		$this->assertSame('conflict', $factory['resolution']['status']);
		$this->assertSame('conflict', $this->source($classes, 'Consumer')['resolution']['status']);
	}

	/**
	 * Bounded optional metadata cannot override a contradictory declaration.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testContradictoryMetadataBlocksRatherThanAuthorisingAnUpdate(): void
	{
		[$container] = $this->engine();
		$this->writeTemporaryFile('metadata/code.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nclass Factory {}\n");
		$this->writeTemporaryFile('metadata/settings.json', json_encode([
			'guid' => $this->guid('power-b'), 'name' => 'Different',
			'namespace' => 'Acme\\Joomla\\Beta.Factory', 'type' => 'class'
		], JSON_THROW_ON_ERROR));
		$container->get('Extrusion.Config')->set('libraries', [$this->temporaryPath('metadata')]);
		$container->get('Extrusion.Powers.Harvester')->harvest();
		$this->assertSame(0, $container->get('Extrusion.Powers.Assembler')->assemble());
		$this->assertNotEmpty($container->get('Extrusion.Registry.Report')->get('powers.blocked'));
	}

	/**
	 * Different native namespaces must not write the same compiler destination.
	 *
	 * @param   bool  $reverse  Reverse library discovery order.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('orders')]
	public function testDistinctNamespacesCannotCollideAtOneNativeOutputPath(bool $reverse): void
	{
		[$container, $load, $item] = $this->engine();
		$roots = [];

		foreach (['First', 'Second'] as $area)
		{
			$root = 'copies/Acme.Component.Beta.Administrator.' . $area;
			$this->writeTemporaryFile($root . '/src/Widget.php', "<?php\nnamespace Acme\\Component\\Beta\\Administrator\\" . $area . ";\nclass Widget {}\n");
			$roots[] = $this->temporaryPath($root);
		}

		$container->get('Extrusion.Config')->set('libraries', $reverse ? array_reverse($roots) : $roots);
		$container->get('Extrusion.Powers.Harvester')->harvest();
		$this->assertSame(0, $container->get('Extrusion.Powers.Assembler')->assemble());
		$classes = $container->get('Extrusion.Registry.Harvest')->get('classes');
		$this->assertCount(2, $classes, 'Neither source disappears into a guessed target.');
		$this->assertCount(2, array_unique(array_column($classes, 'fqn')));

		foreach ($classes as $source)
		{
			$this->assertSame('conflict', $source['resolution']['status']);
			$this->assertContains('Distinct Power definitions produce the same compiler file path.', $source['resolution']['blockers']);
		}

		$this->assertSame([], $item->records());
	}

	/**
	 * Fresh byte reads reuse lexical work without retaining contextual decisions.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testParsingReuseTracksContentAndTheExplicitRunBoundary(): void
	{
		[$container] = $this->engine();
		$this->sources();
		$config = $container->get('Extrusion.Config');
		$config->set('libraries', [$this->temporaryPath('lib')]);
		$harvester = $container->get('Extrusion.Powers.Harvester');
		$report = $container->get('Extrusion.Registry.Report');
		$harvest = $container->get('Extrusion.Registry.Harvest');
		$this->assertSame(2, $harvester->harvest());
		$this->assertSame(2, $report->get('counts.powers.parsed'));
		$this->assertSame(0, $report->get('counts.powers.parse_reused'));
		$first = $this->source($harvest->get('classes'), 'Factory');

		$config->set('component', 1)->set('sourceComponent', 1);
		$this->assertSame(2, $harvester->harvest());
		$this->assertSame(0, $report->get('counts.powers.parsed'));
		$this->assertSame(2, $report->get('counts.powers.parse_reused'));
		$this->assertSame(1, $this->source($harvest->get('classes'), 'Factory')['source_component_id']);

		$this->writeTemporaryFile('lib/Acme.Joomla/src/Beta/Factory.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nclass Factory { public function revised() {} }\n");
		$this->assertSame(2, $harvester->harvest());
		$this->assertSame(1, $report->get('counts.powers.parsed'));
		$this->assertSame(1, $report->get('counts.powers.parse_reused'));
		$changed = $this->source($harvest->get('classes'), 'Factory');
		$this->assertSame($first['source_key'], $changed['source_key']);
		$this->assertStringContainsString('revised', $changed['body']);
		$this->assertNotSame($first['occurrences'][0]['snapshot'], $changed['occurrences'][0]['snapshot']);

		$container->get('Extrusion.Scope')->reset();
		$this->assertSame([], $container->get('Extrusion.Registry.Parsed')->toArray());
		$config->set('libraries', [$this->temporaryPath('lib')])->set('component', 2)->set('sourceComponent', 2);
		$this->assertSame(2, $harvester->harvest());
		$this->assertSame(2, $report->get('counts.powers.parsed'));
		$this->assertSame(0, $report->get('counts.powers.parse_reused'));
	}

	/**
	 * Identical PHP bytes never suppress metadata evidence from a second copy.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testReusedParsingRetainsConflictingMetadataAndEveryObservedFile(): void
	{
		[$container] = $this->engine();
		$roots = [];

		foreach (['power-b', 'power-a'] as $index => $power)
		{
			$root = 'metadata' . $index;
			$this->writeTemporaryFile($root . '/code.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nclass Factory {}\n");
			$this->writeTemporaryFile($root . '/settings.json', json_encode([
				'guid' => $this->guid($power), 'name' => 'Factory', 'type' => 'class',
				'namespace' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory'
			], JSON_THROW_ON_ERROR));
			$roots[] = $this->temporaryPath($root);
		}

		$container->get('Extrusion.Config')->set('libraries', $roots);
		$container->get('Extrusion.Powers.Harvester')->harvest();
		$this->assertSame(0, $container->get('Extrusion.Powers.Assembler')->assemble());
		$factory = $this->source($container->get('Extrusion.Registry.Harvest')->get('classes'), 'Factory');
		$this->assertCount(2, $factory['occurrences']);
		$this->assertCount(2, $factory['metadata_files']);
		$this->assertContains('Duplicate source declarations contain conflicting Power GUID metadata.', $factory['resolution']['blockers']);
		$this->assertSame(1, $container->get('Extrusion.Registry.Report')->get('counts.powers.parsed'));
		$this->assertSame(1, $container->get('Extrusion.Registry.Report')->get('counts.powers.parse_reused'));
	}

	/**
	 * Named new Powers avoid root inference while shared dependencies keep bounded work.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNamedComponentNamespacesAndSharedDependenciesHaveBoundedWork(): void
	{
		[$container, $load, $item] = $this->engine();
		$this->sources();
		$powers = ['power-b'];

		for ($index = 0; $index < 40; $index++)
		{
			$name = 'Existing' . $index;
			$powers[] = $name;
			$load->record('power', 100 + $index, [
				'guid' => $this->guid($name), 'name' => $name, 'type' => 'class',
				'namespace' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].' . $name
			]);
			$this->writeTemporaryFile('lib/Acme.Joomla/src/Beta/' . $name . '.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nclass " . $name . " {}\n");
		}

		for ($index = 0; $index < 30; $index++)
		{
			$name = 'Fresh' . $index;
			$this->writeTemporaryFile('lib/Acme.Joomla/src/Beta/' . $name . '.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nuse Acme\\Joomla\\Beta\\Factory;\nclass " . $name . " extends Factory {}\n");
		}

		$load->record('joomla_component', 2, $this->component('beta', 'component-b', $powers));
		$container->get('Extrusion.Config')->set('libraries', [$this->temporaryPath('lib')]);
		$this->assertSame(72, $container->get('Extrusion.Powers.Harvester')->harvest());
		$this->assertSame(72, $container->get('Extrusion.Powers.Assembler')->assemble());
		$report = $container->get('Extrusion.Registry.Report');
		$this->assertSame(41, $report->get('counts.powers.binding_checks'));
		$this->assertSame(0, $report->get('counts.powers.binding_applications'));
		$this->assertSame(1, $report->get('counts.powers.dependency_lookups'));
		$this->assertGreaterThanOrEqual(30, $report->get('counts.powers.dependency_reused'));
		$harvest = $container->get('Extrusion.Registry.Harvest');
		$source = $this->source($harvest->get('classes'), 'Fresh29');
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Fresh29', $source['placeholder']);
		$this->assertSame($this->guid('power-b'), $harvest->get('resolved.' . $source['source_key'])->extends);
		$this->assertSame([], $item->records());
	}

	/**
	 * A blocked node invalidates a cyclic dependent graph with bounded edge visits.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testBlockedSourcesPropagateThroughCyclesWithoutRepeatedGraphScans(): void
	{
		[$container] = $this->engine();
		$this->sources();
		$this->writeTemporaryFile('copy/Acme.Joomla/src/Beta/Factory.php', "<?php\nnamespace Acme\\Joomla\\Beta;\nclass Factory { public function contradiction() {} }\n");

		for ($index = 0; $index < 30; $index++)
		{
			$name = 'Cycle' . $index;
			$next = 'Cycle' . (($index + 1) % 30);
			$imports = 'use Acme\\Joomla\\Beta\\' . $next . ";\n"
				. ($index === 0 ? "use Acme\\Joomla\\Beta\\Factory;\n" : '');
			$this->writeTemporaryFile('lib/Acme.Joomla/src/Beta/' . $name . '.php', "<?php\nnamespace Acme\\Joomla\\Beta;\n" . $imports . 'class ' . $name . " {}\n");
		}

		$container->get('Extrusion.Config')->set('libraries', [$this->temporaryPath('lib'), $this->temporaryPath('copy')]);
		$container->get('Extrusion.Powers.Harvester')->harvest();
		$this->assertSame(0, $container->get('Extrusion.Powers.Assembler')->assemble());
		$this->assertCount(32, $container->get('Extrusion.Registry.Report')->get('powers.blocked'));
		$this->assertLessThanOrEqual(32, $container->get('Extrusion.Registry.Report')->get('counts.powers.blocked_dependency_edges'));
	}

	/**
	 * Earlier alias sources see a later reviewed root's complete dependency graph.
	 *
	 * @param   bool  $reverse  Reverse source library discovery order.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('orders')]
	public function testFirstImportAliasChildResolvesBeforeItsReviewedRootInEitherDiscoveryOrder(bool $reverse): void
	{
		[$container, $load, $item] = $this->engine();
		$load->placeholder(1, 'DependencyRoot', 'Acme\\Library\\Branch');
		$load->record('power', 51, [
			'guid' => $this->guid('first-root'), 'name' => 'Root', 'type' => 'class',
			'namespace' => 'Acme\\Library\\Branch.Root',
			'use_selection' => json_encode([['use' => $this->guid('first-child'), 'as' => 'LinkedChild']])
		]);
		$load->record('power', 52, [
			'guid' => $this->guid('first-child'), 'name' => '[[[HiddenName]]]', 'type' => 'class',
			'namespace' => '###DependencyRoot###.ChildA',
			'use_selection' => json_encode([['use' => $this->guid('first-root'), 'as' => 'Root']])
		]);
		$this->writeTemporaryFile('child/Acme.Library/src/Branch/ChildA.php', "<?php\nnamespace Acme\\Library\\Branch;\nclass ChildA {}\n");
		$this->writeTemporaryFile('root/Acme.Library/src/Branch/Root.php', "<?php\nnamespace Acme\\Library\\Branch;\nuse Acme\\Library\\Branch\\ChildA as LinkedChild;\nclass Root extends LinkedChild {}\n");
		$libraries = [$this->temporaryPath('child'), $this->temporaryPath('root')];
		$container->get('Extrusion.Config')->set('component', 0)->set('sourceComponent', 0)
			->set('targetComponentGuid', $this->guid('first-import'))
			->set('libraries', $reverse ? array_reverse($libraries) : $libraries);
		$this->assertSame(2, $container->get('Extrusion.Powers.Harvester')->harvest());
		$harvest = $container->get('Extrusion.Registry.Harvest');
		$child = $this->source($harvest->get('classes'), 'ChildA');
		$root = $this->source($harvest->get('classes'), 'Root');
		$this->assertLessThan(0, strcmp($child['source_key'], $root['source_key']), 'The alias source must resolve before the reviewed root.');

		// A new verdict discards the harvest's effective closure. The first
		// assembly pass therefore encounters the alias before selecting its root.
		$container->get('Extrusion.Resolver.Pairing')->load(['power' => [
			$root['source_key'] => ['action' => 'update', 'target' => $this->guid('first-root')]
		]]);
		$assembler = $container->get('Extrusion.Powers.Assembler');
		$this->assertSame(2, $assembler->assemble());
		$child = $harvest->get('classes.' . $child['source_key']);
		$this->assertSame($this->guid('first-child'), $child['matched_guid']);
		$this->assertSame('effective-reference', $child['resolution']['reason']);
		$this->assertSame('###DependencyRoot###.ChildA', $child['placeholder']);
		$this->assertObjectNotHasProperty('namespace', $harvest->get('resolved.' . $child['source_key']), 'A matched alias keeps its stored representation.');
		$this->assertSame('unestablished', $child['resolution']['write_scope']);
		$this->assertSame('approval', $child['resolution']['write_eligibility']);
		$this->assertFalse($child['resolution']['candidates'][$this->guid('first-child')]['in_target']);
		$this->assertTrue($child['resolution']['candidates'][$this->guid('first-child')]['in_effective']);
		$definition = $harvest->get('resolved.' . $root['source_key']);
		$this->assertSame($this->guid('first-child'), $definition->use_selection['use_selection0']['use']);
		$this->assertSame('LinkedChild', $definition->use_selection['use_selection0']['as']);
		$this->assertSame(2, $assembler->assemble(), 'An unchanged review keeps the same settled graph.');
		$this->assertSame($this->guid('first-child'), $harvest->get('classes.' . $child['source_key'])['matched_guid']);
		$this->assertSame([], $item->records());
	}

	/**
	 * Both deterministic database orders.
	 *
	 * @return  array  Named order cases.
	 * @since   6.2.0
	 */
	public static function orders(): array
	{
		return ['forward' => [false], 'reverse' => [true]];
	}

	/**
	 * Compose the production graph with external I/O recorded.
	 *
	 * @param   bool  $reverse    Reverse catalogue insertion order.
	 * @param   bool  $catalogue  Seed the component and Power catalogue.
	 *
	 * @return  array  Container, loader and Data pipeline fixture.
	 * @since   6.2.0
	 */
	protected function engine(bool $reverse = false, bool $catalogue = true): array
	{
		$load = new ExtrusionPowerLoadFixture();
		$item = new ExtrusionItemFixture();
		$order = $reverse ? [2, 1] : [1, 2];

		foreach ($catalogue ? $order : [] as $id)
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

		return [$container, $load, $item];
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
