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
use VDM\Joomla\Abstraction\Factory;
use VDM\Tests\Support\FactoryFixture;
use VDM\Tests\Support\FactoryTestCase;


/**
 * Shared factory container lifecycle and service-resolution tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Factory::class)]
final class FactoryTest extends FactoryTestCase
{
	/**
	 * Reset the concrete fixture factory around every test.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->isolateFactory(FactoryFixture::class);
		FactoryFixture::$creationCount = 0;
	}

	/**
	 * Create one process-static container and return the same identity thereafter.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetContainerCreatesOnceAndReusesIdentity(): void
	{
		$first = FactoryFixture::getContainer();
		$second = FactoryFixture::getContainer();

		$this->assertSame($first, $second);
		$this->assertSame(1, FactoryFixture::$creationCount);
	}

	/**
	 * Resolve services through the shorthand accessor without changing shared identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAccessorResolvesSharedContainerService(): void
	{
		$first = FactoryFixture::_('fixture.value');
		$second = FactoryFixture::_('fixture.value');

		$this->assertSame($first, $second);
		$this->assertSame(1, $first->id);
		$this->assertSame(1, FactoryFixture::$creationCount);
	}
}
