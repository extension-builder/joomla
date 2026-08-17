<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    16th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Adminview\DefaultOrdering;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AdminFilterType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ViewsDefaultOrdering;
use VDM\Joomla\Componentbuilder\Compiler\Field\DatabaseName;


/**
 * Generated view display-method contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedDisplayMethodRendererTest extends ArchitectureTestCase
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
	 * The list ordering clause is emitted for every target without filters.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAdminListDisplayAlwaysEmitsTheOrderingClause(string $version, int $major): void
	{
		$subject = $this->renderer(
			$this->adminViewsClass($version),
			[
				'adminfiltertype' => new AdminFilterType(),
				'defaultordering' => $this->defaultOrdering(),
			]
		);
		$code = $subject->get('articles');

		$this->assertSame(
			PHP_EOL . "\t\t// Add the list ordering clause." . PHP_EOL
			. "\t\t\$this->listOrder = \$this->escape(\$this->state->get('list.ordering', 'a.id'));" . PHP_EOL
			. "\t\t\$this->listDirn = \$this->escape(\$this->state->get('list.direction', 'DESC'));",
			$code
		);
		$this->assertStringNotContainsString('filterForm', $code);
	}

	/**
	 * The top-bar filter type selects the target's filter-form retrieval.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAdminListDisplayFilterFormFollowsTheTarget(string $version, int $major): void
	{
		$filterType = new AdminFilterType();
		$filterType->set('articles', 2);

		$subject = $this->renderer(
			$this->adminViewsClass($version),
			[
				'adminfiltertype' => $filterType,
				'defaultordering' => $this->defaultOrdering(),
			]
		);
		$code = $subject->get('articles');

		if ($major === 3)
		{
			$this->assertStringContainsString(
				"\t\t\$this->filterForm = \$this->get('FilterForm');",
				$code
			);
			$this->assertStringContainsString(
				"\t\t\$this->activeFilters = \$this->get('ActiveFilters');",
				$code
			);
			$this->assertStringContainsString('// Load the filter form from xml.', $code);
			$this->assertStringNotContainsString('searchtools', $code);

			return;
		}

		$this->assertStringContainsString(
			"\t\t\$this->filterForm = \$model->getFilterForm();",
			$code
		);
		$this->assertStringContainsString(
			"\t\t\$this->activeFilters = \$model->getActiveFilters();",
			$code
		);
		$this->assertStringContainsString('for searchtools.', $code);
	}

	/**
	 * Single-item retrieval and the error check follow the target model API.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testCustomViewDisplayRetrievalFollowsTheTarget(string $version, int $major): void
	{
		$view = $this->singleView();

		$subject = $this->renderer($this->customViewClass($version));
		$code = $subject->get($view);

		if ($major === 3)
		{
			$this->assertStringContainsString("\$this->item = \$this->get('Item');", $code);
			$this->assertStringContainsString("if (count(\$errors = \$model->get('Errors')))", $code);

			return;
		}

		$this->assertStringContainsString('$this->item = $model->getItem();', $code);
		$this->assertStringContainsString('if (count($errors = $model->getErrors()))', $code);
	}

	/**
	 * List retrieval and pagination follow the target model API.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testCustomViewDisplayListRetrievalFollowsTheTarget(string $version, int $major): void
	{
		$view = $this->singleView();
		$view['settings']->main_get->gettype = 2;
		$view['settings']->main_get->pagination = 1;

		$subject = $this->renderer($this->customViewClass($version));
		$code = $subject->get($view);

		if ($major === 3)
		{
			$this->assertStringContainsString("\$this->items = \$this->get('Items');", $code);
			$this->assertStringContainsString("\$this->pagination = \$this->get('Pagination');", $code);

			return;
		}

		$this->assertStringContainsString('$this->items = $model->getItems();', $code);
		$this->assertStringContainsString('$this->pagination = $model->getPagination();', $code);
	}

	/**
	 * Custom Dynamic Get retrieval follows the target model API.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testCustomViewDisplayCustomGetFollowsTheTarget(string $version, int $major): void
	{
		$view = $this->singleView();
		$custom = new \stdClass();
		$custom->getcustom = 'getSideMenu';
		$view['settings']->custom_get = [$custom];

		$subject = $this->renderer($this->customViewClass($version));
		$code = $subject->get($view);

		if ($major === 3)
		{
			$this->assertStringContainsString("\$this->sidemenu = \$this->get('SideMenu');", $code);

			return;
		}

		$this->assertStringContainsString('$this->sidemenu = $model->getSideMenu();', $code);
	}

	/**
	 * Content plugin events use the legacy dispatcher only up to Joomla 4.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testCustomViewDisplayPluginEventsFollowTheTarget(string $version, int $major): void
	{
		$view = $this->singleView();
		$view['settings']->main_get->plugin_events = ['onContentAfterTitle'];

		$subject = $this->renderer($this->customViewClass($version));
		$code = $subject->get($view);

		$this->assertStringContainsString(
			'Super_' . '__91004529_94a9_4590_b842_e7c6b624ecf5___Power::check($this->item)',
			$code
		);
		$this->assertStringContainsString(
			'$this->item->event->onContentAfterTitle = trim(implode("\n", $results));',
			$code
		);

		if ($major === 3 || $major === 4)
		{
			$this->assertStringContainsString(
				'$dispatcher = JEventDispatcher::getInstance();',
				$code
			);
			$this->assertStringContainsString(
				"\$results = \$dispatcher->trigger('onContentAfterTitle', array('com_demo.article', "
				. "&\$this->item, &\$params, 0));",
				$code
			);
			$this->assertStringNotContainsString('getDispatcher()->dispatch', $code);

			return;
		}

		$this->assertStringNotContainsString('JEventDispatcher', $code);
		$this->assertStringContainsString(
			"\$results = \$this->getDispatcher()->dispatch('onContentAfterTitle',",
			$code
		);
		$this->assertStringContainsString(
			'new Joomla__' . '_fa9c1320_a115_452a_a0a8_534fcdea490b___Power(',
			$code
		);
		$this->assertStringContainsString("'context' => 'demo.article',", $code);
	}

	/**
	 * The onContentPrepare event is deliberately not triggered in the view.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomViewDisplaySkipsContentPrepare(): void
	{
		$view = $this->singleView();
		$view['settings']->main_get->plugin_events = ['onContentPrepare'];

		$subject = $this->renderer($this->customViewClass('JoomlaSix'));
		$code = $subject->get($view);

		$this->assertStringContainsString('// Process the content plugins.', $code);
		$this->assertStringNotContainsString('onContentPrepare', $code);
	}

	/**
	 * Only Joomla 3 sets the document from a custom admin view.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testCustomAdminViewDocumentIsJoomlaThreeOnly(string $version, int $major): void
	{
		$this->config()->set('build_target', 'custom_admin');

		$subject = $this->renderer($this->customViewClass($version));
		$code = $subject->get($this->singleView());

		$this->assertStringContainsString("if (\$this->getLayout() !== 'modal')", $code);
		$this->assertStringContainsString('$this->addToolBar();', $code);
		$this->assertStringNotContainsString('$this->_prepareDocument();', $code);

		if ($major === 3)
		{
			$this->assertStringContainsString('$this->setDocument();', $code);

			return;
		}

		$this->assertStringNotContainsString('$this->setDocument();', $code);
	}

	/**
	 * A site build target prepares the toolbar and the html document.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSiteViewDisplayPreparesToolbarAndDocument(): void
	{
		$this->config()->set('build_target', 'site');

		$subject = $this->renderer($this->customViewClass('JoomlaSix'));
		$code = $subject->get($this->singleView());

		$this->assertStringContainsString('$this->addToolBar();', $code);
		$this->assertStringContainsString('$this->_prepareDocument();', $code);
		$this->assertStringNotContainsString("if (\$this->getLayout() !== 'modal')", $code);
	}

	/**
	 * A view without a main Dynamic Get produces no display body.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomViewDisplayIsEmptyWithoutAMainGet(): void
	{
		$view = ['settings' => new \stdClass()];

		$subject = $this->renderer($this->customViewClass('JoomlaSix'));

		$this->assertSame('', $subject->get($view));
	}

	/**
	 * Create the real default-ordering service on its fallback path.
	 *
	 * No view declares an admin ordering, so the service returns its
	 * documented `a.id` / `DESC` default without consulting field metadata.
	 *
	 * @return  DefaultOrdering
	 * @since   6.1.7
	 */
	private function defaultOrdering(): DefaultOrdering
	{
		return new DefaultOrdering(
			new ViewsDefaultOrdering(),
			$this->createStub(DatabaseName::class)
		);
	}

	/**
	 * Create a single-item custom view definition.
	 *
	 * @return  array{settings: \stdClass}
	 * @since   6.1.7
	 */
	private function singleView(): array
	{
		$mainGet = new \stdClass();
		$mainGet->gettype = 1;
		$mainGet->pagination = 0;
		$mainGet->plugin_events = [];

		$settings = new \stdClass();
		$settings->main_get = $mainGet;
		$settings->context = 'article';
		$settings->add_php_jview_display = 0;

		return ['settings' => $settings];
	}

	/**
	 * Build a versioned admin list display-method class name.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  class-string
	 * @since   6.1.7
	 */
	private function adminViewsClass(string $version): string
	{
		return 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\'
			. $version . '\\AdminViews\\DisplayMethod';
	}

	/**
	 * Build a versioned custom view display-method class name.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  class-string
	 * @since   6.1.7
	 */
	private function customViewClass(string $version): string
	{
		return 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\'
			. $version . '\\CustomView\\DisplayMethod';
	}
}
