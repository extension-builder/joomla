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

namespace VDM\Joomla\Tests\Componentbuilder\Package\Service;


use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Tests\Support\ServiceProviderTestCase;


/**
 * Exact Package capability-catalog contracts without resolving remote handlers.
 *
 * @since  1.0.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Package\Service')]
final class ProviderCatalogTest extends ServiceProviderTestCase
{
	/**
	 * Verify every Package provider key, factory, alias, and lifecycle.
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
	 * Provide the reviewed Package service-provider catalogs.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, array{aliases: int, services: int, hash: string}}>
	 * @since   1.0.0
	 */
	public static function providerContracts(): iterable
	{
		yield 'admin view get' => [\VDM\Joomla\Componentbuilder\Package\Service\AdminViewGet::class, [
			'aliases' => 0,
			'services' => 15,
			'hash' => '7da005d79500918206f9f5838e653c55d02a4100c4afb68a3aae38d93bc262ad'
		]];
		yield 'admin view set' => [\VDM\Joomla\Componentbuilder\Package\Service\AdminViewSet::class, [
			'aliases' => 0,
			'services' => 12,
			'hash' => '2de895431a0e45aa8ca83c139302cf23765c27e4fedcddf2232ea612bf7dece2'
		]];
		yield 'component get' => [\VDM\Joomla\Componentbuilder\Package\Service\ComponentGet::class, [
			'aliases' => 0,
			'services' => 39,
			'hash' => '3cbf5d411c21ab36496666b33f3e564f05a98ee85f074c6d513f388a6f6e1d8e'
		]];
		yield 'component set' => [\VDM\Joomla\Componentbuilder\Package\Service\ComponentSet::class, [
			'aliases' => 0,
			'services' => 28,
			'hash' => '5dc7e89cf337ad0218faae68ee035d5b8f3f24979a38759c2d56d181b0237882'
		]];
		yield 'custom admin view get' => [\VDM\Joomla\Componentbuilder\Package\Service\CustomAdminViewGet::class, [
			'aliases' => 0,
			'services' => 3,
			'hash' => '95603e2a2dc67991525bd45e45a08746cf70b5a9957bb6df6f001a8084264645'
		]];
		yield 'custom admin view set' => [\VDM\Joomla\Componentbuilder\Package\Service\CustomAdminViewSet::class, [
			'aliases' => 0,
			'services' => 4,
			'hash' => '6d744e7b782240da66f231fb4f8186dfa744f17af08d206b5538c1d28d2ca810'
		]];
		yield 'custom code get' => [\VDM\Joomla\Componentbuilder\Package\Service\CustomCodeGet::class, [
			'aliases' => 0,
			'services' => 3,
			'hash' => 'cc92abb783d5a5e47bf94f7dca5d7aca8806b5403dc1297a97d613811e650040'
		]];
		yield 'custom code set' => [\VDM\Joomla\Componentbuilder\Package\Service\CustomCodeSet::class, [
			'aliases' => 0,
			'services' => 4,
			'hash' => 'f0ee5d8d0044d77460b7e5ad994371cd4cc1f6518df978f073e53a629521fd07'
		]];
		yield 'dependencies get' => [\VDM\Joomla\Componentbuilder\Package\Service\DependenciesGet::class, [
			'aliases' => 0,
			'services' => 18,
			'hash' => 'a7c25369bcd0fb1a8210f15625c3466fff76db59390f148723c9ec5e5634bde6'
		]];
		yield 'dependencies set' => [\VDM\Joomla\Componentbuilder\Package\Service\DependenciesSet::class, [
			'aliases' => 0,
			'services' => 14,
			'hash' => 'fa7012388602075cd11ceb052e8edecfce83cc17327470ab81d910f37a171d01'
		]];
		yield 'dynamic get' => [\VDM\Joomla\Componentbuilder\Package\Service\DynamicGet::class, [
			'aliases' => 0,
			'services' => 3,
			'hash' => 'fa95b05a658b1d1d41fa46cf2ef2ce7d3ac7b955d4172de1abd2a93e077591e9'
		]];
		yield 'dynamic set' => [\VDM\Joomla\Componentbuilder\Package\Service\DynamicSet::class, [
			'aliases' => 0,
			'services' => 4,
			'hash' => '82991983eab1c6e861bcacb23c5323602e4fff11b7161aebe33b5c1158c22fd9'
		]];
		yield 'field get' => [\VDM\Joomla\Componentbuilder\Package\Service\FieldGet::class, [
			'aliases' => 0,
			'services' => 6,
			'hash' => '3abfc4b206abedc18b8fc0982365a59a339ca6a6d4a616d9f921ac7fe6101fb1'
		]];
		yield 'field set' => [\VDM\Joomla\Componentbuilder\Package\Service\FieldSet::class, [
			'aliases' => 0,
			'services' => 8,
			'hash' => '790682fabab504861920b5e286679dea30b94f16d59227580f09838175ff436b'
		]];
		yield 'Joomla module get' => [\VDM\Joomla\Componentbuilder\Package\Service\JoomlaModuleGet::class, [
			'aliases' => 0,
			'services' => 9,
			'hash' => 'c2addd6e538e3e4e449d5e1c6a152be11d2facc7572bff59b108418b158e1f90'
		]];
		yield 'Joomla module set' => [\VDM\Joomla\Componentbuilder\Package\Service\JoomlaModuleSet::class, [
			'aliases' => 0,
			'services' => 8,
			'hash' => 'b7951be9ec9102a1b7c401b33116237efeceee5b7dee2ef58525e61e0c2c3e08'
		]];
		yield 'Joomla plugin get' => [\VDM\Joomla\Componentbuilder\Package\Service\JoomlaPluginGet::class, [
			'aliases' => 0,
			'services' => 12,
			'hash' => '21d606216667ab5227780672e7ef10fac885be1a34b1e859b4b589423ab4a717'
		]];
		yield 'Joomla plugin set' => [\VDM\Joomla\Componentbuilder\Package\Service\JoomlaPluginSet::class, [
			'aliases' => 0,
			'services' => 10,
			'hash' => '421add620420a309ec9a62c83a33fada75103f615720496f174c873e4aa4ba26'
		]];
		yield 'layout get' => [\VDM\Joomla\Componentbuilder\Package\Service\LayoutGet::class, [
			'aliases' => 0,
			'services' => 3,
			'hash' => '1b121492ad978630d14609d8f3667d5dead0bc66eaef088c4bde1fe2272ae563'
		]];
		yield 'layout set' => [\VDM\Joomla\Componentbuilder\Package\Service\LayoutSet::class, [
			'aliases' => 0,
			'services' => 4,
			'hash' => 'a966f2616ead18d05091cab5a1fa10cdf1cb9ec8657131f646ab536527063c23'
		]];
		yield 'library get' => [\VDM\Joomla\Componentbuilder\Package\Service\LibraryGet::class, [
			'aliases' => 0,
			'services' => 9,
			'hash' => 'ad12020780e1c1b98ba0b11d9bed3b2066ef416107c5f9cda6ee33f5fadd480f'
		]];
		yield 'library set' => [\VDM\Joomla\Componentbuilder\Package\Service\LibrarySet::class, [
			'aliases' => 0,
			'services' => 8,
			'hash' => '4723b94dae40c82fc36688dce7a2d71e0eeb883488a06ca398cbd3b51e9319f6'
		]];
		yield 'package' => [\VDM\Joomla\Componentbuilder\Package\Service\Package::class, [
			'aliases' => 2,
			'services' => 1,
			'hash' => '8feeacc67f26b620cc84a65290b7be88101b87507c57da910b7538a310c0429b'
		]];
		yield 'power' => [\VDM\Joomla\Componentbuilder\Package\Service\Power::class, [
			'aliases' => 6,
			'services' => 5,
			'hash' => 'a547881e15c93df439cc3b38ac4ba96c919d009abe34d8f6964318b2168cd707'
		]];
		yield 'site view get' => [\VDM\Joomla\Componentbuilder\Package\Service\SiteViewGet::class, [
			'aliases' => 0,
			'services' => 3,
			'hash' => '3442e8ca95b2d588a7861c200cf7043e120bbbc8abc34e517e1d563cf820b6e3'
		]];
		yield 'site view set' => [\VDM\Joomla\Componentbuilder\Package\Service\SiteViewSet::class, [
			'aliases' => 0,
			'services' => 4,
			'hash' => 'e92a84d88eced377206cdf1185007480f1a66f264925394339be8099120b7888'
		]];
		yield 'template get' => [\VDM\Joomla\Componentbuilder\Package\Service\TemplateGet::class, [
			'aliases' => 0,
			'services' => 3,
			'hash' => 'a1368ada3221f7a7095ec77a5edc9fedccd1480775ef3d1abcf5656dfff29d87'
		]];
		yield 'template set' => [\VDM\Joomla\Componentbuilder\Package\Service\TemplateSet::class, [
			'aliases' => 0,
			'services' => 4,
			'hash' => 'dd9148690a0c90be48a3e905b62765e5d36aee88ae3ec85d62988d3dc4a89c4d'
		]];
	}
}
