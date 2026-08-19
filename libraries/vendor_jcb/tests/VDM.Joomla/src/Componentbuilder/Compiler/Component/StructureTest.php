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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Component;


use FilesystemIterator;
use Joomla\CMS\Application\CMSApplicationInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Component\Structure;
use VDM\Joomla\Componentbuilder\Compiler\Component\Structuremultiple;
use VDM\Joomla\Componentbuilder\Compiler\Component\Structuresingle;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Component\SettingsInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Model\Createdate;
use VDM\Joomla\Componentbuilder\Compiler\Model\Modifieddate;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Files;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File as CompilerFile;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Folder as CompilerFolder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure as UtilityStructure;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Component root, dynamic, and static structure-building contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Structure::class)]
#[CoversClass(Structuremultiple::class)]
#[CoversClass(Structuresingle::class)]
#[UsesClass(Component::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ContentOne::class)]
#[UsesClass(Files::class)]
final class StructureTest extends CompilerDomainTestCase
{
	/**
	 * The root structure walker creates the component root and every nested manifest folder.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRootStructureRecursivelyCreatesManifestFolders(): void
	{
		$settings = $this->createMock(SettingsInterface::class);
		$settings->expects($this->once())->method('exists')->willReturn(true);
		$settings->expects($this->once())->method('structure')->willReturn((object) [
			'administrator' => (object) [
				'src' => new \stdClass(),
				'tmpl' => new \stdClass()
			],
			'media' => new \stdClass()
		]);
		$paths = $this->createStub(Paths::class);
		$paths->method('__get')->willReturn('/build/component');
		$created = [];
		$folder = $this->createMock(CompilerFolder::class);
		$folder->expects($this->exactly(5))
			->method('create')
			->willReturnCallback(static function (string $path) use (&$created): void
			{
				$created[] = $path;
			});

		$this->assertTrue((new Structure($settings, $paths, $folder))->build());
		$this->assertSame([
			'/build/component',
			'/build/component/administrator',
			'/build/component/administrator/src',
			'/build/component/administrator/tmpl',
			'/build/component/media'
		], $created);
	}

	/**
	 * Dynamic structure creation fans one admin view into dashboard, modal, list, and API targets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMultipleStructureBuildsVersionAwareAdminTargets(): void
	{
		$config = $this->compilerConfig(['joomla_version' => 5]);
		$registry = new Registry();
		$settings = $this->createMock(SettingsInterface::class);
		$settings->expects($this->once())->method('exists')->willReturn(true);
		$component = $this->component();
		$component->set('name_code', 'example');
		$component->set('admin_views', [[
			'checkin' => 1,
			'add_api' => 3,
			'settings' => (object) [
				'name_single' => 'article',
				'name_list' => 'articles',
				'version' => '2.4.0'
			]
		]]);
		$createdate = $this->createMock(Createdate::class);
		$createdate->expects($this->once())->method('get')->willReturn('1st January, 2026');
		$modifieddate = $this->createMock(Modifieddate::class);
		$modifieddate->expects($this->once())->method('get')->willReturn('14th August, 2026');
		$builds = [];
		$structure = $this->createMock(UtilityStructure::class);
		$structure->expects($this->exactly(6))
			->method('build')
			->willReturnCallback(static function (
				array $target,
				string $type,
				?string $fileName = null,
				?array $buildConfig = null
			) use (&$builds): bool
			{
				$builds[] = [$target, $type, $fileName, $buildConfig];
				return true;
			});
		$subject = new Structuremultiple(
			$config,
			$registry,
			$settings,
			$component,
			$createdate,
			$modifieddate,
			$structure
		);

		$this->assertTrue($subject->build());
		$this->assertSame([
			'dashboard',
			'single',
			'single_modal',
			'list',
			'list_modal',
			'single'
		], array_column($builds, 1));
		$this->assertSame(['admin' => 'example'], $builds[0][0]);
		$this->assertSame(['api' => 'article'], $builds[5][0]);
		$this->assertSame('1st January, 2026', $builds[1][3]['###CREATIONDATE###']);
		$this->assertSame('14th August, 2026', $builds[1][3]['###BUILDDATE###']);
		$this->assertSame('2.4.0', $builds[1][3]['###VERSION###']);
		$this->assertTrue($config->get('add_checkin'));
		$this->assertSame(1, $config->get('add_api'));
	}

	/**
	 * Static structure creation copies, renames, registers, and declares one template file.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSingleStructureCopiesAndRegistersTemplateFile(): void
	{
		$root = sys_get_temp_dir() . '/jcb-single-' . bin2hex(random_bytes(6));
		$template = $root . '/template';
		$componentPath = $root . '/component';
		$rootCreated = false;

		try
		{
			if (!mkdir($root, 0700))
			{
				throw new RuntimeException('Unable to create the single-structure fixture: ' . $root);
			}

			$rootCreated = true;

			if (!mkdir($template, 0700))
			{
				throw new RuntimeException('Unable to create the single-structure template: ' . $template);
			}

			if (file_put_contents($template . '/sample.txt', 'template-content') === false)
			{
				throw new RuntimeException('Unable to write the single-structure template fixture.');
			}

			// the folder utility seeds every directory it makes from this file
			if (file_put_contents($template . '/index.html', '<html><body bgcolor="#FFFFFF"></body></html>') === false)
			{
				throw new RuntimeException('Unable to write the single-structure index fixture.');
			}

			$config = $this->compilerConfig([
				'component_code_name' => 'example',
				'joomla_version' => 6
			]);
			$registry = new Registry();
			$settings = $this->createStub(SettingsInterface::class);
			$settings->method('exists')->willReturn(true);
			$settings->method('single')->willReturn((object) [
				'sample' => (object) [
					'naam' => 'sample.txt',
					'path' => 'c0mp0n3nt/admin',
					'rename' => 'new',
					'newName' => 'renamed.txt',
					'type' => 'file',
					'custom' => false
				]
			]);
			$settings->method('standardFolder')->willReturnCallback(
				static fn(string $folder): bool => $folder === 'admin'
			);
			$settings->method('standardRootFile')->willReturn(false);
			$component = $this->component();
			$component->set('license', 'Proprietary');
			$component->set('addreadme', false);
			$component->set('changelog', false);
			$content = new ContentOne();
			$counter = $this->inertCompilerCollaborator(Counter::class);
			$paths = $this->createStub(Paths::class);
			$paths->method('__get')->willReturnCallback(
				static function (string $name) use ($template, $componentPath): string
				{
					return match ($name)
					{
						'template_path' => $template,
						'template_path_custom' => $template,
						'component_path' => $componentPath,
						default => ''
					};
				}
			);
			$files = new Files();
			$app = $this->createMock(CMSApplicationInterface::class);
			$app->expects($this->never())->method('enqueueMessage');
			$subject = new Structuresingle(
				$config,
				$registry,
				new Placeholder($config),
				$settings,
				$component,
				$content,
				$counter,
				$paths,
				$files,
				new CompilerFolder($counter, new CompilerFile($counter, $paths)),
				$app
			);

			$this->assertTrue($subject->build());
			$destination = $componentPath . '/admin/renamed.txt';
			$this->assertFileExists($destination);
			$this->assertSame('template-content', file_get_contents($destination));
			// the moved file, plus the index.html seeded into the directory
			// the build had to create for it
			$this->assertSame(2, $counter->file);

			// a directory the build creates must not be left listable
			$this->assertFileExists($componentPath . '/admin/index.html');
			$this->assertSame('renamed.txt', $files->get('static')[0]['name']);
			$this->assertSame($destination, $files->get('static')[0]['path']);
			$this->assertContains('LICENSE.txt', $registry->get('files.not.new'));
			$this->assertStringContainsString(
				'<filename>renamed.txt</filename>',
				$content->get('EXSTRA_ADMIN_FILES')
			);
		}
		finally
		{
			if ($rootCreated)
			{
				$this->removeTemporaryDirectory($root);
			}
		}
	}

	/**
	 * Recursively remove one test-owned directory without following links.
	 *
	 * @param   string  $directory  Test-owned directory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function removeTemporaryDirectory(string $directory): void
	{
		if (is_link($directory) || is_file($directory))
		{
			if (!unlink($directory))
			{
				throw new RuntimeException('Unable to remove the single-structure fixture path: ' . $directory);
			}

			return;
		}

		if (!file_exists($directory))
		{
			return;
		}

		if (!is_dir($directory))
		{
			throw new RuntimeException('The single-structure fixture is not a directory: ' . $directory);
		}

		$iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

		foreach ($iterator as $item)
		{
			$path = $item->getPathname();

			if ($item->isLink() || !$item->isDir())
			{
				if (!unlink($path))
				{
					throw new RuntimeException('Unable to remove the single-structure fixture file: ' . $path);
				}

				continue;
			}

			$this->removeTemporaryDirectory($path);
		}

		unset($iterator);

		if (!rmdir($directory))
		{
			throw new RuntimeException('Unable to remove the single-structure fixture directory: ' . $directory);
		}
	}

	/**
	 * Build a mutable component registry without database modelling.
	 *
	 * @return  Component
	 * @since   6.1.6
	 */
	private function component(): Component
	{
		return new Component(
			$this->inertCompilerCollaborator(Data::class),
			$this->createStub(EventInterface::class)
		);
	}
}
