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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\FieldRelation;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListJoin;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldRelations;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SelectionTranslation;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Tags;


/**
 * Generated list model item fix contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedItemsStringFixTest extends ArchitectureTestCase
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
	 * A view with nothing to fix generates nothing.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewWithNothingToFixGeneratesNothing(string $version, int $major): void
	{
		$subject = $this->subject($version);

		$this->assertSame(
			'',
			$subject->get('article', 'articles', 'Demo')
		);
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
		// $all opens the loop without needing any field to fix
		$code = $this->subject($version)
			->get('article', 'articles', 'Demo', '', false, true);

		if ($major === 3)
		{
			$this->assertStringContainsString(
				'___Power::getUser();',
				$code
			);
			$this->assertStringNotContainsString('$this->getCurrentUser();', $code);

			return;
		}

		$this->assertStringContainsString('$user = $this->getCurrentUser();', $code);
		$this->assertStringNotContainsString('___Power::getUser();', $code);
	}

	/**
	 * A permission guarded field is unset when the user may not see it.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAGuardedFieldIsUnsetWhenNotAuthorised(string $version, int $major): void
	{
		// strict per field control is opt in, and only access or view guard
		$this->config()->set('permission_strict_per_field', true);

		$permissionfields = new PermissionFields();
		$permissionfields->set('article', ['secret' => ['view' => 'text']]);

		$code = $this->subject($version, ['permissionfields' => $permissionfields])
			->get('article', 'articles', 'Demo', '', false, true);

		$this->assertStringContainsString('foreach ($items as $nr => &$item)', $code);
		$this->assertStringContainsString(
			"\$strict_permission_per_field = ComponentHelper::getParams('com_demo')"
			. "->get('strict_permission_per_field', 0);",
			$code
		);
	}

	/**
	 * Related fields are resolved before and after the modelling pass.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testRelatedFieldsAreResolvedInBothPasses(): void
	{
		$relations = new FieldRelations();
		$relations->set('articles', [
			'guid-a' => [
				1 => ['code' => 'before', 'id' => 1, 'guid' => 'guid-a',
					'set' => ' - ', 'join_type' => 1],
				3 => ['code' => 'after', 'id' => 2, 'guid' => 'guid-b',
					'set' => ' - ', 'join_type' => 1],
			],
		]);

		$code = $this->subject('JoomlaSix', ['fieldrelations' => $relations])
			->get('article', 'articles', 'Demo');

		$this->assertStringContainsString('$item->before = $item->before;', $code);
		$this->assertStringContainsString('$item->after = $item->after;', $code);
	}

	/**
	 * The commented out selection translation block stays dormant.
	 *
	 * The legacy helper carries this block inside a block comment. It is
	 * kept exactly as it was, so a view with translatable selections still
	 * generates nothing for them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDormantSelectionTranslationStaysDormant(): void
	{
		$selection = new SelectionTranslation();
		$selection->set('articles', ['status' => ['1' => 'Published']]);

		$code = $this->subject('JoomlaSix', ['selectiontranslation' => $selection])
			->get('article', 'articles', 'Demo');

		$this->assertSame('', $code);
	}

	/**
	 * A tagged view loads its tags onto every item.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testATaggedViewLoadsItsTags(): void
	{
		$tags = new Tags();
		$tags->set('article', ['add' => true]);

		$code = $this->subject('JoomlaSix', ['tags' => $tags])
			->get('article', 'articles', 'Demo');

		$this->assertStringContainsString('// Add the tags', $code);
		$this->assertStringContainsString('$item->tags = new TagsHelper;', $code);
		$this->assertStringContainsString('$item->tags->getTagIds(', $code);
	}

	/**
	 * Create the item string fix of one target with real registries.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $overrides  Constructor dependency overrides.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function subject(string $version, array $overrides = []): object
	{
		// only Joomla 3 models have no getCurrentUser()
		$class = $this->targetClass(
			$version, 'Model\\ItemsStringFix', ['JoomlaThree']
		);

		return $this->renderer($class, $overrides + [
			'fieldrelation' => new FieldRelation(new ListJoin(), $this->placeholder()),
		]);
	}
}
