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

namespace VDM\Joomla\Tests\Componentbuilder\Spreadsheet;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Spreadsheet\RowDataArray;
use VDM\Tests\Support\TestCase;


/**
 * Spreadsheet row-to-array conversion tests.
 *
 * @since  6.1.6
 */
#[CoversClass(RowDataArray::class)]
final class RowDataArrayTest extends TestCase
{
	/**
	 * Return null for an empty spreadsheet row.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testProcessReturnsNullForEmptyRow(): void
	{
		$spreadsheet = new Spreadsheet();
		$row = $spreadsheet->getActiveSheet()->getRowIterator(1, 1)->current();

		$this->assertNull((new RowDataArray())->process($row));

		$spreadsheet->disconnectWorksheets();
	}

	/**
	 * Return the physical row index and only existing cells as string values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testProcessReturnsIndexAndExistingColumnValues(): void
	{
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setCellValue('A4', 'guid');
		$sheet->setCellValue('C4', 17);
		$row = $sheet->getRowIterator(4, 4)->current();

		$this->assertSame(
			['index' => 4, 'values' => ['A' => 'guid', 'C' => '17']],
			(new RowDataArray())->process($row)
		);

		$spreadsheet->disconnectWorksheets();
	}
}
