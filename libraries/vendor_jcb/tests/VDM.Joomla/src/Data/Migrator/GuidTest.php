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

namespace VDM\Joomla\Tests\Data\Migrator;


use Exception;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use VDM\Joomla\Data\Items;
use VDM\Joomla\Data\Migrator\Guid;
use VDM\Joomla\Database\Load;
use VDM\Joomla\Database\Update;
use VDM\Tests\Support\TestCase;


/**
 * GUID migration lifecycle, idempotence, and invalid-data tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Guid::class)]
final class GuidTest extends TestCase
{
	/**
	 * Report an idempotent successful scan when no mappings require work.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmptyConfigurationReportsAlreadyMigratedCompletion(): void
	{
		$subject = $this->subject();

		$this->assertSame([
			'Success: scan to migrate linked IDs to linked GUIDs has started on 0 field areas.',
			'Success: migration completed and all linked IDs are now migrated to linked GUIDs (on previous run).'
		], $subject->process([]));
		$this->assertSame([
			'Success: scan to migrate linked IDs to linked GUIDs has started on 0 field areas.',
			'Success: migration completed and all linked IDs are now migrated to linked GUIDs (on previous run).'
		], $subject->process([]));
	}

	/**
	 * Leave an already migrated basic GUID untouched and report idempotence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBasicMappingSkipsExistingGuidWithoutUpdate(): void
	{
		$guid = '8ad39e2f-8931-4383-bf8f-9e00fe56662c';
		$database = $this->databaseWithRows([['id' => 7, 'link' => $guid]]);
		$subject = $this->subject($this->load($database));
		$mapping = [[
			'valueType' => 1,
			'table' => 'records',
			'column' => 'link',
			'linkedTable' => 'targets',
			'linkedColumn' => 'id',
			'array' => false
		]];

		$this->assertSame([
			'Success: scan to migrate linked IDs to linked GUIDs has started on 1 field areas.',
			'Success: migration completed and all linked IDs are now migrated to linked GUIDs (on previous run).'
		], $subject->process($mapping));
	}

	/**
	 * Wrap invalid legacy values with migration context and preserve the root cause.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testInvalidBasicValueRaisesContextualMigrationError(): void
	{
		$database = $this->databaseWithRows([['id' => 9, 'link' => 'not-an-id-or-guid']]);
		$subject = $this->subject($this->load($database));

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Error: migrating linked IDs to linked GUIDs. Invalid value detected:');
		$this->expectExceptionMessage('(targets:table)->(id:column)');

		$subject->process([[
			'valueType' => 1,
			'table' => 'records',
			'column' => 'link',
			'linkedTable' => 'targets',
			'linkedColumn' => 'id',
			'array' => false
		]]);
	}

	/**
	 * Construct a migrator from concrete final collaborators without invoking global state.
	 *
	 * @param   Load|null  $load  Deterministic database load service.
	 *
	 * @return  Guid
	 * @since   6.1.6
	 */
	private function subject(?Load $load = null): Guid
	{
		return new Guid(
			(new ReflectionClass(Items::class))->newInstanceWithoutConstructor(),
			$load ?? (new ReflectionClass(Load::class))->newInstanceWithoutConstructor(),
			(new ReflectionClass(Update::class))->newInstanceWithoutConstructor()
		);
	}

	/**
	 * Create a load service around a mocked database boundary.
	 *
	 * @param   DatabaseInterface  $database  Database boundary.
	 *
	 * @return  Load
	 * @since   6.1.6
	 */
	private function load(DatabaseInterface $database): Load
	{
		$reflection = new ReflectionClass(Load::class);
		$load = $reflection->newInstanceWithoutConstructor();
		$reflection->getProperty('db')->setValue($load, $database);
		$reflection->getProperty('componentCode')->setValue($load, 'example');
		$reflection->getProperty('table')->setValue($load, '#__example');

		return $load;
	}

	/**
	 * Build a database double that returns one reviewed row set.
	 *
	 * @param   array  $rows  Rows returned by the query.
	 *
	 * @return  DatabaseInterface
	 * @since   6.1.6
	 */
	private function databaseWithRows(array $rows): DatabaseInterface
	{
		$query = $this->createStub(QueryInterface::class);
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->method('quoteName')->willReturnCallback(
			static fn (string|array $name, string|array|null $as = null): string|array => $name
		);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute')->willReturn(true);
		$database->expects($this->once())->method('getNumRows')->willReturn(count($rows));
		$database->expects($this->once())->method('loadAssocList')->with(null)->willReturn($rows);

		return $database;
	}
}
