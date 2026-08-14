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

namespace VDM\Tests\Contract;


use Joomla\DI\Container;
use LogicException;
use PHPUnit\Framework\Attributes\CoversNothing;
use ReflectionClass;
use VDM\Tests\Support\FactoryFixture;
use VDM\Tests\Support\FactoryTestCase;
use VDM\Tests\Support\FilesystemTestCase;
use VDM\Tests\Support\PowerItemFactoryFixture;
use VDM\Tests\Support\TestCase;


/**
 * Shared test-support isolation contracts.
 *
 * @since  1.0.0
 */
#[CoversNothing]
final class TestIsolationTest extends FilesystemTestCase
{
	/**
	 * Restore the exact directory captured by the shared test base.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testTestCaseRestoresOriginalWorkingDirectory(): void
	{
		$suiteDirectory = getcwd();
		$this->assertIsString($suiteDirectory);

		$originalDirectory = $this->createTemporaryDirectory('cwd/original');
		$changedDirectory = $this->createTemporaryDirectory('cwd/changed');

		$fixture = new class('placeholder') extends TestCase
		{
			/**
			 * Capture the fixture's process state.
			 *
			 * @return  void
			 * @since   1.0.0
			 */
			public function beginIsolation(): void
			{
				parent::setUp();
			}

			/**
			 * Restore the fixture's process state.
			 *
			 * @return  void
			 * @since   1.0.0
			 */
			public function endIsolation(): void
			{
				parent::tearDown();
			}

			/**
			 * Provide the PHPUnit fixture's required test method.
			 *
			 * @return  void
			 * @since   1.0.0
			 */
			public function placeholder(): void
			{
			}
		};

		try
		{
			$this->assertTrue(chdir($originalDirectory));
			$fixture->beginIsolation();
			$this->assertTrue(chdir($changedDirectory));
			$fixture->endIsolation();

			$this->assertSame($originalDirectory, getcwd());
		}
		finally
		{
			chdir($suiteDirectory);
		}
	}

	/**
	 * Restore every isolated factory's exact prior container identity.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testFactoryTestCaseRestoresEveryOriginalContainer(): void
	{
		$fixtureState = $this->getFactoryContainer(FactoryFixture::class);
		$powerState = $this->getFactoryContainer(PowerItemFactoryFixture::class);
		$fixtureContainer = new Container();
		$powerContainer = new Container();
		$testCase = $this->createFactoryTestCase();

		try
		{
			$this->setFactoryContainer(FactoryFixture::class, $fixtureContainer);
			$this->setFactoryContainer(PowerItemFactoryFixture::class, $powerContainer);

			$testCase->beginIsolation();
			$testCase->isolate(FactoryFixture::class);
			$testCase->isolate(PowerItemFactoryFixture::class);

			$this->assertNull($this->getFactoryContainer(FactoryFixture::class));
			$this->assertNull($this->getFactoryContainer(PowerItemFactoryFixture::class));

			$this->setFactoryContainer(FactoryFixture::class, new Container());
			$this->setFactoryContainer(PowerItemFactoryFixture::class, new Container());
			$testCase->endIsolation();

			$this->assertSame($fixtureContainer, $this->getFactoryContainer(FactoryFixture::class));
			$this->assertSame($powerContainer, $this->getFactoryContainer(PowerItemFactoryFixture::class));
		}
		finally
		{
			$this->setFactoryContainer(FactoryFixture::class, $fixtureState);
			$this->setFactoryContainer(PowerItemFactoryFixture::class, $powerState);
		}
	}

	/**
	 * Preserve an initialized null container when isolation creates a replacement.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testFactoryTestCaseRestoresOriginalNullContainer(): void
	{
		$originalState = $this->getFactoryContainer(FactoryFixture::class);
		$testCase = $this->createFactoryTestCase();

		try
		{
			$this->setFactoryContainer(FactoryFixture::class, null);
			$testCase->beginIsolation();
			$testCase->isolate(FactoryFixture::class);
			FactoryFixture::getContainer();
			$testCase->endIsolation();

			$this->assertNull($this->getFactoryContainer(FactoryFixture::class));
		}
		finally
		{
			$this->setFactoryContainer(FactoryFixture::class, $originalState);
		}
	}

	/**
	 * Reject an uninitialized static property without irreversibly changing it.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testFactoryTestCaseRejectsUninitializedContainerWithoutMutation(): void
	{
		$factory = new class()
		{
			/**
			 * Uninitialized container state used by the isolation contract.
			 *
			 * @var    Container|null
			 * @since  1.0.0
			 */
			protected static ?Container $container;
		};
		$factoryClass = $factory::class;
		$container = (new ReflectionClass($factoryClass))->getProperty('container');
		$testCase = $this->createFactoryTestCase();

		$testCase->beginIsolation();

		try
		{
			$testCase->isolate($factoryClass);
			$this->fail('An uninitialized static factory container must be rejected.');
		}
		catch (LogicException $error)
		{
			$this->assertStringContainsString('cannot be isolated reversibly', $error->getMessage());
		}
		finally
		{
			$testCase->endIsolation();
		}

		$this->assertFalse($container->isInitialized());
	}

	/**
	 * Build an externally controlled factory-isolation test case.
	 *
	 * @return  FactoryTestCase
	 * @since   1.0.0
	 */
	private function createFactoryTestCase(): FactoryTestCase
	{
		return new class('placeholder') extends FactoryTestCase
		{
			/**
			 * Capture the fixture's process state.
			 *
			 * @return  void
			 * @since   1.0.0
			 */
			public function beginIsolation(): void
			{
				parent::setUp();
			}

			/**
			 * Isolate one static factory container.
			 *
			 * @param   class-string  $factoryClass  Factory class to isolate.
			 *
			 * @return  void
			 * @since   1.0.0
			 */
			public function isolate(string $factoryClass): void
			{
				$this->isolateFactory($factoryClass);
			}

			/**
			 * Restore the fixture's process and factory state.
			 *
			 * @return  void
			 * @since   1.0.0
			 */
			public function endIsolation(): void
			{
				parent::tearDown();
			}

			/**
			 * Provide the PHPUnit fixture's required test method.
			 *
			 * @return  void
			 * @since   1.0.0
			 */
			public function placeholder(): void
			{
			}
		};
	}

	/**
	 * Read one factory's static container without initializing it.
	 *
	 * @param   class-string  $factoryClass  Factory class to inspect.
	 *
	 * @return  Container|null
	 * @since   1.0.0
	 */
	private function getFactoryContainer(string $factoryClass): ?Container
	{
		return (new ReflectionClass($factoryClass))->getProperty('container')->getValue();
	}

	/**
	 * Replace one fixture factory's static container.
	 *
	 * @param   class-string    $factoryClass  Factory class to change.
	 * @param   Container|null  $container     Container state to install.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function setFactoryContainer(string $factoryClass, ?Container $container): void
	{
		(new ReflectionClass($factoryClass))->getProperty('container')->setValue(null, $container);
	}
}
