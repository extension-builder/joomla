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


use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use VDM\Joomla\Utilities\ClassHelper;
use VDM\Tests\Support\TestCase;


/**
 * Component-local class-loader discovery contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(ClassHelper::class)]
final class ClassHelperTest extends TestCase
{
	/**
	 * Unique component code name owned by the current test.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private string $componentCodeName;

	/**
	 * Isolated component directory owned by the current test.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private string $fixtureDirectory;

	/**
	 * Whether the current test created the fixture directory.
	 *
	 * @var    bool
	 * @since  6.1.6
	 */
	private bool $fixtureCreated = false;

	/**
	 * Create one narrowly owned component fixture directory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->fixtureCreated = false;

		$componentsDirectory = JPATH_ADMINISTRATOR . '/components';

		if (!is_dir($componentsDirectory))
		{
			throw new RuntimeException(
				'The class-helper component root is unavailable: ' . $componentsDirectory
			);
		}

		$this->componentCodeName = 'jcb_class_helper_tests_'
			. bin2hex(random_bytes(8));
		$this->fixtureDirectory = $componentsDirectory
			. '/com_' . $this->componentCodeName;

		if (file_exists($this->fixtureDirectory))
		{
			throw new RuntimeException(
				'Refusing to reuse an existing class-helper fixture: ' . $this->fixtureDirectory
			);
		}

		if (!mkdir($this->fixtureDirectory, 0700))
		{
			throw new RuntimeException(
				'Unable to create class-helper fixture: ' . $this->fixtureDirectory
			);
		}

		$this->fixtureCreated = true;
	}

	/**
	 * Remove only files and the exact directory created by this test.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		try
		{
			if (!$this->fixtureCreated)
			{
				return;
			}

			$failures = [];

			foreach (['loader.php', 'empty-loader.php'] as $name)
			{
				$file = $this->fixtureDirectory . '/' . $name;

				if ((is_link($file) || is_file($file)) && !unlink($file))
				{
					$failures[] = $file;
				}
				elseif (file_exists($file))
				{
					$failures[] = $file;
				}
			}

			if (is_link($this->fixtureDirectory))
			{
				if (!unlink($this->fixtureDirectory))
				{
					$failures[] = $this->fixtureDirectory;
				}
			}
			elseif (is_dir($this->fixtureDirectory) && !rmdir($this->fixtureDirectory))
			{
				$failures[] = $this->fixtureDirectory;
			}
			elseif (file_exists($this->fixtureDirectory))
			{
				$failures[] = $this->fixtureDirectory;
			}

			if ($failures !== [])
			{
				throw new RuntimeException(
					'Unable to remove class-helper fixtures: ' . implode(', ', array_unique($failures))
				);
			}
		}
		finally
		{
			$this->fixtureCreated = false;
			parent::tearDown();
		}
	}

	/**
	 * Return immediately when normal autoloading already resolved the class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExistingClassDoesNotRequireAComponentLoader(): void
	{
		$this->assertTrue(
			ClassHelper::exists(self::class, 'missing_component', 'missing-loader.php')
		);
	}

	/**
	 * Return false when neither Composer nor the component loader resolves the class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMissingClassAndLoaderReturnFalse(): void
	{
		$this->assertFalse(
			ClassHelper::exists(
				'VDM\\Joomla\\Tests\\Fixtures\\NeverDefinedClass',
				$this->componentCodeName,
				'missing-loader.php'
			)
		);
	}

	/**
	 * Load the component's declared autoloader and re-check the requested class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testComponentLoaderCanDefineTheRequestedClass(): void
	{
		$this->writeLoader(
			'loader.php',
			'namespace VDM\\Joomla\\Tests\\Fixtures; final class LoadedByClassHelper {}'
		);

		$this->assertTrue(
			ClassHelper::exists(
				'VDM\\Joomla\\Tests\\Fixtures\\LoadedByClassHelper',
				$this->componentCodeName,
				'loader.php'
			)
		);
	}

	/**
	 * Return false when an existing loader does not provide the requested class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLoaderThatDoesNotDefineRequestedClassReturnsFalse(): void
	{
		$this->writeLoader('empty-loader.php', 'return true;');

		$this->assertFalse(
			ClassHelper::exists(
				'VDM\\Joomla\\Tests\\Fixtures\\StillUndefinedClass',
				$this->componentCodeName,
				'empty-loader.php'
			)
		);
	}

	/**
	 * Write one PHP loader inside the exact owned fixture directory.
	 *
	 * @param   string  $name  Loader filename.
	 * @param   string  $body  Executable PHP body.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function writeLoader(string $name, string $body): void
	{
		$file = $this->fixtureDirectory . '/' . $name;

		if (file_put_contents($file, "<?php\n" . $body . "\n") === false)
		{
			throw new RuntimeException('Unable to write class-helper fixture: ' . $file);
		}
	}
}
