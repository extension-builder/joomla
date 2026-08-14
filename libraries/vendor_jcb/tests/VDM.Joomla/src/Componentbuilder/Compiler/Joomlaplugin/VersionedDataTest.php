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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Joomlaplugin;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaFive\Data as JoomlaFiveData;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaFour\Data as JoomlaFourData;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaSix\Data as JoomlaSixData;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaThree\Data as JoomlaThreeData;
use VDM\Joomla\Componentbuilder\Package\Builder\Get as Superpower;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Tests\Support\TestCase;


/**
 * Joomla 3-6 plugin data cache, validation, query-failure, and retry contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeData::class)]
#[CoversClass(JoomlaFourData::class)]
#[CoversClass(JoomlaFiveData::class)]
#[CoversClass(JoomlaSixData::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(GuidHelper::class)]
final class VersionedDataTest extends TestCase
{
	/**
	 * Resolve cached plugin identity aliases and reject malformed identifiers.
	 *
	 * @param   class-string  $class  Versioned data class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testCacheAndIdentifierContracts(string $class): void
	{
		$subject = $this->subject($class);
		$guid = '33333333-3333-4333-8333-333333333333';
		$plugin = (object) ['id' => 12, 'guid' => $guid, 'name' => 'Cached'];
		$this->setProperty($subject, 'data', [12 => $plugin]);
		$this->setProperty($subject, 'index', [12 => 12, $guid => 12]);

		$this->assertTrue($subject->exists());
		$this->assertTrue($subject->exists(12));
		$this->assertTrue($subject->exists($guid));
		$this->assertSame($plugin, $subject->get(12));
		$this->assertSame($plugin, $subject->get($guid));
		$this->assertSame([12 => $plugin], $subject->get());
		$this->assertFalse($subject->set('not a plugin identifier'));
		$this->assertNull($subject->get('not a plugin identifier'));
	}

	/**
	 * Return false for a numeric persistence miss without changing cache state.
	 *
	 * @param   class-string  $class  Versioned data class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testNumericDatabaseMissDoesNotMutateCache(string $class): void
	{
		$query = $this->query();
		$db = $this->database();
		$db->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$db->expects($this->once())->method('setQuery')->with($query);
		$db->expects($this->once())->method('execute');
		$db->expects($this->once())->method('getNumRows')->willReturn(0);
		$subject = $this->subject($class);
		$this->setProperty($subject, 'db', $db);

		$this->assertFalse($subject->set(404));
		$this->assertSame([], $this->property($subject, 'data'));
		$this->assertSame([], $this->property($subject, 'index'));
		$this->assertSame([], $this->property($subject, 'retry'));
	}

	/**
	 * Attempt remote synchronization once for a missing GUID and remember the retry.
	 *
	 * @param   class-string  $class  Versioned data class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testGuidMissAttemptsRemoteFetchOnlyOnce(string $class): void
	{
		$query = $this->query();
		$db = $this->database();
		$db->expects($this->exactly(2))->method('getQuery')->with(true)->willReturn($query);
		$db->expects($this->exactly(2))->method('setQuery')->with($query);
		$db->expects($this->exactly(2))->method('execute');
		$db->expects($this->exactly(2))->method('getNumRows')->willReturn(0);
		$guid = '44444444-4444-4444-8444-444444444444';
		$superpower = $this->getMockBuilder(Superpower::class)
			->disableOriginalConstructor()
			->onlyMethods(['get'])
			->getMock();
		$superpower->expects($this->once())->method('get')
			->with('joomla_plugin', [$guid])->willReturn(['added' => []]);
		$subject = $this->subject($class);
		$this->setProperty($subject, 'db', $db);
		$this->setProperty($subject, 'superpower', $superpower);

		$this->assertFalse($subject->set($guid));
		$this->assertFalse($subject->set($guid));
		$this->assertSame([$guid => true], $this->property($subject, 'retry'));
	}

	/**
	 * Provide every supported Joomla plugin data implementation.
	 *
	 * @return  array<string, array{class-string}>
	 * @since   6.1.6
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => [JoomlaThreeData::class],
			'Joomla 4' => [JoomlaFourData::class],
			'Joomla 5' => [JoomlaFiveData::class],
			'Joomla 6' => [JoomlaSixData::class]
		];
	}

	/**
	 * Create a versioned subject without its unrelated transformation graph.
	 *
	 * @param   class-string  $class  Versioned data class.
	 *
	 * @return  object
	 * @since   6.1.6
	 */
	private function subject(string $class): object
	{
		return (new ReflectionClass($class))->newInstanceWithoutConstructor();
	}

	/**
	 * Build a fluent select query double.
	 *
	 * @return  QueryInterface
	 * @since   6.1.6
	 */
	private function query(): QueryInterface
	{
		$query = $this->createStub(QueryInterface::class);
		$query->method('select')->willReturnSelf();
		$query->method('from')->willReturnSelf();
		$query->method('join')->willReturnSelf();
		$query->method('where')->willReturnSelf();

		return $query;
	}

	/**
	 * Build deterministic quoting around an expectation-ready database boundary.
	 *
	 * @return  DatabaseInterface&\PHPUnit\Framework\MockObject\MockObject
	 * @since   6.1.6
	 */
	private function database(): DatabaseInterface
	{
		$db = $this->createMock(DatabaseInterface::class);
		$db->method('quoteName')->willReturnCallback(
			static function (string|array $name, string|array|null $alias = null): string|array
			{
				if (is_array($name))
				{
					return array_map(static fn (string $value): string => '[' . $value . ']', $name);
				}

				return '[' . $name . (is_string($alias) ? ' AS ' . $alias : '') . ']';
			}
		);
		$db->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);

		return $db;
	}

	/**
	 * Replace or read one non-public dependency or state bucket.
	 *
	 * @param   object  $subject   Subject instance.
	 * @param   string  $property  Property name.
	 * @param   mixed   $value     New value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function setProperty(object $subject, string $property, mixed $value): void
	{
		(new ReflectionProperty($subject, $property))->setValue($subject, $value);
	}

	/**
	 * Read one non-public state bucket.
	 *
	 * @param   object  $subject   Subject instance.
	 * @param   string  $property  Property name.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function property(object $subject, string $property): mixed
	{
		return (new ReflectionProperty($subject, $property))->getValue($subject);
	}
}
