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
use VDM\Joomla\Componentbuilder\Compiler\Builder\Alias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryCode;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Title;


/**
 * Generated admin model batch copy and move contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedModelBatchTest extends ArchitectureTestCase
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
	 * Batch copy takes the current user from the place its target provides.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testBatchCopyUserLookupFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->batch('BatchCopy', $version);

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
	 * Batch move takes the current user from the place its target provides.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testBatchMoveUserLookupFollowsTheTarget(string $version, int $major): void
	{
		$code = $this->batch('BatchMove', $version);

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
	 * Each batch method keeps the spacing the legacy helper wrote.
	 *
	 * The two methods indent the assignment differently, so each family
	 * carries its own statement rather than sharing one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachBatchMethodKeepsItsOwnSpacing(): void
	{
		$this->assertStringContainsString(
			"\$this->user \t\t= ", $this->batch('BatchCopy', 'JoomlaSix')
		);
		$this->assertStringContainsString(
			"\$this->user\t\t= ", $this->batch('BatchMove', 'JoomlaSix')
		);
	}

	/**
	 * Batch copy guards on create, and batch move guards on edit.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachBatchMethodGuardsOnItsOwnAction(): void
	{
		$copy = $this->batch('BatchCopy', 'JoomlaSix');
		$move = $this->batch('BatchMove', 'JoomlaSix');

		$this->assertStringContainsString('protected function batchCopy($values, $pks, $contexts)', $copy);
		$this->assertStringContainsString(
			"if (!\$this->canDo->get('core.create') && !\$this->canDo->get('core.batch'))",
			$copy
		);

		$this->assertStringContainsString('protected function batchMove($values, $pks, $contexts)', $move);
		$this->assertStringContainsString(
			"if (!\$this->canDo->get('core.edit') && !\$this->canDo->get('core.batch'))",
			$move
		);
	}

	/**
	 * Both batch methods guard the publish state and run once per batch.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testBothBatchMethodsGuardThePublishState(): void
	{
		foreach (['BatchCopy', 'BatchMove'] as $family)
		{
			$code = $this->batch($family, 'JoomlaSix');

			$this->assertStringContainsString('if (empty($this->batchSet))', $code);
			$this->assertStringContainsString(
				"!\$this->canDo->get('core.edit.state')", $code
			);
		}
	}

	/**
	 * A copied record is given a fresh title and alias.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACopiedRecordGetsAFreshTitleAndAlias(): void
	{
		$code = $this->batch('BatchCopy', 'JoomlaSix');

		$this->assertStringContainsString(
			'list($this->table->title, $this->table->alias) = $this->_generateNewTitle('
			. '$this->table->alias, $this->table->title);',
			$code
		);
	}

	/**
	 * A moved record is stored back rather than inserted.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAMovedRecordIsStoredBack(): void
	{
		$code = $this->batch('BatchMove', 'JoomlaSix');

		$this->assertStringContainsString('if (!$this->table->store())', $code);
		$this->assertStringNotContainsString('_generateNewTitle(', $code);
	}

	/**
	 * Build one batch method of one target.
	 *
	 * @param   string  $family   BatchCopy or BatchMove.
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function batch(string $family, string $version): string
	{
		// only Joomla 3 takes the current user from the global factory
		$class = $this->targetClass($version, 'Model\\' . $family, ['JoomlaThree']);

		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		$alias = new Alias();
		$alias->set('article', 'alias');

		$title = new Title();
		$title->set('article', 'title');

		$subject = $this->renderer($class, [
			'contentone' => $contentone,
			'alias' => $alias,
			'title' => $title,
			'categorycode' => new CategoryCode(),
		]);

		return $subject->get('article');
	}
}
