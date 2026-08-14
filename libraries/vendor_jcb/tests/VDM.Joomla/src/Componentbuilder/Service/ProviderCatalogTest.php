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

namespace VDM\Joomla\Tests\Componentbuilder\Service;


use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Tests\Support\ServiceProviderTestCase;


/**
 * Contracts for the Component Builder application service-provider catalog.
 *
 * @since  1.0.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Service')]
final class ProviderCatalogTest extends ServiceProviderTestCase
{
	/**
	 * Verify every application provider alias, key, factory, and lifecycle.
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
	 * Provide the reviewed application service-provider catalogs.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, array{aliases: int, services: int, hash: string}}>
	 * @since   1.0.0
	 */
	public static function providerContracts(): iterable
	{
		yield 'api' => [\VDM\Joomla\Componentbuilder\Service\Api::class, [
			'aliases' => 1,
			'services' => 1,
			'hash' => 'd82099ee2ef94cca1333f268cf991cc243331855aaaa8c0b3dd5f92c02ec33fe'
		]];
		yield 'core rules' => [\VDM\Joomla\Componentbuilder\Service\CoreRules::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => '39fad6301beb69ed1aeb5b0ccce8535a3814364db7909f9d738a41b1696a7ce6'
		]];
		yield 'crypt' => [\VDM\Joomla\Componentbuilder\Service\Crypt::class, [
			'aliases' => 8,
			'services' => 8,
			'hash' => 'ecd2651e55656510cb9f392c74e81e410edfa7b21be559742a8bbddb83939d5a'
		]];
		yield 'data' => [\VDM\Joomla\Componentbuilder\Service\Data::class, [
			'aliases' => 1,
			'services' => 1,
			'hash' => '7fa2dd1c512817339ef83cbca6733bc15b32a2cdbcc4b3bbb79d9f78fdd59f6a'
		]];
		yield 'gitea' => [\VDM\Joomla\Componentbuilder\Service\Gitea::class, [
			'aliases' => 2,
			'services' => 2,
			'hash' => 'fcea96cfa56ca711199e8a31d093be03750782c3175adef26c342218d8700a92'
		]];
		yield 'import' => [\VDM\Joomla\Componentbuilder\Service\Import::class, [
			'aliases' => 7,
			'services' => 7,
			'hash' => '48ea0843f725553a544b0e792f5ea2dc8def487f350e7bd763814f94a4e45f3d'
		]];
		yield 'network' => [\VDM\Joomla\Componentbuilder\Service\Network::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => '9a18f4451a7a75304218f5eb5dc2f3a2f86d14012f34f19762a104259f970ca5'
		]];
		yield 'server' => [\VDM\Joomla\Componentbuilder\Service\Server::class, [
			'aliases' => 4,
			'services' => 4,
			'hash' => 'fd4170a21a5b3b2d20767dd841a076e8f3aa90f61bc39a7a9b7757f6d3170f89'
		]];
		yield 'spreadsheet' => [\VDM\Joomla\Componentbuilder\Service\Spreadsheet::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => 'c9c85097d30c8504b9b71cb1cc38e2421a2ae1456e0ab16e3681822587891a69'
		]];
		yield 'utilities' => [\VDM\Joomla\Componentbuilder\Service\Utilities::class, [
			'aliases' => 4,
			'services' => 4,
			'hash' => 'f2c64d3f9c9b595443079bfb0ace84b6f4d5a3eaecc284ce76cf70d98791146d'
		]];
	}
}
