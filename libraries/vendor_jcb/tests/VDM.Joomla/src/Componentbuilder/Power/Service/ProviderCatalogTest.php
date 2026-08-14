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

namespace VDM\Joomla\Tests\Componentbuilder\Power\Service;


use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Tests\Support\ServiceProviderTestCase;


/**
 * Contracts for the Super Power service-provider catalog.
 *
 * @since  1.0.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Power\Service')]
final class ProviderCatalogTest extends ServiceProviderTestCase
{
	/**
	 * Verify every Power provider alias, key, factory, and lifecycle.
	 *
	 * @param   class-string<ServiceProviderInterface>           $providerClass  Provider under test.
	 * @param   array{aliases: int, services: int, hash: string}  $expected       Reviewed catalog fingerprint.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('providerContracts')]
	public function testProviderCatalog(string $providerClass, array $expected): void
	{
		$this->assertServiceProviderContract($providerClass, $expected);
	}

	/**
	 * Provide the reviewed Power service-provider catalogs.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, array{aliases: int, services: int, hash: string}}>
	 * @since   1.0.0
	 */
	public static function providerContracts(): iterable
	{
		yield 'generator' => [\VDM\Joomla\Componentbuilder\Power\Service\Generator::class, [
			'aliases' => 7,
			'services' => 7,
			'hash' => 'c7ecd97084096e2019374719868ff9cfdf62e943b9c47fb267eccea31fe828ca'
		]];
		yield 'git' => [\VDM\Joomla\Componentbuilder\Power\Service\Git::class, [
			'aliases' => 1,
			'services' => 1,
			'hash' => '3ce4e553e87685d07813aa56457368761703a739aa42c789a47d67d52a1efb15'
		]];
		yield 'gitea' => [\VDM\Joomla\Componentbuilder\Power\Service\Gitea::class, [
			'aliases' => 1,
			'services' => 1,
			'hash' => '90bc912c77570b510d256edee7f0554b934e40d757ef8553e677aaa685f26621'
		]];
		yield 'github' => [\VDM\Joomla\Componentbuilder\Power\Service\Github::class, [
			'aliases' => 3,
			'services' => 3,
			'hash' => '8ee635547f513fbbcd867f6d35a9451b84b303ed3c8a52a17de3cf93453ff568'
		]];
		yield 'power' => [\VDM\Joomla\Componentbuilder\Power\Service\Power::class, [
			'aliases' => 10,
			'services' => 9,
			'hash' => '59020f476bf27b53daf4805ee91a874c19dfdd2e7b41407979660a89d14a1d6b'
		]];
	}
}
