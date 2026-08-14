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

namespace VDM\Joomla\Tests\File;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use VDM\Joomla\File\Agent;
use VDM\Joomla\File\Definition;
use VDM\Joomla\File\TypeDefinition;
use VDM\Joomla\Interfaces\File\AgentInterface;
use VDM\Joomla\Interfaces\File\HandlerInterface;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Stateless upload-agent orchestration contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Agent::class)]
#[CoversClass(AgentInterface::class)]
#[UsesClass(Definition::class)]
#[UsesClass(TypeDefinition::class)]
final class AgentTest extends FilesystemTestCase
{
	/**
	 * Reject an upload before any handler interaction when no type is bound.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetRequiresATypeDefinition(): void
	{
		$handler = $this->createMock(HandlerInterface::class);
		$handler->expects($this->never())->method('getFile');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('without File Type Definition loaded');

		(new Agent($handler))->get();
	}

	/**
	 * Configure the handler in order and materialize its upload details as a definition.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetConfiguresHandlerAndReturnsUploadedFileDefinition(): void
	{
		$file = $this->writeTemporaryFile('uploads/stored.txt', 'uploaded content');
		$handler = $this->createMock(HandlerInterface::class);
		$handler->expects($this->once())
			->method('setEnqueueError')
			->with(false)
			->willReturnSelf();
		$handler->expects($this->once())
			->method('setLegalFormats')
			->with(['txt', 'md'])
			->willReturnSelf();
		$handler->expects($this->once())
			->method('getFile')
			->with('attachment', 'document', 'safe', '/srv/uploads')
			->willReturn([
				'name' => 'original.txt',
				'file_name' => 'stored.txt',
				'full_path' => $file,
				'random' => 'abc',
				'extension' => 'txt',
				'size' => 16,
				'mime' => 'text/plain'
			]);
		$subject = new Agent($handler);
		$type = new TypeDefinition([
			'field' => 'attachment',
			'type' => 'document',
			'filter' => 'safe',
			'path' => '/srv/uploads/',
			'formats' => ['txt', 'md']
		]);

		$this->assertSame($subject, $subject->type($type));
		$result = $subject->get();

		$this->assertInstanceOf(Definition::class, $result);
		$this->assertSame('original.txt', $result->name());
		$this->assertSame('stored.txt', $result->fileName());
		$this->assertSame($file, $result->filePath());
		$this->assertSame('abc', $result->random());
	}

	/**
	 * Clear the bound type after a successful upload to prevent accidental reuse.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSuccessfulGetConsumesTheBoundType(): void
	{
		$file = $this->writeTemporaryFile('uploads/once.txt', 'once');
		$handler = $this->createStub(HandlerInterface::class);
		$handler->method('setEnqueueError')->willReturnSelf();
		$handler->method('setLegalFormats')->willReturnSelf();
		$handler->method('getFile')->willReturn([
			'name' => 'once.txt',
			'file_name' => 'once.txt',
			'full_path' => $file
		]);
		$subject = new Agent($handler);
		$subject->type(new TypeDefinition([
			'field' => 'upload',
			'type' => 'file',
			'path' => '/srv/uploads'
		]));

		$subject->get();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('without File Type Definition loaded');

		$subject->get();
	}

	/**
	 * Translate a null handler result into the exact collected handler error.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUploadFailurePropagatesCollectedHandlerErrors(): void
	{
		$handler = $this->createMock(HandlerInterface::class);
		$handler->method('setEnqueueError')->willReturnSelf();
		$handler->method('setLegalFormats')->willReturnSelf();
		$handler->expects($this->once())->method('getFile')->willReturn(null);
		$handler->expects($this->once())
			->method('getErrors')
			->with()
			->willReturn('Upload rejected: invalid signature.');
		$subject = new Agent($handler);
		$subject->type(new TypeDefinition([
			'field' => 'upload',
			'type' => 'file',
			'path' => '/srv/uploads'
		]));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Upload rejected: invalid signature.');

		$subject->get();
	}

	/**
	 * Delegate deletion and preserve the handler's success or failure result.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDeleteReturnsTheExactHandlerResult(): void
	{
		$handler = $this->createMock(HandlerInterface::class);
		$handler->expects($this->exactly(2))
			->method('removeFile')
			->willReturnMap([
				['/srv/uploads/present.txt', true],
				['/srv/uploads/missing.txt', false]
			]);
		$subject = new Agent($handler);

		$this->assertTrue($subject->delete('/srv/uploads/present.txt'));
		$this->assertFalse($subject->delete('/srv/uploads/missing.txt'));
	}
}
