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


use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomForm;
use VDM\Joomla\Componentbuilder\Compiler\Builder\History;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OnlyFunctionButtons;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


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
	 * Every modern item toolbar receives optional empty-state descriptions.
	 *
	 * @return  array<string, array{string,int,string,array{description?:string|null},string}>
	 * @since   6.2.0
	 */
	public static function optionalEmptyStateDescriptions(): array
	{
		$cases = [];
		$descriptions = [
			'absent' => [[], ''],
			'null' => [['description' => null], ''],
			'empty' => [['description' => ''], ''],
			'string' => [['description' => 'Manage articles.'], 'Manage articles.'],
		];

		foreach (self::versions() as [$version, $major])
		{
			if ($major < 4)
			{
				continue;
			}

			foreach (['AddToolBar', 'AddModalToolBar'] as $family)
			{
				foreach ($descriptions as $description => [$settings, $expected])
				{
					$cases[$version . ' ' . $family . ' ' . $description] = [
						$version,
						$major,
						$family,
						$settings,
						$expected,
					];
				}
			}
		}

		return $cases;
	}

	/**
	 * Normalize absent and null descriptions without changing generated toolbar content.
	 *
	 * The native provider selects each target over the same real Language and
	 * Content builders, preserving unrelated registrations and source settings.
	 *
	 * @param   string  $version      Target namespace segment.
	 * @param   int     $major        Joomla target major.
	 * @param   string  $family       Item or modal toolbar service suffix.
	 * @param   array   $description  Optional description input.
	 * @param   string  $expected     Expected empty-state language content.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('optionalEmptyStateDescriptions')]
	public function testOptionalEmptyStateDescriptionPreservesLanguageAndToolbarContent(
		string $version,
		int $major,
		string $family,
		array $description,
		string $expected
	): void
	{
		$this->config()->set('joomla_version', $major);
		$this->language()->set('admin', 'COM_DEMO_UNRELATED', 'Keep admin text.');
		$this->language()->set('site', 'COM_DEMO_UNRELATED', 'Keep site text.');
		$contentone = new ContentOne();
		$contentmulti = new ContentMulti();
		$contentone->set('UNRELATED', 'Keep global content.');
		$contentmulti->set('unrelated|BODY', 'Keep view content.');
		$globalContent = $contentone->toArray();
		$viewContent = $contentmulti->toArray();

		$container = new Container();
		(new ArchitectureView())->register($container);
		$container->set('Config', $this->config(), true);
		$container->set('Placeholder', $this->placeholder(), true);
		$container->set('Language', $this->language(), true);
		$container->set('Compiler.Builder.Content.One', $contentone, true);
		$container->set('Compiler.Builder.Content.Multi', $contentmulti, true);
		$container->set('Compiler.Builder.Custom.Form', new CustomForm(), true);
		$container->set('Compiler.Builder.Only.Function.Buttons', new OnlyFunctionButtons(), true);
		$container->set('Compiler.Builder.History', new History(), true);
		$container->set('Compiler.Creator.Permission', $this->permission(), true);
		$container->set('Utilities.Structure', $this->createStub(Structure::class), true);
		$container->set('Registry', new Registry(), true);
		$subject = $container->get('Architecture.AdminView.' . $family);
		$this->assertInstanceOf($this->rendererClass($version, 'AdminView/' . $family), $subject);

		$view = $this->adminView(2);
		unset($view['settings']->description);

		foreach ($description as $key => $value)
		{
			$view['settings']->{$key} = $value;
		}

		$settings = clone $view['settings'];
		$titleKey = $family === 'AddModalToolBar'
			? 'COM_COMPONENTBUILDER__VIEWNAMELANG_READONLY_'
			: 'COM_DEMO_ARTICLE_READONLY';
		$toolbar = "\$this->input->set('hidemainmenu', true);"
			. "\n\t\tJoomla___0c1a176a_304f_433a_8233_37d01ff87815___Power::title(Text::_('"
			. $titleKey . "'), 'article');"
			. "\n\t\tJoomla___0c1a176a_304f_433a_8233_37d01ff87815___Power::cancel('article.cancel', 'JTOOLBAR_CLOSE');";

		$this->assertSame($toolbar, $subject->get($view));
		$this->assertSame([
			'COM_DEMO_UNRELATED' => 'Keep admin text.',
			'COM_DEMO_ARTICLES_EMPTYSTATE_TITLE' => 'No articles have been created yet.',
			'COM_DEMO_ARTICLES_EMPTYSTATE_CONTENT' => $expected,
			'COM_DEMO_ARTICLES_EMPTYSTATE_BUTTON_ADD' => 'Add your first article',
			'COM_DEMO_ARTICLE_READONLY' => 'Article :: Readonly',
		], $this->language()->getTarget('admin'));
		$this->assertSame(['COM_DEMO_UNRELATED' => 'Keep site text.'], $this->language()->getTarget('site'));
		$this->assertEquals($settings, $view['settings']);
		$this->assertSame($globalContent, $contentone->toArray());
		$this->assertSame($viewContent, $contentmulti->toArray());
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
