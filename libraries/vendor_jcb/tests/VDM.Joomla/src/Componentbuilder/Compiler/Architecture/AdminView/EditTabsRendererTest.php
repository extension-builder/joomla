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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\AdminView;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\CustomTabs;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\TabLayoutFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomTabs as CustomTabsData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Layout;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Edit view custom tab and tab layout field contracts.
 *
 * @since  6.1.7
 */
#[CoversClass(CustomTabs::class)]
#[CoversClass(TabLayoutFields::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class EditTabsRendererTest extends ArchitectureTestCase
{
	/**
	 * A view with no registered layout yields the empty array literal.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTabLayoutFieldsWithoutALayoutIsAnEmptyArray(): void
	{
		$subject = new TabLayoutFields(new Layout());

		$this->assertSame('array()', $subject->get('article'));
	}

	/**
	 * Tabs, alignments and fields each render in key order.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTabLayoutFieldsRenderEveryAlignmentInKeyOrder(): void
	{
		$layout = new Layout();
		$layout->set('article', [
			'Details' => [
				// deliberately out of order, the renderer sorts them
				3 => [1 => 'body', 0 => 'title'],
				1 => [0 => 'name'],
			],
		]);

		$subject = new TabLayoutFields($layout);

		$this->assertSame(
			"array("
			. PHP_EOL . "\t\t'details' => array("
			. PHP_EOL . "\t\t\t'left' => array("
			. PHP_EOL . "\t\t\t\t'name'"
			. PHP_EOL . "\t\t\t),"
			. PHP_EOL . "\t\t\t'fullwidth' => array("
			. PHP_EOL . "\t\t\t\t'title',"
			. PHP_EOL . "\t\t\t\t'body'"
			. PHP_EOL . "\t\t\t)"
			. PHP_EOL . "\t\t)"
			. PHP_EOL . "\t)",
			$subject->get('article')
		);
	}

	/**
	 * A view without custom tabs reports the slot as empty.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomTabsWithoutAnyRegistrationIsFalse(): void
	{
		$subject = new CustomTabs(new CustomTabsData());

		$this->assertFalse($subject->get(1, 'article', 1));
	}

	/**
	 * Only tabs matching both the tab number and the position are emitted.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomTabsOnlyEmitsTheMatchingSlot(): void
	{
		$subject = new CustomTabs($this->customtabs());

		$this->assertSame(
			PHP_EOL . '<p>above</p>',
			$subject->get(1, 'article', 1)
		);
		$this->assertSame(
			PHP_EOL . '<p>below</p>',
			$subject->get(1, 'article', 2)
		);
		// a different tab number shares the position but is not this slot
		$this->assertSame(
			PHP_EOL . '<p>second tab</p>',
			$subject->get(2, 'article', 1)
		);
		$this->assertFalse($subject->get(3, 'article', 1));
	}

	/**
	 * Several tabs in one slot are emitted in registration order.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomTabsInOneSlotKeepRegistrationOrder(): void
	{
		$customtabs = new CustomTabsData();
		$customtabs->set('article', [
			['tab' => 1, 'position' => 1, 'html' => '<p>first</p>'],
			['tab' => 1, 'position' => 1, 'html' => '<p>second</p>'],
		]);

		$subject = new CustomTabs($customtabs);

		$this->assertSame(
			PHP_EOL . '<p>first</p>' . PHP_EOL . '<p>second</p>',
			$subject->get(1, 'article', 1)
		);
	}

	/**
	 * A registration without usable markup never reaches the output.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomTabsSkipEntriesWithoutMarkup(): void
	{
		$customtabs = new CustomTabsData();
		$customtabs->set('article', [
			['tab' => 1, 'position' => 1, 'html' => ''],
			['tab' => 1, 'position' => 1],
			'not an array',
		]);

		$subject = new CustomTabs($customtabs);

		$this->assertFalse($subject->get(1, 'article', 1));
	}

	/**
	 * Build a custom tab registry covering two tabs and both positions.
	 *
	 * @return  CustomTabsData
	 * @since   6.1.7
	 */
	private function customtabs(): CustomTabsData
	{
		$customtabs = new CustomTabsData();
		$customtabs->set('article', [
			['tab' => 1, 'position' => 1, 'html' => '<p>above</p>'],
			['tab' => 1, 'position' => 2, 'html' => '<p>below</p>'],
			['tab' => 2, 'position' => 1, 'html' => '<p>second tab</p>'],
		]);

		return $customtabs;
	}
}
