<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\CustomCSS;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\DocumentCustomPHP;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\FootableScriptsLoader;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\View\GoogleChartLoader;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FootableScripts as FootableScriptsBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GoogleChart;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\AdminView\FootableScriptsInterface;


/**
 * Generated view document asset contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ViewDocumentAssetsTest extends ArchitectureTestCase
{
	/**
	 * The google chart statements this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CHART = <<<'GEN'


		// add the google chart builder class.
		require_once JPATH_ADMINISTRATOR . '/components/com_demo/helpers/chartbuilder.php';
		// load the google chart js.
		Html::_('script', 'media/com_demo/js/google.jsapi.js', ['version' => 'auto']);
		Html::_('script', 'https://canvg.googlecode.com/svn/trunk/rgbcolor.js', ['version' => 'auto']);
		Html::_('script', 'https://canvg.googlecode.com/svn/trunk/canvg.js', ['version' => 'auto']);
GEN;

	/**
	 * Build a view definition.
	 *
	 * @param   array  $over  What the view declares.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(array $over = []): array
	{
		$settings = new stdClass();
		$settings->code = 'demo';
		$settings->add_php_document = 0;
		$settings->php_document = '';
		$settings->add_css = 0;
		$settings->css = '';

		foreach ($over as $key => $value)
		{
			$settings->$key = $value;
		}

		return ['settings' => $settings];
	}

	/**
	 * A view that asked for no custom php is given none.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatAsksForNoCustomPhpIsGivenNone(): void
	{
		$subject = $this->renderer(DocumentCustomPHP::class);
		$view = $this->view(['add_php_document' => 0, 'php_document' => 'echo 1;']);

		$this->assertSame('', $subject->get($view));
	}

	/**
	 * The custom php of a view arrives at the indent the generated method sits at.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheCustomPhpOfAViewArrivesIndented(): void
	{
		$subject = $this->renderer(DocumentCustomPHP::class);
		$view = $this->view(['add_php_document' => 1, 'php_document' => "echo 1;\necho 2;"]);

		$this->assertSame(
			"\n\t\techo 1;\n\t\techo 2;", $subject->get($view)
		);
	}

	/**
	 * A view that asked for no stylesheet is given none.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatAsksForNoStylesheetIsGivenNone(): void
	{
		$subject = $this->renderer(CustomCSS::class);
		$off = $this->view(['add_css' => 0, 'css' => 'body {}']);
		$empty = $this->view(['add_css' => 1, 'css' => '']);

		$this->assertSame('', $subject->get($off));
		$this->assertSame('', $subject->get($empty));
	}

	/**
	 * The stylesheet of a view arrives as it was written.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheStylesheetOfAViewArrivesAsItWasWritten(): void
	{
		$subject = $this->renderer(CustomCSS::class);
		$view = $this->view(['add_css' => 1, 'css' => "body {\n\tcolor: red;\n}"]);

		$this->assertSame("body {\n\tcolor: red;\n}", $subject->get($view));
	}

	/**
	 * A view with no chart on it loads no chart assets.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoChartLoadsNoChartAssets(): void
	{
		$subject = $this->renderer(GoogleChartLoader::class, [
			'googlechart' => new GoogleChart(),
		]);

		$this->assertSame('', $subject->get($this->view()));
	}

	/**
	 * A view with a chart on it loads the chart builder and its scripts.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAChartLoadsTheChartAssets(): void
	{
		$charts = new GoogleChart();
		$charts->set('admin.demo', true);

		$subject = $this->renderer(GoogleChartLoader::class, [
			'googlechart' => $charts,
		]);

		$this->assertSame(self::EXPECTED_CHART, $subject->get($this->view()));
	}

	/**
	 * A view with no footable table on it loads no footable scripts.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithNoFootableTableLoadsNoScripts(): void
	{
		$subject = $this->renderer(FootableScriptsLoader::class, [
			'footablescripts' => new FootableScriptsBuilder(),
			'scripts' => $this->footableScripts(),
		]);

		$this->assertSame('', $subject->get($this->view()));
	}

	/**
	 * A view with a footable table on it is given the scripts without the
	 * initialisation an admin view asks for.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAFootableTableIsGivenTheScriptsUninitialised(): void
	{
		$tables = new FootableScriptsBuilder();
		$tables->set('admin.demo', true);

		$subject = $this->renderer(FootableScriptsLoader::class, [
			'footablescripts' => $tables,
			'scripts' => $this->footableScripts(),
		]);

		$this->assertSame('scripts(init=false)', $subject->get($this->view()));
	}

	/**
	 * A footable script writer that only says how it was called.
	 *
	 * @return  FootableScriptsInterface
	 * @since   6.1.7
	 */
	private function footableScripts(): FootableScriptsInterface
	{
		return new class implements FootableScriptsInterface
		{
			/**
			 * Say how the scripts were asked for.
			 *
			 * @param   bool  $init  Whether the caller wants the initialisation.
			 *
			 * @return  string
			 * @since   6.1.7
			 */
			public function get(bool $init = true): string
			{
				return 'scripts(init=' . var_export($init, true) . ')';
			}
		};
	}
}
