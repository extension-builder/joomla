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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\AdminViews;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\EditView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * What the edit view of one admin view is made of.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class EditViewTest extends ArchitectureTestCase
{
	/**
	 * What was written for each view.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $multi = null;

	/**
	 * What was written for the component as a whole.
	 *
	 * @var    ContentOne|null
	 * @since  6.1.7
	 */
	private ?ContentOne $one = null;

	/**
	 * A view the component gave a single name is given an edit view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithASingleNameIsGivenAnEditView(): void
	{
		$this->build($this->view('demo'));

		$written = $this->multi->get('demo');

		$this->assertIsArray($written);
		$this->assertNotSame([], $written);
	}

	/**
	 * A view the component gave no single name is given nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoSingleNameIsGivenNothing(): void
	{
		$this->build($this->view(null));

		$this->assertSame([], $this->multi->allActive());
	}

	/**
	 * A view named "null" is treated as having no single name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewNamedNullIsTreatedAsHavingNoSingleName(): void
	{
		$this->build($this->view('null'));

		$this->assertSame([], $this->multi->allActive());
	}

	/**
	 * The modal a view is picked through is given the defaults it needs.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheModalIsGivenTheDefaultsItNeeds(): void
	{
		$this->build($this->view('demo'));

		$written = $this->multi->get('demo');

		$this->assertSame('id', $written['###SQL_TITLE_KEY###']);
		$this->assertSame('name', $written['###SQL_TITLE_COLUMN###']);
	}

	/**
	 * The edit view is told what to do before and after it is built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheEventsAreFiredAroundTheBuild(): void
	{
		$fired = [];
		$event = $this->createStub(
			'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\EventInterface'
		);
		$event->method('trigger')->willReturnCallback(
			static function (string $name, $data = null) use (&$fired): void
			{
				$fired[] = $name;
			}
		);

		$this->build($this->view('demo'), ['event' => $event]);

		$this->assertContains('jcb_ce_onBeforeBuildAdminEditViewContent', $fired);
		$this->assertContains('jcb_ce_onAfterBuildAdminEditViewContent', $fired);
	}

	/**
	 * The api controller and view are given their bodies.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheApiControllerAndViewAreGivenTheirBodies(): void
	{
		$this->build($this->view('demo'));

		$written = $this->multi->get('demo');

		$this->assertStringContainsString("\$name = 'demos';", $written['###API_VIEW_CONTROLLER_GETMODEL###']);
		$this->assertStringContainsString('return parent::getModel($name, $prefix, $config);', $written['###API_VIEW_CONTROLLER_GETMODEL###']);
		$this->assertStringContainsString("\$id = \$this->input->getInt('id', 0);", $written['###API_VIEW_CONTROLLER_RECORDID###']);
		$this->assertStringContainsString('return true;', $written['###API_VIEW_CONTROLLER_ALLOWVIEW###']);
		$this->assertStringContainsString("return \$user->authorise('core.delete', \$this->option);", $written['###API_VIEW_CONTROLLER_ALLOWDELETE###']);
		$this->assertStringContainsString("\t\t'id',", $written['###API_VIEW_JSON_FIELDS###']);
		$this->assertSame('', $written['###API_VIEW_JSON_PERMISSIONS###']);
		$this->assertSame('', $written['###API_VIEW_JSON_PREPAREITEM###']);
	}

	/**
	 * Build the edit view of one admin view.
	 *
	 * @param   array  $view       The view being built.
	 * @param   array  $overrides  What the compiler collected.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function build(array $view, array $overrides = []): void
	{
		$this->multi = new ContentMulti();
		$this->one = new ContentOne();

		$subject = $this->renderer(EditView::class, $overrides + [
			'contentmulti' => $this->multi,
			'contentone' => $this->one
		]);

		$single = 'demo';
		$list = 'demos';
		$subject->build($view, $single, $list);
	}

	/**
	 * A view the compiler collected.
	 *
	 * @param   string|null  $single  Its single name, when it has one.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(?string $single): array
	{
		$settings = new stdClass();
		$settings->name_single_code = 'demo';
		$settings->name_list_code = 'demos';
		$settings->add_fadein = 1;

		if ($single !== null)
		{
			$settings->name_single = $single;
		}

		return ['settings' => $settings];
	}
}
