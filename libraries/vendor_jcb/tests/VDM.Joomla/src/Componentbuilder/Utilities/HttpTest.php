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


use Joomla\CMS\Http\Http as JoomlaHttp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Componentbuilder\Utilities\Http;


/**
 * Component Builder API HTTP option tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Http::class)]
final class HttpTest extends TestCase
{
	/**
	 * Configure the stable JCB user agent and JSON content header.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConstructorConfiguresJcbJsonRequests(): void
	{
		$subject = new Http();

		$this->assertInstanceOf(JoomlaHttp::class, $subject);
		$this->assertSame('JCB/5.0', $subject->getOption('userAgent'));
		$this->assertSame(
			['Content-Type' => 'application/json'],
			(array) $subject->getOption('headers')
		);
	}
}
