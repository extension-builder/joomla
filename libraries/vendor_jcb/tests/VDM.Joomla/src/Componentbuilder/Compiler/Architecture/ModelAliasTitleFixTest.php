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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\AliasTitleFix;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Alias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryCode;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomAlias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Title;


/**
 * Generated model title and alias uniqueness fix contracts.
 *
 * The fix reads the same on every Joomla target, so this is one class with
 * no target variants at all.
 *
 * @since  6.1.7
 */
#[CoversClass(AliasTitleFix::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelAliasTitleFixTest extends ArchitectureTestCase
{
	/**
	 * The unique field fix is emitted even when there is no alias.
	 *
	 * It sits outside the alias and title guard, so every view carrying
	 * unique fields gets it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheUniqueFieldFixIsAlwaysEmitted(): void
	{
		foreach ([$this->fix(false, true), $this->fix(true, false)] as $code)
		{
			$this->assertStringContainsString(
				'// Alter the unique field for save as copy', $code
			);
			$this->assertStringContainsString(
				'$uniqueFields = $this->getUniqueFields();', $code
			);
			// but nothing for the title or alias
			$this->assertStringNotContainsString('// Alter the title for save as copy', $code);
		}
	}

	/**
	 * Saving as a copy gives the record a fresh title and alias.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSaveAsCopyGetsAFreshTitleAndAlias(): void
	{
		$code = $this->fix();

		$this->assertStringContainsString('// Alter the title for save as copy', $code);
		$this->assertStringContainsString(
			"if (\$input->get('task') === 'save2copy')", $code
		);
		$this->assertStringContainsString('$origTable = clone $this->getTable();', $code);
		$this->assertStringContainsString(
			"list(\$title, \$alias) = \$this->_generateNewTitle(\$data['alias'], \$data['title']);",
			$code
		);
		$this->assertStringContainsString("\$data['published'] = 0;", $code);
	}

	/**
	 * An empty alias is filled from the title, honouring unicode slugs.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnEmptyAliasIsFilledFromTheTitle(): void
	{
		$code = $this->fix();

		$this->assertStringContainsString(
			'// Automatic handling of alias for empty fields', $code
		);
		$this->assertStringContainsString(
			"OutputFilter::stringURLUnicodeSlug(\$data['title']);", $code
		);
		$this->assertStringContainsString(
			"OutputFilter::stringURLSafe(\$data['title']);", $code
		);
	}

	/**
	 * An uncategorised view checks uniqueness on the alias alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnUncategorisedViewChecksTheAliasAlone(): void
	{
		$code = $this->fix();

		$this->assertStringContainsString(
			"\$table->load(array('alias' => \$data['alias']))", $code
		);
		$this->assertStringNotContainsString("'catid' => \$data['catid']", $code);
	}

	/**
	 * A categorised view scopes uniqueness to the record's category.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACategorisedViewScopesToTheCategory(): void
	{
		$code = $this->fix(true, true, 'catid');

		$this->assertStringContainsString(
			"\$table->load(['alias' => \$data['alias'], 'catid' => \$data['catid']])", $code
		);
		$this->assertStringContainsString(
			"\$this->generateNewTitle(\$data['catid'], \$data['alias'], \$data['title']);",
			$code
		);
		// the uncategorised helper is not used when a category scopes the view
		$this->assertStringNotContainsString(
			"\$this->_generateNewTitle(\$data['alias']);", $code
		);
	}

	/**
	 * Build the fix for one view shape.
	 *
	 * @param   bool         $withAlias  Whether the view has an alias field.
	 * @param   bool         $withTitle  Whether the view has a title field.
	 * @param   string|null  $category   The category code, when categorised.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function fix(bool $withAlias = true, bool $withTitle = true,
		?string $category = null): string
	{
		$alias = new Alias();

		if ($withAlias)
		{
			$alias->set('article', 'alias');
		}

		$title = new Title();

		if ($withTitle)
		{
			$title->set('article', 'title');
		}

		$categorycode = new CategoryCode();

		if ($category !== null)
		{
			$categorycode->set('article.code', $category);
		}

		$contentone = new ContentOne();
		$contentone->set('Component', 'Demo');

		$subject = new AliasTitleFix(
			$alias, $title, new CustomAlias(), $categorycode, $contentone
		);

		return $subject->get('article');
	}
}
