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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Utilities\Base64Helper;


/**
 * JCB base64 storage-format contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Base64Helper::class)]
final class Base64HelperTest extends TestCase
{
	/**
	 * Decode the explicit JCB suffix format without leaking its marker.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOpenDecodesJcbMarkedPayload(): void
	{
		$payload = "binary\0payload\nwith unicode Ω";
		$key = '__jcb_base64_marker__';

		$this->assertSame(
			$payload,
			Base64Helper::open(base64_encode($payload) . $key, $key)
		);
	}

	/**
	 * Decode canonical unmarked base64 as the legacy fallback format.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOpenDecodesCanonicalUnmarkedBase64(): void
	{
		$this->assertSame('JCB compiler', Base64Helper::open('SkNCIGNvbXBpbGVy'));
	}

	/**
	 * Preserve malformed and non-canonical payloads instead of corrupting them.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOpenPreservesValuesThatAreNotCanonicalBase64(): void
	{
		$this->assertSame('not base64!', Base64Helper::open('not base64!'));
		$this->assertSame('YQ', Base64Helper::open('YQ'));
	}

	/**
	 * Honor the requested fallback for empty and null inputs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOpenUsesExplicitFallbackOutsideStringMode(): void
	{
		$this->assertSame('fallback', Base64Helper::open('', null, 'fallback'));
		$this->assertNull(Base64Helper::open(null));
		$this->assertSame('', Base64Helper::open(''));
	}
}
