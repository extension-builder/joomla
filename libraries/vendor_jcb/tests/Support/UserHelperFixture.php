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

namespace VDM\Tests\Support;


use VDM\Joomla\Componentbuilder\Utilities\UserHelper;


/**
 * Deterministic user-helper fixture exposing save routing and data preparation.
 *
 * @since  6.1.6
 */
final class UserHelperFixture extends UserHelper
{
	/** @var int|null User found by email. @since 6.1.6 */
	public static ?int $emailUserId = null;

	/** @var int|null User found by username. @since 6.1.6 */
	public static ?int $usernameUserId = null;

	/** @var array<string, mixed>|null Last create credentials. @since 6.1.6 */
	public static ?array $created = null;

	/** @var array<string, mixed>|null Last update credentials. @since 6.1.6 */
	public static ?array $updated = null;

	/** @var array{autologin: int, params: array, mode: int}|null Last create options. @since 6.1.6 */
	public static ?array $createOptions = null;

	/** @var int Create return value. @since 6.1.6 */
	public static int $createResult = 71;

	/** @var int Update return value. @since 6.1.6 */
	public static int $updateResult = 72;

	/**
	 * Reset deterministic fixture state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public static function reset(): void
	{
		self::$emailUserId = null;
		self::$usernameUserId = null;
		self::$created = null;
		self::$updated = null;
		self::$createOptions = null;
		self::$createResult = 71;
		self::$updateResult = 72;
	}

	/**
	 * Record new-user routing.
	 *
	 * @param   array  $credentials  User credentials.
	 * @param   int    $autologin    Auto-login flag.
	 * @param   array  $params       Registration settings.
	 * @param   int    $mode         Registration mode.
	 *
	 * @return  int  Configured result.
	 * @since   6.1.6
	 */
	public static function create(
		array $credentials,
		int $autologin = 0,
		array $params = ['useractivation' => 0, 'sendpassword' => 1],
		int $mode = 1
	): int
	{
		self::$created = $credentials;
		self::$createOptions = compact('autologin', 'params', 'mode');

		return self::$createResult;
	}

	/**
	 * Record existing-user routing.
	 *
	 * @param   array  $userDetails  User details.
	 *
	 * @return  int  Configured result.
	 * @since   6.1.6
	 */
	public static function update(array $userDetails): int
	{
		self::$updated = $userDetails;

		return self::$updateResult;
	}

	/**
	 * Return the configured username lookup.
	 *
	 * @param   string  $username  Username.
	 *
	 * @return  int|null  Configured user identifier.
	 * @since   6.1.6
	 */
	public static function getUserIdByUsername(string $username): ?int
	{
		return self::$usernameUserId;
	}

	/**
	 * Return the configured email lookup.
	 *
	 * @param   string  $email  Email address.
	 *
	 * @return  int|null  Configured user identifier.
	 * @since   6.1.6
	 */
	public static function getUserIdByEmail(string $email): ?int
	{
		return self::$emailUserId;
	}

	/**
	 * Expose production registration-data preparation.
	 *
	 * @param   array  $credentials  User credentials.
	 * @param   int    $mode         Registration mode.
	 *
	 * @return  array  Prepared model data.
	 * @since   6.1.6
	 */
	public static function prepare(array $credentials, int $mode): array
	{
		return parent::prepareUserData($credentials, $mode);
	}
}
