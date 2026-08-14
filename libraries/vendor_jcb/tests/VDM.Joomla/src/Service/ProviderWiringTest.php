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

namespace VDM\Joomla\Tests\Service;


use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Service\Utilities as UtilitiesProvider;
use VDM\Joomla\Componentbuilder\Utilities\Http;
use VDM\Joomla\Componentbuilder\Utilities\Normalize;
use VDM\Joomla\Componentbuilder\Utilities\Response;
use VDM\Joomla\Componentbuilder\Utilities\Uri;
use VDM\Joomla\Data\Action\Delete as DataDelete;
use VDM\Joomla\Data\Action\Insert as DataInsert;
use VDM\Joomla\Data\Action\Load as DataLoad;
use VDM\Joomla\Data\Action\Update as DataUpdate;
use VDM\Joomla\Data\Item;
use VDM\Joomla\Data\Items;
use VDM\Joomla\Data\MultiSubform;
use VDM\Joomla\Data\Subform;
use VDM\Joomla\Interfaces\Database\DeleteInterface;
use VDM\Joomla\Interfaces\Database\InsertInterface;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Interfaces\Database\UpdateInterface;
use VDM\Joomla\Interfaces\TableInterface;
use VDM\Joomla\Model\Load as ModelLoad;
use VDM\Joomla\Model\Upsert as ModelUpsert;
use VDM\Joomla\Service\Data as DataProvider;
use VDM\Joomla\Service\Model as ModelProvider;
use VDM\Tests\Support\TestCase;


/**
 * Resolved dependency-graph and shared-identity provider contracts.
 *
 * @since  1.0.0
 */
#[CoversClass(DataProvider::class)]
#[CoversClass(ModelProvider::class)]
#[CoversClass(UtilitiesProvider::class)]
#[UsesClass(ModelLoad::class)]
#[UsesClass(ModelUpsert::class)]
#[UsesClass(DataLoad::class)]
#[UsesClass(DataInsert::class)]
#[UsesClass(DataUpdate::class)]
#[UsesClass(DataDelete::class)]
#[UsesClass(Item::class)]
#[UsesClass(Items::class)]
#[UsesClass(Subform::class)]
#[UsesClass(MultiSubform::class)]
#[UsesClass(Normalize::class)]
#[UsesClass(Uri::class)]
#[UsesClass(Http::class)]
#[UsesClass(Response::class)]
final class ProviderWiringTest extends TestCase
{
	/**
	 * Verify the model and data providers compose one shared object graph.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testDataProviderBuildsSharedActionAndAggregateGraph(): void
	{
		$container = new Container();
		$table = $this->createStub(TableInterface::class);
		$databaseLoad = $this->createStub(LoadInterface::class);
		$databaseInsert = $this->createStub(InsertInterface::class);
		$databaseUpdate = $this->createStub(UpdateInterface::class);
		$databaseDelete = $this->createStub(DeleteInterface::class);
		$container->share('Table', $table, true);
		$container->share('Load', $databaseLoad, true);
		$container->share('Insert', $databaseInsert, true);
		$container->share('Update', $databaseUpdate, true);
		$container->share('Delete', $databaseDelete, true);
		$container->registerServiceProvider(new ModelProvider());
		$container->registerServiceProvider(new DataProvider());

		$modelLoad = $container->get('Model.Load');
		$modelUpsert = $container->get('Model.Upsert');
		$load = $container->get('Data.Load');
		$insert = $container->get('Data.Insert');
		$update = $container->get('Data.Update');
		$delete = $container->get('Data.Delete');
		$item = $container->get('Data.Item');
		$items = $container->get('Data.Items');
		$subform = $container->get('Data.Subform');
		$multiSubform = $container->get('Data.MultiSubform');

		$this->assertInstanceOf(ModelLoad::class, $modelLoad);
		$this->assertInstanceOf(ModelUpsert::class, $modelUpsert);
		$this->assertSame($table, $this->property($modelLoad, 'table'));
		$this->assertSame($table, $this->property($modelUpsert, 'table'));
		$this->assertSame($modelLoad, $this->property($load, 'model'));
		$this->assertSame($databaseLoad, $this->property($load, 'load'));
		$this->assertSame($modelUpsert, $this->property($insert, 'model'));
		$this->assertSame($databaseInsert, $this->property($insert, 'database'));
		$this->assertSame($modelUpsert, $this->property($update, 'model'));
		$this->assertSame($databaseUpdate, $this->property($update, 'database'));
		$this->assertSame($databaseDelete, $this->property($delete, 'database'));

		foreach ([$item, $items] as $aggregate)
		{
			$this->assertSame($load, $this->property($aggregate, 'load'));
			$this->assertSame($insert, $this->property($aggregate, 'insert'));
			$this->assertSame($update, $this->property($aggregate, 'update'));
			$this->assertSame($delete, $this->property($aggregate, 'delete'));
			$this->assertSame($databaseLoad, $this->property($aggregate, 'database'));
		}

		$this->assertSame($items, $this->property($subform, 'items'));
		$this->assertSame($subform, $this->property($multiSubform, 'subform'));
		$this->assertSame($load, $container->get(DataLoad::class));
		$this->assertSame($item, $container->get(Item::class));
		$this->assertSame($items, $container->get(Items::class));
	}

	/**
	 * Verify stateless utility factories remain shared through keys and class aliases.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testUtilitiesProviderResolvesSharedClassAliases(): void
	{
		$container = (new Container())->registerServiceProvider(new UtilitiesProvider());
		$services = [
			'Utilities.Normalize' => Normalize::class,
			'Utilities.Uri' => Uri::class,
			'Utilities.Http' => Http::class,
			'Utilities.Response' => Response::class
		];

		foreach ($services as $key => $class)
		{
			$service = $container->get($key);

			$this->assertInstanceOf($class, $service);
			$this->assertSame($service, $container->get($key));
			$this->assertSame($service, $container->get($class));
		}
	}

	/**
	 * Read an injected dependency from a concrete graph node.
	 *
	 * @param   object  $subject   Graph node.
	 * @param   string  $property  Dependency property.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	private function property(object $subject, string $property): mixed
	{
		return (new ReflectionClass($subject))->getProperty($property)->getValue($subject);
	}
}
