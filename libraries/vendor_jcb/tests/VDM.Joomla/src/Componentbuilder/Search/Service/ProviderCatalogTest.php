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

namespace VDM\Joomla\Tests\Componentbuilder\Search\Service;


use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Tests\Support\ServiceProviderTestCase;


/**
 * Contracts for the search service-provider catalog.
 *
 * @since  1.0.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Search\Service')]
final class ProviderCatalogTest extends ServiceProviderTestCase
{
	/**
	 * Verify every search provider alias, key, factory, and lifecycle.
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
	 * Provide the reviewed search service-provider catalogs.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, array{aliases: int, services: int, hash: string}}>
	 * @since   1.0.0
	 */
	public static function providerContracts(): iterable
	{
		yield 'agent' => [\VDM\Joomla\Componentbuilder\Search\Service\Agent::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => 'c33e95d2c50dcccb1de96b85398d2baddd4b6a22f0fdb8f39b47371d8a34ebbf'
		]];
		yield 'database' => [\VDM\Joomla\Componentbuilder\Search\Service\Database::class, [
			'aliases' => 3,
			'services' => 3,
			'hash' => 'e0bfbd70e60d2a5fba18bd4e2053c51654468b27086a31a8c2b129d5d56d7ba6'
		]];
		yield 'model' => [\VDM\Joomla\Componentbuilder\Search\Service\Model::class, [
			'aliases' => 2,
			'services' => 2,
			'hash' => 'db4608652954f0e6bf2021df00414e826ce0908b52ef74cf509973acb0345573'
		]];
		yield 'search' => [\VDM\Joomla\Componentbuilder\Search\Service\Search::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => '43e80ba92aa14e84c30c1a6854a580bea221195223d19b9934c95b9cf9e2e409'
		]];
	}
}
