<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support
{
	/**
	 * Record cURL transport outcomes without sending network requests.
	 *
	 * @since  6.2.1
	 */
	final class FileHelperCurlFixture
	{
		/**
		 * Response returned by the next transfer.
		 *
		 * @var    string|bool
		 * @since  6.2.1
		 */
		public static string|bool $response = false;

		/**
		 * HTTP status returned by transfer metadata.
		 *
		 * @var    int
		 * @since  6.2.1
		 */
		public static int $status = 200;

		/**
		 * Weak reference to the most recent handle, for lifecycle assertions.
		 *
		 * @var    \WeakReference|null
		 * @since  6.2.1
		 */
		public static ?\WeakReference $handle = null;
	}
}

namespace VDM\Joomla\Utilities
{
	use VDM\Tests\Support\FileHelperCurlFixture;

	/**
	 * Force the content reader to exercise its cURL fallback.
	 *
	 * @param   string  $path  Requested URL.
	 *
	 * @return  false
	 * @since   6.2.1
	 */
	function file_get_contents(string $path): false
	{
		return false;
	}

	/**
	 * Return the selected response without executing a real transfer.
	 *
	 * @param   \CurlHandle  $handle  Actual handle configured by FileHelper.
	 *
	 * @return  string|bool
	 * @since   6.2.1
	 */
	function curl_exec(\CurlHandle $handle): string|bool
	{
		FileHelperCurlFixture::$handle = \WeakReference::create($handle);

		return FileHelperCurlFixture::$response;
	}

	/**
	 * Return controlled HTTP metadata for the isolated existence check.
	 *
	 * @param   \CurlHandle  $handle  Actual handle configured by FileHelper.
	 * @param   int          $option  Requested transfer metadata option.
	 *
	 * @return  int
	 * @since   6.2.1
	 */
	function curl_getinfo(\CurlHandle $handle, int $option): int
	{
		return FileHelperCurlFixture::$status;
	}
}
