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

namespace VDM\Joomla\Gitea\Tests\Support;


use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use ReflectionClass;
use VDM\Joomla\Gitea\Abstraction\Api;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Gitea\Utilities\Uri;


/**
 * Shared assertions for Gitea endpoint service providers.
 *
 * @since  1.0.0
 */
abstract class ServiceProviderTestCase extends ApiTestCase
{
	/**
	 * Assert aliases, shared lifecycle, concrete type, and constructor wiring.
	 *
	 * @param   ServiceProviderInterface      $provider  Provider under test.
	 * @param   array<class-string, string>    $services  Expected class-to-key mappings.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function assertEndpointProvider(
		ServiceProviderInterface $provider,
		array $services
	): void
	{
		$container = new Container();
		$http = $this->http(new RecordingTransport());
		$uri = new Uri('https://provider.example');
		$response = new Response();

		$container->share('Gitea.Utilities.Http', $http, true);
		$container->share('Gitea.Dynamic.Uri', $uri, true);
		$container->share('Gitea.Utilities.Response', $response, true);
		$provider->register($container);

		$apiReflection = new ReflectionClass(Api::class);

		foreach ($services as $class => $key)
		{
			$this->assertTrue($container->has($key), 'Missing service key: ' . $key);
			$this->assertTrue($container->has($class), 'Missing class alias: ' . $class);
			$this->assertTrue($container->isShared($key), 'Service must be shared: ' . $key);

			$service = $container->get($key);

			$this->assertInstanceOf($class, $service);
			$this->assertSame($service, $container->get($key));
			$this->assertSame($service, $container->get($class));
			$this->assertSame($http, $apiReflection->getProperty('http')->getValue($service));
			$this->assertSame($uri, $apiReflection->getProperty('uri')->getValue($service));
			$this->assertSame($response, $apiReflection->getProperty('response')->getValue($service));
		}
	}
}
