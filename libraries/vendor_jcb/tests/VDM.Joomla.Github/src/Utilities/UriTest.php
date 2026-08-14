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

namespace VDM\Joomla\Github\Tests\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Github\Utilities\Uri;


/**
 * GitHub URI builder tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Uri::class)]
final class UriTest extends TestCase
{
	/**
	 * Normalize the API root and compose paths without endpoint/version segments.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testApiAndGetNormalizeSlashes(): void
	{
		$subject = new Uri('https://github.example.test/base///', 'ignored', 'ignored');

		$this->assertSame('https://github.example.test/base/', $subject->api());
		$this->assertSame(
			'https://github.example.test/base/repos/owner/repo',
			(string) $subject->get('/repos/owner/repo')
		);
	}

	/**
	 * Replace and expose the configured URL while keeping URI instances isolated.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetUrlAndIndependentRequests(): void
	{
		$subject = new Uri();
		$subject->setUrl('https://enterprise.github.test/api');
		$first = $subject->get('repos');
		$second = $subject->get('repos');
		$first->setVar('page', 2);

		$this->assertSame('https://enterprise.github.test/api', $subject->getUrl());
		$this->assertNotSame($first, $second);
		$this->assertSame('https://enterprise.github.test/api/repos?page=2', (string) $first);
		$this->assertSame('https://enterprise.github.test/api/repos', (string) $second);
	}
}
