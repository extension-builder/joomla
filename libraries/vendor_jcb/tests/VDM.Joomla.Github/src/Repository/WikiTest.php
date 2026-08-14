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

namespace VDM\Joomla\Github\Tests\Repository;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use VDM\Joomla\Github\Repository\Wiki;
use VDM\Joomla\Github\Tests\Support\GithubTestCase;


/**
 * GitHub repository wiki contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Wiki::class)]
final class WikiTest extends GithubTestCase
{
	/**
	 * Fetch public raw Markdown and expose the interface's base64 content shape.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetFetchesRawWikiMarkdownAndReturnsEncodedPage(): void
	{
		/** @var Wiki $subject */
		[$subject, $transport] = $this->createEndpoint(Wiki::class, [
			[200, "# Home\n\nWelcome.\n"]
		]);

		$result = $subject->get('owner', 'project', 'Home');

		$this->assertSame('Home.md', $result?->name);
		$this->assertSame(base64_encode("# Home\n\nWelcome.\n"), $result?->content);
		$this->assertCount(1, $transport->requests);
		$this->assertSame('GET', $transport->requests[0]['method']);
		$this->assertSame(
			'https://raw.githubusercontent.com/wiki/owner/project/Home.md',
			$transport->requests[0]['uri']
		);
		$this->assertNull($transport->requests[0]['data']);
	}

	/**
	 * Keep API credentials off the separate raw.githubusercontent.com host.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testGetDoesNotLeakApiAuthorizationToRawContentHost(): void
	{
		/** @var Wiki $subject */
		[$subject, $transport] = $this->createEndpoint(Wiki::class, [[200, 'content']]);

		$subject->get('owner', 'project', 'Home');

		$this->assertArrayNotHasKey('Authorization', $transport->requests[0]['headers']);
	}

	/**
	 * Return stable unsupported-operation values without attempting HTTP calls.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUnsupportedWikiMutationsAndListingsDoNotPerformIo(): void
	{
		/** @var Wiki $subject */
		[$subject, $transport] = $this->createEndpoint(Wiki::class);

		$this->assertNull(
			$subject->create('owner', 'project', 'Home', base64_encode('content'), 'Create')
		);
		$this->assertNull($subject->pages('owner', 'project', 2, 25));
		$this->assertSame('error', $subject->delete('owner', 'project', 'Home'));
		$this->assertNull(
			$subject->edit('owner', 'project', 'Home', 'Renamed', 'content', 'Edit')
		);
		$this->assertNull($subject->revisions('owner', 'project', 'Home', 2));
		$this->assertSame([], $transport->requests);
	}
}
