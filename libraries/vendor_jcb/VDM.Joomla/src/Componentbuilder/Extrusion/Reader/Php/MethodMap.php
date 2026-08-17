<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Reader\Php;


/**
 * Names the admin view column that one Joomla method belongs in.
 *
 * The extruded method bodies mean nothing until each one is told where it
 * lands, and that correspondence is a fact about JCB's own schema rather than
 * something a reader can work out. It is held here as data, in one place, so
 * the candidate custom code phase stays a matter of lookups.
 *
 * The map is deliberately narrow. Only the methods JCB compiles from a php_*
 * column appear, because a method that has no column has nowhere to go and
 * offering it as a candidate would be an invitation to lose it. Everything else
 * a model declares -- a helper the author wrote, an override JCB does not
 * generate -- is simply not this map's business.
 *
 * The switches are listed separately from the columns and mirror
 * #__componentbuilder_admin_view. Two things about them matter. A column is not
 * compiled unless its switch is on, so the phase must set the switch as well as
 * the body, and the extrusion design is explicit that it leaves the switches
 * off: a candidate is offered, never silently enabled. And not every php_*
 * column has one -- php_model and php_controller are always compiled -- which
 * is why the lookup answers null rather than guessing a name by prefixing.
 *
 * @since 6.1.6
 */
final class MethodMap
{
	/**
	 * The Joomla method to admin view column map.
	 *
	 * The keys are the methods as Joomla declares them, so the map reads the way
	 * the source does. Lookups are case insensitive, so the casing here is for
	 * the reader's benefit rather than the machine's.
	 *
	 * @var    array<string, string>
	 * @since  6.1.6
	 */
	private const COLUMNS = [
		'getItem' => 'php_getitem',
		'getItems' => 'php_getitems',
		'getListQuery' => 'php_getlistquery',
		'save' => 'php_save',
		'postSaveHook' => 'php_postsavehook',
		'getForm' => 'php_getform',
		'allowAdd' => 'php_allowadd',
		'allowEdit' => 'php_allowedit',
		'batchCopy' => 'php_batchcopy',
		'batchMove' => 'php_batchmove',
		'publish' => 'php_before_publish',
		'delete' => 'php_before_delete',
		'cancel' => 'php_before_cancel',
		'document' => 'php_document'
	];

	/**
	 * The admin view column to its own switch map.
	 *
	 * Every php_* column of #__componentbuilder_admin_view that is guarded by a
	 * switch is listed, not only the columns this map's methods reach, so the
	 * answer stays right when a caller holds a column that came from elsewhere.
	 * The one entry that does not follow the add_ plus column shape is the ajax
	 * method, which is why the names are written out instead of derived.
	 *
	 * @var    array<string, string>
	 * @since  6.1.6
	 */
	private const SWITCHES = [
		'php_getitem' => 'add_php_getitem',
		'php_getitems' => 'add_php_getitems',
		'php_getitems_after_all' => 'add_php_getitems_after_all',
		'php_getlistquery' => 'add_php_getlistquery',
		'php_getform' => 'add_php_getform',
		'php_before_save' => 'add_php_before_save',
		'php_save' => 'add_php_save',
		'php_postsavehook' => 'add_php_postsavehook',
		'php_allowadd' => 'add_php_allowadd',
		'php_allowedit' => 'add_php_allowedit',
		'php_before_cancel' => 'add_php_before_cancel',
		'php_after_cancel' => 'add_php_after_cancel',
		'php_batchcopy' => 'add_php_batchcopy',
		'php_batchmove' => 'add_php_batchmove',
		'php_before_publish' => 'add_php_before_publish',
		'php_after_publish' => 'add_php_after_publish',
		'php_before_delete' => 'add_php_before_delete',
		'php_after_delete' => 'add_php_after_delete',
		'php_document' => 'add_php_document',
		'php_ajaxmethod' => 'add_php_ajax'
	];

	/**
	 * The admin view column one Joomla method belongs in.
	 *
	 * @param   string  $method  The method name, as the source declares it.
	 *
	 * @return  string|null  The column name, or null when the method has no column.
	 * @since   6.1.6
	 */
	public function column(string $method): ?string
	{
		$columns = array_change_key_case(self::COLUMNS, CASE_LOWER);

		return $columns[strtolower(trim($method))] ?? null;
	}

	/**
	 * The whole Joomla method to admin view column map.
	 *
	 * @return  array<string, string>  Method name keyed to its column name.
	 * @since   6.1.6
	 */
	public function columns(): array
	{
		return self::COLUMNS;
	}

	/**
	 * The switch that has to be on for one column to be compiled.
	 *
	 * The name is toggle rather than switch because switch is a reserved word in
	 * PHP and cannot name a method; nothing else is meant by the difference.
	 *
	 * @param   string  $column  The php_* column name.
	 *
	 * @return  string|null  The add_php_* switch name, or null when the column has none.
	 * @since   6.1.6
	 */
	public function toggle(string $column): ?string
	{
		return self::SWITCHES[strtolower(trim($column))] ?? null;
	}

	/**
	 * The whole column to switch map.
	 *
	 * @return  array<string, string>  Column name keyed to its switch name.
	 * @since   6.1.6
	 */
	public function toggles(): array
	{
		return self::SWITCHES;
	}
}
