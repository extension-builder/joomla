<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    22nd August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Service;


use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\PowersExtruderInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Assembler;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Extruder;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Harvester;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Reader\ClassFile;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Existing;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Namespacer;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Resolver\Placeholders;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Writer\Power as PowerWriter;
use VDM\Joomla\Componentbuilder\Power\Parser;


/**
 * Extrusion Powers Service Provider
 *
 * The powers branch of the extrusion engine: everything that harvests PHP
 * library classes into JCB powers. The shared run state, scanner and identity
 * resolver are reused from the sibling providers, so both branches of a run
 * see one configuration, one report, and one message bus.
 *
 * @since 6.1.7
 */
class Powers implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function register(Container $container)
	{
		$container->alias(Parser::class, 'Extrusion.Powers.Parser')
			->share('Extrusion.Powers.Parser', [$this, 'getParser'], true);

		$container->alias(ClassFile::class, 'Extrusion.Powers.Reader.ClassFile')
			->share('Extrusion.Powers.Reader.ClassFile', [$this, 'getClassFile'], true);

		$container->alias(Placeholders::class, 'Extrusion.Powers.Resolver.Placeholders')
			->share('Extrusion.Powers.Resolver.Placeholders', [$this, 'getPlaceholders'], true);

		$container->alias(Namespacer::class, 'Extrusion.Powers.Resolver.Namespacer')
			->share('Extrusion.Powers.Resolver.Namespacer', [$this, 'getNamespacer'], true);

		$container->alias(Existing::class, 'Extrusion.Powers.Resolver.Existing')
			->share('Extrusion.Powers.Resolver.Existing', [$this, 'getExisting'], true);

		$container->alias(Harvester::class, 'Extrusion.Powers.Harvester')
			->share('Extrusion.Powers.Harvester', [$this, 'getHarvester'], true);

		$container->alias(Assembler::class, 'Extrusion.Powers.Assembler')
			->share('Extrusion.Powers.Assembler', [$this, 'getAssembler'], true);

		$container->alias(PowerWriter::class, 'Extrusion.Powers.Writer.Power')
			->share('Extrusion.Powers.Writer.Power', [$this, 'getPowerWriter'], true);

		$container->alias(Extruder::class, 'Extrusion.Powers.Extruder')
			->alias(PowersExtruderInterface::class, 'Extrusion.Powers.Extruder')
			->share('Extrusion.Powers.Extruder', [$this, 'getExtruder'], true);
	}

	/**
	 * Get the Power Parser.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Parser
	 * @since   6.1.7
	 */
	public function getParser(Container $container): Parser
	{
		return new Parser();
	}

	/**
	 * Get the Class File Reader.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  ClassFile
	 * @since   6.1.7
	 */
	public function getClassFile(Container $container): ClassFile
	{
		return new ClassFile(
			$container->get('Extrusion.Powers.Parser')
		);
	}

	/**
	 * Get the Placeholders Resolver.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Placeholders
	 * @since   6.1.7
	 */
	public function getPlaceholders(Container $container): Placeholders
	{
		return new Placeholders(
			$container->get('Extrusion.Config'),
			$container->get('Load'),
			$container->get('Extrusion.Registry.Report')
		);
	}

	/**
	 * Get the Namespacer Resolver.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Namespacer
	 * @since   6.1.7
	 */
	public function getNamespacer(Container $container): Namespacer
	{
		return new Namespacer(
			$container->get('Extrusion.Powers.Resolver.Placeholders')
		);
	}

	/**
	 * Get the Existing Power Resolver.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Existing
	 * @since   6.1.7
	 */
	public function getExisting(Container $container): Existing
	{
		return new Existing(
			$container->get('Load'),
			$container->get('Extrusion.Powers.Resolver.Namespacer'),
			$container->get('Extrusion.Registry.Report')
		);
	}

	/**
	 * Get the Harvester.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Harvester
	 * @since   6.1.7
	 */
	public function getHarvester(Container $container): Harvester
	{
		return new Harvester(
			$container->get('Extrusion.Config'),
			$container->get('Extrusion.Scanner'),
			$container->get('Extrusion.Powers.Reader.ClassFile'),
			$container->get('Extrusion.Powers.Resolver.Namespacer'),
			$container->get('Extrusion.Powers.Resolver.Existing'),
			$container->get('Extrusion.Resolver.Guid'),
			$container->get('Extrusion.Registry.Harvest'),
			$container->get('Extrusion.Registry.Report')
		);
	}

	/**
	 * Get the Assembler.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Assembler
	 * @since   6.1.7
	 */
	public function getAssembler(Container $container): Assembler
	{
		return new Assembler(
			$container->get('Extrusion.Config'),
			$container->get('Extrusion.Registry.Harvest'),
			$container->get('Extrusion.Powers.Resolver.Existing'),
			$container->get('Extrusion.Resolver.Pairing'),
			$container->get('Extrusion.Registry.Report'),
			$container->get('Extrusion.Resolver.Constants')
		);
	}

	/**
	 * Get the Power Writer.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  PowerWriter
	 * @since   6.1.7
	 */
	public function getPowerWriter(Container $container): PowerWriter
	{
		return new PowerWriter(
			$container->get('Extrusion.Config'),
			$container->get('Extrusion.Registry.Resolved'),
			$container->get('Data.Item'),
			$container->get('Extrusion.Registry.Report'),
			$container->get('Extrusion.Registry.Harvest')
		);
	}

	/**
	 * Get the Powers Extruder.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  Extruder
	 * @since   6.1.7
	 */
	public function getExtruder(Container $container): Extruder
	{
		return new Extruder(
			$container->get('Extrusion.Config'),
			$container->get('Extrusion.Scope'),
			$container->get('Extrusion.Powers.Harvester'),
			$container->get('Extrusion.Powers.Assembler'),
			$container->get('Extrusion.Powers.Writer.Power'),
			$container->get('Extrusion.Registry.Harvest'),
			$container->get('Extrusion.Registry.Report'),
			$container->get('Extrusion.Registry.Message')
		);
	}
}
