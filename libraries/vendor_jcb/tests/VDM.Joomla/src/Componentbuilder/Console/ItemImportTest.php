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

namespace VDM\Joomla\Tests\Componentbuilder\Console;


use Joomla\CMS\Language\Language;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use VDM\Joomla\Componentbuilder\Abstraction\Console\Import;
use VDM\Joomla\Componentbuilder\Console\ItemImport;
use VDM\Joomla\Componentbuilder\Import\Factory as ImportFactory;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Interfaces\Import\ItemProcessInterface;
use VDM\Joomla\Interfaces\Import\PersistentEntityInterface;
use VDM\Tests\Support\JoomlaTestCase;


require_once dirname(__DIR__, 4) . '/Support/ConsoleSleepFixture.php';


/**
 * Persistent item-import console wiring and queue lifecycle tests.
 *
 * @since  1.0.0
 */
#[CoversClass(Import::class)]
#[CoversClass(ItemImport::class)]
#[UsesClass(ImportFactory::class)]
final class ItemImportTest extends JoomlaTestCase
{
	/**
	 * Wire the concrete command to the reviewed persistent-import aliases and map.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testConfigurationSelectsPersistentItemServicesAndJoinMap(): void
	{
		$entity = $this->createMock(PersistentEntityInterface::class);
		$entity->expects($this->once())
			->method('setParentTable')
			->with('look')
			->willReturnSelf();
		$entity->expects($this->once())
			->method('setParentKey')
			->with('guid')
			->willReturnSelf();
		$entity->expects($this->once())
			->method('setParentJoinKey')
			->with('entity')
			->willReturnSelf();
		$entity->expects($this->once())
			->method('setLinkField')
			->with('guid')
			->willReturnSelf();
		$entity->expects($this->once())
			->method('setJoinFields')
			->with([
				'detail' => ['link_fields' => ['entity']],
			])
			->willReturnSelf();

		$subject = $this->createSubject(
			$this->createStub(ItemsInterface::class),
			$this->createStub(ItemProcessInterface::class),
			$entity
		);

		$this->assertSame('componentbuilder:item:import', $subject->getName());
		$this->assertStringContainsString('item imports', $subject->getDescription());
		$this->assertStringContainsString('item spreadsheets', $subject->getHelp());
	}

	/**
	 * Report an idle queue without mutating queue state or invoking an importer.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testEmptyQueueReturnsSuccessWithoutStartingAnImport(): void
	{
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->once())
			->method('table')
			->with('import_queue')
			->willReturnSelf();
		$items->expects($this->once())
			->method('get')
			->with([10], 'status')
			->willReturn(null);
		$items->expects($this->never())->method('set');
		$import = $this->createMock(ItemProcessInterface::class);
		$import->expects($this->never())->method('execute');
		$entity = $this->entityStub();
		$output = new BufferedOutput();

		$status = $this->createSubject($items, $import, $entity)
			->execute(new ArrayInput([]), $output);

		$this->assertSame(0, $status);
		$this->assertStringContainsString('No item imports found in the queue.', $output->fetch());
	}

	/**
	 * Claim queued spreadsheets before processing and summarize both result types.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testQueuedImportsAreClaimedProcessedAndSummarized(): void
	{
		$first = (object) ['guid' => 'first-guid'];
		$second = (object) ['guid' => 'second-guid'];
		$queue = [$first, $second];
		$items = $this->createMock(ItemsInterface::class);
		$items->expects($this->exactly(2))
			->method('table')
			->with('import_queue')
			->willReturnSelf();
		$items->expects($this->once())
			->method('get')
			->with([10], 'status')
			->willReturn($queue);
		$items->expects($this->once())
			->method('set')
			->with([
				['guid' => 'first-guid', 'status' => 20],
				['guid' => 'second-guid', 'status' => 20],
			]);
		$import = $this->createMock(ItemProcessInterface::class);
		$executed = [];
		$import->expects($this->exactly(2))
			->method('execute')
			->willReturnCallback(
				function (object $spreadsheet) use (&$executed, $import): ItemProcessInterface
				{
					$executed[] = $spreadsheet->guid;

					return $import;
				}
			);
		$results = [
			(object) ['message_success' => 'first imported', 'message_error' => ''],
			(object) ['message_success' => '', 'message_error' => 'second rejected'],
		];
		$resultIndex = 0;
		$import->expects($this->exactly(2))
			->method('result')
			->willReturnCallback(
				function () use (&$resultIndex, $results): object
				{
					return $results[$resultIndex++];
				}
			);
		$output = new BufferedOutput();

		$status = $this->createSubject($items, $import, $this->entityStub())
			->execute(new ArrayInput([]), $output);
		$text = $output->fetch();

		$this->assertSame(0, $status);
		$this->assertSame(['first-guid', 'second-guid'], $executed);
		$this->assertStringContainsString('Initiating import for 2 item spreadsheet(s)', $text);
		$this->assertStringContainsString('Processing spreadsheet #first-guid', $text);
		$this->assertStringContainsString('first imported', $text);
		$this->assertStringContainsString('second rejected', $text);
		$this->assertStringContainsString('50% success, 50% failure', $text);
	}

	/**
	 * Build an entity fixture with deterministic queue metadata and fluent mapping.
	 *
	 * @return  PersistentEntityInterface
	 * @since   1.0.0
	 */
	private function entityStub(): PersistentEntityInterface
	{
		$entity = $this->createStub(PersistentEntityInterface::class);
		$entity->method('setParentTable')->willReturnSelf();
		$entity->method('setParentKey')->willReturnSelf();
		$entity->method('setParentJoinKey')->willReturnSelf();
		$entity->method('setLinkField')->willReturnSelf();
		$entity->method('setJoinFields')->willReturnSelf();
		$entity->method('getQueueTable')->willReturn('import_queue');
		$entity->method('getQueueStatusField')->willReturn('status');
		$entity->method('getQueueWaitState')->willReturn(10);
		$entity->method('getQueueProcessingState')->willReturn(20);

		return $entity;
	}

	/**
	 * Compose the command from a test-owned import factory container.
	 *
	 * @param   ItemsInterface             $items   Queue item gateway.
	 * @param   ItemProcessInterface       $import  Import processor.
	 * @param   PersistentEntityInterface  $entity  Persistent entity configuration.
	 *
	 * @return  ItemImport
	 * @since   1.0.0
	 */
	private function createSubject(
		ItemsInterface $items,
		ItemProcessInterface $import,
		PersistentEntityInterface $entity
	): ItemImport
	{
		$this->isolateFactory(ImportFactory::class);
		$this->setJoomlaFactoryProperty('language', $this->createStub(Language::class));

		$container = new Container();
		$container->set('Data.Items', $items, true);
		$container->set('Import.Persistent', $import, true);
		$container->set('Import.Persistent.Entity', $entity, true);
		(new ReflectionProperty(ImportFactory::class, 'container'))
			->setValue(null, $container);

		return new ItemImport();
	}
}
