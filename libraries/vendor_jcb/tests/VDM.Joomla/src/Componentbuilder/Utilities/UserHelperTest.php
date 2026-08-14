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

namespace VDM\Joomla\Tests\Componentbuilder\Utilities;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Utilities\Exception\NoUserIdFoundException;
use VDM\Joomla\Componentbuilder\Utilities\UserHelper;
use VDM\Tests\Support\TestCase;
use VDM\Tests\Support\UserHelperFixture;


/**
 * User create/update routing and registration-data contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(UserHelper::class)]
#[UsesClass(NoUserIdFoundException::class)]
final class UserHelperTest extends TestCase
{
	/**
	 * Start each test with deterministic lookup and routing state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();
		UserHelperFixture::reset();
	}

	/**
	 * Reject save requests that cannot establish an email identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSaveRejectsCredentialsWithoutEmail(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('COM_COMPONENTBUILDER_CAN_NOT_SAVE_USER_WITHOUT_EMAIL_VALUE');

		UserHelperFixture::save(['name' => 'Missing Email']);
	}

	/**
	 * Route new credentials to creation with every caller option intact.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSaveRoutesNewCredentialsToCreate(): void
	{
		$credentials = ['name' => 'Ada', 'email' => 'ada@example.test'];
		$params = ['useractivation' => 2, 'sendpassword' => 0];

		$result = UserHelperFixture::save($credentials, 1, $params, 2);

		$this->assertSame(71, $result);
		$this->assertSame($credentials, UserHelperFixture::$created);
		$this->assertNull(UserHelperFixture::$updated);
		$this->assertSame(
			['autologin' => 1, 'params' => $params, 'mode' => 2],
			UserHelperFixture::$createOptions
		);
	}

	/**
	 * Route an identity-consistent existing user to update.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSaveRoutesMatchingExistingIdentityToUpdate(): void
	{
		UserHelperFixture::$emailUserId = 17;
		UserHelperFixture::$usernameUserId = 17;
		$credentials = [
			'id' => 17,
			'name' => 'Ada',
			'username' => 'ada',
			'email' => 'ada@example.test'
		];

		$this->assertSame(72, UserHelperFixture::save($credentials));
		$this->assertSame($credentials, UserHelperFixture::$updated);
		$this->assertNull(UserHelperFixture::$created);
	}

	/**
	 * Refuse an update that would reuse another account's email identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSaveRejectsCrossAccountIdentityMismatch(): void
	{
		UserHelperFixture::$emailUserId = 99;
		UserHelperFixture::$usernameUserId = 17;

		$this->expectException(NoUserIdFoundException::class);
		$this->expectExceptionMessage('User ID mismatch detected');

		UserHelperFixture::save([
			'id' => 17,
			'name' => 'Ada',
			'username' => 'ada',
			'email' => 'ada@example.test'
		]);
	}

	/**
	 * Prepare site registration fields without leaking admin-only group data.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPrepareUserDataMapsSiteRegistrationContract(): void
	{
		$this->assertSame(
			[
				'username' => 'ada',
				'name' => 'Ada Lovelace',
				'block' => 0,
				'email1' => 'ada@example.test',
				'password1' => 'secret-one',
				'password2' => 'secret-one'
			],
			UserHelperFixture::prepare([
				'username' => 'ada',
				'name' => 'Ada Lovelace',
				'email' => 'ada@example.test',
				'password' => 'secret-one',
				'password2' => 'secret-one',
				'groups' => [8]
			], 1)
		);
	}
}
