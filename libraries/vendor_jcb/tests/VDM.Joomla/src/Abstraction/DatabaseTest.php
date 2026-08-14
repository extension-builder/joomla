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

namespace VDM\Joomla\Tests\Abstraction;


use DateTimeImmutable;
use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Database;
use VDM\Joomla\Database\QuoteTrait;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\DatabaseFixture;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Shared database table resolution and value-quoting tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Database::class)]
#[CoversTrait(QuoteTrait::class)]
#[UsesClass(Helper::class)]
final class DatabaseTest extends JoomlaTestCase
{
	/**
	 * Original component option.
	 *
	 * @var    string|null
	 * @since  6.1.6
	 */
	private ?string $originalOption = null;

	/**
	 * Install a deterministic component option.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalOption = Helper::$option;
		Helper::setOption('com_example');
	}

	/**
	 * Restore the component option.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		Helper::setOption($this->originalOption);

		parent::tearDown();
	}

	/**
	 * Prefix logical names and preserve already-prefixed table names.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableResolutionUsesComponentCode(): void
	{
		$subject = new DatabaseFixture($this->database());

		$this->assertSame('#__example_items', $subject->tableName('items'));
		$this->assertSame('#__users', $subject->tableName('#__users'));
		$this->assertSame('archive_#__items', $subject->tableName('archive_#__items'));
	}

	/**
	 * Preserve native values and apply the reviewed SQL quoting policy.
	 *
	 * @param   mixed  $value     Source value.
	 * @param   mixed  $expected  Expected quoted value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideQuotedValues')]
	public function testQuotePolicyPreservesDataIntent(mixed $value, mixed $expected): void
	{
		$this->assertSame($expected, (new DatabaseFixture($this->database()))->quoted($value));
	}

	/**
	 * Supply null, numeric, boolean, date, and string quoting cases.
	 *
	 * @return  iterable<string, array{mixed, mixed}>
	 * @since   6.1.6
	 */
	public static function provideQuotedValues(): iterable
	{
		yield 'null' => [null, 'NULL'];
		yield 'integer' => [42, 42];
		yield 'float' => [3.5, 3.5];
		yield 'integer string' => ['42', 42];
		yield 'decimal string' => ['3.50', 3.5];
		yield 'leading zero' => ['007', "'007'"];
		yield 'scientific notation' => ['1e3', "'1e3'"];
		yield 'negative numeric string' => ['-7', "'-7'"];
		yield 'true' => [true, 'TRUE'];
		yield 'false' => [false, 'FALSE'];
		yield 'ordinary string' => ['JCB', "'JCB'"];
		yield 'date' => [new DateTimeImmutable('2026-08-14 12:34:56'), "'2026-08-14 12:34:56'"];
	}

	/**
	 * Build a database double with deterministic SQL string quoting.
	 *
	 * @return  DatabaseInterface
	 * @since   6.1.6
	 */
	private function database(): DatabaseInterface
	{
		$database = $this->createStub(DatabaseInterface::class);
		$database->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);

		return $database;
	}
}
