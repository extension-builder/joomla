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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Model;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomTabs;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Model\JoomlaFive\Customtabs as JoomlaFiveCustomtabs;
use VDM\Joomla\Componentbuilder\Compiler\Model\JoomlaFour\Customtabs as JoomlaFourCustomtabs;
use VDM\Joomla\Componentbuilder\Compiler\Model\JoomlaSix\Customtabs as JoomlaSixCustomtabs;
use VDM\Joomla\Componentbuilder\Compiler\Model\JoomlaThree\Customtabs as JoomlaThreeCustomtabs;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Joomla-version-specific custom-tab generated-output contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeCustomtabs::class)]
#[CoversClass(JoomlaFourCustomtabs::class)]
#[CoversClass(JoomlaFiveCustomtabs::class)]
#[CoversClass(JoomlaSixCustomtabs::class)]
#[UsesClass(CustomTabs::class)]
#[UsesClass(Language::class)]
final class VersionedCustomtabsTest extends CompilerDomainTestCase
{
	/**
	 * Render the correct tab API and grid markup for each compile target.
	 *
	 * @param   class-string  $class       Versioned custom-tab implementation.
	 * @param   string        $addMarker   Expected tab API marker.
	 * @param   string        $rowMarker   Expected target grid marker.
	 * @param   string        $endMarker   Expected closing API marker.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versionCases')]
	public function testSetRendersTargetSpecificTabMarkup(
		string $class,
		string $addMarker,
		string $rowMarker,
		string $endMarker
	): void
	{
		$config = $this->compilerConfig(['lang_prefix' => 'COM_DEMO']);
		$builder = new CustomTabs();
		$language = new Language($config);
		$placeholder = $this->createStub(Placeholder::class);
		$placeholder->method('update_')->willReturnArgument(0);
		$customcode = $this->createStub(Customcode::class);
		$customcode->method('update')->willReturnArgument(0);
		$subject = new $class($config, $builder, $language, $placeholder, $customcode);
		$item = (object) [
			'name_single_code' => 'article',
			'customtabs' => json_encode([[
				'name' => 'Details',
				'html' => "<p>First</p>\n<p>Second</p>",
				'permission' => 0,
			]], JSON_THROW_ON_ERROR),
		];

		$subject->set($item);

		$tabs = $builder->get('article');
		$this->assertCount(1, $tabs);
		$this->assertSame('details', $tabs[0]['code']);
		$this->assertSame('COM_DEMO_ARTICLE_DETAILS', $tabs[0]['lang']);
		$this->assertSame('Details', $language->get('both', 'COM_DEMO_ARTICLE_DETAILS'));
		$this->assertStringContainsString($addMarker, $tabs[0]['html']);
		$this->assertStringContainsString($rowMarker, $tabs[0]['html']);
		$this->assertStringContainsString($endMarker, $tabs[0]['html']);
		$this->assertStringContainsString("\t\t\t\t<p>First</p>", $tabs[0]['html']);
		$this->assertObjectNotHasProperty('customtabs', $item);
	}

	/**
	 * Version-specific generated-code cases.
	 *
	 * @return  iterable<string, array{class-string, string, string, string}>
	 * @since   6.1.6
	 */
	public static function versionCases(): iterable
	{
		yield 'Joomla 3 Bootstrap tabs' => [
			JoomlaThreeCustomtabs::class,
			"Html::_('bootstrap.addTab'",
			'<div class="row-fluid form-horizontal-desktop">',
			"Html::_('bootstrap.endTab')",
		];
		yield 'Joomla 4 UI tabs' => [
			JoomlaFourCustomtabs::class,
			"Html::_('uitab.addTab'",
			'<div class="col-md-12">',
			"Html::_('uitab.endTab')",
		];
		yield 'Joomla 5 Power tabs' => [
			JoomlaFiveCustomtabs::class,
			"Joomla___34690c75_1090_47eb_8c06_7228dc7eedd6___Power::_('uitab.addTab'",
			'<div class="col-md-12">',
			"Joomla___34690c75_1090_47eb_8c06_7228dc7eedd6___Power::_('uitab.endTab')",
		];
		yield 'Joomla 6 Power tabs' => [
			JoomlaSixCustomtabs::class,
			"Joomla___34690c75_1090_47eb_8c06_7228dc7eedd6___Power::_('uitab.addTab'",
			'<div class="col-md-12">',
			"Joomla___34690c75_1090_47eb_8c06_7228dc7eedd6___Power::_('uitab.endTab')",
		];
	}
}
