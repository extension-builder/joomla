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

namespace VDM\Joomla\Tests\Service;


use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Tests\Support\ServiceProviderTestCase;


/**
 * Contracts for the reusable data-layer service-provider catalog.
 *
 * @since  1.0.0
 */
#[CoversNamespace('VDM\Joomla\Service')]
final class ProviderCatalogTest extends ServiceProviderTestCase
{
	/**
	 * Verify every reusable provider alias, key, factory, and lifecycle.
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
	 * Provide the reviewed reusable service-provider catalogs.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, array{aliases: int, services: int, hash: string}}>
	 * @since   1.0.0
	 */
	public static function providerContracts(): iterable
	{
		yield 'data' => [\VDM\Joomla\Service\Data::class, [
			'aliases' => 10,
			'services' => 10,
			'hash' => '62d0be89d5b07e8c765376a69fe9f07804ee52e7042ec128238a9dbacb45ce2b'
		]];
		yield 'database' => [\VDM\Joomla\Service\Database::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => '5c0ffeb39ebab6ff7148aa616efa3239f89d5ff9664d64c06028442bb6f2d08e'
		]];
		yield 'import' => [\VDM\Joomla\Service\Import::class, [
			'aliases' => 8,
			'services' => 8,
			'hash' => '9f9773d5f61ced44cfacaa54852cd8324747df8f87ae99c56dcdc8b319ef68f2'
		]];
		yield 'model' => [\VDM\Joomla\Service\Model::class, [
			'aliases' => 2,
			'services' => 2,
			'hash' => '47d103849b9ab175b8a9010ddf774faaf479644498e9387e7ec040a21b1c6d6d'
		]];
		yield 'table' => [\VDM\Joomla\Service\Table::class, [
			'aliases' => 3,
			'services' => 3,
			'hash' => '4590546451d1663d4a160dbf2ee6d73e058dd5977bc97cad94fbe3d6482d7ac7'
		]];
	}
}
