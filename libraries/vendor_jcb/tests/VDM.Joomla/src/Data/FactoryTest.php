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

namespace VDM\Joomla\Tests\Data;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Data\Factory;
use VDM\Joomla\Service\Data as DataProvider;
use VDM\Joomla\Service\Database as DatabaseProvider;
use VDM\Joomla\Service\Model as ModelProvider;
use VDM\Joomla\Service\Table as TableProvider;
use VDM\Tests\Support\FactoryTestCase;


/**
 * Data factory provider composition and singleton lifecycle tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Factory::class)]
#[UsesClass(DataProvider::class)]
#[UsesClass(DatabaseProvider::class)]
#[UsesClass(ModelProvider::class)]
#[UsesClass(TableProvider::class)]
final class FactoryTest extends FactoryTestCase
{
	/**
	 * Reset the process-static data container around each test.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->isolateFactory(Factory::class);
	}

	/**
	 * Compose every table, database, model, and data service into one shared catalog.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testContainerComposesCompleteDataLayerCatalog(): void
	{
		$container = Factory::getContainer();
		$keys = [
			'Table',
			'Table.Schema',
			'Table.Validator',
			'Joomla.Database',
			'Load',
			'Insert',
			'Update',
			'Delete',
			'Model.Load',
			'Model.Upsert',
			'Data.Load',
			'Data.Insert',
			'Data.Update',
			'Data.Delete',
			'Data.Item',
			'Data.Items',
			'Data.Subform',
			'Data.UsersSubform',
			'Data.MultiSubform',
			'Data.Migrator.Guid'
		];

		foreach ($keys as $key)
		{
			$this->assertTrue($container->has($key), 'Factory must register ' . $key);
		}
	}

	/**
	 * Reuse exactly one container identity for the process lifecycle.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testContainerIdentityIsStableUntilExplicitReset(): void
	{
		$this->assertSame(Factory::getContainer(), Factory::getContainer());
	}
}
