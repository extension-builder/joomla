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

namespace VDM\Joomla\Tests\Componentbuilder\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Utilities\Constantpaths;
use VDM\Joomla\Componentbuilder\Utilities\Normalize;
use VDM\Tests\Support\TestCase;


/**
 * Path normalization and deterministic key contract tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Normalize::class)]
#[UsesClass(Constantpaths::class)]
final class NormalizeTest extends TestCase
{
	/**
	 * Produce stable UUID-v5 keys independent of path separator and case.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testKeyIsStableAcrossCaseAndDirectorySeparators(): void
	{
		$subject = new Normalize();

		$this->assertSame(
			'e82f143f-f864-5b44-aa58-cae07b83dc95',
			$subject->key('images/logo.svg')
		);
		$this->assertSame(
			$subject->key('images/logo.svg'),
			$subject->key('Images\\Logo.SVG')
		);
		$this->assertMatchesRegularExpression(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
			$subject->key('a b+c?d=e&f#x%')
		);
		$this->assertSame(
			'e7d10bdc-f594-5a0f-806e-b7cb9ffa5bcd',
			$subject->key('a b+c?d=e&f#x%')
		);
	}

	/**
	 * Resolve existing files and folders with their relative path and key suffix.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPathDescribesExistingFilesAndDirectories(): void
	{
		$subject = new Normalize();

		$this->assertSame(
			[
				'path' => 'index.php',
				'full' => JPATH_ROOT . '/index.php',
				'key' => '3a85830a-8275-51ac-8594-29253cdb2362.php'
			],
			$subject->path('index.php', 'full')
		);
		$this->assertSame(
			[
				'path' => 'administrator',
				'full' => JPATH_ROOT . '/administrator',
				'key' => '3f3ff483-3ec0-5ed8-9252-84a310b53084.zip'
			],
			$subject->path('administrator', 'full')
		);
	}

	/**
	 * Reject unsupported scopes and paths that do not exist.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInvalidTargetsAndMissingPathsReturnNull(): void
	{
		$subject = new Normalize();

		$this->assertNull($subject->full('index.php', 'unsupported'));
		$this->assertNull($subject->path('not-a-real-jcb-test-file.txt', 'full'));
		$this->assertNull($subject->path('index.php', 'unsupported'));
	}

	/**
	 * Expand known constants while preserving an already rooted full path.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFullExpandsKnownConstantsAndBuildsScopedPaths(): void
	{
		$subject = new Normalize();

		$this->assertSame(JPATH_ROOT . '/index.php', $subject->full('index.php', 'full'));
		$this->assertSame(
			JPATH_ROOT . '/administrator/index.php',
			$subject->full('JPATH_ADMINISTRATOR/index.php', 'full')
		);
		$this->assertSame(
			JPATH_ADMINISTRATOR . '/components/com_componentbuilder/custom/example.php',
			$subject->full('/example.php', 'custom')
		);
		$this->assertSame(JPATH_SITE . '/images/logo.svg', $subject->full('logo.svg', 'image'));
	}

	/**
	 * Keep reconstructed paths inside the selected scope.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testFullRejectsParentDirectoryTraversal(): void
	{
		$this->assertNull((new Normalize())->full('../../outside-jcb-root', 'images'));
	}
}
