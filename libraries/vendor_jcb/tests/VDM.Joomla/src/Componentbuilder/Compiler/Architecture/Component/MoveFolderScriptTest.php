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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Component;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The call an install script copies its folders with, per target.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class MoveFolderScriptTest extends ArchitectureTestCase
{
	/**
	 * The call a modern install script makes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN = <<<'GEN'

		// We check if we have dynamic folders to copy
		$this->moveFolders($adapter);
GEN;

	/**
	 * The call a Joomla 3 install script makes, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3 = <<<'GEN'

		// We check if we have dynamic folders to copy
		$this->setDynamicF0ld3rs($app, $parent);
GEN;

	/**
	 * The targets that hand the installer its adapter.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		return [
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
			'Joomla 6' => ['JoomlaSix'],
		];
	}

	/**
	 * Every later target copies its folders through the adapter.
	 *
	 * @param   string  $version  The target being built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testALaterTargetCopiesThroughTheAdapter(string $version): void
	{
		$subject = $this->renderer(
			$this->targetClass($version, 'Component\\MoveFolderScript', ['JoomlaThree']),
			['registry' => $this->withFolders()]
		);

		$this->assertSame(self::EXPECTED_MODERN, $subject->get());
	}

	/**
	 * Joomla 3 copies its folders through the application and the installer.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeCopiesThroughTheApplicationAndInstaller(): void
	{
		$subject = $this->renderer(
			$this->targetClass('JoomlaThree', 'Component\\MoveFolderScript', ['JoomlaThree']),
			['registry' => $this->withFolders()]
		);

		$this->assertSame(self::EXPECTED_J3, $subject->get());
	}

	/**
	 * A component with no folders to move is given no call.
	 *
	 * @param   string  $version  The target being built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAComponentWithNoFoldersIsGivenNoCall(string $version): void
	{
		$subject = $this->renderer(
			$this->targetClass($version, 'Component\\MoveFolderScript', ['JoomlaThree']),
			['registry' => new Registry()]
		);

		$this->assertSame('', $subject->get());
	}

	/**
	 * Joomla 3 with no folders to move is given no call either.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeWithNoFoldersIsGivenNoCallEither(): void
	{
		$subject = $this->renderer(
			$this->targetClass('JoomlaThree', 'Component\\MoveFolderScript', ['JoomlaThree']),
			['registry' => new Registry()]
		);

		$this->assertSame('', $subject->get());
	}

	/**
	 * A compiler registry that found folders to move.
	 *
	 * @return  Registry
	 * @since   6.1.7
	 */
	private function withFolders(): Registry
	{
		$registry = new Registry();
		$registry->set('set_move_folders_install_script', true);

		return $registry;
	}
}
