<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Dynamic;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\PrepareItem;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\JoinStructure;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The item preparation of a dynamic get JSON view.
 *
 * @since 6.1.7
 */
#[CoversClass(PrepareItem::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class PrepareItemTest extends ArchitectureTestCase
{
	/**
	 * The item preparation of a view with a multi-row join and a custom get.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ITEM = <<<'GEN'

		// A JSON:API resource needs an id.
		if (!isset($item->id))
		{
			$item->id = (int) $this->getModel()->getState('truck.id');
		}

		// The wheel rows joined to this truck on its id.
		$item->wheel = isset($item->id)
			? $this->getModel()->getIdTruckWheelEacc_B($item->id)
			: [];

		// The drivers custom get of the truck view.
		$item->drivers = $this->getModel()->getDrivers();
GEN;

	/**
	 * A bare item gets the id guard from the model state.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testABareItemGetsTheIdGuardFromTheModelState(): void
	{
		$code = $this->subject()->get($this->resource(), false);

		$this->assertSame(
			PHP_EOL . "\t\t// A JSON:API resource needs an id." . PHP_EOL . "\t\tif (!isset(\$item->id))" . PHP_EOL . "\t\t{"
			. PHP_EOL . "\t\t\t\$item->id = (int) \$this->getModel()->getState('truck.id');" . PHP_EOL . "\t\t}",
			$code
		);
	}

	/**
	 * A list row gets its position as the id guard.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListRowGetsItsPositionAsTheIdGuard(): void
	{
		$code = $this->subject()->get($this->resource(), true);

		$this->assertStringContainsString("\$item->id = ++\$this->position;", $code);
		$this->assertStringNotContainsString('getState', $code);
	}

	/**
	 * The multi-row joins and the custom gets ride along on an item.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheJoinsAndTheCustomGetsRideAlongOnAnItem(): void
	{
		$this->assertSame(self::EXPECTED_ITEM, $this->subject()->get($this->resource(true, true), false));
	}

	/**
	 * A list row takes the joins but leaves the custom gets to the document meta.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListRowTakesTheJoinsButNotTheCustomGets(): void
	{
		$code = $this->subject()->get($this->resource(true, true), true);

		$this->assertStringContainsString("\$item->wheel = isset(\$item->id)", $code);
		$this->assertStringNotContainsString('getDrivers', $code);
	}

	/**
	 * A second join of the same name takes its alias, and unusable gets are ignored.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASecondJoinOfTheSameNameTakesItsAlias(): void
	{
		$resource = $this->resource(true);
		$resource['settings']->main_get->custom_get[] = [
			'key' => 'def456', 'as' => 'c', 'on_field' => 'a.id', 'join_field' => 'c.truck', 'selection' => ['name' => 'wheel'],
		];
		$resource['settings']->main_get->custom_get[] = 'not a join';
		$resource['settings']->main_get->custom_get[] = ['as' => 'd'];

		$code = $this->subject()->get($resource, false);

		$this->assertStringContainsString("\$item->wheel = isset(\$item->id)", $code);
		$this->assertStringContainsString("\$item->wheel_c = isset(\$item->id)", $code);
		$this->assertSame(2, substr_count($code, 'rows joined to this truck'));
	}

	/**
	 * Only item and list custom gets with a method are named.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOnlyItemAndListCustomGetsWithAMethodAreNamed(): void
	{
		$settings = (object) ['custom_get' => [
			(object) ['gettype' => 4, 'getcustom' => 'getDrivers'],
			(object) ['gettype' => 3, 'getcustom' => 'getBest_driver'],
			(object) ['gettype' => 2, 'getcustom' => 'getMain'],
			(object) ['gettype' => 4, 'getcustom' => ''],
			'not a get',
		]];

		$this->assertSame(
			['drivers' => 'getDrivers', 'best_driver' => 'getBest_driver'],
			PrepareItem::customs($settings)
		);
		$this->assertSame([], PrepareItem::customs((object) []));
	}

	/**
	 * The renderer over the real join structure.
	 *
	 * @return  PrepareItem
	 * @since   6.1.7
	 */
	private function subject(): PrepareItem
	{
		return new PrepareItem(new JoinStructure());
	}

	/**
	 * A site view resource, with a multi-row join and a custom get when asked.
	 *
	 * @param   bool  $join    Whether the main get has a multi-row join.
	 * @param   bool  $custom  Whether the view has a custom get.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function resource(bool $join = false, bool $custom = false): array
	{
		$settings = (object) [
			'code' => 'truck',
			'main_get' => (object) ['gettype' => 1, 'custom_get' => []],
			'custom_get' => [],
		];

		if ($join)
		{
			$settings->main_get->custom_get[] = [
				'key' => 'abc123', 'as' => 'b', 'on_field' => 'a.id', 'join_field' => 'b.truck', 'selection' => ['name' => 'wheel'],
			];
		}

		if ($custom)
		{
			$settings->custom_get[] = (object) ['gettype' => 4, 'getcustom' => 'getDrivers'];
		}

		return ['area' => 'site', 'code' => 'truck', 'settings' => $settings];
	}
}
