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
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\CustomCSS;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\DocumentCustomPHP;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\DocumentInlineAssets;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\DocumentMetadata;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\FootableScriptsLoader;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\GetModules;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\GoogleChartLoader;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\JavaScriptFile;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\LibrariesLoader;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\PrepareDocument;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\UikitLoader;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FootableScripts as FootableScriptsBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GetModule;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GoogleChart;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LibraryManager;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminView\FootableScriptsInterface;
use VDM\Joomla\Componentbuilder\Compiler\Library\Document;
use VDM\Joomla\Componentbuilder\Compiler\Library\IncludeHelper;
use VDM\Joomla\Componentbuilder\Compiler\Registry;


/**
 * Generated view prepare document contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ViewPrepareDocumentTest extends ArchitectureTestCase
{
	/**
	 * What the compiler was told to write into the view.
	 *
	 * @var    ContentMulti
	 * @since  6.1.7
	 */
	private ContentMulti $content;

	/**
	 * Build the prepare document filler.
	 *
	 * @return  PrepareDocument
	 * @since   6.1.7
	 */
	private function subject(): PrepareDocument
	{
		$this->content = new ContentMulti();

		return $this->renderer(PrepareDocument::class, [
			'contentmulti' => $this->content,
			'libraries' => $this->renderer(LibrariesLoader::class, [
				'registry' => new Registry(),
				'librarymanager' => new LibraryManager(),
				'document' => (new ReflectionClass(Document::class))->newInstanceWithoutConstructor(),
			]),
			'uikit' => $this->renderer(UikitLoader::class),
			'googlechart' => $this->renderer(GoogleChartLoader::class, [
				'googlechart' => new GoogleChart(),
			]),
			'footable' => $this->renderer(FootableScriptsLoader::class, [
				'footablescripts' => new FootableScriptsBuilder(),
				'scripts' => $this->createStub(FootableScriptsInterface::class),
			]),
			'metadata' => $this->renderer(DocumentMetadata::class),
			'customphp' => $this->renderer(DocumentCustomPHP::class),
			'inline' => $this->renderer(DocumentInlineAssets::class),
			'customcss' => $this->renderer(CustomCSS::class),
			'modules' => $this->renderer(GetModules::class, [
				'contentmulti' => $this->content,
				'getmodule' => new GetModule(),
			]),
			'javascript' => $this->renderer(JavaScriptFile::class, [
				'contentmulti' => $this->content,
				'includehelper' => new IncludeHelper(),
			]),
		]);
	}

	/**
	 * Build a view definition.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(): array
	{
		$settings = new stdClass();
		$settings->code = 'demo';
		$settings->add_php_document = 0;
		$settings->php_document = '';
		$settings->add_css = 0;
		$settings->css = '';
		$settings->add_css_document = 0;
		$settings->css_document = '';
		$settings->add_js_document = 0;
		$settings->js_document = '';
		$settings->add_javascript_file = 0;
		$settings->javascript_file = '';
		$settings->metadata = 0;
		$settings->main_get = (object) ['gettype' => 1];

		return ['settings' => $settings];
	}

	/**
	 * Every part of the prepare document method is filled in, under the build
	 * target the view belongs to.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryPartOfThePrepareDocumentMethodIsFilledIn(): void
	{
		$subject = $this->subject();
		$view = $this->view();

		$subject->set($view);

		foreach ([
			'ADMIN_LIBRARIES_LOADER',
			'ADMIN_UIKIT_LOADER',
			'ADMIN_GOOGLECHART_LOADER',
			'ADMIN_FOOTABLE_LOADER',
			'ADMIN_DOCUMENT_METADATA',
			'ADMIN_DOCUMENT_CUSTOM_PHP',
			'ADMIN_DOCUMENT_CUSTOM_CSS',
			'ADMIN_DOCUMENT_CUSTOM_JS',
			'ADMIN_VIEWCSS',
			'SITE_JAVASCRIPT_FOR_BUTTONS',
			'ADMIN_CUSTOM_BUTTONS',
			'ADMIN_GET_MODULE',
		] as $part)
		{
			$this->assertTrue(
				$this->content->exists('demo|' . $part),
				'The prepare document method was left without its ' . $part . '.'
			);
		}
	}

	/**
	 * A site view has its parts filled in under the site target.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteViewIsFilledInUnderTheSiteTarget(): void
	{
		$this->config()->set('build_target', 'site');
		$subject = $this->subject();
		$view = $this->view();

		$subject->set($view);

		$this->assertTrue($this->content->exists('demo|SITE_LIBRARIES_LOADER'));
		$this->assertFalse($this->content->exists('demo|ADMIN_LIBRARIES_LOADER'));
	}

	/**
	 * The language target follows the build target while the view is prepared,
	 * and is put back the way it was found.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheLanguageTargetIsPutBackTheWayItWasFound(): void
	{
		$this->config()->set('build_target', 'site');
		$this->config()->set('lang_target', 'admin');
		$subject = $this->subject();
		$view = $this->view();

		$subject->set($view);

		$this->assertSame('admin', $this->config()->lang_target);
	}
}
