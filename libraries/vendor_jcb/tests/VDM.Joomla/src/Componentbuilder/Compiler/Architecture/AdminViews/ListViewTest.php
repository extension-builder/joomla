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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\EximportView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ImportCustomScripts;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * What the list view of one admin view is made of.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ListViewTest extends ArchitectureTestCase
{
	/**
	 * What was written for each view.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $multi = null;

	/**
	 * Which views the component allows export and import on.
	 *
	 * @var    EximportView|null
	 * @since  6.1.7
	 */
	private ?EximportView $eximport = null;

	/**
	 * Which views brought their own import scripts.
	 *
	 * @var    ImportCustomScripts|null
	 * @since  6.1.7
	 */
	private ?ImportCustomScripts $customImport = null;

	/**
	 * A view the component gave a list name is given a list view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAListNameIsGivenAListView(): void
	{
		$this->build($this->view('demos'));

		$written = $this->multi->get('demos');

		$this->assertIsArray($written);
		$this->assertNotSame([], $written);
	}

	/**
	 * A view the component gave no list name is given nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoListNameIsGivenNothing(): void
	{
		$this->build($this->view(null));

		$this->assertSame([], $this->multi->allActive());
	}

	/**
	 * A view named "null" is treated as having no list name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewNamedNullIsTreatedAsHavingNoListName(): void
	{
		$this->build($this->view('null'));

		$this->assertSame([], $this->multi->allActive());
	}

	/**
	 * A view the component opened a port on is recorded as allowing both.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAPortIsRecordedAsAllowingBoth(): void
	{
		$this->build(['port' => 1] + $this->view('demos'));

		$this->assertTrue($this->eximport->get('demos'));
		$this->assertNull($this->customImport->get('demos'));
	}

	/**
	 * A view with import scripts of its own is recorded as bringing them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithItsOwnImportScriptsIsRecordedAsBringingThem(): void
	{
		$this->build($this->view('demos', ['add_custom_import' => 1]));

		$this->assertTrue($this->eximport->get('demos'));
		$this->assertTrue($this->customImport->get('demos'));
	}

	/**
	 * A view the component opened no port on is recorded as allowing neither.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoPortIsRecordedAsAllowingNeither(): void
	{
		$this->build($this->view('demos'));

		$this->assertFalse($this->eximport->get('demos'));
		$this->assertNull($this->customImport->get('demos'));
	}

	/**
	 * The list view is told what to do before and after it is built.
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

		$this->build($this->view('demos'), ['event' => $event]);

		$this->assertContains('jcb_ce_onBeforeBuildAdminListViewContent', $fired);
		$this->assertContains('jcb_ce_onAfterBuildAdminListViewContent', $fired);
	}

	/**
	 * The api controller and view are given their bodies.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheApiControllerAndViewAreGivenTheirBodies(): void
	{
		$this->build($this->view('demos'));

		$written = $this->multi->get('demos');

		$this->assertStringContainsString("\$name = 'demos';", $written['###API_VIEWS_CONTROLLER_GETMODEL###']);
		$this->assertStringContainsString("\$this->modelState->set('filter.search'", $written['###API_VIEWS_CONTROLLER_DISPLAYLIST###']);
		$this->assertStringContainsString('return parent::displayList();', $written['###API_VIEWS_CONTROLLER_DISPLAYLIST###']);
		$this->assertStringContainsString("\t\t'id',", $written['###API_VIEWS_JSON_FIELDS###']);
		$this->assertSame('', $written['###API_VIEWS_JSON_PERMISSIONS###']);
		$this->assertSame('', $written['###API_VIEWS_JSON_PREPAREITEM###']);
		$this->assertStringContainsString("\t\t'modified_by',", $written['###API_VIEWS_JSON_RELATIONSHIP###']);
	}

	/**
	 * Build the list view of one admin view.
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
		$this->eximport = new EximportView();
		$this->customImport = new ImportCustomScripts();

		$subject = $this->renderer(ListView::class, $overrides + [
			'contentmulti' => $this->multi,
			'contentone' => new ContentOne(),
			'eximportview' => $this->eximport,
			'importcustomscriptsbuilder' => $this->customImport
		]);

		$single = 'demo';
		$list = 'demos';
		$subject->build($view, $single, $list);
	}

	/**
	 * A view the compiler collected.
	 *
	 * @param   string|null  $list      Its list name, when it has one.
	 * @param   array        $settings  What else the view was given.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(?string $list, array $settings = []): array
	{
		$s = new stdClass();
		$s->name_single_code = 'demo';
		$s->name_list_code = 'demos';

		if ($list !== null)
		{
			$s->name_list = $list;
		}

		foreach ($settings as $key => $value)
		{
			$s->{$key} = $value;
		}

		// every admin view the compiler collects carries its icon
		return ['settings' => $s, 'icomoon' => 'demo-icon'];
	}
}
