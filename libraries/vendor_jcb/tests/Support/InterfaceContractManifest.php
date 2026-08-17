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

namespace VDM\Tests\Support;


/**
 * Reviewed interface declarations and canonical implementation maps.
 *
 * This manifest is deliberately static. Any signature or conformance change
 * must be inspected before its fingerprint is updated.
 *
 * @since  1.0.0
 */
final class InterfaceContractManifest
{
	/**
	 * Get every reviewed interface contract.
	 *
	 * @return  array<class-string, array{path: string, implementations: int, concrete: int, implementation_hash: string, signature_hash: string}>
	 * @since   1.0.0
	 */
	public static function all(): array
	{
		return [
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\AdminView\\AddModalToolBarInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminView/AddModalToolBarInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '6366cc1502d8a5a6b1e3ef167ecfe4f495befa3946cf655f113ea2984ca25b2d',
			'signature_hash' => '1d0c3d3aa4efe0a01c2deff1b3d698c52e9a48656b35d8f18b8f2c3fb2b9716d'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\AdminView\\AddToolBarInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminView/AddToolBarInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'cec1bfbee8f350e636b18cd88c5c1e322fed0d2d85f611c7b61119c326f6a256',
			'signature_hash' => 'b8b273b8817597f3457c0ba21cd8901d873f558c531fa7a9cf6e5508ed66a9d8'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\AdminViews\\AddToolBarInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/AddToolBarInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '3a0a0a5b80c4cc7bbe12507b49c295c4d47557a0e23ae1e630d3c36f96fb8a5e',
			'signature_hash' => '1d0c3d3aa4efe0a01c2deff1b3d698c52e9a48656b35d8f18b8f2c3fb2b9716d'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\AdminViews\\DisplayMethodInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/DisplayMethodInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => 'eb28ca382f354578e9b14ae35e7641e0a63fbddbf81701cb8653a6caafb11a9b',
			'signature_hash' => '50bb884ac24f8d42ea4cf6eb416779b176a214863d647105b70e367b5b05a29f'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\AdminViews\\ListHeadInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/ListHeadInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => 'b34d9dc9fb950e9fb68f71a09a11fa306f2b82d5889efc2febcff205513dd088',
			'signature_hash' => 'e91441e3d05b795f6fcfa445d824058473c4aafe129bc800616ad3cd83ede517'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\AdminViews\\ViewBodyInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/ViewBodyInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '1592c2d9cd0cedb211f250b1ea5a904d0dce94481fd57d67d12457628d5c7e2d',
			'signature_hash' => '8a9f89a192ce427c1bcf1a1bb71d17653caa8ba165accaa74dffd08a48cdcb8b'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\ComHelperClass\\CreateUserInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/ComHelperClass/CreateUserInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'a74a8c6eb4e341d7a89a8eff232802b218dbe912542393f4aa2d1e30cfdfa00b',
			'signature_hash' => '3e87c9aaba86e07ad1451a818121751705b41c0d21eeb61eda6afa7ac1097389'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\ComHelperClass\\ExcelMethodsInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/ComHelperClass/ExcelMethodsInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '90fe0368faa63872c53853a362d52ee41e7554d89321dc58a89c4551d55e7b1b',
			'signature_hash' => 'e4526e7d5b72bc0eb41bbb0b0b6befadff35d00c4d0895ba17a603877c7ee88a'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Controller\\AllowAddInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Controller/AllowAddInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '3eb4bb59f25ac8cebe1c9afcf38a5feaa837ba24270cc5784ebc1fae99706714',
			'signature_hash' => '3f61cf0f7a6780bb6ed01def0615d2f9d6eb6721fa4f67c10cc33d2e7714d4a2'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Controller\\AllowEditInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Controller/AllowEditInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'ef0d77808f061e29afd2becfa99ce1d450f7c6e328d811355f276cd2d24df117',
			'signature_hash' => 'e91441e3d05b795f6fcfa445d824058473c4aafe129bc800616ad3cd83ede517'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Controller\\AllowEditViewsInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Controller/AllowEditViewsInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '99003a43e8ab87485edc7db90f3229f09016fb09fb30a7d24a36fda94c32ffd2',
			'signature_hash' => '550212dddd225b283f10ffae4b0a7a03a30ac92325bcf14bb12d61361547e8ee'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\CustomAdmin\\AddToolBarInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/CustomAdmin/AddToolBarInterface.php',
			'implementations' => 8,
			'concrete' => 8,
			'implementation_hash' => '47c07dd2c5d852633eabb61da9b59ec73bb1981d6617c00122d242d2c7ef0122',
			'signature_hash' => '1d0c3d3aa4efe0a01c2deff1b3d698c52e9a48656b35d8f18b8f2c3fb2b9716d'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\CustomView\\DisplayMethodInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/CustomView/DisplayMethodInterface.php',
			'implementations' => 3,
			'concrete' => 3,
			'implementation_hash' => '0ae5cf031d2afdfcf6dbd4ba369039b00ab78a109aeb926a1d10de78a5ea39e5',
			'signature_hash' => '40572d240d841a80e907a386ff0be2cb0734fc7895eb1a8d8fd8c135d4b72db5'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Dashboard\\ViewInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Dashboard/ViewInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '094bdd739552998b80d6a3896229a05636dd3777acc87301358066a39c099fcc',
			'signature_hash' => 'e4526e7d5b72bc0eb41bbb0b0b6befadff35d00c4d0895ba17a603877c7ee88a'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Menu\\CustomViewInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Menu/CustomViewInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '1debb5c11db64515624cd425c90756dd600a8a6b28ff1f87f48b28b4c254d24b',
			'signature_hash' => 'e634946193b7824d22820d442104ddd7c52763347937c99b3bf95359b267abd4'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Model\\AllowEditInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/AllowEditInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '37862342bf7af389346d349ea67a1763c08d9347216459f6cf32a139aa433952',
			'signature_hash' => 'e91441e3d05b795f6fcfa445d824058473c4aafe129bc800616ad3cd83ede517'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Model\\CanDeleteInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/CanDeleteInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '64a00709ea4115451d3236bf4924fae5f16a5da17c0241789e720a14481dffba',
			'signature_hash' => '3f61cf0f7a6780bb6ed01def0615d2f9d6eb6721fa4f67c10cc33d2e7714d4a2'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Model\\CanEditStateInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/CanEditStateInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '5759e88d62e9a52e4538c3f663419af9e1dd0bffd7db79f483cc9541814172bc',
			'signature_hash' => '3f61cf0f7a6780bb6ed01def0615d2f9d6eb6721fa4f67c10cc33d2e7714d4a2'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Model\\CheckInNowInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/CheckInNowInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'e5d38a7f85512b996f027c1244c96344b97d0c8f1c206021c26228b224ffd898',
			'signature_hash' => '36b8ca24faf331bc949bc165d23830ff6d41bfaa2e1274d1bc84b8f3e72fcc11'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Module\\DispatcherInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/DispatcherInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'cd0fb39f845259c0a34a2e060d33150339d81b5d8e7e9946e0fc2e96cbef0aa5',
			'signature_hash' => '77ad54734163ed1142ba4e60e37678689944d8bd40cc8bded48dc883b8044717'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Module\\HelperInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/HelperInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'a91066ad78aefe236762d20b98bd8d3b675eec08654b500b62b482691f27ac7a',
			'signature_hash' => 'd502d5242c32945218753f4b5e360ca038d87d51e52eae61a6a64d317951f51e'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Module\\LibraryInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/LibraryInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '69968b2804fe51b9131c1a9b37adadf9c56d512a0ddc19bb34e30051f931c8d5',
			'signature_hash' => '77ad54734163ed1142ba4e60e37678689944d8bd40cc8bded48dc883b8044717'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Module\\ProviderInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/ProviderInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '2315f41539e1bcb893f42f6b30bcc833e0a158091e253468d68df7e35fc010eb',
			'signature_hash' => '77ad54734163ed1142ba4e60e37678689944d8bd40cc8bded48dc883b8044717'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Module\\TemplateInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/TemplateInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '9a299ef6dad308d56585d232387acc8c579fb18f0d5a776f4d7281c6fa1dd25d',
			'signature_hash' => 'df0a9229c24cd8fdc75c980595974db254af409f06a258841840c838f0048964'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Plugin\\ExtensionInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Plugin/ExtensionInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '3ce26ac75a6ddc1253973e9ccd21cde6e9830a727483d283a063a184dda2bcec',
			'signature_hash' => '694422497d563115adfe11629f178267a9e1cc8ab7874584792c27cb63444fa4'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\Plugin\\ProviderInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Plugin/ProviderInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '0878d7d2dba29f5fb906155321c3920df49986227b8a1e86eee538ce14141890',
			'signature_hash' => '694422497d563115adfe11629f178267a9e1cc8ab7874584792c27cb63444fa4'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Architecture\\SiteView\\AddToolBarInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/SiteView/AddToolBarInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '46cf3e681b936c25645d8cd10ded1db02b61d2d27119fda5bd7d1034459496ce',
			'signature_hash' => '1d0c3d3aa4efe0a01c2deff1b3d698c52e9a48656b35d8f18b8f2c3fb2b9716d'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Component\\PlaceholderInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Component/PlaceholderInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '0449a904debf002e908a199a61e24b8a70f7268b36232641e101ddabbc505e3a',
			'signature_hash' => 'd6f6e248dcdd6b3dbdc8e72c433764742e313a86907b7804fb629fa725b7050f'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Component\\SettingsInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Component/SettingsInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'd1ebf2db93b00803cb26015f64112cb5eda42065f7cdd6e1d284810a2fcd157c',
			'signature_hash' => 'f81587348994fafbadf870a642d9afd1774d4a162add9dd91b6f7146fc849cea'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Creator\\Fielddynamicinterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Creator/Fielddynamicinterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'cd989faea4a91660a6195a8e4187a8ac49019f930ca26132870c6d14045dc6c5',
			'signature_hash' => 'bd4fa654873cb4c06e3a12a182fee5f41f103dde8439a81d84a872d55789769d'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Creator\\Fieldsetinterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Creator/Fieldsetinterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '8b79df6c51514c49386d2da6e9457b658445e6db1f00fa32fb524bad1713a1a0',
			'signature_hash' => 'bdbb33cb4ddc33dbd2e5ca0e38003299077aee144164c03f7eec516f4660dcbf'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Creator\\Fieldtypeinterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Creator/Fieldtypeinterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => 'ed79cc217bcde0205fb06968c4ada40c55e4c71ac25b34c04a2c18a2b8df657c',
			'signature_hash' => '51ac512fe885d03cc5be49df21a064f045807c968ed13a09c918fc462f71e57f'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\CustomcodeInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/CustomcodeInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '20cd9609ae90d1862f8fb3304a06a13bcb81961ce8de7ecd7aa5dff9f2f4be69',
			'signature_hash' => '2dbb8c624ae8634c9ca4823000e958855842ad3024a2048be0d7ec7ab38350f4'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Customcode\\DispenserInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/DispenserInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '6873a68ed8dd40c0877dd1ba77fa2da97b314ab4de4a947bca1bd44ef9a15cc4',
			'signature_hash' => '4de84f80bd1c6c974463b3096398d2ff19a9efd721be9031246aedc7023ee594'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Customcode\\ExternalInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/ExternalInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '152274b4a2227d66e0eca984d4714fdc9472eeb5a4a19cd3408f04c8f8e00af1',
			'signature_hash' => 'ecf246d07cb0f7275b694a51583e79888e0d8baa29c07b23dbf99899fcfe9ac4'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Customcode\\ExtractorInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/ExtractorInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'd06df58c785dca1eaa0ac3bb7c8d67669db04346f20b2032c3c5e186eb3527d1',
			'signature_hash' => '84d62aee8ab2a1df6b60e9507840c4a9b0a2052c0ef0b920e4989cd16ad6fae8'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Customcode\\GuiInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/GuiInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '597dd9e8f884406e39cf2ef24e84d87c4378ae2d01386482cccafc3c78e4ef9c',
			'signature_hash' => 'b5dd1bd4b41757fe249ec7606efb2403dba41dd0780d450c9457eaf1e974f561'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Customcode\\LockBaseInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/LockBaseInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '32bf23d665a0711a9a52213500e0a13b8ab3107222762ce95dd491e372100e1d',
			'signature_hash' => '24d8a1d93740e4f1f3ab7eb2bd7f3c21b64217fa419033ba9a7e8ec4e9ac5525'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\EventInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/EventInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '97ba7ba3fd482520082656f813f90cbd347573d57035c3e3dae8fd471ab3c127',
			'signature_hash' => 'cac62e0b4f5b73c4b25443d37986346f094347317ab94640832ed5b64703c891'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\ExtensionFilesUpdateInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/ExtensionFilesUpdateInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'e2479bc9aaa776ea7536d6b2b688779aacca913a6ff5e8e9efaeec1bc49a1284',
			'signature_hash' => '8f579c1347dfd1493959fece1df2078b8a0292f1176c9ef3659006995a0a97ce'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Extension\\InstallInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Extension/InstallInterface.php',
			'implementations' => 0,
			'concrete' => 0,
			'implementation_hash' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
			'signature_hash' => 'bf857db72dc85fb89b6a3f792144f613198b5c3e97dfc02f6d00bc682d7333c1'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Field\\CoreFieldInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Field/CoreFieldInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'd980e1f49b10b4b0371ff41f4a25286776c861fd18cdc6509b99c39af58ef353',
			'signature_hash' => '34d9294161bc9a8ec17ba2631b31e45c3ef149cbe46a23dc55f19af36cbee1cd'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Field\\CoreRuleInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Field/CoreRuleInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '9a4e613b1ad70c32941e852600997edbadc751623a2d8af32fbc2ccf3bb9879b',
			'signature_hash' => '34d9294161bc9a8ec17ba2631b31e45c3ef149cbe46a23dc55f19af36cbee1cd'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Field\\InputButtonInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Field/InputButtonInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'cfccdd4955ec8df00a5d697aedc9f1883e0b7fcc7582c992fbe76b5b4457ff98',
			'signature_hash' => '09a1d8d0850fc38fc2344560e863397c3e9b62c5fd36db4f1f7fbbf2280d4279'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\GetScriptInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/GetScriptInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'ebceef3896370de95a3d44f138ed5f73820b029c78ff6fc3675474cf6135962b',
			'signature_hash' => '9d28a32dce86b2d813606205bf49306d8891e115ce5a7cae5691cf4d5098b3c3'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\HeaderInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/HeaderInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '55e3d632b98941d70dc319199c8bfe9ab928bffc9d6c469f8e38bf96a59fd613',
			'signature_hash' => '6dc41c2c33ea80b0ddb731b007d80b54142af6a92609ac7265781e7d670d4af5'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\HistoryInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/HistoryInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'a809e843db993ba8e8296d3087d4b69cbcea27deacb6a35b9896083895a542b3',
			'signature_hash' => 'b819e72dca6ffd68ed420b2f1f603b7cfc075ec731a9de45c4415ec08f779839'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\LanguageInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/LanguageInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '25af0a7288310fab2c2e957215f9c275b6cf4b50d95e7fec584a99cba66fb7fd',
			'signature_hash' => 'a8888ef49dc96c7366650bf4266ef8859593378bcdcfbd5984e855686c79056e'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Language\\ExtractorInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Language/ExtractorInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '5283bf609bc7c8a007b465a630c19c466ef8f98d12bcf26142a9d0da9bb06096',
			'signature_hash' => '0fc93e1634106268d6c4f8573b31a621559b6e87ae089a4438b86ed324e1b2e2'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Model\\CustomtabsInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Model/CustomtabsInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '31a754ef383cfa99d09d9e57c993f83108640e221e1a60004d0d36f7cb9377e8',
			'signature_hash' => '21800650ebbbf777237326a28f51347162bd0244aa2b9462c3c3eeb383d18ce4'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\ModuleDataInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/ModuleDataInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '8e9dc5bca031b0045d1dc3b7d2708fc2731c21ac58b5d427839ee994c0bd62a4',
			'signature_hash' => '21ebc1ab7babefc14e5dd5465f52ebb671561c0c3546c4218926cd364d116f79'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\MoveFieldsRulesInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/MoveFieldsRulesInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '310c0b18e0b1ed1e6ae27c33a6a1733d755a84e674e0de86c5d8c1c1048535d1',
			'signature_hash' => '00e48a95b90fcca469397567a95863994d7ce93dbb54e43951a096e125631376'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\PlaceholderInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/PlaceholderInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '6d6f7c741ccc2467b3bd5f10150ccab346db26f76cce3c21fb4e8221e987fab5',
			'signature_hash' => 'e610b2c9e458b780bf974307ec2a2e9bee14c69eaf585a6736aebc777cff68a3'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\PluginDataInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/PluginDataInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => '4a1bd5c075afa682ab5f8810931ccdb2599cf85af3156c158c6967e2e5dc5f9f',
			'signature_hash' => 'e5e7da56e6bf609168b5b976417e7a75f3e7d52dd1a0d6e514078893373a01ce'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\PowerInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/PowerInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '8a575e6a17fe05d66befcf4fa5006ea9f7b5a8ea30cdecbd63b3ed8d1a2c33e4',
			'signature_hash' => '81cc672f30e8d13088fcfaf4dc63d4d91964d92551b3e1bf8f11841d4e7c1a55'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Power\\ExtractorInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Power/ExtractorInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => 'ef5daa22073811bcdb4676b4e63a7eb322891c65cdc3075b1f1fb598bc012bec',
			'signature_hash' => '4623e518fc912c5124c4d35ea276d21e7934d15a7254b5ab048a3d49afa4fccf'
		],
		'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\Power\\InjectorInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Power/InjectorInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '49cc369aed6b30a253978319eb0644bfbaf34d770a75897b45fd9cf018b44581',
			'signature_hash' => 'd2e46c27f58d0846cc2cb84de25597370b031ad3b30b76fda7b0eb63f96ab3c8'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\Architecture\\Module\\MainXMLInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/Architecture/Module/MainXMLInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'ec84e7b0f2460a307d123e771846c16c9b2821c993f81ae6429571ece7346e30',
			'signature_hash' => '77ad54734163ed1142ba4e60e37678689944d8bd40cc8bded48dc883b8044717'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\Architecture\\Plugin\\MainXMLInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/Architecture/Plugin/MainXMLInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'ec6124598b8642d28bd2987839c74d8af3926426b35bc0399d5b0c4725e70390',
			'signature_hash' => '694422497d563115adfe11629f178267a9e1cc8ab7874584792c27cb63444fa4'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\Cryptinterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/Cryptinterface.php',
			'implementations' => 3,
			'concrete' => 3,
			'implementation_hash' => 'd0e2d70f6789467311a034cd7194d401f20e73cc539450f1ba16127d183b96d6',
			'signature_hash' => 'c7e6137612a36dd29e3b1e38954c2a741a3a0239fba0069ef6c0a436dfe1317b'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\File\\DefinitionInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/File/DefinitionInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'e1b40c90aef3f505dd9773dae6122c8180c0af8e07339c9e46f80d7b710ff639',
			'signature_hash' => 'c675f53ccc9e7019886143e78cf116bf619082a689ce9060afe1e1b7c1909cc1'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\File\\TypeDefinitionInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/File/TypeDefinitionInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '1d76de46532b6822d6cc189807c3fcbf9a304c5a195aeadec35cd2e20ba1b950',
			'signature_hash' => '56713409a246dab36436c299cf8d21207d181d2231c4d9a394e79421f3937561'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\Module\\InfusionInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/Module/InfusionInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'a5c0d8928ffa52777ddfb78eff99cade8126ac21ae3d45ad3b3f0e897c09b1f2',
			'signature_hash' => '9878bdfab70b33730235cf017512098f818c2c354dfdfae6029a8d0c55a57b85'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\Module\\StructureInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/Module/StructureInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'bc52f97d0c1b2da35bd87dc3aeb4479b4e1da8d5591dba4fbbc998fb9e931c50',
			'signature_hash' => '3e0692f659836ef74bd3c2f5638c59e4f818bbbafc150f14d57530f25513df69'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\Plugin\\InfusionInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/Plugin/InfusionInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'ef13f3e3515eacca1f20a6c6dd8908334dc4b4a383aa2f53ff0fefc2da73fe3e',
			'signature_hash' => '9878bdfab70b33730235cf017512098f818c2c354dfdfae6029a8d0c55a57b85'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\Plugin\\StructureInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/Plugin/StructureInterface.php',
			'implementations' => 4,
			'concrete' => 4,
			'implementation_hash' => 'f8707b7981dbc3ffb7b96bc61f65bf9ed09bdd6cc8d2d1fd2dcf90ad92bfb40e',
			'signature_hash' => '3e0692f659836ef74bd3c2f5638c59e4f818bbbafc150f14d57530f25513df69'
		],
		'VDM\\Joomla\\Componentbuilder\\Interfaces\\Serverinterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Interfaces/Serverinterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '3859f96c68ffabd2a82f552a0ecc860eb7d353bf3b26763005a0086a55a13937',
			'signature_hash' => '9fb1fcca94e85f5eac51859b6b6feff0393ca14db2d9681d50f5a5304dce517d'
		],
		'VDM\\Joomla\\Componentbuilder\\Power\\Interfaces\\TableInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Power/Interfaces/TableInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '722211fb3d5f436689acc5cf85feb87c514c6339a8b93ac8115b3569c8f60c26',
			'signature_hash' => 'd3f67ec0a474a58991302fede1393064204d1dacde83f120502f2d383bcf7740'
		],
		'VDM\\Joomla\\Componentbuilder\\Search\\Interfaces\\FindInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Search/Interfaces/FindInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '508338117003561f2ded8a3185c9065683622ce643ca5c5546a98e0ccfa1b31a',
			'signature_hash' => '532a609ff210dfdf0f3eb8655d0ac07776dc26ca3457369bc87e435d7c446b98'
		],
		'VDM\\Joomla\\Componentbuilder\\Search\\Interfaces\\InsertInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Search/Interfaces/InsertInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'cfcc238525049393bffb145f112bad89faa27176dbf0fa5e4c4a013e9ce81693',
			'signature_hash' => 'cb0cc9fc22916cdf53f5642817072c497002ef1d8a25d86ec27f9c6f0f8ec950'
		],
		'VDM\\Joomla\\Componentbuilder\\Search\\Interfaces\\LoadInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Search/Interfaces/LoadInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '8080ddd1fa3ede6fc146aa31730daed34edb4657e2e31044b4099b2493017eb8',
			'signature_hash' => '02c121a2725a49eb50304e3ca0ee97933771aa5c8b1efbdda84165f0ee1b10a5'
		],
		'VDM\\Joomla\\Componentbuilder\\Search\\Interfaces\\ReplaceInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Search/Interfaces/ReplaceInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '849bdf2ba1fd372939b1279c92a7c45040682482fde0af10f48cc70954abeea6',
			'signature_hash' => '532a609ff210dfdf0f3eb8655d0ac07776dc26ca3457369bc87e435d7c446b98'
		],
		'VDM\\Joomla\\Componentbuilder\\Search\\Interfaces\\SearchInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Search/Interfaces/SearchInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'e59b30b4d06ff4903177ce5eae7b4c50534480a073ec4e496af84049d0826dbf',
			'signature_hash' => '06a41bc751191984f8c903c382456de6533272b2c7896e6863449656bbe99b44'
		],
		'VDM\\Joomla\\Componentbuilder\\Search\\Interfaces\\SearchTypeInterface' => [
			'path' => 'VDM.Joomla/src/Componentbuilder/Search/Interfaces/SearchTypeInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '1db063d57d343852da40e8194727f76fe1263a374cebfcc57ae025556ad18738',
			'signature_hash' => '5b1b55d4ca0869fa7a000bdf732d7a664ef32b5f63eba56d61b9bdf30a349dc8'
		],
		'VDM\\Joomla\\Interfaces\\Data\\DeleteInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Data/DeleteInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '5ecf1b8c033893cc0b50bf9338d7c97b140d44d45be59b3dab195836a29b8d8a',
			'signature_hash' => '91a0de384dbfb77041765587f2b26fce4e1735d7ba7d213dfe77493a3d39a224'
		],
		'VDM\\Joomla\\Interfaces\\Data\\GuidInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Data/GuidInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '5583da11897d81563b89745457a4468d2b1dc6a518a8b04c0c3a97a92a5128f3',
			'signature_hash' => '82c8a83c481c478b91b1b59b72a21492ddf01ccd7d1baeb33b417a9c413e1fc9'
		],
		'VDM\\Joomla\\Interfaces\\Data\\InsertInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Data/InsertInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '4fe6a700f46f6ad4b5ae2410fc452ebee9d24a27490a80d3ca0ff8225354cd7a',
			'signature_hash' => '16e7973b22d62696b8bb1f26c84ebb9f47a78c6209704b8f4fd9193545d4c56d'
		],
		'VDM\\Joomla\\Interfaces\\Data\\ItemInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Data/ItemInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '93dca104efe8f8ba96d5414113e157c3ccca603567ae94d26de658401dfc44ae',
			'signature_hash' => '43b182da5729cbf98e0f77cceb51f01a05b711a39deadc6f26c5cf33bde62b7b'
		],
		'VDM\\Joomla\\Interfaces\\Data\\ItemsInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Data/ItemsInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '9e1d65bf0430045e936b14c8174f7a480751be9a51d12e6b142866f81fc430aa',
			'signature_hash' => '90d7e32ad2783926988c939004539362b73998b03fd4bcb249aabeb37435944e'
		],
		'VDM\\Joomla\\Interfaces\\Data\\LoadInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Data/LoadInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '44f7d38a706fab903563e7e0b89b725710ff79cd11df3ef8dd77be2cb79952bd',
			'signature_hash' => 'bfe42342200a4f24908a5e85f17439c129dbec7742dcad26ba5cef4aab7c2258'
		],
		'VDM\\Joomla\\Interfaces\\Data\\MultiSubformInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Data/MultiSubformInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '1e4fa2c40b362a641f994a6d0c5fd2a3f58d02b8bc67d3e71ca75ddb3b8c3ac2',
			'signature_hash' => 'a130f26c3174f634f239af4d6fa8b9511180be405fd5d2a2742157824f8932d0'
		],
		'VDM\\Joomla\\Interfaces\\Data\\SubformInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Data/SubformInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '5583da11897d81563b89745457a4468d2b1dc6a518a8b04c0c3a97a92a5128f3',
			'signature_hash' => 'ace030909169808024e0622b7b9535faa3c87a33ff6f9dba8eb63a6634b1c096'
		],
		'VDM\\Joomla\\Interfaces\\Data\\UpdateInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Data/UpdateInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'fe56ca50b6524f026ca8d369174e611a69186f842d69d9ce0202023b4a5632a0',
			'signature_hash' => '57ae9acf967536fd295a4ac7cb94a9b33f33de835e33eef873a85eedf1106bc1'
		],
		'VDM\\Joomla\\Interfaces\\Database\\DefaultInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Database/DefaultInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '020c088bd0db7db058e49b4271d49357f60e1aada70ea6ea668ab26c9c4edae1',
			'signature_hash' => 'a69a356a35632c722ea60595080f29fbe2c3605848e4ce3e23825dc115fc8a06'
		],
		'VDM\\Joomla\\Interfaces\\Database\\DeleteInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Database/DeleteInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '1c7619231d5eca4ca5eeafb9622307f6be690718c9f0ef4f9ad18751d49cbb48',
			'signature_hash' => 'c27690f64e4c6fe9b5d363076bbc8c363be6db689c27c496279163036af0c3ab'
		],
		'VDM\\Joomla\\Interfaces\\Database\\InsertInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Database/InsertInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '519381a92bacce5eb12dd7126588a4c567bc52092d4224e997e9cfbd463ff9ad',
			'signature_hash' => 'bf2b49f1f03a7d681169123b658078f1bb766c1a25b10c5a70f04fb1cc888cf7'
		],
		'VDM\\Joomla\\Interfaces\\Database\\LoadInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Database/LoadInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '8ff315aeca04e4f47d65cc0c35213df4ac776d667089b66b780928cd8525cff4',
			'signature_hash' => '4973ac5ecb55838d5d6befd482bc9a6c1b33df92f635ad280aef26ff2325811e'
		],
		'VDM\\Joomla\\Interfaces\\Database\\UpdateInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Database/UpdateInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'e10537535533696549157ea9f6e98282f493e91885082bd1f2f05f4e3a156d2c',
			'signature_hash' => '21435756fad5270fe9cb17362e5ec10ad198b46283fb34ea5365548f843afd92'
		],
		'VDM\\Joomla\\Interfaces\\Database\\VersioningInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Database/VersioningInterface.php',
			'implementations' => 3,
			'concrete' => 2,
			'implementation_hash' => '9abaf017df2d1c4960adc8f258f008fdae058aad625ea0f227b2654afbdef395',
			'signature_hash' => '4f6d92d2cafcbf097b65978c9ec777f8886ad444fc6cfa23f28b48e23a54d809'
		],
		'VDM\\Joomla\\Interfaces\\FactoryInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/FactoryInterface.php',
			'implementations' => 16,
			'concrete' => 0,
			'implementation_hash' => 'cbaeda148bdaafa0ceebf0d0caf1cd9625802fa48efd1259b7d72a393beb85b3',
			'signature_hash' => 'c30e60ed4f900dd723042033cf3d15bdbd902b429e71d75b6dfc28b31be4b00c'
		],
		'VDM\\Joomla\\Interfaces\\File\\HandlerInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/File/HandlerInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '5bf9c923cc336563630cb76330ea55287d1739c614ccfcfd0d589f71c5ccdcdd',
			'signature_hash' => '22d3e90faa5625e42f892b9b1e7cc5eb0a8872911cf6b40227fb7d786f880d46'
		],
		'VDM\\Joomla\\Interfaces\\File\\PersistentManagerInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/File/PersistentManagerInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'bb22c0deeaa5432641b5dfcc71cbde6de1a31bd1b009a0fb0e9345643536a385',
			'signature_hash' => '3bb6cfbc6d280cbcc9abd751496703b1e981ed28c81044bc648bcbe4a6261715'
		],
		'VDM\\Joomla\\Interfaces\\Git\\ApiInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Git/ApiInterface.php',
			'implementations' => 103,
			'concrete' => 101,
			'implementation_hash' => '6ea0006957db0062cb93eab7010a0c32279993b7c0752ba51fca8ab09871cf62',
			'signature_hash' => '3572b09ef4df75d63f85d317b054c298d4dd58cf942d44067f6a63e0264241c6'
		],
		'VDM\\Joomla\\Interfaces\\Git\\Repository\\ContentsInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Git/Repository/ContentsInterface.php',
			'implementations' => 3,
			'concrete' => 3,
			'implementation_hash' => '97d93bb1e178da618fe8961b3a73a26ef0a5dbcda0d09298c61e6595dae6b42a',
			'signature_hash' => '59ad19066d3effc32f44b6f2662cf14a5ba63db8e58901ace4226de1666be482'
		],
		'VDM\\Joomla\\Interfaces\\Git\\Repository\\TagsInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Git/Repository/TagsInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => 'ac752c9ea16e08b7feb0c0b8d022e490e6450f26f498a1971a026979ebdb1477',
			'signature_hash' => '3ca2eb4935ea42d56675d506ac98e5f2e8890e048305de4e2b5392a32f5ff8a8'
		],
		'VDM\\Joomla\\Interfaces\\Git\\Repository\\WikiInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Git/Repository/WikiInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '56d8b6b3cdf65627bbdb57a3534e488685147df9b6828172818426a2952b10b2',
			'signature_hash' => '941b30b14dbb8db6e16a60552fa8014ff24edcc3e5885de19fa75ebbfc50e237'
		],
		'VDM\\Joomla\\Interfaces\\GrepInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/GrepInterface.php',
			'implementations' => 9,
			'concrete' => 7,
			'implementation_hash' => '5fc07802dc0c93fc4cab6e1e5e67711ee0c3bf33362a99f83f596db04c6f675b',
			'signature_hash' => 'e6c3912c1decb965e53d65a293fbe33ce06d8accb9efe5a0e6aa05f84f93a56e'
		],
		'VDM\\Joomla\\Interfaces\\Import\\AssessorInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/AssessorInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '25081de41cbd00a22e02227551fdc47c4df8422c0ab9febe3f5b88702fbfdf76',
			'signature_hash' => '1b6d0f2d69c4d17e88e6aa8416a96e17d0ef8b44a97a3753c868b5c057aba7e3'
		],
		'VDM\\Joomla\\Interfaces\\Import\\DatabaseMessageInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/DatabaseMessageInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '3cfb8150d3cc14a7efa06d7a32989fe91a2cbe9da9edce52bf0948d1ec9830a8',
			'signature_hash' => 'f170715b09f6cd36037cfd6d82b5c4d328ee6a8da27357e210acba949e3c4765'
		],
		'VDM\\Joomla\\Interfaces\\Import\\EntityInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/EntityInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => 'd60a2465e051c59817deab355beeb3e3de138359e4c5b54dbe5073fc59a50832',
			'signature_hash' => 'fca0f3996f46cd23cedb3c761eef27936b8cb210dceced1b7602435766a351bc'
		],
		'VDM\\Joomla\\Interfaces\\Import\\FileReaderInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/FileReaderInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'c5f4e2dc324afeae073322f38a7608c759055cbd6a2508a9fdc130cc7ba7daaf',
			'signature_hash' => 'e89f172cddc1969b942520b66b271553ac877e085c95e735a328dc1188c5b9bc'
		],
		'VDM\\Joomla\\Interfaces\\Import\\ItemProcessInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/ItemProcessInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => '02987cc62183a3a3a39b8831f6d911a99ac3bccbe1656fd29e48585d1cd4b899',
			'signature_hash' => '80707fa96af654ca9560456610f1ffdf42e422095a9e115ffc4556c42177512f'
		],
		'VDM\\Joomla\\Interfaces\\Import\\JoinTablesInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/JoinTablesInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '9de1a19e3fa941fdd57739a1c6802c6d65e327990ee2145783dfdffa75db9181',
			'signature_hash' => '13be95b70ddb2758a26ac8d1a210ef5dbef2823f4ae509c903c65e4812672066'
		],
		'VDM\\Joomla\\Interfaces\\Import\\MapperInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/MapperInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '08795a964456338d3c0f4b149256d066da377e76f0f21f8f8b600160c62a154f',
			'signature_hash' => '88416589b944cebfa6cde2f6538fc452eaa0f439dea0c5e0cba512e43a013d67'
		],
		'VDM\\Joomla\\Interfaces\\Import\\MessageInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/MessageInterface.php',
			'implementations' => 2,
			'concrete' => 2,
			'implementation_hash' => 'fe217c1c2ee8301b3fe58ec8869fc2f9424d04b876a7ee7a75c11dd5dfe2f134',
			'signature_hash' => '053fd647e5e475f104e75a4797424ded7351d5059a5d95c9c3d86a790da835eb'
		],
		'VDM\\Joomla\\Interfaces\\Import\\ParentTableInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/ParentTableInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'cb0252c1103ac9053cae9bf63bc2aa364703638af8c44fa16445e452a7c6e244',
			'signature_hash' => '6fe6dcb8df5f7442b9da7d00e577aab568ea3ccfded28b5399e0b912f5c72873'
		],
		'VDM\\Joomla\\Interfaces\\Import\\PersistentEntityInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/PersistentEntityInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '7b3f15ced42018425e3d92a3f47fba36fccdab998be7c52bf1c3382f7b52d67b',
			'signature_hash' => '7335161af55589e0f39d0ee2093521d64632c1025e0082b9ee0dd3c5ad6f922b'
		],
		'VDM\\Joomla\\Interfaces\\Import\\RowInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/RowInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => 'e36257d837af8bf52c77b230b7a44496eb40add2829be89bac1cb719de065d11',
			'signature_hash' => 'f974915c5b30ec443136b706f7d9ddef2a8da0fbbee05b75c811c2173ebdc9d3'
		],
		'VDM\\Joomla\\Interfaces\\Import\\RowItemInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/RowItemInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '78edf6c592a75221287350f503326c8e9f12935841739657345c417b06c7634e',
			'signature_hash' => 'fb561239b82ad7f86eceecacba30db055c0acf4a4eaff93dabaa546ed5a23063'
		],
		'VDM\\Joomla\\Interfaces\\Import\\SpreadsheetReaderInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/SpreadsheetReaderInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '9c5566b7f5178658bc3f8a5da1aa340cd3e0baa1b75b242d1e0badd7092471db',
			'signature_hash' => 'f1d3523e48672b9c3a5d69f6794968ed1039da669991af29daac3f11fbed5595'
		],
		'VDM\\Joomla\\Interfaces\\Import\\StatusInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Import/StatusInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '075771a54c365b291effdfa0ff22123b0ffbff2b380980203ded6e24c1898ad7',
			'signature_hash' => 'd60a5b35715e9e4b3bb20930748fed80ea6881b367a3e64a63ab7d4ed0b8d227'
		],
		'VDM\\Joomla\\Interfaces\\ModelInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/ModelInterface.php',
			'implementations' => 6,
			'concrete' => 5,
			'implementation_hash' => 'ba98c64a6e24b08dafe3e63b5fd2864ae498efd256e036915136b875b5d7227b',
			'signature_hash' => '2a1f77e16569fc8cb3aaad91d8e35c40bf56c5805075448be62f7b85106d756b'
		],
		'VDM\\Joomla\\Interfaces\\PHPConfigurationCheckerInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/PHPConfigurationCheckerInterface.php',
			'implementations' => 2,
			'concrete' => 1,
			'implementation_hash' => 'a806e9bff5f6a668f4d35a32ec374089daa700f9621bf2b822fd70e14ad669c0',
			'signature_hash' => '4870760c017dc96adcb75c8f706ccef929740c308034c9eaf76bedb0ebbf7371'
		],
		'VDM\\Joomla\\Interfaces\\Readme\\ItemInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Readme/ItemInterface.php',
			'implementations' => 19,
			'concrete' => 19,
			'implementation_hash' => '8e26d5780c04ea2d52b5f63cc295d2ee2cb8d5a0bb85bbaac5968066c206d694',
			'signature_hash' => '33729b02ad8b11a7a4f4bf8decacf5b280fa33676f19382a7cc6835b72cbe4e8'
		],
		'VDM\\Joomla\\Interfaces\\Readme\\MainInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Readme/MainInterface.php',
			'implementations' => 19,
			'concrete' => 19,
			'implementation_hash' => '15790e18223cf96587a450a2f34226e963da506b8d20e54ad63ad8d00f98830f',
			'signature_hash' => '3805a39bce07b6121d3a1085a10c16b8ec753951234e35b3a883e8a0b9ec6ffc'
		],
		'VDM\\Joomla\\Interfaces\\Remote\\BaseInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Remote/BaseInterface.php',
			'implementations' => 17,
			'concrete' => 12,
			'implementation_hash' => '622211d342fc3b0a77ecede540a0bab8f6dcb593c65a7a0ec16370525831008b',
			'signature_hash' => '3e901e0f1ef102bc4090695fffdda7d7affc4f2adb3e90b1131644a0a19a0952'
		],
		'VDM\\Joomla\\Interfaces\\Remote\\ConfigInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Remote/ConfigInterface.php',
			'implementations' => 66,
			'concrete' => 60,
			'implementation_hash' => 'e7c599e5eac5e01b3597d6d78fa4fa9583e92d255af3deefe9746a72b7bd5921',
			'signature_hash' => 'e576fca0edeb043f113063d1e968bbfb48c2b68235b06f19e60f0a1d848624a7'
		],
		'VDM\\Joomla\\Interfaces\\Remote\\Dependency\\ResolverInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Remote/Dependency/ResolverInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '3425dd5cd03f2fecda8cb347e9acbaf0c799c5edaaa39c70835c01ba1100bb6b',
			'signature_hash' => '3f7a70c9932643a05a1d2a1734cb3c25e6aeca31311d18d49593de6a789c2db4'
		],
		'VDM\\Joomla\\Interfaces\\Remote\\GetInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Remote/GetInterface.php',
			'implementations' => 5,
			'concrete' => 3,
			'implementation_hash' => 'c3082e05f8816575ae32aa00c84e50267ba667870e6556b9d12099892977bc85',
			'signature_hash' => '5464a93fe5d6e705b6abe97c2d7f74b0401c1821aef67e06ac16448c40ce6758'
		],
		'VDM\\Joomla\\Interfaces\\Remote\\SetInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Remote/SetInterface.php',
			'implementations' => 11,
			'concrete' => 9,
			'implementation_hash' => '48105d7c2e0cd76b14f096f1d79e6566ec1569e6756b333912818ab660601e8a',
			'signature_hash' => '8aff5008a37f26db420a563921d72e39d277c736bafb4d88f3e4c066eeb09cb2'
		],
		'VDM\\Joomla\\Interfaces\\SchemaCheckerInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/SchemaCheckerInterface.php',
			'implementations' => 2,
			'concrete' => 1,
			'implementation_hash' => '6ef68f0466fc1bc8e648c12cb0cd6144665e134a3b5d276299020edc2b383b7b',
			'signature_hash' => '4870760c017dc96adcb75c8f706ccef929740c308034c9eaf76bedb0ebbf7371'
		],
		'VDM\\Joomla\\Interfaces\\SchemaInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/SchemaInterface.php',
			'implementations' => 2,
			'concrete' => 1,
			'implementation_hash' => '5f255c1a40014b551ec92f4a35c759240011cadc915cea90e0a6b31c521a60a2',
			'signature_hash' => '8c3e2bccb09bb2ea8d41e944031601421cad3203cede0b9a1fcd207d8a2a7271'
		],
		'VDM\\Joomla\\Interfaces\\Spreadsheet\\RowDataInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/Spreadsheet/RowDataInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '543aa36d41ca785054f3d45aead5092758792ad4a701a485812422a55b72ebe4',
			'signature_hash' => 'abac42d4028b905ea165770eb543601022f13618778959c75396b05541696302'
		],
		'VDM\\Joomla\\Interfaces\\TableInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/TableInterface.php',
			'implementations' => 4,
			'concrete' => 3,
			'implementation_hash' => '2c00d9cf599f85523317c637e01f989fefeacb9eb34453b73af5bcfc061f57c0',
			'signature_hash' => '45768c4055c535c6b2e3634b92e3b6f9b450f817f5777facc645cf548d4978fb'
		],
		'VDM\\Joomla\\Interfaces\\TableValidatorInterface' => [
			'path' => 'VDM.Joomla/src/Interfaces/TableValidatorInterface.php',
			'implementations' => 1,
			'concrete' => 1,
			'implementation_hash' => '9149cd60c3cbc632d71eff79db63246ad63cb80d21446d35a351ebe7edb4e73c',
			'signature_hash' => '7ac1f051ca18c15baf8b298ce9a1a0e77842340bed72bca6de731b5eeb89f5af'
		],
		];
	}
}
