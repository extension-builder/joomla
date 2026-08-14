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


use VDM\Joomla\Abstraction\FunctionRegistry;


/**
 * Dynamic-getter registry fixture with an observable getter call count.
 *
 * @since  1.0.0
 */
final class FunctionRegistryFixture extends FunctionRegistry
{
	/**
	 * Number of dynamic getter calls.
	 *
	 * @var    int
	 * @since  1.0.0
	 */
	public int $dynamicCalls = 0;

	/**
	 * Resolve the dynamic_value registry path.
	 *
	 * @param   mixed  $default  Caller fallback.
	 *
	 * @return  string  Derived registry value.
	 * @since   1.0.0
	 */
	protected function getDynamicvalue(mixed $default): string
	{
		$this->dynamicCalls++;

		return 'derived:' . (string) $default;
	}
}
