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

namespace VDM\Joomla\Tests\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use VDM\Joomla\Utilities\DateHelper;
use VDM\Tests\Support\TestCase;


/**
 * Deterministic date formatting and validation contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(DateHelper::class)]
final class DateHelperTest extends TestCase
{
	/**
	 * Original process timezone restored after every test.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private string $originalTimezone;

	/**
	 * Fix date formatting to UTC for deterministic expectations.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalTimezone = date_default_timezone_get();
		date_default_timezone_set('UTC');
	}

	/**
	 * Restore global timezone state even when a test fails.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		date_default_timezone_set($this->originalTimezone);

		parent::tearDown();
	}

	/**
	 * Format one timestamp through every stable public formatter.
	 *
	 * @param   string  $method    Public formatter name.
	 * @param   array   $arguments  Arguments passed to the formatter.
	 * @param   string  $expected   Exact UTC result.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('fixedFormatterProvider')]
	public function testFixedTimestampFormattersReturnExactOutput(
		string $method,
		array $arguments,
		string $expected
	): void
	{
		$this->assertSame($expected, DateHelper::$method(...$arguments));
	}

	/**
	 * Provide stable UTC examples for each formatting contract.
	 *
	 * @return  iterable<string, array{string, array, string}>
	 * @since   6.1.6
	 */
	public static function fixedFormatterProvider(): iterable
	{
		$timestamp = 1714731296;

		yield 'fancy date' => ['fancyDate', [$timestamp], '3rd of May 2024'];
		yield 'day, time, and date' => ['fancyDayTimeDate', [$timestamp], 'Fri 10AM 3rd of May 2024'];
		yield 'time' => ['fancyTime', [$timestamp], '10:14'];
		yield 'day name' => ['setDayName', [$timestamp], 'Friday'];
		yield 'month name' => ['setMonthName', [$timestamp], 'May'];
		yield 'day ordinal' => ['setDay', [$timestamp], '3rd'];
		yield 'numeric month' => ['setMonth', [$timestamp], '5'];
		yield 'year' => ['setYear', [$timestamp], '2024'];
		yield 'year and month' => ['setYearMonth', [$timestamp, '-'], '2024-05'];
		yield 'year, month, and day' => ['setYearMonthDay', [$timestamp, '.'], '2024.05.03'];
		yield 'day, month, and year' => ['setDayMonthYear', [$timestamp, '/'], '03/05/2024'];
	}

	/**
	 * Select the documented dynamic format for old, recent, and current-day dates.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDynamicFormatterSelectsOutputByAge(): void
	{
		$old = strtotime('-3 years');
		$recent = strtotime('-2 days');
		$today = strtotime('-1 hour');

		$this->assertSame(date('m/d/y', $old), DateHelper::fancyDynamicDate($old));
		$this->assertSame(date('M j', $recent), DateHelper::fancyDynamicDate($recent));
		$this->assertSame(date('g:i A', $today), DateHelper::fancyDynamicDate($today));
	}

	/**
	 * Normalize date strings only when timestamp checking is requested.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTimestampNormalizationHonorsTheCheckSwitch(): void
	{
		$this->assertSame(1714731296, DateHelper::getValidTimestamp(1714731296, true));
		$this->assertSame(
			strtotime('2024-05-03 09:34:56 UTC'),
			DateHelper::getValidTimestamp('2024-05-03 09:34:56 UTC', true)
		);
		$this->assertSame(123, DateHelper::getValidTimestamp('123', false));
	}

	/**
	 * Accept only positive, whole numeric Unix timestamps.
	 *
	 * @param   mixed  $value     Candidate timestamp.
	 * @param   bool   $expected  Expected validity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('timestampValidityProvider')]
	public function testTimestampValidationHasExplicitNumericBoundaries(mixed $value, bool $expected): void
	{
		$this->assertSame($expected, DateHelper::isValidTimeStamp($value));
	}

	/**
	 * Provide valid and invalid timestamp shapes.
	 *
	 * @return  iterable<string, array{mixed, bool}>
	 * @since   6.1.6
	 */
	public static function timestampValidityProvider(): iterable
	{
		yield 'positive integer' => [1714731296, true];
		yield 'positive integer string' => ['1714731296', true];
		yield 'whole float' => [1714731296.0, true];
		yield 'fractional number' => [1714731296.5, false];
		yield 'zero' => [0, false];
		yield 'negative integer' => [-1, false];
		yield 'date string' => ['2024-05-03', false];
		yield 'null' => [null, false];
	}

	/**
	 * Require exact calendar formatting, including zero padding and real dates.
	 *
	 * @param   string  $date      Candidate date.
	 * @param   string  $format    Expected format.
	 * @param   bool    $expected  Expected validity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('calendarDateProvider')]
	public function testCalendarDateValidationRequiresAnExactRoundTrip(
		string $date,
		string $format,
		bool $expected
	): void
	{
		$this->assertSame($expected, DateHelper::isValidateDate($date, $format));
	}

	/**
	 * Provide exact, malformed, and impossible calendar dates.
	 *
	 * @return  iterable<string, array{string, string, bool}>
	 * @since   6.1.6
	 */
	public static function calendarDateProvider(): iterable
	{
		yield 'default valid' => ['2024-02-29 12:30:45', 'Y-m-d H:i:s', true];
		yield 'default missing padding' => ['2024-2-29 12:30:45', 'Y-m-d H:i:s', false];
		yield 'impossible leap day' => ['2023-02-29 12:30:45', 'Y-m-d H:i:s', false];
		yield 'custom valid' => ['03/05/2024', 'd/m/Y', true];
		yield 'custom mismatch' => ['2024-05-03', 'd/m/Y', false];
	}

	/**
	 * Define the intended date-time formatter contract while the variable defect exists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testFancyDateTimeUsesTheNormalizedInputTimestamp(): void
	{
		$this->assertSame(
			'(10:14) 3rd of May 2024',
			DateHelper::fancyDateTime(1714731296)
		);
	}
}
