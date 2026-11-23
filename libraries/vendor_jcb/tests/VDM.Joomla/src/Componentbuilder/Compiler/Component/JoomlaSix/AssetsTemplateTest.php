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
use Joomla\Registry\Registry as JoomlaRegistry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View\LibrariesLoader;
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
	 * View-local paths follow model styles and retain library dependency seeds.
	 *
	 * @param   string  $template  The site or custom-administrator template.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('documentTemplates')]
	public function testCompleteDocumentBlockOrdersViewPathsAndModelAssets(string $template): void
	{
		$wa = $this->assetManager();
		$wa->registerStyle('library.style', 'https://example.invalid/library.css');
		$wa->registerScript('library.script', 'https://example.invalid/library.js');
		$viewPath = 'media/com_componentbuilder/js/admin/compiler.js';
		$markedPath = 'administrator/components/com_componentbuilder/custom/marked.js';
		$modelPath = 'media/com_componentbuilder/js/admin/admin.js';
		$viewName = 'jcb.script.29bd167572d7e7dec97b195cf9778ec23f524e241839579e68e7a48ab9fbd505';
		$markedName = 'jcb.script.' . hash('sha256', $markedPath);
		$modelName = 'jcb.script.' . hash('sha256', $modelPath);
		$result = $this->runDocumentAssetCode(
			$template,
			$wa,
			['/media/com_demo/css/view.css'],
			[$modelPath, '/' . $viewPath, '/' . $markedPath, $modelPath],
			[
				'VIEW_SCRIPT_ENTRY' => "\n\t\t\t// Add View JavaScript File\n\t\t\t'" . $viewPath . "',",
				'VIEW_SCRIPT_PATHS' => "\n\t\t\t'" . $markedPath . "',",
				'VIEW_SCRIPT_DEPENDENCIES' => "\n\t\t\t'jquery',",
			],
			['style' => ['library.style', 'library.style'], 'script' => ['library.script', 'core', 'library.script']]
		);
		$this->assertSame(['core', 'jquery', 'library.script'], $wa->getAsset('script', $viewName)->getDependencies());
		$this->assertSame(['core', 'jquery', 'library.script', $viewName], $wa->getAsset('script', $markedName)->getDependencies());
		$this->assertSame(['core', 'jquery', 'library.script', $viewName, $markedName], $wa->getAsset('script', $modelName)->getDependencies());
		$this->assertSame(['library.style'], $wa->getAsset('style', 'jcb.style.' . hash('sha256', 'media/com_demo/css/view.css'))->getDependencies());
		$this->assertSame(
			['core', 'jquery', 'library.script', $viewName, $markedName, $modelName],
			$result['scripts']
		);
		$sorted = array_keys($wa->getAssets('script', true));
		$this->assertSame([$viewName, $markedName, $modelName], array_values(array_filter($sorted, static fn(string $name): bool => str_starts_with($name, 'jcb.script.'))));
		foreach (['core', 'jquery', 'library.script'] as $dependency)
		{
			$this->assertLessThan(array_search($viewName, $sorted, true), array_search($dependency, $sorted, true));
		}
		$this->assertSame('https://example.test/subdir/' . $viewPath, $wa->getAsset('script', $viewName)->getUri());
		$this->assertSame('https://example.test/subdir/' . $markedPath, $wa->getAsset('script', $markedName)->getUri());
		$this->assertStringNotContainsString('$jcbAssetManager', $result['source']);
		$this->assertStringNotContainsString('###', $result['source']);
		$this->assertSame(1, substr_count($result['source'], '$wa = '));
		$this->assertSame(1, substr_count($result['source'], 'getWebAssetManager()'));
		$this->assertStringContainsString("\n\t\t\t\t\$wa->registerScript(\n\t\t\t\t\t\$assetName,", $result['source']);
	}

	/**
	 * Optional paths can be absent or the sole asset without state crossing views.
	 *
	 * @param   string  $template  The site or custom-administrator template.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('documentTemplates')]
	public function testOptionalViewFileAndIndependentDocuments(string $template): void
	{
		$first = $this->assetManager();
		$result = $this->runDocumentAssetCode(
			$template,
			$first,
			[],
			[],
			['VIEW_SCRIPT_ENTRY' => "\n\t\t\t'media/com_demo/js/first.js',"]
		);
		$firstName = 'jcb.script.' . hash('sha256', 'media/com_demo/js/first.js');
		$this->assertSame(['core', $firstName], array_keys($first->getAssets('script', true)));
		$this->assertSame(['media/com_demo/js/first.js'], $result['paths']);
		$this->assertSame(['core'], $first->getAsset('script', $firstName)->getDependencies());

		$second = $this->assetManager();
		$result = $this->runDocumentAssetCode($template, $second, [], []);
		$this->assertSame([], $result['paths']);
		$this->assertSame([], $second->getAssets('script', true));
		$this->assertFalse($second->assetExists('script', $firstName));
		$this->assertSame(['core', $firstName], array_keys($first->getAssets('script', true)));
	}

	/**
	 * Existing registrations retain their identity, metadata and dependency graph.
	 *
	 * @param   string  $template  The site or custom-administrator template.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('documentTemplates')]
	public function testPreRegisteredViewAssetsArePreserved(string $template): void
	{
		$wa = $this->assetManager();
		$path = 'media/com_demo/js/preexisting.js';
		$name = 'jcb.script.' . hash('sha256', $path);
		$wa->registerScript($name, 'https://cdn.example.invalid/preexisting.js', ['version' => '42'], ['defer' => true], ['core']);
		$original = $wa->getAsset('script', $name);
		$slots = ['VIEW_SCRIPT_ENTRY' => "\n\t\t\t'/" . $path . "',"];
		$result = $this->runDocumentAssetCode($template, $wa, [], [$path, 'https://cdn.example.invalid/next.js'], $slots);
		$nextName = 'jcb.script.' . hash('sha256', 'https://cdn.example.invalid/next.js');
		$this->assertSame($original, $wa->getAsset('script', $name));
		$this->assertSame('42', $original->getVersion());
		$this->assertSame(['defer' => true], $original->getAttributes());
		$this->assertSame(['core'], $original->getDependencies());
		$this->assertSame('https://cdn.example.invalid/preexisting.js', $original->getUri());
		$this->assertSame(['core', $name], $wa->getAsset('script', $nextName)->getDependencies());
		$this->assertSame(['core', $name, $nextName], $result['scripts']);
		$assets = $wa->getAssets('script', true);
		$this->runDocumentAssetCode($template, $wa, [], [$path, 'https://cdn.example.invalid/next.js'], $slots);
		$this->assertSame($assets, $wa->getAssets('script', true));
		foreach ($assets as $assetName => $asset)
		{
			$this->assertNotContains($assetName, $asset->getDependencies());
		}
	}

	/**
	 * The loader reuses the binding while explicit view dependencies survive opt-out.
	 *
	 * @param   string  $template  The site or custom-administrator template.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('documentTemplates')]
	public function testConditionalJqueryUsesDocumentBinding(string $template): void
	{
		$loader = $this->renderer(LibrariesLoader::class)->get(['settings' => (object) ['code' => 'demo']]);
		$loader = str_replace('new HeaderCheck()', 'new \\stdClass()', $loader);
		foreach ([false, true] as $enabled)
		{
			foreach ([false, true] as $required)
			{
				$wa = $this->assetManager();
				$result = $this->runDocumentAssetCode(
					$template,
					$wa,
					[],
					['media/com_demo/js/view.js'],
					[
						'LIBRARIES_LOADER' => $loader,
						'VIEW_SCRIPT_DEPENDENCIES' => $required ? "\n\t\t\t'jquery'," : '',
					],
					[],
					1,
					$enabled
				);
				$assets = $wa->getAssets('script', true);
				$this->assertSame($enabled || $required, isset($assets['jquery']));
				$this->assertSame($enabled, isset($assets['jquery-noconflict']));
				$this->assertSame(1, substr_count($result['source'], 'getWebAssetManager()'));
			}
		}
	}

	/**
	 * Document hooks keep their order and inline source keeps its own receiver.
	 *
	 * @param   string  $template  The site or custom-administrator template.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('documentTemplates')]
	public function testDocumentHooksAndInlineReceiverRemainInPlace(string $template): void
	{
		$wa = $this->assetManager();
		$source = $this->template($template);
		$prefix = str_starts_with($template, 'SITE_') ? 'SITE' : 'CUSTOM_ADMIN';
		$slots = [];
		$expected = [];
		preg_match_all('/###' . $prefix . '_(LIBRARIES_LOADER|DOCUMENT_METADATA|UIKIT_LOADER|GOOGLECHART_LOADER|FOOTABLE_LOADER|DOCUMENT_CUSTOM_PHP)###/', $source, $matches);
		foreach ($matches[1] as $hook)
		{
			$slots[$hook] = "\n\t\t\$this->events[] = '" . $hook . "';";
			$expected[] = $hook;
		}
		$slots['DOCUMENT_CUSTOM_CSS'] = "\n\t\t\$this->events[] = ['css', count(\$wa->getAssets('style', true)), count(\$wa->getAssets('script', true))];";
		$inline = "\n\t\t// Set the Custom JS script to view\n\t\t\$this->getDocument()->getWebAssetManager()->addInlineScript(\"function componentDetails(id) { jQuery('#joomla_version').trigger('liszt:updated'); }\");";
		$slots['DOCUMENT_CUSTOM_JS'] = $inline;
		$slots['JAVASCRIPT_FOR_BUTTONS'] = "\n\t\t\$this->events[] = 'buttons';";
		$expected[] = ['css', 1, 0];
		if ($prefix === 'SITE')
		{
			$expected[] = 'buttons';
		}
		$result = $this->runDocumentAssetCode($template, $wa, ['view.css'], ['view.js'], $slots, [], 2);
		$this->assertSame($expected, $result['events']);
		$this->assertStringContainsString($inline, $result['source']);
		$this->assertSame(1, substr_count(explode('// Set the Custom JS script to view', $result['source'], 2)[0], 'getWebAssetManager()'));
		$inlineAssets = array_filter($wa->getAssets('script', true), static fn($asset): bool => (bool) $asset->getOption('inline'));
		$this->assertCount(1, $inlineAssets);
		$this->assertSame("function componentDetails(id) { jQuery('#joomla_version').trigger('liszt:updated'); }", array_values($inlineAssets)[0]->getOption('content'));
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
	 * Templates that collect optional view paths before their model assets.
	 *
	 * @return  iterable<string, array{string}>
	 * @since   6.2.0
	 */
	public static function documentTemplates(): iterable
	{
		foreach (['CUSTOM_ADMIN_VIEW_HTML.php', 'CUSTOM_ADMIN_VIEWS_HTML.php', 'SITE_VIEW_HTML.php', 'SITE_VIEWS_HTML.php'] as $name)
		{
			yield $name => [$name];
		}
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
		if (array_key_exists($template, iterator_to_array(self::documentTemplates())))
		{
			$this->runDocumentAssetCode($template, $wa, $styles, $scripts);

			return;
		}

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
	 * Provide the Joomla assets needed by the generated dependency declarations.
	 *
	 * @return  WebAssetManager
	 * @since   6.2.0
	 */
	private function assetManager(): WebAssetManager
	{
		$wa = new WebAssetManager(new WebAssetRegistry());
		$wa->registerScript('core', 'https://example.test/subdir/core.js');
		$wa->registerScript('jquery', 'https://example.test/subdir/jquery.js');
		$wa->registerScript('jquery-noconflict', 'https://example.test/subdir/jquery-noconflict.js', [], [], ['jquery']);

		return $wa;
	}

	/**
	 * Execute the complete document body with counting document and root boundaries.
	 *
	 * @param   string           $template        Template filename.
	 * @param   WebAssetManager  $wa              Real registry and dependency manager.
	 * @param   array            $styles          Model stylesheet paths.
	 * @param   array            $scripts         Model script paths.
	 * @param   array            $slots           Explicit fragments indexed without the target prefix.
	 * @param   array            $seeds           Style and script dependency lists supplied by a prior loader.
	 * @param   int              $managerLookups  Expected lookups including unchanged inline receivers.
	 * @param   bool             $jquery          Existing automatic jQuery setting.
	 *
	 * @return  array  Emitted source, accumulated dependencies, paths and hook observations.
	 * @since   6.2.0
	 */
	private function runDocumentAssetCode(
		string $template,
		WebAssetManager $wa,
		array $styles,
		array $scripts,
		array $slots = [],
		array $seeds = [],
		int $managerLookups = 1,
		bool $jquery = false
	): array
	{
		$this->assertSame(1, preg_match('~protected function _prepareDocument\(\): void\n\t\{(?<code>.*?)\n\t\}~s', $this->template($template), $match));
		$prefix = str_starts_with($template, 'SITE_') ? 'SITE' : 'CUSTOM_ADMIN';
		$replacements = [];
		foreach ($slots as $key => $fragment)
		{
			$replacements['###' . $prefix . '_' . $key . '###'] = $fragment;
		}
		$source = preg_replace('/###[A-Z_]+###/', '', strtr($match['code'], $replacements));
		$root = 'Joomla___eecc143e_b5cf_4c33_ba4d_97da1df61422___Power::root()';
		$this->assertSame(1, substr_count($source, $root));
		$code = str_replace($root, '$this->root()', $source);
		$document = $this->getMockBuilder(HtmlDocument::class)
			->setConstructorArgs([['webAssetManager' => $wa]])
			->onlyMethods(['getWebAssetManager'])
			->getMock();
		$document->expects($this->exactly($managerLookups))->method('getWebAssetManager')->willReturn($wa);
		$view = new class
		{
			/**
			 * Document supplied to the generated view.
			 *
			 * @var    HtmlDocument
			 * @since  6.2.0
			 */
			public HtmlDocument $document;

			/**
			 * Existing component parameters, including automatic jQuery loading.
			 *
			 * @var    JoomlaRegistry
			 * @since  6.2.0
			 */
			public JoomlaRegistry $params;

			/**
			 * Styles supplied by the model.
			 *
			 * @var    array
			 * @since  6.2.0
			 */
			public array $styles = [];

			/**
			 * Scripts supplied by the model.
			 *
			 * @var    array
			 * @since  6.2.0
			 */
			public array $scripts = [];

			/**
			 * Observations made by authored document hooks.
			 *
			 * @var    array
			 * @since  6.2.0
			 */
			public array $events = [];

			/**
			 * Number of root URL resolutions in the emitted block.
			 *
			 * @var    int
			 * @since  6.2.0
			 */
			public int $rootCalls = 0;

			/**
			 * Return the view-owned document.
			 *
			 * @return  HtmlDocument
			 * @since   6.2.0
			 */
			public function getDocument(): HtmlDocument
			{
				return $this->document;
			}

			/**
			 * Record a subdirectory installation root lookup.
			 *
			 * @return  string
			 * @since   6.2.0
			 */
			public function root(): string
			{
				$this->rootCalls++;

				return 'https://example.test/subdir/';
			}

			/**
			 * Evaluate the emitted body in the same scope as prior library seeds.
			 *
			 * @param   string  $code   Generated document method body.
			 * @param   array   $seeds  Existing dependency arrays.
			 *
			 * @return  array  Final dependencies and the ordered script path list.
			 * @since   6.2.0
			 */
			public function run(string $code, array $seeds): array
			{
				$jcbStyleDependencies = $seeds['style'] ?? [];
				$jcbScriptDependencies = $seeds['script'] ?? [];
				eval($code);

				return ['styles' => $jcbStyleDependencies, 'scripts' => $jcbScriptDependencies, 'paths' => $viewScripts];
			}
		};
		$view->document = $document;
		$view->params = new JoomlaRegistry(['add_jquery_framework' => $jquery ? 1 : 0]);
		$view->styles = $styles;
		$view->scripts = $scripts;
		$result = $view->run($code, $seeds);
		$this->assertSame(1, $view->rootCalls);

		return $result + ['source' => $source, 'events' => $view->events];
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
