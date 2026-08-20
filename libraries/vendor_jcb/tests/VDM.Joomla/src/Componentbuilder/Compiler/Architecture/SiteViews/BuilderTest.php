<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    20th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\SiteViews;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\SiteViews\Builder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Everything one site view of the component adds to the compiler.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\SiteViews')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class BuilderTest extends ArchitectureTestCase
{
	/**
	 * What was written for the view.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $multi = null;

	/**
	 * What was written once for the whole site.
	 *
	 * @var    ContentOne|null
	 * @since  6.1.7
	 */
	private ?ContentOne $one = null;

	/**
	 * The view is named to everything built for it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheViewIsNamedToEverythingBuiltForIt(): void
	{
		$this->build($this->view());

		$written = $this->multi->get('looker');

		$this->assertSame('Looker', $written['###SView###']);
		$this->assertSame('looker', $written['###sview###']);
		$this->assertSame('Looker', $written['###SViews###']);
		$this->assertSame('looker', $written['###sviews###']);
	}

	/**
	 * The view names itself to every placeholder the site files read.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheViewNamesItselfToEveryPlaceholderTheSiteFilesRead(): void
	{
		$this->build($this->view());

		$this->assertSame('Looker', $this->placeholder()->get('###SView###'));
		$this->assertSame('looker', $this->placeholder()->get('###sview###'));
		$this->assertSame('LOOKER', $this->placeholder()->get('###SVIEW###'));
		$this->assertSame('Looker', $this->placeholder()->get('###SViews###'));
		$this->assertSame('looker', $this->placeholder()->get('###sviews###'));
		$this->assertSame('LOOKER', $this->placeholder()->get('###SVIEWS###'));
	}

	/**
	 * The view the component marked default is the one the site opens on.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheViewMarkedDefaultIsTheOneTheSiteOpensOn(): void
	{
		$view = $this->view();
		$view['default_view'] = 1;

		$this->build($view);

		$this->assertSame(
			'looker', $this->one->allActive()['###SITE_DEFAULT_VIEW###']
		);
	}

	/**
	 * A view the component did not mark default leaves the site entry alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewNotMarkedDefaultLeavesTheSiteEntryAlone(): void
	{
		$this->build($this->view());

		$this->assertArrayNotHasKey(
			'###SITE_DEFAULT_VIEW###', $this->one->allActive()
		);
	}

	/**
	 * A view given a menu is written into the site menu xml.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewGivenAMenuIsWrittenIntoTheSiteMenuXml(): void
	{
		$view = $this->view();
		$view['menu'] = 1;

		$this->build($view);

		$this->assertArrayHasKey(
			'###SITE_MENU_XML###', $this->multi->get('looker')
		);
	}

	/**
	 * A view given no menu is left out of the site menu xml.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewGivenNoMenuIsLeftOutOfTheSiteMenuXml(): void
	{
		$this->build($this->view());

		$this->assertArrayNotHasKey(
			'###SITE_MENU_XML###', $this->multi->get('looker')
		);
	}

	/**
	 * Every site file the view fills is written for it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEverySiteFileTheViewFillsIsWrittenForIt(): void
	{
		$this->build($this->view());

		$written = $this->multi->get('looker');

		foreach ([
			'###SITE_CUSTOM_METHODS###',
			'###SITE_DIPLAY_METHOD###',
			'###SITE_EXTRA_DIPLAY_METHODS###',
			'###SITE_CODE_BODY###',
			'###SITE_BODY###',
			'###SITE_ADDTOOLBAR###',
			'###SITE_TOP_FORM###',
			'###SITE_BOTTOM_FORM###'
		] as $key)
		{
			$this->assertArrayHasKey($key, $written);
		}
	}

	/**
	 * The router is told about the view once for each thing it must route.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheRouterIsToldAboutTheView(): void
	{
		$this->build($this->view());

		$written = $this->one->allActive();

		$this->assertArrayHasKey('###ROUTEHELPER###', $written);
		$this->assertStringContainsString(
			"case 'looker':", $written['###ROUTER_PARSE_SWITCH###']
		);
		$this->assertSame(
			'$view === \'looker\'', $written['###ROUTER_BUILD_VIEWS###']
		);
	}

	/**
	 * The view names itself to the site language.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheViewNamesItselfToTheSiteLanguage(): void
	{
		$this->build($this->view());

		$this->assertSame(
			'Looker', $this->language()->get('site', 'COM_DEMO_LOOKER')
		);
		$this->assertSame(
			'What it looks at',
			$this->language()->get('site', 'COM_DEMO_LOOKER_DESC')
		);
	}

	/**
	 * Build one site view.
	 *
	 * @param   array  $view  The view the component was given.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function build(array $view): void
	{
		$this->multi = new ContentMulti();
		$this->one = new ContentOne();

		$subject = $this->renderer(Builder::class, [
			'contentmulti' => $this->multi,
			'contentone' => $this->one,
			'config' => $this->config(),
			'placeholder' => $this->placeholder(),
			'language' => $this->language()
		]);

		$subject->build($view);
	}

	/**
	 * A site view the compiler collected.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(): array
	{
		$settings = new stdClass();
		$settings->code = 'looker';
		$settings->Code = 'Looker';
		$settings->CODE = 'LOOKER';
		$settings->name = 'Looker';
		$settings->description = 'What it looks at';
		$settings->php_controller = '//';
		$settings->main_get = new stdClass();
		$settings->main_get->gettype = 1;
		$settings->main_get->main_get = [];
		$settings->add_php_view = 0;
		$settings->add_php_jview = 0;
		$settings->add_php_jview_display = 0;
		$settings->add_php_document = 0;
		$settings->add_css = 0;
		$settings->add_css_document = 0;
		$settings->add_javascript_file = 0;
		$settings->add_js_document = 0;
		$settings->default = '';

		return ['settings' => $settings];
	}
}
