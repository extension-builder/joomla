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


use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use VDM\Joomla\Interfaces\Activeregistryinterface;
use VDM\Joomla\Interfaces\Registryinterface;
use VDM\Tests\Support\RegistryFixture;


/**
 * Registry public contract test.
 *
 * @since  6.1.6
 */
#[CoversNothing]
final class RegistryinterfaceTest extends TestCase
{
	/**
	 * Lock the Registry extension relationship and implementation protocols.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRegistryContractExtendsActiveStorageAndPhpProtocols(): void
	{
		$reflection = new ReflectionClass(Registryinterface::class);
		$subject = new RegistryFixture();

		$this->assertTrue($reflection->implementsInterface(Activeregistryinterface::class));
		$this->assertCount(42, $reflection->getMethods());
		$this->assertInstanceOf(Registryinterface::class, $subject);
		$this->assertInstanceOf(JsonSerializable::class, $subject);
		$this->assertInstanceOf(ArrayAccess::class, $subject);
		$this->assertInstanceOf(IteratorAggregate::class, $subject);
		$this->assertInstanceOf(Countable::class, $subject);
	}
}
