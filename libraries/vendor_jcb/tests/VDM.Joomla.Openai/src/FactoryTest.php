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

namespace VDM\Joomla\Openai\Tests;


use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;
use VDM\Joomla\Abstraction\Factory as BaseFactory;
use VDM\Joomla\Openai\Abstraction\Api as ApiAbstraction;
use VDM\Joomla\Openai\Chat;
use VDM\Joomla\Openai\Factory;
use VDM\Joomla\Openai\Service\Api;
use VDM\Joomla\Openai\Service\Utilities;
use VDM\Joomla\Openai\Utilities\Http;
use VDM\Joomla\Openai\Utilities\Response;
use VDM\Joomla\Openai\Utilities\Uri;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\FactoryTestCase;


/**
 * OpenAI factory composition test.
 *
 * @since  6.1.6
 */
#[CoversClass(Factory::class)]
#[UsesClass(BaseFactory::class)]
#[UsesClass(ApiAbstraction::class)]
#[UsesClass(Api::class)]
#[UsesClass(Utilities::class)]
#[UsesClass(Chat::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
#[UsesClass(Helper::class)]
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
	 * Compose one process-local container and resolve shared services through aliases.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFactoryBuildsContainerAndResolvesSharedEndpoint(): void
	{
		$this->isolateFactory(Factory::class);
		$this->installDisabledCredentials();
		$container = Factory::getContainer();

		$chat = Factory::_('Openai.Chat');

		$this->assertSame($container, Factory::getContainer());
		$this->assertInstanceOf(Chat::class, $chat);
		$this->assertSame($chat, Factory::_('Openai.Chat'));
		$this->assertSame($chat, Factory::_(Chat::class));
		$this->assertSame(
			Factory::_('Openai.Utilities.Uri'),
			Factory::_(Uri::class)
		);
	}

	/**
	 * Install deterministic component parameters before the protected providers resolve.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function installDisabledCredentials(): void
	{
		Helper::$option = 'com_componentbuilder';
		(new ReflectionProperty(Helper::class, 'params'))->setValue(
			null,
			['com_componentbuilder' => new Registry(['enable_open_ai' => 0])]
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
