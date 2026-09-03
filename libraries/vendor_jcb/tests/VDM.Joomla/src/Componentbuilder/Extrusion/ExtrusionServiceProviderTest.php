<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion;


use FilesystemIterator;
use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use VDM\Joomla\Componentbuilder\Extrusion\Factory;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Discovery;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Extrusion;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Powers;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Reader;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Registry;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Resolver;
use VDM\Joomla\Componentbuilder\Extrusion\Service\Writer;
use VDM\Tests\Support\RecordingServiceProviderContainer;
use VDM\Tests\Support\ServiceProviderTestCase;


/**
 * Composition contract for the extrusion engine.
 *
 * The providers are the only place an extrusion collaborator is constructed, and
 * the Factory is the only permitted static resolution, so the catalogue below is
 * the whole surface through which the engine is wired. Nothing here resolves a
 * service that would open a database connection: the container is inspected, not
 * exercised.
 *
 * The six extrusion interfaces are owned here as well. An interface holds no
 * executable line, so its ownership is this file's structural assertions rather
 * than a coverage target, which is why none of them appears as a covered class.
 *
 * @since  6.1.6
 */
#[CoversClass(Factory::class)]
#[CoversClass(Extrusion::class)]
#[CoversClass(Registry::class)]
#[CoversClass(Discovery::class)]
#[CoversClass(Reader::class)]
#[CoversClass(Resolver::class)]
#[CoversClass(Writer::class)]
#[CoversClass(Powers::class)]
final class ExtrusionServiceProviderTest extends ServiceProviderTestCase
{
	/**
	 * The namespace prefix of the whole extrusion tree.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const PREFIX = 'VDM\Joomla\Componentbuilder\Extrusion';

	/**
	 * The service providers the JCB data pipeline contributes, in order.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const PIPELINE = ['Database', 'Table', 'Model', 'Data'];

	/**
	 * The extrusion service providers, in the order the Factory composes them.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const PROVIDERS = [
		'Registry', 'Discovery', 'Reader', 'Resolver', 'Writer', 'Powers', 'Extrusion'
	];

	/**
	 * The method each extrusion interface declares.
	 *
	 * @var    array<string, array<string>>
	 * @since  6.1.6
	 */
	private const CONTRACTS = [
		'ExtruderInterface' => ['reset', 'path', 'dump', 'extrude', 'messages'],
		'PowersExtruderInterface' => ['reset', 'library', 'component', 'harvest', 'extrude', 'messages'],
		'LayoutInterface' => ['version', 'kinds', 'candidates', 'roots'],
		'LocatorInterface' => ['kind', 'locate'],
		'PrecedenceInterface' => ['resolve'],
		'ReaderInterface' => ['read'],
		'WriterInterface' => ['write']
	];

	/**
	 * Verify every extrusion provider alias, key, factory, and lifecycle.
	 *
	 * @param   class-string<ServiceProviderInterface>           $providerClass  Provider under test.
	 * @param   array{aliases: int, services: int, hash: string}  $expected       Reviewed catalog fingerprint.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('providerContracts')]
	public function testProviderCatalog(string $providerClass, array $expected): void
	{
		$this->assertServiceProviderContract($providerClass, $expected);
	}

	/**
	 * Provide the reviewed extrusion service provider catalogs.
	 *
	 * @return  iterable<string, array{class-string<ServiceProviderInterface>, array{aliases: int, services: int, hash: string}}>
	 * @since   6.1.6
	 */
	public static function providerContracts(): iterable
	{
		yield 'extrusion' => [Extrusion::class, [
			'aliases' => 2,
			'services' => 1,
			'hash' => '3a198de224a88564b42e2a4deedf2ee460b0902849b7d4127592a0b253af0185'
		]];
		yield 'registry' => [Registry::class, [
			'aliases' => 15,
			'services' => 15,
			'hash' => '9202d8e9802545b6e893b751ff753ca8ecc897001015f79dbb7fb96824ccf4e7'
		]];
		yield 'discovery' => [Discovery::class, [
			'aliases' => 15,
			'services' => 15,
			'hash' => 'f9d09367b448e9c69b0ca50aafa24edfe20b68d70a4b085d23f9c3900d9e2f42'
		]];
		yield 'reader' => [Reader::class, [
			'aliases' => 9,
			'services' => 9,
			'hash' => 'c3099dbda11915b6480d87f41c9ca82ad92d0cc24a3825d072435c81ec711633'
		]];
		yield 'resolver' => [Resolver::class, [
			'aliases' => 23,
			'services' => 23,
			'hash' => 'e61e42645309527869654e3500376c72a72d1af836d0530aa22bcadb953d5300'
		]];
		yield 'writer' => [Writer::class, [
			'aliases' => 12,
			'services' => 12,
			'hash' => '7ed1666cb188a54268bd241eb4c7a34d56f2fd008ba1e34d9a2dbbb1029b272c'
		]];
		yield 'powers' => [Powers::class, [
			'aliases' => 11,
			'services' => 10,
			'hash' => 'ad961696aae10b4670b5ed96b300e9c7f4cbcb34f6fe469cd2cbdb2693b82c53'
		]];
	}

	/**
	 * Every registered service is shared and built by a public provider method.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEveryProviderRegistersOnlySharedServicesOfItsOwn(): void
	{
		foreach (self::PROVIDERS as $name)
		{
			$provider = $this->provider($name);
			$container = new RecordingServiceProviderContainer();
			$provider->register($container);
			$services = $container->servicesRegistered();

			$this->assertNotSame([], $services, 'Provider registers nothing: ' . $name);

			$keys = [];

			foreach ($services as [$key, $factory, $protected])
			{
				$this->assertArrayNotHasKey($key, $keys, 'Duplicate service key: ' . $key);
				$keys[$key] = true;
				$this->assertTrue($container->isShared($key), 'Service is not shared: ' . $key);
				$this->assertTrue($protected, 'Service is not protected: ' . $key);
				$this->assertIsArray($factory, 'Factory is not an instance method: ' . $key);
				$this->assertSame($provider, $factory[0], 'Factory is not this provider: ' . $key);
				$this->assertTrue(
					(new ReflectionMethod($provider, (string) $factory[1]))->isPublic(),
					'Factory method is not public: ' . $factory[1]
				);
			}
		}
	}

	/**
	 * Every namespaced alias names a class or interface that really exists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEveryNamespacedAliasNamesADeclaredClassOrInterface(): void
	{
		$namespaced = 0;

		foreach (self::PROVIDERS as $name)
		{
			$container = new RecordingServiceProviderContainer();
			$this->provider($name)->register($container);
			$keys = [];

			foreach ($container->servicesRegistered() as [$key])
			{
				$keys[$key] = true;
			}

			foreach ($container->aliasesRegistered() as [$alias, $key])
			{
				$this->assertArrayHasKey($key, $keys, 'Alias targets an unregistered key: ' . $key);

				if (!str_contains($alias, '\\'))
				{
					continue;
				}

				$namespaced++;
				$this->assertTrue(
					class_exists($alias) || interface_exists($alias),
					'Alias does not name a declared class or interface: ' . $alias
				);
			}
		}

		$this->assertSame(87, $namespaced);
	}

	/**
	 * The Factory composes the data pipeline and every extrusion provider.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFactoryComposesTheDataPipelineAndEveryExtrusionProvider(): void
	{
		$method = new ReflectionMethod(Factory::class, 'createContainer');

		$this->assertTrue($method->isProtected());
		$this->assertTrue($method->isStatic());
		$this->assertSame('Joomla\DI\Container', (string) $method->getReturnType());

		$found = [];
		preg_match_all(
			'/registerServiceProvider\(new ([A-Za-z]+)\(\)\)/',
			$this->body($method),
			$found
		);

		$this->assertSame(
			array_merge(self::PIPELINE, self::PROVIDERS),
			$found[1]
		);
	}

	/**
	 * The composed container knows the pipeline and extrusion keys, unresolved.
	 *
	 * The database aliases are only inspected. Resolving one would build a Joomla
	 * database object, which is exactly what an extrusion unit test must not do.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testComposedContainerKnowsEveryKeyWithoutResolvingIt(): void
	{
		$method = new ReflectionMethod(Factory::class, 'createContainer');
		$method->setAccessible(true);
		$container = $method->invoke(null);

		foreach ([
			'Load',
			'Data.Item',
			'Table',
			'Model.Load',
			'Extruder',
			'Extrusion.Config',
			'Extrusion.Collector',
			'Extrusion.Reader.Dispatcher',
			'Extrusion.Assembler',
			'Extrusion.Writer.Dispatcher'
		] as $key)
		{
			$this->assertTrue($container->has($key), 'The container cannot see: ' . $key);
			$this->assertTrue($container->isShared($key), 'The service is not shared: ' . $key);
		}

		$this->assertFalse($container->has('Extrusion.Nothing.Registered'));
	}

	/**
	 * Every extrusion interface is an interface declaring exactly its contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEveryExtrusionInterfaceDeclaresItsWholeContract(): void
	{
		foreach (self::CONTRACTS as $short => $expected)
		{
			$name = self::PREFIX . '\Interfaces\\' . $short;

			$this->assertTrue(interface_exists($name), 'Missing interface: ' . $name);

			$reflection = new ReflectionClass($name);

			$this->assertTrue($reflection->isInterface(), 'Not an interface: ' . $name);
			$this->assertSame([], $reflection->getInterfaceNames(), 'Unexpected parent: ' . $name);
			$this->assertSame([], $reflection->getConstants(), 'Unexpected constant: ' . $name);

			$declared = array_map(
				static fn (ReflectionMethod $method): string => $method->getName(),
				$reflection->getMethods()
			);
			sort($declared, SORT_STRING);
			sort($expected, SORT_STRING);

			$this->assertSame($expected, $declared, 'Contract changed: ' . $name);

			foreach ($reflection->getMethods() as $method)
			{
				$this->assertTrue($method->isPublic(), 'Contract method is not public: ' . $method->getName());
				$this->assertTrue($method->isAbstract(), 'Contract method has a body: ' . $method->getName());
			}
		}
	}

	/**
	 * Every extrusion interface has a concrete implementation in the tree.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEveryExtrusionInterfaceHasAConcreteImplementation(): void
	{
		$classes = $this->declared();

		$this->assertGreaterThan(60, count($classes));

		foreach (array_keys(self::CONTRACTS) as $short)
		{
			$name = self::PREFIX . '\Interfaces\\' . $short;
			$concrete = [];

			foreach ($classes as $class)
			{
				$reflection = new ReflectionClass($class);

				if ($reflection->implementsInterface($name) && !$reflection->isAbstract())
				{
					$concrete[] = $class;
				}
			}

			$this->assertNotSame([], $concrete, 'No concrete implementation of: ' . $name);
		}
	}

	/**
	 * One extrusion service provider by its short class name.
	 *
	 * @param   string  $name  The provider's short class name.
	 *
	 * @return  ServiceProviderInterface  The provider instance.
	 * @since   6.1.6
	 */
	private function provider(string $name): ServiceProviderInterface
	{
		$class = self::PREFIX . '\Service\\' . $name;

		return new $class();
	}

	/**
	 * The source text of one method, read from its own file.
	 *
	 * @param   ReflectionMethod  $method  The method to read.
	 *
	 * @return  string  The method's source lines.
	 * @since   6.1.6
	 */
	private function body(ReflectionMethod $method): string
	{
		$lines = (array) file((string) $method->getFileName());

		return implode('', array_slice(
			$lines,
			$method->getStartLine() - 1,
			$method->getEndLine() - $method->getStartLine() + 1
		));
	}

	/**
	 * Every class the extrusion tree declares, by its fully qualified name.
	 *
	 * The names follow from the folder layout, so a class that moved without its
	 * namespace following it fails this test rather than quietly disappearing from
	 * the implementation scan.
	 *
	 * @return  array<string>  The declared class names.
	 * @since   6.1.6
	 */
	private function declared(): array
	{
		$root = dirname(__DIR__, 5) . '/VDM.Joomla/src/Componentbuilder/Extrusion';
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
		);
		$classes = [];

		foreach ($iterator as $file)
		{
			if (!$file->isFile() || $file->getExtension() !== 'php')
			{
				continue;
			}

			$relative = substr($file->getPathname(), strlen($root) + 1, -4);
			$name = self::PREFIX . '\\' . str_replace('/', '\\', $relative);

			$this->assertTrue(
				class_exists($name) || interface_exists($name),
				'The extrusion tree declares no ' . $name
			);

			if (class_exists($name))
			{
				$classes[] = $name;
			}
		}

		sort($classes, SORT_STRING);

		return $classes;
	}
}
