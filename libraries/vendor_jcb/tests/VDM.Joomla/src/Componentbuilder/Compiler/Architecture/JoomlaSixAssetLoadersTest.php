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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\CMS\WebAsset\WebAssetRegistry;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GoogleChart;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView;
use VDM\Joomla\Componentbuilder\Compiler\Service\Library;
use VDM\Joomla\Componentbuilder\Power\Parser;

/**
 * Executable native asset graphs and target boundaries.
 *
 * @since 6.2.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Library')]
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Service')]
final class JoomlaSixAssetLoadersTest extends ArchitectureTestCase
{
	/**
	 * Every service target has its own explicit renderer identity.
	 *
	 * @param   int     $major    Target major.
	 * @param   string  $version  Namespace segment.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testTargetSelectionKeepsOlderLoaderOutput(int $major, string $version): void
	{
		$this->config()->set('joomla_version', $major);
		$container = new Container();
		$container->set('Config', $this->config());
		$container->registerServiceProvider(new ArchitectureView());
		$container->registerServiceProvider(new Library());
		$view = [];
		$jquery = $container->get('Architecture.View.Jquery');
		$include = $container->get('Library.IncludeHelper');
		$this->assertSame('VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\' . $version . '\\View\\Jquery', $jquery::class);
		$this->assertSame('VDM\\Joomla\\Componentbuilder\\Compiler\\Library\\' . $version . '\\IncludeHelper', $include::class);
		$this->assertSame($jquery, $container->get('Architecture.View.J' . $major . '.Jquery'));
		$this->assertSame($include, $container->get('Library.J' . $major . '.IncludeHelper'));

		if ($major < 6)
		{
			$shared = new \VDM\Joomla\Componentbuilder\Compiler\Architecture\View\Jquery();
			$this->assertSame($shared->get($view), $jquery->get($view));
			$shared = new \VDM\Joomla\Componentbuilder\Compiler\Library\IncludeHelper();
			$this->assertSame($shared->get('/media/com_demo/a.js'), $include->get('/media/com_demo/a.js'));
		}
		else
		{
			$this->assertStringContainsString("useScript('jquery')", $jquery->get($view));
			$this->assertStringContainsString('getWebAssetManager()', $include->get('/media/com_demo/a.js'));
		}

		// These constructors retain the original dependency graph; only emission differs.
		foreach (['AdminView\\ViewScript', 'AdminView\\FootableScripts', 'View\\GoogleChartLoader', 'View\\LibrariesLoader'] as $family)
		{
			$class = 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\' . $version . '\\' . $family;
			$renderer = $this->renderer($class);
			$this->assertSame($class, $renderer::class);
		}
	}

	/**
	 * Supported compile targets.
	 *
	 * @return  array
	 * @since   6.2.0
	 */
	public static function targets(): array
	{
		return [[3, 'JoomlaThree'], [4, 'JoomlaFour'], [5, 'JoomlaFive'], [6, 'JoomlaSix']];
	}

	/**
	 * URI normalization, attributes, ordering and duplicate handling execute in WAM.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testLibraryAssetsResolvePowerImportsAndMaintainOrder(): void
	{
		$assets = $this->assets();
		$include = new \VDM\Joomla\Componentbuilder\Compiler\Library\JoomlaSix\IncludeHelper();
		$uris = ['/media/com_demo/js/first.js?rev=2#code', 'https://example.test/second.js?v=1', '/media/com_demo/js/first.js?rev=2#code', '//example.test/third.js'];
		$code = implode(PHP_EOL, array_map($include->get(...), $uris));
		$code .= PHP_EOL . $include->get('/media/com_demo/css/first.css?theme=dark');
		$code .= PHP_EOL . $include->get('https://example.test/second.css');
		$run = $this->compileClosure($code);
		$run();
		$scripts = $assets->getAssets('script', true);
		$this->assertSame([
			Uri::root() . 'media/com_demo/js/first.js?rev=2#code',
			$uris[1], $uris[3],
		], array_values(array_map(static fn ($asset) => $asset->getUri(false), $scripts)));
		$this->assertSame(array_keys($scripts), array_keys($assets->getAssets('script', true)));
		$seen = [];

		foreach ($scripts as $name => $asset)
		{
			$this->assertSame('auto', $asset->getVersion());
			$this->assertSame([], $asset->getAttributes());
			$this->assertSame($seen, $asset->getDependencies());
			$seen[] = $name;
		}

		$run();
		$this->assertSame($scripts, $assets->getAssets('script', true));
		$this->assertCount(2, $assets->getAssets('style', true));
		$this->assertSame('', $include->get('https://example.test/library.php'));
		$this->assertSame('require_once("/srv/library.php");', $include->get('/srv/library.php'));
	}

	/**
	 * Footable initialization waits for jQuery and every selected version's script.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFootableRuntimeHasOrderedDependenciesAndNoDuplicateRegistration(): void
	{
		foreach ([2, 3] as $version)
		{
			$assets = $this->assets();
			$assets->registerScript('jquery', 'jquery.js');
			$assets->registerScript('jquery-noconflict', 'jquery-noconflict.js', [], [], ['jquery']);
			$this->config()->set('footable_version', $version);
			$renderer = $this->renderer(\VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminView\FootableScripts::class);
			$run = eval('return function () {' . $renderer->get(true) . '};');
			$view = $this->view($assets);
			$run->call($view);
			$scripts = $assets->getAssets('script', true);
			$last = end($scripts);
			$this->assertSame('com_demo.footable.init', $last->getName());
			$this->assertStringContainsString('jQuery(document).ready', $last->getOption('inline') ? $last->getOption('content') : '');
			$this->assertCount($version === 2 ? 7 : 4, $scripts);
			$this->assertCount(2, $assets->getAssets('style', true));
			$run->call($view);
			$this->assertCount(count($scripts), $assets->getAssets('script', true));
		}
	}

	/**
	 * The active chart flag selects the official loader and native helper.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testChartLoaderKeepsConditionalBehavior(): void
	{
		$charts = new GoogleChart();
		$view = ['settings' => (object) ['code' => 'articles']];
		$subject = $this->renderer(\VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View\GoogleChartLoader::class, ['googlechart' => $charts]);
		$this->assertSame('', $subject->get($view));
		$charts->set($this->config()->build_target . '.articles', true);
		$code = $subject->get($view);
		$this->assertStringContainsString('https://www.gstatic.com/charts/loader.js', $code);
		$this->assertStringContainsString('/src/Helper/Chartbuilder.php', $code);
		$this->assertStringNotContainsString('googlecode.com', $code);
		$this->assertStringNotContainsString('google.jsapi.js', $code);
	}

	/**
	 * The generated chart helper preserves the public PHP API and escapes data.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNativeChartHelperRegistersMultipleChartsThroughWam(): void
	{
		$assets = $this->assets();
		$this->loadTemplate('ADMIN_CHARTBUILDER.php', 'Chartbuilder');
		$class = __NAMESPACE__ . '\\AssetFixture\\Chartbuilder';
		$first = new $class('LineChart');
		$first->load([['Label', 'Count'], ['</script>', 4]], 'array');
		$this->assertSame('', $first->draw('chart-first', ['height' => 400, 'width' => 600, 'tabclickdraw' => 'results']));
		$second = new $class('PieChart');
		$second->load('{"cols":[],"rows":[]}');
		$this->assertSame('', $second->draw('chart-second', []));
		$scripts = $assets->getAssets('script', true);
		$this->assertCount(3, $scripts);
		$this->assertSame('https://www.gstatic.com/charts/loader.js', $scripts['google.charts']->getUri(false));
		$contents = [];

		foreach ($scripts as $name => $asset)
		{
			if ($name === 'google.charts')
			{
				continue;
			}

			$this->assertSame(['google.charts'], $asset->getDependencies());
			$this->assertStringContainsString("google.charts.load('current', {packages: ['corechart']})", $asset->getOption('content'));
			$this->assertStringContainsString('google.charts.setOnLoadCallback', $asset->getOption('content'));
			$this->assertStringContainsString('DOMContentLoaded', $asset->getOption('content'));
			$this->assertStringNotContainsString('google.load(', $asset->getOption('content'));
			$this->assertStringNotContainsString('</script>', $asset->getOption('content'));
			$contents[] = $asset->getOption('content');
		}

		$this->assertStringContainsString('\\u003C', $contents[0]);
		$this->assertStringContainsString('"label":"Count","type":"number"', $contents[0]);
		$this->assertStringContainsString('"chart-second"', $contents[1]);
	}

	/**
	 * Header detection includes active dependencies but excludes inactive registrations.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testHeaderCheckRecognizesOnlyActiveWamAssets(): void
	{
		$assets = $this->assets();
		$assets->registerScript('dependency', 'https://example.test/dependency.js');
		$assets->registerAndUseScript('active', 'https://example.test/active.js', [], [], ['dependency']);
		$assets->registerScript('inactive', 'https://example.test/inactive.js');
		$assets->registerAndUseStyle('active-style', 'https://example.test/style.css');

		foreach (['ADMIN_HEADERCHECK.php', 'SITE_HEADERCHECK.php'] as $file)
		{
			$class = $this->loadTemplate($file, 'HeaderCheck');
			$checker = new $class();
			$this->assertTrue($checker->js_loaded('dependency.js'));
			$this->assertTrue($checker->js_loaded('active'));
			$this->assertFalse($checker->js_loaded('inactive.js'));
			$this->assertTrue($checker->css_loaded('style.css'));
		}
	}

	/**
	 * Selected view script hooks preserve older media behavior and fork only Joomla six.
	 *
	 * @param   int     $major    Target major.
	 * @param   string  $version  Namespace segment.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testViewScriptSelectsNativeMediaEventsAndPaths(int $major, string $version): void
	{
		$class = 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\' . $version . '\\AdminView\\ViewScript';
		$subject = $this->renderer($class);
		$this->assertSame($major < 6, (new ReflectionMethod($class, 'usesModalMediaListener'))->invoke($subject));
		$this->assertSame($major === 6 ? '/media/com_demo/js/admin/articles.js' : '/administrator/components/com_demo/assets/js/articles.js', (new ReflectionMethod($class, 'listScriptPath'))->invoke($subject, 'articles'));
		$footer = (new ReflectionMethod($class, 'footerScript'))->invoke($subject, 'window.fixture = <?php echo 7; ?>;', 'article');
		$this->assertStringContainsString($major === 6 ? 'addInlineScript' : '<script type="text/javascript">', $footer);
	}

	/**
	 * Real module writers publish assets into media and wire a loadable form field.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testModuleFormAssetsHaveInstallablePathsAndExecutableField(): void
	{
		$assets = $this->assets();
		$assets->registerScript('core', 'core.js');
		$assets->registerScript('jquery', 'jquery.js');
		$folder = $this->createTemporaryDirectory('module-assets');
		$paths = $this->createStub(\VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths::class);
		$paths->method('__get')->willReturnCallback(static fn (string $key): string => $key === 'component_path' ? $folder : dirname(JPATH_COMPONENT_ADMINISTRATOR) . '/admin/compiler/joomla_6');
		$counter = $this->renderer(\VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter::class);
		$file = new \VDM\Joomla\Componentbuilder\Compiler\Utilities\File($counter, $paths);
		$files = new \VDM\Joomla\Componentbuilder\Compiler\Utilities\Files();
		$dispenser = $this->createStub(\VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser::class);
		$dispenser->method('get')->willReturnCallback(static fn (string $first): string => $first === 'css_view' ? '.field { color: red; }' : 'window.fieldLoaded = true;');
		$class = \VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaSix\Structure::class;
		$subject = $this->renderer($class, [
			'dispenser' => $dispenser,
			'folder' => new \VDM\Joomla\Componentbuilder\Compiler\Utilities\Folder($counter, $file),
			'file' => $file, 'files' => $files, 'counter' => $counter,
		]);
		(new \ReflectionProperty($class, 'NamespacePrefix'))->setValue($subject, 'AssetTest');
		$module = (object) [
			'key' => 'module.article', 'class_name' => 'Articles', 'code_name' => 'articles',
			'folder_name' => 'mod_articles', 'folder_path' => $folder, 'namespace' => 'Articles',
			'target_client_namespace' => 'Site', 'config_fields' => ['params' => ['basic' => []]],
		];
		(new ReflectionMethod($class, 'setCssJsForm'))->invoke($subject, $module);
		$this->assertTrue($module->add_scripts_field);
		$this->assertFileExists($folder . '/media/css/Articlesengine.css');
		$this->assertFileExists($folder . '/media/js/Articlesengine.js');
		$this->assertFileExists($folder . '/src/Field/ArticlesengineField.php');
		$this->assertDirectoryDoesNotExist($folder . '/assets');
		$written = $files->get($module->key);
		$this->assertSame(['media/css/Articlesengine.css', 'media/js/Articlesengine.js', 'src/Field/ArticlesengineField.php'], array_column($written, 'zip'));
		$source = file_get_contents($folder . '/src/Field/ArticlesengineField.php');
		$source = str_replace('###BOM###', '', $source);
		eval($source);
		$field = new \AssetTest\Module\Articles\Site\Field\ArticlesengineField();
		$this->assertSame('', (new ReflectionMethod($field, 'getInput'))->invoke($field));
		$this->assertSame('media/mod_articles/css/Articlesengine.css', $assets->getAsset('style', 'mod_articles.form')->getUri(false));
		$this->assertSame('media/mod_articles/js/Articlesengine.js', $assets->getAsset('script', 'mod_articles.form')->getUri(false));
		$this->assertSame(['core', 'jquery'], $assets->getAsset('script', 'mod_articles.form')->getDependencies());
		$this->config()->set('namespace_prefix', 'AssetTest');
		$module->lang_prefix = 'MOD_ARTICLES';
		$placeholder = (new \ReflectionClass(\VDM\Joomla\Componentbuilder\Compiler\Component\Placeholder::class))->newInstanceWithoutConstructor();
		(new \ReflectionProperty($placeholder, 'placeholders'))->setValue($placeholder, []);
		$dynamic = (new \ReflectionClass(\VDM\Joomla\Componentbuilder\Compiler\Creator\FieldsetDynamic::class))->newInstanceWithoutConstructor();
		$fieldset = new \VDM\Joomla\Componentbuilder\Compiler\Creator\FieldsetExtension($placeholder, $dynamic);
		$manifest = $this->renderer(\VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Module\MainXML::class, ['fieldsetextension' => $fieldset]);
		$fields = (new ReflectionMethod($manifest, 'buildConfigFields'))->invoke($manifest, $module);
		$this->assertStringContainsString('type="Articlesengine" addfieldprefix="AssetTest\Module\Articles\Site\Field"', $fields['paramsbasic']);
		$module->file_name = 'mod_articles';
		$module->add_sql = false;
		$module->add_sql_uninstall = false;
		$xml = (new ReflectionMethod($manifest, 'generateFileXml'))->invoke($manifest, $module, []);
		$this->assertStringContainsString('<media destination="mod_articles" folder="media">', $xml);
		$this->assertStringContainsString('<folder>css</folder>', $xml);
		$this->assertStringContainsString('<folder>js</folder>', $xml);
		$this->assertStringNotContainsString('<folder>media</folder>', $xml);
	}

	/**
	 * PHP-bearing footer code executes in its view and restores buffer ownership.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testDynamicViewFooterIsCapturedOnceWithoutSelfDependencies(): void
	{
		$assets = $this->assets();
		$assets->registerAndUseScript('core', 'core.js');
		$assets->registerAndUseScript('jquery', 'jquery.js');
		$assets->registerAndUseScript('project.script', 'project.js', [], [], ['jquery']);
		$class = \VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminView\ViewScript::class;
		$renderer = $this->renderer($class);
		$source = (new ReflectionMethod($class, 'footerScript'))->invoke($renderer, 'window.fixture = <?php echo 7; ?>;', 'article');
		$run = eval('return function () { ?>' . $source . '<?php };');
		$view = $this->view($assets);
		$level = ob_get_level();
		ob_start();
		try
		{
			$run->call($view);
			$run->call($view);
			$this->assertSame('', trim(ob_get_contents()));
			$this->assertSame($level + 1, ob_get_level());
		}
		finally
		{
			ob_end_clean();
		}

		$asset = $assets->getAsset('script', 'com_demo.admin.article.footer');
		$this->assertSame('window.fixture = 7;', trim($asset->getOption('content')));
		$this->assertSame(['core', 'jquery', 'project.script'], $asset->getDependencies());
		$this->assertCount(4, $assets->getAssets('script', true));
	}

	/**
	 * Create a real asset graph behind the current application boundary.
	 *
	 * @return  WebAssetManager
	 * @since   6.2.0
	 */
	private function assets(): WebAssetManager
	{
		$assets = new WebAssetManager(new WebAssetRegistry());
		$document = $this->createStub(HtmlDocument::class);
		$document->method('getWebAssetManager')->willReturn($assets);
		$document->method('getHeadData')->willReturn(['scripts' => [], 'styleSheets' => []]);
		$app = $this->createStub(CMSApplication::class);
		$app->method('getDocument')->willReturn($document);
		$this->setJoomlaApplication($app);

		return $assets;
	}

	/**
	 * A view boundary exposes the same real Web Asset Manager as the application.
	 *
	 * @param   WebAssetManager  $assets  Asset graph.
	 *
	 * @return  object
	 * @since   6.2.0
	 */
	private function view(WebAssetManager $assets): object
	{
		$document = $this->createStub(HtmlDocument::class);
		$document->method('getWebAssetManager')->willReturn($assets);

		return new class($document)
		{
			/**
			 * The view document boundary.
			 *
			 * @var    HtmlDocument
			 * @since  6.2.0
			 */
			private HtmlDocument $document;

			/**
			 * Keep the document in the view's own scope.
			 *
			 * @param   HtmlDocument  $document  Native document.
			 * @since   6.2.0
			 */
			public function __construct(HtmlDocument $document)
			{
				$this->document = $document;
			}

			/**
			 * Expose the native document to generated view code.
			 *
			 * @return  HtmlDocument
			 * @since   6.2.0
			 */
			public function getDocument(): HtmlDocument
			{
				return $this->document;
			}
		};
	}

	/**
	 * Resolve Power imports before executing emitted code.
	 *
	 * @param   string  $code  Generated statements.
	 *
	 * @return  \Closure
	 * @since   6.2.0
	 */
	private function compileClosure(string $code): \Closure
	{
		$source = $this->resolve('<?php namespace ' . __NAMESPACE__ . ';' . PHP_EOL . 'use stdClass as Factory;' . PHP_EOL . 'return function () {' . $code . '};');

		return eval(substr($source, 5));
	}

	/**
	 * Resolve Joomla Power tokens through the actual compiler pipeline.
	 *
	 * @param   string  $source  Generated PHP.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	private function resolve(string $source): string
	{
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['39403062-84fb-46e0-bac4-0023f766e827', 0, (object) ['_namespace' => 'Joomla\\CMS', 'class_name' => 'Factory', 'type' => 'class']],
			['eecc143e-b5cf-4c33-ba4d-97da1df61422', 0, (object) ['_namespace' => 'Joomla\\CMS\\Uri', 'class_name' => 'Uri', 'type' => 'class']],
		]);
		$injector = new Injector($power, new Extractor($this->createStub(DatabaseInterface::class), 6), new Parser(), $this->placeholder());
		$source = $injector->power($source);
		$this->assertStringNotContainsString('Joomla___', $source);

		return $source;
	}

	/**
	 * Materialize one maintained template without replacing Power tokens by hand.
	 *
	 * @param   string  $file   Template filename.
	 * @param   string  $class  Generated class name.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	private function loadTemplate(string $file, string $class): string
	{
		$source = file_get_contents(dirname(JPATH_COMPONENT_ADMINISTRATOR) . '/admin/compiler/joomla_6/' . $file);
		$source = substr($source, strpos($source, '###BOM###') + strlen('###BOM###'));
		$source = str_replace(['###NAMESPACEPREFIX###', '###ComponentNamespace###', '###component###'], ['AssetTest', 'Demo', 'demo'], $source);
		$namespace = $class === 'Chartbuilder' ? __NAMESPACE__ . '\\AssetFixture' : 'AssetTest\\Component\\Demo\\' . (str_starts_with($file, 'ADMIN') ? 'Administrator' : 'Site') . '\\Helper';
		$qualified = $namespace . '\\' . $class;

		if (!class_exists($qualified, false))
		{
			$source = $class === 'Chartbuilder' ? PHP_EOL . 'namespace ' . $namespace . ';' . $source : $source;
			$source = $this->resolve('<?php' . PHP_EOL . $source);
			eval(substr($source, 5));
		}

		return $qualified;
	}
}
