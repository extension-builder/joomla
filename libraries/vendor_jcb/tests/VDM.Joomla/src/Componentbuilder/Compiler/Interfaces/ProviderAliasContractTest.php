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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Interfaces;


use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Tests\Support\RecordingServiceProviderContainer;
use VDM\Tests\Support\TestCase;


/**
 * Public interface-to-service identities registered by domain providers.
 *
 * @since  1.0.0
 */
#[CoversNothing]
final class ProviderAliasContractTest extends TestCase
{
	/**
	 * Verify a public interface points at the reviewed shared service key.
	 *
	 * Registering providers is enough to inspect this metadata; no compiler or
	 * dependency-heavy domain service is resolved.
	 *
	 * @param   class-string                            $interface      Public interface alias.
	 * @param   class-string<ServiceProviderInterface>  $providerClass  Registering provider.
	 * @param   string                                  $key            Shared service key.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('aliasContracts')]
	public function testProviderRegistersInterfaceIdentity(
		string $interface,
		string $providerClass,
		string $key
	): void
	{
		$container = new RecordingServiceProviderContainer();
		(new $providerClass())->register($container);
		$services = [];

		foreach ($container->servicesRegistered() as [$serviceKey, $factory, $protected])
		{
			$services[$serviceKey] = [$factory, $protected];
		}

		$this->assertTrue(interface_exists($interface));
		$this->assertContains([$interface, $key], $container->aliasesRegistered());
		$this->assertArrayHasKey($key, $services);
		$this->assertTrue($container->has($interface));
		$this->assertTrue($container->isShared($key));
		$this->assertSame($container->isShared($key), $container->isShared($interface));
		$this->assertSame($container->isProtected($key), $container->isProtected($interface));
	}

	/**
	 * Lock the complete public interface-alias inventory.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testManifestContainsEveryProviderInterfaceAlias(): void
	{
		$this->assertCount(49, iterator_to_array(self::aliasContracts()));
	}

	/**
	 * Provide every reviewed provider-facing interface alias.
	 *
	 * @return  iterable<string, array{class-string, class-string<ServiceProviderInterface>, string}>
	 * @since   1.0.0
	 */
	public static function aliasContracts(): iterable
	{
		yield 'Architecture.AdminView.AddModalToolBar' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminView\AddModalToolBarInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.AdminView.AddModalToolBar'
		];
		yield 'Architecture.AdminView.AddToolBar' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminView\AddToolBarInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.AdminView.AddToolBar'
		];
		yield 'Architecture.AdminViews.AddToolBar' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminViews\AddToolBarInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.AdminViews.AddToolBar'
		];
		yield 'Architecture.AdminViews.DisplayMethod' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminViews\DisplayMethodInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.AdminViews.DisplayMethod'
		];
		yield 'Architecture.AdminViews.ListHead' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminViews\ListHeadInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.AdminViews.ListHead'
		];
		yield 'Architecture.AdminViews.ViewBody' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminViews\ViewBodyInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.AdminViews.ViewBody'
		];
		yield 'Architecture.ComHelperClass.CreateUser' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\ComHelperClass\CreateUserInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureComponent::class,
			'Architecture.ComHelperClass.CreateUser'
		];
		yield 'Architecture.ComHelperClass.ExcelMethods' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\ComHelperClass\ExcelMethodsInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureComponent::class,
			'Architecture.ComHelperClass.ExcelMethods'
		];
		yield 'Architecture.Controller.AllowAdd' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Controller\AllowAddInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureController::class,
			'Architecture.Controller.AllowAdd'
		];
		yield 'Architecture.Controller.AllowEdit' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Controller\AllowEditInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureController::class,
			'Architecture.Controller.AllowEdit'
		];
		yield 'Architecture.Controller.AllowEditViews' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Controller\AllowEditViewsInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureController::class,
			'Architecture.Controller.AllowEditViews'
		];
		yield 'Architecture.CustomAdminView.AddToolBar' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\CustomAdmin\AddToolBarInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.CustomAdminView.AddToolBar'
		];
		yield 'Architecture.CustomAdminViews.AddToolBar' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\CustomAdmin\AddToolBarInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.CustomAdminViews.AddToolBar'
		];
		yield 'Architecture.CustomView.DisplayMethod' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\CustomView\DisplayMethodInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.CustomView.DisplayMethod'
		];
		yield 'Architecture.Dashboard.View' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Dashboard\ViewInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureDashboard::class,
			'Architecture.Dashboard.View'
		];
		yield 'Architecture.Menu.CustomView' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Menu\CustomViewInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.Menu.CustomView'
		];
		yield 'Architecture.Model.AllowEdit' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Model\AllowEditInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel::class,
			'Architecture.Model.AllowEdit'
		];
		yield 'Architecture.Model.CanDelete' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Model\CanDeleteInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel::class,
			'Architecture.Model.CanDelete'
		];
		yield 'Architecture.Model.CanEditState' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Model\CanEditStateInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel::class,
			'Architecture.Model.CanEditState'
		];
		yield 'Architecture.Model.CheckInNow' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Model\CheckInNowInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel::class,
			'Architecture.Model.CheckInNow'
		];
		yield 'Architecture.Module.Dispatcher' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Module\DispatcherInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModule::class,
			'Architecture.Module.Dispatcher'
		];
		yield 'Architecture.Module.Helper' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Module\HelperInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModule::class,
			'Architecture.Module.Helper'
		];
		yield 'Architecture.Module.Library' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Module\LibraryInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModule::class,
			'Architecture.Module.Library'
		];
		yield 'Architecture.Module.Provider' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Module\ProviderInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModule::class,
			'Architecture.Module.Provider'
		];
		yield 'Architecture.Module.Template' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Module\TemplateInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModule::class,
			'Architecture.Module.Template'
		];
		yield 'Architecture.Plugin.Extension' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Plugin\ExtensionInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitecturePlugin::class,
			'Architecture.Plugin.Extension'
		];
		yield 'Architecture.Plugin.Provider' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Plugin\ProviderInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitecturePlugin::class,
			'Architecture.Plugin.Provider'
		];
		yield 'Architecture.SiteView.AddToolBar' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\SiteView\AddToolBarInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView::class,
			'Architecture.SiteView.AddToolBar'
		];
		yield 'Component.Settings' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Component\SettingsInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Component::class,
			'Component.Settings'
		];
		yield 'Compiler.Creator.Fieldset' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Creator\Fieldsetinterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Creator::class,
			'Compiler.Creator.Fieldset'
		];
		yield 'Compiler.Creator.Field.Type' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Creator\Fieldtypeinterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Creator::class,
			'Compiler.Creator.Field.Type'
		];
		yield 'Event' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Event::class,
			'Event'
		];
		yield 'Field.Core.Field' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Field\CoreFieldInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Field::class,
			'Field.Core.Field'
		];
		yield 'Field.Core.Rule' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Field\CoreRuleInterface::class,
			\VDM\Joomla\Componentbuilder\Service\CoreRules::class,
			'Field.Core.Rule'
		];
		yield 'Field.Input.Button' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Field\InputButtonInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Field::class,
			'Field.Input.Button'
		];
		yield 'Extension.InstallScript' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\GetScriptInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Extension::class,
			'Extension.InstallScript'
		];
		yield 'Header' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\HeaderInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Header::class,
			'Header'
		];
		yield 'History' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\HistoryInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\History::class,
			'History'
		];
		yield 'Model.Customtabs' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\Model\CustomtabsInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Model::class,
			'Model.Customtabs'
		];
		yield 'Joomlamodule.Data' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\ModuleDataInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Joomlamodule::class,
			'Joomlamodule.Data'
		];
		yield 'Extension.MoveFieldsRules' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\MoveFieldsRulesInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Extension::class,
			'Extension.MoveFieldsRules'
		];
		yield 'Joomlaplugin.Data' => [
			\VDM\Joomla\Componentbuilder\Compiler\Interfaces\PluginDataInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Joomlaplugin::class,
			'Joomlaplugin.Data'
		];
		yield 'Architecture.Module.MainXML' => [
			\VDM\Joomla\Componentbuilder\Interfaces\Architecture\Module\MainXMLInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModule::class,
			'Architecture.Module.MainXML'
		];
		yield 'Architecture.Plugin.MainXML' => [
			\VDM\Joomla\Componentbuilder\Interfaces\Architecture\Plugin\MainXMLInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\ArchitecturePlugin::class,
			'Architecture.Plugin.MainXML'
		];
		yield 'Joomlamodule.Infusion' => [
			\VDM\Joomla\Componentbuilder\Interfaces\Module\InfusionInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Joomlamodule::class,
			'Joomlamodule.Infusion'
		];
		yield 'Joomlamodule.Structure' => [
			\VDM\Joomla\Componentbuilder\Interfaces\Module\StructureInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Joomlamodule::class,
			'Joomlamodule.Structure'
		];
		yield 'Joomlaplugin.Infusion' => [
			\VDM\Joomla\Componentbuilder\Interfaces\Plugin\InfusionInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Joomlaplugin::class,
			'Joomlaplugin.Infusion'
		];
		yield 'Joomlaplugin.Structure' => [
			\VDM\Joomla\Componentbuilder\Interfaces\Plugin\StructureInterface::class,
			\VDM\Joomla\Componentbuilder\Compiler\Service\Joomlaplugin::class,
			'Joomlaplugin.Structure'
		];
		yield 'Search' => [
			\VDM\Joomla\Componentbuilder\Search\Interfaces\SearchTypeInterface::class,
			\VDM\Joomla\Componentbuilder\Search\Service\Search::class,
			'Search'
		];
	}
}
