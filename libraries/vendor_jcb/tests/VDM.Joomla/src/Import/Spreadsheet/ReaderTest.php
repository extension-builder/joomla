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


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Import\Spreadsheet\Reader;
use VDM\Joomla\Interfaces\Import\FileReaderInterface;
use VDM\Joomla\Interfaces\Spreadsheet\RowDataInterface;
use VDM\Tests\Support\TestCase;


/**
 * Spreadsheet reader orchestration and row-processor tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Reader::class)]
final class ReaderTest extends TestCase
{
	/**
	 * Forward reader arguments and lazily yield each processor result in order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testReadDelegatesAndYieldsProcessedRowsInOrder(): void
	{
		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setCellValue('A1', 'first');
		$sheet->setCellValue('A2', 'second');
		$rows = iterator_to_array($sheet->getRowIterator(1, 2), false);
		$fileReader = $this->createMock(FileReaderInterface::class);
		$fileReader->expects($this->once())
			->method('read')
			->with('/virtual/items.xlsx', 2, 25, 3)
			->willReturn((static function () use ($rows): \Generator
			{
				yield from $rows;
			})());
		$processor = $this->createMock(RowDataInterface::class);
		$processor->expects($this->exactly(2))
			->method('process')
			->willReturnCallback(static fn ($row): string => 'row-' . $row->getRowIndex());
		$generator = (new Reader($fileReader))->read('/virtual/items.xlsx', 2, 25, $processor, 3);

		$this->assertInstanceOf(\Generator::class, $generator);
		$this->assertSame(['row-1', 'row-2'], iterator_to_array($generator, false));

		$spreadsheet->disconnectWorksheets();
	}
}
