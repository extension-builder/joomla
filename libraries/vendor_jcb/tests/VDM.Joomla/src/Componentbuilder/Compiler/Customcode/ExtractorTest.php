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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Customcode;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Extractor;
use VDM\Tests\Support\CustomcodeExtractorFixture;
use VDM\Tests\Support\TestCase;


/**
 * Installed custom-code marker parsing contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Extractor::class)]
final class ExtractorTest extends TestCase
{
	/**
	 * Emit the stored ID using the comment syntax selected by the marker type.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStartReplaceUsesPhpAndHtmlMarkerSyntax(): void
	{
		$subject = new CustomcodeExtractorFixture();

		$this->assertSame('prefix/*73*/', $subject->startReplace(73, 1, 'prefix'));
		$this->assertSame('prefix<!--73-->', $subject->startReplace(73, 2, 'prefix'));
		$this->assertSame('prefix', $subject->startReplace(0, 1, 'prefix'));
	}

	/**
	 * Capture only content on the requested side of a replacement marker.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLineCheckerReturnsAdjacentContentAndRejectsAnEmptySide(): void
	{
		$subject = new CustomcodeExtractorFixture();

		$this->assertSame('after', $subject->lineContent('MARK', 1, 'before MARK after'));
		$this->assertSame('before', $subject->lineContent('MARK', 2, 'before MARK after'));
		$this->assertFalse($subject->lineContent('MARK', 1, 'before MARK'));
	}

	/**
	 * Recover the first stored numeric ID from PHP and HTML marker lines.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSystemIdExtractsTheFirstMarkerNumberOrReturnsFalse(): void
	{
		$subject = new CustomcodeExtractorFixture();
		$placeholders = [1 => 'CUSTOM/', 2 => 'CUSTOM'];

		$this->assertSame('17', $subject->systemId('CUSTOM//17*/', $placeholders, 1));
		$this->assertSame('29', $subject->systemId('CUSTOM<!--29-->', $placeholders, 2));
		$this->assertFalse($subject->systemId('CUSTOM/no-id', $placeholders, 1));
	}
}
