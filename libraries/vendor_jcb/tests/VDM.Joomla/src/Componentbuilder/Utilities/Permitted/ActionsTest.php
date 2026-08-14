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

namespace VDM\Joomla\Tests\Componentbuilder\Utilities\Permitted;


use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Utilities\Permitted\Actions;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\PermittedActionsFixture;
use VDM\Tests\Support\TestCase;


/**
 * Component, item, and category ACL policy tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Actions::class)]
#[UsesClass(StringHelper::class)]
final class ActionsTest extends TestCase
{
	/**
	 * Normalize target selectors and build stable Joomla ACL asset names.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTargetAndAssetNormalizationProtectsAclIdentity(): void
	{
		$this->assertSame([], PermittedActionsFixture::targets(null));
		$this->assertSame(['edit'], PermittedActionsFixture::targets('edit'));
		$this->assertSame(['edit', 'delete'], PermittedActionsFixture::targets(['edit', 'delete']));
		$this->assertSame([], PermittedActionsFixture::targets((object) ['edit' => true]));
		$this->assertSame(
			[
				'component' => 'com_demo',
				'item' => 'com_demo.article.17',
				'category' => 'com_demo.articles.category.5'
			],
			PermittedActionsFixture::assets('demo', 'article', 'articles', 17, 5)
		);
		$this->assertSame('core.edit', PermittedActionsFixture::categoryAction('article.edit', 'article'));
		$this->assertSame('core.delete', PermittedActionsFixture::categoryAction('core.delete', 'article'));
	}

	/**
	 * Filter actions to the explicitly requested view or core target suffixes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testActionFilteringMatchesOnlyRequestedTargets(): void
	{
		$this->assertFalse(PermittedActionsFixture::filtered('article', 'article.edit', ['edit']));
		$this->assertFalse(PermittedActionsFixture::filtered('article', 'core.create', ['create']));
		$this->assertTrue(PermittedActionsFixture::filtered('article', 'article.delete', ['edit', 'create']));
	}

	/**
	 * Resolve component-wide actions exclusively against the component asset.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testComponentActionUsesGlobalComponentAuthorization(): void
	{
		$user = $this->createMock(User::class);
		$user->expects($this->once())
			->method('authorise')
			->with('core.manage', 'com_demo')
			->willReturn(true);
		$result = new Registry(null, '');
		$record = (object) ['id' => 21, 'created_by' => 7];

		PermittedActionsFixture::process('core.manage', $result, $user, 'demo', 'article', 'articles', $record);

		$this->assertTrue($result->get('core.manage'));
	}

	/**
	 * A denied item scope blocks the action without a permissive global fallback.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDeniedItemScopeCannotBeOverriddenGlobally(): void
	{
		$user = $this->createMock(User::class);
		$user->expects($this->once())
			->method('authorise')
			->with('core.delete', 'com_demo.article.21')
			->willReturn(false);
		$result = new Registry(null, '');
		$record = (object) ['id' => 21, 'created_by' => 7];

		PermittedActionsFixture::process('core.delete', $result, $user, 'demo', 'article', null, $record);

		$this->assertFalse($result->get('core.delete'));
	}

	/**
	 * Own-edit requires both item and component own-edit grants.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOwnEditRequiresBothScopedAuthorizations(): void
	{
		$user = $this->createMock(User::class);
		$user->id = 7;
		$calls = [];
		$user->expects($this->exactly(3))->method('authorise')->willReturnCallback(
			static function (string $action, string $asset) use (&$calls): bool
			{
				$calls[] = [$action, $asset];

				return $action === 'core.edit.own';
			}
		);
		$result = new Registry(null, '');
		$record = (object) ['id' => 21, 'created_by' => 7];

		PermittedActionsFixture::process('core.edit', $result, $user, 'demo', 'article', 'articles', $record);

		$this->assertTrue($result->get('core.edit'));
		$this->assertSame(
			[
				['core.edit', 'com_demo.article.21'],
				['core.edit.own', 'com_demo.article.21'],
				['core.edit.own', 'com_demo']
			],
			$calls
		);
	}

	/**
	 * A category grant still remains subordinate to the component-wide grant.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCategoryGrantStillRequiresGlobalAuthorization(): void
	{
		$user = $this->createMock(User::class);
		$user->id = 9;
		$calls = [];
		$user->expects($this->exactly(3))->method('authorise')->willReturnCallback(
			static function (string $action, string $asset) use (&$calls): bool
			{
				$calls[] = [$action, $asset];

				return $asset === 'com_demo.articles.category.5';
			}
		);
		$result = new Registry(null, '');
		$record = (object) ['id' => 21, 'created_by' => 7, 'catid' => 5];

		PermittedActionsFixture::process('article.edit', $result, $user, 'demo', 'article', 'articles', $record);

		$this->assertFalse($result->get('article.edit'));
		$this->assertSame(
			[
				['article.edit', 'com_demo.article.21'],
				['core.edit', 'com_demo.articles.category.5'],
				['article.edit', 'com_demo']
			],
			$calls
		);
	}

	/**
	 * A supplied plural view must survive nullable normalization.
	 *
	 * The public get() path passes its plural view through safe(..., true); losing
	 * that value silently disables category-scope ACL checks.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testNullableNormalizationPreservesNonEmptyPluralView(): void
	{
		$this->assertSame('articles', PermittedActionsFixture::safeValue('articles', true));
	}
}
