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


use Joomla\CMS\Application\CMSApplicationInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Layout\View as LayoutView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\HasPermissions;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Layout;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SecondRunAdmin;
use VDM\Joomla\Componentbuilder\Compiler\Builder\TabCounter;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Adminview\Data as AdminviewData;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\CustomTabs;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomTabs as CustomTabsData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LayoutData;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HeaderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Templatelayout\Data as TemplatelayoutData;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * Generated admin edit view body contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedEditBodyRendererTest extends ArchitectureTestCase
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
	 * A view with no registered layout produces no edit body at all.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewWithoutALayoutHasNoEditBody(string $version, int $major): void
	{
		$subject = $this->editBody($version, ['layout' => new Layout()]);
		$view = $this->view();

		$this->assertSame('', $subject->get($view));
	}

	/**
	 * Each target uses its own grid, tab helper and outer form vocabulary.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheTargetGridAndTabVocabularyIsUsed(string $version, int $major): void
	{
		$subject = $this->editBody($version);
		$view = $this->view();

		$code = $subject->get($view);

		if ($major === 3)
		{
			$this->assertStringStartsWith(
				PHP_EOL . '<div class="form-horizontal">',
				$code
			);
			$this->assertStringContainsString("Html::_('bootstrap.startTabSet'", $code);
			$this->assertStringContainsString("Html::_('bootstrap.addTab'", $code);
			$this->assertStringContainsString('class="row-fluid form-horizontal-desktop"', $code);
			$this->assertStringNotContainsString('uitab.', $code);
			$this->assertStringNotContainsString('col-md-', $code);

			return;
		}

		$this->assertStringStartsWith(
			PHP_EOL . '<div class="main-card">',
			$code
		);
		$this->assertStringContainsString("Html::_('uitab.startTabSet'", $code);
		$this->assertStringContainsString("Html::_('uitab.addTab'", $code);
		$this->assertStringContainsString('<div class="row">', $code);
		$this->assertStringNotContainsString('bootstrap.', $code);
		$this->assertStringNotContainsString('class="span', $code);
	}

	/**
	 * The tab set, its token field and the closing container are always emitted.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheEditBodyClosesItsFormContainer(string $version, int $major): void
	{
		$subject = $this->editBody($version);
		$view = $this->view();

		$code = $subject->get($view);

		$this->assertStringContainsString(
			'<input type="hidden" name="task" value="article.edit" />',
			$code
		);
		$this->assertStringContainsString("Html::_('form.token')", $code);
		$this->assertStringEndsWith(PHP_EOL . '</div>', $code);
		// the tab set opens and closes exactly once
		$this->assertSame(1, substr_count($code, '.startTabSet'));
		$this->assertSame(1, substr_count($code, '.endTabSet'));
	}

	/**
	 * The layout of every alignment of every tab reaches the layout builder.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testEachTabAlignmentBuildsItsOwnLayout(string $version, int $major): void
	{
		$built = [];
		$subject = $this->editBody($version, [
			'layoutview' => $this->layoutview($built),
		]);
		$view = $this->view();

		$subject->get($view);

		$this->assertContains(['article', 'details_left', 'layoutitems'], $built);
		$this->assertContains(['article', 'details_fullwidth', 'layoutfull'], $built);
	}

	/**
	 * The permissions tab renders the access control of its target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testThePermissionsTabFollowsTheTarget(string $version, int $major): void
	{
		$haspermissions = new HasPermissions();
		$haspermissions->set('article', true);

		$subject = $this->editBody($version, ['haspermissions' => $haspermissions]);
		$view = $this->view();

		$code = $subject->get($view);

		$this->assertStringContainsString("\$this->canDo->get('core.admin')", $code);

		if ($major === 3)
		{
			$this->assertStringContainsString('<fieldset class="adminform">', $code);
			$this->assertStringContainsString(
				"\$this->form->getFieldset('accesscontrol')",
				$code
			);
			$this->assertStringNotContainsString("getInput('rules')", $code);

			return;
		}

		$this->assertStringContainsString(
			'<fieldset id="fieldset-rules" class="options-form">',
			$code
		);
		$this->assertStringContainsString("\$this->form->getInput('rules')", $code);
		$this->assertStringNotContainsString("getFieldset('accesscontrol')", $code);
	}

	/**
	 * A view named component never gets a permissions tab.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheComponentViewNeverGetsAPermissionsTab(): void
	{
		$haspermissions = new HasPermissions();
		$haspermissions->set('component', true);

		$subject = $this->editBody('JoomlaSix', [
			'haspermissions' => $haspermissions,
			'layout' => $this->layout('component'),
			'tabcounter' => $this->tabcounter('component'),
		]);
		$view = $this->view('component');

		$this->assertStringNotContainsString(
			"\$this->canDo->get('core.admin')",
			$subject->get($view)
		);
	}

	/**
	 * A linked view tab defers its own build to the second admin run.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testALinkedViewTabIsQueuedForTheSecondRun(): void
	{
		$secondrunadmin = new SecondRunAdmin();

		$registry = new Registry();
		$registry->set('builder.linked_admin_views.article', [
			[
				'adminview' => 'guid-of-linked-view',
				'tab' => 1,
				'key' => 'article_id',
				'parentkey' => 'id',
				'addnew' => 1,
			],
		]);

		$linkedViewData = new \stdClass();
		$linkedViewData->name_single = 'Comment';
		$adminviewdata = $this->createStub(AdminviewData::class);
		$adminviewdata->method('get')->willReturn($linkedViewData);

		$subject = $this->editBody('JoomlaSix', [
			'secondrunadmin' => $secondrunadmin,
			'registry' => $registry,
			'adminviewdata' => $adminviewdata,
			// the linked view owns tab 1, so no field layout exists for it
			'layout' => $this->layout(),
			'tabcounter' => new TabCounter(),
		]);
		$view = $this->view();
		$view['settings']->tabs = [1 => 'Details'];

		$subject->get($view);

		$queued = $secondrunadmin->allActive();

		$this->assertArrayHasKey('setLinkedView', $queued);
		$this->assertCount(1, $queued['setLinkedView']);
		$this->assertSame('guid-of-linked-view', $queued['setLinkedView'][0]['viewGuid']);
		$this->assertSame('article', $queued['setLinkedView'][0]['nameSingleCode']);
		$this->assertSame('article_id', $queued['setLinkedView'][0]['key']);
		$this->assertSame('id', $queued['setLinkedView'][0]['parentKey']);
		$this->assertSame(1, $queued['setLinkedView'][0]['addNewButon']);
	}

	/**
	 * Build a view definition for the edit body.
	 *
	 * @param   string  $nameSingleCode  The single view name.
	 *
	 * @return  array{settings: \stdClass}
	 * @since   6.1.7
	 */
	private function view(string $nameSingleCode = 'article'): array
	{
		$settings = new \stdClass();
		$settings->name_single_code = $nameSingleCode;
		$settings->tabs = [];

		return ['settings' => $settings];
	}

	/**
	 * Build a layout registry with a left and a fullwidth alignment.
	 *
	 * @param   string  $nameSingleCode  The single view name.
	 *
	 * @return  Layout
	 * @since   6.1.7
	 */
	private function layout(string $nameSingleCode = 'article'): Layout
	{
		$layout = new Layout();
		$layout->set($nameSingleCode, [
			'Details' => [
				1 => [0 => 'name'],
				3 => [0 => 'description'],
			],
		]);

		return $layout;
	}

	/**
	 * Build a tab counter holding one tab.
	 *
	 * @param   string  $nameSingleCode  The single view name.
	 *
	 * @return  TabCounter
	 * @since   6.1.7
	 */
	private function tabcounter(string $nameSingleCode = 'article'): TabCounter
	{
		$tabcounter = new TabCounter();
		$tabcounter->set($nameSingleCode . '.1', 'Details');

		return $tabcounter;
	}

	/**
	 * Build a real layout view over a structure double that records builds.
	 *
	 * The layout view is final, so it is constructed for real and only its
	 * structure boundary is doubled. What the structure is asked to build is
	 * exactly which layout files the edit body decided to emit.
	 *
	 * @param   array  $built  Receives each build call.
	 *
	 * @return  LayoutView
	 * @since   6.1.7
	 */
	private function layoutview(array &$built): LayoutView
	{
		$structure = $this->createStub(Structure::class);
		$structure->method('build')
			->willReturnCallback(
				static function (array $target, string $type, $name = null)
					use (&$built): bool
				{
					$built[] = [reset($target), $name, $type];

					return true;
				}
			);

		return $this->realLayoutView($structure);
	}

	/**
	 * Build a real layout view with no template-layout override in play.
	 *
	 * @param   Structure|null  $structure  The structure boundary.
	 *
	 * @return  LayoutView
	 * @since   6.1.7
	 */
	private function realLayoutView(?Structure $structure = null): LayoutView
	{
		if ($structure === null)
		{
			$structure = $this->createStub(Structure::class);
			$structure->method('build')->willReturn(true);
		}

		$templatelayout = $this->createStub(TemplatelayoutData::class);
		$templatelayout->method('set')->willReturn(false);

		return new LayoutView(
			$this->config(),
			$this->placeholder(),
			new ContentMulti(),
			new LayoutData(),
			$templatelayout,
			$this->createStub(HeaderInterface::class),
			$structure
		);
	}

	/**
	 * Create the edit body of one target with real registries.
	 *
	 * @param   string  $version    Target namespace segment.
	 * @param   array   $overrides  Constructor dependency overrides.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function editBody(string $version, array $overrides = []): object
	{
		// only Joomla 3 keeps its own grid and access control markup
		$class = $this->targetClass($version, 'AdminView\\EditBody', ['JoomlaThree']);

		return $this->renderer($class, $overrides + [
			'layout' => $this->layout(),
			'tabcounter' => $this->tabcounter(),
			'secondrunadmin' => new SecondRunAdmin(),
			'haspermissions' => new HasPermissions(),
			'registry' => new Registry(),
			'layoutview' => $this->realLayoutView(),
			'customtabs' => new CustomTabs(new CustomTabsData()),
			'app' => $this->createStub(CMSApplicationInterface::class),
		]);
	}
}
