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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Closure;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\RecordKeyFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Alias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\BaseSixFour;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueGuid;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueKeys;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonItem;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonString;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelBasicField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelExpertField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelExpertFieldInitiator;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionFields;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;


/**
 * Generated edit model save contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedModelItemSaveTest extends ArchitectureTestCase
{
	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.7
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree', 3],
			'Joomla 4' => ['JoomlaFour', 4],
			'Joomla 5' => ['JoomlaFive', 5],
			'Joomla 6' => ['JoomlaSix', 6],
		];
	}

	/**
	 * The record keys a view with nothing else to save still resolves.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const RECORD_KEYS = <<<'GEN'


		// The record keys, as every line below expects them: the primary key as an
		// integer that is never taken from the request (null from the API on create).
		$data['id'] = (int) ($data['id'] ?? 0);
GEN;

	/**
	 * A view with nothing to save still resolves its record keys, the same
	 * way on every target, because Joomla's API hands the model a null key.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewWithNothingToSaveStillResolvesItsRecordKeys(string $version, int $major): void
	{
		$this->assertStringStartsWith(self::RECORD_KEYS, $this->save($version));
		$this->assertStringContainsString('$jcbPatchStored = [];', $this->save($version));
	}

	/**
	 * The record keys open the save method, ahead of every modelling block,
	 * so custom code and field scripts never read an undefined key.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheRecordKeysOpenTheSaveMethod(string $version, int $major): void
	{
		$guid = new DatabaseUniqueGuid();
		$guid->set('article', true);

		$code = $this->save($version, $this->jsonOnly() + [
			'recordkeyfix' => new RecordKeyFix(
				$this->config(), $guid, new DatabaseUniqueKeys(), new Alias()
			),
		]);

		$this->assertStringStartsWith(self::RECORD_KEYS, $code);
		$this->assertLessThan(
			strpos($code, '// Set the params items to data.'),
			strpos($code, "Super___9c513baf_b279_43fd_ae29_a585c8cbc4f0___Power::valid(\$data['guid'], 'article', \$data['id'], 'demo')"),
			'the guid is settled before the json items are modelled'
		);
	}

	/**
	 * A guarded json item reaches the current user its target's way.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheGuardedItemUserLookupFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->save($version, $this->guarded());

		if ($major === 3)
		{
			$this->assertStringContainsString(
				"___Power::getUser()->authorise('article.edit.params', 'com_demo')",
				$code
			);
			$this->assertStringNotContainsString('getIdentity()', $code);

			return;
		}

		$this->assertStringContainsString(
			"___Power::getApplication()->getIdentity()->authorise("
			. "'article.edit.params', 'com_demo')",
			$code
		);
		$this->assertStringNotContainsString('___Power::getUser()->authorise', $code);
	}

	/**
	 * A json item is folded back into a string before it is stored.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJsonItemIsFoldedBackIntoAString(): void
	{
		$code = $this->save('JoomlaSix', $this->jsonOnly());

		$this->assertStringContainsString('// Set the params items to data.', $code);
		$this->assertStringContainsString(
			"if (isset(\$data['params']) && is_array(\$data['params']) && !array_key_exists('params', \$jcbPatchStored))", $code
		);
		$this->assertStringContainsString('$params = new Registry;', $code);
		$this->assertStringContainsString("\$data['params'] = (string) \$params;", $code);
	}

	/**
	 * Every cryption type is looked up in its own field registry.
	 *
	 * Config::getCryptiontypes() is a hardcoded list of four, and a view
	 * with no field of a given type simply has no entry in that registry,
	 * which is how an unencrypted view is represented.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachCryptionTypeUsesItsOwnFieldRegistry(): void
	{
		$this->assertSame(
			['basic', 'medium', 'whmcs', 'expert'],
			$this->config()->cryption_types
		);

		// only the basic registry carries a field, so only basic is emitted
		$basic = new ModelBasicField();
		$basic->set('article', ['secret']);

		$code = $this->save('JoomlaSix', ['modelbasicfield' => $basic]);

		$this->assertStringContainsString('$basickey = ', $code);
		$this->assertStringNotContainsString('$mediumkey = ', $code);
		$this->assertStringNotContainsString('$whmcskey = ', $code);
	}

	/**
	 * A view with no encrypted field emits no encryption at all.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutEncryptedFieldsEmitsNoEncryption(): void
	{
		$code = $this->save('JoomlaSix', $this->jsonOnly());

		foreach (['basic', 'medium', 'whmcs', 'expert'] as $type)
		{
			$this->assertStringNotContainsString('$' . $type . 'key = ', $code);
		}
	}

	/**
	 * Unchanged omitted fields retain their exact stored bytes on every target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('versions')]
	public function testPatchPreservesOmittedStorageRepresentations(string $version, int $major): void
	{
		$this->config()->set('joomla_version', $major);
		$stored = [
			'id' => 7,
			'name' => 'Before',
			'code' => base64_encode('source'),
			'json_zero' => '0',
			'json_null' => 'null',
			'json_empty' => '[]',
			'params' => '{ "enabled" : true }',
			'secret' => 'unchanged-ciphertext',
			'expert' => 'expert-storage',
			'nullable' => null,
		];
		$input = [
			'id' => 7,
			'name' => 'After',
			'code' => 'source',
			'json_zero' => '0',
			'json_null' => null,
			'json_empty' => [],
			'params' => ['enabled' => true],
			'secret' => 'plaintext',
			'expert' => 'expert-input',
			'nullable' => null,
		];
		$fixture = new GeneratedSaveModelFixture($stored, ['name' => 'After']);
		$save = $this->executableSave($fixture, $version, $this->storageFields());

		$this->assertSame(array_replace($stored, ['name' => 'After']), $save($input));
		$this->assertSame(1, $fixture->loads);
		$this->assertSame(0, $fixture->encryptions);
		$this->assertSame(0, $fixture->expertInitializers);
		$this->assertSame(0, $fixture->expertConversions);
	}

	/**
	 * Explicit null, empty arrays and empty strings remain deliberate input.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testPatchPreservesExplicitReplacementsAndClearing(): void
	{
		$submitted = [
			'code' => '',
			'json_zero' => 0,
			'json_null' => null,
			'json_empty' => [],
			'params' => [],
			'secret' => 'replacement',
			'expert' => 'replacement',
		];
		$stored = ['id' => 7] + array_fill_keys(array_keys($submitted), 'old');
		$fixture = new GeneratedSaveModelFixture($stored, $submitted);
		$save = $this->executableSave($fixture, 'JoomlaSix', $this->storageFields());
		$saved = $save(['id' => 7] + $submitted);

		$this->assertSame('', $saved['code']);
		$this->assertSame('0', $saved['json_zero']);
		$this->assertNull($saved['json_null']);
		$this->assertSame('[]', $saved['json_empty']);
		$this->assertSame('{}', $saved['params']);
		$this->assertSame('encrypted:replacement', $saved['secret']);
		$this->assertSame('expert:replacement', $saved['expert']);
		$this->assertSame(1, $fixture->encryptions);
		$this->assertSame(1, $fixture->expertInitializers);
		$this->assertSame(1, $fixture->expertConversions);
	}

	/**
	 * Custom derived values still pass through encoding and after-save modelling.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCustomBeforeSaveDerivationsAreNotRestoredOver(): void
	{
		$before = <<<'PHP'
		$data['code'] = $data['name'] . '-generated';
		$data['json_empty']->nested->value = 'derived';
		$data['expert'] = $data['name'] . '-expert';
PHP;
		$after = <<<'PHP'
		$data['after'] = $data['code'];
PHP;
		$fixture = new GeneratedSaveModelFixture(
			['id' => 7, 'name' => 'Before', 'code' => base64_encode('source'), 'json_empty' => '{"nested":{"value":"original"}}', 'expert' => 'old'],
			['name' => 'After']
		);
		$save = $this->executableSave($fixture, 'JoomlaSix', $this->storageFields($before, $after));
		$saved = $save([
			'id' => 7,
			'name' => 'After',
			'code' => 'source',
			'json_empty' => (object) ['nested' => (object) ['value' => 'original']],
			'expert' => 'decoded',
		]);

		$this->assertSame(base64_encode('After-generated'), $saved['code']);
		$this->assertSame('{"nested":{"value":"derived"}}', $saved['json_empty']);
		$this->assertSame('expert:After-expert', $saved['expert']);
		$this->assertSame($saved['code'], $saved['after']);
		$this->assertSame(1, $fixture->expertConversions);
	}

	/**
	 * A supplied expert field may deliberately change another stored column.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testExpertDerivedColumnsAreNotRestoredOver(): void
	{
		$overrides = $this->storageFields();
		$overrides['modelexpertfield']->set('article', ['expert' => ['save' => [
			"\$data['code'] = base64_encode('expert-generated');",
			"[[[field]]] = 'expert:' . [[[field]]];",
		]]]);
		$fixture = new GeneratedSaveModelFixture(
			['id' => 7, 'code' => base64_encode('source'), 'expert' => 'old'],
			['expert' => 'replacement']
		);
		$save = $this->executableSave($fixture, 'JoomlaSix', $overrides);
		$saved = $save(['id' => 7, 'code' => 'source', 'expert' => 'replacement']);

		$this->assertSame(base64_encode('expert-generated'), $saved['code']);
		$this->assertSame('expert:replacement', $saved['expert']);
	}

	/**
	 * An empty expert registry cannot generate an invalid conditional expression.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testEmptyExpertRegistryRetainsItsInitializer(): void
	{
		$overrides = $this->storageFields();
		$overrides['modelexpertfield']->set('article', []);
		$fixture = new GeneratedSaveModelFixture(['id' => 7], ['name' => 'After']);
		$save = $this->executableSave($fixture, 'JoomlaSix', $overrides);
		$saved = $save(['id' => 7, 'name' => 'After']);

		$this->assertSame('After', $saved['name']);
		$this->assertSame(1, $fixture->expertInitializers);
		$this->assertSame(0, $fixture->expertConversions);
	}

	/**
	 * A custom save-as-copy change must encode the full new record normally.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testSaveAsCopyDisablesRawRestoration(): void
	{
		$fixture = new GeneratedSaveModelFixture(
			['id' => 7, 'name' => 'Before', 'code' => 'legacy-storage'],
			['name' => 'Copy']
		);
		$save = $this->executableSave($fixture, 'JoomlaSix', $this->storageFields("\t\t\$data['id'] = 0;"));
		$saved = $save(['id' => 7, 'name' => 'Copy', 'code' => 'decoded']);

		$this->assertSame(0, $saved['id']);
		$this->assertSame(base64_encode('decoded'), $saved['code']);
	}

	/**
	 * ACL-filtered columns stay absent and record-key normalization stays authoritative.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testPatchDoesNotReintroduceFilteredFieldsOrOldGuids(): void
	{
		$fixture = new GeneratedSaveModelFixture(
			['id' => 7, 'guid' => 'old-guid', 'name' => 'Before', 'params' => '{"protected":true}'],
			['name' => 'After']
		);
		$save = $this->executableSave($fixture, 'JoomlaSix', $this->storageFields());
		$saved = $save(['id' => 7, 'guid' => 'server-guid', 'name' => 'After']);

		$this->assertArrayNotHasKey('params', $saved);
		$this->assertSame('server-guid', $saved['guid']);
	}

	/**
	 * An unavailable stored row must fail instead of writing uncertain inherited data.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testPatchFailsClosedWhenStoredRowCannotBeLoaded(): void
	{
		$fixture = new GeneratedSaveModelFixture(['id' => 7], ['name' => 'After']);
		$fixture->loadSucceeds = false;
		$save = $this->executableSave($fixture, 'JoomlaSix');

		$this->assertFalse($save(['id' => 7, 'name' => 'After']));
		$this->assertSame('JLIB_APPLICATION_ERROR_RECORD_LOAD', $fixture->error);
	}

	/**
	 * Full form saves and creates retain their original transformation behavior.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNonPatchAndAdministratorSavesDoNotReadStoredData(): void
	{
		foreach ([['POST', true, 0], ['POST', true, 7], ['PATCH', false, 7]] as [$method, $api, $id])
		{
			$fixture = new GeneratedSaveModelFixture(['id' => 7, 'code' => 'old'], [], $method);
			$fixture->api = $api;
			$save = $this->executableSave($fixture, 'JoomlaSix', $this->storageFields());
			$saved = $save(['id' => $id, 'code' => 'source']);

			$this->assertSame(base64_encode('source'), $saved['code']);
			$this->assertSame(0, $fixture->loads);
			$this->assertSame('', $saved['params']);
		}
	}

	/**
	 * Execute generated PHP while replacing only request, storage and crypto boundaries.
	 *
	 * @param   GeneratedSaveModelFixture  $fixture    External runtime boundaries.
	 * @param   string                     $version    Target namespace segment.
	 * @param   array                      $overrides  Compiler field/custom-code dependencies.
	 *
	 * @return  Closure
	 * @since   6.2.0
	 */
	private function executableSave(GeneratedSaveModelFixture $fixture, string $version, array $overrides = []): Closure
	{
		$code = strtr($this->save($version, $overrides), [
			'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()' => '$this->getApplication()',
			"DemoHelper::getCryptKey('basic')" => "'fixture-key'",
			'new Super___99175f6d_dba8_4086_8a65_5c4ec175e61d___Power($basickey)' => '$this',
			'new Registry' => 'new \\Joomla\\Registry\\Registry',
			'Text::_(' => '$this->translate(',
		]);
		$save = eval('return function (array $data) { $input = $this->input;' . $code . '; return $data; };');

		return $save->bindTo($fixture, GeneratedSaveModelFixture::class);
	}

	/**
	 * Provide generated storage families and deterministic custom derivations.
	 *
	 * @param   string  $before  Custom code before storage transformations.
	 * @param   string  $after   Custom code after storage transformations.
	 *
	 * @return  array
	 * @since   6.2.0
	 */
	private function storageFields(string $before = '', string $after = ''): array
	{
		$content = new ContentOne();
		$content->set('Component', 'Demo');
		$base = new BaseSixFour();
		$base->set('article', ['code']);
		$json = new JsonString();
		$json->set('article', ['json_zero', 'json_null', 'json_empty']);
		$basic = new ModelBasicField();
		$basic->set('article', ['secret']);
		$expert = new ModelExpertField();
		$expert->set('article', ['expert' => ['save' => [
			'if (isset([[[field]]]))',
			'{',
			"\t\$this->expertConversions++;",
			"\t[[[field]]] = 'expert:' . [[[field]]];",
			'}',
		]]]);
		$initiator = new ModelExpertFieldInitiator();
		$initiator->set('article.save', [['$this->expertInitializers++;']]);
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->method('get')->willReturnCallback(
			static fn (string $first, string $second, string $prefix = ''): string => $prefix
				. ($first === 'php_before_save' ? $before : $after)
		);

		return $this->jsonOnly() + [
			'contentone' => $content,
			'basesixfour' => $base,
			'jsonstring' => $json,
			'modelbasicfield' => $basic,
			'modelexpertfield' => $expert,
			'modelexpertfieldinitiator' => $initiator,
			'dispenser' => $dispenser,
		];
	}

	/**
	 * Build the save method of one target.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $overrides  Constructor dependency overrides.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function save(string $version, array $overrides = []): string
	{
		// only Joomla 3 reaches the current user through the global factory
		$class = $this->targetClass($version, 'Model\\ItemSave', ['JoomlaThree']);

		$subject = $this->renderer($class, $overrides);

		$view = 'article';
		$out = $subject->get($view);

		if (getenv('DUMP_SAVE'))
		{
			file_put_contents(getenv('DUMP_SAVE'), $out);
		}

		return $out;
	}

	/**
	 * Dependencies for a view carrying one guarded json item.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function guarded(): array
	{
		$permissionfields = new PermissionFields();
		$permissionfields->set('article', ['params' => ['edit' => 'json']]);

		return $this->jsonOnly() + ['permissionfields' => $permissionfields];
	}

	/**
	 * Dependencies for a view carrying one json item and nothing else.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function jsonOnly(): array
	{
		$jsonitem = new JsonItem();
		$jsonitem->set('article', ['params']);

		return ['jsonitem' => $jsonitem];
	}
}
