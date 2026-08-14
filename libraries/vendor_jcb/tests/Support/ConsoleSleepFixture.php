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

namespace VDM\Joomla\Componentbuilder\Abstraction\Console;


/**
 * Replace the import command's presentation delay inside the unit-test process.
 *
 * @param   int  $seconds  Requested delay in seconds.
 *
 * @return  int  Always zero because no delay remains.
 * @since   1.0.0
 */
function sleep(int $seconds): int
{
	return 0;
}
