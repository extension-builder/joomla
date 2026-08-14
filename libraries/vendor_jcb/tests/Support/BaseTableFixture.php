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


use VDM\Joomla\Abstraction\BaseTable;


/**
 * Table metadata fixture with two representative entity catalogs.
 *
 * @since  1.0.0
 */
final class BaseTableFixture extends BaseTable
{
	/**
	 * Reviewed table field definitions.
	 *
	 * @var    array<string, array<string, array<string, mixed>>>
	 * @since  1.0.0
	 */
	protected array $tables = [
		'power' => [
			'system_name' => [
				'name' => 'system_name',
				'label' => 'System Name',
				'type' => 'text',
				'title' => true,
				'list' => 'powers'
			],
			'namespace' => [
				'name' => 'namespace',
				'label' => 'Namespace',
				'type' => 'text',
				'title' => false,
				'list' => 'powers'
			],
			'id' => [
				'name' => 'id',
				'label' => 'Custom ID',
				'type' => 'number',
				'title' => false,
				'list' => null
			]
		],
		'repository' => [
			'guid' => [
				'name' => 'guid',
				'label' => 'GUID',
				'type' => 'text',
				'title' => false,
				'list' => 'repositories'
			]
		]
	];
}
