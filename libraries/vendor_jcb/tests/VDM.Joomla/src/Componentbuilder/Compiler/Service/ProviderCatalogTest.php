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
		// Joomla 6 native completion adds target identities while retaining every
		// baseline alias, factory and protection flag. Controller input, model
		// saves, WAM loaders, library includes, privacy events, native DKIM fields
		// and footer diagnostics have focused behavior contracts in addition to
		// these exact registration fingerprints.
		yield 'admin view' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Adminview::class, [
			'aliases' => 3,
			'services' => 3,
			'hash' => '939f77714d7406ed22878759a98c2443a978c4aa6b840131b329850cb743d82c'
		]];
		yield 'architecture api' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureApi::class, [
			'aliases' => 18,
			'services' => 18,
			'hash' => '06f61f11303eeac098bc93f8d39a54260f5dd42274deda1d4fb7953a6cdb4e31'
		]];
		yield 'architecture component' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureComponent::class, [
			'aliases' => 44,
			'services' => 44,
			'hash' => 'fb9cb8897caa141ca066461a603c4cf10a93f8fbe87c06bd711b900288446395'
		]];
		yield 'architecture controller' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureController::class, [
			'aliases' => 27,
			'services' => 27,
			'hash' => '7c5987e6a33eca26e4af506f3581e724c6d54be40717a6e7ac77f47198bdef91'
		]];
		yield 'architecture dashboard' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureDashboard::class, [
			'aliases' => 7,
			'services' => 7,
			'hash' => '9441e4b653baea61fae9536896c4121bb56b9fde3bbc259ce20b57bf08cbba03'
		]];
		yield 'architecture model' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel::class, [
			'aliases' => 64,
			'services' => 65,
			'hash' => 'dc1a1b36d9b9ae0e8bba0089c89dd881835e47216d8372a92a7a5b51fa2bfc4c'
		]];
		yield 'architecture module' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModule::class, [
			'aliases' => 30,
			'services' => 30,
			'hash' => '3342cd94bf9791ae4d55937450396b81c8f359f23a62386f0920d3ba097fac09'
		]];
		yield 'architecture plugin' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitecturePlugin::class, [
			'aliases' => 16,
			'services' => 16,
			'hash' => '6e2d5a6eebe70d81c8ad277b91c12a92e1b406a823849e4b0936d65332380e0c'
		]];
		yield 'architecture view' => [\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class, [
			'aliases' => 189,
			'services' => 193,
			'hash' => '4316d027f5f6981245f7fbf4b838ee542f3d8d0436bc5613d7e408d1ca5a3f35'
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
			'aliases' => 43,
			'services' => 46,
			'hash' => '8ee05e12e1000f85031b63a9d422d59d93b86f2f2fa88586d7b8975894f7eb7f'
		]];
		yield 'custom code' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Customcode::class, [
			'aliases' => 8,
			'services' => 8,
			'hash' => '7a58f40d2e5c46a240a18f67a6c2922acddc5cbd791bf6c9793c70e9fafc7013'
		]];
		yield 'custom view' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Customview::class, [
			'aliases' => 23,
			'services' => 26,
			'hash' => '1d05c9622709c57c71dd42a32da60fab400425ff46a4d3c2fd3981ad2bdee8ef'
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
			'aliases' => 28,
			'services' => 29,
			'hash' => '9a388636249d2c3341edc240a9551f579ae57c2dc91420adb872989f5ec02a98'
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
			'aliases' => 8,
			'services' => 8,
			'hash' => '9a46c28a1e94465192b8d3843c155b24410c9c0ceb830f0a1bea8701ac0cbaef'
		]];
		yield 'model' => [\VDM\Joomla\Componentbuilder\Compiler\Service\Model::class, [
			'aliases' => 48,
			'services' => 46,
			'hash' => 'c7c541c2b7333ebc352b957e04eccb1764e785a148b1ecdcfee2701cc038022f'
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
			'aliases' => 16,
			'services' => 16,
			'hash' => '0cf5dd7c3d43ec0988a5e14cf887c121b832888ee934dfa5f3f4d8deb1cbc791'
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
