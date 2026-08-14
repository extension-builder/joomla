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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Adminview;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Adminview\Data;
use VDM\Joomla\Componentbuilder\Compiler\Adminview\DefaultOrdering;
use VDM\Joomla\Componentbuilder\Compiler\Adminview\Permission;
use VDM\Joomla\Componentbuilder\Compiler\Builder\HasPermissions;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Lists;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ViewsDefaultOrdering;
use VDM\Joomla\Componentbuilder\Compiler\Field\DatabaseName;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Admin-view cache, ordering, and permission contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Data::class)]
#[CoversClass(DefaultOrdering::class)]
#[CoversClass(Permission::class)]
final class AdminviewContractTest extends CompilerDomainTestCase
{
	/**
	 * Cached admin views resolve identically through ID and GUID indices.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDataReturnsCachedIdentityWithoutReloadingDependencies(): void
	{
		$guid = '5871fef9-5d4e-4517-99ce-833f04d08064';
		$view = (object) ['id' => 17, 'guid' => $guid, 'name_single' => 'Article'];
		$subject = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$this->setCompilerProperty($subject, 'data', [17 => $view]);
		$this->setCompilerProperty($subject, 'index', [17 => 17, $guid => 17]);

		$this->assertSame($view, $subject->get(17));
		$this->assertSame($view, $subject->get($guid));
		$this->assertNull($subject->get(''));
	}

	/**
	 * Default ordering resolves the first configured database field and fallback.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDefaultOrderingResolvesConfiguredFieldAndDisabledFallback(): void
	{
		$order = new ViewsDefaultOrdering();
		$lists = new Lists();
		$order->set('articles.add_admin_ordering', 1);
		$order->set('articles.admin_ordering_fields', [
			['field' => 17, 'direction' => 'ASC'],
		]);
		$lists->set('articles', [
			['id' => 17, 'guid' => 'f824e6be-209a-46cc-bd68-ad82d5644c5f', 'type' => 'text', 'code' => 'title'],
		]);
		$subject = new DefaultOrdering($order, new DatabaseName($lists, new Registry()));

		$this->assertSame(
			['name' => 'a.title', 'direction' => 'ASC'],
			$subject->get('articles')
		);
		$this->assertSame(
			['name' => 'a.id', 'direction' => 'DESC'],
			$subject->get('categories')
		);
	}

	/**
	 * Missing configured ordering fields must be skipped in favor of later valid fields.
	 *
	 * Production compares the nullable database-name result only with false, so
	 * null is accepted and returned before the valid second ordering is reached.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testDefaultOrderingSkipsMissingFields(): void
	{
		$order = new ViewsDefaultOrdering();
		$lists = new Lists();
		$order->set('articles.add_admin_ordering', 1);
		$order->set('articles.admin_ordering_fields', [
			['field' => 999, 'direction' => 'DESC'],
			['field' => 17, 'direction' => 'ASC'],
		]);
		$lists->set('articles', [
			['id' => 17, 'guid' => 'f824e6be-209a-46cc-bd68-ad82d5644c5f', 'type' => 'text', 'code' => 'title'],
		]);

		$this->assertSame(
			['name' => 'a.title', 'direction' => 'ASC'],
			(new DefaultOrdering($order, new DatabaseName($lists, new Registry())))->get('articles')
		);
	}

	/**
	 * Permission detection records each supported permission source and caches it.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPermissionDetectsHistoryViewAndFieldSourcesWithCaching(): void
	{
		$registry = new HasPermissions();
		$subject = new Permission($registry);
		$history = ['history' => 1];
		$viewPermission = ['settings' => (object) [
			'permissions' => [['implementation' => 3]],
			'fields' => [],
		]];
		$fieldPermission = ['settings' => (object) [
			'permissions' => [],
			'fields' => [['permission' => ['edit' => true]]],
		]];
		$none = ['settings' => (object) ['permissions' => [], 'fields' => []]];
		$historyName = 'article';
		$viewName = 'category';
		$fieldName = 'author';
		$noneName = 'tag';

		$this->assertTrue($subject->check($history, $historyName));
		$this->assertTrue($subject->check($viewPermission, $viewName));
		$this->assertTrue($subject->check($fieldPermission, $fieldName));
		$this->assertFalse($subject->check($none, $noneName));

		$history = [];
		$this->assertTrue($subject->check($history, $historyName));
		$this->assertTrue($registry->get('article'));
		$this->assertTrue($registry->get('category'));
		$this->assertTrue($registry->get('author'));
		$this->assertFalse($registry->exists('tag'));
	}
}
