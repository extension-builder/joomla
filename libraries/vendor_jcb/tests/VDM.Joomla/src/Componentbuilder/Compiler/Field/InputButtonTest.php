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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Field;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionCore;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Permission;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaFive\InputButton as JoomlaFiveInputButton;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaFour\InputButton as JoomlaFourInputButton;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaSix\InputButton as JoomlaSixInputButton;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaThree\InputButton as JoomlaThreeInputButton;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Field\InputButtonInterface;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Target-version add/edit field button generator contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeInputButton::class)]
#[CoversClass(JoomlaFourInputButton::class)]
#[CoversClass(JoomlaFiveInputButton::class)]
#[CoversClass(JoomlaSixInputButton::class)]
#[UsesClass(Config::class)]
#[UsesClass(Placeholder::class)]
#[UsesClass(Permission::class)]
#[UsesClass(PermissionCore::class)]
final class InputButtonTest extends CompilerDomainTestCase
{
	/**
	 * Disabled or incomplete definitions never emit an override.
	 *
	 * @param   class-string<InputButtonInterface>  $class   Target implementation.
	 * @param   bool                                $modern  Modern markup switch.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('targetProvider')]
	public function testInvalidDefinitionsProduceNoOverride(string $class, bool $modern): void
	{
		$subject = $this->subject($class);

		$this->assertSame('', $subject->get([]));
		$this->assertSame('', $subject->get(['add_button' => 'false', 'view' => 'article', 'views' => 'articles']));
		$this->assertSame('', $subject->get(['add_button' => 'true', 'view' => '', 'views' => 'articles']));
	}

	/**
	 * Generated overrides preserve the local component and target-specific client code.
	 *
	 * @param   class-string<InputButtonInterface>  $class   Target implementation.
	 * @param   bool                                $modern  Modern markup switch.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('targetProvider')]
	public function testTargetEmitsReviewedAddAndEditButtonContract(string $class, bool $modern): void
	{
		$output = $this->subject($class)->get([
			'add_button' => 'true',
			'view' => 'article',
			'views' => 'articles'
		]);

		$this->assertStringContainsString('protected function getInput()', $output);
		$this->assertStringContainsString("authorise('core.create', 'com_demo')", $output);
		$this->assertStringContainsString('option=com_demo&amp;view=article', $output);
		$this->assertStringContainsString('task=article.edit&id=', $output);
		$this->assertStringContainsString("COM_DEMO_CREATE_NEW_S", $output);
		$this->assertStringContainsString("COM_DEMO_EDIT_S", $output);

		if ($modern)
		{
			$this->assertStringContainsString("document.addEventListener('DOMContentLoaded'", $output);
			$this->assertStringContainsString('<div class="input-group">', $output);
			$this->assertStringNotContainsString('jQuery(document).ready', $output);
		}
		else
		{
			$this->assertStringContainsString('jQuery(document).ready', $output);
			$this->assertStringContainsString('<div class="input-append">', $output);
		}
	}

	/**
	 * Target implementations and the expected browser API generation.
	 *
	 * @return  array<string, array{class-string<InputButtonInterface>, bool}>
	 * @since   6.1.6
	 */
	public static function targetProvider(): array
	{
		return [
			'Joomla 3' => [JoomlaThreeInputButton::class, false],
			'Joomla 4' => [JoomlaFourInputButton::class, true],
			'Joomla 5' => [JoomlaFiveInputButton::class, true],
			'Joomla 6' => [JoomlaSixInputButton::class, true]
		];
	}

	/**
	 * Build a target implementation without resolving the compiler factory.
	 *
	 * @param   class-string<InputButtonInterface>  $class  Target implementation.
	 *
	 * @return  InputButtonInterface
	 * @since   6.1.6
	 */
	private function subject(string $class): InputButtonInterface
	{
		$config = $this->compilerConfig([
			'component_code_name' => 'demo',
			'lang_prefix' => 'COM_DEMO'
		]);
		$permission = $this->inertCompilerCollaborator(Permission::class);
		$this->setCompilerProperty($permission, 'permissioncore', new PermissionCore());

		return new $class($config, new Placeholder($config), $permission);
	}
}
