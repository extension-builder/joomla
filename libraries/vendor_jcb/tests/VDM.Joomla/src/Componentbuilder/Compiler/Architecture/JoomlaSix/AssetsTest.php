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
use InvalidArgumentException;
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
		$this->placeholder()->set_('script_value', 'preserved');
		$this->placeholder()->set('component', 'demo');
		$content = new ContentMulti();
		$prefix = 'article|' . strtoupper($target);
		$content->set($prefix . '_LIBRARIES_LOADER', 'loadLibrary();');
		$content->set($prefix . '_DOCUMENT_CUSTOM_PHP',
			"\n\t\t// add marked library\n\t\tHtml::_('script', 'custom/marked.js', ['version' => 'auto']);"
		);
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->once())->method('build')->with(
			[$target => 'article'],
			'javascript_file',
			'',
			['###CREATIONDATE###' => 'created', '###BUILDDATE###' => 'modified', '###VERSION###' => '1.2.3']
		)->willReturn(true);
		$include = $this->createMock(IncludeHelper::class);
		$include->expects($this->never())->method('getViewScript');
		$include->expects($this->never())->method('get');
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
			'javascript_file' => "window.article = '[[[script_value]]]';\n",
			'view_script_paths' => ["media/com_###component###/it's\\extra.js", '//cdn.example.test/library.js'],
			'view_script_dependencies' => ['jquery', 'vendor.###component###'],
		]];
		$subject->set($view, strtoupper($target));

		$this->assertSame(
			"\n\t\t\t// Add View JavaScript File\n\t\t\t'media/com_demo/js/" . $client . "/article.js',",
			$content->get($prefix . '_VIEW_SCRIPT_ENTRY')
		);
		$this->assertSame(['custom/marked.js', "media/com_demo/it's\\extra.js", '//cdn.example.test/library.js'],
			eval('return [' . $content->get($prefix . '_VIEW_SCRIPT_PATHS') . '];'));
		$this->assertStringStartsWith("\n\t\t\t// Add marked library\n\t\t\t'custom/marked.js',",
			$content->get($prefix . '_VIEW_SCRIPT_PATHS'));
		$this->assertStringEndsWith(PHP_EOL, $content->get($prefix . '_VIEW_SCRIPT_PATHS'));
		$this->assertSame('', $content->get($prefix . '_DOCUMENT_CUSTOM_PHP'));
		$this->assertSame(['core', 'jquery', 'vendor.demo'],
			eval("return ['core'" . $content->get($prefix . '_VIEW_SCRIPT_DEPENDENCIES') . '];'));
		$this->assertSame('loadLibrary();', $content->get($prefix . '_LIBRARIES_LOADER'));
		$this->assertSame("window.article = 'preserved';\n", $content->get($prefix . '_JAVASCRIPT_FILE'));
	}

	/**
	 * Reusing the emitter clears empty slots without leaking another view's inputs.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testScriptSlotsRemainScopedAndClearAbsentInputs(): void
	{
		$content = new ContentMulti();
		$content->set('first|ADMIN_VIEW_SCRIPT_ENTRY', 'stale entry');
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->never())->method('build');
		$subject = $this->renderer(JavaScriptFile::class, [
			'contentmulti' => $content, 'structure' => $structure,
		]);
		$first = ['settings' => (object) [
			'code' => 'first', 'add_javascript_file' => 0,
			'view_script_paths' => ['first.js'], 'view_script_dependencies' => ['jquery'],
		]];
		$second = ['settings' => (object) [
			'code' => 'second', 'add_javascript_file' => 0, 'view_script_paths' => ['second.js'],
		]];
		$subject->set($first, 'ADMIN');
		$subject->set($second, 'SITE');
		$this->assertSame(", 'jquery'", $content->get('first|ADMIN_VIEW_SCRIPT_DEPENDENCIES'));
		unset($first['settings']->view_script_paths, $first['settings']->view_script_dependencies);
		$subject->set($first, 'ADMIN');

		foreach (['ENTRY', 'PATHS', 'DEPENDENCIES'] as $slot)
		{
			$this->assertSame('', $content->get('first|ADMIN_VIEW_SCRIPT_' . $slot));
		}
		$this->assertSame(PHP_EOL . "\t\t\t'second.js'," . PHP_EOL, $content->get('second|SITE_VIEW_SCRIPT_PATHS'));
		$this->assertSame('', $content->get('second|SITE_VIEW_SCRIPT_DEPENDENCIES'));
		$this->assertFalse($content->exists('first|SITE_VIEW_SCRIPT_ENTRY'));
		$this->assertFalse($content->exists('second|ADMIN_VIEW_SCRIPT_ENTRY'));
	}

	/**
	 * Invalid inputs fail before file generation or any script-slot mutation.
	 *
	 * @param   string  $property  The scoped script input.
	 * @param   mixed   $value     An invalid list or list entry.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('invalidScriptInputs')]
	public function testInvalidScriptInputsHaveNoSideEffects(string $property, mixed $value): void
	{
		$this->placeholder()->set('empty', '');
		$content = new ContentMulti();
		$content->set('article|ADMIN_VIEW_SCRIPT_ENTRY', 'existing entry');
		$content->set('article|ADMIN_DOCUMENT_CUSTOM_PHP',
			"Html::_('script', 'marked.js', ['version' => 'auto']);"
		);
		$original = $content->toArray();
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->never())->method('build');
		$subject = $this->renderer(JavaScriptFile::class, [
			'contentmulti' => $content, 'structure' => $structure,
		]);
		$view = ['settings' => (object) [
			'code' => 'article', 'add_javascript_file' => 1, 'javascript_file' => 'run();',
			'view_script_paths' => ['valid.js'], 'view_script_dependencies' => ['core'],
		]];
		$view['settings']->{$property} = $value;
		$this->expectException(InvalidArgumentException::class);

		try
		{
			$subject->set($view, 'ADMIN');
		}
		finally
		{
			$this->assertSame($original, $content->toArray());
		}
	}

	/**
	 * Literal Power includes migrate in source order even without a view file.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testLiteralPowerIncludesKeepCommentsEscapingAndSourceOrder(): void
	{
		$content = new ContentMulti();
		$power = 'Joomla__' . '_34690c75_1090_47eb_8c06_7228dc7eedd6___Power';
		$path = "'media/it\\'s\\\\escaped.js'";
		$content->set('manual|CUSTOM_ADMIN_DOCUMENT_CUSTOM_PHP',
			'/* Keep the prerequisite comment. */' . PHP_EOL
				. $power . "::_('script', " . $path . ", ['version' => 'auto']);" . PHP_EOL
				. 'Html::_("script", "second.js", ["version" => "auto"]);'
		);
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->never())->method('build');
		$subject = $this->renderer(JavaScriptFile::class, ['contentmulti' => $content, 'structure' => $structure]);
		$view = ['settings' => (object) ['code' => 'manual', 'add_javascript_file' => 0]];

		$subject->set($view, 'CUSTOM_ADMIN');

		$this->assertSame(
			PHP_EOL . "\t\t\t/* Keep the prerequisite comment. */"
				. PHP_EOL . "\t\t\t" . $path . ',' . PHP_EOL . "\t\t\t\"second.js\"," . PHP_EOL,
			$content->get('manual|CUSTOM_ADMIN_VIEW_SCRIPT_PATHS')
		);
		$this->assertSame(["media/it's\\escaped.js", 'second.js'],
			eval('return [' . $content->get('manual|CUSTOM_ADMIN_VIEW_SCRIPT_PATHS') . '];'));
		$this->assertSame('', $content->get('manual|CUSTOM_ADMIN_DOCUMENT_CUSTOM_PHP'));
		$this->assertSame('', $content->get('manual|CUSTOM_ADMIN_VIEW_SCRIPT_ENTRY'));
	}

	/**
	 * Unrecognised document PHP remains byte-for-byte intact without partial migration.
	 *
	 * @param   string  $php  The original document contribution.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('unmigratedDocumentScripts')]
	public function testDynamicConditionalAndMixedDocumentPhpIsNotMoved(string $php): void
	{
		$content = new ContentMulti();
		$content->set('manual|ADMIN_DOCUMENT_CUSTOM_PHP', $php);
		$subject = $this->renderer(JavaScriptFile::class, ['contentmulti' => $content]);
		$view = ['settings' => (object) ['code' => 'manual', 'add_javascript_file' => 0]];

		$subject->set($view, 'ADMIN');

		$this->assertSame($php, $content->get('manual|ADMIN_DOCUMENT_CUSTOM_PHP'));
		$this->assertSame('', $content->get('manual|ADMIN_VIEW_SCRIPT_PATHS'));
	}

	/**
	 * Statements whose behaviour cannot be represented by an unconditional path list.
	 *
	 * @return  iterable
	 * @since   6.2.1
	 */
	public static function unmigratedDocumentScripts(): iterable
	{
		$literal = "Html::_('script', 'marked.js', ['version' => 'auto']);";
		yield 'conditional' => ['if ($enabled) { ' . $literal . ' }'];
		yield 'unbraced conditional' => ['if ($enabled) ' . $literal];
		yield 'dynamic path' => ["Html::_('script', \$path, ['version' => 'auto']);"];
		yield 'empty path' => ["Html::_('script', '', ['version' => 'auto']);"];
		yield 'mixed PHP after valid include' => [$literal . ' inspectDocument();'];
		yield 'different version' => [str_replace("'auto'", "'1.2'", $literal)];
		yield 'attributes' => [str_replace(']);', "], ['defer' => true]);", $literal)];
		yield 'return value' => ['return ' . $literal];
		yield 'comment only' => ['// ' . $literal];
		yield 'different helper' => [str_replace('Html', 'OtherHelper', $literal)];
		yield 'interpolated literal' => ["Html::_('script', \"\$path/marked.js\", ['version' => 'auto']);"];
	}

	/**
	 * Escaped path entries remain executable array literals under every indentation.
	 *
	 * @param   string  $indent  The configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('indentationValues')]
	public function testViewPathEntryEscapesItsLiteralAndKeepsDebugOrigin(string $indent): void
	{
		$this->configureEmission($indent);
		$structure = $this->createStub(Structure::class);
		$structure->method('build')->willReturn(true);
		$include = $this->createMock(IncludeHelper::class);
		$include->expects($this->never())->method('getViewScript');
		$include->expects($this->never())->method('get');
		$subject = $this->renderer(JavaScriptFile::class, ['structure' => $structure, 'includehelper' => $include]);
		$view = ['settings' => (object) [
			'code' => "article'\\preview", 'version' => '1.2.3',
			'add_javascript_file' => 1, 'javascript_file' => 'window.article = true;',
		]];
		$entry = $subject->get($view, 'ADMIN');
		$prefix = str_repeat($indent, 3);
		$this->assertSame(
			PHP_EOL . $prefix . '// Add View JavaScript File' . PHP_EOL
				. $prefix . "'media/com_demo/js/admin/article\\'\\\\preview.js',",
			$entry
		);
		$this->assertSame(["media/com_demo/js/admin/article'\\preview.js"], eval('return [' . $entry . '];'));
		$this->configureEmission($indent, true);
		$entry = $subject->get($view, 'ADMIN');
		$this->assertMatchesRegularExpression(
			'~^\n' . preg_quote($prefix, '~') . '// \[VDM\\\\Joomla\\\\Componentbuilder\\\\Compiler\\\\Architecture\\\\JoomlaSix\\\\View\\\\JavaScriptFile \d+\] Add View JavaScript File\n~',
			$entry
		);
		$this->assertSame(["media/com_demo/js/admin/article'\\preview.js"], eval('return [' . $entry . '];'));
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
	 * Cover list shape, entry type, whitespace and placeholder-expansion validation.
	 *
	 * @return  iterable<string, array{string, mixed}>
	 * @since   6.2.1
	 */
	public static function invalidScriptInputs(): iterable
	{
		foreach (['view_script_paths', 'view_script_dependencies'] as $property)
		{
			foreach (['["script.js"]', ['named' => 'script.js'], ['valid.js', 42], [" \t\n"], ['###empty###']] as $case => $value)
			{
				yield $property . ':' . $case => [$property, $value];
			}
		}
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
