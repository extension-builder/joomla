<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    7th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\User\User;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use VDM\Tests\Support\TestCase;


/**
 * Exercise stored-owner authorization in the actual shipped administrator controllers.
 *
 * The imported administrator classes are outside vendor-library coverage telemetry.
 * Their unchanged allowEdit methods run with injected user, application and model
 * boundaries; no source fragment is extracted or reimplemented by the test.
 *
 * @since  6.2.0
 */
#[CoversNothing]
final class ShippedControllerOwnershipTest extends TestCase
{
	/**
	 * A posted owner never substitutes for the stored owner or native ACL grants.
	 *
	 * @param   string  $view  The imported controller's singular view name.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('shippedFormControllers')]
	public function testOwnershipComesFromTheStoredRecord(string $view): void
	{
		$cases = [
			'stored owner wins over a different posted owner' => [42, 99, true, true, false, false, true, true, true],
			'forged posted owner cannot acquire another record' => [99, 42, true, true, false, false, true, true, false],
			'missing stored record cannot be owned' => [null, 42, true, true, false, false, true, true, false],
			'component ownership permission is required' => [42, 42, true, true, false, false, true, false, false],
			'record ownership permission is required' => [42, 42, true, true, false, false, false, true, false],
			'record access is required' => [42, 42, false, true, false, false, true, true, false],
			'component access is required' => [42, 42, true, false, false, false, true, true, false],
			'general record and component edit permit another owner' => [99, 42, true, true, true, true, false, false, true],
			'record edit alone cannot bypass component edit' => [99, 42, true, true, true, false, false, false, false],
		];

		$hasAccess = $view !== 'Joomla_plugin_group';
		$usesCoreEdit = in_array($view, ['Custom_admin_view', 'Site_view', 'Layout', 'Template', 'Snippet', 'Joomla_plugin_group'], true);

		if (!$hasAccess)
		{
			unset($cases['record access is required'], $cases['component access is required']);
		}

		foreach ($cases as $label => $case)
		{
			[$owner, $postedOwner, $accessRecord, $accessComponent, $editRecord, $editComponent, $ownRecord, $ownComponent, $allowed] = $case;
			$action = strtolower($view);
			$asset = 'com_componentbuilder.' . $action . '.17';
			$editAction = $usesCoreEdit ? 'core' : $action;
			$permissions = [
				$action . '.access|' . $asset => $accessRecord,
				$action . '.access|com_componentbuilder' => $accessComponent,
				$editAction . '.edit|' . $asset => $editRecord,
				$editAction . '.edit|com_componentbuilder' => $editComponent,
				$editAction . '.edit.own|' . $asset => $ownRecord,
				$editAction . '.edit.own|com_componentbuilder' => $ownComponent,
			];
			$user = $this->getMockBuilder(User::class)->disableOriginalConstructor()
				->onlyMethods(['authorise'])->getMock();
			$user->id = 42;
			$user->expects($this->atLeastOnce())->method('authorise')->willReturnCallback(
				function (string $permission, string $scope) use ($permissions): bool
				{
					$key = $permission . '|' . $scope;
					$this->assertArrayHasKey($key, $permissions, 'The shipped controller checks the actual record and component.');

					return $permissions[$key];
				}
			);
			$app = $this->createMock(CMSWebApplicationInterface::class);
			$app->expects($this->atLeastOnce())->method('getIdentity')->willReturn($user);
			$app->method('getName')->willReturn('administrator');
			$loadsOwner = (!$hasAccess || ($accessRecord && $accessComponent)) && !$editRecord && $ownRecord;
			$model = $this->getMockBuilder(AdminModel::class)->disableOriginalConstructor()
				->onlyMethods(['getItem', 'getForm', 'setState'])->getMock();
			$model->expects($loadsOwner ? $this->once() : $this->never())->method('getItem')
				->with(17)->willReturn($owner === null ? false : (object) ['id' => 17, 'created_by' => $owner]);
			$factory = $this->createMock(MVCFactoryInterface::class);
			$factory->expects($loadsOwner ? $this->once() : $this->never())->method('createModel')
				->with($action, 'administrator', ['ignore_request' => true])->willReturn($model);
			$controller = $this->controller($view, $app, $factory);

			$this->assertSame(
				$allowed,
				(new ReflectionMethod($controller, 'allowEdit'))->invoke(
					$controller, ['record_id' => '17', 'created_by' => $postedOwner], 'record_id'
				),
				$view . ': ' . $label
			);
		}
	}

	/**
	 * The 51 form controllers whose ownership checks changed in the stable import.
	 *
	 * Explicit names prevent a broken declaration from removing itself from discovery.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.2.0
	 */
	public static function shippedFormControllers(): array
	{
		$views = [
			'Admin_custom_tabs', 'Admin_fields', 'Admin_fields_conditions', 'Admin_fields_relations',
			'Admin_view', 'Class_extends', 'Class_method', 'Class_property', 'Component_admin_views',
			'Component_config', 'Component_custom_admin_menus', 'Component_custom_admin_views',
			'Component_dashboard', 'Component_files_folders', 'Component_modules', 'Component_mysql_tweaks',
			'Component_placeholders', 'Component_plugins', 'Component_router', 'Component_site_views',
			'Component_updates', 'Custom_admin_view', 'Custom_code', 'Dynamic_get', 'Field', 'Fieldtype',
			'Help_document', 'Joomla_component', 'Joomla_module', 'Joomla_module_files_folders_urls',
			'Joomla_module_updates', 'Joomla_plugin', 'Joomla_plugin_files_folders_urls', 'Joomla_plugin_group',
			'Joomla_plugin_updates', 'Joomla_power', 'Language', 'Language_translation', 'Layout', 'Library',
			'Library_config', 'Library_files_folders_urls', 'Placeholder', 'Power', 'Repository', 'Server',
			'Site_view', 'Snippet', 'Snippet_type', 'Template', 'Validation_rule',
		];

		return array_combine($views, array_map(static fn (string $view): array => [$view], $views));
	}

	/**
	 * Load a shipped class and inject only the boundaries its permission method uses.
	 *
	 * @param   string                      $view     The singular view name.
	 * @param   CMSWebApplicationInterface  $app      The application boundary.
	 * @param   MVCFactoryInterface         $factory  The model factory boundary.
	 *
	 * @return  FormController
	 * @since   6.2.0
	 */
	private function controller(string $view, CMSWebApplicationInterface $app, MVCFactoryInterface $factory): FormController
	{
		require_once dirname(__DIR__, 4) . '/admin/src/Controller/' . $view . 'Controller.php';
		$class = 'VDM\\Component\\Componentbuilder\\Administrator\\Controller\\' . $view . 'Controller';
		$controller = (new ReflectionClass($class))->newInstanceWithoutConstructor();

		foreach (['app' => $app, 'factory' => $factory, 'context' => strtolower($view), 'option' => 'com_componentbuilder'] as $name => $value)
		{
			(new ReflectionProperty($controller, $name))->setValue($controller, $value);
		}

		return $controller;
	}
}
