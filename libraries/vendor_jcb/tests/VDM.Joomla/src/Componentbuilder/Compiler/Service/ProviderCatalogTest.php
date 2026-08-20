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
			'aliases' => 43,
			'services' => 43,
			'hash' => '2bfef6f2dc0b750283c1fcf902dc475ce6d84617022cfddcdfb1334f09932aae'
		]];
		yield 'architecture controller' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureController::class, [
			'aliases' => 25,
			'services' => 25,
			'hash' => '64e1cae0c3a6696f1402e7b1e1461336fbc8f3845eaed2be346035a913f9fa41'
		]];
		yield 'architecture dashboard' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureDashboard::class, [
			'aliases' => 7,
			'services' => 7,
			'hash' => '9441e4b653baea61fae9536896c4121bb56b9fde3bbc259ce20b57bf08cbba03'
		]];
		yield 'architecture model' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel::class, [
			'aliases' => 58,
			'services' => 58,
			'hash' => 'f06e7065f38599beb1ae19c971c8572aee258919b046cd8fc6106205acb00764'
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
			'aliases' => 155,
			'services' => 155,
			'hash' => 'b446d74f6ae5f9e36ae9251999ba744f92aee414ac36f6f982becf117a188f57'
		]];
		yield 'builder A-J' => [\VDM\Joomla\Componentbuilder\Compiler\Service\BuilderAJ::class, [
			'aliases' => 57,
			'services' => 57,
			'hash' => 'af63e1152ba93b12852abe036d1ac456373368604f6c26f402e3ec4d61a2ef5c'
		]];
		yield 'builder L-Z' => [\VDM\Joomla\Componentbuilder\Compiler\Service\BuilderLZ::class, [
			'aliases' => 63,
			'services' => 63,
			'hash' => 'd32fb1fd784d651b11e3ed6761c7b2922bcaddd5c2f64c7d7748d6be59159f18'
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
			'aliases' => 27,
			'services' => 27,
			'hash' => 'c719835e6b5f162a75dea04e37c5512163d1caa16d351e3721555f0677e2d045'
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
			'aliases' => 14,
			'services' => 14,
			'hash' => 'b2feacc414e7ee3160232a73965bcdcdba3052c4e52cae7dcd58f676abfd5cb7'
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
			'aliases' => 3,
			'services' => 3,
			'hash' => 'e4f796113e922ad2ab23c65b063f4c2cbd726557341004fdb40b96d6ec725d89'
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
