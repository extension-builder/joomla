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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Import\Data;
use VDM\Tests\Support\TestCase;


/**
 * Import-scoped registry isolation and path behavior tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Data::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class DataTest extends TestCase
{
	/**
	 * Preserve nested import state and isolate independent import runs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRegistryStoresNestedImportStateWithoutCrossInstanceLeakage(): void
	{
		$first = new Data(['import' => ['created_by' => 42]]);
		$second = new Data();

		$this->assertSame(42, $first->get('import.created_by'));
		$this->assertTrue($first->exists('import.created_by'));
		$this->assertNull($second->get('import.created_by'));
		$this->assertSame($first, $first->set('import.batch', 'alpha'));
		$this->assertSame('alpha', $first->get('import.batch'));
	}

	/**
	 * Honor a caller-selected separator for non-dot import payloads.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorHonorsCustomPathSeparator(): void
	{
		$subject = new Data(['import' => ['created_by' => 7]], '/');

		$this->assertSame(7, $subject->get('import/created_by'));
		$this->assertNull($subject->get('import.created_by'));
	}
}
