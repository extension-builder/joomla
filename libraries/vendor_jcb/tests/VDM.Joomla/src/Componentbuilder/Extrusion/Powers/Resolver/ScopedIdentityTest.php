<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    16th September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Powers\Resolver;


use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Powers;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Existing;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Namespacer;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Placeholders;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
use VDM\Tests\Support\TestCase;


/**
 * Namespace words and namespace templates are not definition identities.
 *
 * @since  6.2.0
 */
#[CoversClass(Existing::class)]
#[CoversClass(Namespacer::class)]
#[CoversClass(Placeholders::class)]
final class ScopedIdentityTest extends TestCase
{
	/**
	 * Literal repairs preserve stored separators, custom aliases and class names.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testNamespaceRepairPreservesSymbolicRepresentationsAndExactSegments(): void
	{
		[$names, $load] = $this->repairNamespacer();
		$load->placeholder(1, 'VendorRoot', 'Acme\\Joomla');
		$load->placeholder(2, 'ComponentRoot', 'Acme\\Joomla\\Beta');
		$load->placeholder(3, 'Branch', 'Deep');
		$cases = [
			'Acme\\Joomla\\Beta.Factory' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory',
			'[[[NamespacePrefix]]]\\Joomla\\Beta.Factory' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory',
			'###NamespacePrefix###\\Joomla\\Beta.Factory' => '###NamespacePrefix###\\Joomla\\[[[ComponentNamespace]]].Factory',
			'[[[VendorRoot]]]\\Beta.Factory' => '[[[VendorRoot]]]\\[[[ComponentNamespace]]].Factory',
			'###VendorRoot###\\Beta.Factory' => '###VendorRoot###\\[[[ComponentNamespace]]].Factory',
			'[[[ComponentRoot]]].Factory' => '[[[ComponentRoot]]].Factory',
			'[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory',
			'[[[NamespacePrefix]]]\\Joomla\\###Branch###.Beta.Factory' => '[[[NamespacePrefix]]]\\Joomla\\###Branch###.[[[ComponentNamespace]]].Factory',
			'[[[NamespacePrefix]]]\\Joomla\\BetaTools.Beta' => '[[[NamespacePrefix]]]\\Joomla\\BetaTools.Beta',
		];

		foreach ($cases as $standing => $expected)
		{
			$source = [
				'stored' => $names->expand($standing, $names->context()), 'fqn' => $names->resolve($standing),
				'placement_valid' => true,
			];
			$proposal = $names->repair($source, $standing);
			$this->assertTrue($proposal['round_trip'], $standing);
			$this->assertFalse($proposal['relocation'], $standing);
			$this->assertSame($expected, $proposal['value'], $standing);
			$this->assertSame($names->output($standing), $names->output($expected), 'Repair must retain the compiler class and file: ' . $standing);
		}
	}

	/**
	 * A repair cannot change the selected target's actual vendor or component class.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testNamespaceRepairRejectsChangedTargetOutputAndFileSeams(): void
	{
		[$names] = $this->repairNamespacer();
		$source = [
			'stored' => 'Other\\Joomla\\Alpha.Factory',
			'fqn' => 'Other\\Joomla\\Alpha\\Factory', 'placement_valid' => true,
		];
		$proposal = $names->repair($source, '[[[NamespacePrefix]]]\\Joomla\\Alpha.Factory', $names->context(4));
		$this->assertFalse($proposal['round_trip'], 'Changing a foreign literal Alpha to the target component Beta would rename its compiled class.');

		$source['stored'] = 'Other\\Joomla\\Beta.Factory';
		$source['fqn'] = 'Other\\Joomla\\Beta\\Factory';
		$proposal = $names->repair($source, 'Other\\Joomla\\Beta.Factory');
		$this->assertFalse($proposal['round_trip'], 'Deferring a literal Other vendor cannot move the actual target output to Acme.');

		$source = [
			'stored' => 'Acme\\Joomla\\Beta\\Deep.Factory',
			'fqn' => 'Acme\\Joomla\\Beta\\Deep\\Factory',
			'placement_valid' => true, 'placement_evidence' => true,
		];
		$proposal = $names->repair($source, '[[[NamespacePrefix]]]\\Joomla\\Beta.Deep.Factory');
		$this->assertFalse($proposal['round_trip'], 'Repair cannot include an independently required physical file relocation.');
	}

	/**
	 * Effective component overrides remain the literal role repaired by maintenance.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testNamespaceRepairUsesCompilerNormalizedComponentOverrides(): void
	{
		[$names, $load] = $this->repairNamespacer();
		$load->overrides('aaaaaaaa-1111-4111-8111-111111111111', [
			['target' => '[[[ComponentNamespace]]]', 'value' => '[[[Component]]]Portal'],
		]);
		$standing = '[[[NamespacePrefix]]]\\Joomla\\BetaPortal.Factory';
		$proposal = $names->repair([
			'stored' => 'Acme\\Joomla\\BetaPortal.Factory',
			'fqn' => 'Acme\\Joomla\\BetaPortal\\Factory', 'placement_valid' => true,
		], $standing);
		$this->assertTrue($proposal['round_trip']);
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory', $proposal['value']);
		$this->assertSame($names->output($standing), $names->output($proposal['value']));
	}

	/**
	 * Only the explicitly selected component parameterises matching namespace words.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testOnlyTheSelectedComponentAuthorizesComponentReplacement(): void
	{
		foreach (['Registry', 'Storage', 'Domainfixture'] as $word)
		{
			foreach ([0, 3] as $selected)
			{
				$config = new Config(['component' => $selected]);
				$load = new ExtrusionPowerLoadFixture();
				$load->component(3, 'aaaaaaaa-1111-4111-8111-111111111111', strtolower($word), 1, 'Acme');
				$load->placeholder(1, 'ComponentNamespace', $word);
				$report = new Report();
				$values = new Placeholders($config, $load, $report, new Source());
				$names = new Namespacer($values);

				foreach (['Abstraction.' . $word . '.Traits', $word . '.Library', 'Deep.Path.' . $word] as $branch)
				{
					$expected = $selected === 3 ? str_replace($word, '[[[ComponentNamespace]]]', $branch) : $branch;
					$this->assertSame(
						'[[[NamespacePrefix]]]\\Joomla\\' . $expected . '.PathToString',
						$names->placeholderize('Acme\\Joomla\\' . $branch . '.PathToString')
					);
				}

				$this->assertSame([], $values->witnessed());
			}
		}
	}

	/**
	 * Entered component codes match complete compiled segments and never class names.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testEnteredCodeUsesCompiledSegmentsAndPreservesPlacement(): void
	{
		$config = new Config(['componentCode' => 'my_component']);
		$values = new Placeholders($config, new ExtrusionPowerLoadFixture(), new Report(), new Source(), 'Acme');
		$names = new Namespacer($values);
		$cases = [
			'Acme\\Joomla\\Mycomponent.Service' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Service',
			'Acme\\Mycomponent\\Deep.Mycomponent.Service' => '[[[NamespacePrefix]]]\\[[[ComponentNamespace]]]\\Deep.[[[ComponentNamespace]]].Service',
			'Acme\\Joomla\\Mycomponent.Mycomponent' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Mycomponent',
			'Acme\\Joomla\\Mycomponent\\Mycomponent' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]]\\Mycomponent',
			'Acme\\Joomla\\mycomponent.Service' => '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Service',
			'Acme\\Joomla\\MycomponentTools.Service' => '[[[NamespacePrefix]]]\\Joomla\\MycomponentTools.Service',
			'Acme\\Joomla\\ToolsMycomponent.Service' => '[[[NamespacePrefix]]]\\Joomla\\ToolsMycomponent.Service',
			'Acme\\Joomla\\Library.Mycomponent' => '[[[NamespacePrefix]]]\\Joomla\\Library.Mycomponent',
		];

		$this->assertSame('Mycomponent', $values->component());

		foreach ($cases as $source => $expected)
		{
			$this->assertSame($expected, $names->placeholderize($source), $source);
		}

		$config->set('componentCode', 'acme');
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Acme', $names->placeholderize('Acme\\Joomla\\Acme.Acme'));
		$this->assertSame([], $values->witnessed(), 'Namespace reconstruction cannot propose component configuration writes.');
	}

	/**
	 * Effective compiler overrides define which component namespace to reverse.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testComponentOverrideDefinesTheMatchingNamespaceSegment(): void
	{
		$config = new Config(['component' => 3]);
		$load = new ExtrusionPowerLoadFixture();
		$load->component(3, 'aaaaaaaa-1111-4111-8111-111111111111', 'demo', 1, 'Acme');
		$load->overrides('aaaaaaaa-1111-4111-8111-111111111111', [
			['target' => '[[[ComponentNamespace]]]', 'value' => '[[[Component]]]Portal'],
		]);
		$names = new Namespacer(new Placeholders($config, $load, new Report(), new Source()));

		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Service', $names->placeholderize('Acme\\Joomla\\DemoPortal.Service'));
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\Demo.Service', $names->placeholderize('Acme\\Joomla\\Demo.Service'));
	}

	/**
	 * A proposal reconstructs its source component without leaking target values.
	 *
	 * @return  void
	 * @since   6.2.2
	 */
	public function testProposalUsesItsIsolatedSourceComponentContext(): void
	{
		$config = new Config(['component' => 3]);
		$load = new ExtrusionPowerLoadFixture();
		$load->component(3, 'aaaaaaaa-1111-4111-8111-111111111111', 'beta', 1, 'Acme');
		$load->component(4, 'bbbbbbbb-2222-4222-8222-222222222222', 'alpha', 1, 'Other');
		$names = new Namespacer(new Placeholders($config, $load, new Report(), new Source()));
		$target = $names->context();
		$source = [
			'stored' => 'SourceVendor\\Joomla\\Alpha.Controller.AllowDelete',
			'fqn' => 'SourceVendor\\Joomla\\Alpha\\Controller\\AllowDelete',
			'placement_valid' => true,
		];
		$proposal = $names->proposal($source, null, $names->context(4));

		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Controller.AllowDelete', $proposal['value']);
		$this->assertTrue($proposal['round_trip']);
		$this->assertSame($source['fqn'], $proposal['source_fqn']);
		$this->assertSame('Acme\\Joomla\\Beta\\Controller\\AllowDelete', $proposal['target_fqn']);
		$this->assertSame('[[[NamespacePrefix]]]\\Joomla\\Alpha.Controller.AllowDelete', $names->proposal($source, null, $target)['value']);
		$this->assertSame($target, $names->context());
	}

	/**
	 * Colliding records remain GUID-addressable and neither lookup chooses a row.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCollidingNamespacesRetainEveryGuidAndNeverPickFirst(): void
	{
		$guids = [
			'aaaaaaaa-1111-4111-8111-111111111111',
			'bbbbbbbb-2222-4222-8222-222222222222'
		];
		$template = '[[[NamespacePrefix]]]\\Joomla\\[[[ComponentNamespace]]].Factory';

		foreach ([$guids, array_reverse($guids)] as $order)
		{
			$config = new Config(['component' => 3]);
			$load = new ExtrusionPowerLoadFixture();
			$load->component(3, 'cccccccc-3333-4333-8333-333333333333', 'beta', 1, 'Acme');

			foreach ($order as $id => $guid)
			{
				$load->power($id + 1, $guid, 'Factory', $template);
			}

			$report = new Report();
			$names = new Namespacer(new Placeholders($config, $load, $report, new Source()));
			$existing = new Existing($load, $names, $report);

			foreach ($guids as $guid)
			{
				$this->assertSame($guid, $existing->power($guid)['guid'] ?? null);
			}

			$this->assertSame(2, $existing->count());
			$this->assertNull($existing->match($template));
			$this->assertNull($existing->find('Acme\\Joomla\\Beta\\Factory'));
		}
	}
	/**
	 * Output placement follows the compiler's core paths, not namespace depth.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testOutputPreservesPlacementAndDoesNotReuseAnotherContext(): void
	{
		$config = new Config(['component' => 3]);
		$load = new ExtrusionPowerLoadFixture();
		$load->component(3, 'aaaaaaaa-1111-4111-8111-111111111111', 'beta', 1, 'Acme');
		$load->component(4, 'bbbbbbbb-2222-4222-8222-222222222222', 'alpha', 1, 'Other');
		$container = new Container();
		$container->set('Extrusion.Config', $config);
		$container->set('Load', $load);
		$container->set('Extrusion.Registry.Report', new Report());
		$container->set('Extrusion.Registry.Source', new Source());
		$container->registerServiceProvider(new Powers());
		$names = $container->get('Extrusion.Powers.Resolver.Namespacer');
		$context = $names->context(3);
		$cases = [
			'[[[NamespacePrefix]]]\Joomla\Abstraction.Registry.Value' => 'library:Acme.Joomla/src/Abstraction/Registry/Value.php',
			'[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine.Widget' => 'extension:admin/src/Engine/Widget.php',
			'[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine\Widget' => 'extension:admin/src/Widget.php',
			'[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Site\Widget' => 'extension:site/src/Widget.php',
			'[[[NamespacePrefix]]]\Module\Example\Service.Widget' => 'extension:mod_example/src/Service/Widget.php',
			'[[[NamespacePrefix]]]\Plugin\System\Example\Service.Widget' => 'extension:plg_system_example/src/Service/Widget.php'
		];

		foreach ($cases as $stored => $path)
		{
			$this->assertSame($path, $names->output($stored, $context)['path']);
			$this->assertSame($names->resolve($stored, $context), $names->output($stored, $context)['fqn']);
		}

		$stored = '[[[NamespacePrefix]]]\Component\[[[ComponentNamespace]]]\Administrator\Engine.Widget';
		$before = $names->output($stored, $context);
		$this->assertSame('Other\Component\Alpha\Administrator\Engine\Widget', $names->output($stored, $names->context(4))['fqn']);
		$this->assertSame($before, $names->output($stored, $context));
		$this->assertNull($names->output('[[[Unknown]]]\Engine.Widget', $context));
	}

	/**
	 * Compose namespace placement and placeholders without installed Joomla state.
	 *
	 * @return  array  Namespacer, recorded loader and operation configuration.
	 * @since   6.2.2
	 */
	protected function repairNamespacer(): array
	{
		$config = new Config(['component' => 3]);
		$load = new ExtrusionPowerLoadFixture();
		$load->component(3, 'aaaaaaaa-1111-4111-8111-111111111111', 'beta', 1, 'Acme');
		$load->component(4, 'bbbbbbbb-2222-4222-8222-222222222222', 'alpha', 1, 'Other');
		$container = new Container();
		$container->set('Extrusion.Config', $config, true);
		$container->set('Load', $load, true);
		$container->set('Extrusion.Registry.Report', new Report(), true);
		$container->set('Extrusion.Registry.Source', new Source(), true);
		$container->registerServiceProvider(new Powers());

		return [$container->get('Extrusion.Powers.Resolver.Namespacer'), $load, $config];
	}

}
