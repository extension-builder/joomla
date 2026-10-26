<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    23rd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\JoomlaSix;


use DOMDocument;
use DOMXPath;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\CMS\WebAsset\WebAssetRegistry;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionProperty;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminViews\ListView;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminViews\ViewBody;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View\JavaScriptFile;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Factory as Compiler;
use VDM\Joomla\Componentbuilder\Compiler\Library\IncludeHelper;
use VDM\Joomla\Componentbuilder\Compiler\Model\Createdate;
use VDM\Joomla\Componentbuilder\Compiler\Model\Modifieddate;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Joomla 6 asset renderers retain PHP execution and native ordering contracts.
 *
 * @since  6.2.0
 */
#[CoversClass(JavaScriptFile::class)]
#[CoversClass(ViewBody::class)]
#[CoversClass(ListView::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class AssetsTest extends ArchitectureTestCase
{
	/**
	 * Generated view files use the correct client within component media.
	 *
	 * @param   string  $target  The compiler build target.
	 * @param   string  $client  The media client directory.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('clients')]
	public function testViewScriptFileUsesMediaAndPreservesItsBody(string $target, string $client): void
	{
		$this->config()->set('build_target', $target);
		$content = new ContentMulti();
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->once())->method('build')->with([$target => 'article'], 'javascript_file', '', $this->isArray())->willReturn(true);
		$include = $this->createMock(IncludeHelper::class);
		$include->expects($this->once())->method('get')->with('/media/com_demo/js/' . $client . '/article.js')->willReturn('registered-script;');
		$created = $this->createStub(Createdate::class);
		$created->method('get')->willReturn('created');
		$modified = $this->createStub(Modifieddate::class);
		$modified->method('get')->willReturn('modified');
		$subject = $this->renderer(JavaScriptFile::class, [
			'contentmulti' => $content, 'structure' => $structure, 'includehelper' => $include,
			'createdate' => $created, 'modifieddate' => $modified,
		]);
		$view = ['settings' => (object) [
			'code' => 'article', 'version' => '1.2.3', 'add_javascript_file' => 1,
			'javascript_file' => 'window.article = true;',
		]];
		$this->assertStringEndsWith('registered-script;', $subject->get($view, strtoupper($target)));
		$this->assertSame('window.article = true;', $content->get('article|' . strtoupper($target) . '_JAVASCRIPT_FILE'));
	}

	/**
	 * Authored list footer PHP runs in its view and emits JavaScript after the body.
	 *
	 * @param   string  $indent  The configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('indentationValues')]
	public function testFooterExecutesDynamicPhpAtItsOriginalBodyPosition(string $indent): void
	{
		$this->configureEmission($indent);
		$authored = "\n\t  window.caption = <?php echo json_encode(\$this->label); ?>;\n";
		$source = $this->footer($authored);
		$this->assertSame(PHP_EOL . '<script type="text/javascript">' . $authored . '</script>', $source);
		$view = $this->runtimeView();
		$wa = $view->getDocument()->getWebAssetManager();
		$wa->registerAndUseScript('com_demo.file', 'https://example.invalid/file.js', [], [], ['core']);
		$body = '<form id="adminForm"></form>';
		$expected = $body . PHP_EOL . '<script type="text/javascript">'
			. "\n\t  window.caption = \"O'Reilly\";\n" . '</script>';
		$this->assertSame($expected, $view->run($body . $source));
		$this->assertSame(['core', 'com_demo.file'], array_keys($wa->getAssets('script', true)));

		// Each layout render retains its own footer, without registering a head asset.
		$this->assertSame($expected, $view->run($body . $source));
		$this->assertSame(['core', 'com_demo.file'], array_keys($wa->getAssets('script', true)));
	}

	/**
	 * Custom footer PHP errors propagate without registering partial head scripts.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFooterFailurePreservesTheCallingOutputBuffer(): void
	{
		$source = $this->footer('before; <?php throw new \\RuntimeException("footer failed"); ?> after;');
		$view = $this->runtimeView();
		$level = ob_get_level();
		ob_start();
		try
		{
			echo 'caller-owned';
			try
			{
				$view->run($source);
				$this->fail('Custom footer exceptions must propagate.');
			}
			catch (RuntimeException $exception)
			{
				$this->assertSame('footer failed', $exception->getMessage());
			}
			$this->assertSame($level + 1, ob_get_level());
			$this->assertSame('caller-owned', ob_get_contents());
			$this->assertSame([], $view->getDocument()->getWebAssetManager()->getAssets('script'));
		}
		finally
		{
			ob_end_clean();
		}
	}

	/**
	 * Native list markup carries safely encoded draggable metadata only when enabled.
	 *
	 * @param   string  $indent  The configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('indentationValues')]
	public function testListBodyRegistersOrderingAndEmitsNativeDraggableMetadata(string $indent): void
	{
		$this->configureEmission($indent);
		$subject = $this->renderer(ViewBody::class);
		$source = $subject->getDefault('article', 'articles');
		$view = $this->runtimeView();
		$prefix = substr($source, 0, strpos($source, '<form'));
		$this->assertSame('', trim($view->run($prefix)));
		$document = $view->getDocument();
		$this->assertSame(['order' => $view->listOrder], $document->getScriptOptions('com_demo.ordering'));
		$assets = $document->getWebAssetManager()->getAssets('script', true);
		$this->assertSame(['core', 'com_demo.ordering'], array_keys($assets));
		$this->assertSame(['core'], $assets['com_demo.ordering']->getDependencies());
		$ordering = $assets['com_demo.ordering']->getOption('content');
		$this->assertStringContainsString('Joomla.tableOrdering(order, dirn', $ordering);
		$lines = explode(PHP_EOL, $ordering);
		$this->assertSame('Joomla.orderTable = function () {', array_shift($lines));
		$this->assertSame('};', array_pop($lines));
		$this->assertCount(6, $lines);
		foreach ($lines as $line)
		{
			$this->assertSame($indent, substr($line, 0, strlen($line) - strlen(ltrim($line))));
		}
		$this->assertStringNotContainsString('<script', $prefix);

		$this->assertSame(1, preg_match('~<table[^>]*>.*?</table>~s', $source, $match));
		$url = 'index.php?task=articles.saveOrderAjax&token=1&label="safe"';
		$html = $view->run($match[0], $url);
		$dom = new DOMDocument();
		$this->assertTrue($dom->loadHTML($html));
		$body = (new DOMXPath($dom))->query('//tbody')->item(0);
		$this->assertSame('js-draggable', $body->getAttribute('class'));
		$this->assertSame($url, $body->getAttribute('data-url'));
		$this->assertSame('desc', $body->getAttribute('data-direction'));
		$this->assertSame(3, $body->attributes->length);
		$view->saveOrder = false;
		$this->assertStringNotContainsString('js-draggable', $view->run($match[0], $url));
		$view->saveOrder = true;
		$this->assertStringNotContainsString('js-draggable', $view->run($match[0]));
	}

	/**
	 * Shared list comments retain their debug origin under configured indentation.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testListBodyUsesConfiguredIndentationAndCommentOrigins(): void
	{
		$this->configureEmission('    ', true);
		$filters = new AdminFilterType();
		$filters->set('articles', 2);
		$source = $this->renderer(ViewBody::class, ['adminfiltertype' => $filters])->getDefault('article', 'articles');
		$this->assertMatchesRegularExpression(
			'~\n    // \[VDM\\\\Joomla\\\\Componentbuilder\\\\Compiler\\\\Architecture\\\\AdminViews\\\\ViewBody \d+\] Add the searchtools\n~',
			$source
		);
		$this->assertStringContainsString("Joomla__" . "_7ab82272_0b3d_4bb1_af35_e63a096cfe0b___Power::render('joomla.searchtools.default'", $source);
	}

	/**
	 * Indentation units supported by the compiler configuration.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.2.0
	 */
	public static function indentationValues(): iterable
	{
		yield 'tabs' => ["\t"];
		yield 'two spaces' => ['  '];
		yield 'four spaces' => ['    '];
	}

	/**
	 * Build targets with their expected installed asset clients.
	 *
	 * @return  iterable<string, array{string, string}>
	 * @since   6.2.0
	 */
	public static function clients(): iterable
	{
		yield 'admin' => ['admin', 'admin'];
		yield 'site' => ['site', 'site'];
		yield 'custom admin' => ['custom_admin', 'admin'];
	}

	/**
	 * Initialize the real emission utilities from the isolated compiler config.
	 *
	 * @param   string  $indent  The configured indentation unit.
	 * @param   bool    $debug   Whether generated comments include their source.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	private function configureEmission(string $indent, bool $debug = false): void
	{
		$this->config()->set('indentation_value', $indent);
		$this->config()->set('debug_line_nr', $debug);
		$this->isolateFactory(Compiler::class);
		$container = new Container();
		$container->set('Config', $this->config());
		(new ReflectionProperty(Compiler::class, 'container'))->setValue(null, $container);
		(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, '');
		(new ReflectionProperty(Indent::class, 'bucket'))->setValue(null, []);
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, 'check');
	}

	/**
	 * Compile a footer through the public list-view builder.
	 *
	 * @param   string  $script  The custom script body.
	 *
	 * @return  string  The emitted template fragment.
	 * @since   6.2.0
	 */
	private function footer(string $script): string
	{
		$content = new ContentMulti();
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->method('get')->willReturnCallback(static fn(string $key): string => $key === 'views_footer' ? $script : '');
		$subject = $this->renderer(ListView::class, ['dispenser' => $dispenser, 'contentmulti' => $content]);
		$view = ['settings' => (object) [
			'name_single_code' => 'article', 'name_list_code' => 'articles', 'name_list' => 'Articles',
		], 'icomoon' => 'article-icon'];
		$single = 'article';
		$list = 'articles';
		$subject->build($view, $single, $list);

		return $content->get('articles|VIEWS_FOOTER_SCRIPT');
	}

	/**
	 * Create a generated-view runtime with a real document and asset registry.
	 *
	 * @return  object  The isolated view context.
	 * @since   6.2.0
	 */
	private function runtimeView(): object
	{
		$wa = new WebAssetManager(new WebAssetRegistry());
		$wa->registerScript('core', 'https://example.invalid/core.js');
		$view = new class
		{
			/**
			 * The runtime document.
			 *
			 * @var    HtmlDocument
			 * @since  6.2.0
			 */
			public HtmlDocument $document;

			/**
			 * Dynamic content used by custom PHP.
			 *
			 * @var    string
			 * @since  6.2.0
			 */
			public string $label = "O'Reilly";

			/**
			 * The current ordering key.
			 *
			 * @var    string
			 * @since  6.2.0
			 */
			public string $listOrder = "a.ordering'\n";

			/**
			 * The requested direction.
			 *
			 * @var    string
			 * @since  6.2.0
			 */
			public string $listDirn = 'DESC';

			/**
			 * Whether drag ordering is authorized.
			 *
			 * @var    bool
			 * @since  6.2.0
			 */
			public bool $saveOrder = true;

			/**
			 * Return the isolated document.
			 *
			 * @return  HtmlDocument
			 * @since   6.2.0
			 */
			public function getDocument(): HtmlDocument
			{
				return $this->document;
			}

			/**
			 * Supply deterministic list sublayouts.
			 *
			 * @param   string  $name  The sublayout name.
			 *
			 * @return  string  Empty markup suitable for the table section.
			 * @since   6.2.0
			 */
			public function loadTemplate(string $name): string
			{
				return '<!--' . $name . '-->';
			}

			/**
			 * Evaluate generated layout PHP without leaking its output.
			 *
			 * @param   string       $source  The layout fragment.
			 * @param   string|null  $url     Optional save-order URL in layout scope.
			 *
			 * @return  string  Rendered body markup.
			 * @since   6.2.0
			 */
			public function run(string $source, ?string $url = null): string
			{
				if ($url !== null)
				{
					$saveOrderingUrl = $url;
				}
				ob_start();
				try
				{
					eval('?>' . $source);

					return ob_get_contents();
				}
				finally
				{
					ob_end_clean();
				}
			}
		};
		$view->document = new HtmlDocument(['webAssetManager' => $wa]);

		return $view;
	}
}
