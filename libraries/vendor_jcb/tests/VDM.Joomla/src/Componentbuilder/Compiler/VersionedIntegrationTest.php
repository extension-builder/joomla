<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler;


use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry as JoomlaRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaFive\Event as JoomlaFiveEvent;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaFive\Header as JoomlaFiveHeader;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaFive\History as JoomlaFiveHistory;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaFour\Event as JoomlaFourEvent;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaFour\Header as JoomlaFourHeader;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaFour\History as JoomlaFourHistory;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaSix\Event as JoomlaSixEvent;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaSix\Header as JoomlaSixHeader;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaSix\History as JoomlaSixHistory;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaThree\Event as JoomlaThreeEvent;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaThree\Header as JoomlaThreeHeader;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaThree\History as JoomlaThreeHistory;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Host-event, target-header, and history contracts for every Joomla major.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeEvent::class)]
#[CoversClass(JoomlaFourEvent::class)]
#[CoversClass(JoomlaFiveEvent::class)]
#[CoversClass(JoomlaSixEvent::class)]
#[CoversClass(JoomlaThreeHeader::class)]
#[CoversClass(JoomlaFourHeader::class)]
#[CoversClass(JoomlaFiveHeader::class)]
#[CoversClass(JoomlaSixHeader::class)]
#[CoversClass(JoomlaThreeHistory::class)]
#[CoversClass(JoomlaFourHistory::class)]
#[CoversClass(JoomlaFiveHistory::class)]
#[CoversClass(JoomlaSixHistory::class)]
final class VersionedIntegrationTest extends CompilerDomainTestCase
{
	/**
	 * Modern host adapters invoke each registered listener with unpacked arguments.
	 *
	 * @param   class-string  $class  Event adapter class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('modernEventProvider')]
	public function testModernEventsDispatchArgumentsInListenerOrder(string $class): void
	{
		$calls = [];
		$dispatcher = $this->createMock(DispatcherInterface::class);
		$dispatcher->expects($this->once())
			->method('getListeners')
			->with('jcb_ce_contract')
			->willReturn([
				function (string $value, int $number) use (&$calls): void
				{
					$calls[] = 'first:' . $value . ':' . $number;
				},
				function (string $value, int $number) use (&$calls): void
				{
					$calls[] = 'second:' . $value . ':' . $number;
				},
			]);
		$subject = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		$this->setCompilerProperty($subject, 'activePlugins', true);
		$this->setCompilerProperty($subject, 'dispatcher', $dispatcher);

		$subject->trigger('jcb_ce_contract', ['value', 7]);

		$this->assertSame(['first:value:7', 'second:value:7'], $calls);
	}

	/**
	 * The Joomla 3 adapter remains a safe no-op when no compiler plugin is enabled.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJoomlaThreeEventSkipsLegacyDispatcherWithoutActivePlugins(): void
	{
		$subject = new JoomlaThreeEvent(new JoomlaRegistry());

		$this->assertNull($subject->trigger('jcb_ce_contract', ['unused']));
	}

	/**
	 * Every target emits its reviewed component bootstrap imports and event seam.
	 *
	 * @param   class-string  $class   Header implementation.
	 * @param   bool          $modern  Whether namespaced component helpers are required.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('headerProvider')]
	public function testHeadersProtectTargetBootstrapImports(string $class, bool $modern): void
	{
		$config = $this->compilerConfig(['build_target' => 'admin']);
		$placeholder = new Placeholder($config);
		$placeholder->set('NamespacePrefix', 'Acme');
		$placeholder->set('Component', 'Demo');
		$placeholder->set('ComponentNamespace', 'Demo');
		$event = $this->createMock(EventInterface::class);
		$event->expects($this->once())
			->method('trigger')
			->with(
				'jcb_ce_setClassHeader',
				$this->callback(static fn(array $arguments): bool => $arguments[0] === 'admin.component')
			);
		$subject = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		$this->setCompilerProperty($subject, 'config', $config);
		$this->setCompilerProperty($subject, 'event', $event);
		$this->setCompilerProperty($subject, 'placeholder', $placeholder);
		$this->setCompilerProperty($subject, 'headers', []);

		if ($modern)
		{
			$this->setCompilerProperty($subject, 'NamespacePrefix', 'Acme');
			$this->setCompilerProperty($subject, 'ComponentName', 'Demo');
			$this->setCompilerProperty($subject, 'ComponentNamespace', 'Demo');
		}

		$output = $subject->get('admin.component', 'demo');

		$this->assertStringContainsString('use Joomla\CMS\Factory;', $output);
		$this->assertStringContainsString('use Joomla\CMS\MVC\Controller\BaseController;', $output);

		if ($modern)
		{
			$this->assertStringContainsString(
				'use Acme\Component\Demo\Administrator\Helper\DemoHelper;',
				$output
			);
		}
		else
		{
			$this->assertStringNotContainsString('Acme\Component\Demo', $output);
		}
	}

	/**
	 * History adapters use their host table and store one component lock exactly once.
	 *
	 * @param   class-string  $class  History implementation.
	 * @param   string        $table  Host-version history table.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('historyProvider')]
	public function testHistoryAddsComponentWatchUsingHostTable(string $class, string $table): void
	{
		$config = $this->compilerConfig(['component_id' => 42]);
		$object = (object) [
			'version_id' => 9,
			'version_note' => '{"component":[]}',
			'version_data' => '{"title":"old"}',
		];
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->once())
			->method('updateObject')
			->with($table, $this->identicalTo($object), 'version_id')
			->willReturn(true);
		$subject = new $class($config, $db);

		$this->assertTrue((new ReflectionMethod($class, 'set'))->invoke($subject, $object, 1));
		$this->assertSame(['component' => [42]], json_decode($object->version_note, true));
		$this->assertSame('1', $object->keep_forever);
	}

	/**
	 * Modern host event implementations.
	 *
	 * @return  array<string, array{class-string}>
	 * @since   6.1.6
	 */
	public static function modernEventProvider(): array
	{
		return [
			'Joomla 4' => [JoomlaFourEvent::class],
			'Joomla 5' => [JoomlaFiveEvent::class],
			'Joomla 6' => [JoomlaSixEvent::class],
		];
	}

	/**
	 * Target header implementations.
	 *
	 * @return  array<string, array{class-string,bool}>
	 * @since   6.1.6
	 */
	public static function headerProvider(): array
	{
		return [
			'Joomla 3' => [JoomlaThreeHeader::class, false],
			'Joomla 4' => [JoomlaFourHeader::class, true],
			'Joomla 5' => [JoomlaFiveHeader::class, true],
			'Joomla 6' => [JoomlaSixHeader::class, true],
		];
	}

	/**
	 * Host history implementations and storage tables.
	 *
	 * @return  array<string, array{class-string,string}>
	 * @since   6.1.6
	 */
	public static function historyProvider(): array
	{
		return [
			'Joomla 3' => [JoomlaThreeHistory::class, '#__ucm_history'],
			'Joomla 4' => [JoomlaFourHistory::class, '#__history'],
			'Joomla 5' => [JoomlaFiveHistory::class, '#__history'],
			'Joomla 6' => [JoomlaSixHistory::class, '#__history'],
		];
	}
}
