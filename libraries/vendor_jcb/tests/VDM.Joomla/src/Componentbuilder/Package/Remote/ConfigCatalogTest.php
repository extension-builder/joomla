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

namespace VDM\Joomla\Tests\Componentbuilder\Package\Remote;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Package\Component\Remote\Config as ComponentConfig;
use VDM\Joomla\Componentbuilder\Power\Interfaces\TableInterface;
use VDM\Joomla\Interfaces\Remote\ConfigInterface;
use VDM\Tests\Support\TestCase;


/**
 * Package Remote Configuration Catalog Test.
 *
 * @since  1.0.0
 */
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\AdminCustomTabs\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\AdminFields\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\AdminFieldsConditions\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\AdminFieldsRelations\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\AdminView\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ClassExtends\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ClassMethod\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ClassProperty\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Component\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentAdminViews\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentConfig\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentCustomAdminMenus\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentCustomAdminViews\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentDashboard\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentFilesFolders\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentModules\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentPlaceholders\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentPlugins\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentRouter\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentSiteViews\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ComponentUpdates\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\CustomAdminView\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\CustomCode\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\DynamicGet\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Field\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\File\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Folder\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaModule\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaModuleFilesFoldersUrls\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaModuleUpdates\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaPlugin\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaPluginFilesFoldersUrls\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaPluginGroup\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaPluginUpdates\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Layout\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Library\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\LibraryConfig\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\LibraryFilesFoldersUrls\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Placeholder\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\SiteView\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Template\Remote\Config::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ValidationRule\Remote\Config::class)]
final class ConfigCatalogTest extends TestCase
{
	/**
	 * Protect the complete declarative repository contract for every entity.
	 *
	 * @param   class-string<ConfigInterface>  $configClass  Configuration class.
	 * @param   string                         $expectedHash  Reviewed public-state fingerprint.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('configurations')]
	public function testDeclarativeConfigurationContract(string $configClass, string $expectedHash): void
	{
		$config = new $configClass($this->createStub(TableInterface::class));
		$snapshot = $this->snapshot($config);
		$json = json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		$this->assertInstanceOf(ConfigInterface::class, $config);
		$this->assertNotSame('', $snapshot['table']);
		$this->assertNotSame('', $snapshot['area']);
		$this->assertSame('name', $snapshot['index_header'][0]);
		$this->assertSame('local', $snapshot['index_header'][array_key_last($snapshot['index_header'])]);
		$this->assertContains('path', $snapshot['index_header']);
		$this->assertContains('guid', $snapshot['index_header']);
		$this->assertArrayHasKey('name', $snapshot['index_map']);
		$this->assertArrayHasKey('path', $snapshot['index_map']);
		$this->assertArrayHasKey('guid', $snapshot['index_map']);
		$this->assertSame($snapshot['main_readme'] !== '', $config->hasMainReadme());
		$this->assertSame($snapshot['item_readme'] !== '', $config->hasItemReadme());
		$this->assertRepositoryRelativePath($snapshot['index']);
		$this->assertRepositoryRelativePath($snapshot['src']);
		$this->assertSame(
			$expectedHash,
			hash('sha256', (string) $json),
			"Remote configuration changed; review this public contract:\n" . $json
		);
	}

	/**
	 * Common configuration mechanics delegate table metadata and cache field maps.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testCommonMetadataDelegationMutationAndMapCaching(): void
	{
		$core = $this->createMock(TableInterface::class);
		$core->expects($this->once())
			->method('fields')
			->with('joomla_component')
			->willReturn(['id', 'access', 'export_key', 'name', 'guid']);
		$core->expects($this->once())
			->method('titleName')
			->with('joomla_component')
			->willReturn('system_name');
		$core->expects($this->once())
			->method('listViewCodeName')
			->with('joomla_component')
			->willReturn('joomla_components');
		$config = new ComponentConfig($core);

		$this->assertSame(
			['id' => 'id', 'name' => 'name', 'guid' => 'guid'],
			$config->getMap()
		);
		$this->assertSame($config->getMap(), $config->getMap());
		$this->assertSame('system_name', $config->getTitleName());
		$this->assertSame('joomla_components', $config->getListViewCodeName());

		$this->assertSame($config, $config->table('custom_table'));
		$this->assertSame('custom_table', $config->getTable());
		$this->assertSame($config, $config->area('custom_area'));
		$this->assertSame('Custom area', $config->getArea());
		$this->assertSame($config, $config->setSettingsName('definition.json'));
		$this->assertSame('definition.json', $config->getSettingsName());
		$config->setIndexPath('index/custom.json');
		$this->assertSame('index/custom.json', $config->getIndexPath());
	}

	/**
	 * Provide every Package remote configuration and reviewed fingerprint.
	 *
	 * @return  iterable<string, array{class-string<ConfigInterface>, string}>
	 * @since   1.0.0
	 */
	public static function configurations(): iterable
	{
		yield 'Admin Custom Tabs / admin_custom_tabs' => [\VDM\Joomla\Componentbuilder\Package\AdminCustomTabs\Remote\Config::class, '98f4be2746de60f755ca8570c1fdcf4c53e971afcc8e3d74c9ad4ffe2e2fd52e'];
		yield 'Admin Fields Conditions / admin_fields_conditions' => [\VDM\Joomla\Componentbuilder\Package\AdminFieldsConditions\Remote\Config::class, '31836d0cc7e5e43412c8b51b38f30bb3b1257a5075f2396d656aca0f40b0e56a'];
		yield 'Admin Fields Relations / admin_fields_relations' => [\VDM\Joomla\Componentbuilder\Package\AdminFieldsRelations\Remote\Config::class, '2a8e95effe8a6141fa65604986efcaf8fc30628c90da07dbca58675dd3fc8afe'];
		yield 'Admin Fields / admin_fields' => [\VDM\Joomla\Componentbuilder\Package\AdminFields\Remote\Config::class, '99fdcfc24dbbf168def2e7c81bb22d347e5722c585bae3a303210bf3641a398b'];
		yield 'Admin View / admin_view' => [\VDM\Joomla\Componentbuilder\Package\AdminView\Remote\Config::class, '9c055b13a9598d95a7025ad0c8994c003123415cabe373f94e9eb20173666b89'];
		yield 'Class Extends / class_extends' => [\VDM\Joomla\Componentbuilder\Package\ClassExtends\Remote\Config::class, '1a8376d3b8614d705e97bd4981ba612607fdf3675a188806ccca0cbd712cc125'];
		yield 'Class Method / class_method' => [\VDM\Joomla\Componentbuilder\Package\ClassMethod\Remote\Config::class, '94a6e201f29cb2b91be73b6f106105dcdfa2247ec9fb225e7f5a13a3832c5a0d'];
		yield 'Class Property / class_property' => [\VDM\Joomla\Componentbuilder\Package\ClassProperty\Remote\Config::class, '90f914aa6c9c2f4868cc6fd39cc9939e743518f7b2c59357811ef08796cf5eca'];
		yield 'Component Admin Views / component_admin_views' => [\VDM\Joomla\Componentbuilder\Package\ComponentAdminViews\Remote\Config::class, '66651fe19a7f6db495d83c36e42f6115e8851d15cf77c6236ba1617676b53e46'];
		yield 'Component Config / component_config' => [\VDM\Joomla\Componentbuilder\Package\ComponentConfig\Remote\Config::class, 'd08bc77a3ce2480ef2a912fab106e96afd575305c2194c2a3b178639c43192fc'];
		yield 'Component Custom Admin Menus / component_custom_admin_menus' => [\VDM\Joomla\Componentbuilder\Package\ComponentCustomAdminMenus\Remote\Config::class, 'a64d3b71015012b430915c51ab397ef8b6633e43e52b31c873f5733129178bbf'];
		yield 'Component Custom Admin Views / component_custom_admin_views' => [\VDM\Joomla\Componentbuilder\Package\ComponentCustomAdminViews\Remote\Config::class, '11cd075abedf6d4b959f0cdcd167490211ce0967f3177da5e591d13e4815af60'];
		yield 'Component Dashboard / component_dashboard' => [\VDM\Joomla\Componentbuilder\Package\ComponentDashboard\Remote\Config::class, 'ed10311add10f593e475158e97a4b75a4d99f8e24fe83ff977f6fe71ca0b1633'];
		yield 'Component Files Folders / component_files_folders' => [\VDM\Joomla\Componentbuilder\Package\ComponentFilesFolders\Remote\Config::class, 'a1dbc2b1ba6c59aca9355c414ab1c45d6e426fcaa018793052979675ccf9f470'];
		yield 'Component Modules / component_modules' => [\VDM\Joomla\Componentbuilder\Package\ComponentModules\Remote\Config::class, 'f327450d0759bba1300a7a447da987af95e16debda4c962ebcd12e929974e254'];
		yield 'Component Placeholders / component_placeholders' => [\VDM\Joomla\Componentbuilder\Package\ComponentPlaceholders\Remote\Config::class, '6ecc57d288f84bd0e0cbb1c8bed8b9d0363c138c31eeb582316c12a9f00ec2e9'];
		yield 'Component Plugins / component_plugins' => [\VDM\Joomla\Componentbuilder\Package\ComponentPlugins\Remote\Config::class, '3cbd1928e217663e078a7a10e2b79384bfc0064a846d86b09a9269a1dc19d35b'];
		yield 'Component Router / component_router' => [\VDM\Joomla\Componentbuilder\Package\ComponentRouter\Remote\Config::class, '4b6f6a71addb2bef0e7e0810d3e1e08fb0fc6541cb0fc9f3d0fc723f07e5abf7'];
		yield 'Component Site Views / component_site_views' => [\VDM\Joomla\Componentbuilder\Package\ComponentSiteViews\Remote\Config::class, '932920fc5a380846f4dbb34de2775d17d9533a3bd304bc2a50f9f4b496cc31bf'];
		yield 'Component Updates / component_updates' => [\VDM\Joomla\Componentbuilder\Package\ComponentUpdates\Remote\Config::class, '92588ac9b43941778ed73615fed6d66b39871d9368742f356e967bb9cc47c057'];
		yield 'Joomla Component / joomla_component' => [\VDM\Joomla\Componentbuilder\Package\Component\Remote\Config::class, 'e2ded1dc7559473c958145ef4d5513c1a2dfe785285d90c1f04c7631492eb80e'];
		yield 'Custom Admin View / custom_admin_view' => [\VDM\Joomla\Componentbuilder\Package\CustomAdminView\Remote\Config::class, 'd1752e472961ccdf4cda87146059f0e27aafe2a42fb86ff7c26d89c987b6e647'];
		yield 'Custom Code / custom_code' => [\VDM\Joomla\Componentbuilder\Package\CustomCode\Remote\Config::class, 'd21a93365998ae0fbc447c4c1e8099a675c1890a6caa49804ea28c7066baf7e1'];
		yield 'Dynamic Get / dynamic_get' => [\VDM\Joomla\Componentbuilder\Package\DynamicGet\Remote\Config::class, '79128db599ee336b47f4370ab0686e6d0faceddc46da837c5f4b874c85fb0cb9'];
		yield 'Field / field' => [\VDM\Joomla\Componentbuilder\Package\Field\Remote\Config::class, '8242694b266c7e8c40480a332ea0d9c863f1477528f3fad9ea3913393e4bbbc5'];
		yield 'File / file_system' => [\VDM\Joomla\Componentbuilder\Package\File\Remote\Config::class, '7b35ee1192dfd43a3a56caeef8ee86a8aead4eccb9a54d3bb3e8c1e3c02ac2c7'];
		yield 'Folder / file_system' => [\VDM\Joomla\Componentbuilder\Package\Folder\Remote\Config::class, '384819a80baf9721653e7d47d29ba0e1c8bee5ffabed1e791a4b6c556b3fe09c'];
		yield 'Joomla Module Files Folders Urls / joomla_module_files_folders_urls' => [\VDM\Joomla\Componentbuilder\Package\JoomlaModuleFilesFoldersUrls\Remote\Config::class, 'c763314c0bf0ef142edc10a80f79d932d24c3902a71f372eba06b1a74ce194c3'];
		yield 'Joomla Module Updates / joomla_module_updates' => [\VDM\Joomla\Componentbuilder\Package\JoomlaModuleUpdates\Remote\Config::class, '0e22ae20c86a1bbb27d21f494dac980d9f648493a30129b67cffcecd2cb9a61a'];
		yield 'Joomla Module / joomla_module' => [\VDM\Joomla\Componentbuilder\Package\JoomlaModule\Remote\Config::class, '2ddd461e1180b52909d1a26120fd4e5417f53d80bebe57853dd6c236584e504d'];
		yield 'Joomla Plugin Files Folders Urls / joomla_plugin_files_folders_urls' => [\VDM\Joomla\Componentbuilder\Package\JoomlaPluginFilesFoldersUrls\Remote\Config::class, 'dcf1bf8ead1a5ccdd045d35ad130d5bf1bbab401176b3b1dbc2ef6635b48f097'];
		yield 'Joomla Plugin Group / joomla_plugin_group' => [\VDM\Joomla\Componentbuilder\Package\JoomlaPluginGroup\Remote\Config::class, '9e787a70db3ecd532c18c7fb220c16cb0fdb11111d3cb8654d5aa8fcadb64946'];
		yield 'Joomla Plugin Updates / joomla_plugin_updates' => [\VDM\Joomla\Componentbuilder\Package\JoomlaPluginUpdates\Remote\Config::class, '53cdd83aa7c83c8c183857203bc82023e58c89a38d135594e43cea9cc53e5075'];
		yield 'Joomla Plugin / joomla_plugin' => [\VDM\Joomla\Componentbuilder\Package\JoomlaPlugin\Remote\Config::class, '1e97d0846159248a61edfd62630e72764cd6b6c7b1e7137c46a504e745a85689'];
		yield 'Layout / layout' => [\VDM\Joomla\Componentbuilder\Package\Layout\Remote\Config::class, '16c0b4358b0a8547c0b614133ee760876168de9ba537e008851605a25b9a49eb'];
		yield 'Library Config / library_config' => [\VDM\Joomla\Componentbuilder\Package\LibraryConfig\Remote\Config::class, '1c296097e7dfc94fce577baddd3c151bcd6dbeba4b3c9892d7cf272b64b17085'];
		yield 'Library Files Folders Urls / library_files_folders_urls' => [\VDM\Joomla\Componentbuilder\Package\LibraryFilesFoldersUrls\Remote\Config::class, '7a6b80a48e56c6bb11e97ef0a3d1e626b7594b11b594e684bcc264eae6be9cb6'];
		yield 'Library / library' => [\VDM\Joomla\Componentbuilder\Package\Library\Remote\Config::class, '58aceb65d9cdbf359e5c96e152a94e4211f25443665e99d9352c5301e526bee7'];
		yield 'Placeholder / placeholder' => [\VDM\Joomla\Componentbuilder\Package\Placeholder\Remote\Config::class, '976c54706af17ecd21bb2b739246b6820689505b929f73929d3145402fe1c319'];
		yield 'Site View / site_view' => [\VDM\Joomla\Componentbuilder\Package\SiteView\Remote\Config::class, '965d410c2a14ad1bf3f65ecbb9f54b97074296c85a3825e2c322593f2e0f8cb3'];
		yield 'Template / template' => [\VDM\Joomla\Componentbuilder\Package\Template\Remote\Config::class, 'e40ae9e8089f58b9794ba83392eec76a59469f1e69b746a125e970ef006cf184'];
		yield 'ValidationRule / validation_rule' => [\VDM\Joomla\Componentbuilder\Package\ValidationRule\Remote\Config::class, '2b2c739001d945a7c164451863ef6a2095cde78b9c72be5cc3db1d9b1ee22879'];
	}

	/**
	 * Snapshot every public declarative setting that controls repository layout.
	 *
	 * @param   ConfigInterface  $config  Configuration under test.
	 *
	 * @return  array<string, mixed>
	 * @since   1.0.0
	 */
	private function snapshot(ConfigInterface $config): array
	{
		return [
			'table' => $config->getTable(),
			'area' => $config->getArea(),
			'settings' => $config->getSettingsName(),
			'index' => $config->getIndexPath(),
			'index_map' => $config->getIndexMap(),
			'index_header' => $config->getIndexHeader(),
			'src' => $config->getSrcPath(),
			'main_readme' => $config->getMainReadmePath(),
			'item_readme' => $config->getItemReadmeName(),
			'files' => $config->getFiles(),
			'folders' => $config->getFolders(),
			'children' => $config->getChildren(),
			'guid' => $config->getGuidField(),
			'guid_helper' => $config->getGuidHelperField(),
			'prefix' => $config->getPrefixKey(),
			'suffix' => $config->getSuffixKey(),
			'placeholders' => $config->getPlaceholders(),
		];
	}

	/**
	 * Assert that a repository path is relative and contains no traversal segment.
	 *
	 * @param   string  $path  Repository-relative path.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function assertRepositoryRelativePath(string $path): void
	{
		$this->assertNotSame('', $path);
		$this->assertFalse(str_starts_with($path, '/'));
		$this->assertDoesNotMatchRegularExpression('/^[A-Za-z]:/', $path);
		$this->assertStringNotContainsString('\\', $path);
		$this->assertNotContains('..', explode('/', $path));
	}
}
