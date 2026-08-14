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

namespace VDM\Joomla\Tests\Componentbuilder\Import;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Import\Item\Persistent;
use VDM\Joomla\Componentbuilder\Import\Item\Transient;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\Import\AssessorInterface;
use VDM\Joomla\Interfaces\Import\DatabaseMessageInterface;
use VDM\Joomla\Interfaces\Import\EntityInterface;
use VDM\Joomla\Interfaces\Import\JoinTablesInterface;
use VDM\Joomla\Interfaces\Import\MapperInterface;
use VDM\Joomla\Interfaces\Import\MessageInterface;
use VDM\Joomla\Interfaces\Import\ParentTableInterface;
use VDM\Joomla\Interfaces\Import\PersistentEntityInterface;
use VDM\Joomla\Interfaces\Import\RowInterface;
use VDM\Joomla\Interfaces\Import\SpreadsheetReaderInterface;
use VDM\Joomla\Interfaces\Import\StatusInterface;
use VDM\Joomla\Interfaces\Registryinterface;
use VDM\Joomla\Interfaces\Spreadsheet\RowDataInterface;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Persistent and transient item import lifecycle contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Persistent::class)]
#[CoversClass(Transient::class)]
final class ImportProcessTest extends FilesystemTestCase
{
	/**
	 * Execute valid transient rows, ignore malformed rows, and assess counters.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTransientExecutesMappedRowsAndReportsExactCounters(): void
	{
		$file = $this->writeTemporaryFile('imports/rows.csv', "title\nfirst\nsecond\n");
		$maps = (object) ['A' => 'title'];
		$payload = (object) ['file_path' => $file, 'maps' => $maps, 'batch' => 'demo'];
		$message = $this->createMock(MessageInterface::class);
		$message->expects($this->never())->method('addError');
		$result = (object) ['message_success' => ['done']];
		$message->expects($this->once())->method('get')->willReturn($result);
		$message->expects($this->once())->method('reset');
		$mapper = $this->createMock(MapperInterface::class);
		$mapper->expects($this->once())->method('set')->with($maps, 'article');
		$data = $this->createMock(Registryinterface::class);
		$data->expects($this->once())->method('set')->with('import', $this->callback(
			static fn(array $stored): bool => $stored['file_path'] !== ''
				&& $stored['batch'] === 'demo'
				&& !array_key_exists('maps', $stored)
		))->willReturnSelf();
		$importer = $this->createMock(SpreadsheetReaderInterface::class);
		$rowData = $this->createStub(RowDataInterface::class);
		$importer->expects($this->once())
			->method('read')
			->with($file, 2, 100, $rowData)
			->willReturn($this->rows([
				null,
				['index' => 1, 'values' => []],
				['index' => 2, 'values' => ['first']],
				['index' => 3, 'values' => ['second']],
			]));
		$seenRows = [];
		$row = $this->createMock(RowInterface::class);
		$row->expects($this->exactly(2))->method('set')->willReturnCallback(
			static function (int $index, array $values) use (&$seenRows): void
			{
				$seenRows[] = [$index, $values];
			}
		);
		$row->expects($this->exactly(2))->method('clear')->willReturnSelf();
		$parent = $this->createMock(ParentTableInterface::class);
		$parent->expects($this->exactly(2))
			->method('set')
			->with('external_id', 'guid', 'article')
			->willReturnOnConsecutiveCalls('parent-guid', null);
		$joins = $this->createMock(JoinTablesInterface::class);
		$joins->expects($this->once())->method('set')->with('article_guid', 'parent-guid', ['article_tag' => ['tag']]);
		$assessor = $this->createMock(AssessorInterface::class);
		$assessor->expects($this->once())->method('evaluate')->with(2, 1, 1);
		$entity = $this->entity();
		$subject = new Transient($message, $mapper, $data, $importer, $rowData, $row, $parent, $joins, $assessor, $entity);

		$this->assertSame($subject, $subject->execute($payload));
		$this->assertSame([[2, ['first']], [3, ['second']]], $seenRows);
		$this->assertObjectNotHasProperty('maps', $payload);
		$this->assertSame($result, $subject->result());
	}

	/**
	 * Move a persistent queue item into processing and then its configured error state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPersistentRecordsMissingFileAsPrematureQueueError(): void
	{
		$missing = $this->temporaryPath('imports/missing.csv');
		$payload = (object) ['guid' => 'queue-guid', 'file' => 'file-guid', 'maps' => (object) []];
		$statusCalls = [];
		$status = $this->createMock(StatusInterface::class);
		$status->expects($this->once())->method('table')->with('import_queue')->willReturnSelf();
		$status->expects($this->once())->method('field')->with('import_status')->willReturnSelf();
		$status->expects($this->exactly(2))->method('set')->willReturnCallback(
			static function (int $state, string $guid) use (&$statusCalls): void
			{
				$statusCalls[] = [$state, $guid];
			}
		);
		$result = (object) ['message_error' => ['missing']];
		$message = $this->createMock(DatabaseMessageInterface::class);
		$message->expects($this->once())->method('load')->with('queue-guid', 'import_queue', 'import_message')->willReturnSelf();
		$message->expects($this->once())->method('addError')->with($this->callback(
			static fn(string $text): bool => str_contains($text, 'COM_COMPONENTBUILDER_FILE_NOT_FOUND_S')
		))->willReturnSelf();
		$message->expects($this->once())->method('archive')->willReturnSelf();
		$message->expects($this->once())->method('set')->willReturnSelf();
		$message->expects($this->once())->method('get')->willReturn($result);
		$message->expects($this->once())->method('reset');
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->once())->method('table')->with('file')->willReturnSelf();
		$item->expects($this->once())->method('get')->with('file-guid')->willReturn((object) ['file_path' => $missing]);
		$entity = $this->persistentEntity();
		$subject = new Persistent(
			$status,
			$message,
			$this->createStub(MapperInterface::class),
			$this->createStub(Registryinterface::class),
			$this->createStub(SpreadsheetReaderInterface::class),
			$this->createStub(RowDataInterface::class),
			$this->createStub(RowInterface::class),
			$this->createStub(ParentTableInterface::class),
			$this->createStub(JoinTablesInterface::class),
			$this->createStub(AssessorInterface::class),
			$item,
			$entity
		);

		$this->assertSame($subject, $subject->execute($payload));
		$this->assertSame([[2, 'queue-guid'], [4, 'queue-guid']], $statusCalls);
		$this->assertSame($result, $subject->result());
	}

	/**
	 * Build the shared entity configuration used by the transient import.
	 *
	 * @return  EntityInterface
	 * @since   6.1.6
	 */
	private function entity(): EntityInterface
	{
		$entity = $this->createStub(EntityInterface::class);
		$entity->method('getStartingRow')->willReturn(2);
		$entity->method('getMinimalColumns')->willReturn(1);
		$entity->method('getParentTable')->willReturn('article');
		$entity->method('getParentKey')->willReturn('guid');
		$entity->method('getParentJoinKey')->willReturn('article_guid');
		$entity->method('getLinkField')->willReturn('external_id');
		$entity->method('getDataKey')->willReturn('import');
		$entity->method('getJoinFields')->willReturn(['article_tag' => ['tag']]);

		return $entity;
	}

	/**
	 * Build persistent queue, message, and file configuration.
	 *
	 * @return  PersistentEntityInterface
	 * @since   6.1.6
	 */
	private function persistentEntity(): PersistentEntityInterface
	{
		$entity = $this->createStub(PersistentEntityInterface::class);
		$entity->method('getQueueTable')->willReturn('import_queue');
		$entity->method('getQueueStatusField')->willReturn('import_status');
		$entity->method('getQueueProcessingState')->willReturn(2);
		$entity->method('getQueueErrorState')->willReturn(4);
		$entity->method('getMessageLogTable')->willReturn('import_message');
		$entity->method('getFileTable')->willReturn('file');

		return $entity;
	}

	/**
	 * Yield deterministic importer rows.
	 *
	 * @param   array<int, mixed>  $rows  Rows to stream.
	 *
	 * @return  \Generator
	 * @since   6.1.6
	 */
	private function rows(array $rows): \Generator
	{
		foreach ($rows as $row)
		{
			yield $row;
		}
	}
}
