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

namespace VDM\Joomla\Tests\Componentbuilder\Import\Persistent;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Import\Persistent\Assessor;
use VDM\Joomla\Componentbuilder\Import\Persistent\Message;
use VDM\Joomla\Import\Data;
use VDM\Joomla\Interfaces\Data\InsertInterface;
use VDM\Joomla\Interfaces\Data\UpdateInterface;
use VDM\Joomla\Interfaces\Import\MessageInterface;
use VDM\Joomla\Interfaces\Import\StatusInterface;
use VDM\Tests\Support\TestCase;


/**
 * Persistent import message archive and completion assessment contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Message::class)]
#[CoversClass(Assessor::class)]
final class PersistentMessageTest extends TestCase
{
	/**
	 * Archive prior rows and insert every message category with stable statuses.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMessageArchivesAndPersistsCategorizedRows(): void
	{
		$update = $this->createMock(UpdateInterface::class);
		$update->expects($this->once())->method('table')->with('import_message')->willReturnSelf();
		$update->expects($this->once())
			->method('rows')
			->with([['entity' => 'queue-guid', 'published' => -2]], 'entity')
			->willReturn(true);
		$insert = $this->createMock(InsertInterface::class);
		$insert->expects($this->once())->method('table')->with('import_message')->willReturnSelf();
		$insert->expects($this->once())->method('rows')->with($this->callback(
			static function (?array $rows): bool
			{
				if (count($rows ?? []) !== 3 || array_column($rows, 'message_status') !== [1, 2, 3])
				{
					return false;
				}

				foreach ($rows as $index => $row)
				{
					if ($row['entity'] !== 'queue-guid'
						|| $row['entity_type'] !== 'import_queue'
						|| $row['message'] !== ['completed', 'review note', 'failed row'][$index]
						|| preg_match('/^[a-f0-9-]{36}$/', $row['guid']) !== 1)
					{
						return false;
					}
				}

				return true;
			}
		))->willReturn(true);
		$subject = new Message($update, $insert);

		$this->assertSame($subject, $subject->load('queue-guid', 'import_queue', 'import_message'));
		$subject->addSuccess('completed')->addInfo('review note')->addError('failed row');
		$this->assertSame($subject, $subject->archive());
		$this->assertSame($subject, $subject->set());
		$this->assertSame(['completed'], $subject->get()->message_success);
		$this->assertSame(['review note'], $subject->get()->message_info);
		$this->assertSame(['failed row'], $subject->get()->message_error);
		$subject->reset();
		$this->assertSame([], $subject->get()->message_success);
		$this->assertSame([], $subject->get()->message_info);
		$this->assertSame([], $subject->get()->message_error);
	}

	/**
	 * Refuse database operations until all owning entity coordinates are loaded.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMessageRejectsIncompleteOwnershipCoordinates(): void
	{
		$subject = new Message($this->createStub(UpdateInterface::class), $this->createStub(InsertInterface::class));

		try
		{
			$subject->load('', 'entity', 'message');
			$this->fail('Empty GUID was accepted.');
		}
		catch (InvalidArgumentException $error)
		{
			$this->assertSame('GUID, entity, and table must not be null or empty.', $error->getMessage());
		}

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('GUID, entity, and table must not be null or empty.');
		$subject->archive();
	}

	/**
	 * Update queue state to complete only for a perfect import.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPersistentAssessorUpdatesPerfectAndPartialCompletionStates(): void
	{
		$completeData = $this->createMock(Data::class);
		$completeData->expects($this->once())->method('get')->with('import.guid')->willReturn('complete-guid');
		$completeStatus = $this->createMock(StatusInterface::class);
		$completeStatus->expects($this->once())->method('set')->with(3, 'complete-guid');
		$completeMessage = $this->createMock(MessageInterface::class);
		$completeMessage->expects($this->once())->method('addSuccess')->willReturnSelf();
		(new Assessor($completeData, $completeStatus, $completeMessage))->evaluate(10, 10, 0);

		$partialData = $this->createMock(Data::class);
		$partialData->expects($this->once())->method('get')->with('import.guid')->willReturn('partial-guid');
		$partialStatus = $this->createMock(StatusInterface::class);
		$partialStatus->expects($this->once())->method('set')->with(4, 'partial-guid');
		$partialMessage = $this->createMock(MessageInterface::class);
		$partialMessage->expects($this->once())->method('addSuccess')->willReturnSelf();
		(new Assessor($partialData, $partialStatus, $partialMessage))->evaluate(10, 8, 2);
	}

	/**
	 * Mark an empty import as completed with errors and retain its explanation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPersistentAssessorHandlesEmptyImport(): void
	{
		$data = $this->createMock(Data::class);
		$data->expects($this->once())->method('get')->with('import.guid')->willReturn('empty-guid');
		$status = $this->createMock(StatusInterface::class);
		$status->expects($this->once())->method('set')->with(4, 'empty-guid');
		$message = $this->createMock(MessageInterface::class);
		$message->expects($this->once())
			->method('addError')
			->with('COM_COMPONENTBUILDER_NO_ROWS_WERE_PROCESSED')
			->willReturnSelf();

		(new Assessor($data, $status, $message))->evaluate(0, 0, 0);
	}
}
