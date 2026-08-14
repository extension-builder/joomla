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


/**
 * Legacy component-helper boundary for compiler Loader contracts.
 *
 * @since  1.0.0
 */
final class LoaderUikitHelperFixture
{
	/**
	 * Calls received through the legacy static component helper.
	 *
	 * @var    array<int, array{0: string, 1: array<int, string>}>
	 * @since  1.0.0
	 */
	public static array $calls = [];

	/**
	 * Discover UIkit components while preserving previously registered values.
	 *
	 * @param   string              $content     Content inspected by the loader.
	 * @param   array<int, string>  $components  Previously registered components.
	 *
	 * @return  array<int, string>|false  Discovered components or the failure sentinel.
	 * @since   1.0.0
	 */
	public static function getUikitComp(string $content, array $components = []): array|false
	{
		self::$calls[] = [$content, $components];

		if (!str_contains($content, 'uk-'))
		{
			return $components !== [] ? $components : false;
		}

		return array_values(array_unique([...$components, 'fixture-component']));
	}

	/**
	 * Clear calls between tests.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public static function reset(): void
	{
		self::$calls = [];
	}
}
