<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Library;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data as ComponentData;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Field\Data as FieldData;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Library\Data as LibraryData;
use VDM\Joomla\Componentbuilder\Compiler\Library\Document;
use VDM\Joomla\Componentbuilder\Compiler\Library\IncludeHelper;
use VDM\Joomla\Componentbuilder\Compiler\Library\Structure;
use VDM\Joomla\Componentbuilder\Compiler\Model\Filesfolders;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Folder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Joomla\Componentbuilder\Package\Builder\Get as Superpower;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Compiler library loading, document, include, and structure contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(LibraryData::class)]
#[CoversClass(Document::class)]
#[CoversClass(IncludeHelper::class)]
#[CoversClass(Structure::class)]
final class LibraryPipelineTest extends CompilerDomainTestCase
{
	/**
	 * Invalid, cached, and static GUIDs avoid database and remote resolution.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDataShortCircuitsInvalidCachedAndStaticLibraries(): void
	{
		$registry = new Registry();
		$cachedGuid = 'e24b127f-8f82-4870-9144-28f94ad8f8a9';
		$cached = (object) ['name' => 'Cached'];
		$registry->set('builder.libraries.' . $cachedGuid, $cached);
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');
		$superpower = $this->createMock(Superpower::class);
		$superpower->expects($this->never())->method('get');
		$subject = $this->libraryData($registry, $db, $superpower, 5);

		$this->assertFalse($subject->get('not-a-guid'));
		$this->assertSame($cached, $subject->get($cachedGuid));
		$this->assertFalse($subject->get('bc8e675d-7536-4a68-b186-fb4b988fa3e2'));
		$this->assertTrue($registry->exists(
			'builder.libraries.bc8e675d-7536-4a68-b186-fb4b988fa3e2'
		));
		$this->assertFalse($registry->get(
			'builder.libraries.bc8e675d-7536-4a68-b186-fb4b988fa3e2'
		));
	}

	/**
	 * Built-in library fallbacks remain target-version specific.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDataAppliesBuiltInFallbackOnlyToLegacyTargets(): void
	{
		$legacyConfig = $this->compilerConfig(['joomla_version' => 5]);
		$legacy = $this->libraryData(new Registry(), null, null, 5, $legacyConfig);
		$modernConfig = $this->compilerConfig(['joomla_version' => 6]);
		$modern = $this->libraryData(new Registry(), null, null, 6, $modernConfig);
		$method = new ReflectionMethod(LibraryData::class, 'applyBuildInFallback');
		$legacyLibrary = (object) ['id' => 3, 'how' => 4];
		$modernLibrary = (object) ['id' => 3, 'how' => 4];

		$method->invoke($legacy, $legacyLibrary);
		$method->invoke($modern, $modernLibrary);

		$this->assertSame(0, $legacyLibrary->how);
		$this->assertSame(3, $legacyConfig->get('uikit'));
		$this->assertSame(1, $modernLibrary->how);
		$this->assertSame(0, $modernConfig->get('uikit', 0));
	}

	/**
	 * Include rendering is extension-aware and rejects remote PHP execution.
	 *
	 * @param   string  $path      Input path.
	 * @param   string  $expected  Expected include statement.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('includeProvider')]
	public function testIncludeHelperRendersExtensionContracts(string $path, string $expected): void
	{
		$this->assertSame($expected, (new IncludeHelper())->get($path));
	}

	/**
	 * Include contract fixtures.
	 *
	 * @return  array<string, array{string, string}>
	 * @since   6.1.6
	 */
	public static function includeProvider(): array
	{
		$power = 'Joomla___34690c75_1090_47eb_8c06_7228dc7eedd6___Power';

		return [
			'javascript' => [
				'/media/app.js',
				$power . "::_('script', 'media/app.js', ['version' => 'auto']);",
			],
			'css' => [
				'https://cdn.example.test/app.CSS',
				$power . "::_('stylesheet', 'https://cdn.example.test/app.CSS', ['version' => 'auto']);",
			],
			'less' => [
				'/media/theme.less',
				$power . "::_('stylesheet', 'media/theme.less', ['version' => 'auto']);",
			],
			'local php' => ['/srv/library.php', 'require_once("/srv/library.php");'],
			'remote php' => ['https://example.test/library.php', ''],
			'unknown' => ['/media/readme.txt', ''],
		];
	}

	/**
	 * Document generation rewrites component roots and stores one deterministic block.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDocumentBuildsAndCachesRootAwareIncludeBlock(): void
	{
		$config = $this->compilerConfig([
			'component_code_name' => 'demo',
			'compiler_path' => sys_get_temp_dir(),
			'joomla_version' => 5,
		]);
		$component = $this->componentRegistry();
		$component->set('sales_name', 'demo');
		$component->set('name_code', 'demo');
		$component->set('component_version', '1.0.0');
		$registry = new Registry();
		$registry->set('builder.libraries.assets', (object) [
			'how' => 1,
			'urls' => [
				['path' => '/media/js/local.js', 'url' => 'https://cdn.example.test/local.js'],
				['url' => 'https://cdn.example.test/theme.css'],
			],
			'files' => [
				['path' => '/admin/js/admin.js', 'file' => ''],
				['path' => '/site/css/site.css', 'file' => ''],
			],
		]);
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->never())->method('enqueueMessage');
		$subject = new Document(
			$config,
			$registry,
			new IncludeHelper(),
			new Paths($config, $component),
			$app
		);

		$document = $subject->get('assets');

		$this->assertStringStartsWith(PHP_EOL . PHP_EOL . "\t\t//", $document);
		$this->assertStringContainsString("media/com_demo/js/local.js", $document);
		$this->assertStringContainsString("https://cdn.example.test/theme.css", $document);
		$this->assertStringContainsString("administrator/components/com_demo/js/admin.js", $document);
		$this->assertStringContainsString("components/com_demo/css/site.css", $document);
		$this->assertStringNotContainsString('cdn.example.test/local.js', $document);
		$this->assertSame(trim($document), trim((string) $registry->get(
			'builder.libraries.assets.document'
		)));
	}

	/**
	 * Conditional libraries warn exactly once while preserving the empty document contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDocumentWarnsOnceForUnsupportedConditionalBuilder(): void
	{
		$config = $this->compilerConfig(['component_code_name' => 'demo']);
		$registry = new Registry();
		$registry->set('builder.libraries.conditional', (object) [
			'how' => 2,
			'name' => 'Conditional Kit',
			'conditions' => [['field' => 'state']],
			'urls' => [['url' => 'https://cdn.example.test/conditional.js']],
		]);
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(2))
			->method('enqueueMessage')
			->with($this->isString(), 'Warning');
		$subject = new Document(
			$config,
			$registry,
			new IncludeHelper(),
			$this->inertCompilerCollaborator(Paths::class),
			$app
		);

		$this->assertSame('', $subject->get('conditional'));
		$this->assertSame('', $subject->get('conditional'));
	}

	/**
	 * Structure projects resolved files, folders, and unique config into component state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testStructureProjectsLibraryAssetsAndConfigIntoComponentState(): void
	{
		$root = sys_get_temp_dir() . '/jcb-library-' . bin2hex(random_bytes(5));
		$rootCreated = false;

		try
		{
			if (!mkdir($root, 0700))
			{
				throw new RuntimeException('Unable to create the library-structure fixture: ' . $root);
			}

			$rootCreated = true;
			$config = $this->compilerConfig(['component_context' => 'com_demo']);
			$registry = new Registry();
			$library = (object) [
				'how' => 2,
				'files' => [['path' => '/media/js/app.js']],
				'folders' => [['path' => '/media', 'folder' => 'images']],
				'config' => [
					['field' => 'theme'],
					['field' => 'existing'],
				],
			];
			$registry->set('builder.libraries.assets', $library);
			$component = $this->componentRegistry();
			$component->set('config', [['field' => 'existing']]);
			$event = $this->createMock(EventInterface::class);
			$event->expects($this->once())
				->method('trigger')
				->with(
					'jcb_ce_onBeforeSetLibraries',
					$this->callback(
						static fn(array $arguments): bool => $arguments[0] === 'com_demo'
							&& isset($arguments[1]->assets)
					)
				);
			$folder = $this->createMock(Folder::class);
			$folder->expects($this->once())->method('create')->with($root . '/media');
			$file = $this->createMock(File::class);
			$file->expects($this->never())->method('html');
			$paths = new class($root) extends Paths
			{
				/**
				 * Root component path.
				 *
				 * @var    string
				 * @since  6.1.6
				 */
				private string $root;

				/**
				 * Constructor.
				 *
				 * @param   string  $root  Root component path.
				 *
				 * @since   6.1.6
				 */
				public function __construct(string $root)
				{
					$this->root = $root;
				}

				/**
				 * Resolve the component path.
				 *
				 * @param   string  $key  Requested path key.
				 *
				 * @return  string
				 * @since   6.1.6
				 */
				public function __get($key)
				{
					if ($key === 'component_path')
					{
						return $this->root;
					}

					throw new \InvalidArgumentException((string) $key);
				}
			};
			$subject = new Structure(
				$config,
				$registry,
				$event,
				$component,
				new ContentOne(),
				$this->inertCompilerCollaborator(Counter::class),
				$paths,
				$folder,
				$file
			);

			$subject->build();

			$this->assertSame($library->files, $component->get('files'));
			$this->assertSame($library->folders, $component->get('folders'));
			$this->assertSame(
				[['field' => 'existing'], ['field' => 'theme']],
				$component->get('config')
			);
			$this->assertSame($library, $registry->get('builder.libraries.assets'));
		}
		finally
		{
			if ($rootCreated && is_link($root) && !unlink($root))
			{
				throw new RuntimeException('Unable to remove the library-structure fixture link: ' . $root);
			}

			if ($rootCreated && is_dir($root) && !rmdir($root))
			{
				throw new RuntimeException('Unable to remove the library-structure fixture: ' . $root);
			}

			if ($rootCreated && file_exists($root))
			{
				throw new RuntimeException('The library-structure fixture is not a directory: ' . $root);
			}
		}
	}

	/**
	 * Build Library Data with inert collaborators for short-circuit paths.
	 *
	 * @param   Registry                $registry    Compiler registry.
	 * @param   DatabaseInterface|null  $db          Optional database mock.
	 * @param   Superpower|null         $superpower  Optional remote loader mock.
	 * @param   int                     $version     Target Joomla version.
	 * @param   mixed                   $config      Optional compiler config.
	 *
	 * @return  LibraryData
	 * @since   6.1.6
	 */
	private function libraryData(
		Registry $registry,
		?DatabaseInterface $db = null,
		?Superpower $superpower = null,
		int $version = 5,
		mixed $config = null
	): LibraryData
	{
		$config ??= $this->compilerConfig(['joomla_version' => $version]);

		return new LibraryData(
			$config,
			$registry,
			$this->createStub(Customcode::class),
			$this->createStub(Gui::class),
			$this->inertCompilerCollaborator(FieldData::class),
			$this->createStub(Filesfolders::class),
			$db ?? $this->createStub(DatabaseInterface::class),
			$superpower ?? $this->createStub(Superpower::class)
		);
	}

	/**
	 * Create a usable component registry without loading component data.
	 *
	 * @return  Component
	 * @since   6.1.6
	 */
	private function componentRegistry(): Component
	{
		$data = (new ReflectionClass(ComponentData::class))->newInstanceWithoutConstructor();

		return new Component($data, $this->createStub(EventInterface::class));
	}
}
