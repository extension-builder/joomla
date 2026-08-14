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

namespace VDM\Joomla\Tests\Componentbuilder;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Factory;
use VDM\Joomla\Componentbuilder\Fieldtype\Factory as FieldtypeFactory;
use VDM\Joomla\Componentbuilder\JoomlaPower\Factory as JoomlaPowerFactory;
use VDM\Joomla\Componentbuilder\Package\Factory as PackageFactory;
use VDM\Joomla\Componentbuilder\Power\Factory as PowerFactory;
use VDM\Joomla\Componentbuilder\Repository\Factory as RepositoryFactory;
use VDM\Joomla\Componentbuilder\Snippet\Factory as SnippetFactory;
use VDM\Tests\Support\TestCase;


/**
 * Central entity, area, superpower, and factory routing catalog tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Factory::class)]
final class FactoryTest extends TestCase
{
	/**
	 * Resolve every reviewed entity and area bidirectionally to its factory.
	 *
	 * @param   string  $entity   Canonical entity name.
	 * @param   string  $area     Public area name.
	 * @param   string  $factory  Container factory class.
	 * @param   bool    $power    Whether the entity is a superpower.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('provideEntityCatalog')]
	public function testEntityCatalogIsBidirectionalAndFactoryStable(
		string $entity,
		string $area,
		string $factory,
		bool $power
	): void
	{
		$this->assertSame($area, Factory::getArea($entity));
		$this->assertSame($entity, Factory::getEntity($area));
		$this->assertSame($factory, Factory::getEntityFactory($entity));
		$this->assertSame($factory, Factory::getAreaFactory($area));
		$this->assertSame($power, isset(Factory::getSuperpowers()[$entity]));

		if ($power)
		{
			$this->assertSame($entity, Factory::getSuperpowers()[$entity]);
		}
	}

	/**
	 * Supply the complete reviewed entity-routing catalog.
	 *
	 * @return  iterable<string, array{string, string, class-string, bool}>
	 * @since   6.1.6
	 */
	public static function provideEntityCatalog(): iterable
	{
		$package = [
			'joomla_component' => ['Component', true],
			'component_admin_views' => ['ComponentAdminViews', false],
			'component_custom_admin_views' => ['ComponentCustomAdminViews', false],
			'component_site_views' => ['ComponentSiteViews', false],
			'component_router' => ['ComponentRouter', false],
			'component_config' => ['ComponentConfig', false],
			'component_placeholders' => ['ComponentPlaceholders', false],
			'component_updates' => ['ComponentUpdates', false],
			'component_files_folders' => ['ComponentFilesFolders', false],
			'component_custom_admin_menus' => ['ComponentCustomAdminMenus', false],
			'component_dashboard' => ['ComponentDashboard', false],
			'component_modules' => ['ComponentModules', false],
			'component_plugins' => ['ComponentPlugins', false],
			'joomla_module' => ['JoomlaModule', true],
			'joomla_module_updates' => ['JoomlaModuleUpdates', false],
			'joomla_module_files_folders_urls' => ['JoomlaModuleFilesFoldersUrls', false],
			'joomla_plugin' => ['JoomlaPlugin', true],
			'joomla_plugin_group' => ['JoomlaPluginGroup', false],
			'joomla_plugin_updates' => ['JoomlaPluginUpdates', false],
			'joomla_plugin_files_folders_urls' => ['JoomlaPluginFilesFoldersUrls', false],
			'admin_view' => ['AdminView', true],
			'admin_fields' => ['AdminFields', false],
			'admin_fields_relations' => ['AdminFieldsRelations', false],
			'admin_fields_conditions' => ['AdminFieldsConditions', false],
			'admin_custom_tabs' => ['AdminCustomTabs', false],
			'custom_admin_view' => ['CustomAdminView', true],
			'site_view' => ['SiteView', true],
			'template' => ['Template', true],
			'layout' => ['Layout', true],
			'dynamic_get' => ['DynamicGet', true],
			'custom_code' => ['CustomCode', true],
			'field' => ['Field', true],
			'validation_rule' => ['ValidationRule', true],
			'library' => ['Library', true],
			'library_config' => ['LibraryConfig', false],
			'library_files_folders_urls' => ['LibraryFilesFoldersUrls', false],
			'class_method' => ['ClassMethod', true],
			'class_property' => ['ClassProperty', true],
			'class_extends' => ['ClassExtends', true],
			'placeholder' => ['Placeholder', true]
		];

		foreach ($package as $entity => [$area, $power])
		{
			yield $entity => [$entity, $area, PackageFactory::class, $power];
		}

		yield 'fieldtype' => ['fieldtype', 'Joomla.Fieldtype', FieldtypeFactory::class, true];
		yield 'power' => ['power', 'Power', PowerFactory::class, true];
		yield 'joomla_power' => ['joomla_power', 'Joomla.Power', JoomlaPowerFactory::class, true];
		yield 'repository' => ['repository', 'Repository', RepositoryFactory::class, true];
		yield 'snippet' => ['snippet', 'Snippet', SnippetFactory::class, true];
	}

	/**
	 * Reject unknown entity and area names without initializing another factory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUnknownTargetsReturnNull(): void
	{
		$this->assertNull(Factory::getArea('unknown_entity'));
		$this->assertNull(Factory::getEntity('UnknownArea'));
		$this->assertNull(Factory::getEntityFactory('unknown_entity'));
		$this->assertNull(Factory::getAreaFactory('UnknownArea'));
		$this->assertNull(Factory::_('UnknownArea', 'Any.Service'));
	}
}
