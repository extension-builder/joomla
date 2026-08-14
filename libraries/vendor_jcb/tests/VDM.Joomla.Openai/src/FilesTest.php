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

namespace VDM\Joomla\Openai\Tests;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Openai\Files;
use VDM\Joomla\Openai\Tests\Support\OpenaiTestCase;


/**
 * OpenAI files endpoint test.
 *
 * @since  6.1.6
 */
#[CoversClass(Files::class)]
final class FilesTest extends OpenaiTestCase
{
	/**
	 * Use the correct method and resource path for list, information, content, and delete.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResourceOperationsUseExactMethodsAndPaths(): void
	{
		/** @var Files $subject */
		[$subject, $transport] = $this->createEndpoint(Files::class, [
			[200, '{"data":[]}'],
			[200, '{"id":"file-1"}'],
			[200, "raw\ncontent"],
			[200, '{"deleted":true}']
		]);

		$list = $subject->list();
		$info = $subject->info('file-1');
		$content = $subject->content('file-1');
		$deleted = $subject->delete('file-1');

		$this->assertSame([], $list?->data);
		$this->assertSame('file-1', $info?->id);
		$this->assertSame("raw\ncontent", $content);
		$this->assertTrue($deleted?->deleted);
		$this->assertOpenaiRequest($transport, 'GET', '/files', 0);
		$this->assertOpenaiRequest($transport, 'GET', '/files/file-1', 1);
		$this->assertOpenaiRequest($transport, 'GET', '/files/file-1/content', 2);
		$this->assertOpenaiRequest($transport, 'DELETE', '/files/file-1', 3);
		$this->assertNull($transport->requests[0]['data']);
		$this->assertNull($transport->requests[3]['data']);
	}

	/**
	 * Upload the exact file and purpose payload to the files collection.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUploadSendsFileAndPurposePayload(): void
	{
		/** @var Files $subject */
		[$subject, $transport] = $this->createEndpoint(Files::class);

		$subject->upload('/tmp/training.jsonl', 'fine-tune');

		$this->assertOpenaiRequest($transport, 'POST', '/files');
		$this->assertSame([
			'file' => '/tmp/training.jsonl',
			'purpose' => 'fine-tune'
		], $this->jsonRequest($transport));
	}
}
