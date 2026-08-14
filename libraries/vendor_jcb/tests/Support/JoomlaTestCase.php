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

namespace VDM\Tests\Support;


use InvalidArgumentException;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\DI\Container;
use ReflectionClass;
use ReflectionProperty;


/**
 * Test case that isolates Joomla's process-static Factory state.
 *
 * @since  1.0.0
 */
abstract class JoomlaTestCase extends FactoryTestCase
{
	/**
	 * Joomla Factory state that existed before the current test.
	 *
	 * @var    array<string, mixed>
	 * @since  1.0.0
	 */
	private array $joomlaFactoryState = [];

	/**
	 * Snapshot Joomla Factory state and start the test from its declared defaults.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$reflection = new ReflectionClass(JoomlaFactory::class);
		$defaults = $reflection->getDefaultProperties();

		foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $property)
		{
			if ($property->getDeclaringClass()->getName() !== JoomlaFactory::class)
			{
				continue;
			}

			$name = $property->getName();
			$this->joomlaFactoryState[$name] = $property->getValue();
			$property->setValue(null, $defaults[$name] ?? null);
		}
	}

	/**
	 * Install a test-specific Joomla dependency-injection container.
	 *
	 * @param   Container  $container  The container to expose through Joomla Factory.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setJoomlaContainer(Container $container): void
	{
		$this->setJoomlaFactoryProperty('container', $container);
	}

	/**
	 * Install a test-specific Joomla application.
	 *
	 * @param   CMSApplicationInterface  $application  The application to expose through Joomla Factory.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setJoomlaApplication(CMSApplicationInterface $application): void
	{
		$this->setJoomlaFactoryProperty('application', $application);
	}

	/**
	 * Replace one declared static Joomla Factory property for the current test.
	 *
	 * @param   string  $name   The declared property name.
	 * @param   mixed   $value  The temporary property value.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setJoomlaFactoryProperty(string $name, mixed $value): void
	{
		$reflection = new ReflectionClass(JoomlaFactory::class);

		if (!$reflection->hasProperty($name))
		{
			throw new InvalidArgumentException('Unknown Joomla Factory property: ' . $name);
		}

		$property = $reflection->getProperty($name);

		if (!$property->isStatic())
		{
			throw new InvalidArgumentException('Joomla Factory property is not static: ' . $name);
		}

		$property->setValue(null, $value);
	}

	/**
	 * Restore Joomla Factory state after the current test.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		$reflection = new ReflectionClass(JoomlaFactory::class);

		foreach ($this->joomlaFactoryState as $name => $value)
		{
			$reflection->getProperty($name)->setValue(null, $value);
		}

		$this->joomlaFactoryState = [];

		parent::tearDown();
	}
}
