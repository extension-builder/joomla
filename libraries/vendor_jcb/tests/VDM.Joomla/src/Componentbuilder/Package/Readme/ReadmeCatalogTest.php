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
 * Fingerprints include the LF heredoc output imported by the 6.2.0 stable build.
 * The complete prior outputs were reviewed: only their CRLF line endings changed.
 * Assertions deliberately continue to compare the raw emitted bytes.
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
		yield 'admin view' => [\VDM\Joomla\Componentbuilder\Package\AdminView\Readme\Item::class, '### JCB! Admin View', 'f375253ec8eebdf66441c91d7d31af700477baf394fa52ba26597a8718cc4165'];
		yield 'children' => [\VDM\Joomla\Componentbuilder\Package\Children\Readme\Item::class, '', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'];
		yield 'component' => [\VDM\Joomla\Componentbuilder\Package\Component\Readme\Item::class, '### JCB! Joomla Component', '85cf33e29d38e8c084c31789f2d1da620cef0c72d5408a0a15141e15141d56ba'];
		yield 'custom admin view' => [\VDM\Joomla\Componentbuilder\Package\CustomAdminView\Readme\Item::class, '### JCB! Custom Admin View', '40b4a435b812d776fbc838153563882e4adf077ddfec7d39c13b45c70f564566'];
		yield 'custom code' => [\VDM\Joomla\Componentbuilder\Package\CustomCode\Readme\Item::class, '### JCB! Custom Code', 'e8309d23335fab6380f50c56ccda4dbb8c806f7c778f7c30c4f90e703a628100'];
		yield 'dynamic get' => [\VDM\Joomla\Componentbuilder\Package\DynamicGet\Readme\Item::class, '### JCB! Dynamic Get', '6260bffcf994e6f157f07a1c723c4de89659dd90c41e5b74427e1708e9271657'];
		yield 'field' => [\VDM\Joomla\Componentbuilder\Package\Field\Readme\Item::class, '### JCB! Field', '516b9b6bff8a41486bf27c898ce75e6185fa639354db98f1495833e95d6146d3'];
		yield 'Joomla module' => [\VDM\Joomla\Componentbuilder\Package\JoomlaModule\Readme\Item::class, '### JCB! Joomla Module', '8a0fb7f59d4f3d800f4057d84be7ad95aa39e74b9080d02946d24a500cc8d6b7'];
		yield 'Joomla plugin' => [\VDM\Joomla\Componentbuilder\Package\JoomlaPlugin\Readme\Item::class, '### JCB! Joomla Plugin', '501e5be4b975b136bd5a95e610c3a4cef3a528fbb896cbdac85a5f0246b93866'];
		yield 'layout' => [\VDM\Joomla\Componentbuilder\Package\Layout\Readme\Item::class, '### JCB! Layout', 'da58662873623d3082f41f6d7d317b8daeda5f154050bae4e2c3116d899e426b'];
		yield 'library' => [\VDM\Joomla\Componentbuilder\Package\Library\Readme\Item::class, '### JCB! Library', '740484c19aca4080a28afb53b52a28b90ddfb9b61303031f3c441d7e97343b99'];
		yield 'site view' => [\VDM\Joomla\Componentbuilder\Package\SiteView\Readme\Item::class, '### JCB! Site View', 'd17dd8ff8531bbe89899cd440a3f87ab1015a9e79df4d1fc1fec2ad24806f8c1'];
		yield 'template' => [\VDM\Joomla\Componentbuilder\Package\Template\Readme\Item::class, '### JCB! Template', '72338d939bf0f5a5954f718b65ee9d6756bfe5bf6cac209708b9d4d69aba747f'];
		yield 'validation rule' => [\VDM\Joomla\Componentbuilder\Package\ValidationRule\Readme\Item::class, '### JCB! Validation Rule', '020bf93056b99223b248b198a648c42a0eb63855166dc87b152b5fa8d8353a71'];
	}

	/**
	 * Provide main README generators and reviewed output fingerprints.
	 *
	 * @return  iterable<string, array{class-string<MainInterface>, string, string}>
	 * @since   1.0.0
	 */
	public static function mainRenderers(): iterable
	{
		yield 'admin views' => [\VDM\Joomla\Componentbuilder\Package\AdminView\Readme\Main::class, '# JCB! Admin Views', 'fd06b9aedaa2c47c5e122c5dbc44ac57d45c80308271d11e3b4de4608aba0aaa'];
		yield 'children' => [\VDM\Joomla\Componentbuilder\Package\Children\Readme\Main::class, '', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'];
		yield 'components' => [\VDM\Joomla\Componentbuilder\Package\Component\Readme\Main::class, '# JCB! Joomla Components', '10b5b317acea5a92939685516dffff6d9103718f06ab060d3d72117bf2325fd7'];
		yield 'custom admin views' => [\VDM\Joomla\Componentbuilder\Package\CustomAdminView\Readme\Main::class, '# JCB! Custom Admin Views', '390aae295b6140482f4bfaaa9b6371411dcfc1dae6b2b82e441ad281a08f9bb9'];
		yield 'custom codes' => [\VDM\Joomla\Componentbuilder\Package\CustomCode\Readme\Main::class, '# JCB! Custom Codes', '66bab269eb1e894831fa52e0d1b0348fbf6539f2ca12d78de03b5c1ec3d5d503'];
		yield 'dynamic gets' => [\VDM\Joomla\Componentbuilder\Package\DynamicGet\Readme\Main::class, '# JCB! Dynamic Gets', '4107a53e754e9f8439288174d87c77a7fb33e689d4be1beec82f1b557ab8dd95'];
		yield 'fields' => [\VDM\Joomla\Componentbuilder\Package\Field\Readme\Main::class, '# JCB! Fields', '2a8ea3ac485a85d46f9360b9c58e50d038901234f452151fbad38f8c2ac58c71'];
		yield 'Joomla modules' => [\VDM\Joomla\Componentbuilder\Package\JoomlaModule\Readme\Main::class, '# JCB! Joomla Modules', 'f87ceceb9bf3e489a6b24589a6a9b092eea8c01a2633ade040e3b00470628908'];
		yield 'Joomla plugins' => [\VDM\Joomla\Componentbuilder\Package\JoomlaPlugin\Readme\Main::class, '# JCB! Joomla Plugins', '9eaf6be6fd6a1e665693d6cff6df4f1c908401ff240fb91ec8194c41f3e5ccba'];
		yield 'layouts' => [\VDM\Joomla\Componentbuilder\Package\Layout\Readme\Main::class, '# JCB! Layouts', 'e2d675605f6c04afd4b90f69fc2bfd010ba83b64b0af9f0767516056ce33ca25'];
		yield 'libraries' => [\VDM\Joomla\Componentbuilder\Package\Library\Readme\Main::class, '# JCB! Libraries', '1fbf4e106f26998c28f52198f30b060f8ce9190c3ef9864e6636d411c3cc0c47'];
		yield 'site views' => [\VDM\Joomla\Componentbuilder\Package\SiteView\Readme\Main::class, '# JCB! Site Views', '27e32923693697a018bef8bc733002271f4096ad412e51faf4051b55f1be5593'];
		yield 'templates' => [\VDM\Joomla\Componentbuilder\Package\Template\Readme\Main::class, '# JCB! Templates', 'd322f00307bbf6c8f5de35bd5a7355deee7861f13b867b7f6542f14afae40b53'];
		yield 'validation rules' => [\VDM\Joomla\Componentbuilder\Package\ValidationRule\Readme\Main::class, '# JCB! Validation Rules', '482fdd12ecee6719899add5bbb882612099becb8b56faaf9446c821c4685910d'];
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
