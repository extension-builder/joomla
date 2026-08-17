<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    15th August, 2026
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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Menu\AdminView as MenuAdminView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FrontendParams;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Request;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * Generated view menu metadata contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedMenuRendererTest extends ArchitectureTestCase
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
	 * The admin view site menu writes its language keys and metadata XML.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAdminViewMenuBuildsMetadataAndLanguageKeys(): void
	{
		$structure = $this->structure();
		$structure->expects($this->once())
			->method('build')
			->with(['site' => 'article'], 'admin_menu')
			->willReturn(true);

		$subject = new MenuAdminView(
			$this->config(),
			$this->language(),
			$structure,
			$this->createStub(CMSApplicationInterface::class)
		);

		$settings = new \stdClass();
		$settings->name_single = 'Article';
		$settings->short_description = 'One article.';

		$xml = $subject->get('article', ['settings' => $settings]);

		$this->assertSame(
			'<?xml version="1.0" encoding="utf-8" ?>' . PHP_EOL
			. '<metadata>' . PHP_EOL
			. "\t" . '<layout title="COM_DEMO_MENU_ARTICLE_TITLE" option="COM_DEMO_MENU_ARTICLE_OPTION">' . PHP_EOL
			. "\t\t" . '<message>' . PHP_EOL
			. "\t\t\t" . '<![CDATA[COM_DEMO_MENU_ARTICLE_DESC]]>' . PHP_EOL
			. "\t\t" . '</message>' . PHP_EOL
			. "\t" . '</layout>' . PHP_EOL
			. '</metadata>',
			$xml
		);
		$this->assertSame(
			'Create Article',
			$this->language()->get('adminsys', 'COM_DEMO_MENU_ARTICLE_TITLE')
		);
		$this->assertSame(
			'One article.',
			$this->language()->get('adminsys', 'COM_DEMO_MENU_ARTICLE_DESC')
		);
	}

	/**
	 * A failed menu structure build warns and produces no XML.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAdminViewMenuWarnsWhenTheFileIsNotBuilt(): void
	{
		$structure = $this->structure();
		$structure->expects($this->once())
			->method('build')
			->willReturn(false);

		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->once())
			->method('enqueueMessage')
			->with($this->stringContains('article'), 'Warning');

		$subject = new MenuAdminView(
			$this->config(),
			$this->language(),
			$structure,
			$app
		);

		$settings = new \stdClass();
		$settings->name_single = 'Article';
		$settings->short_description = 'One article.';

		$this->assertSame('', $subject->get('article', ['settings' => $settings]));
	}

	/**
	 * The custom view menu keeps its target-specific lookup attributes.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testCustomViewMenuFollowsTheTargetLookupAttributes(string $version, int $major): void
	{
		$contentone = new ContentOne();
		$contentone->set('COMPONENT', 'DEMO');
		$contentone->set('ComponentNamespace', 'Demo');

		$request = new Request();
		$request->set('id.article', ['<field name="id" type="modal_article" />']);

		$frontendparams = new FrontendParams();
		$frontendparams->set(
			'Article',
			['<field name="show_title" type="list" display="menu" />']
		);

		$structure = $this->structure();
		$structure->expects($this->once())
			->method('build')
			->with(['site' => 'article'], 'menu')
			->willReturn(true);

		$subject = $this->renderer(
			$this->rendererClass($version),
			[
				'contentone' => $contentone,
				'frontendparams' => $frontendparams,
				'request' => $request,
				'structure' => $structure,
				'app' => $this->createStub(CMSApplicationInterface::class),
			]
		);

		$settings = new \stdClass();
		$settings->code = 'article';
		$settings->name = 'Article';
		$settings->description = 'One article.';

		$xml = $subject->get(['settings' => $settings]);

		$this->assertStringContainsString(
			'<layout title="COM_DEMO_MENU_ARTICLE_TITLE" option="COM_DEMO_MENU_ARTICLE_OPTION">',
			$xml
		);
		$this->assertStringContainsString('<fields name="request">', $xml);
		$this->assertStringContainsString(
			'<field name="id" type="modal_article" />',
			$xml
		);
		$this->assertStringContainsString('<fields name="params">', $xml);
		$this->assertStringContainsString(
			'<fieldset name="basic" label="COM_DEMO"',
			$xml
		);
		$this->assertStringContainsString(
			'<field name="show_title" type="list"  />',
			$xml
		);
		$this->assertStringEndsWith(PHP_EOL . '</metadata>', $xml);

		if ($major === 3)
		{
			$this->assertStringContainsString(
				'addrulepath="/administrator/components/com_demo/models/rules"',
				$xml
			);
			$this->assertStringContainsString(
				'addfieldpath="/administrator/components/com_demo/models/fields">',
				$xml
			);
			$this->assertStringNotContainsString('addruleprefix=', $xml);

			return;
		}

		$this->assertStringContainsString(
			'addruleprefix="Acme\Component\Demo\Administrator\Rule"',
			$xml
		);
		$this->assertStringContainsString(
			'addfieldprefix="Acme\Component\Demo\Administrator\Field">',
			$xml
		);
		$this->assertStringNotContainsString('addrulepath=', $xml);
	}

	/**
	 * The site build target switches the lookup attributes to the site area.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testCustomViewMenuUsesTheSiteAreaOnSiteBuilds(): void
	{
		$this->config()->set('build_target', 'site');

		$contentone = new ContentOne();
		$contentone->set('COMPONENT', 'DEMO');
		$contentone->set('ComponentNamespace', 'Demo');

		$request = new Request();
		$request->set('catid.article', ['<field name="catid" type="category" />']);

		$structure = $this->structure();
		$structure->expects($this->once())
			->method('build')
			->willReturn(true);

		$subject = $this->renderer(
			$this->rendererClass('JoomlaFive'),
			[
				'contentone' => $contentone,
				'frontendparams' => new FrontendParams(),
				'request' => $request,
				'structure' => $structure,
				'app' => $this->createStub(CMSApplicationInterface::class),
			]
		);

		$settings = new \stdClass();
		$settings->code = 'article';
		$settings->name = 'Article';
		$settings->description = 'One article.';

		$xml = $subject->get(['settings' => $settings]);

		$this->assertStringContainsString(
			'addruleprefix="Acme\Component\Demo\Site\Rule"',
			$xml
		);
		$this->assertStringContainsString(
			'<field name="catid" type="category" />',
			$xml
		);
	}

	/**
	 * Frontend option-set fields gain the global option and relaxed rules.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testFrontendParamFieldsAreRelaxedForMenuUse(): void
	{
		$subject = $this->renderer(
			$this->rendererClass('JoomlaSix'),
			['app' => $this->createStub(CMSApplicationInterface::class)]
		);

		$optionField = '<!-- Option Set. -->' . PHP_EOL
			. '<field name="show_title"' . PHP_EOL
			. 'default="1"' . PHP_EOL
			. 'filter="uint"' . PHP_EOL
			. 'required="true" />';
		$menuOnly = '<field name="menu_note" display="menu" />';
		$adminOnly = '<field name="hidden_note" display="admin" />';

		$keep = $subject->params([$optionField, $menuOnly, $adminOnly], 'article');

		$this->assertCount(2, $keep);
		$this->assertStringContainsString('JGLOBAL_USE_GLOBAL</option>', $keep[0]);
		$this->assertStringContainsString('default=""', $keep[0]);
		$this->assertStringContainsString('filter="string"', $keep[0]);
		$this->assertStringContainsString('required="false"', $keep[0]);
		$this->assertSame('<field name="menu_note"  />', $keep[1]);
	}

	/**
	 * Build a versioned renderer class name.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  class-string
	 * @since   6.1.7
	 */
	private function rendererClass(string $version): string
	{
		// only Joomla 3 keeps its own path-attribute rendering
		return $this->targetClass($version, 'Menu\\CustomView', ['JoomlaThree']);
	}

	/**
	 * Create a structure double with only its build boundary open.
	 *
	 * @return  Structure&\PHPUnit\Framework\MockObject\MockObject
	 * @since   6.1.7
	 */
	private function structure(): Structure
	{
		return $this->getMockBuilder(Structure::class)
			->disableOriginalConstructor()
			->onlyMethods(['build'])
			->getMock();
	}
}
