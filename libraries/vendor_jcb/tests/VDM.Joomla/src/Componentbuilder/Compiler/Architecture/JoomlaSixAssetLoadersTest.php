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
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GoogleChart;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView;
use VDM\Joomla\Componentbuilder\Compiler\Service\Library;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
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
	 * @param   string  $indent  Configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('indentations')]
	public function testLibraryAssetsResolvePowerImportsAndMaintainOrder(string $indent): void
	{
		$this->configureIndentation($indent);
		$assets = $this->assets();
		$include = new \VDM\Joomla\Componentbuilder\Compiler\Library\JoomlaSix\IncludeHelper();
		$uris = ['/media/com_demo/js/first.js?rev=2#code', 'https://example.test/second.js?v=1', '/media/com_demo/js/first.js?rev=2#code', '//example.test/third.js'];
		$code = implode(PHP_EOL, array_map($include->get(...), $uris));
		$code .= PHP_EOL . $include->get('/media/com_demo/css/first.css?theme=dark');
		$code .= PHP_EOL . $include->get('https://example.test/second.css');
		$this->assertStringContainsString(PHP_EOL . $indent . '$jcbAssetManager->registerScript', $code);
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
	 * @param   string  $indent  Configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('indentations')]
	public function testFootableRuntimeHasOrderedDependenciesAndNoDuplicateRegistration(string $indent): void
	{
		$this->configureIndentation($indent);
		foreach ([2, 3] as $version)
		{
			$assets = $this->assets();
			$assets->registerScript('jquery', 'jquery.js');
			$assets->registerScript('jquery-noconflict', 'jquery-noconflict.js', [], [], ['jquery']);
			$this->config()->set('footable_version', $version);
			$renderer = $this->renderer(\VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminView\FootableScripts::class);
			$code = $renderer->get(true);
			$this->assertStringContainsString(PHP_EOL . str_repeat($indent, 2) . '$jcbFootableAssets', $code);
			$this->assertStringNotContainsString('array (', $code, 'Dependency literals must not introduce fixed indentation.');
			$run = eval('return function () {' . $code . '};');
			$view = $this->view($assets);
			$run->call($view);
			$scripts = $assets->getAssets('script', true);
			$last = end($scripts);
			$this->assertSame('com_demo.footable.init', $last->getName());
			$this->assertStringContainsString('jQuery(document).ready', $last->getOption('inline') ? $last->getOption('content') : '');
			$this->assertStringContainsString(PHP_EOL . $indent . "jQuery('.footable').footable();", $last->getOption('content'));

			if ($indent !== "\t")
			{
				$this->assertStringNotContainsString("\t", $code);
				$this->assertStringNotContainsString("\t", $last->getOption('content'));
			}
			$this->assertCount($version === 2 ? 7 : 4, $scripts);
			$this->assertCount(2, $assets->getAssets('style', true));
			$run->call($view);
			$this->assertCount(count($scripts), $assets->getAssets('script', true));
		}
	}

	/**
	 * Generated inline PHP and JavaScript both use the configured indentation.
	 *
	 * @param   string  $indent  Configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('indentations')]
	public function testInlineAssetEmittersRespectIndentationAndDebugComments(string $indent): void
	{
		$this->configureIndentation($indent);
		$assets = $this->assets();
		$assets->registerScript('core', 'core.js');
		$view = $this->view($assets);
		$definition = ['settings' => (object) ['add_fadein' => 1, 'default' => '<form></form>', 'code' => 'article']];
		$overlay = $this->renderer(\VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminView\FadeInEffect::class);
		$submit = $this->renderer(\VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\CustomView\SubmitButtonScript::class);
		$toolbar = $this->renderer(\VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\CustomButtons::class);
		$debug = new ReflectionProperty(Line::class, 'add');
		$this->assertStringNotContainsString('Compiler\\Architecture\\AdminView\\FadeInEffect', $overlay->get($definition));
		$debug->setValue(null, true);
		$source = $overlay->get($definition) . $submit->get($definition);
		$this->assertStringNotContainsString('<<<', $source);
		$this->assertStringContainsString(PHP_EOL . $indent . '. PHP_EOL . ', $source);
		$run = eval('return function () { ?>' . $source . '<?php };');
		ob_start();

		try
		{
			$run->call($view);
			$this->assertSame('<div id="demo_loader" style="display: none;">', trim(ob_get_contents()));
		}
		finally
		{
			ob_end_clean();
		}

		$buttons = $toolbar->javascript();
		$this->assertStringContainsString(PHP_EOL . str_repeat($indent, 2) . '$jcbToolbarScript', $buttons);
		(eval('return function () {' . $buttons . '};'))->call($view);
		$scripts = $assets->getAssets('script', true);
		$this->assertCount(4, $scripts);
		$overlayScript = $scripts['com_demo.loading-overlay']->getOption('content');
		$this->assertMatchesRegularExpression('/\/\/ \[VDM\\\\Joomla\\\\Componentbuilder\\\\Compiler\\\\Architecture\\\\AdminView\\\\FadeInEffect \d+\] create loading overlay/', $overlayScript);
		$this->assertStringContainsString(PHP_EOL . $indent . 'const initialise', $overlayScript);
		$this->assertStringContainsString(PHP_EOL . str_repeat($indent, 3) . 'parent.history.back();', $scripts['com_demo.article.submit']->getOption('content'));
		$this->assertStringContainsString(PHP_EOL . str_repeat($indent, 2) . 'Joomla.submitform(task);', $scripts['com_demo.toolbar-buttons']->getOption('content'));

		foreach (['com_demo.loading-overlay', 'com_demo.article.submit', 'com_demo.toolbar-buttons'] as $name)
		{
			$this->assertSame(['core'], $scripts[$name]->getDependencies());

			if ($indent !== "\t")
			{
				$this->assertStringNotContainsString("\t", $scripts[$name]->getOption('content'));
			}
		}

		if ($indent !== "\t")
		{
			$this->assertStringNotContainsString("\t", $source . $buttons);
		}
	}

	/**
	 * Compiler indentation configurations supported by emitted asset code.
	 *
	 * @return  array
	 * @since   6.2.0
	 */
	public static function indentations(): array
	{
		return ['tabs' => ["\t"], 'two spaces' => ['  '], 'four spaces' => ['    ']];
	}

	/**
	 * Reconfigure the same utility used by compiler-owned generated lines.
	 *
	 * @param   string  $indent  Configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	private function configureIndentation(string $indent): void
	{
		(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, $indent);
		(new ReflectionProperty(Indent::class, 'bucket'))->setValue(null, []);
	}

	/**
	 * The active chart flag selects the official loader and native helper.
	 *
	 * @param   string  $indent  Configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('indentations')]
	public function testChartLoaderKeepsConditionalBehavior(string $indent): void
	{
		$this->configureIndentation($indent);
		$charts = new GoogleChart();
		$view = ['settings' => (object) ['code' => 'articles']];
		$subject = $this->renderer(\VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View\GoogleChartLoader::class, ['googlechart' => $charts]);
		$this->assertSame('', $subject->get($view));
		$charts->set($this->config()->build_target . '.articles', true);
		$code = $subject->get($view);
		$this->assertStringContainsString(PHP_EOL . str_repeat($indent, 2) . '$jcbChartAssets = ', $code);
		$this->assertStringContainsString(PHP_EOL . str_repeat($indent, 3) . '$jcbChartAssets->registerScript', $code);
		$jquery = new \VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View\Jquery();
		$this->assertStringStartsWith(PHP_EOL . str_repeat($indent, 2) . '$this->getDocument()', $jquery->get($view));
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
	 * Reusing a chart instance preserves every draw and its own data snapshot.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testRepeatedChartDrawsKeepIndependentWamAssets(): void
	{
		$assets = $this->assets();
		$class = $this->loadTemplate('ADMIN_CHARTBUILDER.php', 'Chartbuilder');
		$chart = new $class('LineChart');
		$count = $class::$_count;
		$chart->load([['Label', 'Count'], ['First', 4]], 'array');
		$this->assertSame('', $chart->draw('first-target', ['width' => 600]));
		$chart->load([['Label', 'Count'], ['Second', 9]], 'array');
		$this->assertSame('', $chart->draw('second-target', ['width' => 800]));
		$this->assertSame($count, $class::$_count, 'The public instance counter must not become a draw counter.');
		$scripts = $assets->getAssets('script', true);
		$this->assertCount(3, $scripts, 'Both draws must survive alongside the shared loader.');
		unset($scripts['google.charts']);
		$draws = array_values($scripts);
		$this->assertNotSame($draws[0]->getName(), $draws[1]->getName());
		$this->assertSame(['google.charts'], $draws[0]->getDependencies());
		$this->assertSame(['google.charts'], $draws[1]->getDependencies());
		$this->assertStringContainsString('"first-target"', $draws[0]->getOption('content'));
		$this->assertStringContainsString('"v":"First"', $draws[0]->getOption('content'));
		$this->assertStringContainsString('"v":4', $draws[0]->getOption('content'));
		$this->assertStringNotContainsString('"second-target"', $draws[0]->getOption('content'));
		$this->assertStringContainsString('"second-target"', $draws[1]->getOption('content'));
		$this->assertStringContainsString('"v":"Second"', $draws[1]->getOption('content'));
		$this->assertStringContainsString('"v":9', $draws[1]->getOption('content'));
		$this->assertStringNotContainsString('"first-target"', $draws[1]->getOption('content'));
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
		$this->assertSame(PHP_EOL . PHP_EOL . '<script type="text/javascript">' . PHP_EOL
			. 'window.fixture = <?php echo 7; ?>;' . PHP_EOL . '</script>', $footer);
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
	 * PHP-bearing footer code executes in its view at the authored body position.
	 *
	 * @param   string  $indent  Configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('indentations')]
	public function testDynamicViewFooterRendersAfterMarkupWithoutHeadRegistration(string $indent): void
	{
		$this->configureIndentation($indent);
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
			echo '<form id="adminForm"></form>';
			$run->call($view);
			$this->assertSame('<form id="adminForm"></form>' . PHP_EOL
				. '<script type="text/javascript">' . PHP_EOL
				. 'window.fixture = 7;' . PHP_EOL . '</script>', ob_get_contents());
			$this->assertSame($level + 1, ob_get_level());
		}
		finally
		{
			ob_end_clean();
		}

		$this->assertSame(['core', 'jquery', 'project.script'], array_keys($assets->getAssets('script', true)));
	}

	/**
	 * Module CSS retains WAM loading while authored field JavaScript follows its body.
	 *
	 * @param   string  $indent  Configured indentation unit.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('indentations')]
	public function testModuleFooterPreservesBodyPlacementAndNativeCss(string $indent): void
	{
		$this->configureIndentation($indent);
		$assets = $this->assets();
		$class = \VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Module\Template::class;
		$module = (object) ['folder_name' => 'mod_articles', 'default' => '<div id="module">Article</div>'];
		$payload = "const authored = <?php echo 7; ?>;\n  window.moduleValue = authored;";
		$dispenser = $this->createMock(\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Customcode\DispenserInterface::class);
		$dispenser->expects($this->exactly(2))->method('get')->willReturnCallback(
			function ($first, $second, $prefix, $note, $unset, $default, $suffix) use ($payload): string
			{
				$this->assertSame('module.article', $second);
				$this->assertSame(['', '', true, null, ''], [$prefix, $note, $unset, $default, $suffix]);
				return $first === 'css_views' ? '#module { color: red; }' : $payload;
			}
		);
		$renderer = $this->renderer($class, ['dispenser' => $dispenser]);
		$source = $renderer->default($module, 'module.article');
		$this->assertStringContainsString($payload, $source);
		$this->assertStringContainsString(PHP_EOL . str_repeat($indent, 2) . 'ob_end_clean();', $source);
		$resolved = $this->resolve('<?php namespace ' . __NAMESPACE__ . ';' . PHP_EOL . 'use stdClass as Factory;' . PHP_EOL . 'return function () { ?>' . $source . '<?php };');
		$run = eval(substr($resolved, 5));
		$level = ob_get_level();
		ob_start();

		try
		{
			$run();
			$this->assertSame('<div id="module">Article</div>' . PHP_EOL . PHP_EOL
				. '<script type="text/javascript">const authored = 7;'
				. "\n  window.moduleValue = authored;" . PHP_EOL . '</script>', trim(ob_get_contents()));
			$this->assertSame($level + 1, ob_get_level());
		}
		finally
		{
			ob_end_clean();
		}

		$this->assertSame([], $assets->getAssets('script', true));
		$styles = $assets->getAssets('style', true);
		$this->assertCount(1, $styles);
		$style = reset($styles);
		$this->assertSame('#module { color: red; }', trim($style->getOption('content')));
		$this->assertSame([], $style->getDependencies());
	}

	/**
	 * The provider migrates only Joomla six overlay scripts while retaining markup.
	 *
	 * @param   int     $major    Target major.
	 * @param   string  $version  Namespace segment.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testFadeInEffectUsesTargetSelectedWamOutput(int $major, string $version): void
	{
		$this->config()->set('joomla_version', $major);
		$container = new Container();
		$container->set('Config', $this->config());
		$container->registerServiceProvider(new ArchitectureView());
		$renderer = $container->get('Architecture.AdminView.FadeInEffect');
		$shared = new \VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\FadeInEffect($this->config());
		$definition = ['settings' => (object) ['add_fadein' => 1]];
		$code = $renderer->get($definition);
		$this->assertSame('<div id="demo_loader">', $renderer->get(['settings' => (object) ['add_fadein' => 0]]));

		if ($major !== 6)
		{
			$this->assertSame($shared::class, $renderer::class);
			$this->assertSame($shared->get($definition), $code);
			return;
		}

		$this->assertSame(\VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\AdminView\FadeInEffect::class, $renderer::class);
		$this->assertSame($renderer, $container->get('Architecture.AdminView.J6.FadeInEffect'));
		$this->assertStringNotContainsString('<script', $code);
		$assets = $this->assets();
		$assets->registerScript('core', 'core.js');
		$run = eval('return function () { ?>' . $code . '<?php };');
		ob_start();
		try
		{
			$run->call($this->view($assets));
			$this->assertSame('<div id="demo_loader" style="display: none;">', trim(ob_get_contents()));
		}
		finally
		{
			ob_end_clean();
		}

		$asset = $assets->getAsset('script', 'com_demo.loading-overlay');
		$this->assertSame(['core'], $asset->getDependencies());
		$this->assertStringContainsString("document.readyState === 'complete'", $asset->getOption('content'));
		$this->assertStringContainsString("document.addEventListener('DOMContentLoaded', initialise", $asset->getOption('content'));
		$this->assertStringContainsString("window.addEventListener('load'", $asset->getOption('content'));
		$this->assertStringContainsString('document.body.appendChild(loadingDiv);', $asset->getOption('content'));
		$this->assertStringContainsString("loadingDiv.style.display = 'none';", $asset->getOption('content'));
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
