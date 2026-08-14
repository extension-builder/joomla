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

namespace VDM\Joomla\Tests\Componentbuilder\User;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\DI\Container;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use RuntimeException;
use VDM\Joomla\Componentbuilder\User\IdentityTrait;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\IdentityFixture;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Execution-identity trust-boundary tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(IdentityTrait::class)]
#[UsesClass(Helper::class)]
final class IdentityTraitTest extends JoomlaTestCase
{
	/**
	 * Original component-parameter cache.
	 *
	 * @var    array<string, Registry>
	 * @since  6.1.6
	 */
	private array $componentParams = [];

	/**
	 * Isolate the component-parameter cache used by CLI identity lookup.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$property = (new ReflectionClass(Helper::class))->getProperty('params');
		$this->componentParams = $property->getValue();
		$property->setValue(null, []);
	}

	/**
	 * Restore the shared component-parameter cache.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		(new ReflectionClass(Helper::class))
			->getProperty('params')
			->setValue(null, $this->componentParams);
		$this->componentParams = [];

		parent::tearDown();
	}

	/**
	 * Prefer a valid identity supplied by the active application.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolveReturnsApplicationIdentityWithoutElevation(): void
	{
		$user = $this->createStub(User::class);
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('getIdentity')->willReturn($user);
		$app->expects($this->never())->method('isClient');
		$this->setJoomlaApplication($app);

		$this->assertSame($user, (new IdentityFixture())->resolve());
	}

	/**
	 * Refuse silent privilege escalation when a web identity is unavailable.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolveRejectsMissingWebIdentityAfterApplicationFailure(): void
	{
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('getIdentity')->willThrowException(new RuntimeException('identity unavailable'));
		$app->expects($this->once())->method('isClient')->with('cli')->willReturn(false);
		$this->setJoomlaApplication($app);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No authenticated user available in web execution context');

		(new IdentityFixture())->resolve();
	}

	/**
	 * Resolve the explicitly configured CLI user through Joomla's user factory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolveLoadsConfiguredCliIdentityFromContainer(): void
	{
		$user = $this->createStub(User::class);
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())->method('getIdentity')->willReturn(null);
		$app->expects($this->once())->method('isClient')->with('cli')->willReturn(true);
		$this->setJoomlaApplication($app);

		$factory = $this->createMock(UserFactoryInterface::class);
		$factory->expects($this->once())->method('loadUserById')->with(42)->willReturn($user);
		$container = new Container();
		$container->share(UserFactoryInterface::class, $factory, true);
		$this->setJoomlaContainer($container);
		(new ReflectionClass(Helper::class))->getProperty('params')->setValue(
			null,
			['com_componentbuilder' => new Registry(['cli_user' => 42])]
		);

		$this->assertSame($user, (new IdentityFixture())->resolve());
	}

	/**
	 * Propagate a configured CLI user-factory failure without changing context.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolvePropagatesConfiguredCliUserFactoryFailure(): void
	{
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->method('getIdentity')->willReturn(null);
		$app->method('isClient')->with('cli')->willReturn(true);
		$this->setJoomlaApplication($app);
		$factory = $this->createMock(UserFactoryInterface::class);
		$factory->expects($this->once())
			->method('loadUserById')
			->with(9)
			->willThrowException(new RuntimeException('configured user storage unavailable'));
		$container = new Container();
		$container->share(UserFactoryInterface::class, $factory, true);
		$this->setJoomlaContainer($container);
		(new ReflectionClass(Helper::class))->getProperty('params')->setValue(
			null,
			['com_componentbuilder' => new Registry(['cli_user' => 9])]
		);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('configured user storage unavailable');

		(new IdentityFixture())->resolve();
	}
}
