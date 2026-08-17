<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;


/**
 * Generated Footable asset loader contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedFootableScriptsRendererTest extends ArchitectureTestCase
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
	 * Footable 2 loads its four scripts and its three stylesheets.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testFootableTwoLoadsItsOwnAssets(string $version, int $major): void
	{
		$this->config()->set('footable_version', 2);

		$code = $this->renderer($this->footableClass($version))->get(false);

		$this->assertSame(4, substr_count($code, '/footable-v2/js/'));
		$this->assertSame(3, substr_count($code, '/footable-v2/css/'));
		$this->assertStringContainsString(
			"Html::_('script', 'media/com_demo/footable-v2/js/footable.js'",
			$code
		);
		$this->assertStringContainsString('$this->fooTableStyle', $code);
		$this->assertStringNotContainsString('footable-v3', $code);
		// no init was asked for, so nothing reaches the document
		$this->assertStringNotContainsString('$footable', $code);
	}

	/**
	 * Footable 3 loads one script and the standalone stylesheet.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testFootableThreeLoadsItsOwnAssets(string $version, int $major): void
	{
		$this->config()->set('footable_version', 3);

		$code = $this->renderer($this->footableClass($version))->get(false);

		$this->assertStringContainsString(
			"Html::_('script', 'media/com_demo/footable-v3/js/footable.min.js'",
			$code
		);
		$this->assertStringContainsString('font-awesome.min.css', $code);
		$this->assertStringNotContainsString('footable-v2', $code);
		$this->assertStringNotContainsString('$footable', $code);
	}

	/**
	 * Each target puts the initialisation script on the document its own way.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testTheInlineScriptFollowsTheTarget(string $version, int $major): void
	{
		$subject = $this->renderer($this->footableClass($version));

		foreach ([2, 3] as $footable)
		{
			$this->config()->set('footable_version', $footable);

			$code = $subject->get(true);

			$this->assertStringContainsString('$footable = "jQuery(document)', $code);

			if ($major === 3)
			{
				$this->assertStringContainsString(
					'$this->getDocument()->addScriptDeclaration($footable);',
					$code
				);
				$this->assertStringNotContainsString('getWebAssetManager', $code);

				continue;
			}

			$this->assertStringContainsString(
				'$this->getDocument()->getWebAssetManager()->addInlineScript($footable);',
				$code
			);
			$this->assertStringNotContainsString('addScriptDeclaration', $code);
		}
	}

	/**
	 * Footable 2 alone wires the tab-change table fix.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testOnlyFootableTwoWiresTheTabResizeFix(): void
	{
		$subject = $this->renderer($this->footableClass('JoomlaSix'));

		$this->config()->set('footable_version', 2);
		$this->assertStringContainsString('function tableFix()', $subject->get(true));

		$this->config()->set('footable_version', 3);
		$this->assertStringNotContainsString('function tableFix()', $subject->get(true));
	}

	/**
	 * An unconfigured release loads nothing rather than failing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnUnknownReleaseLoadsNoAssets(): void
	{
		$this->config()->set('footable_version', 9);

		$this->assertSame(
			'',
			$this->renderer($this->footableClass('JoomlaSix'))->get(true)
		);
	}

	/**
	 * Build the Footable scripts class of one target.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  class-string
	 * @since   6.1.7
	 */
	private function footableClass(string $version): string
	{
		// only Joomla 3 declares an inline script without the asset manager
		return $this->targetClass(
			$version, 'AdminView\\FootableScripts', ['JoomlaThree']
		);
	}
}
