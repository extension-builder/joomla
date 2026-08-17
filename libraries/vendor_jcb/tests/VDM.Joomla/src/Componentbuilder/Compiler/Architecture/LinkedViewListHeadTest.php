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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\LinkedView\ListHead;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListHeadOverride;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Lists;


/**
 * Linked admin view table head contracts.
 *
 * @since  6.1.7
 */
#[CoversClass(ListHead::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class LinkedViewListHeadTest extends ArchitectureTestCase
{
	/**
	 * A view with no list fields renders no table head.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutListFieldsHasNoHead(): void
	{
		$this->assertSame(
			'',
			$this->subject(new Lists())->get('article', 'articles', 0, 'article')
		);
	}

	/**
	 * Only columns targeting the linked list view are rendered.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOnlyLinkedTargetsBecomeColumns(): void
	{
		$lists = new Lists();
		$lists->set('articles', [
			['target' => 1, 'guid' => 'a', 'lang' => 'COM_DEMO_TITLE', 'link' => false],
			['target' => 2, 'guid' => 'b', 'lang' => 'COM_DEMO_HIDDEN', 'link' => false],
			['target' => 4, 'guid' => 'c', 'lang' => 'COM_DEMO_BODY', 'link' => false],
		]);

		$head = $this->subject($lists)->get('article', 'articles', 0, 'article');

		$this->assertStringContainsString("Text::_('COM_DEMO_TITLE')", $head);
		$this->assertStringContainsString("Text::_('COM_DEMO_BODY')", $head);
		$this->assertStringNotContainsString('COM_DEMO_HIDDEN', $head);
		$this->assertStringEndsWith(PHP_EOL . '</thead>', $head);
	}

	/**
	 * A registered override replaces the language key of its column.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnOverrideReplacesTheColumnLabel(): void
	{
		$override = new ListHeadOverride();
		$override->set('articles.a', 'COM_DEMO_OVERRIDDEN');

		$head = $this->subject($this->lists(), $override)
			->get('article', 'articles', 0, 'article');

		$this->assertStringContainsString("Text::_('COM_DEMO_OVERRIDDEN')", $head);
		$this->assertStringNotContainsString('COM_DEMO_TITLE', $head);
	}

	/**
	 * Footable 2 and 3 describe the same table with their own attributes.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachFootableReleaseUsesItsOwnAttributes(): void
	{
		$subject = $this->subject($this->lists());

		$this->config()->set('footable_version', 2);
		$two = $subject->get('article', 'articles', 0, 'article');

		$this->assertStringContainsString('metro-blue', $two);
		$this->assertStringContainsString('data-page-size="20"', $two);
		$this->assertStringContainsString('data-filter="#filter_articles"', $two);
		$this->assertStringContainsString('data-hide="phone"', $two);
		$this->assertStringContainsString('data-type="numeric"', $two);
		$this->assertStringNotContainsString('data-breakpoints', $two);

		$this->config()->set('footable_version', 3);
		$three = $subject->get('article', 'articles', 0, 'article');

		$this->assertStringContainsString('data-show-toggle="true"', $three);
		$this->assertStringContainsString('data-paging="true"', $three);
		$this->assertStringContainsString('data-breakpoints="xs sm"', $three);
		$this->assertStringContainsString('data-type="number"', $three);
		$this->assertStringNotContainsString('metro-blue', $three);
		$this->assertStringNotContainsString('data-hide=', $three);
	}

	/**
	 * Columns drop off progressively as the table grows.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testColumnsHideProgressivelyAsTheTableGrows(): void
	{
		$lists = new Lists();
		for ($i = 0; $i < 8; $i++)
		{
			$lists->add('articles', [
				'target' => 1,
				'guid' => 'g' . $i,
				'lang' => 'COM_DEMO_F' . $i,
				'link' => false,
			], true);
		}

		$head = $this->subject($lists)->get('article', 'articles', 0, 'article');

		// the first three stay, the next three drop on phones, the rest go
		$this->assertSame(3, substr_count($head, '<th data-hide="phone">'));
		$this->assertSame(3, substr_count($head, '<th data-hide="phone,tablet">'));
		$this->assertSame(2, substr_count($head, '<th data-hide="all">'));
		// the trailing status and id columns carry their own width first
		$this->assertStringContainsString(
			'<th width="10" data-hide="phone,tablet">', $head
		);
	}

	/**
	 * The first linkable column becomes the responsive toggle on Footable 2.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheFirstLinkedColumnIsTheToggle(): void
	{
		$lists = new Lists();
		$lists->set('articles', [
			['target' => 1, 'guid' => 'a', 'lang' => 'COM_DEMO_TITLE', 'link' => true],
			['target' => 1, 'guid' => 'b', 'lang' => 'COM_DEMO_BODY', 'link' => true],
		]);

		$head = $this->subject($lists)->get('article', 'articles', 0, 'article');

		$this->assertSame(1, substr_count($head, 'data-toggle="true"'));
	}

	/**
	 * Status and id columns are skipped when the view declares them itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testDeclaredStatusAndIdFieldsAreNotAddedTwice(): void
	{
		$fieldnames = new FieldNames();
		$fieldnames->set('article.published', 'published');
		$fieldnames->set('article.id', 'id');

		$head = $this->subject($this->lists(), null, $fieldnames)
			->get('article', 'articles', 0, 'article');

		$this->assertStringNotContainsString('COM_DEMO_ARTICLE_STATUS', $head);
		$this->assertStringNotContainsString('COM_DEMO_ARTICLE_ID', $head);
	}

	/**
	 * Each new-button mode renders exactly the buttons it names.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEachNewButtonModeRendersItsOwnButtons(): void
	{
		$subject = $this->subject($this->lists());

		$none = $subject->get('article', 'articles', 0, 'article');
		$this->assertStringStartsWith('<?php if (Super_', $none);

		$new = $subject->get('article', 'articles', 1, 'article');
		$this->assertStringContainsString('$new; ?>', $new);
		$this->assertStringNotContainsString('$close_new', $new);
		$this->assertStringNotContainsString('btn-group', $new);

		$both = $subject->get('article', 'articles', 2, 'article');
		$this->assertStringContainsString('btn-group', $both);
		$this->assertStringContainsString('$new; ?>', $both);
		$this->assertStringContainsString('$close_new; ?>', $both);

		$closeNew = $subject->get('article', 'articles', 3, 'article');
		$this->assertStringContainsString('$close_new; ?>', $closeNew);
		$this->assertStringNotContainsString('$new; ?>', $closeNew);
		$this->assertStringContainsString("submitbutton('article.cancel')", $closeNew);
	}

	/**
	 * Build a list registry with one linkable column.
	 *
	 * @return  Lists
	 * @since   6.1.7
	 */
	private function lists(): Lists
	{
		$lists = new Lists();
		$lists->set('articles', [
			['target' => 1, 'guid' => 'a', 'lang' => 'COM_DEMO_TITLE', 'link' => false],
		]);

		return $lists;
	}

	/**
	 * Create the linked view list head with real registries.
	 *
	 * @param   Lists                  $lists       The list registry.
	 * @param   ListHeadOverride|null  $override    The head override registry.
	 * @param   FieldNames|null        $fieldnames  The field name registry.
	 *
	 * @return  ListHead
	 * @since   6.1.7
	 */
	private function subject(Lists $lists, ?ListHeadOverride $override = null,
		?FieldNames $fieldnames = null): ListHead
	{
		return new ListHead(
			$this->config(),
			$this->language(),
			$lists,
			$this->permission(),
			$override ?? new ListHeadOverride(),
			$fieldnames ?? new FieldNames()
		);
	}
}
