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

namespace VDM\Joomla\Tests\Componentbuilder\Remote;


use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use VDM\Joomla\Componentbuilder\JoomlaPower\Remote\Config as JoomlaPowerConfig;
use VDM\Joomla\Componentbuilder\JoomlaPower\Remote\Set as JoomlaPowerSet;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Componentbuilder\Power\Remote\Config as PowerConfig;
use VDM\Joomla\Componentbuilder\Power\Remote\Set as PowerSet;
use VDM\Joomla\Componentbuilder\Power\Table;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Interfaces\Git\Repository\ContentsInterface;
use VDM\Joomla\Interfaces\GrepInterface;
use VDM\Joomla\Interfaces\Readme\ItemInterface;
use VDM\Joomla\Interfaces\Readme\MainInterface;
use VDM\Tests\Support\TestCase;


/**
 * Specialized Joomla Power and Super Power remote-write contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaPowerSet::class)]
#[CoversClass(PowerSet::class)]
final class SpecializedSetTest extends TestCase
{
	/**
	 * Protect Joomla Power settings path, payload, commit, and author boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJoomlaPowerSetCreatesExactSettingsFileAtGitBoundary(): void
	{
		$item = (object) ['guid' => 'power-guid', 'system_name' => 'Factory'];
		$repo = (object) [
			'organisation' => 'acme',
			'repository' => 'joomla-powers',
			'write_branch' => 'main',
			'author_name' => 'Build Bot',
			'author_email' => 'bot@example.test',
		];
		$git = $this->createMock(ContentsInterface::class);
		$git->expects($this->once())
			->method('create')
			->with(
				'acme',
				'joomla-powers',
				'src/power-guid/item.json',
				json_encode($item, JSON_PRETTY_PRINT),
				'Create Factory',
				'main',
				'Build Bot',
				'bot@example.test'
			)
			->willReturn((object) ['sha' => 'created']);
		$subject = new JoomlaPowerSet(
			new JoomlaPowerConfig(new Table()),
			$this->createStub(GrepInterface::class),
			$this->createStub(ItemsInterface::class),
			$this->createStub(ItemInterface::class),
			$this->createStub(MainInterface::class),
			$git,
			new Tracker(),
			new MessageBus(),
			[]
		);

		$this->assertTrue($this->invoke($subject, 'createItem', [$item, $repo]));
	}

	/**
	 * Protect namespace placeholder expansion, dot folding, and class validation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPowerSetNormalizesNamespaceAndBuildsIndexPaths(): void
	{
		$subject = new PowerSet(
			new PowerConfig(new Table()),
			$this->createStub(GrepInterface::class),
			$this->createStub(ItemsInterface::class),
			$this->createStub(ItemInterface::class),
			$this->createStub(MainInterface::class),
			$this->createStub(ContentsInterface::class),
			new Tracker(),
			new MessageBus(),
			new Parser(),
			[]
		);
		$item = (object) [
			'guid' => 'power-guid',
			'name' => 'Widget',
			'namespace' => '[[[NamespacePrefix]]]\\Demo.Services.Widget',
			'type' => 'final class',
		];
		$this->invoke($subject, 'setRepoPlaceholders', [(object) []]);

		$this->assertSame(
			'VDM\\Demo\\Services',
			$this->invoke($subject, 'getNamespace', [$item->namespace, $item->name])
		);
		$this->assertNull($this->invoke($subject, 'getNamespace', ['VDM\\Demo\\Other', 'Widget']));
		$this->assertSame('VDM\\Demo', $this->invoke($subject, 'getCleanNamespace', ['use VDM\\Demo;']));
		$this->assertSame('src/power-guid/code.php', $this->invoke($subject, 'index_map_CodePath', [$item]));
		$this->assertSame('src/power-guid/code.power', $this->invoke($subject, 'index_map_PowerPath', [$item]));
		$this->assertSame('final class', $this->invoke($subject, 'index_map_TypeName', [$item]));
		$this->assertSame('VDM\\Demo\\Services', $this->invoke($subject, 'index_map_NameSpace', [$item]));
	}

	/**
	 * Invoke a protected specialization without replacing its implementation.
	 *
	 * @param   object             $subject    Set implementation.
	 * @param   string             $method     Method name.
	 * @param   array<int, mixed>  $arguments  Method arguments.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function invoke(object $subject, string $method, array $arguments): mixed
	{
		return (new ReflectionMethod($subject, $method))->invokeArgs($subject, $arguments);
	}
}
