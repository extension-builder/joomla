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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Componentbuilder\Utilities\Uri;


/**
 * Component Builder API URI construction tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
	/**
	 * Build the documented public v1 API root and preserve query strings.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDefaultsBuildPublicV1ApiUris(): void
	{
		$subject = new Uri();

		$this->assertSame('https://api.joomlacomponentbuilder.com', $subject->getUrl());
		$this->assertSame('https://api.joomlacomponentbuilder.com/v1', $subject->api());
		$this->assertSame(
			'https://api.joomlacomponentbuilder.com/v1/package/resolve?id=42&format=json',
			(string) $subject->get('/package/resolve?id=42&format=json')
		);
	}

	/**
	 * Use custom API roots and runtime URL replacements verbatim.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomVersionAndRuntimeUrlAreApplied(): void
	{
		$subject = new Uri('https://jcb.example/api', 'v2');

		$this->assertSame('https://jcb.example/api/v2', $subject->api());

		$subject->setUrl('http://localhost:8080');

		$this->assertSame('http://localhost:8080', $subject->getUrl());
		$this->assertSame('http://localhost:8080/v2/items/7', (string) $subject->get('/items/7'));
	}
}
