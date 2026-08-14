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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Dynamicget;


use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Model\Dynamicget;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Package\Builder\Get;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Dynamic-get definition caching and model-boundary contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Data::class)]
final class DataTest extends CompilerDomainTestCase
{
	/**
	 * Cached definitions are deduplicated, cloned, modeled, and isolated across reads.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCachedDefinitionsAreClonedDeduplicatedAndModeledPerRead(): void
	{
		$guid = '123e4567-e89b-12d3-a456-426614174000';
		$cached = (object) [
			'id' => 17,
			'guid' => $guid,
			'name' => 'Latest Articles',
			'gettype' => 0
		];
		$event = $this->createMock(EventInterface::class);
		$event->expects($this->exactly(4))->method('trigger');
		$model = $this->createMock(Dynamicget::class);
		$model->expects($this->exactly(2))->method('set');
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');
		$subject = new Data(
			$this->compilerConfig(['build_target' => 'site']),
			new Registry(),
			$event,
			$this->createStub(Customcode::class),
			$this->createStub(Dispenser::class),
			$this->createStub(Gui::class),
			$model,
			$this->inertCompilerCollaborator(Counter::class),
			$db,
			$this->inertCompilerCollaborator(Get::class)
		);
		$this->setCompilerProperty($subject, 'data', [17 => $cached]);
		$this->setCompilerProperty($subject, 'index', [17 => 17, $guid => 17]);

		$first = $subject->get([17, $guid, 17], 'articles', 'site');
		$this->assertCount(1, $first);
		$this->assertNotSame($cached, $first[0]);
		$this->assertSame(0, $first[0]->add_php_router_parse);
		$this->assertSame('', $first[0]->plugin_events);
		$this->assertStringStartsWith('articles_latest_articles_', $first[0]->key);
		$first[0]->name = 'mutated';

		$second = $subject->get([$guid], 'articles', 'site');
		$this->assertSame('Latest Articles', $second[0]->name);
		$this->assertFalse(property_exists($cached, 'add_php_router_parse'));
	}

	/**
	 * An empty request is rejected before database, model, or remote work.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmptyRequestReturnsNull(): void
	{
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');
		$subject = new Data(
			$this->compilerConfig(),
			new Registry(),
			$this->createStub(EventInterface::class),
			$this->createStub(Customcode::class),
			$this->createStub(Dispenser::class),
			$this->createStub(Gui::class),
			$this->createStub(Dynamicget::class),
			$this->inertCompilerCollaborator(Counter::class),
			$db,
			$this->inertCompilerCollaborator(Get::class)
		);

		$this->assertNull($subject->get([], 'articles', 'site'));
	}
}
