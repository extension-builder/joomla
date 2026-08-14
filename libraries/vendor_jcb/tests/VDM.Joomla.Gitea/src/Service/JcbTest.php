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

namespace VDM\Joomla\Gitea\Tests\Service;


use Joomla\DI\Container;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Gitea\Service\Jcb;
use VDM\Joomla\Gitea\Utilities\Http;
use VDM\Joomla\Gitea\Utilities\Http\Transport\UnsafeCurl;
use VDM\Joomla\Gitea\Utilities\Uri;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\TestCase;


/**
 * JCB-configured Gitea URL and token service tests.
 *
 * A separate process preserves UnsafeCurl's initially uninitialized security
 * switch, which PHP cannot restore after the first assignment.
 *
 * @since  1.0.0
 */
#[CoversClass(Jcb::class)]
#[UsesClass(Http::class)]
#[UsesClass(UnsafeCurl::class)]
#[UsesClass(Uri::class)]
#[UsesClass(Helper::class)]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class JcbTest extends TestCase
{
	/**
	 * Original component parameter cache.
	 *
	 * @var    array<string, Registry>
	 * @since  1.0.0
	 */
	private array $componentParams = [];

	/**
	 * Preserve shared component configuration and disable unsafe transport selection.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$params = (new ReflectionClass(Helper::class))->getProperty('params');
		$this->componentParams = $params->getValue();
		(new ReflectionClass(UnsafeCurl::class))->getProperty('allowSelfSigned')->setValue(null, false);
	}

	/**
	 * Restore shared component configuration.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		(new ReflectionClass(Helper::class))->getProperty('params')->setValue(null, $this->componentParams);
		(new ReflectionClass(UnsafeCurl::class))->getProperty('allowSelfSigned')->setValue(null, false);

		parent::tearDown();
	}

	/**
	 * Use the configured custom URL and custom token when the switch is enabled.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testCustomConfigurationBuildsSharedDynamicServices(): void
	{
		$this->setParams([
			'add_custom_gitea_url' => 2,
			'custom_gitea_url' => 'https://custom.gitea.example',
			'custom_gitea_token' => 'custom-token',
			'allow_selfsigned_certificates' => false
		]);
		$container = $this->container();

		$uri = $container->get('Gitea.Dynamic.Uri');
		$http = $container->get('Gitea.Utilities.Http');

		$this->assertSame('https://custom.gitea.example/api/v1', $uri->api());
		$this->assertSame('custom-token', $http->getToken());
		$this->assertSame($uri, $container->get(Uri::class));
		$this->assertSame($http, $container->get(Http::class));
		$this->assertSame($uri, $container->get('Gitea.Dynamic.Uri'));
		$this->assertSame($http, $container->get('Gitea.Utilities.Http'));
	}

	/**
	 * Reuse the default utility URI and select the standard token otherwise.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testDefaultConfigurationReusesUtilityUriAndStandardToken(): void
	{
		$this->setParams([
			'add_custom_gitea_url' => 1,
			'gitea_token' => 'standard-token',
			'allow_selfsigned_certificates' => false
		]);
		$defaultUri = new Uri('https://default.gitea.example');
		$container = $this->container($defaultUri);

		$this->assertSame($defaultUri, $container->get('Gitea.Dynamic.Uri'));
		$this->assertSame('standard-token', $container->get('Gitea.Utilities.Http')->getToken());
	}

	/**
	 * Create a container with the JCB provider registered lazily.
	 *
	 * @return  Container
	 * @since   1.0.0
	 */
	private function container(?Uri $uri = null): Container
	{
		$container = new Container();
		$container->share('Gitea.Utilities.Uri', $uri ?? new Uri(), true);
		$container->registerServiceProvider(new Jcb());

		$this->assertTrue($container->isShared('Gitea.Dynamic.Uri'));
		$this->assertTrue($container->isShared('Gitea.Utilities.Http'));

		return $container;
	}

	/**
	 * Replace the component parameter cache without querying Joomla state.
	 *
	 * @param   array<string, mixed>  $values  Component parameter values.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function setParams(array $values): void
	{
		(new ReflectionClass(Helper::class))->getProperty('params')->setValue(null, [
			'com_componentbuilder' => new Registry($values)
		]);
	}
}
