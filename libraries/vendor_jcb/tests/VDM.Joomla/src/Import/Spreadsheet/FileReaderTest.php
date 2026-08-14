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


use InvalidArgumentException;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Import\Spreadsheet\ChunkReadFilter;
use VDM\Joomla\Import\Spreadsheet\FileReader;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Chunked spreadsheet row streaming and failure-boundary tests.
 *
 * @since  6.1.6
 */
#[CoversClass(FileReader::class)]
#[UsesClass(ChunkReadFilter::class)]
final class FileReaderTest extends FilesystemTestCase
{
	/**
	 * Stream every row from the selected start across multiple chunks in order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testReadStreamsRequestedRowsAcrossChunks(): void
	{
		$file = $this->writeTemporaryFile(
			'items.csv',
			"heading,value\none,1\ntwo,2\nthree,3\nfour,4\n"
		);
		$indexes = [];
		$values = [];

		foreach ((new FileReader())->read($file, 2, 2) as $row)
		{
			$indexes[] = $row->getRowIndex();
			$values[] = (string) $row->getWorksheet()->getCell('A' . $row->getRowIndex())->getValue();
		}

		$this->assertSame([2, 3, 4, 5], $indexes);
		$this->assertSame(['one', 'two', 'three', 'four'], $values);
	}

	/**
	 * Delay and then raise the documented missing-file error when iteration starts.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testReadRejectsMissingFileWhenGeneratorIsConsumed(): void
	{
		$generator = (new FileReader())->read($this->temporaryPath('missing.csv'), 1, 10);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('File not found:');
		iterator_to_array($generator);
	}

	/**
	 * Reject a starting row beyond the physical spreadsheet boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testReadRejectsStartBeyondHighestRow(): void
	{
		$file = $this->writeTemporaryFile('items.csv', "heading\nvalue\n");

		$this->expectException(OutOfRangeException::class);
		$this->expectExceptionMessage('Start row (3) is beyond highest row (2)');
		iterator_to_array((new FileReader())->read($file, 3, 2));
	}
}
