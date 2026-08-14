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
 * Controllable entity factory used by power data-item contract tests.
 *
 * @since  1.0.0
 */
final class PowerItemFactoryFixture extends Factory
{
	/**
	 * Fixture container.
	 *
	 * @var    Container|null
	 * @since  1.0.0
	 */
	protected static ?Container $container = null;

	/**
	 * Replace the fixture container for one test scenario.
	 *
	 * @param   Container|null  $container  Container to expose.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public static function seed(?Container $container): void
	{
		static::$container = $container;
	}

	/**
	 * Create an empty fixture container when none was seeded.
	 *
	 * @return  Container
	 * @since   1.0.0
	 */
	protected static function createContainer(): Container
	{
		return new Container();
	}
}
