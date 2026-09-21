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
		$this->assertSame('foreign', $again['resolution']['write_scope']);
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
