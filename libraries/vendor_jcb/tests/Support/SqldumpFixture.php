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

namespace VDM\Tests\Support;


use Joomla\Database\DatabaseInterface;
use VDM\Joomla\Componentbuilder\Compiler\Model\Sqldump;
use VDM\Joomla\Componentbuilder\Compiler\Registry;


/**
 * Exposes SQL-dump formatting and sizing seams.
 *
 * @since  1.0.0
 */
final class SqldumpFixture extends Sqldump
{
	/**
	 * Constructor.
	 *
	 * @param   Registry           $registry  Compiler registry.
	 * @param   DatabaseInterface  $database  Database boundary.
	 *
	 * @since   1.0.0
	 */
	public function __construct(Registry $registry, DatabaseInterface $database)
	{
		parent::__construct($registry, $database);
	}

	/**
	 * Parse a compact field mapping.
	 *
	 * @param   string  $map    Stored mapping.
	 * @param   string  $alias  Table alias.
	 *
	 * @return  array<string, string>
	 * @since   1.0.0
	 */
	public function mappings(string $map, string $alias): array
	{
		return $this->parseFieldMappings($map, $alias);
	}

	/**
	 * Render insert statements for one view.
	 *
	 * @param   string  $view  View code.
	 * @param   array   $data  Database rows.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function dump(string $view, array $data): string
	{
		return $this->buildSqlDump($view, $data);
	}

	/**
	 * Resolve the insertion chunk size.
	 *
	 * @param   int  $rows  Total row count.
	 *
	 * @return  int
	 * @since   1.0.0
	 */
	public function chunkSize(int $rows): int
	{
		return $this->determineOptimalChunkSize($rows);
	}

	/**
	 * Escape a scalar for generated SQL.
	 *
	 * @param   mixed  $value  Value to escape.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	public function escaped(mixed $value): mixed
	{
		return $this->escape($value);
	}
}
