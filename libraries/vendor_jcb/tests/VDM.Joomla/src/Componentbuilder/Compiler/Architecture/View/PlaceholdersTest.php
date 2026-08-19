<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\View;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\Placeholders;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * What one view is named to everything built for it.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\View')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class PlaceholdersTest extends ArchitectureTestCase
{
	/**
	 * The registry the names were written into.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $content = null;

	/**
	 * A view with both names is named to itself in all six casings.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithBothNamesIsNamedInAllSixCasings(): void
	{
		$this->nameView($this->view('demo', 'demos'));

		$this->assertSame(
			[
				'###view###' => 'demo',
				'###VIEW###' => 'DEMO',
				'###View###' => 'Demo',
				'###views###' => 'demos',
				'###VIEWS###' => 'DEMOS',
				'###Views###' => 'Demos'
			],
			$this->content->get('demo')
		);
	}

	/**
	 * The list view is named the same way, under its own key.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheListViewIsNamedUnderItsOwnKey(): void
	{
		$this->nameView($this->view('demo', 'demos'));

		$this->assertSame(
			[
				'###view###' => 'demo',
				'###VIEW###' => 'DEMO',
				'###View###' => 'Demo',
				'###views###' => 'demos',
				'###VIEWS###' => 'DEMOS',
				'###Views###' => 'Demos'
			],
			$this->content->get('demos')
		);
	}

	/**
	 * The compiler is told the three casings of the single name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheCompilerIsToldTheCasingsOfTheSingleName(): void
	{
		$this->nameView($this->view('demo', 'demos'));

		$this->assertSame('demo', $this->placeholder()->get_h('view'));
		$this->assertSame('Demo', $this->placeholder()->get_h('View'));
		$this->assertSame('DEMO', $this->placeholder()->get_h('VIEW'));
		$this->assertSame('demos', $this->placeholder()->get_h('views'));
		$this->assertSame('Demos', $this->placeholder()->get_h('Views'));
		$this->assertSame('DEMOS', $this->placeholder()->get_h('VIEWS'));
	}

	/**
	 * A view with only a single name is named by that one alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithOnlyASingleNameIsNamedByThatAlone(): void
	{
		$this->nameView($this->view('demo', null));

		$this->assertSame(
			[
				'###view###' => 'demo',
				'###VIEW###' => 'DEMO',
				'###View###' => 'Demo'
			],
			$this->content->get('demo')
		);
		$this->assertNull($this->placeholder()->get_h('views'));
	}

	/**
	 * A view with only a list name is named by that one alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithOnlyAListNameIsNamedByThatAlone(): void
	{
		$this->nameView($this->view(null, 'demos'));

		$this->assertSame(
			[
				'###views###' => 'demos',
				'###VIEWS###' => 'DEMOS',
				'###Views###' => 'Demos'
			],
			$this->content->get('demos')
		);
		$this->assertNull($this->placeholder()->get_h('view'));
	}

	/**
	 * A view named "null" on either side is treated as having no such name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewNamedNullIsTreatedAsHavingNoSuchName(): void
	{
		$this->nameView($this->view('null', 'null'));

		$this->assertSame([], $this->content->allActive());
		$this->assertNull($this->placeholder()->get_h('view'));
		$this->assertNull($this->placeholder()->get_h('views'));
	}

	/**
	 * Naming a second view forgets the first, so nothing carries over.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testNamingASecondViewForgetsTheFirst(): void
	{
		$subject = $this->renderer(Placeholders::class, [
			'contentmulti' => $this->content = new ContentMulti()
		]);

		$first = $this->view('demo', 'demos');
		$subject->set($first);
		$second = $this->view('looker', null);
		$subject->set($second);

		$this->assertSame('looker', $this->placeholder()->get_h('view'));
		$this->assertNull($this->placeholder()->get_h('views'));
		$this->assertSame(
			['demo', 'demos', 'looker'], array_keys($this->content->allActive())
		);
	}

	/**
	 * Name one view.
	 *
	 * @param   object  $view  The view being built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function nameView(object $view): void
	{
		$this->content = new ContentMulti();
		$subject = $this->renderer(Placeholders::class, [
			'contentmulti' => $this->content
		]);

		$subject->set($view);
	}

	/**
	 * A view the compiler collected.
	 *
	 * @param   string|null  $single  Its single name, when it has one.
	 * @param   string|null  $list    Its list name, when it has one.
	 *
	 * @return  stdClass
	 * @since   6.1.7
	 */
	private function view(?string $single, ?string $list): stdClass
	{
		$view = new stdClass();

		if ($single !== null)
		{
			$view->name_single = $single;
			$view->name_single_code = $single;
		}

		if ($list !== null)
		{
			$view->name_list = $list;
			$view->name_list_code = $list;
		}

		return $view;
	}
}
