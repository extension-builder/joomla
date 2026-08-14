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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Service;


use Joomla\CMS\Version;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureComponent;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureController;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureDashboard;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModel;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureModule;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitecturePlugin;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView;
use VDM\Joomla\Componentbuilder\Compiler\Service\Component;
use VDM\Joomla\Componentbuilder\Compiler\Service\Event;
use VDM\Joomla\Componentbuilder\Compiler\Service\Extension;
use VDM\Joomla\Componentbuilder\Compiler\Service\Field;
use VDM\Joomla\Componentbuilder\Compiler\Service\Header;
use VDM\Joomla\Componentbuilder\Compiler\Service\History;
use VDM\Joomla\Componentbuilder\Compiler\Service\Joomlamodule;
use VDM\Joomla\Componentbuilder\Compiler\Service\Joomlaplugin;
use VDM\Joomla\Componentbuilder\Compiler\Service\Model;
use VDM\Joomla\Componentbuilder\Service\CoreRules;
use VDM\Tests\Support\TestCase;


/**
 * Contracts for the distinct target-version and current-host selectors.
 *
 * @since  1.0.0
 */
#[CoversClass(ArchitectureComponent::class)]
#[CoversClass(ArchitectureController::class)]
#[CoversClass(ArchitectureDashboard::class)]
#[CoversClass(ArchitectureModel::class)]
#[CoversClass(ArchitectureModule::class)]
#[CoversClass(ArchitecturePlugin::class)]
#[CoversClass(ArchitectureView::class)]
#[CoversClass(Component::class)]
#[CoversClass(Event::class)]
#[CoversClass(Extension::class)]
#[CoversClass(Field::class)]
#[CoversClass(Header::class)]
#[CoversClass(History::class)]
#[CoversClass(Joomlamodule::class)]
#[CoversClass(Joomlaplugin::class)]
#[CoversClass(Model::class)]
#[CoversClass(CoreRules::class)]
final class VersionSelectionTest extends TestCase
{
	/**
	 * Verify target selectors route every supported compiler target and cache it per provider.
	 *
	 * @param   class-string<ServiceProviderInterface>  $providerClass  Provider under test.
	 * @param   string                                  $method         Logical selector method.
	 * @param   string                                  $keyTemplate    Versioned service-key template.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('targetSelectors')]
	public function testTargetSelectorRoutesEverySupportedVersion(
		string $providerClass,
		string $method,
		string $keyTemplate
	): void
	{
		foreach ([3, 4, 5, 6] as $version)
		{
			$provider = new $providerClass();
			$container = new Container();
			$config = (object) ['joomla_version' => $version];
			$selected = $this->selectorStub($providerClass, $method);
			$container->share('Config', $config, true);
			$container->share(sprintf($keyTemplate, $version), $selected, true);

			$this->assertSame($selected, $provider->{$method}($container));

			$nextVersion = $version === 6 ? 3 : $version + 1;
			$next = $this->selectorStub($providerClass, $method);
			$config->joomla_version = $nextVersion;
			$container->share(sprintf($keyTemplate, $nextVersion), $next, true);

			$this->assertSame(
				$selected,
				$provider->{$method}($container),
				'Target selection must remain stable for one shared provider instance.'
			);
		}
	}

	/**
	 * Verify host selectors use Joomla's installed major and expose every supported host key.
	 *
	 * Versions other than the installed test runtime are selected by setting the provider's
	 * cached host-major field; Joomla's MAJOR_VERSION constant cannot be changed in-process.
	 *
	 * @param   class-string<ServiceProviderInterface>  $providerClass  Provider under test.
	 * @param   string                                  $method         Logical selector method.
	 * @param   string                                  $keyTemplate    Versioned service-key template.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('currentHostSelectors')]
	public function testCurrentHostSelectorRoutesInstalledAndSupportedVersions(
		string $providerClass,
		string $method,
		string $keyTemplate
	): void
	{
		$installedProvider = new $providerClass();
		$installedContainer = new Container();
		$installed = $this->selectorStub($providerClass, $method);
		$installedContainer->share(sprintf($keyTemplate, Version::MAJOR_VERSION), $installed, true);

		$this->assertSame($installed, $installedProvider->{$method}($installedContainer));

		foreach ([3, 4, 5, 6] as $version)
		{
			$provider = new $providerClass();
			$container = new Container();
			$selected = $this->selectorStub($providerClass, $method);
			$property = (new ReflectionClass($provider))->getProperty('currentVersion');
			$property->setValue($provider, $version);
			$container->share(sprintf($keyTemplate, $version), $selected, true);

			$this->assertSame($selected, $provider->{$method}($container));
		}
	}

	/**
	 * Provide every compiler target-version selector and its concrete key family.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, string, string}>
	 * @since   1.0.0
	 */
	public static function targetSelectors(): iterable
	{
		yield 'component helper create user' => [ArchitectureComponent::class, 'getCreateUser', 'Architecture.ComHelperClass.J%d.CreateUser'];
		yield 'controller allow add' => [ArchitectureController::class, 'getAllowAdd', 'Architecture.Controller.J%d.AllowAdd'];
		yield 'controller allow edit' => [ArchitectureController::class, 'getAllowEdit', 'Architecture.Controller.J%d.AllowEdit'];
		yield 'controller allow edit views' => [ArchitectureController::class, 'getAllowEditViews', 'Architecture.Controller.J%d.AllowEditViews'];
		yield 'dashboard view' => [ArchitectureDashboard::class, 'getViewInterface', 'Architecture.Dashboard.J%d.View'];
		yield 'model allow edit' => [ArchitectureModel::class, 'getModelAllowEdit', 'Architecture.Model.J%d.AllowEdit'];
		yield 'model can delete' => [ArchitectureModel::class, 'getModelCanDelete', 'Architecture.Model.J%d.CanDelete'];
		yield 'model can edit state' => [ArchitectureModel::class, 'getModelCanEditState', 'Architecture.Model.J%d.CanEditState'];
		yield 'model check in now' => [ArchitectureModel::class, 'getCheckInNow', 'Architecture.Model.J%d.CheckInNow'];
		yield 'module library' => [ArchitectureModule::class, 'getLibrary', 'Architecture.Module.J%d.Library'];
		yield 'module template' => [ArchitectureModule::class, 'getTemplate', 'Architecture.Module.J%d.Template'];
		yield 'module helper' => [ArchitectureModule::class, 'getHelper', 'Architecture.Module.J%d.Helper'];
		yield 'module dispatcher' => [ArchitectureModule::class, 'getDispatcher', 'Architecture.Module.J%d.Dispatcher'];
		yield 'module provider' => [ArchitectureModule::class, 'getProvider', 'Architecture.Module.J%d.Provider'];
		yield 'module XML' => [ArchitectureModule::class, 'getMainXML', 'Architecture.Module.J%d.MainXML'];
		yield 'plugin extension' => [ArchitecturePlugin::class, 'getExtension', 'Architecture.Plugin.J%d.Extension'];
		yield 'plugin provider' => [ArchitecturePlugin::class, 'getProvider', 'Architecture.Plugin.J%d.Provider'];
		yield 'plugin XML' => [ArchitecturePlugin::class, 'getMainXML', 'Architecture.Plugin.J%d.MainXML'];
		yield 'admin view toolbar' => [ArchitectureView::class, 'getAdminViewAddToolBar', 'Architecture.AdminView.J%d.AddToolBar'];
		yield 'admin view modal toolbar' => [ArchitectureView::class, 'getAdminViewAddModalToolBar', 'Architecture.AdminView.J%d.AddModalToolBar'];
		yield 'admin views toolbar' => [ArchitectureView::class, 'getAdminViewsAddToolBar', 'Architecture.AdminViews.J%d.AddToolBar'];
		yield 'site view toolbar' => [ArchitectureView::class, 'getSiteViewAddToolBar', 'Architecture.SiteView.J%d.AddToolBar'];
		yield 'custom admin view toolbar' => [ArchitectureView::class, 'getCustomAdminViewAddToolBar', 'Architecture.CustomAdminView.J%d.AddToolBar'];
		yield 'custom admin views toolbar' => [ArchitectureView::class, 'getCustomAdminViewsAddToolBar', 'Architecture.CustomAdminViews.J%d.AddToolBar'];
		yield 'component settings' => [Component::class, 'getSettings', 'Component.J%d.Settings'];
		yield 'extension install script' => [Extension::class, 'getExtensionInstallScript', 'J%d.Extension.InstallScript'];
		yield 'extension moved fields and rules' => [Extension::class, 'getMoveFieldsRules', 'J%d.Extension.MoveFieldsRules'];
		yield 'field input button' => [Field::class, 'getInputButton', 'J%d.Field.Input.Button'];
		yield 'header' => [Header::class, 'getHeader', 'J%d.Header'];
		yield 'module data' => [Joomlamodule::class, 'getData', 'Joomlamodule.J%d.Data'];
		yield 'module structure' => [Joomlamodule::class, 'getStructure', 'Joomlamodule.J%d.Structure'];
		yield 'module infusion' => [Joomlamodule::class, 'getInfusion', 'Joomlamodule.J%d.Infusion'];
		yield 'plugin data' => [Joomlaplugin::class, 'getPluginData', 'Joomlaplugin.J%d.Data'];
		yield 'plugin structure' => [Joomlaplugin::class, 'getStructure', 'Joomlaplugin.J%d.Structure'];
		yield 'plugin infusion' => [Joomlaplugin::class, 'getInfusion', 'Joomlaplugin.J%d.Infusion'];
		yield 'custom tabs' => [Model::class, 'getCustomtabs', 'Model.J%d.Customtabs'];
	}

	/**
	 * Provide every installed-host selector and its concrete key family.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, string, string}>
	 * @since   1.0.0
	 */
	public static function currentHostSelectors(): iterable
	{
		yield 'core field' => [Field::class, 'getCoreField', 'J%d.Field.Core.Field'];
		yield 'event' => [Event::class, 'getEvent', 'J%d.Event'];
		yield 'history' => [History::class, 'getHistory', 'J%d.History'];
		yield 'core rule' => [CoreRules::class, 'getCoreRule', 'J%d.Field.Core.Rule'];
	}

	/**
	 * Create a typed sentinel for a selector's interface return contract.
	 *
	 * @param   class-string<ServiceProviderInterface>  $providerClass  Provider under test.
	 * @param   string                                  $method         Selector method.
	 *
	 * @return  object
	 * @since   1.0.0
	 */
	private function selectorStub(string $providerClass, string $method): object
	{
		$returnType = (new ReflectionMethod($providerClass, $method))->getReturnType();
		$this->assertInstanceOf(ReflectionNamedType::class, $returnType);

		return $this->createStub($returnType->getName());
	}
}
