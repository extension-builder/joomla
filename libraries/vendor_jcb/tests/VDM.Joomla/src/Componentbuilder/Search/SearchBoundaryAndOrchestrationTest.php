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

namespace VDM\Joomla\Tests\Componentbuilder\Search;


use Joomla\Input\Input;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Search\Agent;
use VDM\Joomla\Componentbuilder\Search\Agent\Find;
use VDM\Joomla\Componentbuilder\Search\Agent\Replace;
use VDM\Joomla\Componentbuilder\Search\Agent\Search;
use VDM\Joomla\Componentbuilder\Search\Agent\Update;
use VDM\Joomla\Componentbuilder\Search\Config;
use VDM\Joomla\Componentbuilder\Search\Database\Insert as DatabaseInsert;
use VDM\Joomla\Componentbuilder\Search\Database\Load as DatabaseLoad;
use VDM\Joomla\Componentbuilder\Search\Interfaces\InsertInterface;
use VDM\Joomla\Componentbuilder\Search\Interfaces\LoadInterface;
use VDM\Joomla\Componentbuilder\Search\Model\Insert as ModelInsert;
use VDM\Joomla\Componentbuilder\Search\Model\Load as ModelLoad;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Joomla\Database\Load as CoreDatabaseLoad;
use VDM\Joomla\Interfaces\TableInterface;
use VDM\Joomla\Utilities\Component\Helper as ComponentHelper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Search orchestration, database boundary, and storage-model contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Agent::class)]
#[CoversClass(DatabaseInsert::class)]
#[CoversClass(DatabaseLoad::class)]
#[CoversClass(ModelInsert::class)]
#[CoversClass(ModelLoad::class)]
#[UsesClass(Config::class)]
#[UsesClass(Table::class)]
final class SearchBoundaryAndOrchestrationTest extends JoomlaTestCase
{
	/**
	 * Protect value loading, non-string signalling, update, and save delegation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAgentRoutesSingleValueOperationsThroughCollaborators(): void
	{
		$config = $this->config(['table_name' => 'demo']);
		$load = $this->createMock(DatabaseLoad::class);
		$insert = $this->createMock(DatabaseInsert::class);
		$update = $this->createMock(Update::class);
		$load->expects($this->exactly(3))
			->method('value')
			->with(7, 'code', 'demo')
			->willReturnOnConsecutiveCalls('Needle', ['not editable'], null);
		$update->expects($this->once())
			->method('value')
			->with('Needle', 2)
			->willReturn('Thread');
		$insert->expects($this->once())
			->method('value')
			->with('saved', 7, 'code', 'demo')
			->willReturn(true);
		$subject = $this->agent($config, $load, $insert, $update);

		$this->assertSame('Thread', $subject->getValue(7, 'code', 2, null, true));
		$this->assertSame(
			'// VALUE CAN NOT BE LOADED (AT THIS TIME) SINCE ITS NOT A STRING',
			$subject->getValue(7, 'code')
		);
		$this->assertNull($subject->getValue(7, 'code'));
		$this->assertTrue($subject->setValue('saved', 7, 'code'));
	}

	/**
	 * Protect bundle iteration and the distinction between find and result state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAgentFindConsumesEveryBundleAndReturnsSearchState(): void
	{
		$config = $this->config(['table_name' => 'demo']);
		$first = [1 => (object) ['id' => 1, 'code' => 'one']];
		$second = [2 => (object) ['id' => 2, 'code' => 'two']];
		$load = $this->createMock(DatabaseLoad::class);
		$load->expects($this->exactly(3))
			->method('items')
			->willReturnMap([
				['demo', 1, $first],
				['demo', 2, $second],
				['demo', 3, null],
			]);
		$find = $this->createMock(Find::class);
		$find->expects($this->exactly(2))
			->method('items')
			->willReturnCallback(function (array $items, string $table) use ($first, $second): void
			{
				$this->assertSame('demo', $table);
				$this->assertContains($items, [$first, $second], true);
			});
		$search = $this->createMock(Search::class);
		$search->expects($this->once())
			->method('get')
			->with('demo')
			->willReturn([1 => ['code' => [1 => 'marked']]]);
		$subject = $this->agent($config, $load, search: $search, find: $find);

		$this->assertSame([1 => ['code' => [1 => 'marked']]], $subject->find());
	}

	/**
	 * Protect replacement batches, successful write counting, and state resets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAgentReplaceCountsSuccessfulBatchesAndAlwaysResetsState(): void
	{
		$config = $this->config(['table_name' => 'demo']);
		$batches = [
			[1 => (object) ['id' => 1, 'code' => 'one']],
			[2 => (object) ['id' => 2, 'code' => 'two']],
		];
		$load = $this->createMock(DatabaseLoad::class);
		$load->expects($this->exactly(3))
			->method('items')
			->willReturnOnConsecutiveCalls($batches[0], $batches[1], null);
		$find = $this->createMock(Find::class);
		$find->expects($this->exactly(2))->method('items');
		$find->expects($this->exactly(2))->method('get')->willReturnOnConsecutiveCalls($batches[0], $batches[1]);
		$find->expects($this->exactly(2))->method('reset')->with('demo');
		$replace = $this->createMock(Replace::class);
		$replace->expects($this->exactly(2))->method('items');
		$replace->expects($this->exactly(2))->method('get')->willReturnOnConsecutiveCalls($batches[0], $batches[1]);
		$replace->expects($this->exactly(2))->method('reset')->with('demo');
		$insert = $this->createMock(DatabaseInsert::class);
		$insert->expects($this->exactly(2))->method('items')->willReturnOnConsecutiveCalls(true, false);
		$subject = $this->agent($config, $load, $insert, find: $find, replace: $replace);

		$this->assertSame(1, $subject->replace());
	}

	/**
	 * Protect database update shape, field-name mapping, and model encoding.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDatabaseInsertMapsOneValueToJoomlaUpdateObject(): void
	{
		$database = new class()
		{
			/**
			 * Recorded database update calls.
			 *
			 * @var    array<int, mixed>
			 * @since  6.1.6
			 */
			public array $calls = [];

			/**
			 * Record a Joomla update-object call.
			 *
			 * @param   string  $table  Database table.
			 * @param   object  $item   Values to update.
			 * @param   string  $key    Key property.
			 *
			 * @return  bool
			 * @since   6.1.6
			 */
			public function updateObject(string $table, object $item, string $key): bool
			{
				$this->calls[] = [$table, clone $item, $key];

				return true;
			}
		};
		$this->setJoomlaFactoryProperty('database', $database);
		$config = $this->config(['table_name' => 'demo']);
		$table = $this->createMock(Table::class);
		$table->expects($this->once())->method('get')->with('demo', 'code', 'name')->willReturn('code_column');
		$model = $this->createMock(ModelInsert::class);
		$model->expects($this->once())->method('value')->with('raw', 'code_column', 'demo')->willReturn('stored');
		$subject = new DatabaseInsert($config, $table, $model);

		$this->assertInstanceOf(InsertInterface::class, $subject);
		$this->assertTrue($subject->value('raw', 12, 'code'));
		$this->assertSame('#__componentbuilder_demo', $database->calls[0][0]);
		$this->assertEquals((object) ['id' => 12, 'code_column' => 'stored'], $database->calls[0][1]);
		$this->assertSame('id', $database->calls[0][2]);
	}

	/**
	 * Protect storage transforms and invalid JSON passthrough.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSearchModelsRoundTripBase64AndJsonStorage(): void
	{
		$config = $this->config(['table_name' => 'demo']);
		$table = $this->createStub(Table::class);
		$table->method('get')->willReturnCallback(
			static fn (string $tableName, string $field, string $property): ?string => match ($field)
			{
				'code' => 'base64',
				'params' => 'json',
				default => null,
			}
		);
		$insert = new ModelInsert($config, $table);
		$load = new ModelLoad($config, $table);

		$this->assertSame('Y29kZQ==', $insert->value('code', 'code'));
		$this->assertSame('code', $load->value('Y29kZQ==', 'code'));
		$this->assertSame('{"one":1,"two":2}', $insert->value(['one' => 1, 'two' => 2], 'params'));
		$this->assertSame(['one' => 1, 'two' => 2], $load->value('{"one":1,"two":2}', 'params'));
		$this->assertSame('not-json', $load->value('not-json', 'params'));
	}

	/**
	 * Protect database field aliases and deterministic bundle start arithmetic.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDatabaseLoadBuildsAliasesAndBundleOffsets(): void
	{
		$config = $this->config(['table_name' => 'demo']);
		$table = $this->createStub(Table::class);
		$table->method('fields')->willReturn(['title', 'code']);
		$model = $this->createStub(ModelLoad::class);
		$subject = new class($config, $table, $model) extends DatabaseLoad
		{
			/**
			 * Build a database-load probe without opening a database connection.
			 *
			 * @param   Config     $config  Search configuration.
			 * @param   Table      $table   Table metadata.
			 * @param   ModelLoad  $model   Load model.
			 *
			 * @since   6.1.6
			 */
			public function __construct(Config $config, Table $table, ModelLoad $model)
			{
				$this->config = $config;
				$this->table = $table;
				$this->model = $model;
			}

			/**
			 * Expose database-field alias generation.
			 *
			 * @param   string  $table  Table name.
			 *
			 * @return  array<string, string>|null
			 * @since   6.1.6
			 */
			public function aliases(string $table): ?array
			{
				return $this->setDatabaseFields($table);
			}

			/**
			 * Expose bundle-offset arithmetic.
			 *
			 * @param   int  $bundle  One-based bundle number.
			 *
			 * @return  int
			 * @since   6.1.6
			 */
			public function offset(int $bundle): int
			{
				return $this->incremental($bundle);
			}
		};

		$this->assertInstanceOf(LoadInterface::class, $subject);
		$this->assertSame(['a.id' => 'id', 'a.title' => 'title', 'a.code' => 'code'], $subject->aliases('demo'));
		$this->assertSame(1, $subject->offset(1));
		$this->assertSame(301, $subject->offset(2));
		$this->assertSame(1201, $subject->offset(5));
	}

	/**
	 * Protect the shared ID axis used by bundled ordering and continuation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDatabaseLoadOrdersLimitedBundlesById(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->method('select')->willReturnSelf();
		$query->method('from')->willReturnSelf();
		$query->expects($this->once())->method('where')->with('[a.id] >= 310')->willReturnSelf();
		$query->expects($this->once())->method('order')->with('[a.id] ASC')->willReturnSelf();
		$query->expects($this->once())->method('setLimit')->with(300)->willReturnSelf();

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
		$database->expects($this->once())->method('createQuery')->willReturn($query);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute')->willReturn(true);
		$database->expects($this->once())->method('getNumRows')->willReturn(0);
		$database->expects($this->never())->method('loadObjectList');

		$config = $this->config(['table_name' => 'demo']);
		$table = $this->createStub(Table::class);
		$table->method('fields')->willReturn(['title', 'code']);
		$table->method('titleName')->willReturn('title');
		$model = $this->createStub(ModelLoad::class);
		$model->method('last')->willReturn(309);
		$originalOption = ComponentHelper::$option;
		ComponentHelper::setOption('com_componentbuilder');

		try
		{
			$load = new CoreDatabaseLoad($database);
			$subject = new DatabaseLoad($config, $table, $model, $load);

			$this->assertNull($subject->items('demo', 2));
		}
		finally
		{
			ComponentHelper::setOption($originalOption);
		}
	}

	/**
	 * Walk real model cursors across reversed titles without omissions or repeats.
	 *
	 * @param   int   $count       Number of stored records.
	 * @param   int   $stride      Gap between stored identifiers.
	 * @param   bool  $duplicates  Whether adjacent titles are identical.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('bundleDatasets')]
	public function testDatabaseLoadVisitsEveryRecordExactlyOnce(
		int $count, int $stride, bool $duplicates
	): void
	{
		$records = [];

		for ($number = 1; $number <= $count; $number++)
		{
			$id = $number * $stride;
			$records[$id] = (object) [
				'id' => (string) $id,
				'title' => sprintf('Title %04d', $duplicates ? intdiv($count - $number, 2) : $count - $number),
				'code' => base64_encode('Code ' . $id),
			];
		}

		$config = $this->config(['table_name' => 'demo']);
		$table = $this->createStub(Table::class);
		$table->method('titleName')->willReturn('title');
		$table->method('get')->willReturnCallback(
			static fn (string $name, string $field, string $property): ?string => $field === 'code' ? 'base64' : null
		);
		$table->method('fields')->willReturnCallback(
			static fn (string $name, bool $default = false): array => $default ? ['id', 'title', 'code'] : ['title', 'code']
		);
		$model = new ModelLoad($config, $table);
		$requests = [];
		$load = $this->datasetLoad($records, $requests, (int) ceil($count / 300) + 1);
		$subject = new DatabaseLoad($config, $table, $model, $load);
		$ids = [];
		$sizes = [];
		$expectedRequests = [1];
		$terminated = false;

		for ($bundle = 1; $bundle <= (int) ceil($count / 300) + 1; $bundle++)
		{
			$items = $subject->items('demo', $bundle);

			if ($items === null)
			{
				$terminated = true;
				break;
			}

			$sizes[] = count($items);

			foreach ($items as $id => $item)
			{
				$ids[] = $id;
				$this->assertSame('Code ' . $id, $item->code);
			}

			$expectedRequests[] = end($ids) + 1;
		}

		$this->assertTrue($terminated, 'An exhausted dataset must terminate the bundle walk.');
		$this->assertSame(array_keys($records), $ids);
		$this->assertCount($count, array_unique($ids));
		$this->assertSame($expectedRequests, array_column($requests, 'start'));
		$this->assertSame(['id'], array_values(array_unique(array_column($requests, 'order'))));
		$this->assertSame([300], array_values(array_unique(array_column($requests, 'limit'))));
		$this->assertSame(array_map('count', array_chunk($records, 300)), $sizes);
	}

	/**
	 * Cover a complete two-bundle replay, sparse IDs, duplicate titles and boundaries.
	 *
	 * @return  array<string, array{int, int, bool}>
	 * @since   6.2.0
	 */
	public static function bundleDatasets(): array
	{
		return [
			'600 reversed titles' => [600, 1, false],
			'600 sparse IDs and duplicate titles' => [600, 7, true],
			'empty table' => [0, 1, false],
			'one record' => [1, 5, false],
			'below one full bundle' => [299, 3, true],
			'one full bundle' => [300, 2, false],
			'one full bundle plus one' => [301, 4, true],
			'two full bundles plus one' => [601, 3, false],
		];
	}

	/**
	 * Keep the title sort and unlimited result contract outside bundled searches.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testUnbundledLoadRetainsTitleOrdering(): void
	{
		$config = $this->config(['table_name' => 'demo']);
		$table = $this->createStub(Table::class);
		$table->method('fields')->willReturn(['title', 'code']);
		$table->method('titleName')->willReturn('title');
		$requests = [];
		$load = $this->datasetLoad([], $requests, 1);
		$subject = new DatabaseLoad($config, $table, new ModelLoad($config, $table), $load);

		$this->assertNull($subject->items('demo'));
		$this->assertSame([['start' => null, 'order' => 'title', 'limit' => null]], $requests);
	}

	/**
	 * Record the missing StringHelper import as a desired model contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testInsertModelCanValidateAndModelAWholeItem(): void
	{
		$config = $this->config(['table_name' => 'demo']);
		$table = $this->createStub(Table::class);
		$table->method('get')->willReturn(null);
		$table->method('fields')->willReturn(['code', 'title']);
		$subject = new ModelInsert($config, $table);

		$this->assertEquals(
			(object) ['code' => 'value', 'title' => 'Example'],
			$subject->item((object) ['code' => 'value', 'title' => 'Example'], 'demo')
		);
	}

	/**
	 * Feed deterministic records through the real query builder and model boundary.
	 *
	 * @param   array  $records   Stored database rows keyed by identifier.
	 * @param   array  $requests  Captured query cursor, ordering and limit.
	 * @param   int    $calls     Expected number of database queries.
	 *
	 * @return  CoreDatabaseLoad
	 * @since   6.2.0
	 */
	private function datasetLoad(array $records, array &$requests, int $calls): CoreDatabaseLoad
	{
		$state = [];
		$rows = [];
		$query = $this->createMock(QueryInterface::class);
		$query->method('select')->willReturnSelf();
		$query->method('from')->willReturnSelf();
		$query->method('where')->willReturnCallback(function (string $clause) use (&$state, $query): QueryInterface
		{
			$this->assertMatchesRegularExpression('/^\[a\.id\] >= [0-9]+$/', $clause);
			$state['start'] = (int) substr($clause, strlen('[a.id] >= '));

			return $query;
		});
		$query->expects($this->exactly($calls))->method('order')->willReturnCallback(function (string $order) use (&$state, $query): QueryInterface
		{
			$this->assertContains($order, ['[a.id] ASC', '[a.title] ASC']);
			$state['order'] = $order === '[a.id] ASC' ? 'id' : 'title';

			return $query;
		});
		$query->method('setLimit')->willReturnCallback(static function (int $limit) use (&$state, $query): QueryInterface
		{
			$state['limit'] = $limit;

			return $query;
		});
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

				return $as === null ? '[' . $name . ']' : '[' . $name . '] AS [' . $as . ']';
			}
		);
		$database->method('quote')->willReturnCallback(static fn (mixed $value): string => "'" . (string) $value . "'");
		$database->expects($this->exactly($calls))->method('createQuery')->willReturnCallback(
			static function () use (&$state, $query): QueryInterface
			{
				$state = ['start' => null, 'order' => null, 'limit' => null];

				return $query;
			}
		);
		$database->expects($this->exactly($calls))->method('setQuery')->with($query);
		$database->expects($this->exactly($calls))->method('execute')->willReturnCallback(
			static function () use ($records, &$requests, &$state, &$rows): bool
			{
				$requests[] = $state;
				$rows = array_filter($records, static fn (object $row): bool => $row->id >= ($state['start'] ?? 0));
				uasort($rows, static fn (object $left, object $right): int => $left->{$state['order']} <=> $right->{$state['order']});
				$rows = array_slice($rows, 0, $state['limit'], true);

				return true;
			}
		);
		$database->method('getNumRows')->willReturnCallback(static function () use (&$rows): int
		{
			return count($rows);
		});
		$database->method('loadObjectList')->willReturnCallback(function (string $key) use (&$rows): array
		{
			$this->assertSame('id', $key);

			return array_map(static fn (object $row): object => clone $row, $rows);
		});
		$originalOption = ComponentHelper::$option;
		ComponentHelper::setOption('com_componentbuilder');

		try
		{
			return new CoreDatabaseLoad($database);
		}
		finally
		{
			ComponentHelper::setOption($originalOption);
		}
	}

	/**
	 * Build an orchestration subject with harmless defaults for unused services.
	 *
	 * @param   Config               $config   Search configuration.
	 * @param   DatabaseLoad|null    $load     Read boundary.
	 * @param   DatabaseInsert|null  $insert   Write boundary.
	 * @param   Update|null          $update   Update agent.
	 * @param   Search|null          $search   Search-state agent.
	 * @param   Find|null            $find     Find agent.
	 * @param   Replace|null         $replace  Replace agent.
	 *
	 * @return  Agent
	 * @since   6.1.6
	 */
	private function agent(
		Config $config,
		?DatabaseLoad $load = null,
		?DatabaseInsert $insert = null,
		?Update $update = null,
		?Search $search = null,
		?Find $find = null,
		?Replace $replace = null
	): Agent
	{
		return new Agent(
			$config,
			$load ?? $this->createStub(DatabaseLoad::class),
			$insert ?? $this->createStub(DatabaseInsert::class),
			$find ?? $this->createStub(Find::class),
			$replace ?? $this->createStub(Replace::class),
			$search ?? $this->createStub(Search::class),
			$update ?? $this->createStub(Update::class),
			$this->createStub(TableInterface::class)
		);
	}

	/**
	 * Build a search configuration from deterministic request input.
	 *
	 * @param   array<string, mixed>  $values  Request values.
	 *
	 * @return  Config
	 * @since   6.1.6
	 */
	private function config(array $values): Config
	{
		return new Config(new Input($values));
	}
}
