<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    26th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Document\Renderer\Html\ScriptsRenderer;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\CMS\WebAsset\WebAssetRegistry;
use Joomla\Database\DatabaseInterface;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminView\ViewScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminViews\ListView;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Module\Template;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Hash;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\LockBase;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaSix\Customcode as FieldCustomcode;
use VDM\Joomla\Componentbuilder\Compiler\Model\JoomlaSix\Javascriptadminview;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder\Reverse;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Render actual compiler footer output for the separate Chromium regression suite.
 *
 * The collectors, dispenser, renderers and Joomla head renderer are real. Only
 * unrelated custom-code processors and the application boundary are isolated.
 * This fixture does not claim a complete component compilation or installation.
 *
 * @since  6.2.0
 */
final class CompilerFooterBrowserFixture extends ArchitectureTestCase
{
	/**
	 * Export pages after evaluating generated PHP in its view scope.
	 *
	 * @return  array<string, array{html:string,evaluations:int}>
	 * @since   6.2.0
	 */
	public function pages(): array
	{
		$pages = [];

		foreach (['edit-view', 'list-view', 'edit-field', 'list-field', 'module-field'] as $name)
		{
			parent::setUp();

			try
			{
				$pages[$name] = $this->page($name);
			}
			finally
			{
				parent::tearDown();
			}
		}

		return $pages;
	}

	/**
	 * Follow collection, template emission, PHP evaluation and head rendering.
	 *
	 * @param   string  $name  Footer consumer and definition source.
	 *
	 * @return  array{html:string,evaluations:int}
	 * @since   6.2.0
	 */
	private function page(string $name): array
	{
		$this->config()->set('add_placeholders', false);
		$assets = new WebAssetManager(new WebAssetRegistry());
		$assets->registerScript('jquery', '');
		$assets->addInlineScript(
			'window.footerOrder = ["head"]; document.addEventListener("DOMContentLoaded", function () { window.footerOrder.push("dom-ready"); });',
			['name' => 'core']
		);
		$document = new HtmlDocument(['webAssetManager' => $assets]);
		$application = $this->createStub(CMSApplication::class);
		$application->method('getDocument')->willReturn($document);
		$this->setJoomlaApplication($application);
		$dispenser = $this->dispenser();
		$script = $this->authoredScript();
		$isEdit = str_starts_with($name, 'edit-');
		$property = $isEdit ? 'javascript_view_footer' : 'javascript_views_footer';

		if (str_ends_with($name, '-field'))
		{
			$field = (object) [
				'add_javascript_view_footer' => $isEdit ? 1 : 0,
				'javascript_view_footer' => $isEdit ? base64_encode($script) : '',
				'add_javascript_views_footer' => $isEdit ? 0 : 1,
				'javascript_views_footer' => $isEdit ? '' : base64_encode($script),
				'add_css_view' => 0,
				'add_css_views' => 0,
			];
			$collector = $this->renderer(FieldCustomcode::class, ['dispenser' => $dispenser, 'app' => $application]);
			$collector->update(42, $field, 'article', 'articles');
		}
		else
		{
			$item = (object) ['id' => 42, 'name_single_code' => 'article', 'add_' . $property => 1, $property => base64_encode($script)];
			$this->renderer(Javascriptadminview::class, ['dispenser' => $dispenser, 'app' => $application])->set($item);
		}

		$markup = '<input id="footer-field" value="ready"><script>window.footerOrder.push("body");</script>';
		$viewDefinition = ['settings' => (object) [
			'name_single_code' => 'article', 'name_list_code' => 'articles', 'name_list' => 'Articles',
		], 'icomoon' => 'article-icon'];

		if ($name === 'module-field')
		{
			$module = (object) ['folder_name' => 'mod_articles', 'default' => $markup];
			$source = $this->renderer(Template::class, ['dispenser' => $dispenser])->default($module, 'article');
		}
		elseif ($isEdit)
		{
			$renderer = $this->renderer(ViewScript::class, ['dispenser' => $dispenser]);
			$renderer->get($viewDefinition);
			$source = $markup . $renderer->script('article', 'footerScript');
		}
		else
		{
			$content = new ContentMulti();
			$renderer = $this->renderer(ListView::class, ['dispenser' => $dispenser, 'contentmulti' => $content]);
			$single = 'article';
			$list = 'articles';
			$renderer->build($viewDefinition, $single, $list);
			$source = $markup . $content->get('articles|VIEWS_FOOTER_SCRIPT');
		}

		// The module's Joomla Factory Power is resolved through the production injector.
		$source = $this->resolveModulePower($source);
		$view = $this->view($document);
		$path = $this->writeTemporaryFile($name . '.php', $source);
		$body = $view->render($path);
		$head = (new ScriptsRenderer($document))->render('');
		$after = '<script>window.footerAfter = authoredFooterAction(); window.footerOrder.push("after");</script>';

		return [
			'html' => '<!doctype html><html><head><meta charset="utf-8">' . $head . '</head><body>' . $body . $after . '</body></html>',
			'evaluations' => $view->evaluations,
		];
	}

	/**
	 * Authored data intentionally requires the body and global execution scope.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	private function authoredScript(): string
	{
		return 'var footerElement = document.getElementById("footer-field");' . PHP_EOL
			. 'var footerData = <?php ++$this->evaluations; echo json_encode($this->caption); ?>;' . PHP_EOL
			. 'function authoredFooterAction() { return footerElement.value + ":" + footerData; }' . PHP_EOL
			. 'window.footerSnapshot = { value: footerElement.value, state: document.readyState, parent: document.currentScript.parentElement.tagName, payload: footerData };' . PHP_EOL
			. 'window.footerOrder.push("footer");';
	}

	/**
	 * Use the existing dispenser while isolating unrelated custom source processors.
	 *
	 * @return  Dispenser
	 * @since   6.2.0
	 */
	private function dispenser(): Dispenser
	{
		$custom = $this->createStub(Customcode::class);
		$custom->method('update')->willReturnArgument(0);
		$gui = new Gui($this->config(), $this->createStub(Reverse::class), new Parser(), $this->createStub(DatabaseInterface::class));
		$hash = $this->createStub(Hash::class);
		$hash->method('set')->willReturnArgument(0);
		$lock = $this->createStub(LockBase::class);
		$lock->method('set')->willReturnArgument(0);
		$dispenser = new Dispenser($this->placeholder(), $custom, $gui, $hash, $lock);
		$dispenser->hub = [];

		return $dispenser;
	}

	/**
	 * Resolve emitted Joomla Factory references using the real Power import pipeline.
	 *
	 * @param   string  $source  Generated template with optional Joomla Power tokens.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	private function resolveModulePower(string $source): string
	{
		$power = $this->createStub(\VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface::class);
		$power->method('get')->willReturnMap([
			['39403062-84fb-46e0-bac4-0023f766e827', 0, (object) ['_namespace' => 'Joomla\\CMS', 'class_name' => 'Factory', 'type' => 'class']],
		]);
		$injector = new \VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector(
			$power,
			new \VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor($this->createStub(\Joomla\Database\DatabaseInterface::class), 6),
			new \VDM\Joomla\Componentbuilder\Power\Parser(),
			$this->placeholder()
		);

		return $injector->power('<?php namespace JcbFooterBrowser;' . PHP_EOL
			. "defined('_JEXEC') or die;" . PHP_EOL . '?>' . $source);
	}

	/**
	 * Supply the generated view's document and PHP values without a Joomla site.
	 *
	 * @param   HtmlDocument  $document  The native runtime document.
	 *
	 * @return  object
	 * @since   6.2.0
	 */
	private function view(HtmlDocument $document): object
	{
		return new class($document)
		{
			/**
			 * Native document used by generated code.
			 *
			 * @var    HtmlDocument
			 * @since  6.2.0
			 */
			private HtmlDocument $document;

			/**
			 * PHP interpolation payload containing quotes and Unicode.
			 *
			 * @var    string
			 * @since  6.2.0
			 */
			public string $caption = 'O\'Reilly "quoted" — Joomla';

			/**
			 * Count template interpolation side effects.
			 *
			 * @var    int
			 * @since  6.2.0
			 */
			public int $evaluations = 0;

			/**
			 * Keep the native document in the generated view scope.
			 *
			 * @param   HtmlDocument  $document  The runtime document.
			 * @since   6.2.0
			 */
			public function __construct(HtmlDocument $document)
			{
				$this->document = $document;
			}

			/**
			 * Expose the native document to compiler-generated PHP.
			 *
			 * @return  HtmlDocument
			 * @since   6.2.0
			 */
			public function getDocument(): HtmlDocument
			{
				return $this->document;
			}

			/**
			 * Execute generated PHP exactly once and retain the rendered body.
			 *
			 * @param   string  $path  Isolated generated template file.
			 *
			 * @return  string
			 * @since   6.2.0
			 */
			public function render(string $path): string
			{
				ob_start();

				try
				{
					require $path;

					return ob_get_contents();
				}
				finally
				{
					ob_end_clean();
				}
			}
		};
	}
}
