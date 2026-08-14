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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use VDM\Joomla\Abstraction\BaseTable;
use VDM\Tests\Support\BaseTableFixture;
use VDM\Tests\Support\TestCase;


/**
 * Table metadata lookup, defaults, title, and field-list contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(BaseTable::class)]
final class BaseTableTest extends TestCase
{
	/**
	 * Resolve catalogs, tables, fields, properties, and default-field fallbacks.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetSupportsEachMetadataDepthAndDefaultFallback(): void
	{
		$subject = new BaseTableFixture();

		$this->assertSame($subject->get(), $subject->get('ALL'));
		$this->assertSame($subject->get('power'), $subject->get()['power']);
		$this->assertSame('System Name', $subject->get('power', 'system_name', 'label'));
		$this->assertSame('powers', $subject->get('power', 'namespace', 'list'));
		$this->assertSame('Custom ID', $subject->get('power', 'id', 'label'));
		$this->assertSame('Status', $subject->get('power', 'published', 'label'));
		$this->assertSame('json', $subject->get('power', 'params', 'store'));
		$this->assertNull($subject->get('missing'));
		$this->assertNull($subject->get('power', 'missing'));
	}

	/**
	 * Resolve title metadata and use ID when no explicit title exists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTitleResolutionUsesExplicitFieldAndIdFallback(): void
	{
		$subject = new BaseTableFixture();

		$this->assertSame('system_name', $subject->titleName('power'));
		$this->assertSame('System Name', $subject->title('power')['label']);
		$this->assertNull($subject->title('repository'));
		$this->assertSame('id', $subject->titleName('repository'));
		$this->assertSame('id', $subject->titleName('missing'));
	}

	/**
	 * Distinguish dynamic, default, missing fields, and known tables.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExistRecognizesTablesAndDefaultFields(): void
	{
		$subject = new BaseTableFixture();

		$this->assertSame(['power', 'repository'], $subject->tables());
		$this->assertTrue($subject->exist('power'));
		$this->assertTrue($subject->exist('power', 'namespace'));
		$this->assertTrue($subject->exist('power', 'published'));
		$this->assertFalse($subject->exist('power', 'missing'));
		$this->assertTrue($subject->exist('missing', 'published'));
		$this->assertFalse($subject->exist('missing', 'not_default'));
	}

	/**
	 * Return field keys or full definitions with optional default metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldsSupportsKeysDetailsAndDefaults(): void
	{
		$subject = new BaseTableFixture();

		$this->assertSame(['system_name', 'namespace', 'id'], $subject->fields('power'));
		$withDefaults = $subject->fields('power', true);
		$this->assertSame(['system_name', 'namespace', 'id'], array_slice($withDefaults, 0, 3));
		$this->assertSame(1, array_count_values($withDefaults)['id']);
		$this->assertContains('published', $withDefaults);
		$this->assertContains('params', $withDefaults);
		$this->assertSame($subject->get('power'), $subject->fields('power', false, true));
		$details = $subject->fields('power', true, true);
		$this->assertSame('Custom ID', $details['id']['label']);
		$this->assertSame('Status', $details['published']['label']);
		$this->assertNull($subject->fields('missing', true, true));
	}

	/**
	 * Return false rather than raising a type error for a missing table.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testExistReturnsFalseForMissingTableWithoutField(): void
	{
		$this->assertFalse((new BaseTableFixture())->exist('missing'));
	}
}
