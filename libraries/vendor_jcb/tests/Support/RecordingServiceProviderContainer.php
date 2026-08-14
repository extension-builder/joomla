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


/**
 * Container that records a service provider's public registration contract.
 *
 * @since  1.0.0
 */
final class RecordingServiceProviderContainer extends Container
{
	/**
	 * Alias registrations in call order.
	 *
	 * @var    array<int, array{string, string}>
	 * @since  1.0.0
	 */
	private array $aliasesRegistered = [];

	/**
	 * Shared-service registrations in call order.
	 *
	 * @var    array<int, array{string, mixed, bool}>
	 * @since  1.0.0
	 */
	private array $servicesRegistered = [];

	/**
	 * Record and apply an alias registration.
	 *
	 * @param   string  $alias  The alias name.
	 * @param   string  $key    The service key.
	 *
	 * @return  static
	 * @since   1.0.0
	 */
	public function alias($alias, $key)
	{
		$this->aliasesRegistered[] = [(string) $alias, (string) $key];

		return parent::alias($alias, $key);
	}

	/**
	 * Record and apply a shared-service registration.
	 *
	 * @param   string  $key        The service key.
	 * @param   mixed   $value      The service factory or value.
	 * @param   bool    $protected  Whether the service is protected.
	 *
	 * @return  static
	 * @since   1.0.0
	 */
	public function share($key, $value, $protected = false)
	{
		$this->servicesRegistered[] = [(string) $key, $value, (bool) $protected];

		return parent::share($key, $value, $protected);
	}

	/**
	 * Get every recorded alias registration.
	 *
	 * @return  array<int, array{string, string}>
	 * @since   1.0.0
	 */
	public function aliasesRegistered(): array
	{
		return $this->aliasesRegistered;
	}

	/**
	 * Get every recorded shared-service registration.
	 *
	 * @return  array<int, array{string, mixed, bool}>
	 * @since   1.0.0
	 */
	public function servicesRegistered(): array
	{
		return $this->servicesRegistered;
	}
}
