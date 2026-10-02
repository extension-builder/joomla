<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Controller;


/**
 * The database and application boundaries of a generated getItem() model.
 *
 * @since 6.1.7
 */
class GeneratedItemModelFixture
{
	/**
	 * The already loaded database record.
	 *
	 * @var   object|false
	 * @since 6.1.7
	 */
	private object|false $item;

	/**
	 * The request application.
	 *
	 * @var   object
	 * @since 6.1.7
	 */
	private object $app;

	/**
	 * Whether the administrator may edit the record.
	 *
	 * @var   bool
	 * @since 6.1.7
	 */
	private bool $editable;

	/**
	 * The number of calls to the administrator edit guard.
	 *
	 * @var   int
	 * @since 6.1.7
	 */
	public int $editChecks = 0;

	/**
	 * Supply the boundaries without a Joomla database or application bootstrap.
	 *
	 * @param   object|false  $item      The loaded record.
	 * @param   object        $app       The request application.
	 * @param   bool          $editable  The administrator edit permission.
	 *
	 * @since   6.1.7
	 */
	public function __construct(object|false $item, object $app, bool $editable)
	{
		$this->item = $item;
		$this->app = $app;
		$this->editable = $editable;
	}

	/**
	 * Return the record at the parent model's database boundary.
	 *
	 * @param   int|null  $pk  The requested numeric record id.
	 *
	 * @return  object|false  The loaded record.
	 * @since   6.1.7
	 */
	public function getItem($pk = null)
	{
		return $this->item;
	}

	/**
	 * Resolve the application at the generated Joomla Power boundary.
	 *
	 * @return  object  The request application.
	 * @since   6.1.7
	 */
	protected function getApplication(): object
	{
		return $this->app;
	}

	/**
	 * Resolve language keys at the generated Joomla Power boundary.
	 *
	 * @param   string  $key  The language key or literal administrator message.
	 *
	 * @return  string  The same key without loading an installed language.
	 * @since   6.1.7
	 */
	protected static function _(string $key): string
	{
		return $key;
	}

	/**
	 * Record the administrator edit check without granting API read access.
	 *
	 * @param   array   $data  The loaded record data.
	 * @param   string  $key   The primary-key field.
	 *
	 * @return  bool  The supplied administrator permission.
	 * @since   6.1.7
	 */
	protected function allowEdit(array $data = [], string $key = 'id'): bool
	{
		$this->editChecks++;

		return $this->editable;
	}
}
