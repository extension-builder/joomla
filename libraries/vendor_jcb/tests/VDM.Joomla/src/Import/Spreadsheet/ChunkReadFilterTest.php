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

namespace VDM\Joomla\Tests\Import\Spreadsheet;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Import\Spreadsheet\ChunkReadFilter;
use VDM\Tests\Support\TestCase;


/**
 * Spreadsheet chunk boundary tests.
 *
 * @since  6.1.6
 */
#[CoversClass(ChunkReadFilter::class)]
final class ChunkReadFilterTest extends TestCase
{
	/**
	 * Accept only rows inside the inclusive chunk, independent of column or worksheet.
	 *
	 * @param   string  $column     Spreadsheet column.
	 * @param   int     $row        Spreadsheet row.
	 * @param   string  $worksheet  Worksheet name.
	 * @param   bool    $expected   Expected filter decision.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideChunkBoundaryCases')]
	public function testReadCellUsesInclusiveRowBoundaries(
		string $column,
		int $row,
		string $worksheet,
		bool $expected
	): void
	{
		$this->assertSame($expected, (new ChunkReadFilter(4, 3))->readCell($column, $row, $worksheet));
	}

	/**
	 * Supply positions below, at, within, and above the selected chunk.
	 *
	 * @return  iterable<string, array{string, int, string, bool}>
	 * @since   6.1.6
	 */
	public static function provideChunkBoundaryCases(): iterable
	{
		yield 'below start' => ['A', 3, 'First', false];
		yield 'start' => ['A', 4, 'First', true];
		yield 'inside' => ['ZZ', 5, 'Second', true];
		yield 'end' => ['C', 6, '', true];
		yield 'above end' => ['A', 7, 'First', false];
	}
}
