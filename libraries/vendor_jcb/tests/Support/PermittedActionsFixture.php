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


use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use VDM\Joomla\Componentbuilder\Utilities\Permitted\Actions;


/**
 * Fixture exposing the pure and orchestration boundaries of permitted actions.
 *
 * @since  6.1.6
 */
final class PermittedActionsFixture extends Actions
{
	/**
	 * Normalize a value through the production ACL string policy.
	 *
	 * @param   mixed  $value      Value to normalize.
	 * @param   bool   $allowNull  Whether null is permitted.
	 *
	 * @return  string|null  Normalized value.
	 * @since   6.1.6
	 */
	public static function safeValue(mixed $value, bool $allowNull = false): ?string
	{
		return parent::safe($value, $allowNull);
	}

	/**
	 * Normalize a target selector.
	 *
	 * @param   mixed  $target  Target selector.
	 *
	 * @return  array<int|string, mixed>  Normalized target list.
	 * @since   6.1.6
	 */
	public static function targets(mixed $target): array
	{
		return parent::normalizeTargets($target);
	}

	/**
	 * Report whether an action is filtered out.
	 *
	 * @param   string         $view     View name.
	 * @param   string         $action   Action name.
	 * @param   array<string>  $targets  Requested target suffixes.
	 *
	 * @return  bool  True when filtered out.
	 * @since   6.1.6
	 */
	public static function filtered(string $view, string $action, array $targets): bool
	{
		return parent::filterActions($view, $action, $targets);
	}

	/**
	 * Process one action through item, category, and component scopes.
	 *
	 * @param   string       $action     Action name.
	 * @param   Registry     $result     Result registry.
	 * @param   User         $user       Acting user.
	 * @param   string       $component  Component name.
	 * @param   string       $view       Singular view.
	 * @param   string|null  $views      Plural view.
	 * @param   object|null &$record     Optional record.
	 * @param   array        $targets    Requested target suffixes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public static function process(
		string $action,
		Registry $result,
		User $user,
		string $component,
		string $view,
		?string $views,
		?object &$record,
		array $targets = []
	): void
	{
		parent::processAction(
			$action,
			$result,
			$user,
			$component,
			$view,
			$views,
			$record,
			$targets,
			parent::componentActions()
		);
	}

	/**
	 * Build the three ACL asset identities.
	 *
	 * @param   string  $component  Component name.
	 * @param   string  $view       Singular view.
	 * @param   string  $views      Plural view.
	 * @param   int     $id         Record identifier.
	 * @param   int     $category   Category identifier.
	 *
	 * @return  array{component: string, item: string, category: string}  Asset identities.
	 * @since   6.1.6
	 */
	public static function assets(
		string $component,
		string $view,
		string $views,
		int $id,
		int $category
	): array
	{
		return [
			'component' => parent::assetComponent($component),
			'item' => parent::assetItem($component, $view, $id),
			'category' => parent::assetCategory($component, $views, $category)
		];
	}

	/**
	 * Convert a view action to its category-scope equivalent.
	 *
	 * @param   string  $action  Action name.
	 * @param   string  $view    View name.
	 *
	 * @return  string  Category action name.
	 * @since   6.1.6
	 */
	public static function categoryAction(string $action, string $view): string
	{
		return parent::categoryActionName($action, $view);
	}
}
