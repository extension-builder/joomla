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
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Model\Cssadminview;
use VDM\Joomla\Componentbuilder\Compiler\Model\Csscustomview;
use VDM\Joomla\Componentbuilder\Compiler\Model\Javascriptadminview;
use VDM\Joomla\Componentbuilder\Compiler\Model\Javascriptcustomview;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * Custom-code collaborator and mutation contracts for compiler models.
 *
 * @since  6.1.6
 */
#[CoversClass(Cssadminview::class)]
#[CoversClass(Csscustomview::class)]
#[CoversClass(Javascriptadminview::class)]
#[CoversClass(Javascriptcustomview::class)]
#[UsesClass(Customcode::class)]
#[UsesClass(Dispenser::class)]
#[UsesClass(Gui::class)]
#[UsesClass(StringHelper::class)]
final class CustomCodeModelTest extends TestCase
{
	/**
	 * Send enabled admin CSS to the dispenser with additive compiler options.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCssadminviewDispensesEnabledAreasAndConsumesTheirSource(): void
	{
		$calls = [];
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->method('set')->willReturnCallback(
			static function (&$script, string $first, ?string $second, ?string $third,
				array $config, bool $base64, bool $dynamic, bool $add) use (&$calls): bool
			{
				$calls[] = [
					'script' => $script,
					'first' => $first,
					'second' => $second,
					'third' => $third,
					'config' => $config,
					'base64' => $base64,
					'dynamic' => $dynamic,
					'add' => $add
				];

				return true;
			}
		);
		$item = (object) [
			'name_single_code' => 'article',
			'add_css_view' => 1,
			'css_view' => base64_encode('.article { color: red; }'),
			'add_css_views' => 0,
			'css_views' => base64_encode('.articles { color: blue; }')
		];

		(new Cssadminview($dispenser))->set($item);

		$this->assertSame([[
			'script' => base64_encode('.article { color: red; }'),
			'first' => 'css_view',
			'second' => 'article',
			'third' => null,
			'config' => ['prefix' => PHP_EOL],
			'base64' => true,
			'dynamic' => true,
			'add' => true
		]], $calls);
		$this->assertObjectNotHasProperty('css_view', $item);
		$this->assertSame(
			base64_encode('.articles { color: blue; }'),
			$item->css_views
		);
	}

	/**
	 * Decode and update enabled custom-view CSS while leaving disabled data intact.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCsscustomviewDecodesAndUpdatesEnabledAreas(): void
	{
		$inputs = [];
		$customcode = $this->createStub(Customcode::class);
		$customcode->method('update')->willReturnCallback(
			static function (string $code) use (&$inputs): string
			{
				$inputs[] = $code;

				return 'updated:' . $code;
			}
		);
		$item = (object) [
			'add_css_document' => 1,
			'css_document' => base64_encode('body { margin: 0; }'),
			'add_css' => 0,
			'css' => base64_encode('.kept { display: block; }')
		];

		(new Csscustomview($customcode))->set($item);

		$this->assertSame(['body { margin: 0; }'], $inputs);
		$this->assertSame('updated:body { margin: 0; }', $item->css_document);
		$this->assertSame(base64_encode('.kept { display: block; }'), $item->css);
	}

	/**
	 * Dispense admin JavaScript with exact GUI metadata and detect token use.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJavascriptadminviewDispensesAreasAndMarksTokenRequirement(): void
	{
		$calls = [];
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = ['token' => ['article' => false]];
		$dispenser->method('set')->willReturnCallback(
			static function (&$script, string $first, ?string $second, ?string $third,
				array $config, bool $base64, bool $dynamic, bool $add) use (&$calls): bool
			{
				$original = $script;
				$script = base64_decode((string) $script);
				$calls[] = [
					'script' => $original,
					'first' => $first,
					'second' => $second,
					'third' => $third,
					'config' => $config,
					'base64' => $base64,
					'dynamic' => $dynamic,
					'add' => $add
				];

				return true;
			}
		);
		$item = (object) [
			'id' => '42',
			'name_single_code' => 'article',
			'add_javascript_view_file' => 1,
			'javascript_view_file' => base64_encode('const token = "required";'),
			'add_javascript_view_footer' => 1,
			'javascript_view_footer' => base64_encode('console.log("footer");'),
			'add_javascript_views_file' => 0,
			'javascript_views_file' => base64_encode('console.log("kept");')
		];

		(new Javascriptadminview($dispenser))->set($item, 'custom_admin_view');

		$this->assertCount(2, $calls);
		$this->assertSame('view_file', $calls[0]['first']);
		$this->assertSame('view_footer', $calls[1]['first']);
		$this->assertSame('article', $calls[0]['second']);
		$this->assertNull($calls[0]['third']);
		$this->assertSame([
			'table' => 'custom_admin_view',
			'id' => 42,
			'field' => 'javascript_view_file',
			'type' => 'js',
			'prefix' => PHP_EOL
		], $calls[0]['config']);
		$this->assertSame([
			'table' => 'custom_admin_view',
			'id' => 42,
			'field' => 'javascript_view_footer',
			'type' => 'js',
			'prefix' => PHP_EOL
		], $calls[1]['config']);
		$this->assertTrue($calls[0]['base64']);
		$this->assertTrue($calls[0]['dynamic']);
		$this->assertTrue($calls[0]['add']);
		$this->assertTrue($dispenser->hub['token']['article']);
		$this->assertObjectNotHasProperty('javascript_view_file', $item);
		$this->assertObjectNotHasProperty('javascript_view_footer', $item);
		$this->assertSame(
			base64_encode('console.log("kept");'),
			$item->javascript_views_file
		);
	}

	/**
	 * Decode, update, and GUI-wrap both custom-view JavaScript areas.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJavascriptcustomviewAppliesCustomcodeAndGuiPipelines(): void
	{
		$customcodeInputs = [];
		$guiCalls = [];
		$customcode = $this->createStub(Customcode::class);
		$customcode->method('update')->willReturnCallback(
			static function (string $code) use (&$customcodeInputs): string
			{
				$customcodeInputs[] = $code;

				return 'updated:' . $code;
			}
		);
		$gui = $this->createStub(Gui::class);
		$gui->method('set')->willReturnCallback(
			static function (string $code, array $config) use (&$guiCalls): string
			{
				$guiCalls[] = [$code, $config];

				return 'gui:' . $config['field'] . ':' . $code;
			}
		);
		$item = (object) [
			'id' => '17',
			'add_javascript_file' => 1,
			'javascript_file' => base64_encode('file();'),
			'add_js_document' => 1,
			'js_document' => base64_encode('document();')
		];

		(new Javascriptcustomview($customcode, $gui))->set($item, 'custom_view');

		$this->assertSame(['file();', 'document();'], $customcodeInputs);
		$this->assertSame([
			[
				'updated:file();',
				[
					'table' => 'custom_view',
					'id' => 17,
					'field' => 'javascript_file',
					'type' => 'js'
				]
			],
			[
				'updated:document();',
				[
					'table' => 'custom_view',
					'id' => 17,
					'field' => 'js_document',
					'type' => 'js'
				]
			]
		], $guiCalls);
		$this->assertSame('gui:javascript_file:updated:file();', $item->javascript_file);
		$this->assertSame('gui:js_document:updated:document();', $item->js_document);
	}
}
