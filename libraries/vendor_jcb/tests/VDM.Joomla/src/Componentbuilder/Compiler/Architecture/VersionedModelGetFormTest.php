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


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Language;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Input\Input;
use Joomla\Input\Json;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionFields;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Permission;


/**
 * Generated edit model getForm contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedModelGetFormTest extends ArchitectureTestCase
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
	 * Each target puts the current user in scope its own way.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheCurrentUserLookupFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->form($version);

		if ($major === 3)
		{
			$this->assertStringContainsString('___Power::getUser();', $code);
			$this->assertStringNotContainsString('getIdentity()', $code);

			return;
		}

		$this->assertStringContainsString(
			'___Power::getApplication()->getIdentity();', $code
		);
		$this->assertStringNotContainsString('___Power::getUser();', $code);
	}

	/**
	 * The form is loaded by name and returned, or false when it fails.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheFormIsLoadedByNameAndReturned(string $version, int $major): void
	{
		$code = $this->form($version);

		$this->assertStringContainsString(
			"\$form = \$this->loadForm('com_demo.article', 'article', \$options, \$clear, \$xpath);",
			$code
		);
		$this->assertStringContainsString('if (empty($form))', $code);
		$this->assertStringContainsString('return false;', $code);
		$this->assertStringContainsString('return $form;', $code);
	}

	/**
	 * The record id is taken from the loaded item, falling back to the input.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheRecordIdFallsBackToTheInput(): void
	{
		$code = $this->form('JoomlaSix');

		$this->assertStringContainsString("\$jinput = ", $code);
		$this->assertStringContainsString("\$id = \$jinput->get('id', 0, 'INT');", $code);
	}

	/**
	 * The record being saved decides the permissions, not the request.
	 *
	 * getForm() receives the data being saved, while the request can name a
	 * different record through a_id. Keying the permission decision on the
	 * request let a caller have the field guards evaluated against a record
	 * they may edit while the values were written to another one.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheSavedRecordIdOutranksTheRequest(string $version, int $major): void
	{
		$code = $this->form($version);

		$this->assertStringContainsString(
			"if (is_array(\$data) && isset(\$data['id']) && (int) \$data['id'] > 0)",
			$code
		);
		$this->assertStringContainsString("\$id = (int) \$data['id'];", $code);

		// the request is only consulted when the data carries no record
		$this->assertStringContainsString("elseif (\$jinput->get('a_id'))", $code);
		$this->assertSame(1, substr_count($code, "\$jinput->get('a_id')"));

		// the guarded decisions still key off that one resolved id
		$this->assertStringContainsString("'com_demo.article.' . (int) \$id", $code);
	}

	/**
	 * A field guarded on edit is unset when the user may not edit it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnEditGuardedFieldIsUnsetWhenNotAuthorised(): void
	{
		$permissionfields = new PermissionFields();
		$permissionfields->set('article', ['secret' => ['edit' => 'text']]);

		$code = $this->form('JoomlaSix', $permissionfields);

		$this->assertStringContainsString(
			"!\$user->authorise('article.edit.secret', 'com_demo')", $code
		);
		$this->assertStringContainsString(
			"\$form->setFieldAttribute('secret', 'disabled', 'true');", $code
		);
		$this->assertStringContainsString(
			"\$form->setFieldAttribute('secret', 'filter', 'unset');", $code
		);
		// an edit guard disables the field, it does not remove it
		$this->assertStringNotContainsString("\$form->removeField('secret');", $code);
	}

	/**
	 * A field guarded on access is removed rather than only disabled.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAccessGuardedFieldIsRemoved(): void
	{
		$permissionfields = new PermissionFields();
		$permissionfields->set('article', ['secret' => ['access' => 'text']]);

		$code = $this->form('JoomlaSix', $permissionfields);

		$this->assertStringContainsString(
			"!\$user->authorise('article.access.secret', 'com_demo')", $code
		);
		$this->assertStringContainsString("\$form->removeField('secret');", $code);
		// an access guard removes the field outright
		$this->assertStringNotContainsString(
			"\$form->setFieldAttribute('secret'", $code
		);
	}

	/**
	 * The core publishing guards render even when the view guards nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheCorePublishingGuardsAlwaysRender(): void
	{
		$code = $this->form('JoomlaSix');

		$this->assertStringContainsString(
			"\$form->setFieldAttribute('created', 'disabled', 'true');", $code
		);
		$this->assertStringContainsString(
			"authorise('core.edit.state', 'com_demo')", $code
		);
		// but nothing for a field the view never guarded
		$this->assertStringNotContainsString('secret', $code);
	}

	/**
	 * Every target keeps metadata protection inside its existing ACL branches.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('versions')]
	public function testMetadataIsRemovedOnlyFromApiValidationForms(string $version, int $major): void
	{
		$code = $this->form($version);

		foreach (['created', 'created_by'] as $field)
		{
			$this->assertStringContainsString(
				"if (!\$user->authorise('core.edit.{$field}', 'com_demo'))\n"
				. "\t\t{\n\t\t\tif (\$app->isClient('api'))\n\t\t\t{",
				$code
			);
			$this->assertStringContainsString("\$form->removeField('{$field}');", $code);
			$this->assertStringContainsString(
				"\$form->setFieldAttribute('{$field}', 'disabled', 'true');", $code
			);
			$this->assertStringContainsString(
				"\$form->setFieldAttribute('{$field}', 'filter', 'unset');", $code
			);
		}
	}

	/**
	 * Generated ACL decisions filter forbidden metadata without blocking edits.
	 *
	 * @param   bool  $api          Whether the model serves an API request.
	 * @param   bool  $recordAcl    Whether metadata actions are record-scoped.
	 * @param   bool  $editCreated  Whether the caller may change the date.
	 * @param   bool  $editOwner    Whether the caller may change the owner.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('metadataPermissions')]
	public function testMetadataPermissionsFollowTheClientAndActualRecord(bool $api, bool $recordAcl, bool $editCreated, bool $editOwner): void
	{
		$actions = $recordAcl
			? ['article.edit.created' => $editCreated, 'article.edit.created_by' => $editOwner]
			: ['core.edit.created' => $editCreated, 'core.edit.created_by' => $editOwner];
		$checks = [];
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturnCallback(
			static function (string $action, string $asset) use ($actions, &$checks): bool
			{
				$checks[$action] = $asset;

				return $actions[$action] ?? true;
			}
		);
		$app = $this->createStub(CMSApplication::class);
		$app->method('isClient')->willReturnCallback(
			static fn(string $client): bool => $client === ($api ? 'api' : 'administrator')
		);
		$input = new Input(['id' => 999, 'a_id' => 999]);
		$input->server->set('REQUEST_METHOD', 'PATCH');
		$app->method('getInput')->willReturn($input);
		$app->method('getIdentity')->willReturn($user);
		$permission = $recordAcl
			? $this->permissionWith(
				[
					'article|core.edit.created' => 'article.edit.created',
					'article|core.edit.created_by' => 'article.edit.created_by',
				],
				[
					'article.edit.created|article' => 'article',
					'article.edit.created_by|article' => 'article',
				]
			)
			: $this->permission();
		$data = [
			'id' => 42,
			'name' => 'Renamed record',
			'created' => '2026-10-07 12:30:00',
			'created_by' => 23,
		];
		$input->set('data', $data);
		$form = $this->generatedModel($app, $permission)->getForm($data);

		foreach (['created' => $editCreated, 'created_by' => $editOwner] as $field => $allowed)
		{
			$this->assertSame(
				$api && !$allowed ? null : $field,
				$form->getFieldAttribute($field, 'name')
			);
			$action = ($recordAcl ? 'article' : 'core') . '.edit.' . $field;
			$this->assertSame($recordAcl ? 'com_demo.article.42' : 'com_demo', $checks[$action]);

			if (!$api && !$allowed)
			{
				$this->assertSame('true', $form->getFieldAttribute($field, 'disabled'));
				$this->assertSame('unset', $form->getFieldAttribute($field, 'filter'));
			}
			elseif ($allowed)
			{
				$this->assertNull($form->getFieldAttribute($field, 'disabled'));
			}
		}

		if (!$api && !$editOwner)
		{
			$this->assertSame('true', $form->getFieldAttribute('created_by', 'readonly'));
		}

		// The browser excludes disabled controls; PATCH supplies stored values
		// for omitted columns and may also explicitly submit forbidden values.
		if (!$api)
		{
			foreach (['created' => $editCreated, 'created_by' => $editOwner] as $field => $allowed)
			{
				if (!$allowed)
				{
					unset($data[$field]);
				}
			}
		}

		$previousApplication = Factory::$application;
		Factory::$application = $app;

		try
		{
			$filtered = $form->filter($data);
			$this->assertTrue($form->validate($filtered));
		}
		finally
		{
			Factory::$application = $previousApplication;
		}

		$this->assertSame(42, $filtered['id']);
		$this->assertSame('Renamed record', $filtered['name']);

		foreach (['created' => $editCreated, 'created_by' => $editOwner] as $field => $allowed)
		{
			if ($allowed)
			{
				$this->assertSame($data[$field], $filtered[$field]);
			}
			else
			{
				$this->assertArrayNotHasKey($field, $filtered);
			}
		}
	}

	/**
	 * Omitted metadata never goes through a timezone filter or an update bind.
	 *
	 * @param   bool    $rawJson        Whether input comes from the raw JSON body.
	 * @param   string  $dateFilter     The calendar timezone filter.
	 * @param   bool    $submitCreated  Whether the PATCH explicitly sets the date.
	 * @param   bool    $submitOwner    Whether the PATCH explicitly sets the owner.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('patchMetadata')]
	public function testPatchOnlyFiltersExplicitMetadata(bool $rawJson, string $dateFilter, bool $submitCreated, bool $submitOwner): void
	{
		$data = [
			'id' => 42,
			'name' => 'Renamed record',
			'created' => '2026-10-07 12:30:00',
			'created_by' => 23,
		];
		$submitted = ['name' => $data['name']];

		if ($submitCreated)
		{
			$submitted['created'] = $data['created'];
		}

		if ($submitOwner)
		{
			$submitted['created_by'] = 0;
			$data['created_by'] = 0;
		}

		$json = $this->createStub(Json::class);
		$json->method('getRaw')->willReturn(json_encode($submitted, JSON_THROW_ON_ERROR));
		$server = new Input(['REQUEST_METHOD' => 'PATCH']);
		$input = $this->getMockBuilder(Input::class)
			->setConstructorArgs([$rawJson ? [] : ['data' => $submitted]])
			->onlyMethods(['__get'])
			->getMock();
		$input->expects($this->atLeastOnce())->method('__get')->willReturnMap([['json', $json], ['server', $server]]);
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturn(true);
		$user->method('getParam')->willReturn('Africa/Windhoek');
		$app = $this->createStub(CMSApplication::class);
		$app->method('isClient')->willReturnCallback(static fn(string $client): bool => $client === 'api');
		$app->method('getInput')->willReturn($input);
		$app->method('getIdentity')->willReturn($user);
		$app->method('get')->willReturn('Africa/Windhoek');
		$form = $this->generatedModel($app, $this->permission(), $dateFilter)->getForm($data);
		$previousApplication = Factory::$application;
		$previousDatabase = Factory::$database;
		$previousDates = Factory::$dates;
		$previousLanguage = Factory::$language;
		$database = $this->createStub(DatabaseInterface::class);
		$database->method('getDateFormat')->willReturn('Y-m-d H:i:s');
		$language = $this->createStub(Language::class);
		$language->method('getTag')->willReturn('en-GB');
		Factory::$application = $app;
		Factory::$database = $database;
		Factory::$language = $language;

		try
		{
			$filtered = $form->filter($data);
			$this->assertTrue($form->validate($filtered));
		}
		finally
		{
			Factory::$application = $previousApplication;
			Factory::$database = $previousDatabase;
			Factory::$dates = $previousDates;
			Factory::$language = $previousLanguage;
		}

		$this->assertSame('Renamed record', $filtered['name']);

		if ($submitCreated)
		{
			$this->assertSame('2026-10-07 10:30:00', $filtered['created']);
		}
		else
		{
			$this->assertArrayNotHasKey('created', $filtered);
		}

		if ($submitOwner)
		{
			$this->assertSame(0, $filtered['created_by']);
		}
		else
		{
			$this->assertArrayNotHasKey('created_by', $filtered);
		}

		$this->assertSame($submitted, $input->get('data', json_decode($input->json->getRaw(), true), 'array'));
	}

	/**
	 * Both native payload sources and timezone filters retain PATCH presence.
	 *
	 * @return  iterable<string, array{bool, string, bool, bool}>  Payload and fields.
	 * @since   6.2.0
	 */
	public static function patchMetadata(): iterable
	{
		foreach ([false, true] as $rawJson)
		{
			foreach (['user_utc', 'server_utc'] as $dateFilter)
			{
				foreach ([false, true] as $submitCreated)
				{
					foreach ([false, true] as $submitOwner)
					{
						$name = ($rawJson ? 'json' : 'data') . ' ' . $dateFilter
							. ' date:' . (int) $submitCreated . ' owner:' . (int) $submitOwner;
						yield $name => [$rawJson, $dateFilter, $submitCreated, $submitOwner];
					}
				}
			}
		}
	}

	/**
	 * New records keep their normal metadata fields and owner defaults.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testApiCreatesKeepAuthorisedMetadataFields(): void
	{
		$user = $this->createStub(User::class);
		$user->id = 7;
		$user->method('authorise')->willReturn(true);
		$input = new Input(['data' => ['name' => 'New record']]);
		$input->server->set('REQUEST_METHOD', 'POST');
		$app = $this->createStub(CMSApplication::class);
		$app->method('isClient')->willReturnCallback(static fn(string $client): bool => $client === 'api');
		$app->method('getInput')->willReturn($input);
		$app->method('getIdentity')->willReturn($user);
		$form = $this->generatedModel($app, $this->permission())->getForm(['name' => 'New record']);

		$this->assertSame('created', $form->getFieldAttribute('created', 'name'));
		$this->assertSame('created_by', $form->getFieldAttribute('created_by', 'name'));
		$this->assertSame(7, $form->getValue('created_by'));
	}

	/**
	 * Both metadata rights vary independently under both permission scopes.
	 *
	 * @return  iterable<string, array{bool, bool, bool, bool}>  Client and rights.
	 * @since   6.2.0
	 */
	public static function metadataPermissions(): iterable
	{
		foreach ([false, true] as $api)
		{
			foreach ([false, true] as $recordAcl)
			{
				foreach ([false, true] as $editCreated)
				{
					foreach ([false, true] as $editOwner)
					{
						$name = ($api ? 'api' : 'administrator') . ($recordAcl ? ' record' : ' component')
							. ' date:' . (int) $editCreated . ' owner:' . (int) $editOwner;
						yield $name => [$api, $recordAcl, $editCreated, $editOwner];
					}
				}
			}
		}
	}

	/**
	 * Execute generated source while substituting only application/form loading.
	 *
	 * @param   CMSApplication  $app         The bounded request application.
	 * @param   Permission      $permission  The compiler's metadata actions.
	 * @param   string          $dateFilter  The calendar's input date filter.
	 *
	 * @return  GeneratedFormModelFixture  The executable generated model.
	 * @since   6.2.0
	 */
	private function generatedModel(CMSApplication $app, Permission $permission, string $dateFilter = 'raw'): GeneratedFormModelFixture
	{
		$form = new Form('metadata');
		$form->setDatabase($this->createStub(DatabaseInterface::class));
		$form->setCurrentUser($app->getIdentity());
		$this->assertTrue($form->load(
			'<form><field name="id" type="text" filter="integer" label="ID" translateLabel="false" />'
			. '<field name="name" type="text" filter="string" required="true" label="Name" translateLabel="false" />'
			. '<field name="created" type="calendar" filter="' . $dateFilter . '" label="Created" translateLabel="false" />'
			. '<field name="created_by" type="text" filter="integer" label="Owner" translateLabel="false" /></form>'
		));
		$subject = $this->renderer($this->targetClass('JoomlaSix', 'Model\\GetForm', ['JoomlaThree']), [
			'permission' => $permission,
			'permissionfields' => new PermissionFields(),
		]);
		$body = str_replace(
			'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()',
			'$this->getApplication()',
			$subject->get('article', 'articles')
		);
		$method = "public function getForm(array \$data, array \$options = []): \\Joomla\\CMS\\Form\\Form\n{\n"
			. $body . "\n}";

		return eval('return new class($form, $app) extends \\' . GeneratedFormModelFixture::class . ' {' . $method . '};');
	}

	/**
	 * Build the getForm method of one target.
	 *
	 * @param   string                 $version           Target namespace segment.
	 * @param   PermissionFields|null  $permissionfields  The guarded field registry.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function form(string $version, ?PermissionFields $permissionfields = null): string
	{
		// only Joomla 3 takes the current user from the global factory
		$class = $this->targetClass($version, 'Model\\GetForm', ['JoomlaThree']);

		$subject = $this->renderer($class, [
			'permissionfields' => $permissionfields ?? new PermissionFields(),
		]);

		return $subject->get('article', 'articles');
	}
}
