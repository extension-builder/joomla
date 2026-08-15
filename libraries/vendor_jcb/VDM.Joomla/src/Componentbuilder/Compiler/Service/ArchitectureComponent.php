<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    4th September, 2022
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Service;


use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\ComHelperClass\CreateUserInterface;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\ComHelperClass\CreateUser as J6CreateUser;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaFive\ComHelperClass\CreateUser as J5CreateUser;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaFour\ComHelperClass\CreateUser as J4CreateUser;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaThree\ComHelperClass\CreateUser as J3CreateUser;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\ComHelperClass\ExcelMethodsInterface;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\ComHelperClass\ExcelMethods as J6ExcelMethods;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaFive\ComHelperClass\ExcelMethods as J5ExcelMethods;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaFour\ComHelperClass\ExcelMethods as J4ExcelMethods;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaThree\ComHelperClass\ExcelMethods as J3ExcelMethods;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\ImageType;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\LicenseLock;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\Whmcs;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\ComHelperClass\CryptKey;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\ComHelperClass\UikitMethods;


/**
 * Architecture Component Helper Class Service Provider
 *
 * @since 5.0.2
 */
class ArchitectureComponent implements ServiceProviderInterface
{
	/**
	 * Current Joomla Version Being Build
	 *
	 * @var     int
	 * @since 5.0.2
	 **/
	protected $targetVersion;

	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 * @since 5.0.2
	 */
	public function register(Container $container)
	{
		$container->alias(CreateUserInterface::class, 'Architecture.ComHelperClass.CreateUser')
			->share('Architecture.ComHelperClass.CreateUser', [$this, 'getCreateUser'], true);

		$container->alias(J6CreateUser::class, 'Architecture.ComHelperClass.J6.CreateUser')
			->share('Architecture.ComHelperClass.J6.CreateUser', [$this, 'getJ6CreateUser'], true);

		$container->alias(J5CreateUser::class, 'Architecture.ComHelperClass.J5.CreateUser')
			->share('Architecture.ComHelperClass.J5.CreateUser', [$this, 'getJ5CreateUser'], true);

		$container->alias(J4CreateUser::class, 'Architecture.ComHelperClass.J4.CreateUser')
			->share('Architecture.ComHelperClass.J4.CreateUser', [$this, 'getJ4CreateUser'], true);

		$container->alias(J3CreateUser::class, 'Architecture.ComHelperClass.J3.CreateUser')
			->share('Architecture.ComHelperClass.J3.CreateUser', [$this, 'getJ3CreateUser'], true);

		$container->alias(ImageType::class, 'Architecture.Component.ImageType')
			->share('Architecture.Component.ImageType', [$this, 'getImageType'], true);

		$container->alias(LicenseLock::class, 'Architecture.Component.LicenseLock')
			->share('Architecture.Component.LicenseLock', [$this, 'getLicenseLock'], true);

		$container->alias(Whmcs::class, 'Architecture.Component.Whmcs')
			->share('Architecture.Component.Whmcs', [$this, 'getWhmcs'], true);

		$container->alias(CryptKey::class, 'Architecture.ComHelperClass.CryptKey')
			->share('Architecture.ComHelperClass.CryptKey', [$this, 'getCryptKey'], true);

		$container->alias(UikitMethods::class, 'Architecture.ComHelperClass.UikitMethods')
			->share('Architecture.ComHelperClass.UikitMethods', [$this, 'getUikitMethods'], true);

		$container->alias(ExcelMethodsInterface::class, 'Architecture.ComHelperClass.ExcelMethods')
			->share('Architecture.ComHelperClass.ExcelMethods', [$this, 'getExcelMethods'], true);

		$container->alias(J6ExcelMethods::class, 'Architecture.ComHelperClass.J6.ExcelMethods')
			->share('Architecture.ComHelperClass.J6.ExcelMethods', [$this, 'getJ6ExcelMethods'], true);

		$container->alias(J5ExcelMethods::class, 'Architecture.ComHelperClass.J5.ExcelMethods')
			->share('Architecture.ComHelperClass.J5.ExcelMethods', [$this, 'getJ5ExcelMethods'], true);

		$container->alias(J4ExcelMethods::class, 'Architecture.ComHelperClass.J4.ExcelMethods')
			->share('Architecture.ComHelperClass.J4.ExcelMethods', [$this, 'getJ4ExcelMethods'], true);

		$container->alias(J3ExcelMethods::class, 'Architecture.ComHelperClass.J3.ExcelMethods')
			->share('Architecture.ComHelperClass.J3.ExcelMethods', [$this, 'getJ3ExcelMethods'], true);
	}

	/**
	 * Get The CreateUserInterface Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  CreateUserInterface
	 * @since   5.0.2
	 */
	public function getCreateUser(Container $container): CreateUserInterface
	{
		if (empty($this->targetVersion))
		{
			$this->targetVersion = $container->get('Config')->joomla_version;
		}

		return $container->get('Architecture.ComHelperClass.J' . $this->targetVersion . '.CreateUser');
	}

	/**
	 * Get The CreateUser Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  J6CreateUser
	 * @since   5.1.2
	 */
	public function getJ6CreateUser(Container $container): J6CreateUser
	{
		return new J6CreateUser();
	}

	/**
	 * Get The CreateUser Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  J5CreateUser
	 * @since   5.0.2
	 */
	public function getJ5CreateUser(Container $container): J5CreateUser
	{
		return new J5CreateUser();
	}

	/**
	 * Get The CreateUser Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  J4CreateUser
	 * @since   5.0.2
	 */
	public function getJ4CreateUser(Container $container): J4CreateUser
	{
		return new J4CreateUser();
	}

	/**
	 * Get The CreateUser Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  J3CreateUser
	 * @since   5.0.2
	 */
	public function getJ3CreateUser(Container $container): J3CreateUser
	{
		return new J3CreateUser();
	}

	/**
	 * Get The ImageType Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  ImageType
	 * @since   5.1.4
	 */
	public function getImageType(Container $container): ImageType
	{
		return new ImageType(
			$container->get('Utilities.Paths'),
			$container->get('Utilities.Image')
		);
	}

	/**
	 * Get The LicenseLock Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  LicenseLock
	 * @since   6.1.7
	 */
	public function getLicenseLock(Container $container): LicenseLock
	{
		return new LicenseLock(
			$container->get('Config'),
			$container->get('Component'),
			$container->get('Compiler.Builder.Content.One'),
			$container->get('Compiler.Builder.Content.Multi')
		);
	}

	/**
	 * Get The Whmcs Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Whmcs
	 * @since   6.1.7
	 */
	public function getWhmcs(Container $container): Whmcs
	{
		return new Whmcs(
			$container->get('Component')
		);
	}

	/**
	 * Get The CryptKey Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  CryptKey
	 * @since   6.1.7
	 */
	public function getCryptKey(Container $container): CryptKey
	{
		return new CryptKey(
			$container->get('Config'),
			$container->get('Component'),
			$container->get('Compiler.Builder.Content.One'),
			$container->get('Compiler.Builder.Content.Multi'),
			$container->get('Compiler.Builder.Model.Basic.Field'),
			$container->get('Compiler.Builder.Model.Medium.Field'),
			$container->get('Compiler.Builder.Model.Whmcs.Field'),
			$container->get('Utilities.Structure'),
			$container->get('Architecture.Component.Whmcs')
		);
	}

	/**
	 * Get The UikitMethods Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  UikitMethods
	 * @since   6.1.7
	 */
	public function getUikitMethods(Container $container): UikitMethods
	{
		return new UikitMethods(
			$container->get('Config')
		);
	}

	/**
	 * Get The ExcelMethods Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  ExcelMethodsInterface
	 * @since   6.1.7
	 */
	public function getExcelMethods(Container $container): ExcelMethodsInterface
	{
		if (empty($this->targetVersion))
		{
			$this->targetVersion = $container->get('Config')->joomla_version;
		}

		return $container->get('Architecture.ComHelperClass.J' . $this->targetVersion . '.ExcelMethods');
	}

	/**
	 * Get The ExcelMethods Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  J6ExcelMethods
	 * @since   6.1.7
	 */
	public function getJ6ExcelMethods(Container $container): J6ExcelMethods
	{
		return new J6ExcelMethods(
			$container->get('Config'),
			$container->get('Compiler.Builder.Content.One')
		);
	}

	/**
	 * Get The ExcelMethods Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  J5ExcelMethods
	 * @since   6.1.7
	 */
	public function getJ5ExcelMethods(Container $container): J5ExcelMethods
	{
		return new J5ExcelMethods(
			$container->get('Config'),
			$container->get('Compiler.Builder.Content.One')
		);
	}

	/**
	 * Get The ExcelMethods Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  J4ExcelMethods
	 * @since   6.1.7
	 */
	public function getJ4ExcelMethods(Container $container): J4ExcelMethods
	{
		return new J4ExcelMethods(
			$container->get('Config'),
			$container->get('Compiler.Builder.Content.One')
		);
	}

	/**
	 * Get The ExcelMethods Class.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  J3ExcelMethods
	 * @since   6.1.7
	 */
	public function getJ3ExcelMethods(Container $container): J3ExcelMethods
	{
		return new J3ExcelMethods(
			$container->get('Config'),
			$container->get('Compiler.Builder.Content.One')
		);
	}
}
