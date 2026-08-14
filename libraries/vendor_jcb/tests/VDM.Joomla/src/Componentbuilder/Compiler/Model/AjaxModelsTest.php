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
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteEditView;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Model\Ajaxadmin;
use VDM\Joomla\Componentbuilder\Compiler\Model\Ajaxcustomview;
use VDM\Joomla\Componentbuilder\Compiler\Model\Custombuttons;
use VDM\Joomla\Componentbuilder\Compiler\Templatelayout\Data as Templatelayout;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * AJAX and custom-button source-model contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Ajaxadmin::class)]
#[CoversClass(Ajaxcustomview::class)]
#[CoversClass(Custombuttons::class)]
final class AjaxModelsTest extends CompilerDomainTestCase
{
	/**
	 * Mirror an administrator AJAX method to an enabled site edit view and restore the target.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAjaxAdminMirrorsControllersAndMethodsToTheSiteEditView(): void
	{
		$config = $this->compilerConfig(['build_target' => 'admin']);
		$siteEditView = new SiteEditView();
		$siteEditView->set('view-guid', true);
		$calls = [];
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = [];
		$dispenser->method('set')->willReturnCallback(
			static function (&$script, string $first, ?string $second, ?string $third, array $mapper, bool $base64 = true, bool $dynamic = true) use (&$calls): bool
			{
				$calls[] = [$script, $first, $second, $third, $mapper, $base64, $dynamic];

				return true;
			}
		);
		$templateCalls = [];
		$template = $this->createStub(Templatelayout::class);
		$template->method('set')->willReturnCallback(
			static function (string $code, string $view) use (&$templateCalls): bool
			{
				$templateCalls[] = [$code, $view];

				return true;
			}
		);
		$item = (object) [
			'id' => 41,
			'guid' => 'view-guid',
			'name_single_code' => 'article',
			'add_php_ajax' => 1,
			'ajax_input' => json_encode([['name' => 'id']], JSON_THROW_ON_ERROR),
			'php_ajaxmethod' => 'public function fetch() {}',
		];

		(new Ajaxadmin($config, $siteEditView, $dispenser, $template))->set($item);

		$this->assertTrue($dispenser->hub['token']['article']);
		$this->assertSame([['name' => 'id']], $dispenser->hub['admin']['ajax_controller']['article']);
		$this->assertSame([['name' => 'id']], $dispenser->hub['site']['ajax_controller']['article']);
		$this->assertSame('admin', $config->build_target);
		$this->assertTrue($config->add_ajax);
		$this->assertTrue($config->add_site_ajax);
		$this->assertCount(2, $calls);
		$this->assertSame(['admin', 'site'], array_column($calls, 1));
		$this->assertSame([true, false], array_column($calls, 5));
		$this->assertSame([true, false], array_column($calls, 6));
		$this->assertSame([
			['public function fetch() {}', 'article'],
			['public function fetch() {}', 'article'],
		], $templateCalls);
		$this->assertObjectNotHasProperty('ajax_input', $item);
		$this->assertObjectNotHasProperty('php_ajaxmethod', $item);
	}

	/**
	 * Route custom-view AJAX state to the active build target and consume raw sources.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAjaxCustomViewTargetsSiteAndConsumesItsRawSources(): void
	{
		$config = $this->compilerConfig(['build_target' => 'site']);
		$dispenser = $this->createMock(Dispenser::class);
		$dispenser->hub = [];
		$dispenser->expects($this->once())
			->method('set')
			->with(
				$this->identicalTo('function loadData() {}'),
				'site',
				'ajax_model',
				'catalog',
				$this->callback(static fn(array $mapper): bool => $mapper['table'] === 'site_view'
					&& $mapper['field'] === 'php_ajaxmethod'
					&& $mapper['id'] === 12)
			);
		$template = $this->createMock(Templatelayout::class);
		$template->expects($this->once())->method('set')->with('function loadData() {}', 'catalog');
		$item = (object) [
			'id' => 12,
			'code' => 'catalog',
			'add_php_ajax' => 1,
			'ajax_input' => '[{"name":"term"}]',
			'php_ajaxmethod' => 'function loadData() {}',
		];

		(new Ajaxcustomview($config, $dispenser, $template))->set($item);

		$this->assertSame([['name' => 'term']], $dispenser->hub['site']['ajax_controller']['catalog']);
		$this->assertTrue($config->add_site_ajax);
		$this->assertObjectNotHasProperty('ajax_input', $item);
		$this->assertObjectNotHasProperty('php_ajaxmethod', $item);
	}

	/**
	 * Decode custom-button areas, map them through GUI code, and normalize toolbars.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomButtonsTransformsCodeAndNormalizesButtonAndToolbarState(): void
	{
		$customcode = $this->createStub(Customcode::class);
		$customcode->method('update')->willReturnCallback(
			static fn(string $code): string => 'dynamic:' . $code
		);
		$gui = $this->createStub(Gui::class);
		$gui->method('set')->willReturnCallback(
			static fn(string $code, array $mapper): string => $mapper['field'] . ':' . $code
		);
		$templateCalls = [];
		$template = $this->createStub(Templatelayout::class);
		$template->method('set')->willReturnCallback(
			static function (string $code, string $view) use (&$templateCalls): bool
			{
				$templateCalls[] = [$code, $view];

				return true;
			}
		);
		$item = (object) [
			'id' => 4,
			'name_single_code' => 'article',
			'add_custom_button' => 1,
			'php_model' => base64_encode('model code'),
			'custom_button' => '[{"name":"Publish"}]',
			'add_views_toolbar' => 1,
			'views_toolbar' => base64_encode('toolbar code'),
			'add_view_toolbar' => 0,
			'view_toolbar' => base64_encode('discarded'),
		];

		(new Custombuttons($customcode, $gui, $template))->set($item);

		$this->assertSame('php_model:dynamic:model code', $item->php_model);
		$this->assertSame('views_toolbar:dynamic:toolbar code', $item->views_toolbar);
		$this->assertNull($item->view_toolbar);
		$this->assertSame([['name' => 'Publish']], $item->custom_buttons);
		$this->assertObjectNotHasProperty('custom_button', $item);
		$this->assertSame([
			['php_model:dynamic:model code', 'article'],
			['views_toolbar:dynamic:toolbar code', 'article'],
		], $templateCalls);
	}
}
