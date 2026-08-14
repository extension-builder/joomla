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

namespace VDM\Joomla\Tests\Utilities\String;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Utilities\String\ComponentCodeNameHelper;


/**
 * Component namespace code-name normalization test.
 *
 * @since  6.1.6
 */
#[CoversClass(ComponentCodeNameHelper::class)]
final class ComponentCodeNameHelperTest extends TestCase
{
	/**
	 * Remove separators and punctuation while preserving letters and numbers.
	 *
	 * @param   string  $input     Candidate component name.
	 * @param   string  $expected  Expected code name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('componentNameProvider')]
	public function testSafeBuildsComponentCodeName(string $input, string $expected): void
	{
		$this->assertSame($expected, ComponentCodeNameHelper::safe($input));
	}

	/**
	 * Provide whitespace, punctuation, Unicode, number, and empty cases.
	 *
	 * @return  iterable<string, array{string, string}>
	 * @since   6.1.6
	 */
	public static function componentNameProvider(): iterable
	{
		yield 'words and separators' => [' my_component-name ', 'Mycomponentname'];
		yield 'punctuation removed' => ['component.builder!', 'Componentbuilder'];
		yield 'unicode letters retained' => ['Über Werk', 'ÜberWerk'];
		yield 'numbers retained' => ['component 6', 'Component6'];
		yield 'only punctuation' => [' _-! ', ''];
	}

	/**
	 * Require the generated code name to be legal as a PHP namespace segment.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testSafePreventsNumericNamespacePrefix(): void
	{
		$this->assertMatchesRegularExpression(
			'/^[\p{L}_]/u',
			ComponentCodeNameHelper::safe('6 component')
		);
	}
}
