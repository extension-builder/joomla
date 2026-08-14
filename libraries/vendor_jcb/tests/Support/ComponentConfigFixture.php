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

namespace VDM\Tests\Support;

use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;

/**
 * Component configuration fixture with an observable dynamic getter.
 *
 * @since  1.0.0
 */
final class ComponentConfigFixture extends ComponentConfig
{
	/**
	 * Dynamic getter invocation count.
	 *
	 * @var    int
	 * @since  1.0.0
	 */
	public int $dynamicCalls = 0;

	/**
	 * Resolve the generated value before component params and request input.
	 *
	 * @param   mixed  $default  Caller fallback.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function getGeneratedvalue(mixed $default): string
	{
		$this->dynamicCalls++;

		return 'generated-value';
	}
}
