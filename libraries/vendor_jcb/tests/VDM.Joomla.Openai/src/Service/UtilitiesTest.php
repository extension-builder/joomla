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

namespace VDM\Joomla\Openai\Tests\Service;


use Joomla\DI\Container;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;
use VDM\Joomla\Openai\Service\Utilities;
use VDM\Joomla\Openai\Utilities\Http;
use VDM\Joomla\Openai\Utilities\Response;
use VDM\Joomla\Openai\Utilities\Uri;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\TestCase;


/**
 * OpenAI utility service provider test.
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
	 * Register utility aliases as shared services and load configured credentials.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRegisterProvidesSharedUtilitiesWithConfiguredTokens(): void
	{
		$this->installParams(new Registry([
			'enable_open_ai' => 1,
			'openai_token' => 'user-token',
			'enable_open_ai_org' => 1,
			'openai_org_token' => 'organization-token'
		]));
		$container = (new Container())->registerServiceProvider(new Utilities());

		$http = $container->get('Openai.Utilities.Http');
		$uri = $container->get('Openai.Utilities.Uri');
		$response = $container->get('Openai.Utilities.Response');
		$headers = (array) $http->getOption('headers');

		$this->assertSame('Bearer user-token', $headers['Authorization']);
		$this->assertSame('organization-token', $headers['OpenAI-Organization']);
		$this->assertSame($http, $container->get(Http::class));
		$this->assertSame($uri, $container->get(Uri::class));
		$this->assertSame($response, $container->get(Response::class));
		$this->assertTrue($container->isShared('Openai.Utilities.Http'));
		$this->assertTrue($container->isShared('Openai.Utilities.Uri'));
		$this->assertTrue($container->isShared('Openai.Utilities.Response'));
	}

	/**
	 * Omit disabled and masked credentials from constructed HTTP clients.
	 *
	 * @param   array<string, mixed>  $params  Component parameters.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('hiddenTokenProvider')]
	public function testGetHttpOmitsDisabledOrMaskedCredentials(array $params): void
	{
		$this->installParams(new Registry($params));
		$http = (new Utilities())->getHttp(new Container());

		$this->assertSame(
			['Content-Type' => 'application/json'],
			(array) $http->getOption('headers')
		);
	}

	/**
	 * Provide disabled and masked credential configurations.
	 *
	 * @return  iterable<string, array{array<string, mixed>}>
	 * @since   6.1.6
	 */
	public static function hiddenTokenProvider(): iterable
	{
		yield 'disabled' => [[
			'enable_open_ai' => 0,
			'openai_token' => 'must-not-leak',
			'enable_open_ai_org' => 1,
			'openai_org_token' => 'must-not-leak'
		]];
		yield 'masked secrets' => [[
			'enable_open_ai' => 1,
			'openai_token' => 'secret',
			'enable_open_ai_org' => 1,
			'openai_org_token' => 'secret'
		]];
	}

	/**
	 * Install component parameters into the helper's documented static cache.
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
