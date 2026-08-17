<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Layout;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\Heuristic;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaFive;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaFour;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaSix;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaThree;
use VDM\Tests\Support\ExtrusionComponentFixture;
use VDM\Tests\Support\TestCase;


/**
 * Placement-map inversion, root-shape expansion, and content-signature contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Layout::class)]
#[CoversClass(JoomlaThree::class)]
#[CoversClass(JoomlaFour::class)]
#[CoversClass(JoomlaFive::class)]
#[CoversClass(JoomlaSix::class)]
#[CoversClass(Heuristic::class)]
final class LayoutContractTest extends TestCase
{
	/**
	 * The kinds whose placement genuinely moved between Joomla 3 and Joomla 4.
	 *
	 * @var    array<int, string>
	 * @since  6.1.6
	 */
	private const MOVED = [
		'form', 'form_dir', 'model', 'model_dir', 'controller', 'controller_dir',
		'table', 'table_dir', 'view_class', 'view_dir', 'tmpl', 'tmpl_dir',
		'field_class', 'rule_class', 'language_file', 'language_sys',
		'site_form', 'site_form_dir', 'site_tmpl', 'site_tmpl_dir',
		'site_model_dir', 'site_view_dir'
	];

	/**
	 * The kinds whose placement is identical in both generations.
	 *
	 * @var    array<int, string>
	 * @since  6.1.6
	 */
	private const SETTLED = [
		'manifest', 'schema', 'schema_dir', 'schema_updates', 'layouts',
		'layouts_view', 'language', 'config', 'access', 'site_layouts'
	];

	/**
	 * The complete token set every placement pattern is expanded with.
	 *
	 * @return  array<string, string>  The expansion tokens.
	 * @since   6.1.6
	 */
	private function tokens(): array
	{
		return [
			'option' => 'com_example',
			'view' => 'item',
			'Name' => 'Item',
			'name' => 'item',
			'tag' => 'en-GB'
		];
	}

	/**
	 * Each layout owns its version identity while the modern three share one map.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEachLayoutOwnsItsVersionAndTheModernThreeShareOneMap(): void
	{
		$three = new JoomlaThree();
		$four = new JoomlaFour();
		$five = new JoomlaFive();
		$six = new JoomlaSix();
		$tokens = $this->tokens();

		$this->assertSame('J3', $three->version());
		$this->assertSame('J4', $four->version());
		$this->assertSame('J5', $five->version());
		$this->assertSame('J6', $six->version());

		$this->assertSame($four->kinds(), $five->kinds());
		$this->assertSame($four->kinds(), $six->kinds());
		$this->assertNotSame($four->kinds(), $three->kinds());

		foreach (['form', 'model', 'table', 'view_class', 'tmpl', 'language_file'] as $kind)
		{
			$this->assertSame($four->candidates($kind, $tokens), $five->candidates($kind, $tokens));
			$this->assertSame($four->candidates($kind, $tokens), $six->candidates($kind, $tokens));
		}

		$this->assertSame(
			['administrator/components/{option}', 'admin', 'administrator', ''],
			$four->roots()['admin']
		);
		$this->assertSame(['admin', 'site', 'media', 'api'], array_keys($four->roots()));
		$this->assertSame($four->roots(), $three->roots());
	}

	/**
	 * One placement pattern expands to every plausible shape of a source root.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCandidatesExpandEveryRootShapeInOrder(): void
	{
		$four = new JoomlaFour();
		$tokens = $this->tokens();

		$this->assertSame(
			[
				'administrator/components/com_example/sql/install.mysql.utf8.sql',
				'admin/sql/install.mysql.utf8.sql',
				'administrator/sql/install.mysql.utf8.sql',
				'sql/install.mysql.utf8.sql'
			],
			$four->candidates('schema', $tokens)
		);

		$this->assertSame(
			[
				'components/com_example/forms/item.xml',
				'site/forms/item.xml',
				'forms/item.xml'
			],
			$four->candidates('site_form', $tokens)
		);

		$this->assertSame(
			[
				'administrator/components/com_example/com_example.xml',
				'admin/com_example.xml',
				'administrator/com_example.xml',
				'com_example.xml',
				'administrator/components/com_example/manifest.xml',
				'admin/manifest.xml',
				'administrator/manifest.xml',
				'manifest.xml'
			],
			$four->candidates('manifest', $tokens)
		);

		$untokenised = $four->candidates('schema');

		$this->assertCount(4, $untokenised);
		$this->assertContains('admin/sql/install.mysql.utf8.sql', $untokenised);
		$this->assertContains('sql/install.mysql.utf8.sql', $untokenised);
	}

	/**
	 * The two generations diverge on exactly the kinds the move maps moved.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheGenerationsDivergeOnExactlyTheMovedKinds(): void
	{
		$three = new JoomlaThree();
		$four = new JoomlaFour();
		$tokens = $this->tokens();
		$shared = array_values(array_intersect($three->kinds(), $four->kinds()));
		$moved = [];
		$settled = [];

		foreach ($shared as $kind)
		{
			if ($three->candidates($kind, $tokens) === $four->candidates($kind, $tokens))
			{
				$settled[] = $kind;

				continue;
			}

			$moved[] = $kind;
		}

		$this->assertSame(self::MOVED, $moved);
		$this->assertSame(self::SETTLED, $settled);

		$this->assertSame('admin/models/forms/item.xml', $three->candidates('form', $tokens)[1]);
		$this->assertSame('admin/forms/item.xml', $four->candidates('form', $tokens)[1]);
		$this->assertSame('admin/models/item.php', $three->candidates('model', $tokens)[1]);
		$this->assertSame('admin/src/Model/ItemModel.php', $four->candidates('model', $tokens)[1]);
		$this->assertSame('admin/tables/item.php', $three->candidates('table', $tokens)[1]);
		$this->assertSame('admin/src/Table/ItemTable.php', $four->candidates('table', $tokens)[1]);
		$this->assertSame('admin/views/item/view.html.php', $three->candidates('view_class', $tokens)[1]);
		$this->assertSame('admin/src/View/Item/HtmlView.php', $four->candidates('view_class', $tokens)[1]);
		$this->assertSame('admin/views/item/tmpl', $three->candidates('tmpl', $tokens)[1]);
		$this->assertSame('admin/tmpl/item', $four->candidates('tmpl', $tokens)[1]);
		$this->assertSame(
			'admin/language/en-GB/en-GB.com_example.ini',
			$three->candidates('language_file', $tokens)[1]
		);
		$this->assertSame(
			'admin/language/en-GB/com_example.ini',
			$four->candidates('language_file', $tokens)[1]
		);

		$this->assertSame(['provider'], array_values(array_diff($four->kinds(), $three->kinds())));
		$this->assertSame([], array_values(array_diff($three->kinds(), $four->kinds())));
		$this->assertSame([], $three->candidates('provider', $tokens));
		$this->assertSame('admin/services/provider.php', $four->candidates('provider', $tokens)[1]);
	}

	/**
	 * An unknown kind yields nothing and every declared token is replaced.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUnknownKindsYieldNothingAndEveryTokenIsReplaced(): void
	{
		$tokens = $this->tokens();

		foreach ([new JoomlaThree(), new JoomlaFour(), new JoomlaFive(), new JoomlaSix()] as $layout)
		{
			$this->assertSame([], $layout->candidates('', $tokens));
			$this->assertSame([], $layout->candidates('schemas', $tokens));
			$this->assertSame([], $layout->candidates('admin/sql', $tokens));

			foreach ($layout->kinds() as $kind)
			{
				$candidates = $layout->candidates($kind, $tokens);

				$this->assertNotSame([], $candidates);
				$this->assertSame($candidates, array_values(array_unique($candidates)));

				foreach ($candidates as $candidate)
				{
					$this->assertStringNotContainsString('{', $candidate);
					$this->assertStringNotContainsString('}', $candidate);
					$this->assertStringStartsNotWith('/', $candidate);
					$this->assertStringEndsNotWith('/', $candidate);
				}
			}
		}

		$four = new JoomlaFour();

		$this->assertSame('admin/com_example.xml', $four->candidates('manifest', $tokens)[1]);
		$this->assertSame('admin/tmpl/item', $four->candidates('tmpl', $tokens)[1]);
		$this->assertSame('admin/layouts/item', $four->candidates('layouts_view', $tokens)[1]);
		$this->assertSame('admin/language/en-GB', $four->candidates('language', $tokens)[1]);
	}

	/**
	 * Every signature test accepts its artifact and rejects its near miss.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEachSignatureAcceptsItsArtifactAndRejectsItsNearMiss(): void
	{
		$heuristic = new Heuristic();

		$this->assertTrue($heuristic->isSchema(ExtrusionComponentFixture::SCHEMA));
		$this->assertTrue($heuristic->isSchema("create   table `#__x` (`id` INT);\n"));
		$this->assertFalse($heuristic->isSchema("DROP TABLE `#__example_item`;\n"));
		$this->assertFalse($heuristic->isSchema("INSERT INTO `#__example_item` (`id`) VALUES (1);\n"));

		$this->assertTrue($heuristic->isForm(ExtrusionComponentFixture::FORM));
		$this->assertFalse($heuristic->isForm(
			"<form>\n\t<fieldset name=\"details\" label=\"COM_EXAMPLE\" />\n</form>\n"
		));
		$this->assertFalse($heuristic->isForm(
			"<config>\n\t<field name=\"amount\" type=\"number\" />\n</config>\n"
		));

		$this->assertTrue($heuristic->isLanguage(ExtrusionComponentFixture::LANGUAGE));
		$this->assertFalse($heuristic->isLanguage("PLG_EXAMPLE_TITLE=\"Example\"\n"));
		$this->assertTrue($heuristic->isLanguage("LIB_EXAMPLE_TITLE=\"Example\"\n", 'lib_example'));
		$this->assertFalse($heuristic->isLanguage("LIB_EXAMPLE_TITLE=\"Example\"\n", 'lib_other'));

		$this->assertTrue($heuristic->isTableClass(ExtrusionComponentFixture::tableClass()));
		$this->assertTrue($heuristic->isTableClass(ExtrusionComponentFixture::unsafeTableClass()));
		$this->assertFalse($heuristic->isTableClass(
			"<?php\nclass Bare\n{\n\tprotected array \$tables = [];\n}\n"
		));
		$this->assertFalse($heuristic->isTableClass(
			"<?php\nclass ItemTable extends Table\n{\n\tprotected \$name = 'item';\n}\n"
		));

		$this->assertTrue($heuristic->isViewFile(ExtrusionComponentFixture::LAYOUT));
		$this->assertTrue($heuristic->isViewFile("<?php\ndefined('_JEXEC') or die;\n?>\n<p>main</p>\n"));
		$this->assertFalse($heuristic->isViewFile("<?php\nclass ItemModel {}\n"));
		$this->assertFalse($heuristic->isViewFile("<?php\nfinal class ItemTable extends Table\n{\n}\n"));
		$this->assertFalse($heuristic->isViewFile("<?php\n\$total = 1;\n"));

		$this->assertFalse($heuristic->isViewFile($this->declaration('class ExampleViewItem extends HtmlView')));
		$this->assertFalse($heuristic->isViewFile($this->declaration('final class ExampleViewList')));
		$this->assertFalse($heuristic->isViewFile($this->declaration('abstract class ExampleViewBase')));
		$this->assertFalse($heuristic->isViewFile($this->declaration('interface ExampleViewInterface')));
		$this->assertFalse($heuristic->isViewFile($this->declaration('trait ExampleViewTrait')));
		$this->assertFalse($heuristic->isViewFile($this->declaration('enum ExampleViewState')));
	}

	/**
	 * A type declaration that also closes PHP and emits markup.
	 *
	 * A Joomla 3 view.html.php sits in the very folder the view locator walks and
	 * looks exactly like this: a real class that still drops out of PHP and prints
	 * markup. Both of the cheap markup signals therefore fire on it, which is why
	 * only the type-declaration refusal can keep it from being taken for a
	 * template.
	 *
	 * @param   string  $declaration  The type declaration line.
	 *
	 * @return  string  The file source.
	 * @since   6.1.6
	 */
	private function declaration(string $declaration): string
	{
		return "<?php\ndefined('_JEXEC') or die;\n\n" . $declaration
			. "\n{\n}\n?>\n<div class=\"wrap\"><h1>Heading</h1></div>\n";
	}

	/**
	 * Classification routes on the extension and on what is inside the file.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testClassifyRoutesByExtensionAndContent(): void
	{
		$heuristic = new Heuristic();

		$this->assertSame(
			'schema',
			$heuristic->classify('/root/admin/sql/install.mysql.utf8.sql', ExtrusionComponentFixture::SCHEMA)
		);
		$this->assertSame(
			'form',
			$heuristic->classify('/root/admin/forms/item.xml', ExtrusionComponentFixture::FORM)
		);
		$this->assertSame(
			'language',
			$heuristic->classify('/root/admin/language/en-GB/com_example.ini', ExtrusionComponentFixture::LANGUAGE)
		);
		$this->assertSame(
			'table_class',
			$heuristic->classify('/root/vendor/Example/Power/Table.php', ExtrusionComponentFixture::tableClass())
		);
		$this->assertSame(
			'form',
			$heuristic->classify('/root/admin/forms/ITEM.XML', ExtrusionComponentFixture::FORM)
		);

		$this->assertNull(
			$heuristic->classify('/root/admin/forms/item.xml', ExtrusionComponentFixture::SCHEMA)
		);
		$this->assertNull(
			$heuristic->classify('/root/admin/sql/install.mysql.utf8.sql', ExtrusionComponentFixture::FORM)
		);
		$this->assertNull(
			$heuristic->classify('/root/notes.txt', ExtrusionComponentFixture::SCHEMA)
		);
		$this->assertNull(
			$heuristic->classify('/root/admin/language/en-GB/com_example.ini', "PLG_EXAMPLE_TITLE=\"Example\"\n")
		);
		$this->assertNull(
			$heuristic->classify('/root/admin/src/Model/ItemModel.php', "<?php\nclass ItemModel {}\n")
		);
		$this->assertNull($heuristic->classify('/root/README', ''));
	}
}
