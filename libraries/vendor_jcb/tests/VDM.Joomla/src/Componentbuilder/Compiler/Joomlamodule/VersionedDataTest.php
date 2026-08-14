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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Joomlamodule;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaFive\Data as JoomlaFiveData;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaFour\Data as JoomlaFourData;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaSix\Data as JoomlaSixData;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaThree\Data as JoomlaThreeData;
use VDM\Joomla\Componentbuilder\Package\Builder\Get as Superpower;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Tests\Support\TestCase;


/**
 * Joomla 3-6 module data cache, validation, query-failure, and retry contracts.
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
	 * Resolve the same cached module by ID and GUID without persistence access.
	 *
	 * @param   class-string  $class  Versioned data class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testGetAndExistsResolveCachedIdentityAliases(string $class): void
	{
		$subject = $this->subject($class);
		$guid = '11111111-1111-4111-8111-111111111111';
		$module = (object) ['id' => 7, 'guid' => $guid, 'name' => 'Cached'];
		$this->setProperty($subject, 'data', [7 => $module]);
		$this->setProperty($subject, 'index', [7 => 7, $guid => 7]);

		$this->assertTrue($subject->exists());
		$this->assertTrue($subject->exists(7));
		$this->assertTrue($subject->exists($guid));
		$this->assertSame($module, $subject->get(7));
		$this->assertSame($module, $subject->get($guid));
		$this->assertSame([7 => $module], $subject->get());
	}

	/**
	 * Reject malformed identifiers before constructing a database query.
	 *
	 * @param   class-string  $class  Versioned data class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testSetRejectsMalformedIdentifierWithoutCollaborators(string $class): void
	{
		$subject = $this->subject($class);

		$this->assertFalse($subject->set('not a module identifier'));
		$this->assertFalse($subject->exists('not a module identifier'));
		$this->assertNull($subject->get('not a module identifier'));
		$this->assertFalse($subject->exists());
		$this->assertNull($subject->get());
	}

	/**
	 * Return false for a numeric database miss and leave all cache buckets empty.
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
	 * Attempt one remote synchronization per missing GUID while allowing database rechecks.
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
		$guid = '22222222-2222-4222-8222-222222222222';
		$superpower = $this->getMockBuilder(Superpower::class)
			->disableOriginalConstructor()
			->onlyMethods(['get'])
			->getMock();
		$superpower->expects($this->once())->method('get')
			->with('joomla_module', [$guid])->willReturn(['added' => []]);
		$subject = $this->subject($class);
		$this->setProperty($subject, 'db', $db);
		$this->setProperty($subject, 'superpower', $superpower);

		$this->assertFalse($subject->set($guid));
		$this->assertFalse($subject->set($guid));
		$this->assertSame([$guid => true], $this->property($subject, 'retry'));
	}

	/**
	 * Provide every supported Joomla module data implementation.
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
	 * Replace a non-public dependency or cache bucket.
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
	 * Read a non-public cache bucket.
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
