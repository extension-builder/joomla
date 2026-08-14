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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Gitea\Service\Utilities;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Gitea\Utilities\Uri;


/**
 * Core Gitea utility service-provider tests.
 *
 * @since  1.0.0
 */
#[CoversClass(Utilities::class)]
#[UsesClass(Response::class)]
#[UsesClass(Uri::class)]
final class UtilitiesTest extends TestCase
{
	/**
	 * Register both utility aliases as shared services.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testRegistersSharedUriAndResponseAliases(): void
	{
		$container = new Container();
		$container->registerServiceProvider(new Utilities());

		$uri = $container->get('Gitea.Utilities.Uri');
		$response = $container->get('Gitea.Utilities.Response');

		$this->assertInstanceOf(Uri::class, $uri);
		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame($uri, $container->get(Uri::class));
		$this->assertSame($response, $container->get(Response::class));
		$this->assertSame($uri, $container->get('Gitea.Utilities.Uri'));
		$this->assertSame($response, $container->get('Gitea.Utilities.Response'));
		$this->assertTrue($container->isShared('Gitea.Utilities.Uri'));
		$this->assertTrue($container->isShared('Gitea.Utilities.Response'));
	}
}
