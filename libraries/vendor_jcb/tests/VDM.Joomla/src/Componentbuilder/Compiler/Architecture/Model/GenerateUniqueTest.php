<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    30th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Model;


use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\AliasTitleFix;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\BatchCopy;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\UniqueFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseTables;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;
use VDM\Joomla\Utilities\GuidHelper;


/**
 * Execute actual copy helpers and callers with observable persistence boundaries.
 *
 * The table double bounds failed regressions, so an unbounded helper cannot hang CI.
 * Joomla 5 shares the Joomla 4 template family; the legacy family remains covered
 * because the shared caller emitter must remain compatible with every selector.
 *
 * @since  6.2.1
 */
#[CoversClass(AliasTitleFix::class)]
#[CoversClass(BatchCopy::class)]
#[CoversClass(UniqueFields::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class GenerateUniqueTest extends ArchitectureTestCase
{
	/**
	 * Every static helper receiving the shared compiler-generated copy callers.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.2.1
	 */
	public static function templates(): array
	{
		return [
			'J3 administrator' => ['joomla_3/AdminModel.php'],
			'J3 site edit' => ['joomla_3/AdminModel_site.php'],
			'J4/J5 administrator' => ['joomla_4/ADMIN_VIEW_MODEL.php'],
			'J4/J5 site edit' => ['joomla_4/SITE_ADMIN_VIEW_MODEL.php'],
			'J6 administrator' => ['joomla_6/ADMIN_VIEW_MODEL.php'],
			'J6 site edit' => ['joomla_6/SITE_ADMIN_VIEW_MODEL.php'],
		];
	}

	/**
	 * Numeric identifiers and non-text domains reject a collision after one query.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testNonTextCollisionsNeverApplyStringSuffixes(string $template): void
	{
		foreach (['INT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT', 'DECIMAL', 'FLOAT',
			'DOUBLE', 'DATE', 'DATETIME', 'BINARY', 'ENUM', 'TEXT', 'UNKNOWN'] as $type)
		{
			$world = $this->world($template, $this->settings($type), static fn (): array => ['id' => 7]);

			$this->assertFalse($world->generateUnique('code', 3881), $type);
			$this->assertSame([['code' => 3881]], $world->queries, $type);
			$this->assertSame('COM_DEMO_UNIQUE_FIELD_CONFLICT:code', $world->error, $type);
		}
	}

	/**
	 * Available values retain their types, including both zero representations.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testAvailableValuesAndNullKeepTheirSemantics(string $template): void
	{
		foreach ([['INT', 3882], ['BIGINT', '9223372036854775807'], ['INT', 0],
			['INT', '0'], ['VARCHAR', '0'], ['VARCHAR', '']] as [$type, $value])
		{
			$world = $this->world($template, $this->settings($type), static fn () => null);

			$this->assertSame($value, $world->generateUnique('code', $value));
			$this->assertSame([['code' => $value]], $world->queries);
			$this->assertNull($world->error);
		}

		$nullable = $this->world($template, $this->settings('INT', 20, true), static fn (): array => ['id' => 1]);
		$this->assertNull($nullable->generateUnique('code', null));
		$this->assertSame([], $nullable->queries, 'SQL NULL is not queried as an empty string.');

		$required = $this->world($template, $this->settings('INT'), static fn () => null);
		$this->assertFalse($required->generateUnique('code', null));
		$this->assertSame([], $required->queries);
		$this->assertSame('COM_DEMO_UNIQUE_FIELD_ERROR:code', $required->error);
	}

	/**
	 * Text remains text even when its content looks like a number.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testTextUsesTheRealJoomlaIncrementer(string $template): void
	{
		foreach (['sample', '3881', '0', ''] as $value)
		{
			$world = $this->world($template, $this->settings('VARCHAR'),
				static fn (array $keys, int $attempt): ?array => $attempt === 1 ? ['id' => 1] : null);

			$expected = $value === '' ? ' (2)' : $value . ' (2)';
			$this->assertSame($expected, $world->generateUnique('code', $value));
			$this->assertSame([['code' => $value], ['code' => $expected]], $world->queries);
		}

		$world = $this->world($template, $this->settings('VARCHAR'),
			static fn (array $keys, int $attempt): ?array => $attempt < 3 ? ['id' => $attempt] : null);
		$this->assertSame('sample (4)', $world->generateUnique('code', 'sample (2)'));
		$this->assertCount(3, $world->queries);
	}

	/**
	 * Column bounds count characters and reject an unstorable suffix before lookup.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testCharacterBoundsPreventTruncationAndAllowUnicode(string $template): void
	{
		foreach (['CHAR', 'VARCHAR'] as $type)
		{
			$world = $this->world($template, $this->settings($type, 6),
				static fn (array $keys, int $attempt): ?array => $attempt === 1 ? ['id' => 1] : null);
			$this->assertSame('éé (2)', $world->generateUnique('code', 'éé'));
			$this->assertCount(2, $world->queries);

			$full = $this->world($template, $this->settings($type, 6), static fn (): array => ['id' => 1]);
			$this->assertFalse($full->generateUnique('code', 'sample'));
			$this->assertCount(1, $full->queries);
			$this->assertSame('COM_DEMO_UNIQUE_FIELD_LENGTH:code', $full->error);

			$oversize = $this->world($template, $this->settings($type, 3), static fn () => null);
			$this->assertFalse($oversize->generateUnique('code', 'sample'));
			$this->assertSame([], $oversize->queries);
		}
	}

	/**
	 * Repeated DB-equivalent rows terminate promptly, and distinct rows hit a hard cap.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testLookupAttemptsAreStrictlyBounded(string $template): void
	{
		$coercion = $this->world($template, $this->settings('VARCHAR'), static fn (): array => ['id' => 3881]);
		$this->assertFalse($coercion->generateUnique('code', '3881'));
		$this->assertCount(2, $coercion->queries);
		$this->assertSame('COM_DEMO_UNIQUE_FIELD_CONFLICT:code', $coercion->error);

		$occupied = $this->world($template, $this->settings('VARCHAR'),
			static fn (array $keys, int $attempt): array => ['id' => $attempt]);
		$this->assertFalse($occupied->generateUnique('code', 'sample'));
		$this->assertCount(100, $occupied->queries);
		$this->assertSame('COM_DEMO_UNIQUE_FIELD_LIMIT:code', $occupied->error);

		$last = $this->world($template, $this->settings('VARCHAR'),
			static fn (array $keys, int $attempt): ?array => $attempt < 100 ? ['id' => $attempt] : null);
		$this->assertSame('sample (100)', $last->generateUnique('code', 'sample'));
		$this->assertCount(100, $last->queries);
	}

	/**
	 * Exceptions and error-bearing false loads never authorize a copy candidate.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testDatabaseFailuresAreNotAvailability(string $template): void
	{
		$throwing = $this->world($template, $this->settings('INT'), static function (): never
		{
			throw new RuntimeException('Database connection failed.');
		});
		$this->assertFalse($throwing->generateUnique('code', 3881));
		$this->assertSame('COM_DEMO_UNIQUE_FIELD_ERROR:code', $throwing->error);

		$failed = $this->world($template, $this->settings('VARCHAR'), static fn () => null);
		$failed->tableError = 'The table could not bind the row.';
		$this->assertFalse($failed->generateUnique('code', 'sample'));
		$this->assertSame('COM_DEMO_UNIQUE_FIELD_ERROR:code', $failed->error);

		$later = $this->world($template, $this->settings('VARCHAR'), static fn () => null);
		$later->lookup = static function (array $keys, int $attempt) use ($later): ?array
		{
			if ($attempt === 1)
			{
				return ['id' => 1];
			}

			$later->tableError = 'Query failed after the first collision.';
			return null;
		};
		$this->assertFalse($later->generateUnique('code', 'sample'));
		$this->assertSame('COM_DEMO_UNIQUE_FIELD_ERROR:code', $later->error);
		$this->assertCount(2, $later->queries);
	}

	/**
	 * GUID collisions use the established UUID generator, never a text suffix.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testGuidCollisionsProduceAnotherValidGuid(string $template): void
	{
		$original = '00112233-4455-4677-8899-aabbccddeeff';
		$settings = ['guid' => ['type' => 'VARCHAR', 'length' => 36, 'nullable' => false]];
		$world = $this->world($template, $settings,
			static fn (array $keys): ?array => $keys['guid'] === $original ? ['id' => 1] : null);
		$result = $world->generateUnique('guid', $original);

		$this->assertIsString($result);
		$this->assertTrue(GuidHelper::valid($result));
		$this->assertNotSame($original, $result);
		$this->assertCount(2, $world->queries);
	}

	/**
	 * Missing metadata and invalid input fail before any database operation.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testUnsafeInputCannotBecomeAnAvailableCandidate(string $template): void
	{
		foreach ([false, [], (object) []] as $value)
		{
			$world = $this->world($template, $this->settings('VARCHAR'), static fn () => null);
			$this->assertFalse($world->generateUnique('code', $value));
			$this->assertSame([], $world->queries);
		}

		$unknown = $this->world($template, [], static fn () => null);
		$this->assertFalse($unknown->generateUnique('missing', 'sample'));
		$this->assertSame([], $unknown->queries);

		$unboundedLength = $this->world($template, $this->settings('VARCHAR', null), static fn (): array => ['id' => 1]);
		$this->assertFalse($unboundedLength->generateUnique('code', 'sample'));
		$this->assertCount(1, $unboundedLength->queries);
		$this->assertSame('COM_DEMO_UNIQUE_FIELD_CONFLICT:code', $unboundedLength->error);
	}

	/**
	 * Save as Copy returns failure before persistence and preserves falsey values.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testSaveCallerStopsBeforePersistingConflictingNumericFields(string $template): void
	{
		$settings = ['name' => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false]]
			+ $this->settings('INT');
		$world = $this->world($template, $settings,
			static fn (array $keys): ?array => isset($keys['code']) ? ['id' => 1] : null);
		$save = $this->saveCaller($world);
		$data = ['id' => 0, 'name' => 'sample', 'code' => 3881];

		$this->assertFalse($save($data));
		$this->assertSame([], $world->stored);
		$this->assertSame(3881, $data['code'], 'A false error sentinel must never be assigned to the field.');
		$this->assertCount(2, $world->queries);

		foreach ([3882, 0, '0', null] as $value)
		{
			$available = $this->world($template, $this->settings('INT', 20, true), static fn () => null);
			$save = $this->saveCaller($available);
			$data = ['id' => 0, 'code' => $value];
			$this->assertTrue($save($data));
			$this->assertSame([$data], $available->stored);
			$this->assertSame($value, $available->stored[0]['code']);
		}

		$none = $this->world($template, [], static fn () => null);
		$data = ['id' => 0, 'title' => 'Unchanged'];
		$this->assertTrue(($this->saveCaller($none))($data));
		$this->assertSame([], $none->queries);
		$this->assertSame([$data], $none->stored);

		$omitted = $this->world($template, $this->settings('INT'), static fn (): array => ['id' => 1]);
		$data = ['id' => 0, 'title' => 'No unique value submitted'];
		$this->assertTrue(($this->saveCaller($omitted))($data));
		$this->assertSame([], $omitted->queries);
		$this->assertSame([$data], $omitted->stored);
	}

	/**
	 * The complete generated batch method rejects conflicts before Table::store.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testBatchCallerStopsBeforeStoreAndKeepsStringCopiesWorking(string $template): void
	{
		$world = $this->world($template, $this->settings('INT'), static fn (): array => ['id' => 7]);
		$world->sources = [
			7 => ['id' => 7, 'code' => 3881, 'published' => 1],
			8 => ['id' => 8, 'code' => 3882, 'published' => 1],
		];
		$batch = $this->batchCaller($world);
		$this->assertFalse($batch([], [7, 8], [7 => 'com_demo.article.7', 8 => 'com_demo.article.8']));
		$this->assertSame([], $world->stored);
		$this->assertSame(3881, $world->table->code);
		$this->assertSame(7, $world->table->id, 'Failure precedes resetting the copy primary key.');
		$this->assertCount(1, $world->queries, 'Later batch rows are not processed after a fatal conflict.');

		$world = $this->world($template, $this->settings('VARCHAR'),
			static fn (array $keys): ?array => $keys['code'] === '3881' ? ['id' => 7] : null);
		$world->sources = [7 => ['id' => 7, 'code' => '3881', 'published' => 1]];
		$batch = $this->batchCaller($world);
		$this->assertSame([7 => 100], $batch([], [7], [7 => 'com_demo.article.7']));
		$this->assertSame('3881 (2)', $world->stored[0]['code']);
		$this->assertSame(0, $world->stored[0]['published']);

		$none = $this->world($template, [], static fn () => null);
		$none->sources = [7 => ['id' => 7, 'code' => 3881, 'published' => 1]];
		$this->assertSame([7 => 100], ($this->batchCaller($none))([], [7], [7 => 'com_demo.article.7']));
		$this->assertSame([], $none->queries);
		$this->assertSame(3881, $none->stored[0]['code']);
	}

	/**
	 * Multiple unique columns and falsey values survive the full batch lifecycle.
	 *
	 * @param   string  $template  Actual compiler template path.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('templates')]
	public function testBatchMultipleFieldsAndFalseyValues(string $template): void
	{
		$settings = ['name' => ['type' => 'VARCHAR', 'length' => 50, 'nullable' => false]]
			+ $this->settings('INT');
		$world = $this->world($template, $settings,
			static fn (array $keys): ?array => isset($keys['code']) ? ['id' => 7] : null);
		$world->sources = [7 => ['id' => 7, 'name' => 'sample', 'code' => 3881, 'published' => 1]];
		$this->assertFalse(($this->batchCaller($world))([], [7], [7 => 'com_demo.article.7']));
		$this->assertSame([], $world->stored);
		$this->assertSame(3881, $world->table->code);
		$this->assertCount(2, $world->queries);

		foreach ([0, '0', null] as $value)
		{
			$available = $this->world($template, $this->settings('INT', 20, true), static fn () => null);
			$available->sources = [7 => ['id' => 7, 'code' => $value, 'published' => 1]];
			$this->assertSame([7 => 100], ($this->batchCaller($available))([], [7], [7 => 'com_demo.article.7']));
			$this->assertSame($value, $available->stored[0]['code']);
		}

		$changed = $this->world($template, $this->settings('INT'),
			static fn (array $keys): ?array => $keys['code'] === 3881 ? ['id' => 7] : null);
		$changed->sources = [7 => ['id' => 7, 'code' => 3881, 'published' => 1]];
		$this->assertSame([7 => 100], ($this->batchCaller($changed))(['code' => 3882], [7], [7 => 'com_demo.article.7']));
		$this->assertSame(3882, $changed->stored[0]['code']);
	}

	/**
	 * Build one field's compiler settings without runtime schema discovery.
	 *
	 * @param   string    $type      SQL datatype.
	 * @param   int|null  $length    Column character limit.
	 * @param   bool      $nullable  Whether SQL NULL is permitted.
	 *
	 * @return  array
	 * @since   6.2.1
	 */
	private function settings(string $type, ?int $length = 100, bool $nullable = false): array
	{
		return ['code' => ['type' => $type, 'length' => $length, 'nullable' => $nullable]];
	}

	/**
	 * Bind the actual helper body; only external language and Power symbols change.
	 *
	 * @param   string   $template  Actual compiler template path.
	 * @param   array    $settings  Emitted field settings.
	 * @param   Closure  $lookup    Table lookup boundary.
	 *
	 * @return  GeneratedUniqueModelFixture
	 * @since   6.2.1
	 */
	private function world(string $template, array $settings, Closure $lookup): GeneratedUniqueModelFixture
	{
		$source = file_get_contents(JCB_PROJECT_ROOT . '/admin/compiler/' . $template);
		$this->assertSame(1, preg_match('/protected function generateUnique\(\$field, \$value\)\s*\{(.*?)\n\t\}/s', $source, $matches));
		$body = $this->resolvePowers($matches[1]);
		$this->assertStringNotContainsString('___Power', $body);
		$world = new GeneratedUniqueModelFixture($settings, $lookup);
		$helper = eval('return function ($field, $value) {' . $body . '};');
		$world->helper = $helper->bindTo($world, GeneratedUniqueModelFixture::class);

		if (isset($settings['guid']))
		{
			$tables = new DatabaseTables();
			$tables->set('article.guid', ['type' => $settings['guid']['type']]);
			$view = 'article';
			$code = $this->renderer(UniqueFields::class, ['databasetables' => $tables])->get($view);
			$this->assertSame(1, preg_match('/protected function generateUniqueGuid\(\)\s*\{(.*?)\n\t\}/s', $code, $guidMatches));
			$guidBody = $this->resolvePowers($guidMatches[1]);
			$this->assertStringNotContainsString('___Power', $guidBody);
			$world->guidHelper = eval('return static function () {' . $guidBody . '};');
		}

		return $world;
	}

	/**
	 * Execute the emitted save fix before the parent-save persistence boundary.
	 *
	 * @param   GeneratedUniqueModelFixture  $world  Isolated copy operation.
	 *
	 * @return  Closure
	 * @since   6.2.1
	 */
	private function saveCaller(GeneratedUniqueModelFixture $world): Closure
	{
		$code = $this->resolvePowers($this->renderer(AliasTitleFix::class)->get('article'));
		$run = eval('return function (array &$data) {'
			. '$input = new \\Joomla\\Input\\Input(["task" => "save2copy"]);'
			. $code . '$this->stored[] = $data; return true; };');

		return $run->bindTo($world, GeneratedUniqueModelFixture::class);
	}

	/**
	 * Execute the complete batch method with real load/check/store ordering.
	 *
	 * @param   GeneratedUniqueModelFixture  $world  Isolated copy operation.
	 *
	 * @return  Closure
	 * @since   6.2.1
	 */
	private function batchCaller(GeneratedUniqueModelFixture $world): Closure
	{
		$code = $this->renderer(BatchCopy::class)->get('article');
		$this->assertSame(1, preg_match('/protected function batchCopy\(\$values, \$pks, \$contexts\)\s*\{(.*?)\n\t\}/s', $code, $matches));
		$body = $this->resolvePowers($matches[1]);
		$run = eval('return function ($values, $pks, $contexts) {' . $body . '};');

		return $run->bindTo($world, GeneratedUniqueModelFixture::class);
	}

	/**
	 * Resolve generated symbols to real helpers and the isolated error formatter.
	 *
	 * @param   string  $code  Generated PHP source.
	 *
	 * @return  string
	 * @since   6.2.1
	 */
	private function resolvePowers(string $code): string
	{
		return strtr($code, [
			'COM_###COMPONENT###_' => 'COM_DEMO_',
			'Text::sprintf(' => '$this->translate(',
			'StringHelper::' => '\\Joomla\\String\\StringHelper::',
			'Joomla___46ba6c12_361e_4f7d_b6d9_70450e7cd7c2___Power::' => '\\Joomla\\String\\StringHelper::',
			'Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::sprintf(' => '$this->translate(',
			'Super___9c513baf_b279_43fd_ae29_a585c8cbc4f0___Power::' => '\\VDM\\Joomla\\Utilities\\GuidHelper::',
			'Super___0a59c65c_9daf_4bc9_baf4_e063ff9e6a8a___Power::' => '\\VDM\\Joomla\\Utilities\\ArrayHelper::',
		]);
	}
}
