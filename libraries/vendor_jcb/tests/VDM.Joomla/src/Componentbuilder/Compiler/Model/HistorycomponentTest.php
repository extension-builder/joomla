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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Model;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HistoryInterface;
use VDM\Joomla\Componentbuilder\Compiler\Model\Historycomponent;
use VDM\Joomla\Componentbuilder\Compiler\Model\Updatesql;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\ObjectHelper;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Component-history state and admin-view update contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Historycomponent::class)]
#[UsesClass(Config::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(ObjectHelper::class)]
final class HistorycomponentTest extends CompilerDomainTestCase
{
	/**
	 * Decode prior admin views and expose a manually advanced component version.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRecordsAdminViewChangesAndPreviousComponentVersion(): void
	{
		$config = $this->compilerConfig([
			'component_id' => 41,
			'component_version' => '2.0.0'
		]);
		$oldAdminViews = [
			['adminview' => 'first-view'],
			['adminview' => 'second-view']
		];
		$historyCalls = [];
		$history = $this->createStub(HistoryInterface::class);
		$history->method('get')->willReturnCallback(
			static function (string $type, int $id) use (&$historyCalls, $oldAdminViews): ?object
			{
				$historyCalls[] = [$type, $id];

				return match ($type)
				{
					'component_admin_views' => (object) [
						'addadmin_views' => json_encode($oldAdminViews, JSON_THROW_ON_ERROR)
					],
					'joomla_component' => (object) ['component_version' => 'v1.4.0'],
					default => null,
				};
			}
		);
		$updateCalls = [];
		$updates = $this->createMock(Updatesql::class);
		$updates->expects($this->once())
			->method('set')
			->willReturnCallback(
				static function ($old, $new, string $type) use (&$updateCalls): void
				{
					$updateCalls[] = [$old, $new, $type];
				}
			);
		$currentAdminViews = [['adminview' => 'current-view']];
		$item = (object) [
			'addadmin_views_id' => 73,
			'addadmin_views' => $currentAdminViews
		];

		(new Historycomponent($config, $history, $updates))->set($item);

		$this->assertSame([
			['component_admin_views', 73],
			['joomla_component', 41]
		], $historyCalls);
		$this->assertSame([[$oldAdminViews, $currentAdminViews, 'adminview']], $updateCalls);
		$this->assertSame('1.4.0', $item->old_component_version);
	}

	/**
	 * Avoid creating manual-version state when history matches the active version.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetLeavesVersionStateUnsetWhenVersionDidNotChange(): void
	{
		$config = $this->compilerConfig([
			'component_id' => 7,
			'component_version' => '2.0.0'
		]);
		$history = $this->createMock(HistoryInterface::class);
		$history->expects($this->once())
			->method('get')
			->with('joomla_component', 7)
			->willReturn((object) ['component_version' => 'v2.0.0']);
		$updates = $this->createMock(Updatesql::class);
		$updates->expects($this->never())->method('set');
		$item = (object) [];

		(new Historycomponent($config, $history, $updates))->set($item);

		$this->assertObjectNotHasProperty('old_component_version', $item);
	}

	/**
	 * Ignore malformed historical admin-view JSON without touching update state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRejectsMalformedHistoricalAdminViews(): void
	{
		$config = $this->compilerConfig();
		$history = $this->createMock(HistoryInterface::class);
		$history->expects($this->once())
			->method('get')
			->with('component_admin_views', 5)
			->willReturn((object) ['addadmin_views' => '{invalid']);
		$updates = $this->createMock(Updatesql::class);
		$updates->expects($this->never())->method('set');
		$item = (object) [
			'addadmin_views_id' => 5,
			'addadmin_views' => [['adminview' => 'current']]
		];

		(new Historycomponent($config, $history, $updates))->set($item);

		$this->assertObjectNotHasProperty('old_component_version', $item);
	}
}
