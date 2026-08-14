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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Gitea\Utilities\Uri;


/**
 * Gitea URI construction tests.
 *
 * @since  1.0.0
 */
#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
	/**
	 * Build the documented default API root.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testDefaultsBuildTheVdmGiteaV1ApiRoot(): void
	{
		$subject = new Uri();

		$this->assertSame('https://git.vdm.dev', $subject->getUrl());
		$this->assertSame('https://git.vdm.dev/api/v1', $subject->api());
		$this->assertSame(
			'https://git.vdm.dev/api/v1/repos/acme/project?ref=release',
			(string) $subject->get('/repos/acme/project?ref=release')
		);
	}

	/**
	 * Preserve explicitly supplied endpoint/version segments and URL changes.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testCustomSegmentsAndRuntimeUrlAreUsedVerbatim(): void
	{
		$subject = new Uri('https://code.example/root', 'rest', 'v2');

		$this->assertSame('https://code.example/root/rest/v2', $subject->api());

		$subject->setUrl('http://localhost:3000');

		$this->assertSame('http://localhost:3000/rest/v2', $subject->api());
		$this->assertSame(
			'http://localhost:3000/rest/v2/user/repos',
			(string) $subject->get('/user/repos')
		);
	}
}
