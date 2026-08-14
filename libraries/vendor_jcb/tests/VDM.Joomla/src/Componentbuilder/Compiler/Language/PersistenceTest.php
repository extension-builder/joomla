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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Language;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Language\Insert;
use VDM\Joomla\Componentbuilder\Compiler\Language\Multilingual;
use VDM\Joomla\Componentbuilder\Compiler\Language\Purge;
use VDM\Joomla\Componentbuilder\Compiler\Language\Update;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Tests\Support\TestCase;


/**
 * Language persistence batching, query, purge, and state-reset contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Insert::class)]
#[CoversClass(Multilingual::class)]
#[CoversClass(Purge::class)]
#[CoversClass(Update::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
final class PersistenceTest extends TestCase
{
	/**
	 * Quote seven columns, execute a complete insert batch, and clear it afterwards.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInsertBuildsCompleteBatchAndClearsExecutedTarget(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('insert')
			->with('[#__componentbuilder_language_translation]')->willReturnSelf();
		$query->expects($this->once())->method('columns')->with([
			'[components]', '[source]', '[published]', '[created]',
			'[created_by]', '[version]', '[access]'
		])->willReturnSelf();
		$query->expects($this->once())->method('values')
			->with("'[guid]','Source','1','2026-08-14','7','1','1'")->willReturnSelf();
		$db = $this->database();
		$db->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$db->expects($this->once())->method('setQuery')->with($query);
		$db->expects($this->once())->method('execute');
		$subject = new Insert($db);

		foreach (['[guid]', 'Source', '1', '2026-08-14', '7', '1', '1'] as $value)
		{
			$subject->set('components', 0, $value);
		}

		$subject->execute('components');
		$subject->execute('components');

		$this->assertSame([], $this->property($subject, 'items')['components']);
	}

	/**
	 * Reject malformed rows at the persistence boundary but discard stale batch state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInsertDoesNotExecuteRowsWithWrongColumnCount(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('insert')->willReturnSelf();
		$query->expects($this->once())->method('columns')->willReturnSelf();
		$query->expects($this->never())->method('values');
		$db = $this->database();
		$db->expects($this->once())->method('getQuery')->willReturn($query);
		$db->expects($this->never())->method('setQuery');
		$db->expects($this->never())->method('execute');
		$subject = new Insert($db);

		foreach (['guid', 'Source', '1', 'today', '7', '1'] as $value)
		{
			$subject->set('modules', 0, $value);
		}

		$subject->execute('modules');

		$this->assertSame([], $this->property($subject, 'items')['modules']);
	}

	/**
	 * Query requested sources and key the database result by source text.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMultilingualQueriesOnlyRequestedSources(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('from')
			->with('[#__componentbuilder_language_translation AS a]')->willReturnSelf();
		$query->expects($this->once())->method('select')->with([
			'[a.id]', '[a.translation]', '[a.source]', '[a.components]',
			'[a.modules]', '[a.plugins]', '[a.published]'
		])->willReturnSelf();
		$query->expects($this->once())->method('where')
			->with("[a.source] IN ('Alpha','Beta')")->willReturnSelf();
		$db = $this->database();
		$db->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$db->expects($this->once())->method('setQuery')->with($query);
		$db->expects($this->once())->method('execute');
		$db->expects($this->once())->method('getNumRows')->willReturn(2);
		$db->expects($this->once())->method('loadAssocList')->with('source')->willReturn([
			'Alpha' => ['id' => 1, 'source' => 'Alpha'],
			'Beta' => ['id' => 2, 'source' => 'Beta']
		]);

		$this->assertSame([
			'Alpha' => ['id' => 1, 'source' => 'Alpha'],
			'Beta' => ['id' => 2, 'source' => 'Beta']
		], (new Multilingual($db))->get(['Alpha', 'Beta']));
	}

	/**
	 * Avoid selecting or executing when no source strings are requested.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMultilingualEmptyInputStopsAfterBaseQueryCreation(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('from')->willReturnSelf();
		$query->expects($this->never())->method('select');
		$query->expects($this->never())->method('where');
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$db->expects($this->never())->method('setQuery');
		$db->expects($this->never())->method('execute');

		$this->assertNull((new Multilingual($db))->get([]));
	}

	/**
	 * Accumulate quoted update fields until threshold and clear them after execution.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdateBatchesQuotedFieldsAndHonorsThreshold(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('update')
			->with('[#__componentbuilder_language_translation]')->willReturnSelf();
		$query->expects($this->once())->method('set')->with([
			"[components] = '[\"one\",\"two\"]'",
			"[published] = '1'",
			"[modified] = '2026-08-14'",
			"[modified_by] = '42'"
		])->willReturnSelf();
		$query->expects($this->once())->method('where')->with(["[id] = '9'"])->willReturnSelf();
		$db = $this->database();
		$db->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$db->expects($this->once())->method('setQuery')->with($query);
		$db->expects($this->once())->method('execute');
		$subject = $this->update($db, 42);

		$subject->set(9, 'components', ['one', 'two'], 1, '2026-08-14', 0);
		$subject->execute(2);
		$this->assertCount(1, $this->property($subject, 'items'));

		$subject->execute();
		$this->assertSame([], $this->property($subject, 'items'));
	}

	/**
	 * Reject unknown extension target names before any persistence work.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPurgeRejectsUnknownTargetWithoutQuerying(): void
	{
		$db = $this->createMock(DatabaseInterface::class);
		$db->expects($this->never())->method('getQuery');

		(new Purge($this->update($this->createStub(DatabaseInterface::class)), $db))
			->execute(['Active'], 'guid', 'templates');
		$this->addToAssertionCount(1);
	}

	/**
	 * Keep an unlinked current target when another extension type still links it.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPurgeKeepsStringsLinkedToAnotherExtensionType(): void
	{
		$update = $this->update($this->createStub(DatabaseInterface::class));
		$subject = new Purge($update, $this->createStub(DatabaseInterface::class));
		$counter = 0;

		$this->handleUnlinked($subject, [
			'id' => 5,
			'translation' => '',
			'modules' => '["module-guid"]',
			'plugins' => ''
		], ['modules' => 'modules', 'plugins' => 'plugins'], 'components', [], 'today', $counter);

		$this->assertSame(0, $counter);
		$this->assertSame([], $this->property($update, 'items'));
	}

	/**
	 * Archive translated strings after their final extension link is removed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPurgeArchivesTranslatedStringsWithoutRemainingLinks(): void
	{
		$db = $this->databaseStub();
		$update = $this->update($db, 11);
		$subject = new Purge($update, $db);
		$counter = 0;

		$this->handleUnlinked($subject, [
			'id' => 6,
			'translation' => '[{"language":"af-ZA","translation":"Bron"}]',
			'modules' => '',
			'plugins' => ''
		], ['modules' => 'modules', 'plugins' => 'plugins'], 'components', [], 'today', $counter);

		$items = $this->property($update, 'items');
		$this->assertSame(1, $counter);
		$this->assertSame(6, $items[0]['id']);
		$this->assertContains("[published] = '2'", $items[0]['fields']);
		$this->assertContains("[components] = '[]'", $items[0]['fields']);
	}

	/**
	 * Delete an untranslated string that no extension still references.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPurgeDeletesUntranslatedStringsWithoutRemainingLinks(): void
	{
		$query = $this->createMock(QueryInterface::class);
		$query->expects($this->once())->method('delete')
			->with('[#__componentbuilder_language_translation]')->willReturnSelf();
		$query->expects($this->once())->method('where')->with('[id] = 8')->willReturnSelf();
		$db = $this->database();
		$db->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$db->expects($this->once())->method('setQuery')->with($query);
		$db->expects($this->once())->method('execute');
		$subject = new Purge($this->update($this->createStub(DatabaseInterface::class)), $db);
		$counter = 0;

		$this->handleUnlinked($subject, [
			'id' => 8,
			'translation' => '',
			'modules' => '',
			'plugins' => ''
		], ['modules' => 'modules', 'plugins' => 'plugins'], 'components', [], 'today', $counter);

		$this->assertSame(0, $counter);
	}

	/**
	 * Invoke the reviewed protected branch while preserving its reference counter.
	 *
	 * @param   Purge                 $subject      Purge subject.
	 * @param   array<string, mixed>  $item         Database row.
	 * @param   array<string, string> $otherTypes   Other link fields.
	 * @param   string                $target       Current link field.
	 * @param   array<int, string>    $targets      Remaining links.
	 * @param   string                $today        SQL date.
	 * @param   int                   $counter      Update counter.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function handleUnlinked(Purge $subject, array $item, array $otherTypes,
		string $target, array $targets, string $today, int &$counter): void
	{
		$method = new ReflectionMethod($subject, 'handleUnlinkedString');
		$method->invokeArgs($subject, [$item, $otherTypes, $target, $targets, $today, &$counter]);
	}

	/**
	 * Create an update batch without requiring Joomla application identity globals.
	 *
	 * @param   DatabaseInterface  $db      Database boundary.
	 * @param   int                $userId  Acting user ID.
	 *
	 * @return  Update
	 * @since   6.1.6
	 */
	private function update(DatabaseInterface $db, int $userId = 0): Update
	{
		$reflection = new ReflectionClass(Update::class);
		$subject = $reflection->newInstanceWithoutConstructor();
		$reflection->getProperty('db')->setValue($subject, $db);
		$reflection->getProperty('user')->setValue($subject, (object) ['id' => $userId]);

		return $subject;
	}

	/**
	 * Read non-public persistence state.
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

	/**
	 * Build deterministic quoting with expectation-ready database methods.
	 *
	 * @return  DatabaseInterface&\PHPUnit\Framework\MockObject\MockObject
	 * @since   6.1.6
	 */
	private function database(): DatabaseInterface
	{
		$db = $this->createMock(DatabaseInterface::class);
		$this->configureQuoting($db);

		return $db;
	}

	/**
	 * Build deterministic quoting without expectation requirements.
	 *
	 * @return  DatabaseInterface
	 * @since   6.1.6
	 */
	private function databaseStub(): DatabaseInterface
	{
		$db = $this->createStub(DatabaseInterface::class);
		$this->configureQuoting($db);

		return $db;
	}

	/**
	 * Configure identifier and value quoting used by language persistence.
	 *
	 * @param   DatabaseInterface  $db  Database boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function configureQuoting(DatabaseInterface $db): void
	{
		$db->method('quoteName')->willReturnCallback(
			static function (string|array $name, ?string $alias = null): string|array
			{
				if (is_array($name))
				{
					return array_map(static fn (string $value): string => '[' . $value . ']', $name);
				}

				return '[' . $name . ($alias !== null ? ' AS ' . $alias : '') . ']';
			}
		);
		$db->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);
	}
}
