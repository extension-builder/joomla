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
use VDM\Joomla\Componentbuilder\Compiler\Model\Customimportscripts;
use VDM\Joomla\Componentbuilder\Compiler\Model\Loader;
use VDM\Joomla\Componentbuilder\Compiler\Model\Phpadminview;
use VDM\Joomla\Componentbuilder\Compiler\Model\Phpcustomview;
use VDM\Joomla\Componentbuilder\Compiler\Templatelayout\Data as Templatelayout;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * PHP code pipeline contracts for compiler models.
 *
 * @since  6.1.6
 */
#[CoversClass(Customimportscripts::class)]
#[CoversClass(Phpadminview::class)]
#[CoversClass(Phpcustomview::class)]
#[UsesClass(Customcode::class)]
#[UsesClass(Dispenser::class)]
#[UsesClass(Gui::class)]
#[UsesClass(Loader::class)]
#[UsesClass(Templatelayout::class)]
#[UsesClass(StringHelper::class)]
final class PhpModelTest extends TestCase
{
	/**
	 * Dispense all custom-import areas with exact mapper types and consume sources.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomimportscriptsMapsPhpAndHtmlAreas(): void
	{
		$calls = [];
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = [];
		$dispenser->method('set')->willReturnCallback(
			static function (&$script, string $first, ?string $second, ?string $third,
				array $config) use (&$calls): bool
			{
				$calls[] = [$script, $first, $second, $third, $config];

				return true;
			}
		);
		$areas = [
			'php_import_ext',
			'php_import_display',
			'php_import',
			'php_import_setdata',
			'php_import_save',
			'php_import_headers',
			'html_import_view'
		];
		$item = (object) [
			'id' => '88',
			'name_list_code' => 'articles',
			'add_custom_import' => 1
		];

		foreach ($areas as $area)
		{
			$item->{$area} = base64_encode($area . ' content');
		}

		(new Customimportscripts($dispenser))->set($item, 'custom_admin_view');

		$this->assertCount(7, $calls);

		foreach ($areas as $index => $area)
		{
			$this->assertSame(base64_encode($area . ' content'), $calls[$index][0]);
			$this->assertSame($area, $calls[$index][1]);
			$this->assertSame('import_articles', $calls[$index][2]);
			$this->assertNull($calls[$index][3]);
			$this->assertSame([
				'table' => 'custom_admin_view',
				'id' => 88,
				'field' => $area,
				'type' => $area === 'html_import_view' ? 'html' : 'php'
			], $calls[$index][4]);
			$this->assertObjectNotHasProperty($area, $item);
		}
	}

	/**
	 * Send enabled admin PHP through the dispenser before template discovery.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPhpadminviewDispensesThenDiscoversTemplates(): void
	{
		$dispenserCalls = [];
		$templateCalls = [];
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->method('set')->willReturnCallback(
			static function (&$script, string $first, ?string $second, ?string $third,
				array $config) use (&$dispenserCalls): bool
			{
				$original = $script;
				$script = base64_decode((string) $script);
				$dispenserCalls[] = [$original, $first, $second, $third, $config];

				return true;
			}
		);
		$template = $this->createStub(Templatelayout::class);
		$template->method('set')->willReturnCallback(
			static function (string $content, string $view) use (&$templateCalls): bool
			{
				$templateCalls[] = [$content, $view];

				return true;
			}
		);
		$item = (object) [
			'id' => '31',
			'name_single_code' => 'article',
			'add_php_getitem' => 1,
			'php_getitem' => base64_encode('$item = $this->getItem();'),
			'add_php_save' => 0,
			'php_save' => base64_encode('kept();')
		];

		(new Phpadminview($dispenser, $template))->set($item, 'custom_admin_view');

		$this->assertSame([[
			base64_encode('$item = $this->getItem();'),
			'php_getitem',
			'article',
			null,
			[
				'table' => 'custom_admin_view',
				'id' => 31,
				'field' => 'php_getitem',
				'type' => 'php'
			]
		]], $dispenserCalls);
		$this->assertSame([[
			'$item = $this->getItem();',
			'article'
		]], $templateCalls);
		$this->assertObjectNotHasProperty('php_getitem', $item);
		$this->assertSame(base64_encode('kept();'), $item->php_save);
	}

	/**
	 * Apply custom-code, GUI, template, loader, and UIkit stages in order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPhpcustomviewRunsEveryPipelineStageForEnabledAreas(): void
	{
		$customcodeInputs = [];
		$guiCalls = [];
		$templateCalls = [];
		$loaderCalls = [];
		$uikitCalls = [];
		$customcode = $this->createStub(Customcode::class);
		$customcode->method('update')->willReturnCallback(
			static function (string $content) use (&$customcodeInputs): string
			{
				$customcodeInputs[] = $content;

				return 'updated:' . $content;
			}
		);
		$gui = $this->createStub(Gui::class);
		$gui->method('set')->willReturnCallback(
			static function (string $content, array $config) use (&$guiCalls): string
			{
				$guiCalls[] = [$content, $config];

				return 'gui:' . $config['field'] . ':' . $content;
			}
		);
		$template = $this->createStub(Templatelayout::class);
		$template->method('set')->willReturnCallback(
			static function (string $content, string $view) use (&$templateCalls): bool
			{
				$templateCalls[] = [$content, $view];

				return true;
			}
		);
		$loader = $this->createStub(Loader::class);
		$loader->method('set')->willReturnCallback(
			static function (string $key, string $content) use (&$loaderCalls): void
			{
				$loaderCalls[] = [$key, $content];
			}
		);
		$loader->method('uikit')->willReturnCallback(
			static function (string $key, string $content) use (&$uikitCalls): void
			{
				$uikitCalls[] = [$key, $content];
			}
		);
		$item = (object) [
			'id' => '19',
			'code' => 'catalog',
			'add_php_view' => 1,
			'php_view' => base64_encode('view();'),
			'add_php_document' => 1,
			'php_document' => base64_encode('document();')
		];

		(new Phpcustomview($customcode, $gui, $loader, $template))->set($item, 'custom_view');

		$this->assertSame(['view();', 'document();'], $customcodeInputs);
		$this->assertSame([
			[
				'updated:view();',
				['table' => 'custom_view', 'id' => 19, 'field' => 'php_view', 'type' => 'php']
			],
			[
				'updated:document();',
				['table' => 'custom_view', 'id' => 19, 'field' => 'php_document', 'type' => 'php']
			]
		], $guiCalls);
		$this->assertSame([
			['gui:php_view:updated:view();', 'catalog'],
			['gui:php_document:updated:document();', 'catalog']
		], $templateCalls);
		$this->assertSame([
			['catalog', 'gui:php_view:updated:view();'],
			['catalog', 'gui:php_document:updated:document();']
		], $loaderCalls);
		$this->assertSame($loaderCalls, $uikitCalls);
		$this->assertSame('gui:php_view:updated:view();', $item->php_view);
		$this->assertSame('gui:php_document:updated:document();', $item->php_document);
	}
}
