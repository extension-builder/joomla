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

namespace VDM\Joomla\Github\Tests;


use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;
use VDM\Joomla\Abstraction\Factory as BaseFactory;
use VDM\Joomla\Componentbuilder\Power\Service\Github as RepositoryProvider;
use VDM\Joomla\Github\Factory;
use VDM\Joomla\Github\Repository\Contents;
use VDM\Joomla\Github\Repository\Tags;
use VDM\Joomla\Github\Repository\Wiki;
use VDM\Joomla\Github\Service\Utilities;
use VDM\Joomla\Github\Utilities\Http;
use VDM\Joomla\Github\Utilities\Response;
use VDM\Joomla\Github\Utilities\Uri;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\FactoryTestCase;


/**
 * GitHub factory composition tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Factory::class)]
#[UsesClass(BaseFactory::class)]
#[UsesClass(RepositoryProvider::class)]
#[UsesClass(Utilities::class)]
#[UsesClass(Contents::class)]
#[UsesClass(Tags::class)]
#[UsesClass(Wiki::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class FactoryTest extends FactoryTestCase
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
	 * Capture component-helper state before factory composition.
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
	 * Compose one container and preserve shared identity through all aliases.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFactoryResolvesSharedUtilitiesAndRepositories(): void
	{
		$this->isolateFactory(Factory::class);
		$this->installParams(new Registry(['github_access_token' => 'factory-token']));
		$container = Factory::getContainer();

		$contents = Factory::_('Github.Repository.Contents');
		$tags = Factory::_('Github.Repository.Tags');
		$wiki = Factory::_('Github.Repository.Wiki');
		$http = Factory::_('Github.Utilities.Http');

		$this->assertSame($container, Factory::getContainer());
		$this->assertInstanceOf(Contents::class, $contents);
		$this->assertInstanceOf(Tags::class, $tags);
		$this->assertInstanceOf(Wiki::class, $wiki);
		$this->assertSame($contents, Factory::_(Contents::class));
		$this->assertSame($tags, Factory::_(Tags::class));
		$this->assertSame($wiki, Factory::_(Wiki::class));
		$this->assertSame($http, Factory::_(Http::class));
		$this->assertSame('factory-token', $http->getToken());
		$this->assertSame(Factory::_('Github.Utilities.Uri'), Factory::_(Uri::class));
		$this->assertSame(Factory::_('Github.Utilities.Response'), Factory::_(Response::class));
	}

	/**
	 * Install deterministic component parameters.
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
	 * Restore helper and factory state after every test.
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
