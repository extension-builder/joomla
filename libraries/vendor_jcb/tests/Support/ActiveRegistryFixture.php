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


use VDM\Joomla\Abstraction\ActiveRegistry;


/**
 * Concrete ActiveRegistry fixture with explicit policy controls.
 *
 * @since  1.0.0
 */
final class ActiveRegistryFixture extends ActiveRegistry
{
	/**
	 * Select the class-level default add policy.
	 *
	 * @param   bool  $state  True to add values as array elements.
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function addValuesAsArrays(bool $state = true): self
	{
		$this->addAsArray = $state;

		return $this;
	}

	/**
	 * Select whether array additions must remain unique.
	 *
	 * @param   bool  $state  True to suppress duplicate array values.
	 *
	 * @return  self
	 * @since   1.0.0
	 */
	public function keepArrayValuesUnique(bool $state = true): self
	{
		$this->uniqueArray = $state;

		return $this;
	}
}
