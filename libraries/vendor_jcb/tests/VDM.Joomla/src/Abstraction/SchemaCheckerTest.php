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

namespace VDM\Joomla\Tests\Abstraction;


use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use VDM\Joomla\Abstraction\SchemaChecker;
use VDM\Joomla\Interfaces\SchemaInterface;
use VDM\Joomla\Interfaces\TableInterface;
use VDM\Tests\Support\MessageApplicationFixture;
use VDM\Tests\Support\SchemaCheckerFixture;
use VDM\Tests\Support\TestCase;


/**
 * Schema-checker update reporting and failure-boundary tests.
 *
 * @since  6.1.6
 */
#[CoversClass(SchemaChecker::class)]
final class SchemaCheckerTest extends TestCase
{
	/**
	 * Enqueue every successful schema action as an application message.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRunEnqueuesEverySchemaUpdateMessage(): void
	{
		$schema = $this->createMock(SchemaInterface::class);
		$schema->expects($this->once())
			->method('update')
			->willReturn(['Created table records', 'Added column records.guid']);
		$app = new MessageApplicationFixture();

		(new SchemaCheckerFixture($schema, null, $app))->run();

		$this->assertSame(
			[
				['message' => 'Created table records', 'type' => 'message'],
				['message' => 'Added column records.guid', 'type' => 'message'],
			],
			$app->messages
		);
	}

	/**
	 * Translate schema update exceptions into a warning without propagation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRunEnqueuesSchemaExceptionAsWarning(): void
	{
		$schema = $this->createStub(SchemaInterface::class);
		$schema->method('update')->willThrowException(new RuntimeException('Database unavailable'));
		$app = new MessageApplicationFixture();

		(new SchemaCheckerFixture($schema, null, $app))->run();

		$this->assertSame(
			[['message' => 'Database unavailable', 'type' => 'warning']],
			$app->messages
		);
	}

	/**
	 * Warn and return when fallback discovery cannot resolve a schema class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRunWarnsWhenSchemaClassCannotBeResolved(): void
	{
		$app = new MessageApplicationFixture();
		$table = $this->createStub(TableInterface::class);

		(new SchemaCheckerFixture(null, $table, $app))->run();

		$this->assertSame(
			[['message' => 'We failed to find/load the Schema class', 'type' => 'warning']],
			$app->messages
		);
	}
}
