<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    18th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UikitComp;


/**
 * Generated uikit asset loading contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedViewUikitLoaderTest extends ArchitectureTestCase
{
	/**
	 * Supported Joomla target namespace segments.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree'],
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
		];
	}

	/**
	 * Joomla 6 carries no uikit, whatever the component asked for.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaSixCarriesNoUikitAtAll(): void
	{
		foreach ([1, 2, 3] as $uikit)
		{
			$this->assertSame('', $this->loader('JoomlaSix', $uikit, true));
		}
	}

	/**
	 * A component built without uikit loads nothing.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAComponentBuiltWithoutUikitLoadsNothing(string $version): void
	{
		$this->assertSame('', $this->loader($version, 0, true));
	}

	/**
	 * Every target that carries uikit reads the same three options.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testEveryTargetReadsTheSameOptions(string $version): void
	{
		$code = $this->loader($version, 1);

		$this->assertStringContainsString(
			"\$uikit = \$this->params->get('uikit_load');", $code
		);
		$this->assertStringContainsString(
			"\$size = \$this->params->get('uikit_min');", $code
		);
		$this->assertStringContainsString(
			"\$style = \$this->params->get('uikit_style');", $code
		);
	}

	/**
	 * Uikit 2 is loaded from its own folder, with the style option.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testUikitTwoIsLoadedWithItsStyleOption(): void
	{
		$code = $this->loader('JoomlaFive', 1);

		$this->assertStringContainsString(
			"'stylesheet', 'media/com_demo/uikit-v2/css/uikit'.\$style.\$size.'.css'", $code
		);
		$this->assertStringContainsString(
			"'script', 'media/com_demo/uikit-v2/js/uikit'.\$size.'.js'", $code
		);
		$this->assertStringNotContainsString('uikit-v3', $code);
	}

	/**
	 * Uikit 3 has no style option, and brings its icons along.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testUikitThreeHasNoStyleAndBringsItsIcons(): void
	{
		$code = $this->loader('JoomlaFive', 3);

		$this->assertStringContainsString(
			"'stylesheet', 'media/com_demo/uikit-v3/css/uikit'.\$size.'.css'", $code
		);
		$this->assertStringContainsString(
			"'script', 'media/com_demo/uikit-v3/js/uikit-icons'.\$size.'.js'", $code
		);
		$this->assertStringNotContainsString("\$this->params->get('uikit_style')", $code);
		$this->assertStringNotContainsString('uikit-v2', $code);
	}

	/**
	 * Offering both leaves the choice to the site, at runtime.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOfferingBothLeavesTheChoiceToTheSite(): void
	{
		$code = $this->loader('JoomlaFive', 2);

		$this->assertStringContainsString(
			"\$this->uikitVersion = \$this->params->get('uikit_version', 2);", $code
		);
		$this->assertStringContainsString('if (2 == $this->uikitVersion)', $code);
		$this->assertStringContainsString('elseif (3 == $this->uikitVersion)', $code);
		$this->assertStringContainsString('uikit-v2', $code);
		$this->assertStringContainsString('uikit-v3', $code);
	}

	/**
	 * The components a view's fields ask for are gathered and loaded.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheComponentsTheFieldsAskForAreGathered(): void
	{
		$code = $this->loader('JoomlaFive', 1, true);

		$this->assertStringContainsString("\$uikitComp[] = 'align';", $code);
		$this->assertStringContainsString("\$uikitComp[] = 'form';", $code);
		$this->assertStringContainsString("\$uikitFieldComp = \$this->get('UikitComp');", $code);
		$this->assertStringContainsString(
			'$uikitComp = array_merge($uikitComp, $uikitFieldComp);', $code
		);
	}

	/**
	 * Uikit 3 needs no component loading, so a view that asks gets none.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testUikitThreeNeedsNoComponentLoading(): void
	{
		$this->assertSame(
			$this->loader('JoomlaFive', 3),
			$this->loader('JoomlaFive', 3, true)
		);
	}

	/**
	 * Build the loader for one target and one uikit setting.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $uikit    The component uikit setting.
	 * @param   bool    $comp     Whether the view's fields ask for components.
	 *
	 * @return  string
	 * @since   6.1.7
	 */
	private function loader(string $version, int $uikit, bool $comp = false): string
	{
		// only Joomla 6 carries no uikit at all
		$class = $this->targetClass($version, 'View\\UikitLoader', ['JoomlaSix']);
		$this->config()->set('uikit', $uikit);

		$uikitcomp = new UikitComp();
		$sitefielddata = new SiteFieldData();

		if ($comp)
		{
			$uikitcomp->set('articles', ['align', 'form']);
			$sitefielddata->set('uikit.articles', ['align']);
		}

		$settings = new stdClass();
		$settings->code = 'articles';

		return $this->renderer($class, [
			'uikitcomp' => $uikitcomp,
			'sitefielddata' => $sitefielddata,
		])->get(['settings' => $settings]);
	}
}
