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

namespace VDM\Joomla\Github\Tests\Service;


use Joomla\DI\Container;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;
use VDM\Joomla\Github\Service\Utilities;
use VDM\Joomla\Github\Utilities\Http;
use VDM\Joomla\Github\Utilities\Response;
use VDM\Joomla\Github\Utilities\Uri;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\TestCase;


/**
 * GitHub utility service provider tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Utilities::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
#[UsesClass(Helper::class)]
final class UtilitiesTest extends TestCase
{
	/**
	 * Component option active before the current test.
	 *
	 * @var    mixed
	 * @since  6.1.6
	 */
	private mixed $originalOption;

	/**
	 * Component parameter cache active before the current test.
	 *
	 * @var    array<mixed>
	 * @since  6.1.6
	 */
	private array $originalParams = [];

	/**
	 * Capture component-helper state before provider resolution.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalOption = Helper::$option;
		$this->originalParams = (new ReflectionProperty(Helper::class, 'params'))->getValue();
	}

	/**
	 * Register protected shared aliases and load the configured access token.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRegisterProvidesSharedUtilitiesWithConfiguredToken(): void
	{
		$this->installParams(new Registry(['github_access_token' => 'configured-token']));
		$container = (new Container())->registerServiceProvider(new Utilities());
		$http = $container->get('Github.Utilities.Http');
		$uri = $container->get('Github.Utilities.Uri');
		$response = $container->get('Github.Utilities.Response');

		$this->assertSame('configured-token', $http->getToken());
		$this->assertSame('Bearer configured-token', ((array) $http->getOption('headers'))['Authorization']);
		$this->assertSame($http, $container->get(Http::class));
		$this->assertSame($uri, $container->get(Uri::class));
		$this->assertSame($response, $container->get(Response::class));
		$this->assertTrue($container->isShared('Github.Utilities.Http'));
		$this->assertTrue($container->isShared('Github.Utilities.Uri'));
		$this->assertTrue($container->isShared('Github.Utilities.Response'));
	}

	/**
	 * Keep an absent configured credential out of default headers.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetHttpOmitsAbsentCredential(): void
	{
		$this->installParams(new Registry());
		$http = (new Utilities())->getHttp(new Container());

		$this->assertNull($http->getToken());
		$this->assertArrayNotHasKey('Authorization', (array) $http->getOption('headers'));
	}

	/**
	 * Install component parameters into the helper's static cache.
	 *
	 * @param   Registry  $params  Parameters for com_componentbuilder.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function installParams(Registry $params): void
	{
		Helper::$option = 'com_componentbuilder';
		(new ReflectionProperty(Helper::class, 'params'))->setValue(
			null,
			['com_componentbuilder' => $params]
		);
	}

	/**
	 * Restore helper state after every test.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		Helper::$option = $this->originalOption;
		(new ReflectionProperty(Helper::class, 'params'))
			->setValue(null, $this->originalParams);
		$this->originalParams = [];

		parent::tearDown();
	}
}
