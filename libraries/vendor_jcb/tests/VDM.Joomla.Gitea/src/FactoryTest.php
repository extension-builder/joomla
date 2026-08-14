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

namespace VDM\Joomla\Gitea\Tests;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Factory as ExtendingFactory;
use VDM\Joomla\Gitea\Factory;
use VDM\Joomla\Gitea\Service\Admin;
use VDM\Joomla\Gitea\Service\Issue;
use VDM\Joomla\Gitea\Service\Jcb;
use VDM\Joomla\Gitea\Service\Miscellaneous;
use VDM\Joomla\Gitea\Service\Notifications;
use VDM\Joomla\Gitea\Service\Organization;
use VDM\Joomla\Gitea\Service\Package;
use VDM\Joomla\Gitea\Service\Repository;
use VDM\Joomla\Gitea\Service\Settings;
use VDM\Joomla\Gitea\Service\User;
use VDM\Joomla\Gitea\Service\Utilities;
use VDM\Joomla\Gitea\Utilities\Response;
use VDM\Joomla\Gitea\Utilities\Uri;
use VDM\Tests\Support\FactoryTestCase;


/**
 * Top-level Gitea factory registration and singleton-container tests.
 *
 * @since  1.0.0
 */
#[CoversClass(Factory::class)]
#[UsesClass(ExtendingFactory::class)]
#[UsesClass(Admin::class)]
#[UsesClass(Issue::class)]
#[UsesClass(Jcb::class)]
#[UsesClass(Miscellaneous::class)]
#[UsesClass(Notifications::class)]
#[UsesClass(Organization::class)]
#[UsesClass(Package::class)]
#[UsesClass(Repository::class)]
#[UsesClass(Response::class)]
#[UsesClass(Settings::class)]
#[UsesClass(User::class)]
#[UsesClass(Utilities::class)]
#[UsesClass(Uri::class)]
final class FactoryTest extends FactoryTestCase
{
	/**
	 * Register every provider in one shared factory container.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testFactoryBuildsOneContainerWithEveryServiceLayer(): void
	{
		$this->isolateFactory(Factory::class);

		$container = Factory::getContainer();
		$keys = [
			'Gitea.Utilities.Uri',
			'Gitea.Dynamic.Uri',
			'Gitea.Utilities.Http',
			'Gitea.Settings.Api',
			'Gitea.Organization',
			'Gitea.User',
			'Gitea.Repository',
			'Gitea.Package',
			'Gitea.Issue',
			'Gitea.Notifications',
			'Gitea.Miscellaneous.Version',
			'Gitea.Admin.Cron'
		];

		$this->assertSame($container, Factory::getContainer());

		foreach ($keys as $key)
		{
			$this->assertTrue($container->has($key), 'Factory did not register ' . $key);
			$this->assertTrue($container->isShared($key), 'Factory service is not shared: ' . $key);
		}

		$this->assertInstanceOf(Uri::class, Factory::_('Gitea.Utilities.Uri'));
		$this->assertInstanceOf(Response::class, Factory::_('Gitea.Utilities.Response'));
	}
}
