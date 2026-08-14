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

namespace VDM\Joomla\Tests\Import;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Import\Entity;
use VDM\Tests\Support\TestCase;


/**
 * Import entity defaults, fluent configuration, and validation tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Entity::class)]
final class EntityTest extends TestCase
{
	/**
	 * Expose the stable defaults used by transient and persistent imports.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDefaultImportConfigurationIsStable(): void
	{
		$subject = new Entity();

		$this->assertSame(2, $subject->getStartingRow());
		$this->assertSame(2, $subject->getMinimalColumns());
		$this->assertSame('', $subject->getParentTable());
		$this->assertSame('', $subject->getParentKey());
		$this->assertSame('', $subject->getParentJoinKey());
		$this->assertSame('guid', $subject->getLinkField());
		$this->assertSame('import', $subject->getDataKey());
		$this->assertSame([], $subject->getJoinFields());
	}

	/**
	 * Apply a complete custom import configuration through fluent setters.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSettersAreFluentAndPreserveExactConfiguration(): void
	{
		$joins = ['contacts' => ['link_fields' => ['email', 'company']]];
		$subject = new Entity();

		$this->assertSame($subject, $subject->setStartingRow(5));
		$this->assertSame($subject, $subject->setMinimalColumns(4));
		$this->assertSame($subject, $subject->setParentTable('people'));
		$this->assertSame($subject, $subject->setParentKey('id'));
		$this->assertSame($subject, $subject->setParentJoinKey('person_id'));
		$this->assertSame($subject, $subject->setLinkField('email'));
		$this->assertSame($subject, $subject->setDataKey('payload'));
		$this->assertSame($subject, $subject->setJoinFields($joins));

		$this->assertSame(5, $subject->getStartingRow());
		$this->assertSame(4, $subject->getMinimalColumns());
		$this->assertSame('people', $subject->getParentTable());
		$this->assertSame('id', $subject->getParentKey());
		$this->assertSame('person_id', $subject->getParentJoinKey());
		$this->assertSame('email', $subject->getLinkField());
		$this->assertSame('payload', $subject->getDataKey());
		$this->assertSame($joins, $subject->getJoinFields());
	}

	/**
	 * Reject invalid lower bounds and empty identifiers without mutating defaults.
	 *
	 * @param   string  $method  Setter to call.
	 * @param   mixed   $value   Invalid value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('invalidConfiguration')]
	public function testInvalidConfigurationIsRejected(string $method, mixed $value): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new Entity())->{$method}($value);
	}

	/**
	 * Supply every validated entity configuration boundary.
	 *
	 * @return  iterable<string, array{string, mixed}>
	 * @since   6.1.6
	 */
	public static function invalidConfiguration(): iterable
	{
		yield 'starting row zero' => ['setStartingRow', 0];
		yield 'minimal columns zero' => ['setMinimalColumns', 0];
		yield 'empty parent table' => ['setParentTable', ''];
		yield 'empty parent key' => ['setParentKey', ''];
		yield 'empty parent join key' => ['setParentJoinKey', ''];
		yield 'empty link field' => ['setLinkField', ''];
		yield 'empty data key' => ['setDataKey', ''];
	}
}
