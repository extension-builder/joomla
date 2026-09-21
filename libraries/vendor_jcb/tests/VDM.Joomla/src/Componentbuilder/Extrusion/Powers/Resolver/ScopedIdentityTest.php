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
	 * Literal branches survive both catalogue and selected-component collisions.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNamespaceWordsDoNotAuthorizeComponentReplacement(): void
	{
		foreach (['Registry', 'Storage', 'Domain' . substr(sha1('fixture'), 0, 8)] as $word)
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
					$this->assertSame(
						'[[[NamespacePrefix]]]\\Joomla\\' . $branch . '.PathToString',
						$names->placeholderize('Acme\\Joomla\\' . $branch . '.PathToString')
					);
				}

				$this->assertSame([], $values->witnessed());
			}
		}
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

}
