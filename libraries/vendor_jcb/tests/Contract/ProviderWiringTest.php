<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    20th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use ReflectionClass;
use ReflectionMethod;
use VDM\Tests\Support\RecordingServiceProviderContainer;
use VDM\Tests\Support\SourceInventory;
use VDM\Tests\Support\TestCase;


/**
 * Every factory must hand its class the services its constructor declares.
 *
 * A class built by hand in a unit test never goes through its factory, so a
 * factory that passes too few services, or passes them in the wrong order,
 * passes every test and then throws the moment the container is asked for it.
 * That is not a hypothetical: the Model ValidationFix factory was left calling
 * a constructor that had gained a registry, and every compile that reached it
 * would have died on an ArgumentCountError.
 *
 * This reads the alias map out of every provider in the library, then walks
 * each factory's container keys against the constructor of the class it builds.
 *
 * @since  1.0.0
 */
#[CoversNothing]
final class ProviderWiringTest extends TestCase
{
	/**
	 * Assert every plainly-constructed service is given what its class asks for.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testEveryFactoryPassesWhatItsClassAsksFor(): void
	{
		// reading the catalog compiles every class a provider names, and a
		// signature deprecation in one of them is not what this test is about;
		// every other test in the suite still reports them
		set_error_handler(
			static fn (): bool => true, E_DEPRECATED | E_USER_DEPRECATED
		);

		try
		{
			[$aliased, $factories] = $this->catalog();
			$read = [];

			foreach ($factories as $key => [$providerClass, $method])
			{
				$built = $this->buildsWhat($providerClass, $method);

				if ($built !== null)
				{
					$read[$key] = $built;
				}
			}
		}
		finally
		{
			restore_error_handler();
		}

		$this->assertNotEmpty($factories, 'No service factories were found.');

		$errors = [];
		$checked = 0;

		foreach ($read as $key => [$class, $given])
		{
			$checked++;
			$errors = array_merge(
				$errors, $this->mismatches($key, $class, $given, $aliased)
			);
		}

		$this->assertGreaterThan(
			500, $checked, 'Far fewer factories were read than the library holds.'
		);
		$this->assertSame([], $errors, implode("\n", $errors));
	}

	/**
	 * Read what every provider in the library registers.
	 *
	 * @return  array{0: array<string, array<string, string>>, 1: array<string, array{string, string}>}
	 *          Every class each key was aliased to, and the factory behind each key.
	 * @since   1.0.0
	 */
	private function catalog(): array
	{
		$aliased = [];
		$factories = [];

		foreach ($this->providers() as $name)
		{
			$container = new RecordingServiceProviderContainer();
			(new $name())->register($container);

			foreach ($container->aliasesRegistered() as [$class, $key])
			{
				// a key is only unambiguous within one container; the library
				// has several, and 'Config' means a different class in each
				$aliased[$key][$class] = $class;
			}

			foreach ($container->servicesRegistered() as [$key, $callable])
			{
				if (is_array($callable) && isset($callable[1]) && is_string($callable[1]))
				{
					$factories[$key] = [$name, $callable[1]];
				}
			}
		}

		return [$aliased, $factories];
	}

	/**
	 * Every service provider in the library source.
	 *
	 * @return  array<int, class-string<ServiceProviderInterface>>
	 * @since   1.0.0
	 */
	private function providers(): array
	{
		$providers = [];

		$vendorRoot = dirname(__DIR__, 2);

		foreach (SourceInventory::discover() as $path => $entry)
		{
			$source = (string) file_get_contents($vendorRoot . '/' . $path);

			// only read a file that names the interface: loading the whole
			// library to find the providers drags in every deprecation with it
			if (!str_contains($source, 'ServiceProviderInterface'))
			{
				continue;
			}

			foreach ($entry['declarations'] as $declaration)
			{
				if ($declaration['kind'] !== 'class'
					|| !class_exists($declaration['name']))
				{
					continue;
				}

				$reflection = new ReflectionClass($declaration['name']);

				$constructor = $reflection->getConstructor();

				if ($reflection->isInstantiable()
					&& $reflection->implementsInterface(ServiceProviderInterface::class)
					&& ($constructor === null
						|| $constructor->getNumberOfRequiredParameters() === 0))
				{
					$providers[] = $declaration['name'];
				}
			}
		}

		return $providers;
	}

	/**
	 * Read the class one factory builds and the keys it hands its constructor.
	 *
	 * Only the plain `return new X($container->get('a'), ...);` shape is read.
	 * A factory that computes anything is deliberately left alone: what it
	 * builds is its own business, and this test would only guess at it.
	 *
	 * @param   string  $providerClass  The provider holding the factory.
	 * @param   string  $method         The factory method.
	 *
	 * @return  array{0: class-string, 1: array<int, string>}|null
	 * @since   1.0.0
	 */
	private function buildsWhat(string $providerClass, string $method): ?array
	{
		if (!method_exists($providerClass, $method))
		{
			return null;
		}

		$reflection = new ReflectionMethod($providerClass, $method);
		$built = $reflection->getReturnType()?->getName();

		if ($built === null || !class_exists($built))
		{
			return null;
		}

		$source = implode('', array_slice(
			file($reflection->getFileName()),
			$reflection->getStartLine() - 1,
			$reflection->getEndLine() - $reflection->getStartLine() + 1
		));

		if (!preg_match('/return new [A-Za-z0-9_]+\(\s*(.*?)\s*\);/s', $source, $matched))
		{
			return null;
		}

		// anything other than a bare list of container keys is out of scope
		if (preg_replace("/\\\$container->get\('[^']+'\)|[\s,]/", '', $matched[1]) !== '')
		{
			return null;
		}

		preg_match_all("/\\\$container->get\('([^']+)'\)/", $matched[1], $keys);

		return [$built, $keys[1]];
	}

	/**
	 * Report where a factory and the constructor it calls disagree.
	 *
	 * @param   string              $key      The container key being built.
	 * @param   class-string        $class    The class the factory builds.
	 * @param   array<int, string>  $given    The keys the factory passes, in order.
	 * @param   array<string, array<string, string>>  $aliased  Every class each key was aliased to.
	 *
	 * @return  array<int, string>
	 * @since   1.0.0
	 */
	private function mismatches(
		string $key,
		string $class,
		array $given,
		array $aliased
	): array
	{
		$constructor = (new ReflectionClass($class))->getConstructor();
		$parameters = $constructor === null ? [] : $constructor->getParameters();
		$required = 0;

		foreach ($parameters as $parameter)
		{
			if (!$parameter->isOptional())
			{
				$required++;
			}
		}

		if (count($given) < $required || count($given) > count($parameters))
		{
			return [sprintf(
				'%s: %s takes %d parameter(s), %d of them required, but the factory passes %d.',
				$key, $class, count($parameters), $required, count($given)
			)];
		}

		$errors = [];

		foreach ($given as $at => $wanted)
		{
			$declared = $parameters[$at]->getType()?->getName();

			if ($declared === null || !isset($aliased[$wanted]))
			{
				continue;
			}

			$candidates = $aliased[$wanted];

			// the key is only wrong when none of the classes it names would fit
			foreach ($candidates as $candidate)
			{
				if (is_a($candidate, $declared, true))
				{
					continue 2;
				}
			}

			$errors[] = sprintf(
				'%s: argument %d is %s (%s), but %s::__construct wants %s.',
				$key, $at + 1, $wanted, implode(', ', $candidates), $class, $declared
			);
		}

		return $errors;
	}
}
