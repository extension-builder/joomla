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


use Joomla\DI\ServiceProviderInterface;
use ReflectionMethod;


/**
 * Shared assertions for first-party Joomla service providers.
 *
 * @since  1.0.0
 */
abstract class ServiceProviderTestCase extends TestCase
{
	/**
	 * Assert the provider's exact catalog and its container invariants.
	 *
	 * @param   class-string<ServiceProviderInterface>                    $providerClass  Provider under test.
	 * @param   array{aliases: int, services: int, hash: string}           $expected       Reviewed catalog fingerprint.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function assertServiceProviderContract(
		string $providerClass,
		array $expected
	): void
	{
		$provider = new $providerClass();
		$contract = ServiceProviderContract::capture($provider);
		$message = 'The registered catalog changed for ' . $providerClass
			. ". Review every alias, key, factory, and protection flag:\n"
			. $contract['snapshot'];

		$this->assertSame($expected['aliases'], $contract['aliases'], $message);
		$this->assertSame($expected['services'], $contract['services'], $message);
		$this->assertSame($expected['hash'], $contract['hash'], $message);

		$container = new RecordingServiceProviderContainer();
		$provider->register($container);
		$serviceKeys = [];

		foreach ($container->servicesRegistered() as [$key, $factory, $protected])
		{
			$this->assertArrayNotHasKey($key, $serviceKeys, 'Duplicate service key: ' . $key);
			$serviceKeys[$key] = true;
			$this->assertTrue($container->has($key), 'Missing registered service: ' . $key);
			$this->assertTrue($container->isShared($key), 'Service must be shared: ' . $key);
			$this->assertSame(
				$protected,
				$container->isProtected($key),
				'Container protection flag differs from the registration: ' . $key
			);
			$this->assertIsArray($factory, 'Service factory must be an instance method: ' . $key);
			$this->assertCount(2, $factory, 'Malformed service factory: ' . $key);
			$this->assertSame($provider, $factory[0], 'Factory must use the provider instance: ' . $key);
			$this->assertIsString($factory[1], 'Factory method must be named: ' . $key);
			$this->assertTrue(method_exists($provider, $factory[1]), 'Missing factory method: ' . $factory[1]);
			$this->assertTrue(
				(new ReflectionMethod($provider, $factory[1]))->isPublic(),
				'Factory method must be public: ' . $factory[1]
			);
		}

		foreach ($container->aliasesRegistered() as [$alias, $key])
		{
			$this->assertNotSame('', $alias, 'Alias cannot be empty.');

			if (str_contains($alias, '\\'))
			{
				$this->assertTrue(
					class_exists($alias) || interface_exists($alias),
					'Namespaced alias does not name a declared class or interface: ' . $alias
				);
			}
			$this->assertArrayHasKey($key, $serviceKeys, 'Alias targets an unregistered key: ' . $key);
			$this->assertTrue($container->has($alias), 'Container cannot resolve alias metadata: ' . $alias);
			$this->assertSame(
				$container->isShared($key),
				$container->isShared($alias),
				'Alias lifecycle differs from its service: ' . $alias
			);
		}
	}
}
