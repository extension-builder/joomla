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

namespace VDM\Joomla\Tests\Spreadsheet;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Import\Spreadsheet\ChunkReadFilter;
use VDM\Joomla\Spreadsheet\Header;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Spreadsheet header extraction and failure-boundary tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Header::class)]
#[UsesClass(ChunkReadFilter::class)]
final class HeaderTest extends FilesystemTestCase
{
	/**
	 * Extract the first CSV row using spreadsheet column identifiers.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetExtractsDefaultHeaderRow(): void
	{
		$file = $this->writeTemporaryFile(
			'component.csv',
			"guid,system_name,published\nabc,Example,1\n"
		);

		$this->assertSame(
			['A' => 'guid', 'B' => 'system_name', 'C' => 'published'],
			(new Header())->get($file)
		);
	}

	/**
	 * Select an explicit row and omit cells that are not present in the source.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetSelectsRequestedRowAndExistingCellsOnly(): void
	{
		$file = $this->writeTemporaryFile(
			'component.csv',
			"metadata,,\nguid,title\nabc,Example\n"
		);

		$this->assertSame(['A' => 'guid', 'B' => 'title'], (new Header())->get($file, 2));
		$this->assertSame([], (new Header())->get($file, 9));
	}

	/**
	 * Return null for missing and unidentifiable spreadsheet inputs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetReturnsNullForMissingAndInvalidInputs(): void
	{
		$invalid = $this->writeTemporaryFile('invalid.data', "\0\1\2not-a-spreadsheet");

		$this->assertNull((new Header())->get($this->temporaryPath('missing.csv')));
		$this->assertNull((new Header())->get($invalid));
	}
}
