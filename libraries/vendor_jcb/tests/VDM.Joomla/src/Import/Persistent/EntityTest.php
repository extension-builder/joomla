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

namespace VDM\Joomla\Tests\Import\Persistent;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Import\Entity as BaseEntity;
use VDM\Joomla\Import\Persistent\Entity;
use VDM\Tests\Support\TestCase;


/**
 * Persistent import queue configuration and validation tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Entity::class)]
#[UsesClass(BaseEntity::class)]
final class EntityTest extends TestCase
{
	/**
	 * Expose stable queue, status, log, and file defaults.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPersistentDefaultsAreStable(): void
	{
		$subject = new Entity();

		$this->assertSame('item_import', $subject->getQueueTable());
		$this->assertSame('import_status', $subject->getQueueStatusField());
		$this->assertSame(1, $subject->getQueueWaitState());
		$this->assertSame(2, $subject->getQueueProcessingState());
		$this->assertSame(3, $subject->getQueueSuccessState());
		$this->assertSame(4, $subject->getQueueErrorState());
		$this->assertSame('message_log', $subject->getMessageLogTable());
		$this->assertSame('file', $subject->getFileTable());
	}

	/**
	 * Apply all persistent values fluently while retaining base configuration behavior.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPersistentSettersAreFluentAndIndependent(): void
	{
		$subject = new Entity();

		$this->assertSame($subject, $subject->setQueueTable('queue'));
		$this->assertSame($subject, $subject->setQueueStatusField('status'));
		$this->assertSame($subject, $subject->setQueueWaitState(10));
		$this->assertSame($subject, $subject->setQueueProcessingState(20));
		$this->assertSame($subject, $subject->setQueueSuccessState(30));
		$this->assertSame($subject, $subject->setQueueErrorState(40));
		$this->assertSame($subject, $subject->setMessageLogTable('logs'));
		$this->assertSame($subject, $subject->setFileTable('files'));
		$this->assertSame($subject, $subject->setParentTable('records'));

		$this->assertSame('queue', $subject->getQueueTable());
		$this->assertSame('status', $subject->getQueueStatusField());
		$this->assertSame(10, $subject->getQueueWaitState());
		$this->assertSame(20, $subject->getQueueProcessingState());
		$this->assertSame(30, $subject->getQueueSuccessState());
		$this->assertSame(40, $subject->getQueueErrorState());
		$this->assertSame('logs', $subject->getMessageLogTable());
		$this->assertSame('files', $subject->getFileTable());
		$this->assertSame('records', $subject->getParentTable());
	}

	/**
	 * Reject invalid persistent queue identifiers and active-state values.
	 *
	 * @param   string  $method  Setter to call.
	 * @param   mixed   $value   Invalid value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('invalidPersistentConfiguration')]
	public function testInvalidPersistentConfigurationIsRejected(string $method, mixed $value): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new Entity())->{$method}($value);
	}

	/**
	 * Supply every validated persistent configuration boundary.
	 *
	 * @return  iterable<string, array{string, mixed}>
	 * @since   6.1.6
	 */
	public static function invalidPersistentConfiguration(): iterable
	{
		yield 'empty queue table' => ['setQueueTable', ''];
		yield 'empty status field' => ['setQueueStatusField', ''];
		yield 'processing status zero' => ['setQueueProcessingState', 0];
		yield 'success status zero' => ['setQueueSuccessState', 0];
		yield 'empty message log table' => ['setMessageLogTable', ''];
		yield 'empty file table' => ['setFileTable', ''];
	}
}
