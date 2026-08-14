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

namespace VDM\Joomla\Tests\Utilities;


use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Joomla\Utilities\FileHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Local file, archive, discovery, and generated-path contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(FileHelper::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(Helper::class)]
#[UsesClass(StringHelper::class)]
final class FileHelperTest extends FilesystemTestCase
{
	/**
	 * Component option active before the current test.
	 *
	 * @var    mixed
	 * @since  6.1.6
	 */
	private mixed $originalOption;

	/**
	 * Component manifest cache active before the current test.
	 *
	 * @var    array<mixed>
	 * @since  6.1.6
	 */
	private array $originalManifest = [];

	/**
	 * Component parameter cache active before the current test.
	 *
	 * @var    array<mixed>
	 * @since  6.1.6
	 */
	private array $originalParams = [];

	/**
	 * Capture component-helper state before filesystem behavior is exercised.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalOption = Helper::$option;
		$this->originalManifest = Helper::$manifest;
		$this->originalParams = (new ReflectionProperty(Helper::class, 'params'))->getValue();
	}

	/**
	 * Restore component helper caches after every test.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		Helper::$option = $this->originalOption;
		Helper::$manifest = $this->originalManifest;
		(new ReflectionProperty(Helper::class, 'params'))
			->setValue(null, $this->originalParams);
		$this->originalManifest = [];
		$this->originalParams = [];

		parent::tearDown();
	}

	/**
	 * Write and overwrite exact bytes, read them, and detect local existence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLocalContentWriteReadAndExistenceContracts(): void
	{
		$file = $this->temporaryPath('content/data.bin');
		$this->createTemporaryDirectory('content');

		$this->assertTrue(FileHelper::write($file, "first\0payload"));
		$this->assertSame("first\0payload", FileHelper::getContent($file, 'missing'));
		$this->assertTrue(FileHelper::exists($file));
		$this->assertTrue(FileHelper::write($file, 'short'));
		$this->assertSame('short', FileHelper::getContent($file, 'missing'));
		$this->assertFalse(FileHelper::exists($this->temporaryPath('content/missing.bin')));
		$this->assertSame(
			'fallback',
			FileHelper::getContent($this->temporaryPath('content/missing.bin'), 'fallback')
		);
		$this->assertFalse(FileHelper::write('', 'data'));
		$this->assertFalse(FileHelper::write($file, ['not', 'a string']));
	}

	/**
	 * Discover matching files recursively and always restore the process directory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetPathsSupportsArrayStringAndAllFileFilters(): void
	{
		$root = $this->createTemporaryDirectory('tree');
		$this->writeTemporaryFile('tree/root.php', '<?php');
		$this->writeTemporaryFile('tree/nested/app.js', 'const value = 1;');
		$this->writeTemporaryFile('tree/nested/style.css', 'body {}');
		$this->writeTemporaryFile('tree/nested/readme.txt', 'readme');
		$workingDirectory = getcwd();

		$code = FileHelper::getPaths($root, ['\.php$', '\.js$']);
		$styles = FileHelper::getPaths($root, '\.css$');
		$all = FileHelper::getPaths($root, null);

		$this->assertSame($workingDirectory, getcwd());
		$this->assertSame(['/nested/app.js', '/root.php'], $this->sortedPaths($code));
		$this->assertSame(['/nested/style.css'], $this->sortedPaths($styles));
		$this->assertSame(
			['/nested/app.js', '/nested/readme.txt', '/nested/style.css', '/root.php'],
			$this->sortedPaths($all)
		);
		$this->assertNull(FileHelper::getPaths($this->temporaryPath('absent')));
	}

	/**
	 * Create a ZIP without absolute source paths and restore every archived byte.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testZipAndUnzipRoundTripPreservesRelativeTree(): void
	{
		$source = $this->createTemporaryDirectory('archive/source');
		$this->writeTemporaryFile('archive/source/root.txt', 'root bytes');
		$this->writeTemporaryFile('archive/source/nested/child.bin', "child\0bytes");
		$archive = $this->temporaryPath('archive/output/package.zip');
		$extract = $this->temporaryPath('archive/extracted');
		$workingDirectory = getcwd();

		$this->assertTrue(FileHelper::zip($source, $archive));
		$this->assertSame($workingDirectory, getcwd());
		$this->assertFileExists($archive);
		$this->assertTrue(FileHelper::unzip($archive, $extract));
		$this->assertSame('root bytes', file_get_contents($extract . '/root.txt'));
		$this->assertSame("child\0bytes", file_get_contents($extract . '/nested/child.bin'));
	}

	/**
	 * Reject missing source paths, missing archives, and non-ZIP archive extensions.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testArchiveOperationsRejectInvalidInputs(): void
	{
		$archive = $this->temporaryPath('invalid/archive.zip');
		$this->assertFalse(FileHelper::zip($this->temporaryPath('missing'), $archive));
		$this->assertFalse(
			FileHelper::unzip($this->temporaryPath('missing.zip'), $this->temporaryPath('extract'))
		);
		$notZip = $this->writeTemporaryFile('invalid/archive.tar', 'not an archive');
		$this->assertFalse(FileHelper::unzip($notZip, $this->temporaryPath('extract')));
	}

	/**
	 * Build a deterministic hashed local path from component parameters.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetPathUsesConfiguredRootAndDeterministicFilename(): void
	{
		$root = $this->temporaryPath('configured/files');
		Helper::$option = 'com_componentbuilder';
		Helper::$manifest = [
			'com_componentbuilder' => (object) ['namespace' => 'Missing\\Component']
		];
		(new ReflectionProperty(Helper::class, 'params'))->setValue(
			null,
			['com_componentbuilder' => new Registry(['filepath' => $root])]
		);
		$baseKey = 'Th!s_iS_n0t_sAfe_buT_b3tter_then_n0thiug';
		$filename = md5('path' . 'filepath' . $baseKey . 'release') . '.json';

		$this->assertSame(
			'/' . trim($root, '/') . '/' . $filename,
			FileHelper::getPath('path', 'filepath', 'json', 'release', $root, false)
		);
		$this->assertDirectoryDoesNotExist($root);
	}

	/**
	 * Normalize path-list order for assertions without changing path values.
	 *
	 * @param   array|null  $paths  Discovered paths.
	 *
	 * @return  array<int, string>
	 * @since   6.1.6
	 */
	private function sortedPaths(?array $paths): array
	{
		$this->assertIsArray($paths);
		sort($paths);

		return array_values($paths);
	}
}
