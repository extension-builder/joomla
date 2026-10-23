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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Component\JoomlaSix;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Language\Language;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\CMS\WebAsset\WebAssetRegistry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Compiler\Component\Structuresingle;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Component\SettingsInterface;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File as CompilerFile;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Files;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Folder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Executable loading and packaging contracts for the Joomla 6 asset templates.
 *
 * @since  6.2.0
 */
#[CoversNothing]
final class AssetsTemplateTest extends ArchitectureTestCase
{
	/**
	 * Model arrays activate unique assets in dependency order, including repeated URIs.
	 *
	 * @param   string  $template  The HTML view template filename.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('htmlTemplates')]
	public function testModelAssetsRemainOrderedAndDeduplicated(string $template): void
	{
		$wa = new WebAssetManager(new WebAssetRegistry());
		$wa->registerScript('core', 'https://example.test/subdir/core.js');
		$styles = ['/media/com_demo/css/admin/first.css?theme=default#local', 'https://example.invalid/second.css?theme=dark#sheet', 'media/com_demo/css/admin/first.css?theme=default#local', '//example.invalid/last.css'];
		$scripts = ['/media/com_demo/js/admin/first.js?view=edit#ready', 'https://example.invalid/second.js?mode=edit#script', 'media/com_demo/js/admin/first.js?view=edit#ready', '//example.invalid/last.js'];
		$this->runAssetCode($template, $wa, $styles, $scripts);
		$styleAssets = $wa->getAssets('style', true);
		$scriptAssets = $wa->getAssets('script', true);
		$this->assertSame(
			['https://example.test/subdir/media/com_demo/css/admin/first.css?theme=default#local', $styles[1], $styles[3]],
			array_values(array_map(static fn($asset): string => $asset->getUri(), $styleAssets))
		);
		$this->assertSame(
			['https://example.test/subdir/core.js', 'https://example.test/subdir/media/com_demo/js/admin/first.js?view=edit#ready', $scripts[1], $scripts[3]],
			array_values(array_map(static fn($asset): string => $asset->getUri(), $scriptAssets))
		);
		foreach ([$styleAssets, $scriptAssets] as $assets)
		{
			$seen = [];
			foreach ($assets as $name => $asset)
			{
				if ($name !== 'core')
				{
					$this->assertSame('auto', $asset->getVersion());
				}
				foreach ($asset->getDependencies() as $dependency)
				{
					$this->assertContains($dependency, $seen, 'Dependencies must precede the dependent asset.');
				}
				$seen[] = $name;
			}
		}

		// A second preparation reuses the original registration objects.
		$this->runAssetCode($template, $wa, $styles, $scripts);
		$this->assertSame($styleAssets, $wa->getAssets('style', true));
		$this->assertSame($scriptAssets, $wa->getAssets('script', true));
	}

	/**
	 * Empty model arrays leave all assets inactive.
	 *
	 * @param   string  $template  The HTML view template filename.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('htmlTemplates')]
	public function testEmptyModelAssetsDoNotAddDependencies(string $template): void
	{
		$wa = new WebAssetManager(new WebAssetRegistry());
		$wa->registerScript('core', 'https://example.test/subdir/core.js');
		$this->runAssetCode($template, $wa, [], []);
		$this->assertSame([], $wa->getAssets('style', true));
		$this->assertSame([], $wa->getAssets('script', true));
	}

	/**
	 * Real structure writers copy configured assets into installable component media.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSettingsBuildMediaFilesForBothClients(): void
	{
		$manifest = json_decode($this->template('settings.json'), false, 512, JSON_THROW_ON_ERROR);
		$static = new \stdClass();
		$dynamic = new \stdClass();
		foreach ($manifest->move->static as $name => $definition)
		{
			if (preg_match('/\.(js|css)$/D', $name) === 1)
			{
				$static->{$name} = $definition;
			}
		}
		foreach ($manifest->move->dynamic as $client => $definitions)
		{
			$dynamic->{$client} = new \stdClass();
			foreach ($definitions as $name => $definition)
			{
				if (preg_match('/\.(js|css)$/D', $name) === 1)
				{
					$dynamic->{$client}->{$name} = $definition;
				}
			}
		}
		$settings = $this->createStub(SettingsInterface::class);
		$settings->method('exists')->willReturn(true);
		$settings->method('single')->willReturn($static);
		$settings->method('multiple')->willReturn($dynamic);
		$settings->method('standardFolder')->willReturnCallback(
			static fn(string $folder): bool => in_array($folder, ['admin', 'site', 'media'], true)
		);
		$settings->method('standardRootFile')->willReturn(false);
		$package = $this->createTemporaryDirectory('component');
		$template = dirname(JPATH_COMPONENT_ADMINISTRATOR) . '/admin/compiler/joomla_6';
		$paths = $this->createStub(Paths::class);
		$paths->method('__get')->willReturnCallback(
			static fn(string $name): string => $name === 'component_path' ? $package : $template
		);
		$counter = $this->renderer(Counter::class);
		$file = new CompilerFile($counter, $paths);
		$files = new Files();
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->never())->method('enqueueMessage');
		$single = $this->renderer(Structuresingle::class, [
			'settings' => $settings, 'paths' => $paths, 'counter' => $counter,
			'files' => $files, 'folder' => new Folder($counter, $file), 'app' => $app,
		]);
		$this->assertTrue($single->build());
		$this->assertEqualsCanonicalizing(
			['admin.css', 'admin.js', 'site.css', 'site.js', 'dkim.js'],
			array_keys((array) $static)
		);
		foreach ($static as $name => $definition)
		{
			$this->assertMatchesRegularExpression('~^c0mp0n3nt/media/(?:css|js)/(?:admin|site)$~', $definition->path);
			$this->assertFileEquals($template . '/' . $name, str_replace('c0mp0n3nt', $package, $definition->path) . '/' . $name);
		}
		$structure = new Structure($this->placeholder(), $settings, $paths, $counter, $file, $files, $app);
		foreach ($dynamic as $client => $definitions)
		{
			$types = [];
			foreach ($definitions as $name => $definition)
			{
				$this->assertMatchesRegularExpression('~^c0mp0n3nt/media/(?:css|js)/' . ($client === 'site' ? 'site' : 'admin') . '$~', $definition->path, $name);
				$types[$definition->type] = true;
			}
			foreach (array_keys($types) as $type)
			{
				$view = str_replace('_', '', $client . $type);
				$this->assertTrue($structure->build([$client => $view], $type));
				foreach ($files->get('dynamic.' . $view) as $written)
				{
					$this->assertFileExists($written['path']);
					$this->assertStringStartsWith('media/', $written['zip']);
				}
			}
		}
		$this->assertFileExists($package . '/media/js/admin/adminsingle.js');
		$this->assertFileExists($package . '/media/js/site/siteedit.js');
		$this->assertDirectoryDoesNotExist($package . '/admin/assets');
		$this->assertDirectoryDoesNotExist($package . '/site/assets');
		$xml = simplexml_load_string(str_replace('###component###', 'demo', $this->template('component.xml')));
		$this->assertNotFalse($xml);
		$this->assertSame('com_demo', (string) $xml->media['destination']);
		$this->assertSame('media', (string) $xml->media['folder']);
		$folders = array_map('strval', iterator_to_array($xml->media->folder, false));
		$this->assertContains('js', $folders);
		$this->assertContains('css', $folders);
	}

	/**
	 * Every default model asset corresponds to a configured package destination.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testModelUrisMatchConfiguredPackageDestinations(): void
	{
		$settings = json_decode($this->template('settings.json'), true, 512, JSON_THROW_ON_ERROR);
		foreach (self::modelTemplates() as $model => [$client, $assets])
		{
			$source = $this->template($model);
			$uris = [];
			foreach (['styles', 'scripts'] as $property)
			{
				$this->assertSame(1, preg_match('/protected array \$' . $property . '\s*=\s*(\[[\s\S]*?\]);/', $source, $match));
				$uris = [...$uris, ...eval('return ' . $match[1] . ';')];
			}
			$expected = [];
			foreach ($assets as [$scope, $name, $basename])
			{
				$entry = $scope === 'static' ? $settings['move']['static'][$name] : $settings['move']['dynamic'][$client][$name];
				$expected[] = str_replace('c0mp0n3nt/media', 'media/com_###component###', $entry['path']) . '/' . $basename;
			}
			$this->assertSame($expected, $uris, $model);
		}
	}

	/**
	 * Both import stages register their dynamic handlers without writing script tags.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testImportStagesRegisterTheirInlineHandlersThroughTheDocument(): void
	{
		$language = $this->createStub(Language::class);
		$language->method('_')->willReturnCallback(static fn(string $key): string => $key);
		$this->setJoomlaFactoryProperty('language', $language);
		$source = $this->template('default_import.php');
		$start = strpos($source, '$wa = ');
		$source = substr($source, $start, strpos($source, '<div id="installer-import"') - $start);
		$source = str_replace([
			'Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power',
			'Super___0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power',
			'###component###', '###COMPONENT###', '[[[component]]]',
		], ['\\Joomla\\CMS\\Language\\Text', '\\VDM\\Joomla\\Utilities\\ArrayHelper', 'demo', 'DEMO', 'demo'], $source);
		$wa = new WebAssetManager(new WebAssetRegistry());
		foreach (['jquery', 'bootstrap.popover', 'core', 'keepalive', 'form.validate'] as $name)
		{
			$wa->registerScript($name, 'https://example.invalid/' . $name . '.js');
		}
		$view = new class
		{
			/**
			 * The document collecting import assets.
			 *
			 * @var    HtmlDocument
			 * @since  6.2.0
			 */
			public HtmlDocument $document;

			/**
			 * Whether the uploaded file is ready for field mapping.
			 *
			 * @var    bool
			 * @since  6.2.0
			 */
			public bool $hasPackage = false;

			/**
			 * Required import columns.
			 *
			 * @var    array
			 * @since  6.2.0
			 */
			public array $headerList = ['title' => 'Title', 'alias' => 'Alias'];

			/**
			 * Return the document used by the emitted template.
			 *
			 * @return  HtmlDocument
			 * @since   6.2.0
			 */
			public function getDocument(): HtmlDocument
			{
				return $this->document;
			}

			/**
			 * Execute the PHP fragment with the template's view state.
			 *
			 * @param   string  $source  Template registration source.
			 *
			 * @return  string  Any unexpectedly rendered markup.
			 * @since   6.2.0
			 */
			public function run(string $source): string
			{
				ob_start();
				try
				{
					eval($source);

					return ob_get_contents();
				}
				finally
				{
					ob_end_clean();
				}
			}
		};
		$view->document = new HtmlDocument(['webAssetManager' => $wa]);
		$this->assertSame('', trim($view->run($source)));
		$assets = $wa->getAssets('script', true);
		$this->assertCount(6, $assets);
		$import = $assets['com_demo.import'];
		$this->assertSame(['core'], $import->getDependencies());
		$this->assertStringContainsString('Joomla.submitbutton4 = function()', $import->getOption('content'));
		$this->assertStringNotContainsString('Joomla.continueImport =', $import->getOption('content'));
		$this->assertStringNotContainsString('<script', $source);
		$view->hasPackage = true;
		$this->assertSame('', trim($view->run($source)));
		$assets = $wa->getAssets('script', true);
		$this->assertCount(6, $assets);
		$content = $assets['com_demo.import']->getOption('content');
		$this->assertStringContainsString('Joomla.continueImport = function()', $content);
		$this->assertStringContainsString('var therequired = ["vdm_title", "vdm_alias"]', $content);
		$this->assertStringNotContainsString('Joomla.submitbutton4 =', $content);

		// The custom import entry point activates the same assets and leaves its body in view scope.
		$customSource = $this->template('default_import_custom.php');
		$this->assertStringEndsWith('?>###IMPORT_DEFAULT_VIEW_CUSTOM###', trim($customSource));
		$customSource = substr($customSource, strpos($customSource, '$wa = '));
		$customSource = str_replace(
			'###IMPORT_DEFAULT_VIEW_CUSTOM###',
			'<section><?php echo $this->headerList["title"]; ?></section>',
			$customSource
		);
		$wa->disableScript('com_demo.import');
		$this->assertSame('<section>Title</section>', trim($view->run($customSource)));
		$this->assertCount(5, $wa->getAssets('script', true));

	}

	/**
	 * HTML templates that load model asset arrays.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.2.0
	 */
	public static function htmlTemplates(): iterable
	{
		foreach (['ADMIN_VIEW_HTML.php', 'ADMIN_VIEWS_HTML.php', 'DASH_VIEW_HTML.php', 'CUSTOM_ADMIN_VIEW_HTML.php', 'CUSTOM_ADMIN_VIEWS_HTML.php', 'SITE_ADMIN_VIEW_HTML.php', 'SITE_VIEW_HTML.php', 'SITE_VIEWS_HTML.php'] as $name)
		{
			yield $name => [$name];
		}
	}

	/**
	 * Pair model asset names with the settings entries that install them.
	 *
	 * @return  array<string, array>
	 * @since   6.2.0
	 */
	private static function modelTemplates(): array
	{
		return [
			'ADMIN_VIEW_MODEL.php' => ['admin', [['static', 'admin.css', 'admin.css'], ['dynamic', 'view.css', '###view###.css'], ['static', 'admin.js', 'admin.js'], ['dynamic', 'module_forms.js', '###view###.js']]],
			'ADMIN_VIEWS_MODEL.php' => ['admin', [['static', 'admin.css', 'admin.css'], ['dynamic', 'views.css', '###views###.css'], ['static', 'admin.js', 'admin.js']]],
			'DASH_MODEL.php' => ['admin', [['static', 'admin.css', 'admin.css'], ['dynamic', 'dashboard.css', 'dashboard.css'], ['static', 'admin.js', 'admin.js']]],
			'CUSTOM_ADMIN_VIEW_MODEL.php' => ['custom_admin', [['static', 'admin.css', 'admin.css'], ['dynamic', 'view_custom_admin.css', '###sview###.css'], ['static', 'admin.js', 'admin.js']]],
			'CUSTOM_ADMIN_VIEWS_MODEL.php' => ['custom_admin', [['static', 'admin.css', 'admin.css'], ['dynamic', 'views_custom_admin.css', '###sviews###.css'], ['static', 'admin.js', 'admin.js']]],
			'SITE_VIEW_MODEL.php' => ['site', [['static', 'site.css', 'site.css'], ['dynamic', 'view_site.css', '###sview###.css'], ['static', 'site.js', 'site.js']]],
			'SITE_VIEWS_MODEL.php' => ['site', [['static', 'site.css', 'site.css'], ['dynamic', 'views_site.css', '###sview###.css'], ['static', 'site.js', 'site.js']]],
			'SITE_ADMIN_VIEW_MODEL.php' => ['site', [['static', 'site.css', 'site.css'], ['dynamic', 'view_site_edit.css', '###view###.css'], ['static', 'site.js', 'site.js'], ['dynamic', 'module_forms.js', '###view###.js']]],
		];
	}

	/**
	 * Execute emitted asset loops in view scope against the real manager.
	 *
	 * @param   string           $template  The template filename.
	 * @param   WebAssetManager  $wa        The manager collecting assets.
	 * @param   array            $styles    Ordered stylesheet URIs.
	 * @param   array            $scripts   Ordered JavaScript URIs.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	private function runAssetCode(string $template, WebAssetManager $wa, array $styles, array $scripts): void
	{
		$this->assertSame(1, preg_match('~// add styles(?<code>.*?)\n\t\}~s', $this->template($template), $match));
		$code = preg_replace('/###[A-Z_]+###/', '', $match['code']);
		$code = str_replace('Joomla___eecc143e_b5cf_4c33_ba4d_97da1df61422___Power::root()', "'https://example.test/subdir/'", $code);
		$view = new class
		{
			/**
			 * Model stylesheet URIs.
			 *
			 * @var    array
			 * @since  6.2.0
			 */
			public array $styles = [];

			/**
			 * Model JavaScript URIs.
			 *
			 * @var    array
			 * @since  6.2.0
			 */
			public array $scripts = [];

			/**
			 * Run the template's PHP in its expected scope.
			 *
			 * @param   string           $code  Asset-loading statements.
			 * @param   WebAssetManager  $wa    The real manager.
			 *
			 * @return  void
			 * @since   6.2.0
			 */
			public function run(string $code, WebAssetManager $wa): void
			{
				eval($code);
			}
		};
		$view->styles = $styles;
		$view->scripts = $scripts;
		$view->run($code, $wa);
	}

	/**
	 * Read a file from the established Joomla 6 template directory.
	 *
	 * @param   string  $name  The filename.
	 *
	 * @return  string  The template bytes.
	 * @since   6.2.0
	 */
	private function template(string $name): string
	{
		return file_get_contents(dirname(JPATH_COMPONENT_ADMINISTRATOR) . '/admin/compiler/joomla_6/' . $name);
	}
}
