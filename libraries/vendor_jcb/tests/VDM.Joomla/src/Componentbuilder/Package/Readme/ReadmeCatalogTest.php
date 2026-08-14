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

namespace VDM\Joomla\Tests\Componentbuilder\Package\Readme;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Interfaces\Readme\ItemInterface;
use VDM\Joomla\Interfaces\Readme\MainInterface;
use VDM\Tests\Support\TestCase;


/**
 * Package README Catalog Test.
 *
 * @since  1.0.0
 */
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\AdminView\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\AdminView\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Children\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Children\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Component\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Component\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\CustomAdminView\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\CustomAdminView\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\CustomCode\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\CustomCode\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\DynamicGet\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\DynamicGet\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Field\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Field\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaModule\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaModule\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaPlugin\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\JoomlaPlugin\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Layout\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Layout\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Library\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Library\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\SiteView\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\SiteView\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Template\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\Template\Readme\Main::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ValidationRule\Readme\Item::class)]
#[CoversClass(\VDM\Joomla\Componentbuilder\Package\ValidationRule\Readme\Main::class)]
final class ReadmeCatalogTest extends TestCase
{
	/**
	 * Item renderers preserve reviewed generated Markdown byte-for-byte.
	 *
	 * @param   class-string<ItemInterface>  $rendererClass  Renderer under test.
	 * @param   string                       $heading        Required first heading.
	 * @param   string                       $expectedHash   Reviewed output fingerprint.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('itemRenderers')]
	public function testItemReadmeContract(
		string $rendererClass,
		string $heading,
		string $expectedHash
	): void
	{
		$renderer = new $rendererClass();
		$output = $renderer->get($this->itemFixture());

		$this->assertInstanceOf(ItemInterface::class, $renderer);

		if ($heading === '')
		{
			$this->assertSame('', $output);
		}
		else
		{
			$this->assertStringStartsWith($heading . "\n", $output);
			$this->assertStringContainsString('Joomla Component Builder', $output);
		}

		$this->assertSame(
			$expectedHash,
			hash('sha256', $output),
			'Review the complete generated README before changing its fingerprint.'
		);
	}

	/**
	 * Main renderers sort and normalize the same repository index fixture.
	 *
	 * @param   class-string<MainInterface>  $rendererClass  Renderer under test.
	 * @param   string                       $heading        Required first heading.
	 * @param   string                       $expectedHash   Reviewed output fingerprint.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('mainRenderers')]
	public function testMainReadmeAndIndexContract(
		string $rendererClass,
		string $heading,
		string $expectedHash
	): void
	{
		$renderer = new $rendererClass();
		$output = $renderer->get($this->indexFixture());

		$this->assertInstanceOf(MainInterface::class, $renderer);

		if ($heading === '')
		{
			$this->assertSame('', $output);
		}
		else
		{
			$this->assertStringStartsWith($heading . "\n", $output);
			$this->assertStringContainsString(
				'**Alpha** | [Details](src/alpha) | [Settings](src/alpha/item.json) | Alpha definition.',
				$output
			);
			$this->assertStringContainsString(
				'**Zulu** | [Details](src/zulu) | [Settings](src/zulu/item.json) | Zulu definition with a concise description.',
				$output
			);
			$this->assertLessThan(
				strpos($output, '**Zulu**'),
				strpos($output, '**Alpha**'),
				'Repository indexes must be sorted by item name.'
			);
			$this->assertStringNotContainsString('<b>', $output);
		}

		$this->assertSame(
			$expectedHash,
			hash('sha256', $output),
			'Review the complete generated repository README before changing its fingerprint.'
		);
	}

	/**
	 * Provide item README generators and reviewed output fingerprints.
	 *
	 * @return  iterable<string, array{class-string<ItemInterface>, string, string}>
	 * @since   1.0.0
	 */
	public static function itemRenderers(): iterable
	{
		yield 'admin view' => [\VDM\Joomla\Componentbuilder\Package\AdminView\Readme\Item::class, '### JCB! Admin View', '7271bbbb91d769e1e09b12cf12200f89627b6896da052569654c946c1c9576da'];
		yield 'children' => [\VDM\Joomla\Componentbuilder\Package\Children\Readme\Item::class, '', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'];
		yield 'component' => [\VDM\Joomla\Componentbuilder\Package\Component\Readme\Item::class, '### JCB! Joomla Component', 'eb120a2faf37b0c22bc4b98eb859108d964877b7f06ef4dc70282bb1aace9aaa'];
		yield 'custom admin view' => [\VDM\Joomla\Componentbuilder\Package\CustomAdminView\Readme\Item::class, '### JCB! Custom Admin View', '23dd00f3db7dd1f1fe7a60f8c246c24206b2828557abdd6d50c0e1b04c05d118'];
		yield 'custom code' => [\VDM\Joomla\Componentbuilder\Package\CustomCode\Readme\Item::class, '### JCB! Custom Code', '3aa3f13011343a8ff062e7fb29b758389e1e5c912e2d1fb2f948c2c19f430664'];
		yield 'dynamic get' => [\VDM\Joomla\Componentbuilder\Package\DynamicGet\Readme\Item::class, '### JCB! Dynamic Get', '0db93e3bdf44efba78e67cfd0803489ed8af9df29ede65af7bb96ed2c16815cd'];
		yield 'field' => [\VDM\Joomla\Componentbuilder\Package\Field\Readme\Item::class, '### JCB! Field', '615a9e7e8210a8ad6a3cca22dbc3c7a14fff2bacb1d36136604444d343ccf817'];
		yield 'Joomla module' => [\VDM\Joomla\Componentbuilder\Package\JoomlaModule\Readme\Item::class, '### JCB! Joomla Module', 'c2a09f807ba3609e6201f642595ea5684af6c467b9f95d63f80cb6b0d4b56fb0'];
		yield 'Joomla plugin' => [\VDM\Joomla\Componentbuilder\Package\JoomlaPlugin\Readme\Item::class, '### JCB! Joomla Plugin', 'e8ee31172caa69f22df0838027016b13eb5c25e2849bdfd9dceb7b7f870780e4'];
		yield 'layout' => [\VDM\Joomla\Componentbuilder\Package\Layout\Readme\Item::class, '### JCB! Layout', '1d1babb9f6c744fb88c9264dd09f94768982fbfe14721c5428a2494487c31661'];
		yield 'library' => [\VDM\Joomla\Componentbuilder\Package\Library\Readme\Item::class, '### JCB! Library', 'c68ae40082c13c1ae7417974e8839e3f585e5a160168bcc770eb4222b34193c5'];
		yield 'site view' => [\VDM\Joomla\Componentbuilder\Package\SiteView\Readme\Item::class, '### JCB! Site View', 'f1c182bbf866b8342342ebf07fd0136270ccc19b99aec7324ff1d29452a2d4aa'];
		yield 'template' => [\VDM\Joomla\Componentbuilder\Package\Template\Readme\Item::class, '### JCB! Template', '3cd73aa46f1034366871a3f0ce53f5c4413c9dd655660614a3d1638022a86a25'];
		yield 'validation rule' => [\VDM\Joomla\Componentbuilder\Package\ValidationRule\Readme\Item::class, '### JCB! Validation Rule', 'ecb53110c3789faee6f69f3d96a952de564b11cd0e67b111b0c353ed88c54162'];
	}

	/**
	 * Provide main README generators and reviewed output fingerprints.
	 *
	 * @return  iterable<string, array{class-string<MainInterface>, string, string}>
	 * @since   1.0.0
	 */
	public static function mainRenderers(): iterable
	{
		yield 'admin views' => [\VDM\Joomla\Componentbuilder\Package\AdminView\Readme\Main::class, '# JCB! Admin Views', 'c46707b337e946abdc7b85e220b9c85f9c0a33465665ca1bd1ed2106525741fe'];
		yield 'children' => [\VDM\Joomla\Componentbuilder\Package\Children\Readme\Main::class, '', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'];
		yield 'components' => [\VDM\Joomla\Componentbuilder\Package\Component\Readme\Main::class, '# JCB! Joomla Components', '5f8034f7c8e192fec31a3771d887f0cf303a2e61ae70aa55d348ce846c5064a0'];
		yield 'custom admin views' => [\VDM\Joomla\Componentbuilder\Package\CustomAdminView\Readme\Main::class, '# JCB! Custom Admin Views', '2ebf152bfd4660397902082c457a290906f462bcf5a8877f9a8d0dc74bbd7446'];
		yield 'custom codes' => [\VDM\Joomla\Componentbuilder\Package\CustomCode\Readme\Main::class, '# JCB! Custom Codes', 'dbfc9d2c8688cf05bd8e8c38767a5ddd45c18b3b2c9196b79ec7c1d8ce17582d'];
		yield 'dynamic gets' => [\VDM\Joomla\Componentbuilder\Package\DynamicGet\Readme\Main::class, '# JCB! Dynamic Gets', '280abf57fbbd878d14ac4c875c71ffedc4e3dd68ddff746e7c277c362bedffa2'];
		yield 'fields' => [\VDM\Joomla\Componentbuilder\Package\Field\Readme\Main::class, '# JCB! Fields', '373c0f840bb59b22291dbf555d580a5cffcc84480a254cc7d8f71358106f0d5b'];
		yield 'Joomla modules' => [\VDM\Joomla\Componentbuilder\Package\JoomlaModule\Readme\Main::class, '# JCB! Joomla Modules', 'fcc16e5399661c255bc039b1f232850bd28e15de4a782eddddebf0a4f166d601'];
		yield 'Joomla plugins' => [\VDM\Joomla\Componentbuilder\Package\JoomlaPlugin\Readme\Main::class, '# JCB! Joomla Plugins', '0b412301e7ed1868db24fa8f0b9c47d9d366318ea04048f41e4ccd79a7451c4f'];
		yield 'layouts' => [\VDM\Joomla\Componentbuilder\Package\Layout\Readme\Main::class, '# JCB! Layouts', '2772227f14d1af3759b1f3b5e1fdc1f598b8f45e69d0f4131688427658ff9797'];
		yield 'libraries' => [\VDM\Joomla\Componentbuilder\Package\Library\Readme\Main::class, '# JCB! Libraries', '958f0bb6b15d58589b8fd3d02481f8ab4949b9c787a454ee8ec6ca1abd270d38'];
		yield 'site views' => [\VDM\Joomla\Componentbuilder\Package\SiteView\Readme\Main::class, '# JCB! Site Views', '3a184f973b0969693e8ca0da47baf26ba9a73b545060d58c4cff7a915bb63c4b'];
		yield 'templates' => [\VDM\Joomla\Componentbuilder\Package\Template\Readme\Main::class, '# JCB! Templates', 'd5f812e180a308855911680f0289e84b5111958f4523a0db77e9f3bb387d0756'];
		yield 'validation rules' => [\VDM\Joomla\Componentbuilder\Package\ValidationRule\Readme\Main::class, '# JCB! Validation Rules', '42817333b5c32ecc01d7fee5ed57d1ff158568bd8ddc060452affd08391d48bd'];
	}

	/**
	 * Build one rich item that exercises every optional renderer section.
	 *
	 * @return  object
	 * @since   1.0.0
	 */
	private function itemFixture(): object
	{
		return (object) [
			'name' => 'Sample Entity',
			'system_name' => 'System Entity',
			'name_single' => 'Sample Item',
			'name_list' => 'Sample Items',
			'short_description' => 'Short definition summary.',
			'description' => 'Longer definition description.',
			'codename' => 'sample_entity',
			'default' => '<section>Rendered body</section>',
			'component_version' => '1.2.3',
			'name_code' => 'sample',
			'companyname' => 'Example Co',
			'author' => 'Example Author',
			'email' => 'dev@example.test',
			'website' => 'https://example.test',
			'add_placeholders' => 1,
			'debug_linenr' => 1,
			'license' => 'GPL `code`',
			'copyright' => 'Copyright Example',
			'addreadme' => 1,
			'readme' => "## Template\n```php\necho true;\n```",
			'target' => 1,
			'comment_type' => 1,
			'joomla_version' => 6,
			'path' => 'admin/src/Example.php',
			'function_name' => 'sampleFunction',
			'code' => 'return true;',
			'main_source' => 1,
			'gettype' => 2,
			'getcustom' => 'loadSample',
			'view_table_main_name' => 'sample_table',
			'view_table_main' => 'guid',
			'view_selection' => 'a.id, a.title',
			'select_all' => 0,
			'pagination' => 1,
			'plugin_events' => ['onContentPrepare'],
			'db_table_main' => '#__content',
			'db_selection' => 'a.id',
			'php_custom_get' => 'return [];',
			'fieldtype' => 'fieldtype-guid',
			'fieldtype_name' => 'Text',
			'datatype' => 'VARCHAR',
			'datalenght' => 'other',
			'datalenght_other' => '255',
			'datadefault' => 'other',
			'datadefault_other' => 'EMPTY',
			'null_switch' => 'NULL',
			'indexes' => 2,
			'store' => 1,
			'xml' => '<field type="text" />',
			'module_version' => '2.0.0',
			'plugin_version' => '3.0.0',
			'add_default_header' => 1,
			'default_header' => 'defined("_JEXEC") or die;',
			'layout_data' => 'return ["item" => true];',
			'mod_code' => 'echo $module;',
			'add_head' => 1,
			'head' => 'use Joomla\CMS\Plugin\CMSPlugin;',
			'main_class_code' => 'public function run(): void {}',
			'alias' => 'sample_alias',
			'add_php_view' => 1,
			'php_view' => '$value = true;',
			'layout' => '<div>Layout</div>',
			'template' => '<div>Template</div>',
			'php' => 'return preg_match("/^[a-z]+$/", $value);',
		];
	}

	/**
	 * Build an intentionally unsorted repository index fixture.
	 *
	 * @return  array<string, array<string, string>>
	 * @since   1.0.0
	 */
	private function indexFixture(): array
	{
		return [
			'z' => [
				'name' => 'Zulu',
				'path' => 'src/zulu',
				'settings' => 'src/zulu/item.json',
				'desc' => '<b>Zulu</b> definition with a concise description.',
			],
			'a' => [
				'name' => 'Alpha',
				'path' => 'src/alpha',
				'settings' => 'src/alpha/item.json',
				'description' => 'Alpha definition.',
			],
		];
	}
}
