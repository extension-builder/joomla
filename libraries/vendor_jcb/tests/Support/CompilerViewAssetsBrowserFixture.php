<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    23rd November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Support;


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Document\Renderer\Html\ScriptsRenderer;
use Joomla\CMS\Document\Renderer\Html\StylesRenderer;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\CMS\WebAsset\WebAssetRegistry;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry as JoomlaRegistry;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View\DocumentScripts;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View\JavaScriptFile;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View\LibrariesLoader;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\DocumentCustomPHP;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\DocumentInlineAssets;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\DocumentScripts as SharedDocumentScripts;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\JavaScriptFile as SharedJavaScriptFile;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\PrepareDocument;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LibraryManager;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Library\Document;
use VDM\Joomla\Componentbuilder\Compiler\Model\Createdate;
use VDM\Joomla\Componentbuilder\Compiler\Model\Modifieddate;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Render generated document assets through Joomla for the Chromium contract.
 *
 * PrepareDocument, document script collectors, templates, Power injection and
 * Joomla's asset renderers are real. Browser requests use local test payloads.
 * This fixture is not a JCB self-compilation or an installed Compiler view.
 *
 * @since  6.2.1
 */
final class CompilerViewAssetsBrowserFixture extends ArchitectureTestCase
{
	/**
	 * Produce each changed template with populated and empty optional inputs.
	 *
	 * @return  array<string, array{html:string,script:string,populated:bool,jquery:int,client:string}>
	 * @since   6.2.1
	 */
	public function pages(): array
	{
		$pages = [];
		$root = new ReflectionProperty(Uri::class, 'root');
		$originalRoot = $root->getValue();

		try
		{
			$root->setValue(null, ['prefix' => 'https://example.test', 'path' => '/subdir']);

			foreach (['CUSTOM_ADMIN_VIEW_HTML.php', 'CUSTOM_ADMIN_VIEWS_HTML.php', 'SITE_VIEW_HTML.php', 'SITE_VIEWS_HTML.php'] as $template)
			{
				foreach (['enabled' => [true, 1], 'explicit' => [true, 0], 'empty' => [false, 0]] as $scenario => [$populated, $jquery])
				{
					parent::setUp();

					try
					{
						$pages[$template . ':' . $scenario] = $this->page($template, $populated, $jquery);
					}
					finally
					{
						parent::tearDown();
					}
				}
			}
		}
		finally
		{
			$root->setValue(null, $originalRoot);
		}

		return $pages;
	}

	/**
	 * Collect real definition output, execute the template and render its assets.
	 *
	 * @param   string  $template   The Joomla six template filename.
	 * @param   bool    $populated  Whether optional file and paths are configured.
	 * @param   int     $jquery     The existing automatic jQuery setting.
	 *
	 * @return  array{html:string,script:string,populated:bool,jquery:int,client:string}
	 * @throws  RuntimeException  When the template contract is incomplete.
	 * @since   6.2.1
	 */
	private function page(string $template, bool $populated, int $jquery): array
	{
		$target = str_starts_with($template, 'SITE_') ? 'site' : 'custom_admin';
		$client = $target === 'site' ? 'site' : 'admin';
		$this->config()->set('component_code_name', 'fixture');
		$this->config()->set('build_target', $target);
		$content = new ContentMulti();
		$definition = $this->definition($populated);
		$this->preparer($content)->set($definition);
		$templateSource = file_get_contents(JCB_PROJECT_ROOT . '/admin/compiler/joomla_6/' . $template);

		if (preg_match('~protected function _prepareDocument\(\): void\s*\{(?<body>.*?)\n\t\}~s', $templateSource, $match) !== 1)
		{
			throw new RuntimeException($template . ' has no complete document method.');
		}

		$source = preg_replace_callback('/###([A-Z_]+)###/', function (array $slot) use ($content): string
		{
			$key = 'article|' . $slot[1];

			if (!$content->exists($key))
			{
				throw new RuntimeException('The real preparer must populate ' . $slot[1] . '.');
			}

			return $content->get($key);
		}, $match['body']);

		if (str_contains($source, '###')
			|| !str_contains($source, '$this->getDocument()->getWebAssetManager()->addInlineScript("'))
		{
			throw new RuntimeException('The complete document method must resolve all slots and retain its inline receiver.');
		}

		$assets = new WebAssetManager(new WebAssetRegistry());
		$assets->registerScript('core', 'https://example.test/subdir/core.js');
		$assets->registerScript('jquery', 'https://example.test/subdir/jquery.js');
		$assets->registerScript('jquery-noconflict', 'https://example.test/subdir/jquery-noconflict.js', [], [], ['jquery']);
		$document = new HtmlDocument(['webAssetManager' => $assets]);
		$document->setMediaVersion('fixture');
		$application = $this->createStub(CMSApplication::class);
		$application->method('getDocument')->willReturn($document);
		$this->setJoomlaApplication($application);
		$view = new stdClass();
		$view->document = $document;
		$view->params = new JoomlaRegistry(['add_jquery_framework' => $jquery]);
		$view->styles = ['/media/com_fixture/css/view.css'];
		$view->scripts = ['/media/com_fixture/js/model.js'];

		if ($populated)
		{
			$view->scripts[] = '/media/com_fixture/js/extra\'s.js?flavour=a%26b';
		}

		$runner = $this->runner($source, $view);
		$runner();
		$head = (new StylesRenderer($document))->render('') . (new ScriptsRenderer($document))->render('');
		$script = $content->get('article|' . strtoupper($target) . '_JAVASCRIPT_FILE', '');

		return [
			'html' => '<!doctype html><html><head><meta charset="utf-8"><link rel="icon" href="data:,">' . $head . '</head><body><div id="asset-result">Ready</div></body></html>',
			'script' => $script,
			'populated' => $populated,
			'jquery' => $jquery,
			'client' => $client,
		];
	}

	/**
	 * Construct the actual coordinator with disabled unrelated document features.
	 *
	 * @param   ContentMulti  $content  Shared view placeholders.
	 *
	 * @return  PrepareDocument
	 * @since   6.2.1
	 */
	private function preparer(ContentMulti $content): PrepareDocument
	{
		$created = $this->createStub(Createdate::class);
		$created->method('get')->willReturn('2026-11-23');
		$modified = $this->createStub(Modifieddate::class);
		$modified->method('get')->willReturn('2026-11-23');
		$javascript = $this->renderer(JavaScriptFile::class, [
			'contentmulti' => $content,
			'structure' => $this->createStub(Structure::class),
			'createdate' => $created,
			'modifieddate' => $modified,
		]);
		$scripts = $this->renderer(DocumentScripts::class, [
			ContentMulti::class => $content,
			SharedJavaScriptFile::class => $javascript,
		]);

		return $this->renderer(PrepareDocument::class, [
			'contentmulti' => $content,
			'libraries' => $this->renderer(LibrariesLoader::class, [
				'registry' => new Registry(),
				'librarymanager' => new LibraryManager(),
				'document' => (new ReflectionClass(Document::class))->newInstanceWithoutConstructor(),
			]),
			'customphp' => $this->renderer(DocumentCustomPHP::class),
			'inline' => $this->renderer(DocumentInlineAssets::class),
			SharedJavaScriptFile::class => $javascript,
			SharedDocumentScripts::class => $scripts,
		]);
	}

	/**
	 * Provide view-local structured inputs and an unchanged inline consumer.
	 *
	 * @param   bool  $populated  Whether optional scripts are configured.
	 *
	 * @return  array{settings:stdClass}
	 * @since   6.2.1
	 */
	private function definition(bool $populated): array
	{
		return ['settings' => (object) [
			'code' => 'article',
			'version' => '1.0.0',
			'add_javascript_file' => $populated ? 1 : 0,
			'javascript_file' => 'window.viewAssetOrder.push("view"); window.fixturePrerequisites = [window.fixtureCore, window.fixtureJquery];',
			'view_script_paths' => $populated ? ["media/com_fixture/js/extra's.js?flavour=a%26b", '//cdn.example.test/library.js'] : [],
			'view_script_dependencies' => $populated ? ['jquery'] : [],
			'add_php_document' => 0,
			'php_document' => '',
			'add_css' => 0,
			'css' => '',
			'add_css_document' => 1,
			'css_document' => '#asset-result { border-top: 3px solid black; }',
			'add_js_document' => 1,
			'js_document' => 'window.viewAssetOrder.push("inline"); window.fixtureInline = window.fixtureModel;'
				. ($populated ? ' window.fixtureRendered = window.fixtureFormat("ready");' : ''),
			'metadata' => 0,
			'main_get' => (object) ['gettype' => 1],
		]];
	}

	/**
	 * Resolve the actual Uri Power and bind generated code to a minimal view.
	 *
	 * @param   string    $source  Complete document method body.
	 * @param   stdClass  $state   Native document and model state.
	 *
	 * @return  \Closure
	 * @throws  RuntimeException  When a Joomla Power was not resolved.
	 * @since   6.2.1
	 */
	private function runner(string $source, stdClass $state): \Closure
	{
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['eecc143e-b5cf-4c33-ba4d-97da1df61422', 0, (object) ['_namespace' => 'Joomla\\CMS\\Uri', 'class_name' => 'Uri', 'type' => 'class']],
		]);
		$injector = new Injector(
			$power,
			new Extractor($this->createStub(DatabaseInterface::class), 6),
			new Parser(),
			$this->placeholder()
		);
		$source = $injector->power('<?php namespace JcbViewAssetsBrowser;' . PHP_EOL
			. 'use stdClass as HeaderCheck;' . PHP_EOL
			. "defined('_JEXEC') or die;" . PHP_EOL . 'return function (): void {' . $source . PHP_EOL . '};');

		if (str_contains($source, 'Joomla___'))
		{
			throw new RuntimeException('The generated Uri reference must resolve through the Power injector.');
		}

		$view = new class($state)
		{
			/**
			 * Runtime model and document state.
			 *
			 * @var    stdClass
			 * @since  6.2.1
			 */
			private stdClass $state;

			/**
			 * Keep the supplied view state without booting a Joomla application.
			 *
			 * @param   stdClass  $state  Runtime model and document state.
			 *
			 * @since   6.2.1
			 */
			public function __construct(stdClass $state)
			{
				$this->state = $state;
			}

			/**
			 * Read model assets and parameters in the generated method's scope.
			 *
			 * @param   string  $name  View property name.
			 *
			 * @return  mixed
			 * @since   6.2.1
			 */
			public function __get(string $name): mixed
			{
				return $this->state->{$name};
			}

			/**
			 * Supply the native document to both the block and inline receiver.
			 *
			 * @return  HtmlDocument
			 * @since   6.2.1
			 */
			public function getDocument(): HtmlDocument
			{
				return $this->state->document;
			}
		};

		return (eval(substr($source, 5)))->bindTo($view, $view);
	}
}
