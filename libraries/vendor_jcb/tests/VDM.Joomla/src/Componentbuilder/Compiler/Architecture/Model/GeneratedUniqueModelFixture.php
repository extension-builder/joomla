<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    30th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Model;


use Closure;
use RuntimeException;


/**
 * Table and model boundaries for executing the compiler's actual copy code.
 *
 * @since  6.2.1
 */
final class GeneratedUniqueModelFixture
{
	/**
	 * The callback supplying a matching row or null without a database.
	 *
	 * @var    Closure
	 * @since  6.2.1
	 */
	public Closure $lookup;

	/**
	 * The actual template method bound to this model boundary.
	 *
	 * @var    Closure
	 * @since  6.2.1
	 */
	public Closure $helper;

	/**
	 * The conditional GUID method emitted only for views with a GUID column.
	 *
	 * @var    Closure
	 * @since  6.2.1
	 */
	public Closure $guidHelper;

	/**
	 * Database settings emitted by the compiler for this view.
	 *
	 * @var    array
	 * @since  6.2.1
	 */
	public array $settings;

	/**
	 * Every requested field and candidate, including PHP type information.
	 *
	 * @var    array
	 * @since  6.2.1
	 */
	public array $queries = [];

	/**
	 * The error recorded by generated code.
	 *
	 * @var    string|null
	 * @since  6.2.1
	 */
	public ?string $error = null;

	/**
	 * Optional table-level error reported alongside a load result.
	 *
	 * @var    string|null
	 * @since  6.2.1
	 */
	public ?string $tableError = null;

	/**
	 * The working row of an executed batch-copy fragment.
	 *
	 * @var    object
	 * @since  6.2.1
	 */
	public object $table;

	/**
	 * Rows available to the batch loader, keyed by primary key.
	 *
	 * @var    array
	 * @since  6.2.1
	 */
	public array $sources = [];

	/**
	 * Rows that reached either persistence boundary.
	 *
	 * @var    array
	 * @since  6.2.1
	 */
	public array $stored = [];

	/**
	 * The batch fixture preconfigures its Joomla user and table boundaries.
	 *
	 * @var    bool
	 * @since  6.2.1
	 */
	public bool $batchSet = true;

	/**
	 * The permission boundary shared by per-record and component checks.
	 *
	 * @var    object
	 * @since  6.2.1
	 */
	public object $user;

	/**
	 * The component permission boundary.
	 *
	 * @var    object
	 * @since  6.2.1
	 */
	public object $canDo;

	/**
	 * Configure one isolated copy operation.
	 *
	 * @param   array    $settings  Compiler-owned column settings.
	 * @param   Closure  $lookup    Returns a matching row or null.
	 *
	 * @since   6.2.1
	 */
	public function __construct(array $settings, Closure $lookup)
	{
		$this->settings = $settings;
		$this->lookup = $lookup;
		$this->table = $this->getTable();
		$this->user = new class()
		{
			/**
			 * Allow the copy permission so tests reach uniqueness handling.
			 *
			 * @param   string  $action   Permission action.
			 * @param   string  $context  Record context.
			 *
			 * @return  bool
			 * @since   6.2.1
			 */
			public function authorise(string $action, string $context): bool
			{
				return true;
			}

			/**
			 * Allow the component's create, batch, and state permissions.
			 *
			 * @param   string  $action  Permission action.
			 *
			 * @return  bool
			 * @since   6.2.1
			 */
			public function get(string $action): bool
			{
				return true;
			}
		};
		$this->canDo = $this->user;
	}

	/**
	 * Return a fresh table while preserving the observable query history.
	 *
	 * @return  object
	 * @since   6.2.1
	 */
	public function getTable(): object
	{
		return new class($this)
		{
			/**
			 * The operation recording database calls.
			 *
			 * @var    GeneratedUniqueModelFixture
			 * @since  6.2.1
			 */
			private GeneratedUniqueModelFixture $owner;

			/**
			 * The primary key of the most recently matched row.
			 *
			 * @var    int|string|null
			 * @since  6.2.1
			 */
			public int|string|null $id = null;

			/**
			 * Non-primary columns of the currently loaded row.
			 *
			 * @var    array
			 * @since  6.2.1
			 */
			private array $values = [];

			/**
			 * Attach the operation boundary.
			 *
			 * @param   GeneratedUniqueModelFixture  $owner  Copy operation.
			 *
			 * @since   6.2.1
			 */
			public function __construct(GeneratedUniqueModelFixture $owner)
			{
				$this->owner = $owner;
			}

			/**
			 * Execute one recorded lookup with a safety bound for a regressed loop.
			 *
			 * @param   array|int  $keys  Unique values or source primary key.
			 *
			 * @return  bool
			 * @throws  RuntimeException  If a broken helper exceeds the test safety bound.
			 * @since   6.2.1
			 */
			public function load(array|int $keys): bool
			{
				if (is_int($keys))
				{
					$row = $this->owner->sources[$keys] ?? null;
					$this->id = $row['id'] ?? null;
					$this->values = $row ?? [];
					unset($this->values['id']);

					return $row !== null;
				}

				$this->owner->queries[] = $keys;

				if (count($this->owner->queries) > 105)
				{
					throw new RuntimeException('The generated copy helper exceeded 105 database lookups.');
				}

				$row = ($this->owner->lookup)($keys, count($this->owner->queries));
				$this->id = $row['id'] ?? null;

				return $row !== null;
			}

			/**
			 * Expose the table's primary key for repeated-row detection.
			 *
			 * @return  string
			 * @since   6.2.1
			 */
			public function getKeyName(): string
			{
				return 'id';
			}

			/**
			 * Report a driver or table-level failure separately from a missing row.
			 *
			 * @return  string|null
			 * @since   6.2.1
			 */
			public function getError(): ?string
			{
				return $this->owner->tableError;
			}

			/**
			 * Read a loaded column through Joomla's dynamic table contract.
			 *
			 * @param   string  $name  Column name.
			 *
			 * @return  mixed
			 * @since   6.2.1
			 */
			public function __get(string $name): mixed
			{
				return $this->values[$name] ?? null;
			}

			/**
			 * Assign a generated copy value.
			 *
			 * @param   string  $name   Column name.
			 * @param   mixed   $value  Copy value.
			 *
			 * @return  void
			 * @since   6.2.1
			 */
			public function __set(string $name, mixed $value): void
			{
				$this->values[$name] = $value;
			}

			/**
			 * Determine whether batch values may override an existing column.
			 *
			 * @param   string  $name  Column name.
			 *
			 * @return  bool
			 * @since   6.2.1
			 */
			public function __isset(string $name): bool
			{
				return isset($this->values[$name]);
			}

			/**
			 * Reset the batch row between source records.
			 *
			 * @return  void
			 * @since   6.2.1
			 */
			public function reset(): void
			{
				$this->id = null;
				$this->values = [];
			}

			/**
			 * Allow unrelated table validation to reach the persistence boundary.
			 *
			 * @return  bool
			 * @since   6.2.1
			 */
			public function check(): bool
			{
				return true;
			}

			/**
			 * Record an inserted copy without a database write.
			 *
			 * @return  bool
			 * @since   6.2.1
			 */
			public function store(): bool
			{
				$this->id = 100 + count($this->owner->stored);
				$this->owner->stored[] = ['id' => $this->id] + $this->values;

				return true;
			}

			/**
			 * Return the inserted primary key through Joomla's property accessor.
			 *
			 * @param   string  $name  Property name.
			 *
			 * @return  mixed
			 * @since   6.2.1
			 */
			public function get(string $name): mixed
			{
				return $name === 'id' ? $this->id : $this->__get($name);
			}
		};
	}

	/**
	 * Return the metadata emitted for the model.
	 *
	 * @return  array
	 * @since   6.2.1
	 */
	public function getUniqueFieldSettings(): array
	{
		return $this->settings;
	}

	/**
	 * Retain the existing unique-field list contract for generated callers.
	 *
	 * @return  array|false
	 * @since   6.2.1
	 */
	public function getUniqueFields(): array|false
	{
		return $this->settings === [] ? false : array_keys($this->settings);
	}

	/**
	 * Execute the actual template helper from a generated caller.
	 *
	 * @param   string  $field  Unique column name.
	 * @param   mixed   $value  Copy candidate.
	 *
	 * @return  mixed
	 * @since   6.2.1
	 */
	public function generateUnique(string $field, mixed $value): mixed
	{
		return ($this->helper)($field, $value);
	}

	/**
	 * Execute the actual conditional GUID renderer's output.
	 *
	 * @return  string
	 * @since   6.2.1
	 */
	public function generateUniqueGuid(): string
	{
		return ($this->guidHelper)();
	}

	/**
	 * Record the user-facing model error without booting Joomla.
	 *
	 * @param   string  $error  Translated copy error.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function setError(string $error): void
	{
		$this->error = $error;
	}

	/**
	 * Isolate batch cache cleanup from an installed Joomla application.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function cleanCache(): void
	{
	}

	/**
	 * Keep error keys and formatting arguments observable to the tests.
	 *
	 * @param   string  $key        Translation key.
	 * @param   mixed   ...$values  Context used to format the error.
	 *
	 * @return  string
	 * @since   6.2.1
	 */
	public function translate(string $key, mixed ...$values): string
	{
		return $key . ':' . implode(':', $values);
	}
}
