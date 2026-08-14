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
use PHPUnit\Framework\Attributes\UsesTrait;
use ReflectionClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Abstraction\Registry\Traits\IsArray;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LanguageMessages;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Languages;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Multilingual;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Language\Insert;
use VDM\Joomla\Componentbuilder\Compiler\Language\Set;
use VDM\Joomla\Componentbuilder\Compiler\Language\Translation;
use VDM\Joomla\Componentbuilder\Compiler\Language\Update;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\MathHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Translation-threshold and language synchronization orchestration contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Set::class)]
#[CoversClass(Translation::class)]
#[UsesClass(Language::class)]
#[UsesClass(Config::class)]
#[UsesClass(Insert::class)]
#[UsesClass(Update::class)]
#[UsesClass(Languages::class)]
#[UsesClass(Multilingual::class)]
#[UsesClass(LanguageMessages::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(MathHelper::class)]
#[UsesClass(StringHelper::class)]
#[UsesTrait(IsArray::class)]
final class SetAndTranslationTest extends CompilerDomainTestCase
{
	/**
	 * Exclude a translation below the configured percentage and record its audit message.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTranslationExcludesIncompleteForeignLanguage(): void
	{
		$config = $this->compilerConfig([
			'lang_tag' => 'en-GB',
			'percentage_language_add' => 75,
			'debug_line_nr' => false
		]);
		$messages = new LanguageMessages();
		$subject = new Translation($config, $messages);
		$tag = 'af-ZA';
		$strings = ['EEN', 'TWEE'];
		$total = 4;
		$file = 'admin.ini';

		$this->assertFalse($subject->check($tag, $strings, $total, $file));
		$this->assertStringContainsString('only <b>2</b>', $messages->get('exclude.admin.ini'));
		$this->assertStringContainsString('= 50', $messages->get('exclude.admin.ini'));
		$this->assertNull($messages->get('include.admin.ini'));
	}

	/**
	 * Include complete or debug-enabled translations and leave the source language silent.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTranslationIncludesThresholdDebugAndSourceLanguageCases(): void
	{
		$config = $this->compilerConfig([
			'lang_tag' => 'en-GB',
			'percentage_language_add' => 90,
			'debug_line_nr' => false
		]);
		$messages = new LanguageMessages();
		$subject = new Translation($config, $messages);
		$total = 4;
		$file = 'site.ini';
		$tag = 'de-DE';
		$strings = ['A', 'B', 'C', 'D'];

		$this->assertTrue($subject->check($tag, $strings, $total, $file));
		$this->assertStringContainsString('and <b>4</b>', $messages->get('include.site.ini'));

		$config->set('debug_line_nr', true);
		$tag = 'fr-FR';
		$strings = ['A'];
		$file = 'debug.ini';
		$this->assertTrue($subject->check($tag, $strings, $total, $file));
		$this->assertNotNull($messages->get('include.debug.ini'));

		$tag = 'en-GB';
		$file = 'source.ini';
		$this->assertTrue($subject->check($tag, $strings, $total, $file));
		$this->assertNull($messages->get('include.source.ini'));
		$this->assertNull($messages->get('exclude.source.ini'));
	}

	/**
	 * Synchronize existing and new source strings, translations, links, and batch flushes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetSynchronizesExistingAndNewStringsAcrossRegistriesAndDatabase(): void
	{
		$updateQuery = $this->createMock(QueryInterface::class);
		$updateQuery->expects($this->once())->method('update')->willReturnSelf();
		$updateFields = [];
		$updateQuery->expects($this->once())->method('set')
			->willReturnCallback(static function (array $fields) use (&$updateFields, $updateQuery): QueryInterface
			{
				$updateFields = $fields;
				return $updateQuery;
			});
		$updateQuery->expects($this->once())->method('where')->with(["[id] = '5'"])->willReturnSelf();
		$insertQuery = $this->createMock(QueryInterface::class);
		$insertQuery->expects($this->once())->method('insert')->willReturnSelf();
		$insertQuery->expects($this->once())->method('columns')->willReturnSelf();
		$insertValues = '';
		$insertQuery->expects($this->once())->method('values')
			->willReturnCallback(static function (string $values) use (&$insertValues, $insertQuery): QueryInterface
			{
				$insertValues = $values;
				return $insertQuery;
			});
		$db = $this->database();
		$db->expects($this->exactly(2))->method('getQuery')->with(true)
			->willReturnOnConsecutiveCalls($updateQuery, $insertQuery);
		$db->expects($this->exactly(2))->method('setQuery');
		$db->expects($this->exactly(2))->method('execute');
		$config = $this->compilerConfig([
			'lang_tag' => 'en-GB',
			'remove_line_breaks' => false
		]);
		$language = new Language($config);
		$languages = new Languages();
		$languages->set('components.en-GB.admin.COM_EXAMPLE_EXISTING', 'Existing');
		$languages->set('components.en-GB.admin.COM_EXAMPLE_NEW', 'New');
		$multilingual = new Multilingual();
		$multilingual->set('components', [
			'Existing' => [
				'id' => 5,
				'translation' => '[{"language":"af-ZA","translation":"Bestaande"}]',
				'components' => '["old-guid"]'
			]
		]);
		$insert = new Insert($db);
		$update = $this->update($db, 13);
		$subject = new Set($config, $language, $multilingual, $languages, $insert, $update);

		$subject->execute(['Existing', 'New'], 'new-guid');

		$this->assertSame(
			'Bestaande',
			$languages->get('components.af-ZA.admin.COM_EXAMPLE_EXISTING')
		);
		$this->assertContains("[components] = '[\"old-guid\",\"new-guid\"]'", $updateFields);
		$this->assertContains("[published] = '1'", $updateFields);
		$this->assertContains("[modified_by] = '13'", $updateFields);
		$this->assertStringContainsString("'[\"new-guid\"]'", $insertValues);
		$this->assertStringContainsString("'New'", $insertValues);
		$this->assertSame([], $this->property($update, 'items'));
		$this->assertSame([], $this->property($insert, 'items')['components']);
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
	private function update(DatabaseInterface $db, int $userId): Update
	{
		$reflection = new ReflectionClass(Update::class);
		$subject = $reflection->newInstanceWithoutConstructor();
		$reflection->getProperty('db')->setValue($subject, $db);
		$reflection->getProperty('user')->setValue($subject, (object) ['id' => $userId]);

		return $subject;
	}

	/**
	 * Read non-public state for post-flush assertions.
	 *
	 * @param   object  $subject   Subject instance.
	 * @param   string  $property  Property name.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function property(object $subject, string $property): mixed
	{
		return (new \ReflectionProperty($subject, $property))->getValue($subject);
	}

	/**
	 * Build deterministic SQL quoting for both update and insert batches.
	 *
	 * @return  DatabaseInterface&\PHPUnit\Framework\MockObject\MockObject
	 * @since   6.1.6
	 */
	private function database(): DatabaseInterface
	{
		$db = $this->createMock(DatabaseInterface::class);
		$db->method('quoteName')->willReturnCallback(
			static fn (string|array $name): string|array => is_array($name)
				? array_map(static fn (string $value): string => '[' . $value . ']', $name)
				: '[' . $name . ']'
		);
		$db->method('quote')->willReturnCallback(
			static fn (mixed $value): string => "'" . (string) $value . "'"
		);

		return $db;
	}
}
