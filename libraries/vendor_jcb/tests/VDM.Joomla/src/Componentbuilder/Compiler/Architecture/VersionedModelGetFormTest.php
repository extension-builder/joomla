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
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionFields;


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
