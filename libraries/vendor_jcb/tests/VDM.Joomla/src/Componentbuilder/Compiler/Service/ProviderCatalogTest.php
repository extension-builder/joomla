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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Service;


use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Tests\Support\ServiceProviderTestCase;


/**
 * Exact compiler container-catalog contracts without resolving the compiler.
 *
 * @since  1.0.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Service')]
final class ProviderCatalogTest extends ServiceProviderTestCase
{
	/**
	 * Verify every compiler provider alias, key, factory, and lifecycle.
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
	 * Provide the reviewed compiler service-provider catalogs.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, array{aliases: int, services: int, hash: string}}>
	 * @since   1.0.0
	 */
	public static function providerContracts(): iterable
	{
		yield 'admin view' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Adminview::class, [
			'aliases' => 3,
			'services' => 3,
			'hash' => '939f77714d7406ed22878759a98c2443a978c4aa6b840131b329850cb743d82c'
		]];
		yield 'architecture component' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureComponent::class, [
			'aliases' => 15,
			'services' => 15,
			'hash' => 'c22c7a7a65f08bae9b6a0091b8498c1d771fd6d4fdfc19c736c76ea8e7dfc395'
		]];
		yield 'architecture controller' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureController::class, [
			'aliases' => 15,
			'services' => 15,
			'hash' => 'bb400f64d2eb95397c0b47c7040734a33027737e528322eb086c0ec3e9882505'
		]];
		yield 'architecture dashboard' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureDashboard::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => '83cbc21493bb2ebc699aeb3036074b3a773d961316d7e218106566658257d037'
		]];
		yield 'architecture model' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel::class, [
			'aliases' => 20,
			'services' => 20,
			'hash' => 'a79209ba54ed9a8538617aa40463236466c9334bd12e78b2f20367daf50f874f'
		]];
		yield 'architecture module' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModule::class, [
			'aliases' => 30,
			'services' => 30,
			'hash' => '3342cd94bf9791ae4d55937450396b81c8f359f23a62386f0920d3ba097fac09'
		]];
		yield 'architecture plugin' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitecturePlugin::class, [
			'aliases' => 15,
			'services' => 15,
			'hash' => '74ef0d41f9435d40a407239201ab42e9a06a0062048300d55fb3f3254ad007da'
		]];
		yield 'architecture view' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class, [
			'aliases' => 68,
			'services' => 68,
			'hash' => '868ac4902e7a5fcb47ad2911f258a59cc3bf8512ba950001870208088eb7ca74'
		]];
		yield 'builder A-J' => [\VDM\Joomla\Componentbuilder\Compiler\Service\BuilderAJ::class, [
			'aliases' => 55,
			'services' => 55,
			'hash' => '3cae9a36dd37ee1409d2a66a45448980295b7649569737b687097f8fddc2505b'
		]];
		yield 'builder L-Z' => [\VDM\Joomla\Componentbuilder\Compiler\Service\BuilderLZ::class, [
			'aliases' => 57,
			'services' => 57,
			'hash' => '2aeab1cdb922c7f7e9df4ef890b3834ef7554d53ebd4d193013dd8b288f82a4a'
		]];
		yield 'compiler' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Compiler::class, [
			'aliases' => 6,
			'services' => 6,
			'hash' => 'f057ab687374c073bf19ce37bb24d8775294bc8c4c640a63f872f7be36d81f16'
		]];
		yield 'component' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Component::class, [
			'aliases' => 12,
			'services' => 12,
			'hash' => '6444e97cdeac752a2e9ab9dadb225e068de684898098c8aa6768c22abb77d697'
		]];
		yield 'creator' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Creator::class, [
			'aliases' => 36,
			'services' => 36,
			'hash' => 'e466ab8a4c8e2ba42afaec5a750b1cb9f6f38ec00bbeb3516242bcad59b90639'
		]];
		yield 'custom code' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Customcode::class, [
			'aliases' => 8,
			'services' => 8,
			'hash' => '7a58f40d2e5c46a240a18f67a6c2922acddc5cbd791bf6c9793c70e9fafc7013'
		]];
		yield 'custom view' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Customview::class, [
			'aliases' => 20,
			'services' => 20,
			'hash' => 'fc0c05935c931e9cf0da3f285176450eb555034f3eb9e2a9c9681123557549d3'
		]];
		yield 'event' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Event::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => '08d695abb659ee7d09509476583810d680bc0129ac87970077ee7009b4521ea5'
		]];
		yield 'extension' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Extension::class, [
			'aliases' => 18,
			'services' => 18,
			'hash' => 'd3336d2b890090fa098e9f46cc07594e6b001ab0c0e3668bb01e65a4a93bbeca'
		]];
		yield 'field' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Field::class, [
			'aliases' => 21,
			'services' => 21,
			'hash' => '2f199e3288eb8bf85420b800430fab42f2210de7997e81a32959c99ff1fd3cbe'
		]];
		yield 'header' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Header::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => '683859a6068240122f870010c7b88e1aed073535c1303abf8ed225c4b2009b45'
		]];
		yield 'history' => [\VDM\Joomla\Componentbuilder\Compiler\Service\History::class, [
			'aliases' => 5,
			'services' => 5,
			'hash' => '213f70350b9212bb102c2523dadb2e676f1314d84fac32082315e08456cbf8dd'
		]];
		yield 'Joomla Power' => [\VDM\Joomla\Componentbuilder\Compiler\Service\JoomlaPower::class, [
			'aliases' => 6,
			'services' => 6,
			'hash' => '3f44e8f6e9d1871b037195f47a6f62f935704dd23969622597c0877f140e4f6b'
		]];
		yield 'Joomla module' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Joomlamodule::class, [
			'aliases' => 15,
			'services' => 15,
			'hash' => 'df42f19d2e9645e6496cf6b40a2f80e9caa2917591a2d4508975278e7edd55df'
		]];
		yield 'Joomla plugin' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Joomlaplugin::class, [
			'aliases' => 15,
			'services' => 15,
			'hash' => 'e00afb3f23801680635d93adb3b4b8e08b396a7801ac87d215dd9bf9ab015990'
		]];
		yield 'language' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Language::class, [
			'aliases' => 9,
			'services' => 9,
			'hash' => '103540dadb128eebbf37974624e160a80a79cadc6338d550adfcf4fb770ad962'
		]];
		yield 'library' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Library::class, [
			'aliases' => 4,
			'services' => 4,
			'hash' => '8648ac0daf8951876b2247a9855d52c721c92897fcf70059f3d2305d7d79f340'
		]];
		yield 'model' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Model::class, [
			'aliases' => 44,
			'services' => 44,
			'hash' => '9c29c01f0c0e982987eb9088e17b812908a05dac595f860893532c8d9e244f38'
		]];
		yield 'package' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Package::class, [
			'aliases' => 3,
			'services' => 3,
			'hash' => '207fc42837f42538d9bfa644d127817927c09c1307e9e039e3284abff24e5e21'
		]];
		yield 'placeholder' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Placeholder::class, [
			'aliases' => 2,
			'services' => 2,
			'hash' => '38204ff05b73a58889d08ad9e2c4e92b3de598ee62dc72f4fc1a476198a03b6c'
		]];
		yield 'power' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Power::class, [
			'aliases' => 15,
			'services' => 15,
			'hash' => 'a3ff199f078082d19a126fc55e785be23e43be15be52dbf599021a0acbb7541f'
		]];
		yield 'template layout' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Templatelayout::class, [
			'aliases' => 2,
			'services' => 2,
			'hash' => '32d653b3ca3034514648a89f708487a131d350da71d8d6b56340c2047d2ea77a'
		]];
		yield 'utilities' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Utilities::class, [
			'aliases' => 18,
			'services' => 18,
			'hash' => 'af271a7375ea6cce6acf0c5f85a0b55050f169386fda95cfbdad57933bd6636c'
		]];
	}
}
