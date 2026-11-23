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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\View\DocumentScripts as JoomlaSixDocumentScripts;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\DocumentScripts;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\JavaScriptFile;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;


/**
 * The placement and isolation contracts for generated view script inputs.
 *
 * @since  6.2.0
 */
#[CoversClass(DocumentScripts::class)]
#[CoversClass(JoomlaSixDocumentScripts::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ViewDocumentScriptsTest extends ArchitectureTestCase
{
	/**
	 * The targets accepted by the document preparation pass.
	 *
	 * @return  array
	 * @since   6.2.0
	 */
	public static function targets(): array
	{
		return [
			'administrator' => ['ADMIN'],
			'site' => ['SITE'],
		];
	}

	/**
	 * Shared targets append the existing file include to the library slot.
	 *
	 * @param   string  $target  The uppercase compilation target.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testSharedPlacementPreservesTheLegacyLibraryOutput(string $target): void
	{
		$content = new ContentMulti();
		$prefix = 'compiler|' . $target;
		$content->set($prefix . '_LIBRARIES_LOADER', 'loadExistingLibrary();');
		$view = ['settings' => (object) [
			'code' => 'compiler',
			'view_script_paths' => 'unused by shared targets',
			'view_script_dependencies' => ['unused by shared targets'],
		]];
		$javascript = $this->createMock(JavaScriptFile::class);
		$javascript->expects($this->once())->method('get')
			->with($view, $target)->willReturn(PHP_EOL . 'loadExistingViewScript();');
		$subject = new DocumentScripts($javascript, $content);

		$subject->set($view, $target);

		$this->assertSame(
			'loadExistingLibrary();' . PHP_EOL . 'loadExistingViewScript();',
			$content->get($prefix . '_LIBRARIES_LOADER')
		);
		$this->assertFalse($content->exists($prefix . '_VIEW_SCRIPT_ENTRY'));
		$this->assertFalse($content->exists($prefix . '_VIEW_SCRIPT_PATHS'));
		$this->assertFalse($content->exists($prefix . '_VIEW_SCRIPT_DEPENDENCIES'));
	}

	/**
	 * A shared view without a generated script preserves the library output.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSharedPlacementDoesNotClearLibrariesWhenTheFileIsAbsent(): void
	{
		$content = new ContentMulti();
		$content->set('compiler|ADMIN_LIBRARIES_LOADER', 'loadExistingLibrary();');
		$original = $content->toArray();
		$view = ['settings' => (object) ['code' => 'compiler']];
		$javascript = $this->createStub(JavaScriptFile::class);
		$javascript->method('get')->willReturn('');
		$subject = new DocumentScripts($javascript, $content);

		$subject->set($view, 'ADMIN');

		$this->assertSame($original, $content->toArray());
	}

	/**
	 * Joomla 6 expands and escapes ordered entries without consuming libraries.
	 *
	 * @param   string  $target  The uppercase compilation target.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testJoomlaSixPreservesOrderedPathsDependenciesAndLibraryPlacement(string $target): void
	{
		$this->placeholder()->set('component', 'demo');
		$content = new ContentMulti();
		$prefix = 'compiler|' . $target;
		$content->set($prefix . '_LIBRARIES_LOADER', 'loadExistingLibrary();');
		$view = ['settings' => (object) [
			'code' => 'compiler',
			'view_script_paths' => [
				'administrator/components/com_###component###/custom/marked.js',
				"https://cdn.example.test/it's\\marked.js",
				'//cdn.example.test/library.js',
				'administrator/components/com_###component###/custom/marked.js',
			],
			'view_script_dependencies' => ['core', 'vendor.###component###', "it's\\safe"],
		]];
		$entry = PHP_EOL . "\t\t\t'media/com_demo/js/admin/compiler.js',";
		$javascript = $this->createMock(JavaScriptFile::class);
		$javascript->expects($this->once())->method('get')
			->with($view, $target)->willReturn($entry);
		$subject = new JoomlaSixDocumentScripts($javascript, $content, $this->placeholder());

		$subject->set($view, $target);

		$this->assertSame($entry, $content->get($prefix . '_VIEW_SCRIPT_ENTRY'));
		$this->assertSame(
			PHP_EOL . "\t\t\t'administrator/components/com_demo/custom/marked.js',"
				. PHP_EOL . "\t\t\t'https://cdn.example.test/it\'s\\\\marked.js',"
				. PHP_EOL . "\t\t\t'//cdn.example.test/library.js',"
				. PHP_EOL . "\t\t\t'administrator/components/com_demo/custom/marked.js',",
			$content->get($prefix . '_VIEW_SCRIPT_PATHS')
		);
		$this->assertSame(
			PHP_EOL . "\t\t\t'core',"
				. PHP_EOL . "\t\t\t'vendor.demo',"
				. PHP_EOL . "\t\t\t'it\'s\\\\safe',",
			$content->get($prefix . '_VIEW_SCRIPT_DEPENDENCIES')
		);
		$this->assertSame('loadExistingLibrary();', $content->get($prefix . '_LIBRARIES_LOADER'));
	}

	/**
	 * Invalid authored entries must fail before any file or registry mutation.
	 *
	 * @return  iterable
	 * @since   6.2.0
	 */
	public static function invalidInputs(): iterable
	{
		foreach (['view_script_paths', 'view_script_dependencies'] as $property)
		{
			foreach ([
				'JSON text' => ['["script.js"]', ' must be an ordered list of strings.'],
				'associative array' => [['first' => 'script.js'], ' must be an ordered list of strings.'],
				'sparse array' => [[1 => 'script.js'], ' must be an ordered list of strings.'],
				'number after valid entry' => [['script.js', 42], ' must contain non-empty strings.'],
				'empty entry' => [[''], ' must contain non-empty strings.'],
				'whitespace entry' => [[" \t\n"], ' must contain non-empty strings.'],
				'empty expansion' => [['###empty###'], ' expanded to an empty value.'],
			] as $case => [$value, $suffix])
			{
				yield $property . ': ' . $case => [$property, $value, $property . $suffix];
			}
		}
	}

	/**
	 * Both arrays are checked before the file builder or registry is touched.
	 *
	 * @param   string  $property  The invalid input property.
	 * @param   mixed   $value     The invalid input value.
	 * @param   string  $message   The expected validation error.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('invalidInputs')]
	public function testJoomlaSixRejectsInvalidInputBeforeSideEffects(
		string $property, mixed $value, string $message): void
	{
		$this->placeholder()->set('empty', '');
		$content = new ContentMulti();
		foreach (['LIBRARIES_LOADER', 'VIEW_SCRIPT_ENTRY', 'VIEW_SCRIPT_PATHS', 'VIEW_SCRIPT_DEPENDENCIES'] as $part)
		{
			$content->set('compiler|ADMIN_' . $part, 'original ' . $part);
		}
		$original = $content->toArray();
		$view = ['settings' => (object) [
			'code' => 'compiler',
			'view_script_paths' => ['valid.js'],
			'view_script_dependencies' => ['core'],
		]];
		$view['settings']->{$property} = $value;
		$javascript = $this->createMock(JavaScriptFile::class);
		$javascript->expects($this->never())->method('get');
		$subject = new JoomlaSixDocumentScripts($javascript, $content, $this->placeholder());

		try
		{
			$subject->set($view, 'ADMIN');
			$this->fail('Invalid view script input was accepted.');
		}
		catch (InvalidArgumentException $exception)
		{
			$this->assertSame($message, $exception->getMessage());
		}

		$this->assertSame($original, $content->toArray());
	}

	/**
	 * A reused service keeps paths, dependencies and files scoped to each view.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testJoomlaSixKeepsTwoViewsAndTheirTargetsIndependent(): void
	{
		$content = new ContentMulti();
		$first = ['settings' => (object) [
			'code' => 'compiler',
			'view_script_paths' => ['marked.js'],
			'view_script_dependencies' => ['jquery'],
		]];
		$second = ['settings' => (object) [
			'code' => 'report',
			'view_script_paths' => ['chart.js'],
			'view_script_dependencies' => ['core'],
		]];
		$javascript = $this->createMock(JavaScriptFile::class);
		$javascript->expects($this->exactly(2))->method('get')
			->willReturnOnConsecutiveCalls('compiler entry', 'report entry');
		$subject = new JoomlaSixDocumentScripts($javascript, $content, $this->placeholder());

		$subject->set($first, 'ADMIN');
		$subject->set($second, 'SITE');

		$this->assertSame('compiler entry', $content->get('compiler|ADMIN_VIEW_SCRIPT_ENTRY'));
		$this->assertSame(PHP_EOL . "\t\t\t'marked.js',", $content->get('compiler|ADMIN_VIEW_SCRIPT_PATHS'));
		$this->assertSame(PHP_EOL . "\t\t\t'jquery',", $content->get('compiler|ADMIN_VIEW_SCRIPT_DEPENDENCIES'));
		$this->assertSame('report entry', $content->get('report|SITE_VIEW_SCRIPT_ENTRY'));
		$this->assertSame(PHP_EOL . "\t\t\t'chart.js',", $content->get('report|SITE_VIEW_SCRIPT_PATHS'));
		$this->assertSame(PHP_EOL . "\t\t\t'core',", $content->get('report|SITE_VIEW_SCRIPT_DEPENDENCIES'));
		$this->assertFalse($content->exists('compiler|SITE_VIEW_SCRIPT_ENTRY'));
		$this->assertFalse($content->exists('report|ADMIN_VIEW_SCRIPT_ENTRY'));
	}

	/**
	 * Rebuilding a view without optional inputs clears all previous entries.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testJoomlaSixResetsEveryOptionalSlotWhenTheSameViewHasNoScripts(): void
	{
		$content = new ContentMulti();
		$content->set('compiler|ADMIN_LIBRARIES_LOADER', 'loadExistingLibrary();');
		$view = ['settings' => (object) [
			'code' => 'compiler',
			'view_script_paths' => ['marked.js'],
			'view_script_dependencies' => ['jquery'],
		]];
		$javascript = $this->createMock(JavaScriptFile::class);
		$javascript->expects($this->exactly(2))->method('get')
			->willReturnOnConsecutiveCalls('compiler entry', '');
		$subject = new JoomlaSixDocumentScripts($javascript, $content, $this->placeholder());
		$subject->set($view, 'ADMIN');
		unset($view['settings']->view_script_paths, $view['settings']->view_script_dependencies);

		$subject->set($view, 'ADMIN');

		$this->assertSame('', $content->get('compiler|ADMIN_VIEW_SCRIPT_ENTRY'));
		$this->assertSame('', $content->get('compiler|ADMIN_VIEW_SCRIPT_PATHS'));
		$this->assertSame('', $content->get('compiler|ADMIN_VIEW_SCRIPT_DEPENDENCIES'));
		$this->assertSame('loadExistingLibrary();', $content->get('compiler|ADMIN_LIBRARIES_LOADER'));
	}
}
