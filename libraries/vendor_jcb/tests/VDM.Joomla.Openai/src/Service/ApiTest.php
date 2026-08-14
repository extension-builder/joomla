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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Openai\Abstraction\Api as ApiAbstraction;
use VDM\Joomla\Openai\Audio;
use VDM\Joomla\Openai\Chat;
use VDM\Joomla\Openai\Completions;
use VDM\Joomla\Openai\Edits;
use VDM\Joomla\Openai\Embeddings;
use VDM\Joomla\Openai\Files;
use VDM\Joomla\Openai\FineTunes;
use VDM\Joomla\Openai\Images;
use VDM\Joomla\Openai\Models;
use VDM\Joomla\Openai\Moderate;
use VDM\Joomla\Openai\Service\Api;
use VDM\Joomla\Openai\Utilities\Http;
use VDM\Joomla\Openai\Utilities\Response;
use VDM\Joomla\Openai\Utilities\Uri;


/**
 * OpenAI API service provider test.
 *
 * @since  6.1.6
 */
#[CoversClass(Api::class)]
#[UsesClass(ApiAbstraction::class)]
#[UsesClass(Audio::class)]
#[UsesClass(Chat::class)]
#[UsesClass(Completions::class)]
#[UsesClass(Edits::class)]
#[UsesClass(Embeddings::class)]
#[UsesClass(Files::class)]
#[UsesClass(FineTunes::class)]
#[UsesClass(Images::class)]
#[UsesClass(Models::class)]
#[UsesClass(Moderate::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class ApiTest extends TestCase
{
	/**
	 * Register every endpoint alias as one shared service with common utilities.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRegisterProvidesEveryEndpointByLogicalAndClassAlias(): void
	{
		$container = new Container();
		$container->set('Openai.Utilities.Http', new Http(null), true);
		$container->set('Openai.Utilities.Uri', new Uri(), true);
		$container->set('Openai.Utilities.Response', new Response(), true);
		$container->registerServiceProvider(new Api());
		$services = [
			'Openai.Audio' => Audio::class,
			'Openai.Chat' => Chat::class,
			'Openai.Completions' => Completions::class,
			'Openai.Edits' => Edits::class,
			'Openai.Embeddings' => Embeddings::class,
			'Openai.Files' => Files::class,
			'Openai.FineTunes' => FineTunes::class,
			'Openai.Images' => Images::class,
			'Openai.Models' => Models::class,
			'Openai.Moderate' => Moderate::class
		];

		foreach ($services as $key => $class)
		{
			$service = $container->get($key);

			$this->assertInstanceOf($class, $service, $key);
			$this->assertSame($service, $container->get($key), $key . ' is shared');
			$this->assertSame($service, $container->get($class), $class . ' alias');
			$this->assertTrue($container->isShared($key), $key . ' lifecycle');
		}
	}
}
