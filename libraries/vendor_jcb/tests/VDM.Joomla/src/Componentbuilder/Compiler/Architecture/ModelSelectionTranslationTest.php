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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\SelectionTranslation;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SelectionTranslation as SelectionTranslationData;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;


/**
 * Model selection translation loop contracts.
 *
 * @since  6.1.7
 */
#[CoversClass(SelectionTranslation::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelSelectionTranslationTest extends ArchitectureTestCase
{
	/**
	 * A view with no translatable selections generates nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutSelectionsGeneratesNothing(): void
	{
		$subject = new SelectionTranslation(new SelectionTranslationData());

		$this->assertSame('', $subject->get('articles'));
	}

	/**
	 * Every translatable selection is converted inside one guarded loop.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEverySelectionIsConvertedInOneGuardedLoop(): void
	{
		$code = $this->subject()->get('articles');

		$this->assertStringContainsString('foreach ($items as $nr => &$item)', $code);
		$this->assertStringContainsString(
			"\$item->status = \$this->selectionTranslation(\$item->status, 'status');",
			$code
		);
		$this->assertStringContainsString(
			"\$item->kind = \$this->selectionTranslation(\$item->kind, 'kind');",
			$code
		);
		// one guard and one loop for all of them
		$this->assertSame(1, substr_count($code, 'foreach ($items'));
		$this->assertSame(1, substr_count($code, '___Power::check($items)'));
		$this->assertStringEndsWith('}' . PHP_EOL, $code);
	}

	/**
	 * The tab argument indents every generated line of the loop.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheTabArgumentIndentsTheWholeLoop(): void
	{
		$plain = $this->subject()->get('articles');
		$tabbed = $this->subject()->get('articles', Indent::_(1));

		$this->assertNotSame($plain, $tabbed);
		$this->assertStringContainsString(
			PHP_EOL . Indent::_(1) . Indent::_(1) . Indent::_(2)
			. 'foreach ($items as $nr => &$item)',
			$tabbed
		);
		// the same statements, only further in
		$this->assertSame(
			substr_count($plain, 'selectionTranslation'),
			substr_count($tabbed, 'selectionTranslation')
		);
	}

	/**
	 * Create the selection translation builder with two selections.
	 *
	 * @return  SelectionTranslation
	 * @since   6.1.7
	 */
	private function subject(): SelectionTranslation
	{
		$data = new SelectionTranslationData();
		$data->set('articles', [
			'status' => ['1' => 'Published'],
			'kind' => ['2' => 'Draft'],
		]);

		return new SelectionTranslation($data);
	}
}
