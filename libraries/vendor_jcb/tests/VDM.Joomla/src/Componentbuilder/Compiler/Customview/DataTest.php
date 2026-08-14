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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Customview;


use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Customview\Data;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Custom and site-view cache isolation contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Data::class)]
final class DataTest extends CompilerDomainTestCase
{
	/**
	 * The same ID remains isolated by table and resolves through GUID aliases.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCacheKeysIsolateSiteAndCustomAdminViewDomains(): void
	{
		$siteGuid = '915c0829-87c1-4d17-8f84-ff616f5cefd0';
		$adminGuid = '12166619-f776-4a37-9da5-33b9f4c39129';
		$site = (object) ['id' => 17, 'guid' => $siteGuid, 'codename' => 'articles'];
		$admin = (object) ['id' => 17, 'guid' => $adminGuid, 'codename' => 'dashboard'];
		$subject = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$this->setCompilerProperty($subject, 'data', [
			'17site_view' => $site,
			'17custom_admin_view' => $admin,
		]);
		$this->setCompilerProperty($subject, 'index', [
			'17site_view' => '17site_view',
			$siteGuid . 'site_view' => '17site_view',
			'17custom_admin_view' => '17custom_admin_view',
			$adminGuid . 'custom_admin_view' => '17custom_admin_view',
		]);

		$this->assertSame($site, $subject->get(17, 'site_view'));
		$this->assertSame($site, $subject->get($siteGuid, 'site_view'));
		$this->assertSame($admin, $subject->get(17, 'custom_admin_view'));
		$this->assertSame($admin, $subject->get($adminGuid, 'custom_admin_view'));
		$this->assertNotSame(
			$subject->get(17, 'site_view'),
			$subject->get(17, 'custom_admin_view')
		);
	}
}
