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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Customcode\Extractor;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Extractor\Paths;
use VDM\Tests\Support\CompilerDomainTestCase;
use VDM\Tests\Support\CustomcodePathsFixture;


/**
 * Installed extension path-discovery contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Paths::class)]
final class PathsTest extends CompilerDomainTestCase
{
	/**
	 * Retain resolved directories and remove missing component and module paths.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDiscoveryKeepsOnlyExistingResolvedPaths(): void
	{
		$config = $this->compilerConfig([
			'component_code_name' => 'test_component_that_is_not_installed',
			'jcb_powers_path' => 'missing-test-power-directory',
		]);
		$subject = new CustomcodePathsFixture(
			$config,
			[11, 22],
			[11 => JPATH_ROOT, 22 => JPATH_ROOT . '/missing-module-directory']
		);

		$this->assertSame([
			'module_' . str_replace('/', '_', JPATH_ROOT) => JPATH_ROOT,
		], $subject->discover());
	}

	/**
	 * An installation without associated extension paths produces an empty set.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDiscoveryReturnsEmptyWhenNoCandidateDirectoryExists(): void
	{
		$config = $this->compilerConfig([
			'component_code_name' => 'another_missing_test_component',
			'jcb_powers_path' => 'another-missing-test-power-directory',
		]);

		$this->assertSame([], (new CustomcodePathsFixture($config, false, []))->discover());
	}
}
