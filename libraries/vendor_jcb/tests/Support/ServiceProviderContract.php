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


use InvalidArgumentException;
use Joomla\DI\ServiceProviderInterface;


/**
 * Canonical representation of a service provider's container catalog.
 *
 * @since  1.0.0
 */
final class ServiceProviderContract
{
	/**
	 * Capture a provider's aliases, service keys, factories, and flags.
	 *
	 * @param   ServiceProviderInterface  $provider  The provider under test.
	 *
	 * @return  array{aliases: int, services: int, snapshot: string, hash: string}
	 * @since   1.0.0
	 */
	public static function capture(ServiceProviderInterface $provider): array
	{
		$container = new RecordingServiceProviderContainer();
		$provider->register($container);
		$lines = [];
		$aliases = $container->aliasesRegistered();
		$services = $container->servicesRegistered();

		foreach ($aliases as [$alias, $key])
		{
			$lines[] = 'alias|' . $alias . '|' . $key;
		}

		foreach ($services as [$key, $factory, $protected])
		{
			$lines[] = implode('|', [
				'share',
				$key,
				self::factoryName($provider, $factory),
				$protected ? 'protected' : 'mutable'
			]);
		}

		sort($lines, SORT_STRING);
		$snapshot = implode("\n", $lines);

		return [
			'aliases' => count($aliases),
			'services' => count($services),
			'snapshot' => $snapshot,
			'hash' => hash('sha256', $snapshot)
		];
	}

	/**
	 * Describe a provider factory without invoking it.
	 *
	 * @param   ServiceProviderInterface  $provider  The provider under test.
	 * @param   mixed                     $factory   The registered service factory.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private static function factoryName(ServiceProviderInterface $provider, mixed $factory): string
	{
		if (is_array($factory)
			&& count($factory) === 2
			&& $factory[0] === $provider
			&& is_string($factory[1]))
		{
			return get_class($provider) . '::' . $factory[1];
		}

		throw new InvalidArgumentException(
			'Provider registered a factory that is not one of its instance methods: '
			. get_debug_type($factory)
		);
	}
}
