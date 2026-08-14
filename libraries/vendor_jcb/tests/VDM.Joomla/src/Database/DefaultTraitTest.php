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

namespace VDM\Joomla\Tests\Database;


use PHPUnit\Framework\Attributes\CoversTrait;
use VDM\Joomla\Database\DefaultTrait;
use VDM\Tests\Support\DefaultTraitFixture;
use VDM\Tests\Support\TestCase;


/**
 * Database default-field state transition tests.
 *
 * @since  6.1.6
 */
#[CoversTrait(DefaultTrait::class)]
final class DefaultTraitTest extends TestCase
{
	/**
	 * Enable defaults initially and preserve fluent identity across toggles.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDefaultsToggleIsFluentAndReversible(): void
	{
		$subject = new DefaultTraitFixture();

		$this->assertTrue($subject->defaultsEnabled());
		$this->assertSame($subject, $subject->defaults(false));
		$this->assertFalse($subject->defaultsEnabled());
		$this->assertSame($subject, $subject->defaults());
		$this->assertTrue($subject->defaultsEnabled());
	}
}
