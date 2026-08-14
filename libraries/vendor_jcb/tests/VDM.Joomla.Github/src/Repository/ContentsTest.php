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
use VDM\Joomla\Github\Repository\Contents;
use VDM\Joomla\Github\Tests\Support\GithubTestCase;


/**
 * GitHub repository contents API contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Contents::class)]
final class ContentsTest extends GithubTestCase
{
	/**
	 * Request raw content and JSON metadata with the optional ref query.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetAndMetadataUseExactMediaTypesPathsAndRef(): void
	{
		/** @var Contents $subject */
		[$subject, $transport] = $this->createEndpoint(Contents::class, [
			[200, "raw markdown\n"],
			[200, '{"name":"README.md","sha":"abc123"}']
		]);

		$raw = $subject->get('owner', 'project', 'docs/README.md', 'branch-main');
		$metadata = $subject->metadata('owner', 'project', 'docs/README.md');

		$this->assertSame("raw markdown\n", $raw);
		$this->assertSame('README.md', $metadata?->name);
		$this->assertSame('abc123', $metadata?->sha);
		$this->assertGithubRequest(
			$transport,
			'GET',
			'/repos/owner/project/contents/docs/README.md?ref=branch-main',
			'application/vnd.github.raw+json',
			0
		);
		$this->assertGithubRequest(
			$transport,
			'GET',
			'/repos/owner/project/contents/docs/README.md',
			'application/vnd.github+json',
			1
		);
		$this->assertNull($transport->requests[0]['data']);
		$this->assertNull($transport->requests[1]['data']);
	}

	/**
	 * Delegate root metadata to the trailing-slash contents collection.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRootReturnsDirectoryEntries(): void
	{
		/** @var Contents $subject */
		[$subject, $transport] = $this->createEndpoint(Contents::class, [
			[200, '[{"name":"README.md"}]']
		]);

		$result = $subject->root('owner', 'project', 'release');

		$this->assertSame('README.md', $result[0]->name);
		$this->assertGithubRequest(
			$transport,
			'GET',
			'/repos/owner/project/contents/?ref=release'
		);
	}

	/**
	 * Create a file with only required fields and the 201 response contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateSendsRequiredFieldsAndOmitsAbsentOptionals(): void
	{
		/** @var Contents $subject */
		[$subject, $transport] = $this->createEndpoint(Contents::class, [
			[201, '{"content":{"sha":"new-sha"}}']
		]);

		$result = $subject->create(
			'owner',
			'project',
			'src/File.php',
			"<?php\n",
			'Create file',
			'main'
		);

		$this->assertSame('new-sha', $result?->content->sha);
		$this->assertGithubRequest($transport, 'PUT', '/repos/owner/project/contents/src/File.php');
		$this->assertSame([
			'content' => base64_encode("<?php\n"),
			'message' => 'Create file',
			'branch' => 'main'
		], $this->jsonRequest($transport));
	}

	/**
	 * Include every supported create option with its exact wire name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreateIncludesAllOptionalCommitMetadata(): void
	{
		/** @var Contents $subject */
		[$subject, $transport] = $this->createEndpoint(Contents::class, [
			[201, '{"commit":{"sha":"commit-sha"}}']
		]);

		$subject->create(
			'owner',
			'project',
			'file.txt',
			'contents',
			'Create complete file',
			'main',
			'Alice',
			'alice@example.test',
			'Commit Bot',
			'bot@example.test',
			'generated-branch',
			'2026-08-14T10:00:00Z',
			'2026-08-14T10:01:00Z',
			true
		);

		$this->assertSame([
			'content' => base64_encode('contents'),
			'message' => 'Create complete file',
			'branch' => 'main',
			'author' => [
				'name' => 'Alice',
				'email' => 'alice@example.test'
			],
			'committer' => [
				'name' => 'Commit Bot',
				'email' => 'bot@example.test'
			],
			'new_branch' => 'generated-branch',
			'dates' => [
				'author' => '2026-08-14T10:00:00Z',
				'committer' => '2026-08-14T10:01:00Z'
			],
			'signoff' => true
		], $this->jsonRequest($transport));
	}

	/**
	 * Update required fields alone, then include every supported optional field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdateSerializesRequiredAndOptionalPayloadVariants(): void
	{
		/** @var Contents $subject */
		[$subject, $transport] = $this->createEndpoint(Contents::class, [
			[200, '{"commit":{"sha":"first"}}'],
			[200, '{"commit":{"sha":"second"}}']
		]);

		$subject->update(
			'owner',
			'project',
			'file.txt',
			'first content',
			'Update required',
			'blob-sha',
			'main'
		);
		$subject->update(
			'owner',
			'project',
			'renamed.txt',
			'second content',
			'Update complete',
			'blob-sha-2',
			'develop',
			'Alice',
			'alice@example.test',
			'Commit Bot',
			'bot@example.test',
			'2026-08-14T11:00:00Z',
			'2026-08-14T11:01:00Z',
			'file.txt',
			'generated-branch',
			false
		);

		$this->assertSame([
			'content' => base64_encode('first content'),
			'message' => 'Update required',
			'branch' => 'main',
			'sha' => 'blob-sha'
		], $this->jsonRequest($transport, 0));
		$this->assertSame([
			'content' => base64_encode('second content'),
			'message' => 'Update complete',
			'branch' => 'develop',
			'sha' => 'blob-sha-2',
			'author' => ['name' => 'Alice', 'email' => 'alice@example.test'],
			'committer' => ['name' => 'Commit Bot', 'email' => 'bot@example.test'],
			'dates' => [
				'author' => '2026-08-14T11:00:00Z',
				'committer' => '2026-08-14T11:01:00Z'
			],
			'from_path' => 'file.txt',
			'new_branch' => 'generated-branch',
			'signoff' => false
		], $this->jsonRequest($transport, 1));
		$this->assertGithubRequest($transport, 'PUT', '/repos/owner/project/contents/file.txt', index: 0);
		$this->assertGithubRequest($transport, 'PUT', '/repos/owner/project/contents/renamed.txt', index: 1);
	}

	/**
	 * Delete with required fields alone, then include every optional commit field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDeleteSerializesRequiredAndOptionalPayloadVariants(): void
	{
		/** @var Contents $subject */
		[$subject, $transport] = $this->createEndpoint(Contents::class, [
			[200, '{"commit":{"sha":"first"}}'],
			[200, '{"commit":{"sha":"second"}}']
		]);

		$subject->delete('owner', 'project', 'old.txt', 'Delete required', 'old-sha');
		$subject->delete(
			'owner',
			'project',
			'complete.txt',
			'Delete complete',
			'complete-sha',
			'main',
			'Alice',
			'alice@example.test',
			'Commit Bot',
			'bot@example.test',
			'2026-08-14T12:00:00Z',
			'2026-08-14T12:01:00Z',
			'generated-branch',
			true
		);

		$this->assertSame([
			'message' => 'Delete required',
			'sha' => 'old-sha'
		], $this->jsonRequest($transport, 0));
		$this->assertSame([
			'message' => 'Delete complete',
			'sha' => 'complete-sha',
			'branch' => 'main',
			'author' => ['name' => 'Alice', 'email' => 'alice@example.test'],
			'committer' => ['name' => 'Commit Bot', 'email' => 'bot@example.test'],
			'dates' => [
				'author' => '2026-08-14T12:00:00Z',
				'committer' => '2026-08-14T12:01:00Z'
			],
			'new_branch' => 'generated-branch',
			'signoff' => true
		], $this->jsonRequest($transport, 1));
		$this->assertGithubRequest($transport, 'DELETE', '/repos/owner/project/contents/old.txt', index: 0);
		$this->assertGithubRequest($transport, 'DELETE', '/repos/owner/project/contents/complete.txt', index: 1);
	}

	/**
	 * Return the unsupported editor contract without performing I/O.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEditorReturnsNullWithoutHttpRequest(): void
	{
		/** @var Contents $subject */
		[$subject, $transport] = $this->createEndpoint(Contents::class);

		$this->assertNull($subject->editor('owner', 'project', '.editorconfig', 'main'));
		$this->assertSame([], $transport->requests);
	}

	/**
	 * Fetch a blob as JSON and expose its decoded content.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBlobUsesGitBlobResourceAndJsonMediaType(): void
	{
		/** @var Contents $subject */
		[$subject, $transport] = $this->createEndpoint(Contents::class, [[
			200,
			'{"sha":"blob-sha","content":"SGVsbG8=","encoding":"base64"}'
		]]);

		$result = $subject->blob('owner', 'project', 'blob-sha');

		$this->assertSame('blob-sha', $result?->sha);
		$this->assertSame('Hello', $result?->decoded_content);
		$this->assertGithubRequest(
			$transport,
			'GET',
			'/repos/owner/project/git/blobs/blob-sha'
		);
	}
}
