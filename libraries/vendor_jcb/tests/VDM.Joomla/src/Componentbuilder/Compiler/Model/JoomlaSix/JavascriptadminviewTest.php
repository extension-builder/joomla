<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    23rd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Model\JoomlaSix;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Model\Javascriptadminview as SharedJavascriptadminview;
use VDM\Joomla\Componentbuilder\Compiler\Model\JoomlaSix\Javascriptadminview;
use VDM\Joomla\Componentbuilder\Compiler\Service\Model;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Footer collection preserves source, target identity and silent diagnostics.
 *
 * @since  6.2.0
 */
#[CoversClass(Javascriptadminview::class)]
#[CoversClass(SharedJavascriptadminview::class)]
#[CoversClass(Model::class)]
final class JavascriptadminviewTest extends JoomlaTestCase
{
	/**
	 * Every target preserves authored footer source without a placement warning.
	 *
	 * @param   int  $major  Requested Joomla target.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testProviderPreservesGlobalSourceWithoutPlacementWarnings(int $major): void
	{
		$scripts = [];
		$dispenser = $this->dispenser($scripts);
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->never())->method('enqueueMessage');
		$this->setJoomlaApplication($app);
		$config = new Config(new Input([]), new Registry(), new Registry());
		$config->set('joomla_version', $major);
		$container = new Container();
		$container->set('Config', $config);
		$container->set('Customcode.Dispenser', $dispenser);
		$container->registerServiceProvider(new Model());
		$subject = $container->get('Model.Javascriptadminview');
		$this->assertSame($subject, $container->get('Model.J' . $major . '.Javascriptadminview'));
		$source = 'var shared = document.querySelector("#thing"); function onToolbarClick() { return shared; }';
		$item = (object) [
			'id' => 42,
			'name_single_code' => 'article',
			'add_javascript_view_footer' => 1,
			'javascript_view_footer' => base64_encode($source),
			'add_javascript_views_footer' => 1,
			'javascript_views_footer' => base64_encode($source),
		];
		$subject->set($item);
		$this->assertSame(['view_footer' => $source, 'views_footer' => $source], $scripts);
		$this->assertObjectNotHasProperty('javascript_view_footer', $item);
		$this->assertObjectNotHasProperty('javascript_views_footer', $item);
	}

	/**
	 * Supported compile targets share collection semantics.
	 *
	 * @return  array<string, array{int}>
	 * @since   6.2.0
	 */
	public static function targets(): array
	{
		return ['Joomla 3' => [3], 'Joomla 4' => [4], 'Joomla 5' => [5], 'Joomla 6' => [6]];
	}

	/**
	 * Absent, disabled and empty footers and ordinary script files are silent.
	 *
	 * @param   array  $fields  Script fields added to a minimal view.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('silentScripts')]
	public function testNoWarningWithoutAnEnabledNonemptyFooter(array $fields): void
	{
		$scripts = [];
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->never())->method('enqueueMessage');
		$item = (object) (['id' => 42, 'name_single_code' => 'article'] + $fields);
		(new Javascriptadminview($this->dispenser($scripts)))->set($item);
	}

	/**
	 * Boundary cases retain the existing script collection rules.
	 *
	 * @return  array<string, array{array}>
	 * @since   6.2.0
	 */
	public static function silentScripts(): array
	{
		return [
			'absent' => [[]],
			'disabled' => [[
				'add_javascript_view_footer' => 0,
				'javascript_view_footer' => base64_encode('window.shared = true;'),
			]],
			'empty' => [[
				'add_javascript_view_footer' => 1,
				'javascript_view_footer' => '',
			]],
			'whitespace' => [[
				'add_javascript_views_footer' => 1,
				'javascript_views_footer' => base64_encode(" \n\t"),
			]],
			'file' => [[
				'add_javascript_view_file' => 1,
				'javascript_view_file' => base64_encode('window.shared = true;'),
			]],
		];
	}

	/**
	 * Bound custom-code expansion while retaining its by-reference decoding contract.
	 *
	 * @param   array  $scripts  Collected decoded source keyed by placement.
	 *
	 * @return  Dispenser
	 * @since   6.2.0
	 */
	private function dispenser(array &$scripts): Dispenser
	{
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = ['token' => ['article' => false]];
		$dispenser->method('set')->willReturnCallback(
			static function (&$script, string $placement) use (&$scripts): bool
			{
				$script = base64_decode((string) $script);
				$scripts[$placement] = $script;

				return true;
			}
		);

		return $dispenser;
	}
}
