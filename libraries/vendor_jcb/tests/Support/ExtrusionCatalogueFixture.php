<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use VDM\Joomla\Interfaces\Database\LoadInterface;


/**
 * The JCB field type catalogue, served without a database.
 *
 * The extrusion engine reads its Joomla-to-JCB field type mapping out of the
 * fieldtype table rather than hardcoding it, so the database loader is the only
 * boundary that has to be faked to exercise the whole write path. Each served row
 * carries the same shape a real row does: a properties JSON whose first entry is
 * the type property, whose example is the Joomla XML type string.
 *
 * Constructing this with an empty catalogue is deliberate and useful: it is how a
 * field whose type cannot be resolved at all is provoked.
 *
 * @since  6.1.6
 */
final class ExtrusionCatalogueFixture implements LoadInterface
{
	/**
	 * The served field types, as row id, JCB name, and Joomla XML type.
	 *
	 * @var    array<int, array{id: int, name: string, type: string}>
	 * @since  6.1.6
	 */
	public const TYPES = [
		['id' => 1, 'name' => 'Text', 'type' => 'text'],
		['id' => 2, 'name' => 'Editor', 'type' => 'editor'],
		['id' => 3, 'name' => 'List', 'type' => 'list'],
		['id' => 4, 'name' => 'Color', 'type' => 'color'],
		['id' => 5, 'name' => 'Number', 'type' => 'number'],
		['id' => 6, 'name' => 'Textarea', 'type' => 'textarea'],
		['id' => 7, 'name' => 'Checkbox', 'type' => 'checkbox'],
		['id' => 8, 'name' => 'Radio', 'type' => 'radio'],
		['id' => 98, 'name' => 'CustomUser', 'type' => 'customuser'],
		['id' => 99, 'name' => 'Custom', 'type' => 'custom']
	];

	/**
	 * The attribute names every served field type declares.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	public const ATTRIBUTES = [
		'name', 'label', 'size', 'maxlength', 'default', 'description', 'class',
		'required', 'readonly', 'disabled', 'multiple', 'filter', 'validate',
		'message', 'hint'
	];

	/**
	 * The catalogue this instance serves.
	 *
	 * @var    array<int, array{id: int, name: string, type: string}>
	 * @since  6.1.6
	 */
	private array $types;

	/**
	 * How many times the catalogue was actually loaded.
	 *
	 * @var    int
	 * @since  6.1.6
	 */
	private int $calls = 0;

	/**
	 * Constructor.
	 *
	 * @param   array<int, array{id: int, name: string, type: string}>|null  $types  The catalogue to serve, or null for the default.
	 *
	 * @since   6.1.6
	 */
	public function __construct(?array $types = null)
	{
		$this->types = $types ?? self::TYPES;
	}

	/**
	 * The guid the default catalogue gives one JCB field type.
	 *
	 * A real fieldtype row is identified by its guid and nothing else -- the numeric
	 * id is install specific, and field.fieldtype is a VARCHAR(36) holding the guid.
	 * The served guids are derived from the row id so they stay stable and readable.
	 *
	 * @param   string  $name  The JCB field type name.
	 *
	 * @return  string  The guid, or an empty string when the type is not served.
	 * @since   6.1.6
	 */
	public static function identity(string $name): string
	{
		foreach (self::TYPES as $type)
		{
			if (strcasecmp($type['name'], $name) === 0)
			{
				return self::guid($type['id']);
			}
		}

		return '';
	}

	/**
	 * The guid this fixture serves for one row id.
	 *
	 * @param   int  $id  The row id.
	 *
	 * @return  string  A stable well formed guid.
	 * @since   6.1.6
	 */
	public static function guid(int $id): string
	{
		return sprintf('%08d-0000-4000-8000-000000000000', $id);
	}

	/**
	 * How many times the catalogue was loaded from this boundary.
	 *
	 * @return  int  The number of loads.
	 * @since   6.1.6
	 */
	public function calls(): int
	{
		return $this->calls;
	}

	/**
	 * Load data rows as an array of associated arrays.
	 *
	 * @param   array       $select  Array of selection keys.
	 * @param   array       $tables  Array of tables to search.
	 * @param   array|null  $where   Array of where key=>value match exist.
	 * @param   array|null  $order   Array of how to order the data.
	 * @param   int|null    $limit   Limit the number of values returned.
	 *
	 * @return  array|null
	 * @since   6.1.6
	 */
	public function rows(array $select, array $tables, ?array $where = null,
		?array $order = null, ?int $limit = null): ?array
	{
		return null;
	}

	/**
	 * Load data rows as an array of objects.
	 *
	 * @param   array       $select  Array of selection keys.
	 * @param   array       $tables  Array of tables to search.
	 * @param   array|null  $where   Array of where key=>value match exist.
	 * @param   array|null  $order   Array of how to order the data.
	 * @param   int|null    $limit   Limit the number of values returned.
	 *
	 * @return  array|null  The served field type rows.
	 * @since   6.1.6
	 */
	public function items(array $select, array $tables, ?array $where = null,
		?array $order = null, ?int $limit = null): ?array
	{
		// this boundary serves the field type catalogue; any other table a
		// resolver asks about simply has no rows here, and only a real
		// catalogue read counts toward the once-per-request expectation
		if (!in_array('fieldtype', $tables, true))
		{
			return [];
		}

		$this->calls++;
		$rows = [];

		foreach ($this->types as $type)
		{
			$rows[] = (object) [
				'id' => $type['id'],
				'guid' => self::guid($type['id']),
				'name' => $type['name'],
				'properties' => json_encode($this->properties($type['type']))
			];
		}

		return $rows;
	}

	/**
	 * Load data row as an associated array.
	 *
	 * @param   array       $select  Array of selection keys.
	 * @param   array       $tables  Array of tables to search.
	 * @param   array|null  $where   Array of where key=>value match exist.
	 * @param   array|null  $order   Array of how to order the data.
	 *
	 * @return  array|null
	 * @since   6.1.6
	 */
	public function row(array $select, array $tables, ?array $where = null,
		?array $order = null): ?array
	{
		return null;
	}

	/**
	 * Load data row as an object.
	 *
	 * @param   array       $select  Array of selection keys.
	 * @param   array       $tables  Array of tables to search.
	 * @param   array|null  $where   Array of where key=>value match exist.
	 * @param   array|null  $order   Array of how to order the data.
	 *
	 * @return  object|null
	 * @since   6.1.6
	 */
	public function item(array $select, array $tables, ?array $where = null,
		?array $order = null): ?object
	{
		return null;
	}

	/**
	 * Get the max value based on a filtered result from a given table.
	 *
	 * @param   string  $field   The field key.
	 * @param   array   $tables  The table.
	 * @param   array   $filter  The filter keys.
	 *
	 * @return  int|null
	 * @since   6.1.6
	 */
	public function max($field, array $tables, array $filter): ?int
	{
		return null;
	}

	/**
	 * Count the number of items based on filter result from a given table.
	 *
	 * @param   array  $tables  The table.
	 * @param   array  $filter  The filter keys.
	 *
	 * @return  int|null
	 * @since   6.1.6
	 */
	public function count(array $tables, array $filter): ?int
	{
		return null;
	}

	/**
	 * Load one value from a row.
	 *
	 * @param   array       $select  Array of selection keys.
	 * @param   array       $tables  Array of tables to search.
	 * @param   array|null  $where   Array of where key=>value match exist.
	 * @param   array|null  $order   Array of how to order the data.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	public function value(array $select, array $tables, ?array $where = null,
		?array $order = null)
	{
		// the component linkers resolve the target component's guid by its
		// id, because the link columns speak guid -- serve one per id, the
		// same way every install holds one
		if (($tables['a'] ?? null) === 'joomla_component'
			&& isset($select['a.guid'], $where['a.id']))
		{
			return self::componentGuid((int) $where['a.id']);
		}

		return null;
	}

	/**
	 * The served component guid for one component id.
	 *
	 * @param   int  $id  The component id.
	 *
	 * @return  string  The stable guid this fixture serves for that id.
	 * @since   6.1.7
	 */
	public static function componentGuid(int $id): string
	{
		return sprintf('eeeeeeee-%04d-4999-8999-999999999999', $id);
	}

	/**
	 * Load values from multiple rows.
	 *
	 * @param   array       $select  Array of selection keys.
	 * @param   array       $tables  Array of tables to search.
	 * @param   array|null  $where   Array of where key=>value match exist.
	 * @param   array|null  $order   Array of how to order the data.
	 * @param   int|null    $limit   Limit the number of values returned.
	 *
	 * @return  array|null
	 * @since   6.1.6
	 */
	public function values(array $select, array $tables, ?array $where = null,
		?array $order = null, ?int $limit = null): ?array
	{
		return null;
	}

	/**
	 * The properties JSON payload of one served field type.
	 *
	 * @param   string  $type  The Joomla XML type string.
	 *
	 * @return  array<int, array{name: string, example: string}>  The declared properties.
	 * @since   6.1.6
	 */
	private function properties(string $type): array
	{
		$properties = [['name' => 'type', 'example' => $type]];

		foreach (self::ATTRIBUTES as $attribute)
		{
			$properties[] = ['name' => $attribute, 'example' => ''];
		}

		return $properties;
	}
}
