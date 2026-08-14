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

namespace VDM\Tests\Support;


use Joomla\DI\Container;
use VDM\Joomla\Abstraction\Factory;


/**
 * Concrete factory fixture with observable container creation.
 *
 * @since  1.0.0
 */
final class FactoryFixture extends Factory
{
	/**
	 * Shared factory container.
	 *
	 * @var    Container|null
	 * @since  1.0.0
	 */
	protected static ?Container $container = null;

	/**
	 * Number of containers created during the current isolated test.
	 *
	 * @var    int
	 * @since  1.0.0
	 */
	public static int $creationCount = 0;

	/**
	 * Build a deterministic service container.
	 *
	 * @return  Container
	 * @since   1.0.0
	 */
	protected static function createContainer(): Container
	{
		++self::$creationCount;
		$container = new Container();
		$container->set('fixture.value', (object) ['id' => self::$creationCount], true);

		return $container;
	}
}
