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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler;


use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Factory;
use VDM\Tests\Support\FactoryTestCase;


/**
 * Compiler composition-root lifecycle and service-catalogue contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Factory::class)]
final class FactoryTest extends FactoryTestCase
{
	/**
	 * The factory exposes stable compiler aliases without resolving the compiler.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testServiceCatalogueAndUnsetCreateIndependentBuildScopes(): void
	{
		$this->isolateFactory(Factory::class);
		$keys = Factory::getKeys();
		$property = new ReflectionProperty(Factory::class, 'container');
		$first = $property->getValue();

		$this->assertContains('Config', $keys);
		$this->assertContains('Registry', $keys);
		$this->assertContains('Utilities.Paths', $keys);
		$this->assertContains('Compiler.Builder.Content.One', $keys);
		$this->assertContains('Compiler', $keys);

		Factory::unset();
		$this->assertNull($property->getValue());
		$this->assertSame($keys, Factory::getKeys());
		$this->assertNotSame($first, $property->getValue());
	}
}
