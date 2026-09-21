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
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\References;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Powers;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Tests\Support\ExtrusionPowerLoadFixture;
use VDM\Tests\Support\TestCase;


/**
 * Component usage comes from stored references, not matching namespace words.
 *
 * @since  6.2.0
 */
#[CoversClass(References::class)]
#[CoversClass(Powers::class)]
final class ReferencesTest extends TestCase
{
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
			$this->assertCount(3, $a['powers']);
			$this->assertCount(3, $b['powers']);
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
		$before = $graph->fingerprint();
		$this->assertSame([1], array_keys($graph->consumers($this->guid('power-a'))));
		$load->record('admin_view', 6, [
			'guid' => $this->guid('view'),
			'php_getitem' => base64_encode($this->token('power-a'))
		]);
		$graph->refresh();
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
	 * Build the real provider's graph with only its external database mocked.
	 *
	 * @param   ExtrusionPowerLoadFixture  $load  The declared raw records.
	 *
	 * @return  References  The actual production graph.
	 * @since   6.2.0
	 */
	protected function graph(ExtrusionPowerLoadFixture $load): References
	{
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');
		$container = new Container();
		$container->set('Table', new Table());
		$container->set('Load', $load, true);
		$container->set('Joomla.Database', $db, true);
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
