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


use VDM\Joomla\Abstraction\Registry\Traits\GetString;
use VDM\Joomla\Abstraction\Registry\Traits\InArray;
use VDM\Joomla\Abstraction\Registry\Traits\IsArray;
use VDM\Joomla\Abstraction\Registry\Traits\IsString;
use VDM\Joomla\Abstraction\Registry\Traits\PathCount;
use VDM\Joomla\Abstraction\Registry\Traits\PathToString;
use VDM\Joomla\Abstraction\Registry\Traits\VarExport;


/**
 * Concrete registry fixture exposing every reusable registry trait.
 *
 * @since  1.0.0
 */
final class RegistryTraitsFixture extends RegistryFixture
{
	use GetString;
	use InArray;
	use IsArray;
	use IsString;
	use PathCount;
	use PathToString;
	use VarExport;
}
