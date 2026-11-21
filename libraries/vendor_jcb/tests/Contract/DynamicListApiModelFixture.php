<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    5th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use Joomla\CMS\MVC\Model\ListModel;


/**
 * Declare a custom get on the native model boundary used by API template tests.
 *
 * @since  6.2.1
 */
class DynamicListApiModelFixture extends ListModel
{
	/**
	 * Custom list data that the generated JSON view places in document metadata.
	 *
	 * @return  array<string, int>
	 * @since   6.2.1
	 */
	public function getSummary(): array
	{
		return [];
	}
}
