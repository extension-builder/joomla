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


use VDM\Joomla\Abstraction\SchemaChecker;


/**
 * Concrete schema-checker fixture with intentionally absent fallback classes.
 *
 * @since  1.0.0
 */
final class SchemaCheckerFixture extends SchemaChecker
{
	/**
	 * Get the fixture component code.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function getCode(): string
	{
		return 'fixture';
	}

	/**
	 * Get the fixture power path.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function getPowerPath(): string
	{
		return 'VDM.Joomla';
	}

	/**
	 * Get a deliberately absent schema class.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function getSchemaClass(): string
	{
		return 'VDM\\Tests\\Fixtures\\AbsentSchema';
	}

	/**
	 * Get a deliberately absent table class.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	protected function getTableClass(): string
	{
		return 'VDM\\Tests\\Fixtures\\AbsentTable';
	}
}
