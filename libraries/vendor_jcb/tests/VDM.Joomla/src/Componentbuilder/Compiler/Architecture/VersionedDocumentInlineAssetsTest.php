<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;


/**
 * Generated view inline asset contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedDocumentInlineAssetsTest extends ArchitectureTestCase
{
	/**
	 * The inline stylesheet statement this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN_CSS = <<<'GEN'

		// Set the Custom JS script to view
		$this->getDocument()->getWebAssetManager()->addInlineStyle("
			body { color: red; }
			a[href=\"x\"] { color: blue; }
		");
GEN;

	/**
	 * The inline script statement this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN_JS = <<<'GEN'

		// Set the Custom JS script to view
		$this->getDocument()->getWebAssetManager()->addInlineScript("
			var a = 1;
			alert(\"hi\");
		");
GEN;

	/**
	 * The Joomla 3 inline stylesheet statement this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_CSS = <<<'GEN'

		// Set the Custom CSS script to view
		$this->document->addStyleDeclaration("
			body { color: red; }
			a[href=\"x\"] { color: blue; }
		");
GEN;

	/**
	 * The Joomla 3 inline script statement this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_JS = <<<'GEN'

		// Set the Custom JS script to view
		$this->getDocument()->addScriptDeclaration("
			var a = 1;
			alert(\"hi\");
		");
GEN;

	/**
	 * The block an empty declaration still opens, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN_EMPTY_CSS = "\n"
		. "\t\t// Set the Custom JS script to view\n"
		. "\t\t\$this->getDocument()->getWebAssetManager()->addInlineStyle(\"\n"
		. "\t\t\t\n"
		. "\t\t\");";

	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree'],
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * The targets that hand their inline assets to the web asset manager.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		return [
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * Build the inline asset renderer of a target.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function subject(string $version): object
	{
		return $this->renderer(
			$this->targetClass($version, 'View\\DocumentInlineAssets', ['JoomlaThree']),
			[]
		);
	}

	/**
	 * Build a view definition.
	 *
	 * @param   array  $over  What the view declares.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(array $over): array
	{
		$settings = new stdClass();
		$settings->code = 'demo';
		$settings->add_css_document = 0;
		$settings->css_document = '';
		$settings->add_js_document = 0;
		$settings->js_document = '';

		foreach ($over as $key => $value)
		{
			$settings->$key = $value;
		}

		return ['settings' => $settings];
	}

	/**
	 * The stylesheet a view declares, as the demo component wrote it.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function stylesheet(): string
	{
		return "body { color: red; }\na[href=\"x\"] { color: blue; }";
	}

	/**
	 * The script a view declares, as the demo component wrote it.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function script(): string
	{
		return "var a = 1;\nalert(\"hi\");";
	}

	/**
	 * A view that asked for no inline stylesheet is given no statement.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewThatAsksForNoInlineStylesheetIsGivenNoStatement(string $version): void
	{
		$off = $this->view(['add_css_document' => 0, 'css_document' => $this->stylesheet()]);

		$this->assertSame('', $this->subject($version)->css($off));
	}

	/**
	 * A view that asked for no inline script is given no statement.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewThatAsksForNoInlineScriptIsGivenNoStatement(string $version): void
	{
		$off = $this->view(['add_js_document' => 0, 'js_document' => $this->script()]);

		$this->assertSame('', $this->subject($version)->js($off));
	}

	/**
	 * A modern target hands its inline stylesheet to the web asset manager.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernTargetHandsItsStylesheetToTheAssetManager(string $version): void
	{
		$view = $this->view(['add_css_document' => 1, 'css_document' => $this->stylesheet()]);

		$this->assertSame(self::EXPECTED_MODERN_CSS, $this->subject($version)->css($view));
	}

	/**
	 * A modern target hands its inline script to the web asset manager.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernTargetHandsItsScriptToTheAssetManager(string $version): void
	{
		$view = $this->view(['add_js_document' => 1, 'js_document' => $this->script()]);

		$this->assertSame(self::EXPECTED_MODERN_JS, $this->subject($version)->js($view));
	}

	/**
	 * Joomla 3 declares its stylesheet on the document itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeDeclaresItsStylesheetOnTheDocument(): void
	{
		$view = $this->view(['add_css_document' => 1, 'css_document' => $this->stylesheet()]);

		$this->assertSame(self::EXPECTED_J3_CSS, $this->subject('JoomlaThree')->css($view));
	}

	/**
	 * Joomla 3 declares its script on the document itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeDeclaresItsScriptOnTheDocument(): void
	{
		$view = $this->view(['add_js_document' => 1, 'js_document' => $this->script()]);

		$this->assertSame(self::EXPECTED_J3_JS, $this->subject('JoomlaThree')->js($view));
	}

	/**
	 * The quotes of a declared stylesheet are escaped, since the statement
	 * carries it inside a double quoted string.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheQuotesOfADeclaredStylesheetAreEscaped(string $version): void
	{
		$view = $this->view(['add_css_document' => 1, 'css_document' => $this->stylesheet()]);

		$this->assertStringContainsString('a[href=\\"x\\"]', $this->subject($version)->css($view));
	}

	/**
	 * A view that asked for an inline stylesheet and then declared nothing
	 * still opens the block, which is what the compiler has always written.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnEmptyDeclarationStillOpensTheBlock(): void
	{
		$view = $this->view(['add_css_document' => 1, 'css_document' => '']);

		$this->assertSame(
			self::EXPECTED_MODERN_EMPTY_CSS, $this->subject('JoomlaSix')->css($view)
		);
	}
}
