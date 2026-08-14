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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use VDM\Joomla\Utilities\String\ClassfunctionHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Generated class and function identifier normalization test.
 *
 * @since  6.1.6
 */
#[CoversClass(ClassfunctionHelper::class)]
#[UsesClass(StringHelper::class)]
final class ClassfunctionHelperTest extends TestCase
{
	/**
	 * Normalize leading numbers and remove characters outside the retained set.
	 *
	 * @param   mixed   $input     Candidate identifier.
	 * @param   string  $expected  Expected generated identifier.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('identifierProvider')]
	public function testSafeBuildsClassFunctionIdentifier(mixed $input, string $expected): void
	{
		$this->assertSame($expected, ClassfunctionHelper::safe($input));
	}

	/**
	 * Provide number, punctuation, retained separator, and empty cases.
	 *
	 * @return  iterable<string, array{mixed, string}>
	 * @since   6.1.6
	 */
	public static function identifierProvider(): iterable
	{
		yield 'leading number becomes words' => ['12 Build@View', 'twelveBuildView'];
		yield 'letters digits underscore and hyphen remain' => ['Build_6-View', 'Build_6-View'];
		yield 'spaces and punctuation are removed' => [' Build view! ', 'Buildview'];
		yield 'non ascii is removed' => ['Módèl', 'Mdl'];
		yield 'null becomes empty' => [null, ''];
	}
}
