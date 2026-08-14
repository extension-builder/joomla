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
use VDM\Joomla\Abstraction\Database;
use VDM\Joomla\Database\Load;
use VDM\Joomla\Database\QuoteTrait;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Database load normalization, query construction, and result-shape tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Load::class)]
#[UsesClass(Database::class)]
#[UsesTrait(QuoteTrait::class)]
#[UsesClass(ArrayHelper::class)]
final class LoadTest extends JoomlaTestCase
{
	/**
	 * Original component option.
	 *
	 * @var    string|null
	 * @since  6.1.6
	 */
	private ?string $originalOption = null;

	/**
	 * Install a deterministic logical-table prefix.
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
	 * Restore component helper state.
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
	 * Reject a query without a resolvable primary table before database work.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowsRejectsMissingPrimaryTable(): void
	{
		$database = $this->createMock(DatabaseInterface::class);
		$database->expects($this->never())->method('createQuery');

		$this->assertNull((new Load($database))->rows(['id'], []));
		$this->assertNull((new Load($database))->rows(['id'], ['b' => 'records']));
	}

	/**
	 * Normalize selection, joins, filters, ordering, limit, and keyed result loading.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRowsBuildsExactStructuredQueryAndLoadsKeyedResults(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('select')->with([
			'[a.id] AS [id]',
			'[b.title] AS [label]'
		])->willReturnSelf();
		$query->expects($this->once())->method('from')->with('[#__example_records] AS [a]')->willReturnSelf();
		$query->expects($this->once())->method('join')->with(
			'INNER',
			'[#__example_titles] AS [b] ON ([a.title_id] = [b.id])'
		)->willReturnSelf();
		$where = [];
		$query->expects($this->exactly(4))->method('where')->willReturnCallback(
			static function (string $condition) use (&$where, &$query): QueryInterface
			{
				$where[] = $condition;
				return $query;
			}
		);
		$query->expects($this->once())->method('order')->with('[a.title] DESC')->willReturnSelf();
		$query->expects($this->once())->method('setLimit')->with(2)->willReturnSelf();
		$database = $this->database($query);
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute')->willReturn(true);
		$database->expects($this->once())->method('getNumRows')->willReturn(2);
		$expected = [2 => ['id' => 2, 'label' => 'A'], 4 => ['id' => 4, 'label' => 'B']];
		$database->expects($this->once())->method('loadAssocList')->with('id')->willReturn($expected);
		$select = ['key' => 'id', 'id', 'b.title' => 'label'];

		$this->assertSame($expected, (new Load($database))->rows(
			$select,
			[
				'a' => 'records',
				'b' => [
					'name' => 'titles',
					'join_on' => 'a.title_id',
					'as_on' => 'b.id',
					'join' => 'inner'
				]
			],
			[
				'state' => 1,
				'id' => ['operator' => 'IN', 'value' => [2, 4]],
				'score' => [
					['operator' => '>=', 'value' => 10, 'quote' => false],
					['operator' => '<', 'value' => 20, 'quote' => false]
				]
			],
			['title' => 'DESC'],
			2
		));
		$this->assertSame([
			'[a.state] = 1',
			'[a.id] IN (2,4)',
			'[a.score] >= 10',
			'[a.score] < 20'
		], $where);
		$this->assertSame(['key' => 'id', 'id', 'b.title' => 'label'], $select);
	}

	/**
	 * Preserve null across every public result shape when the query has no rows.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAllLoadShapesReturnNullWithoutRows(): void
	{
		$query = $this->createStub(QueryInterface::class);
		$database = $this->database($query);
		$database->expects($this->exactly(6))->method('createQuery')->willReturn($query);
		$database->expects($this->exactly(6))->method('setQuery');
		$database->expects($this->exactly(6))->method('execute')->willReturn(true);
		$database->expects($this->exactly(6))->method('getNumRows')->willReturn(0);
		$database->expects($this->never())->method('loadAssocList');
		$database->expects($this->never())->method('loadObjectList');
		$database->expects($this->never())->method('loadAssoc');
		$database->expects($this->never())->method('loadObject');
		$database->expects($this->never())->method('loadResult');
		$database->expects($this->never())->method('loadColumn');
		$subject = new Load($database);

		$this->assertNull($subject->rows(['id'], ['records']));
		$this->assertNull($subject->items(['id'], ['records']));
		$this->assertNull($subject->row(['id'], ['records']));
		$this->assertNull($subject->item(['id'], ['records']));
		$this->assertNull($subject->value(['id'], ['records']));
		$this->assertNull($subject->values(['id'], ['records']));
	}

	/**
	 * Return numeric aggregate results and preserve null for an empty aggregate query.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMaxAndCountCastDatabaseResultsAndPreserveEmptyState(): void
	{
		$query = $this->createStub(QueryInterface::class);
		$database = $this->database($query);
		$database->expects($this->exactly(2))->method('createQuery')->willReturn($query);
		$database->expects($this->exactly(2))->method('setQuery')->with($query);
		$database->expects($this->exactly(2))->method('execute')->willReturn(true);
		$database->expects($this->exactly(2))->method('getNumRows')->willReturnOnConsecutiveCalls(1, 0);
		$database->expects($this->once())->method('loadResult')->willReturn('17');
		$subject = new Load($database);

		$this->assertSame(17, $subject->max('ordering', ['records'], ['state' => 1]));
		$this->assertNull($subject->count(['records'], ['state' => 1]));
	}

	/**
	 * Build a database mock with deterministic identifier and value quoting.
	 *
	 * @param   QueryInterface  $query  Query object returned by createQuery.
	 *
	 * @return  DatabaseInterface&\PHPUnit\Framework\MockObject\MockObject
	 * @since   6.1.6
	 */
	private function database(QueryInterface $query): DatabaseInterface
	{
		$database = $this->createMock(DatabaseInterface::class);
		$database->method('quoteName')->willReturnCallback(
			static function (string|array $name, string|array|null $as = null): string|array
			{
				if (is_array($name))
				{
					$aliases = is_array($as) ? $as : array_fill(0, count($name), null);
					return array_map(
						static fn (string $column, ?string $alias): string => $alias === null
							? '[' . $column . ']'
							: '[' . $column . '] AS [' . $alias . ']',
						$name,
						$aliases
					);
				}

				return $as === null
					? '[' . $name . ']'
					: '[' . $name . '] AS [' . $as . ']';
			}
		);
		$database->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);

		return $database;
	}
}
