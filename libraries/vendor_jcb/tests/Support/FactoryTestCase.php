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
use LogicException;
use ReflectionClass;
use ReflectionProperty;
use Throwable;


/**
 * Test case that isolates static VDM factory containers.
 *
 * @since  1.0.0
 */
abstract class FactoryTestCase extends TestCase
{
	/**
	 * Original factory-container state keyed by declaring property.
	 *
	 * @var    array<string, array{property: ReflectionProperty, value: mixed}>
	 * @since  1.0.0
	 */
	private array $isolatedFactories = [];

	/**
	 * Reset a factory before use and arrange a second reset after the test.
	 *
	 * @param   class-string  $factoryClass  The concrete static factory class.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function isolateFactory(string $factoryClass): void
	{
		$container = $this->getFactoryContainerProperty($factoryClass);
		$stateKey = $container->getDeclaringClass()->getName() . '::$container';

		if (!array_key_exists($stateKey, $this->isolatedFactories))
		{
			if (!$container->isInitialized())
			{
				throw new LogicException('Factory container property is uninitialized and cannot be isolated reversibly: ' . $factoryClass);
			}

			$this->isolatedFactories[$stateKey] = [
				'property' => $container,
				'value' => $container->getValue()
			];
		}

		$this->resetFactoryContainer($factoryClass);
	}

	/**
	 * Reset the protected static container on a VDM factory.
	 *
	 * @param   class-string  $factoryClass  The concrete static factory class.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function resetFactoryContainer(string $factoryClass): void
	{
		$container = $this->getFactoryContainerProperty($factoryClass);

		if (!$container->isInitialized())
		{
			throw new LogicException('Factory container property is uninitialized and cannot be reset reversibly: ' . $factoryClass);
		}

		$container->setValue(null, null);
	}

	/**
	 * Resolve and validate the protected static container on a VDM factory.
	 *
	 * @param   class-string  $factoryClass  The concrete static factory class.
	 *
	 * @return  ReflectionProperty
	 * @since   1.0.0
	 */
	private function getFactoryContainerProperty(string $factoryClass): ReflectionProperty
	{
		if (!class_exists($factoryClass))
		{
			throw new InvalidArgumentException('Factory class does not exist: ' . $factoryClass);
		}

		$reflection = new ReflectionClass($factoryClass);

		if (!$reflection->hasProperty('container'))
		{
			throw new LogicException('Factory does not declare or inherit a container property: ' . $factoryClass);
		}

		$container = $reflection->getProperty('container');

		if (!$container->isStatic())
		{
			throw new LogicException('Factory container property is not static: ' . $factoryClass);
		}

		return $container;
	}

	/**
	 * Reset every factory isolated by the current test.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		$failure = null;

		foreach ($this->isolatedFactories as $state)
		{
			try
			{
				$state['property']->setValue(null, $state['value']);
			}
			catch (Throwable $error)
			{
				$failure ??= $error;
			}
		}

		$this->isolatedFactories = [];

		try
		{
			parent::tearDown();
		}
		catch (Throwable $error)
		{
			$failure ??= $error;
		}

		if ($failure !== null)
		{
			throw $failure;
		}
	}
}
