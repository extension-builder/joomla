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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Builder;


use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use stdClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Joomla\Interfaces\Registryinterface;
use VDM\Tests\Support\BuilderRegistryProvider;


/**
 * Shared registry contract for every Compiler Builder leaf.
 *
 * @since  6.1.6
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Builder')]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(Placefix::class)]
final class BuilderRegistryContractTest extends TestCase
{
	/**
	 * Keep the explicit provider synchronized with the production Builder folder.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testProviderOwnsEveryBuilderLeafExactlyOnce(): void
	{
		$builderDirectory = dirname(__DIR__, 6)
			. '/VDM.Joomla/src/Componentbuilder/Compiler/Builder';
		$files = glob($builderDirectory . '/*.php');

		$this->assertIsArray($files);

		$actual = array_map(
			static fn (string $file): string => basename($file, '.php'),
			$files
		);
		$expected = BuilderRegistryProvider::builderNames();
		sort($actual, SORT_STRING);
		sort($expected, SORT_STRING);

		$this->assertCount(112, $actual);
		$this->assertSame($expected, $actual);
		$this->assertSame($expected, array_values(array_unique($expected)));
	}

	/**
	 * Verify leaf identity, provider-safe construction, and declared defaults.
	 *
	 * @param   class-string  $class             Concrete Builder class.
	 * @param   string        $name              Expected short class name.
	 * @param   string|null   $separator         Expected path separator.
	 * @param   bool          $addAsArray         Expected null-argument add policy.
	 * @param   bool          $uniqueArray        Expected duplicate policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProviderExternal(BuilderRegistryProvider::class, 'builders')]
	public function testLeafIdentityAndProviderRelevantDefaults(
		string $class,
		string $name,
		?string $separator,
		bool $addAsArray,
		bool $uniqueArray
	): void
	{
		$this->assertTrue(class_exists($class));

		$reflection = new ReflectionClass($class);
		$constructor = $reflection->getConstructor();

		$this->assertFalse($reflection->isAbstract());
		$this->assertSame($name, $reflection->getShortName());
		$this->assertTrue($constructor === null || $constructor->isPublic());
		$this->assertSame(0, $constructor?->getNumberOfRequiredParameters() ?? 0);

		/** @var Registry $subject */
		$subject = $reflection->newInstance();

		$this->assertSame($class, $subject::class);
		$this->assertInstanceOf(Registry::class, $subject);
		$this->assertInstanceOf(Registryinterface::class, $subject);
		$this->assertSame($separator, $subject->getSeparator());
		$this->assertNull($subject->getName());
		$this->assertFalse($subject->isActive());
		$this->assertCount(0, $subject);
		$this->assertSame('fallback', $subject->get('missing', 'fallback'));
		$this->assertSame($addAsArray, $this->policy($subject, 'addAsArray'));
		$this->assertSame($uniqueArray, $this->policy($subject, 'uniqueArray'));
	}

	/**
	 * Apply the complete path mutation lifecycle to every leaf.
	 *
	 * @param   class-string  $class        Concrete Builder class.
	 * @param   string        $name         Expected short class name.
	 * @param   string|null   $separator    Expected path separator.
	 * @param   bool          $addAsArray   Expected null-argument add policy.
	 * @param   bool          $uniqueArray  Expected duplicate policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProviderExternal(BuilderRegistryProvider::class, 'builders')]
	public function testPathSetAddGetExistsRemoveAndDefaultContract(
		string $class,
		string $name,
		?string $separator,
		bool $addAsArray,
		bool $uniqueArray
	): void
	{
		/** @var Registry $subject */
		$subject = new $class();
		$path = $this->path($separator);

		$this->assertSame($subject, $subject->set($path, 'alpha'));
		$this->assertTrue($subject->exists($path));
		$this->assertSame('alpha', $subject->get($path));
		$this->assertSame($subject, $subject->append($path, '-beta'));
		$this->assertSame('alpha-beta', $subject->get($path));
		$this->assertSame('alpha-beta', $subject->def($path, 'ignored'));
		$this->assertSame('created', $subject->def($this->secondaryPath($separator), 'created'));
		$this->assertSame($subject, $subject->remove($path));
		$this->assertFalse($subject->exists($path));
		$this->assertSame('fallback', $subject->get($path, 'fallback'));

		$subject->add($path, 'first', true)->add($path, 'second', true);
		$this->assertSame(['first', 'second'], $subject->get($path));
	}

	/**
	 * Apply serialization, iteration, ArrayAccess, flatten, and clone contracts.
	 *
	 * @param   class-string  $class        Concrete Builder class.
	 * @param   string        $name         Expected short class name.
	 * @param   string|null   $separator    Expected path separator.
	 * @param   bool          $addAsArray   Expected null-argument add policy.
	 * @param   bool          $uniqueArray  Expected duplicate policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProviderExternal(BuilderRegistryProvider::class, 'builders')]
	public function testRepresentationArrayAccessFlattenAndCloneContract(
		string $class,
		string $name,
		?string $separator,
		bool $addAsArray,
		bool $uniqueArray
	): void
	{
		/** @var Registry $subject */
		$subject = new $class();
		$path = $this->path($separator);
		$subject[$path] = 'value';

		$this->assertTrue(isset($subject[$path]));
		$this->assertSame('value', $subject[$path]);
		$this->assertSame($subject->toArray(), $subject->jsonSerialize());
		$this->assertSame($subject->toArray(), iterator_to_array($subject));
		$this->assertSame($subject->toArray(), json_decode((string) $subject, true));
		$this->assertCount(1, $subject);
		$this->assertSame(['value'], array_values($subject->flatten('/', true)));

		$object = new stdClass();
		$object->value = 'original';
		$subject[$path] = $object;
		$clone = clone $subject;
		$clone[$path]->value = 'changed';

		$this->assertSame('original', $subject[$path]->value);
		$this->assertSame('changed', $clone[$path]->value);

		unset($subject[$path]);
		$this->assertFalse(isset($subject[$path]));
	}

	/**
	 * Reject an empty public path for every leaf, including path overrides.
	 *
	 * @param   class-string  $class        Concrete Builder class.
	 * @param   string        $name         Expected short class name.
	 * @param   string|null   $separator    Expected path separator.
	 * @param   bool          $addAsArray   Expected null-argument add policy.
	 * @param   bool          $uniqueArray  Expected duplicate policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProviderExternal(BuilderRegistryProvider::class, 'builders')]
	public function testEmptyPathIsRejectedByEveryLeaf(
		string $class,
		string $name,
		?string $separator,
		bool $addAsArray,
		bool $uniqueArray
	): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new $class())->set('', 'value');
	}

	/**
	 * Get a path compatible with the leaf's constructor-selected separator.
	 *
	 * @param   string|null  $separator  Builder separator.
	 *
	 * @return  string
	 * @since   6.1.6
	 */
	private function path(?string $separator): string
	{
		return $separator === '|' ? 'root|leaf' : 'root.leaf';
	}

	/**
	 * Get a distinct path compatible with the leaf separator.
	 *
	 * @param   string|null  $separator  Builder separator.
	 *
	 * @return  string
	 * @since   6.1.6
	 */
	private function secondaryPath(?string $separator): string
	{
		return $separator === '|' ? 'other|leaf' : 'other.leaf';
	}

	/**
	 * Read a protected registry policy without changing production visibility.
	 *
	 * @param   Registry  $subject   Builder registry.
	 * @param   string    $property  Policy property name.
	 *
	 * @return  bool
	 * @since   6.1.6
	 */
	private function policy(Registry $subject, string $property): bool
	{
		return (new ReflectionProperty(ActiveRegistry::class, $property))->getValue($subject);
	}
}
