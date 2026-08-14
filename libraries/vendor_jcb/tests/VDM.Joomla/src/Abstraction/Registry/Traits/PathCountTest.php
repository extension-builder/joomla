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

namespace VDM\Joomla\Tests\Abstraction\Registry\Traits;


use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Abstraction\Registry\Traits\PathCount;
use VDM\Tests\Support\RegistryTraitsFixture;


/**
 * Registry path-cardinality trait tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(PathCount::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class PathCountTest extends TestCase
{
	/**
	 * Count arrays and object properties while treating other values as one.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPathCountHandlesMissingCollectionsObjectsAndScalars(): void
	{
		$subject = new RegistryTraitsFixture([
			'array' => ['first', 'second'],
			'emptyArray' => [],
			'object' => (object) ['first' => 1, 'second' => 2, 'third' => 3],
			'string' => 'value',
			'zero' => 0,
			'null' => null
		]);

		$this->assertSame(2, $subject->pathCount('array'));
		$this->assertSame(0, $subject->pathCount('emptyArray'));
		$this->assertSame(3, $subject->pathCount('object'));
		$this->assertSame(1, $subject->pathCount('string'));
		$this->assertSame(1, $subject->pathCount('zero'));
		$this->assertSame(0, $subject->pathCount('null'));
		$this->assertSame(0, $subject->pathCount('missing'));
	}
}
