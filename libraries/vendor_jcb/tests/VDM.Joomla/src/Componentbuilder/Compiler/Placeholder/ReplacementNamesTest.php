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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Placeholder;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder\ReplacementNames;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Files;
use VDM\Tests\Support\TestCase;


/**
 * The placeholder names still standing in the files the compiler wrote.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Placeholder')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ReplacementNamesTest extends TestCase
{
	/**
	 * The directory the files under test are written into.
	 *
	 * @var    string|null
	 * @since  6.1.7
	 */
	private ?string $directory = null;

	/**
	 * Remove whatever a test wrote.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	protected function tearDown(): void
	{
		if ($this->directory !== null)
		{
			foreach (glob($this->directory . '/*') as $file)
			{
				unlink($file);
			}
			rmdir($this->directory);
			$this->directory = null;
		}

		parent::tearDown();
	}

	/**
	 * Every text a marker pair can be read out of.
	 *
	 * @return  array<string, array{string, array<string>}>
	 * @since   6.1.7
	 */
	public static function texts(): array
	{
		return [
			'one name' => ['a ###ONE### b', ['ONE']],
			'the same name twice' => ['###ONE### and ###ONE###', ['ONE', 'ONE']],
			'two names' => ['###ONE### ###TWO###', ['ONE', 'TWO']],
			'nothing between the markers' => ['a ###### b', ['']],
			'no markers at all' => ['plain text', []],
			'nothing' => ['', []]
		];
	}

	/**
	 * The names between the markers are read out of a text.
	 *
	 * @param   string         $text      The text to read.
	 * @param   array<string>  $expected  The names it carries.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('texts')]
	public function testTheNamesBetweenTheMarkersAreReadOut(string $text, array $expected): void
	{
		$subject = new ReplacementNames($this->createStub(Files::class));

		$this->assertSame($expected, $subject->inbetween($text));
	}

	/**
	 * A caller may name the markers it wants read between.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACallerMayNameTheMarkers(): void
	{
		$subject = new ReplacementNames($this->createStub(Files::class));

		$this->assertSame(['ONE'], $subject->inbetween('[[[ONE]]]', '\[\[\[', '\]\]\]'));
	}

	/**
	 * Every name the files still carry is reported, once each.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryNameTheFilesCarryIsReportedOnce(): void
	{
		$directory = $this->files([
			'one.php' => 'a ###FIRST### b ###SECOND### c ###FIRST###',
			'two.php' => 'x ###THIRD### y'
		]);

		$subject = new ReplacementNames($this->filesAt($directory));

		ob_start();
		$subject->get();
		$reported = ob_get_clean();

		$this->assertSame(
			'###FIRST###<br /><br />###SECOND###<br /><br />###THIRD###<br /><br />',
			$reported
		);
	}

	/**
	 * A file the compiler wrote nothing into reports nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFileWithNoNamesReportsNothing(): void
	{
		$directory = $this->files([
			'one.php' => 'nothing to fill in here',
			'two.php' => 'nor here'
		]);

		$subject = new ReplacementNames($this->filesAt($directory));

		ob_start();
		$subject->get();

		$this->assertSame('', ob_get_clean());
	}

	/**
	 * Write the given files, and answer where they were written.
	 *
	 * @param   array<string, string>  $files  The files, by name.
	 *
	 * @return  string  The directory they are in.
	 * @since   6.1.7
	 */
	private function files(array $files): string
	{
		$this->directory = sys_get_temp_dir() . '/jcb-replacement-names-' . getmypid();
		if (!is_dir($this->directory))
		{
			mkdir($this->directory, 0777, true);
		}

		foreach ($files as $name => $content)
		{
			file_put_contents($this->directory . '/' . $name, $content);
		}

		return $this->directory;
	}

	/**
	 * A Files double that answers with the two files written above: one the
	 * compiler holds on its own, and one it holds a list of.
	 *
	 * @param   string  $directory  Where the files are.
	 *
	 * @return  Files
	 * @since   6.1.7
	 */
	private function filesAt(string $directory): Files
	{
		$files = $this->createStub(Files::class);
		$files->method('toArray')->willReturn([
			'static' => ['one' => ['path' => $directory . '/one.php']],
			'dynamic' => ['two' => [['path' => $directory . '/two.php']]]
		]);

		return $files;
	}
}
