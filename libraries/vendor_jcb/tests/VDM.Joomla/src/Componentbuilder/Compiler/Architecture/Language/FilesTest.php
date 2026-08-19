<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Language;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Language\Files;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LanguageMessages;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Languages;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Multilingual as MultilingualRegistry;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Language\Insert;
use VDM\Joomla\Componentbuilder\Compiler\Language\Multilingual;
use VDM\Joomla\Componentbuilder\Compiler\Language\Purge;
use VDM\Joomla\Componentbuilder\Compiler\Language\Set;
use VDM\Joomla\Componentbuilder\Compiler\Language\Translation;
use VDM\Joomla\Componentbuilder\Compiler\Language\Update;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The language files a built component ships its strings in.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\Language')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FilesTest extends ArchitectureTestCase
{
	/**
	 * Where this test's component and templates live.
	 *
	 * @var    string|null
	 * @since  6.1.7
	 */
	private ?string $directory = null;

	/**
	 * The registry the strings were handed to.
	 *
	 * @var    Languages|null
	 * @since  6.1.7
	 */
	private ?Languages $languages = null;

	/**
	 * How much was written.
	 *
	 * @var    Counter|null
	 * @since  6.1.7
	 */
	private ?Counter $counter = null;

	/**
	 * Remove whatever the compile wrote.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function tearDown(): void
	{
		if ($this->directory !== null)
		{
			$this->remove($this->directory);
			$this->directory = null;
		}

		parent::tearDown();
	}

	/**
	 * The administrator side gets its strings written into its own ini file.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheAdministratorSideGetsItsOwnIniFile(): void
	{
		$this->build();

		$path = $this->directory . '/component/admin/language/en-GB/en-GB.com_demo.ini';
		$this->assertFileExists($path);

		$written = file_get_contents($path);
		$this->assertStringContainsString('COM_DEMO="Demo"', $written);
		$this->assertStringContainsString('COM_DEMO_DASHBOARD="Demo Dashboard"', $written);
	}

	/**
	 * Each of the four areas is written into the file that belongs to it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachAreaIsWrittenIntoTheFileThatBelongsToIt(): void
	{
		$this->build();

		foreach ([
			'/component/admin/language/en-GB/en-GB.com_demo.ini',
			'/component/admin/language/en-GB/en-GB.com_demo.sys.ini',
			'/component/site/language/en-GB/en-GB.com_demo.ini',
			'/component/site/language/en-GB/en-GB.com_demo.sys.ini'
		] as $file)
		{
			$this->assertFileExists($this->directory . $file);
		}
	}

	/**
	 * The manifest lists the files that were written, and only those.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheManifestListsTheFilesThatWereWritten(): void
	{
		$this->build();

		$manifest = file_get_contents($this->directory . '/component/demo.xml');

		$this->assertStringContainsString(
			'<language tag="en-GB">language/en-GB/en-GB.com_demo.ini</language>', $manifest
		);
		$this->assertStringContainsString(
			'<language tag="en-GB">language/en-GB/en-GB.com_demo.sys.ini</language>', $manifest
		);
		$this->assertStringNotContainsString('ADMIN_LANGUAGES', $manifest);
		$this->assertStringNotContainsString('SITE_LANGUAGES', $manifest);
	}

	/**
	 * A component built without a site side is given no site language files.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutASiteSideGetsNoSiteFiles(): void
	{
		$this->config()->set('remove_site_folder', true);
		$this->config()->set('remove_site_edit_folder', true);

		$this->build();

		$this->assertFileExists(
			$this->directory . '/component/admin/language/en-GB/en-GB.com_demo.ini'
		);
		$this->assertDirectoryDoesNotExist($this->directory . '/component/site');
	}

	/**
	 * Every folder, file and line written is counted.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryFolderFileAndLineWrittenIsCounted(): void
	{
		$this->build();

		$this->assertSame(2, $this->counter->folder);
		$this->assertSame(4, $this->counter->file);
		$this->assertGreaterThan(0, $this->counter->line);
	}

	/**
	 * The strings the areas were given are what reaches the files.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheStringsTheAreasWereGivenReachTheFiles(): void
	{
		$this->build();

		$strings = (array) $this->languages->get('components.en-GB.admin');
		$written = file_get_contents(
			$this->directory . '/component/admin/language/en-GB/en-GB.com_demo.ini'
		);

		$this->assertNotSame([], $strings);

		foreach ($strings as $placeholder => $string)
		{
			$this->assertStringContainsString($placeholder . '="' . $string . '"', $written);
		}
	}

	/**
	 * Run one compile of the language files.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function build(): void
	{
		$this->directory = sys_get_temp_dir() . '/jcb-language-files-' . getmypid();
		$this->remove($this->directory);
		mkdir($this->directory . '/templates', 0777, true);
		mkdir($this->directory . '/component', 0777, true);
		file_put_contents($this->directory . '/templates/en-GB.com_admin.ini', '');
		file_put_contents(
			$this->directory . '/component/demo.xml',
			"<extension>\n\t\t\t###ADMIN_LANGUAGES###\n\t\t###SITE_LANGUAGES###\n</extension>"
		);

		$this->config()->set('lang_tag', 'en-GB');
		$this->config()->set('lang_prefix', 'COM_DEMO');
		$this->config()->set('component_code_name', 'demo');
		$this->config()->set('component_guid', 'demo-guid');
		$this->config()->set('percentage_language_add', 50);

		$this->languages = new Languages();
		$this->counter = new Counter($this->valuation());

		$paths = (new ReflectionClass(Paths::class))->newInstanceWithoutConstructor();
		$paths->set('template_path', $this->directory . '/templates');
		$paths->set('component_path', $this->directory . '/component');

		$db = $this->database();
		$this->setJoomlaFactoryProperty('database', $db);

		$subject = $this->renderer(Files::class, [
			'component' => $this->component(),
			'languages' => $this->languages,
			'multilingualregistry' => new MultilingualRegistry(),
			'multilingual' => new Multilingual($db),
			'set' => $this->set($db),
			'purge' => new Purge($this->update($db), $db),
			'translation' => new Translation($this->config(), new LanguageMessages()),
			'admin' => $this->area('Admin'),
			'adminsys' => $this->area('AdminSys'),
			'site' => $this->area('Site'),
			'sitesys' => $this->area('SiteSys'),
			'paths' => $paths,
			'counter' => $this->counter,
			'file' => new File($this->counter, $paths)
		]);

		$subject->build();
	}

	/**
	 * One of the four areas, over the registries this compile shares.
	 *
	 * @param   string  $name  The area's class name.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function area(string $name): object
	{
		return $this->renderer(
			'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\Language\\' . $name,
			['component' => $this->component(), 'languages' => $this->languages]
		);
	}

	/**
	 * The component being built.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function component(): Component
	{
		static $component = null;

		if ($component === null || $this->directory === null)
		{
			$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
			$component = new Component($data, $this->createStub(EventInterface::class));
			$component->set('name', 'Demo');
		}

		return $component;
	}

	/**
	 * A language set over a database that answers nothing.
	 *
	 * @param   DatabaseInterface  $db  The database boundary.
	 *
	 * @return  Set
	 * @since   6.1.7
	 */
	private function set(DatabaseInterface $db): Set
	{
		return new Set(
			$this->config(),
			$this->language(),
			new MultilingualRegistry(),
			$this->languages,
			new Insert($db),
			$this->update($db)
		);
	}

	/**
	 * An update batch without the application identity globals.
	 *
	 * @param   DatabaseInterface  $db  The database boundary.
	 *
	 * @return  Update
	 * @since   6.1.7
	 */
	private function update(DatabaseInterface $db): Update
	{
		$reflection = new ReflectionClass(Update::class);
		$subject = $reflection->newInstanceWithoutConstructor();
		$reflection->getProperty('db')->setValue($subject, $db);
		$reflection->getProperty('user')->setValue($subject, (object) ['id' => 1]);

		return $subject;
	}

	/**
	 * A database that quotes deterministically and finds nothing.
	 *
	 * @return  DatabaseInterface&\PHPUnit\Framework\MockObject\Stub
	 * @since   6.1.7
	 */
	private function database(): DatabaseInterface
	{
		$db = $this->createStub(DatabaseInterface::class);
		$db->method('quoteName')->willReturnCallback(
			static fn (string|array $name): string|array => is_array($name)
				? array_map(static fn (string $value): string => '[' . $value . ']', $name)
				: '[' . $name . ']'
		);
		$db->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);
		$db->method('getQuery')->willReturnCallback(fn (): QueryInterface => $this->query());
		$db->method('getDateFormat')->willReturn('Y-m-d H:i:s');
		$db->method('getNumRows')->willReturn(0);
		$db->method('loadAssocList')->willReturn([]);
		$db->method('loadObjectList')->willReturn([]);

		return $db;
	}

	/**
	 * A query that answers itself to every clause it is handed.
	 *
	 * @return  QueryInterface&\PHPUnit\Framework\MockObject\Stub
	 * @since   6.1.7
	 */
	private function query(): QueryInterface
	{
		$query = $this->createStub(QueryInterface::class);
		foreach (['select', 'from', 'where', 'update', 'insert', 'columns', 'values'] as $clause)
		{
			$query->method($clause)->willReturnSelf();
		}

		return $query;
	}

	/**
	 * A counter's valuation, which this compile never asks anything of.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function valuation(): object
	{
		return (new ReflectionClass(
			'VDM\\Joomla\\Componentbuilder\\Compiler\\Utilities\\Valuation'
		))->newInstanceWithoutConstructor();
	}

	/**
	 * Remove a directory and everything under it.
	 *
	 * @param   string  $path  What to remove.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function remove(string $path): void
	{
		if (!is_dir($path))
		{
			return;
		}

		foreach (scandir($path) as $entry)
		{
			if ($entry === '.' || $entry === '..')
			{
				continue;
			}

			$full = $path . '/' . $entry;
			is_dir($full) ? $this->remove($full) : unlink($full);
		}

		rmdir($path);
	}
}
