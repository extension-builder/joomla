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
use VDM\Joomla\Github\Repository\Tags;
use VDM\Joomla\Github\Tests\Support\GithubTestCase;


/**
 * GitHub repository tags API contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Tags::class)]
final class TagsTest extends GithubTestCase
{
	/**
	 * Send explicit pagination and replace null pagination with package defaults.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testListUsesExactPaginationContract(): void
	{
		/** @var Tags $subject */
		[$subject, $transport] = $this->createEndpoint(Tags::class, [
			[200, '[{"name":"v2.0.0"}]'],
			[200, '[]']
		]);

		$result = $subject->list('owner', 'project', 3, 25);
		$defaults = $subject->list('owner', 'project', null, null);

		$this->assertSame('v2.0.0', $result[0]->name);
		$this->assertSame([], $defaults);
		$this->assertGithubRequest(
			$transport,
			'GET',
			'/repos/owner/project/tags?page=3&per_page=25',
			index: 0
		);
		$this->assertGithubRequest(
			$transport,
			'GET',
			'/repos/owner/project/tags?page=1&per_page=10',
			index: 1
		);
	}

	/**
	 * Continue across a full page until the named tag is found.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetPaginatesUntilMatchingTagIsFound(): void
	{
		$firstPage = [];

		for ($index = 1; $index <= 10; $index++)
		{
			$firstPage[] = ['name' => 'v1.0.' . $index];
		}

		/** @var Tags $subject */
		[$subject, $transport] = $this->createEndpoint(Tags::class, [
			[200, json_encode($firstPage)],
			[200, '[{"name":"v2.0.0","commit":{"sha":"target-sha"}}]']
		]);

		$result = $subject->get('owner', 'project', 'v2.0.0');

		$this->assertSame('v2.0.0', $result?->name);
		$this->assertSame('target-sha', $result?->commit->sha);
		$this->assertGithubRequest(
			$transport,
			'GET',
			'/repos/owner/project/tags?page=1&per_page=10',
			index: 0
		);
		$this->assertGithubRequest(
			$transport,
			'GET',
			'/repos/owner/project/tags?page=2&per_page=10',
			index: 1
		);
	}

	/**
	 * Stop without another request when a short page does not contain the tag.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetReturnsNullAfterExhaustedShortPage(): void
	{
		/** @var Tags $subject */
		[$subject, $transport] = $this->createEndpoint(Tags::class, [
			[200, '[{"name":"v1.0.0"},{"name":"v1.1.0"}]']
		]);

		$this->assertNull($subject->get('owner', 'project', 'missing'));
		$this->assertCount(1, $transport->requests);
	}

	/**
	 * Retrieve one annotated tag by its object SHA.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testShaUsesAnnotatedTagResource(): void
	{
		/** @var Tags $subject */
		[$subject, $transport] = $this->createEndpoint(Tags::class, [
			[200, '{"sha":"tag-sha","tag":"v2.0.0"}']
		]);

		$result = $subject->sha('owner', 'project', 'tag-sha');

		$this->assertSame('v2.0.0', $result?->tag);
		$this->assertGithubRequest(
			$transport,
			'GET',
			'/repos/owner/project/git/tags/tag-sha'
		);
	}

	/**
	 * Create the annotated tag object before creating its refs/tags reference.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateUsesTwoStepTagAndReferenceProtocol(): void
	{
		/** @var Tags $subject */
		[$subject, $transport] = $this->createEndpoint(Tags::class, [
			[200, '{"sha":"annotated-sha"}'],
			[200, '{"ref":"refs/tags/v2.0.0","object":{"sha":"annotated-sha"}}']
		]);

		$result = $subject->create('owner', 'project', 'v2.0.0', 'commit-sha', 'Release 2.0.0');

		$this->assertSame('refs/tags/v2.0.0', $result?->ref);
		$this->assertGithubRequest(
			$transport,
			'POST',
			'/repos/owner/project/git/tags',
			index: 0
		);
		$this->assertSame([
			'tag' => 'v2.0.0',
			'message' => 'Release 2.0.0',
			'object' => 'commit-sha',
			'type' => 'commit'
		], $this->jsonRequest($transport, 0));
		$this->assertGithubRequest(
			$transport,
			'POST',
			'/repos/owner/project/git/refs',
			index: 1
		);
		$this->assertSame([
			'ref' => 'refs/tags/v2.0.0',
			'sha' => 'annotated-sha'
		], $this->jsonRequest($transport, 1));
	}

	/**
	 * Stop before reference creation if GitHub omits the annotated-tag SHA.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateReturnsNullWhenTagObjectHasNoSha(): void
	{
		/** @var Tags $subject */
		[$subject, $transport] = $this->createEndpoint(Tags::class, [
			[200, '{"tag":"v2.0.0"}']
		]);

		$this->assertNull(
			$subject->create('owner', 'project', 'v2.0.0', 'commit-sha', 'Release')
		);
		$this->assertCount(1, $transport->requests);
	}

	/**
	 * Delete a tag reference and map the empty 204 response to success.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDeleteUsesTagRefAndReturnsSuccess(): void
	{
		/** @var Tags $subject */
		[$subject, $transport] = $this->createEndpoint(Tags::class, [[204, '']]);

		$this->assertSame('success', $subject->delete('owner', 'project', 'v2.0.0'));
		$this->assertGithubRequest(
			$transport,
			'DELETE',
			'/repos/owner/project/git/refs/tags/v2.0.0'
		);
	}
}
