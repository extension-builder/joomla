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
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Tests\Support\CompilerUtilityTestCase;


/**
 * Generated debug-line suffix contract test.
 *
 * @since  6.1.6
 */
#[CoversClass(Line::class)]
final class LineTest extends CompilerUtilityTestCase
{
	/**
	 * Emit no suffix when compiler line diagnostics are disabled.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDisabledDiagnosticsProduceNoOutput(): void
	{
		$this->assertSame('', Line::_(147, 'Renderer'));
	}

	/**
	 * Preserve the exact class and source line in an enabled diagnostic suffix.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEnabledDiagnosticsProduceExactSuffix(): void
	{
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, true);

		$this->assertSame(' [Renderer 147]', Line::_(147, 'Renderer'));
		$this->assertSame(' [Namespace\\Class 0]', Line::_(0, 'Namespace\\Class'));
	}
}
