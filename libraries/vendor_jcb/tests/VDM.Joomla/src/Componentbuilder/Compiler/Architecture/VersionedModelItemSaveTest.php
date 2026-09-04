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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\RecordKeyFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Alias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueGuid;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueKeys;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonItem;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelBasicField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionFields;


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
		$this->assertSame(self::RECORD_KEYS, $this->save($version));
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
			"if (isset(\$data['params']) && is_array(\$data['params']))", $code
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
