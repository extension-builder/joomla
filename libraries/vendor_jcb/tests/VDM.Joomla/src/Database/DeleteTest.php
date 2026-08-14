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

namespace VDM\Joomla\Tests\Database;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use VDM\Joomla\Database\Delete;
use VDM\Joomla\Database\QuoteTrait;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Database delete query construction and safety-boundary tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Delete::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesTrait(QuoteTrait::class)]
#[UsesClass(Helper::class)]
final class DeleteTest extends JoomlaTestCase
{
	/**
	 * Original component option.
	 *
	 * @var    string|null
	 * @since  6.1.6
	 */
	private ?string $originalOption = null;

	/**
	 * Install a deterministic component table prefix.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalOption = Helper::$option;
		Helper::setOption('com_example');
	}

	/**
	 * Restore the component option.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		Helper::setOption($this->originalOption);

		parent::tearDown();
	}

	/**
	 * Reject an unbounded delete before creating or executing a query.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemsRejectsEmptyConditionsWithoutDatabaseWork(): void
	{
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->never())->method('createQuery');
		$database->expects($this->never())->method('execute');

		$this->assertFalse((new Delete($database))->items([], 'records'));
	}

	/**
	 * Reject a structured condition without its required value and operator.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemsRejectsMalformedStructuredConditionBeforeExecution(): void
	{
		$query = $this->createStub(QueryInterface::class);
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->expects($this->never())->method('setQuery');
		$database->expects($this->never())->method('execute');

		$this->assertFalse((new Delete($database))->items(['id' => ['operator' => 'IN']], 'records'));
	}

	/**
	 * Compile scalar and structured conditions with their reviewed quoting policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemsBuildsExactConditionsAndExecutesSelectedTable(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('delete')->with('[#__example_records]')->willReturnSelf();
		$query->expects($this->once())
			->method('where')
			->with([
				'[state] = 1',
				'[guid] IN  (\'one\',\'two\')',
				'[access] >= 3',
				'[id] NOT IN  (8,9)',
			])
			->willReturnSelf();
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->method('quoteName')->willReturnCallback(static fn (string $name): string => '[' . $name . ']');
		$database->method('quote')->willReturnCallback(static fn (mixed $value): string => "'" . $value . "'");
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute')->willReturn(true);
		$conditions = [
			'state' => 1,
			'guid' => ['operator' => 'IN', 'value' => ['one', 'two']],
			'access' => ['operator' => '>=', 'value' => 3, 'quote' => false],
			'id' => ['operator' => 'NOT IN', 'value' => [8, 9], 'quote' => false],
		];

		$this->assertTrue((new Delete($database))->items($conditions, 'records'));
	}

	/**
	 * Preserve an explicitly prefixed table when truncating.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTruncatePreservesExplicitPrefix(): void
	{
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->once())->method('truncateTable')->with('#__shared_records');

		(new Delete($database))->truncate('#__shared_records');
		$this->addToAssertionCount(1);
	}
}
