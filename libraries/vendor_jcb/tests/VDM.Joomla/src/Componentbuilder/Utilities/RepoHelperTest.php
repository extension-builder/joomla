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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Utilities\RepoHelper;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Tests\Support\RepoHelperFixture;
use VDM\Tests\Support\TestCase;


/**
 * Repository configuration normalization tests.
 *
 * @since  6.1.6
 */
#[CoversClass(RepoHelper::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
final class RepoHelperTest extends TestCase
{
	/**
	 * Preserve usable Gitea connection and author metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNormalizePreservesAuthorizedGiteaDetails(): void
	{
		$record = (object) [
			'type' => 1,
			'base' => 'https://git.example/api/v1',
			'access_repo' => 1,
			'username' => 'builder',
			'token' => 'secret',
			'author_name' => 'JCB Builder',
			'author_email' => 'builder@example.com',
			'addplaceholders' => '[{"target":"[[OWNER]]","value":"acme"},{"target":"[[REPO]]","value":"demo"}]'
		];

		$result = RepoHelperFixture::normalize($record);

		$this->assertSame($record, $result);
		$this->assertSame('gitea', $result->target);
		$this->assertSame('https://git.example/api/v1', $result->base);
		$this->assertSame('builder', $result->username);
		$this->assertSame('secret', $result->token);
		$this->assertSame('JCB Builder', $result->author_name);
		$this->assertSame('builder@example.com', $result->author_email);
		$this->assertSame(
			['[[OWNER]]' => 'acme', '[[REPO]]' => 'demo'],
			$result->placeholders
		);
		$this->assertObjectNotHasProperty('access_repo', $result);
		$this->assertObjectNotHasProperty('addplaceholders', $result);
	}

	/**
	 * Remove unauthorized credentials and enforce the canonical GitHub API root.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testNormalizeSanitizesRestrictedGithubRecord(): void
	{
		$result = RepoHelperFixture::normalize(
			(object) [
				'type' => 2,
				'base' => 'https://malicious.example',
				'access_repo' => 0,
				'username' => 'must-not-leak',
				'token' => 'must-not-leak',
				'author_name' => '123456',
				'author_email' => 'invalid-address',
				'addplaceholders' => 'not-json'
			]
		);

		$this->assertSame('github', $result->target);
		$this->assertSame('https://api.github.com', $result->base);
		$this->assertNull($result->author_name);
		$this->assertNull($result->author_email);
		$this->assertSame([], $result->placeholders);
		$this->assertObjectNotHasProperty('username', $result);
		$this->assertObjectNotHasProperty('token', $result);
	}

	/**
	 * Map persisted repository type identifiers to their provider contract.
	 *
	 * @param   int     $type      Repository type.
	 * @param   string  $expected  Provider name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideTargetTypes')]
	public function testTargetMapsProviderTypes(int $type, string $expected): void
	{
		$this->assertSame($expected, RepoHelperFixture::target($type));
	}

	/**
	 * Supply known and fallback repository type identifiers.
	 *
	 * @return  iterable<string, array{int, string}>
	 * @since   6.1.6
	 */
	public static function provideTargetTypes(): iterable
	{
		yield 'gitea' => [1, 'gitea'];
		yield 'github' => [2, 'github'];
		yield 'unknown is github-compatible' => [99, 'github'];
	}

	/**
	 * Return an empty placeholder map for invalid or empty persisted data.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPlaceholderMappingRejectsInvalidShapes(): void
	{
		$this->assertSame([], RepoHelperFixture::placeholders(''));
		$this->assertSame([], RepoHelperFixture::placeholders('{}'));
		$this->assertSame([], RepoHelperFixture::placeholders('not-json'));
	}
}
