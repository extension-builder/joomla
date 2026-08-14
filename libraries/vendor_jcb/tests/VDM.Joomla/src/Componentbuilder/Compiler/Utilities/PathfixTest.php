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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Utilities;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Pathfix;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * Generated-script path normalization contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Pathfix::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(StringHelper::class)]
final class PathfixTest extends TestCase
{
	/**
	 * Convert every backslash in a scalar path without other normalization.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testScalarPathUsesForwardSlashes(): void
	{
		$value = 'C:\\workspace\\component\\src\\File.php';

		(new Pathfix())->set($value);

		$this->assertSame('C:/workspace/component/src/File.php', $value);
	}

	/**
	 * Recursively normalize only named path fields and preserve all other data.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testArrayNormalizationIsRestrictedToRequestedTargets(): void
	{
		$values = [
			'path' => 'root\\first',
			'untouched' => 'root\\second',
			'nested' => [
				'path' => 'nested\\third',
				'untouched' => 'nested\\fourth'
			]
		];

		(new Pathfix())->set($values, ['path', 'nested']);

		$this->assertSame(
			[
				'path' => 'root/first',
				'untouched' => 'root\\second',
				'nested' => [
					'path' => 'nested/third',
					'untouched' => 'nested\\fourth'
				]
			],
			$values
		);
	}

	/**
	 * Leave forward-slash paths, empty values, and non-string values unchanged.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAlreadyPortableAndNonStringValuesRemainUnchanged(): void
	{
		$subject = new Pathfix();
		$values = ['portable/path', '', null, 42, false];

		foreach ($values as &$value)
		{
			$subject->set($value);
		}
		unset($value);

		$this->assertSame(['portable/path', '', null, 42, false], $values);
	}
}
