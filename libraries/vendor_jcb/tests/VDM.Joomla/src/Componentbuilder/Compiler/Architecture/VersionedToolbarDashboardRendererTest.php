<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesNamespace;


/**
 * Toolbar and dashboard generated-output contracts across Joomla targets.
 *
 * @since  6.1.6
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedToolbarDashboardRendererTest extends ArchitectureTestCase
{
	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.6
	 */
	public static function versions(): array
	{
		return VersionedPermissionRendererTest::versions();
	}

	/**
	 * Dashboard implementations whose default render path is warning-free.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.6
	 */
	public static function workingDashboardVersions(): array
	{
		return array_filter(
			self::versions(),
			static fn (array $version): bool => $version[1] >= 4
		);
	}

	/**
	 * Custom-admin list implementations without a title-variable regression.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.6
	 */
	public static function workingCustomAdminListVersions(): array
	{
		return array_filter(
			self::versions(),
			static fn (array $version): bool => $version[1] <= 5
		);
	}

	/**
	 * Toolbar families with their valid code-name setting.
	 *
	 * @return  array<string, array{string,string}>
	 * @since   6.1.6
	 */
	public static function toolbarFamilies(): array
	{
		return [
			'admin modal item' => ['AdminView/AddModalToolBar', 'name_single_code'],
			'admin item' => ['AdminView/AddToolBar', 'name_single_code'],
			'admin list' => ['AdminViews/AddToolBar', 'name_single_code'],
			'custom admin item' => ['CustomAdminView/AddToolBar', 'code'],
			'custom admin list' => ['CustomAdminViews/AddToolBar', 'code'],
			'site item' => ['SiteView/AddToolBar', 'code'],
		];
	}

	/**
	 * Protect the no-context guard on every versioned toolbar family.
	 *
	 * @param   string  $family   Renderer family.
	 * @param   string  $codeKey  Valid code-name key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('toolbarFamilies')]
	public function testEveryToolbarFamilyRejectsMissingViewIdentity(string $family, string $codeKey): void
	{
		foreach (self::versions() as [$version])
		{
			$subject = $this->renderer($this->rendererClass($version, $family));

			$this->assertSame('', $subject->get(['settings' => (object) []]));
		}
	}

	/**
	 * Protect modal readonly language registration and identity API selection.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModalToolbarBuildsReadonlyTitleAndCloseAction(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'AdminView/AddModalToolBar'));
		$code = $subject->get($this->adminView(2));

		$this->assertStringContainsString("Text::_('COM_COMPONENTBUILDER__VIEWNAMELANG_READONLY_')", $code);
		$this->assertStringContainsString("article.cancel', 'JTOOLBAR_CLOSE'", $code);
		$this->assertSame(
			'Article :: Readonly',
			$this->language()->get('admin', 'COM_DEMO_ARTICLE_READONLY')
		);

		if ($major === 3)
		{
			$this->assertStringContainsString('getApplication()->input->set(', $code);
		}
		else
		{
			$this->assertStringContainsString('$this->input->set(', $code);
			$this->assertSame(
				'No articles have been created yet.',
				$this->language()->get('admin', 'COM_DEMO_ARTICLES_EMPTYSTATE_TITLE')
			);
		}
	}

	/**
	 * Protect item readonly toolbar output and site-toolbar initialization.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testAdminItemToolbarPreservesReadonlyAndSiteInitialization(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'AdminView/AddToolBar'));
		$code = $subject->get($this->adminView(2));
		$site = $subject->initSite();

		$this->assertStringContainsString("Text::_('COM_DEMO_ARTICLE_READONLY')", $code);
		$this->assertStringContainsString("article.cancel', 'JTOOLBAR_CLOSE'", $code);
		$this->assertStringContainsString("set('hidemainmenu', true)", $code);

		if ($major <= 4)
		{
			$this->assertStringContainsString('getInstance();', $site);

			if ($major === 3)
			{
				$this->assertStringContainsString('getApplication()->input', $code);
			}
			else
			{
				$this->assertStringContainsString('$this->input->set(', $code);
			}
		}
		else
		{
			$this->assertStringContainsString('$this->getDocument()->getToolbar();', $site);
			$this->assertStringContainsString('$this->input->set(', $code);
		}
	}

	/**
	 * Protect list toolbar title, icon, and modern toolbar acquisition.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testAdminListToolbarPreservesTitleAndTargetToolbarApi(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'AdminViews/AddToolBar'));
		$view = $this->adminView();
		$view['icomoon'] = 'stack';
		$code = $subject->get($view);

		$this->assertStringContainsString("_('COM_DEMO_ARTICLES')", $code);
		$this->assertStringContainsString("'stack'", $code);

		if ($major >= 5)
		{
			$this->assertStringContainsString('$this->getDocument()->getToolbar(', $code);
			$this->assertStringContainsString("dropdownButton('status-group')", $code);
		}
		else
		{
			$this->assertStringNotContainsString('$this->getDocument()->getToolbar(', $code);
			$this->assertStringNotContainsString("dropdownButton('status-group')", $code);
		}
	}

	/**
	 * Protect singular custom-admin toolbar titles and preference buttons.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testCustomAdminItemToolbarPreservesTitleAndPreferences(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'CustomAdminView/AddToolBar'));
		$view = $this->customView('article');
		$code = $subject->get($view);

		$this->assertStringContainsString("_('COM_DEMO_ARTICLE')", $code);
		$this->assertStringContainsString("'article'", $code);
		$this->assertStringContainsString("preferences('com_demo')", $code);

		if ($major === 3)
		{
			$this->assertStringContainsString('$this->app->input->set(', $code);
		}
		else
		{
			$this->assertStringContainsString('$this->input->set(', $code);
		}
	}

	/**
	 * Protect plural custom-admin toolbar titles and preference buttons.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('workingCustomAdminListVersions')]
	public function testCustomAdminListToolbarPreservesTitleAndPreferences(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'CustomAdminViews/AddToolBar'));
		$view = $this->customView('articles');
		$code = $subject->get($view);

		$this->assertStringContainsString("_('COM_DEMO_ARTICLES')", $code);
		$this->assertStringContainsString("'articles'", $code);
		$this->assertStringContainsString("preferences('com_demo')", $code);
	}

	/**
	 * Protect site-view actions, help lookup, and target-specific initialization.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testSiteToolbarPreservesTitleAndModernToolbarInitialization(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'SiteView/AddToolBar'));
		$code = $subject->get($this->customView('article'));

		$this->assertStringContainsString("custom('article.dashboard'", $code);
		$this->assertStringContainsString("getHelpUrl('article')", $code);

		if ($major === 3)
		{
			$this->assertStringNotContainsString('$this->getDocument()->getToolbar();', $code);
			$this->assertStringContainsString('$this->toolbar = Toolbar::getInstance();', $code);
		}
		elseif ($major === 4)
		{
			$this->assertStringNotContainsString('$this->getDocument()->getToolbar();', $code);
			$this->assertStringContainsString('Power::getInstance();', $code);
		}
		else
		{
			$this->assertStringContainsString('$this->getDocument()->getToolbar();', $code);
		}
	}

	/**
	 * Document the Joomla 6 custom-admin list title-variable regression.
	 *
	 * `buildTitle()` receives `$langView` but interpolates the undefined
	 * `$langViews`, leaving the title language key empty.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testJoomlaSixCustomAdminListToolbarUsesItsTitleArgument(): void
	{
		$subject = $this->renderer(
			$this->rendererClass('JoomlaSix', 'CustomAdminViews/AddToolBar')
		);
		$code = $subject->get($this->customView('articles'));

		$this->assertStringContainsString("_('COM_DEMO_ARTICLES')", $code);
	}

	/**
	 * Protect the target-version dashboard grid and placeholder structure.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('workingDashboardVersions')]
	public function testDashboardPreservesTargetSpecificGridAndContentPlaceholders(string $version, int $major): void
	{
		$subject = $this->renderer($this->rendererClass($version, 'Dashboard/View'));
		$code = $subject->get();

		$this->assertStringStartsWith(PHP_EOL, $code);
		$this->assertStringContainsString("\$this->loadTemplate('main')", $code);
		$this->assertStringContainsString("\$this->loadTemplate('vdm')", $code);

		$expected = match ($major)
		{
			4 => ['row', 'col-md-9', 'col-md-3'],
			5 => ['row g-4', 'col-12 col-xl-9', 'col-12 col-xl-3'],
			6 => ['row g-4 align-items-start', 'col-12 col-xxl-9', 'col-12 col-xxl-3'],
		};

		foreach ($expected as $class)
		{
			$this->assertStringContainsString($class, $code);
		}

		if ($major === 6)
		{
			$this->assertStringContainsString('jcb-dashboard__content', $code);
			$this->assertStringContainsString('jcb-dashboard__sidebar', $code);
		}
	}

	/**
	 * Document the Joomla 3 dashboard state-key regression.
	 *
	 * The implementation stores `mainAccordianName` but reads
	 * `mainAccordionName`, so even the default path emits a warning.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testJoomlaThreeDashboardDefaultLayoutIsWarningFree(): void
	{
		$subject = $this->renderer($this->rendererClass('JoomlaThree', 'Dashboard/View'));
		$code = $subject->get();

		$this->assertStringContainsString('row-fluid', $code);
		$this->assertStringContainsString('span9', $code);
		$this->assertStringContainsString('span3', $code);
		$this->assertStringContainsString("\$this->loadTemplate('main')", $code);
		$this->assertStringContainsString("\$this->loadTemplate('vdm')", $code);
	}

	/**
	 * Build a complete admin-view fixture.
	 *
	 * @param   int  $type  View type.
	 *
	 * @return  array{settings:object}
	 * @since   6.1.6
	 */
	private function adminView(int $type = 1): array
	{
		return [
			'settings' => (object) [
				'name_single_code' => 'article',
				'name_list_code' => 'articles',
				'name_single' => 'Article',
				'name_list' => 'Articles',
				'description' => 'Manage articles.',
				'type' => $type,
				'view_toolbar' => '',
				'views_toolbar' => '',
				'add_custom_button' => 0,
			],
		];
	}

	/**
	 * Build a complete custom/site view fixture.
	 *
	 * @param   string  $code  View code name.
	 *
	 * @return  array{settings:object,icomoon:string}
	 * @since   6.1.6
	 */
	private function customView(string $code): array
	{
		return [
			'settings' => (object) [
				'code' => $code,
				'name_single_code' => 'article',
				'name_list_code' => 'articles',
				'view_toolbar' => '',
				'add_custom_button' => 0,
			],
			'icomoon' => 'stack',
		];
	}

	/**
	 * Build a versioned renderer class name.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   string  $family   Slash-delimited renderer family.
	 *
	 * @return  class-string
	 * @since   6.1.6
	 */
	private function rendererClass(string $version, string $family): string
	{
		return 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\'
			. $version . '\\' . str_replace('/', '\\', $family);
	}
}
