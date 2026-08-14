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

namespace VDM\Joomla\Tests\Interfaces;


use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use VDM\Joomla\Interfaces\Activeregistryinterface;
use VDM\Tests\Support\ActiveRegistryFixture;


/**
 * Active registry public contract test.
 *
 * @since  6.1.6
 */
#[CoversNothing]
final class ActiveregistryinterfaceTest extends TestCase
{
	/**
	 * Lock the interface surface and prove the canonical implementation conforms.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInterfaceDefinesTheActiveStorageContract(): void
	{
		$reflection = new ReflectionClass(Activeregistryinterface::class);
		$methods = array_map(
			static fn ($method): string => $method->getName(),
			$reflection->getMethods()
		);
		sort($methods, SORT_STRING);

		$this->assertSame([
			'addActive',
			'allActive',
			'existsActive',
			'getActive',
			'isActive',
			'removeActive',
			'setActive'
		], $methods);
		$this->assertInstanceOf(Activeregistryinterface::class, new ActiveRegistryFixture());
	}
}
