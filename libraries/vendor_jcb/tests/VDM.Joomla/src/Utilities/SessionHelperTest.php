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

namespace VDM\Joomla\Tests\Utilities;


use Joomla\CMS\Session\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use RuntimeException;
use VDM\Joomla\Utilities\SessionHelper;
use VDM\Tests\Support\JoomlaTestCase;
use VDM\Tests\Support\SessionApplicationFixture;


/**
 * Joomla session loading, caching, and value-forwarding tests.
 *
 * @since  6.1.6
 */
#[CoversClass(SessionHelper::class)]
final class SessionHelperTest extends JoomlaTestCase
{
	/**
	 * Original cached session.
	 *
	 * @var    Session|null
	 * @since  6.1.6
	 */
	private ?Session $originalSession = null;

	/**
	 * Clear the process-static session cache.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$property = (new ReflectionClass(SessionHelper::class))->getProperty('session');
		$this->originalSession = $property->getValue();
		$property->setValue(null, null);
	}

	/**
	 * Restore the process-static session cache.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		(new ReflectionClass(SessionHelper::class))
			->getProperty('session')
			->setValue(null, $this->originalSession);

		parent::tearDown();
	}

	/**
	 * Load the active session once and reuse its identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSessionIsLoadedOnceAndCached(): void
	{
		$session = $this->createStub(Session::class);
		$application = new SessionApplicationFixture($session);
		$this->setJoomlaFactoryProperty('application', $application);

		$this->assertSame($session, SessionHelper::session());
		$this->assertSame($session, SessionHelper::session());
		$this->assertSame(1, $application->requests);
	}

	/**
	 * Persist a returned default and forward explicit set values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetAndSetForwardToSessionStore(): void
	{
		$session = $this->createMock(Session::class);
		$session->expects($this->once())
			->method('get')
			->with('channel', 'stable')
			->willReturn('stable');
		$session->expects($this->exactly(2))
			->method('set')
			->willReturnCallback(
				function (string $name, mixed $value): ?string
				{
					$this->assertContains($name, ['channel', 'mode']);
					$this->assertContains($value, ['stable', 'strict']);

					return $name === 'mode' ? 'legacy' : null;
				}
			);
		$application = new SessionApplicationFixture($session);
		$this->setJoomlaFactoryProperty('application', $application);

		$this->assertSame('stable', SessionHelper::get('channel', 'stable'));
		$this->assertSame('legacy', SessionHelper::set('mode', 'strict'));
	}

	/**
	 * Translate application session failures into the public runtime exception.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSessionLoadFailureIsTranslated(): void
	{
		$application = new SessionApplicationFixture(new RuntimeException('unavailable'));
		$this->setJoomlaFactoryProperty('application', $application);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unable to load the session.');

		SessionHelper::session();
	}
}
