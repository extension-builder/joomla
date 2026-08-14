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
use VDM\Joomla\Componentbuilder\Compiler\Builder\FootableScripts;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GetModule;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GoogleChart;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UikitComp;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Model\Loader;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\CompilerDomainTestCase;
use VDM\Tests\Support\LoaderUikitHelperFixture;


/**
 * Automatic compiler dependency-loader contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Loader::class)]
#[UsesClass(Config::class)]
#[UsesClass(FootableScripts::class)]
#[UsesClass(GetModule::class)]
#[UsesClass(GoogleChart::class)]
#[UsesClass(UikitComp::class)]
#[UsesClass(Helper::class)]
final class LoaderTest extends CompilerDomainTestCase
{
	/**
	 * Install the test-owned legacy component-helper boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		if (!class_exists('Model_loader_fixtureHelper', false))
		{
			class_alias(LoaderUikitHelperFixture::class, 'Model_loader_fixtureHelper');
		}

		Helper::$option = 'com_model_loader_fixture';
		LoaderUikitHelperFixture::reset();
	}

	/**
	 * Detect generated dependencies, set focused builders, and raise global flags.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetDetectsDependenciesAndUsesExplicitTarget(): void
	{
		$config = $this->compilerConfig(['build_target' => 'administrator']);
		$footable = new FootableScripts();
		$chart = new GoogleChart();
		$module = new GetModule();
		$subject = new Loader($config, $footable, $chart, $module, new UikitComp());

		$subject->set(
			'article',
			'footable(); Chartbuilder($data); $this->getModules();',
			'site'
		);

		$this->assertTrue($footable->get('site.article'));
		$this->assertTrue($chart->get('site.article'));
		$this->assertTrue($module->get('site.article'));
		$this->assertTrue($config->get('footable'));
		$this->assertTrue($config->get('google_chart'));

		$subject->set('article', 'no dependency markers remain', 'site');

		$this->assertTrue($footable->get('site.article'));
		$this->assertTrue($chart->get('site.article'));
		$this->assertTrue($module->get('site.article'));
	}

	/**
	 * Leave state empty when content contains no recognized dependency marker.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetDoesNotRegisterMissingDependencies(): void
	{
		$config = $this->compilerConfig(['build_target' => 'administrator']);
		$footable = new FootableScripts();
		$chart = new GoogleChart();
		$module = new GetModule();

		(new Loader($config, $footable, $chart, $module, new UikitComp()))
			->set('article', 'plain content');

		$this->assertFalse($footable->exists('administrator.article'));
		$this->assertFalse($chart->exists('administrator.article'));
		$this->assertFalse($module->exists('administrator.article'));
		$this->assertFalse($config->get('footable'));
		$this->assertFalse($config->get('google_chart'));
	}

	/**
	 * Dispatch enabled UIkit discovery and preserve prior component state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUikitUsesLegacyBoundaryOnlyForEnabledVersions(): void
	{
		$config = $this->compilerConfig(['uikit' => 2]);
		$components = new UikitComp();
		$components->set('article', ['existing-component']);
		$subject = new Loader(
			$config,
			new FootableScripts(),
			new GoogleChart(),
			new GetModule(),
			$components
		);

		$subject->uikit('article', '<div class="uk-grid"></div>');

		$this->assertSame([
			['<div class="uk-grid"></div>', ['existing-component']]
		], LoaderUikitHelperFixture::$calls);
		$this->assertSame(
			['existing-component', 'fixture-component'],
			$components->get('article')
		);

		LoaderUikitHelperFixture::reset();
		$config->set('uikit', 3);
		$subject->uikit('disabled', '<div class="uk-grid"></div>');

		$this->assertSame([], LoaderUikitHelperFixture::$calls);
		$this->assertFalse($components->exists('disabled'));
	}
}
