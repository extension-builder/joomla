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

namespace VDM\Joomla\Tests\Componentbuilder\Factory;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Factory as ExtendingFactory;
use VDM\Joomla\Componentbuilder\Data\Migrator\Factory as MigratorFactory;
use VDM\Joomla\Componentbuilder\Fieldtype\Factory as FieldtypeFactory;
use VDM\Joomla\Componentbuilder\Fieldtype\Readme\Item as FieldtypeReadme;
use VDM\Joomla\Componentbuilder\File\Factory as FileFactory;
use VDM\Joomla\Componentbuilder\File\Handler;
use VDM\Joomla\Componentbuilder\Import\Factory as ImportFactory;
use VDM\Joomla\Componentbuilder\Import\Assessor;
use VDM\Tests\Support\FactoryTestCase;


/**
 * Bounded-domain factory service catalogs and container isolation.
 *
 * @since  6.1.6
 */
#[CoversClass(MigratorFactory::class)]
#[CoversClass(FieldtypeFactory::class)]
#[CoversClass(FileFactory::class)]
#[CoversClass(ImportFactory::class)]
#[UsesClass(ExtendingFactory::class)]
final class DomainFactoryCatalogTest extends FactoryTestCase
{
	/**
	 * Protect each factory's reviewed service union and shared registrations.
	 *
	 * @param   class-string        $factory  Factory implementation.
	 * @param   array<int, string>  $keys     Domain services that must be present.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('factoryProvider')]
	public function testFactoryPublishesSharedDomainServices(string $factory, array $keys): void
	{
		$this->isolateFactory($factory);
		$container = $factory::getContainer();

		$this->assertSame($container, $factory::getContainer());

		foreach ($keys as $key)
		{
			$this->assertTrue($container->has($key), $factory . ' did not register ' . $key);
			$this->assertTrue($container->isShared($key), $factory . ' did not share ' . $key);
		}
	}

	/**
	 * Resolve safe leaf services and preserve alias identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFactoriesResolveRepresentativeLeafServicesByKeyAndClass(): void
	{
		foreach ([FileFactory::class, FieldtypeFactory::class, ImportFactory::class] as $factory)
		{
			$this->isolateFactory($factory);
		}

		$handler = FileFactory::_('File.Handler');
		$readme = FieldtypeFactory::_('Joomla.Fieldtype.Readme.Item');
		$assessor = ImportFactory::_('Import.Assessor');

		$this->assertInstanceOf(Handler::class, $handler);
		$this->assertSame($handler, FileFactory::_(Handler::class));
		$this->assertInstanceOf(FieldtypeReadme::class, $readme);
		$this->assertSame($readme, FieldtypeFactory::_(FieldtypeReadme::class));
		$this->assertInstanceOf(Assessor::class, $assessor);
		$this->assertSame($assessor, ImportFactory::_(Assessor::class));
	}

	/**
	 * Ensure static factory state does not bleed between bounded domains.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFactoriesOwnDistinctContainers(): void
	{
		$factories = [MigratorFactory::class, FieldtypeFactory::class, FileFactory::class, ImportFactory::class];

		foreach ($factories as $factory)
		{
			$this->isolateFactory($factory);
		}

		$containers = array_map(static fn(string $factory) => $factory::getContainer(), $factories);

		$this->assertCount(count($factories), array_unique(array_map('spl_object_id', $containers)));
	}

	/**
	 * Supply the exact representative service catalog for every factory.
	 *
	 * @return  array<string, array{class-string, array<int, string>}>
	 * @since   6.1.6
	 */
	public static function factoryProvider(): array
	{
		return [
			'data migrator' => [
				MigratorFactory::class,
				['Table', 'Data.Item', 'Data.Migrator.Guid', 'Component.Data.Migrator.Guid'],
			],
			'field type' => [
				FieldtypeFactory::class,
				['Joomla.Fieldtype.Config', 'Joomla.Fieldtype.Grep', 'Joomla.Fieldtype.Remote.Config', 'Joomla.Fieldtype.Remote.Get', 'Joomla.Fieldtype.Remote.Set', 'Joomla.Fieldtype.Readme.Item', 'Joomla.Fieldtype.Readme.Main'],
			],
			'file' => [
				FileFactory::class,
				['File.Type', 'File.Handler', 'File.Agent', 'File.Manager', 'File.Display', 'File.Image'],
			],
			'import' => [
				ImportFactory::class,
				['Import.Persistent', 'Import.Status', 'Import.Persistent.Message', 'Import.Persistent.Assessor', 'Import.Transient', 'Import.Assessor', 'Import.Item'],
			],
		];
	}
}
