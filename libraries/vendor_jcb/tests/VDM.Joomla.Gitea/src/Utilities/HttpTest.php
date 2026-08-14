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

namespace VDM\Joomla\Gitea\Tests\Utilities;


use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Gitea\Utilities\Http;
use VDM\Joomla\Gitea\Utilities\Http\Transport\UnsafeCurl;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\TestCase;


/**
 * Gitea HTTP option and authentication tests.
 *
 * A separate process preserves UnsafeCurl's initially uninitialized security
 * switch, which PHP cannot restore after the first assignment.
 *
 * @since  1.0.0
 */
#[CoversClass(Http::class)]
#[UsesClass(UnsafeCurl::class)]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class HttpTest extends TestCase
{
	/**
	 * Original component parameter cache.
	 *
	 * @var    array<string, Registry>
	 * @since  1.0.0
	 */
	private array $componentParams = [];

	/**
	 * Install deterministic component parameters before transport selection.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$helper = new ReflectionClass(Helper::class);
		$params = $helper->getProperty('params');
		$this->componentParams = $params->getValue();
		$params->setValue(null, [
			'com_componentbuilder' => new Registry(['allow_selfsigned_certificates' => false])
		]);

		(new ReflectionClass(UnsafeCurl::class))->getProperty('allowSelfSigned')->setValue(null, false);
	}

	/**
	 * Restore the shared component parameter cache.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		(new ReflectionClass(Helper::class))->getProperty('params')->setValue(null, $this->componentParams);

		parent::tearDown();
	}

	/**
	 * Configure the documented user agent, JSON content type, and token header.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testConstructorConfiguresAuthenticatedJsonRequests(): void
	{
		$subject = new Http('secret-token');

		$this->assertSame('JoomlaGitea/3.0', $subject->getOption('userAgent'));
		$this->assertSame(
			[
				'Content-Type' => 'application/json',
				'Authorization' => 'token secret-token'
			],
			(array) $subject->getOption('headers')
		);
		$this->assertSame('secret-token', $subject->getToken());
	}

	/**
	 * Keep anonymous clients free of an authorization header.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testConstructorWithoutTokenCreatesAnonymousClient(): void
	{
		$subject = new Http();

		$this->assertSame(['Content-Type' => 'application/json'], (array) $subject->getOption('headers'));
		$this->assertNull($subject->getToken());
	}

	/**
	 * Replace and remove the authorization header without disturbing other options.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testSetTokenReplacesAndRemovesAuthorizationHeader(): void
	{
		$subject = new Http('initial-token');

		$subject->setToken('replacement-token');

		$this->assertSame('replacement-token', $subject->getToken());
		$this->assertSame(
			'token replacement-token',
			((array) $subject->getOption('headers'))['Authorization']
		);

		$subject->setToken('');
		$headers = (array) $subject->getOption('headers');

		$this->assertArrayNotHasKey('Authorization', $headers);
		$this->assertSame('application/json', $headers['Content-Type']);
	}
}
