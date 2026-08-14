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
 * Contracts for domain-local Component Builder service-provider catalogs.
 *
 * @since  1.0.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Fieldtype\Service')]
#[CoversNamespace('VDM\Joomla\Componentbuilder\File\Service')]
#[CoversNamespace('VDM\Joomla\Componentbuilder\JoomlaPower\Service')]
#[CoversNamespace('VDM\Joomla\Componentbuilder\Repository\Service')]
#[CoversNamespace('VDM\Joomla\Componentbuilder\Snippet\Service')]
final class DomainProviderCatalogTest extends ServiceProviderTestCase
{
	/**
	 * Verify every domain provider alias, key, factory, and lifecycle.
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
	 * Provide the reviewed domain-local service-provider catalogs.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, array{aliases: int, services: int, hash: string}}>
	 * @since   1.0.0
	 */
	public static function providerContracts(): iterable
	{
		yield 'field type' => [\VDM\Joomla\Componentbuilder\Fieldtype\Service\Fieldtype::class, [
			'aliases' => 8,
			'services' => 7,
			'hash' => '308eac18e4b2b27c2d2faf6b0d22f11eef75d69e49887f35d02208d405864f78'
		]];
		yield 'file' => [\VDM\Joomla\Componentbuilder\File\Service\File::class, [
			'aliases' => 6,
			'services' => 6,
			'hash' => '64de36c07323bab372cfde0b3171b66f77537f3ed74599df0e0e714dc8bced38'
		]];
		yield 'Joomla Power' => [\VDM\Joomla\Componentbuilder\JoomlaPower\Service\JoomlaPower::class, [
			'aliases' => 8,
			'services' => 7,
			'hash' => 'c986096041b6bcf93d99b8039a6890d58f0a944f8fb4baa63d995077816e0b4b'
		]];
		yield 'repository' => [\VDM\Joomla\Componentbuilder\Repository\Service\Repository::class, [
			'aliases' => 9,
			'services' => 8,
			'hash' => '1fbc028bd14e6a1f9e1acc6aa46c859ec022bd7791ab9f02c94c78afba1340a1'
		]];
		yield 'snippet' => [\VDM\Joomla\Componentbuilder\Snippet\Service\Snippet::class, [
			'aliases' => 14,
			'services' => 13,
			'hash' => 'eb903aa382b0c9e17f497359a9ff85208ea0c89b2e2cdeb2f63efb59ed2c38c2'
		]];
	}
}
