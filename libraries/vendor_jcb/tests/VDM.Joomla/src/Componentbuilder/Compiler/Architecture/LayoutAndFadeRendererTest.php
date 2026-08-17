<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    16th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminView\FadeInEffect;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Layout\View as LayoutView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LayoutData;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HeaderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Templatelayout\Data as TemplatelayoutData;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * View layout and fade-in effect contracts.
 *
 * @since  6.1.7
 */
#[CoversClass(FadeInEffect::class)]
#[CoversClass(LayoutView::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class LayoutAndFadeRendererTest extends ArchitectureTestCase
{
	/**
	 * A view without the effect renders only the loader container.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testFadeInEffectIsOnlyTheContainerWhenDisabled(): void
	{
		$subject = new FadeInEffect($this->config());

		$this->assertSame(
			'<div id="demo_loader">',
			$subject->get($this->view(0))
		);
	}

	/**
	 * An enabled effect builds the overlay and hides the loader container.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testFadeInEffectBuildsTheLoadingOverlay(): void
	{
		$subject = new FadeInEffect($this->config());
		$code = $subject->get($this->view(1));

		$this->assertStringStartsWith('<script type="text/javascript">', $code);
		$this->assertStringContainsString("loadingDiv.id = 'loading';", $code);
		$this->assertStringContainsString(
			"url('components/com_demo/assets/images/ajax.gif')",
			$code
		);
		$this->assertStringContainsString(
			"var componentLoader = document.getElementById('demo_loader');",
			$code
		);
		$this->assertStringContainsString(
			"window.addEventListener('load', function() {",
			$code
		);
		$this->assertStringEndsWith(
			'<div id="demo_loader" style="display: none;">',
			$code
		);
	}

	/**
	 * A layout without an override stores its generated items.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testLayoutWithoutOverrideStoresItsItems(): void
	{
		$contentmulti = new ContentMulti();

		$structure = $this->structure();
		$structure->expects($this->once())
			->method('build')
			->with(['admin' => 'article'], 'layout', 'metadata')
			->willReturn(true);

		$subject = $this->layoutView($contentmulti, $structure);
		$subject->set('article', 'metadata', '<field name="x" />', 'layout');

		$this->assertSame(
			'<field name="x" />',
			$contentmulti->get('article_metadata|LAYOUTITEMS')
		);
	}

	/**
	 * An empty layout falls back to the placeholder marker entry.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEmptyLayoutStoresTheMarkerEntry(): void
	{
		$contentmulti = new ContentMulti();

		$structure = $this->createStub(Structure::class);
		$structure->method('build')->willReturn(true);

		$subject = $this->layoutView($contentmulti, $structure);
		$subject->set('article', 'metadata', '', 'layout');

		$this->assertNull($contentmulti->get('article_metadata|LAYOUTITEMS'));
		$this->assertSame('boom', $contentmulti->get('article_metadata|bogus'));
	}

	/**
	 * A both-areas language target builds the layout for the site area too.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testBothLanguageTargetBuildsTheSiteLayout(): void
	{
		$this->config()->set('lang_target', 'both');

		$targets = [];
		$structure = $this->structure();
		$structure->expects($this->exactly(2))
			->method('build')
			->willReturnCallback(
				static function (array $target) use (&$targets): bool
				{
					$targets[] = $target;

					return true;
				}
			);

		$subject = $this->layoutView(new ContentMulti(), $structure);
		$subject->set('article', 'metadata', 'items', 'layout');

		$this->assertSame(
			[['admin' => 'article'], ['site' => 'article']],
			$targets
		);
	}

	/**
	 * A matching override emits its header, code and body instead.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testMatchingOverrideEmitsItsOwnLayout(): void
	{
		$contentmulti = new ContentMulti();

		$layoutdata = new LayoutData();
		$layoutdata->set('admin.demoarticlemetadata', [
			'php_view' => '$x = 1;',
			'html' => '<div>###LAYOUTITEMS###</div>',
		]);

		// only the most specific override key resolves
		$templatelayout = $this->createStub(TemplatelayoutData::class);
		$templatelayout->method('set')
			->willReturnCallback(
				static function (string $a, string $b, bool $c, array $d, array $keys): bool
				{
					return $keys === ['demoarticlemetadata'];
				}
			);

		$header = $this->createStub(HeaderInterface::class);
		$header->method('get')->willReturn('use Joomla\CMS\Factory;');

		$structure = $this->structure();
		$structure->expects($this->once())
			->method('build')
			->with(['admin' => 'article'], 'layoutoverride', 'metadata')
			->willReturn(true);

		$subject = $this->layoutView(
			$contentmulti, $structure, $layoutdata, $templatelayout, $header
		);
		$subject->set('article', 'metadata', '<field name="x" />', 'layout');

		$this->assertSame(
			PHP_EOL . PHP_EOL . '$x = 1;',
			$contentmulti->get('article_metadata|OVERRIDE_LAYOUT_CODE')
		);
		$this->assertSame(
			PHP_EOL . '<div><field name="x" /></div>',
			$contentmulti->get('article_metadata|OVERRIDE_LAYOUT_BODY')
		);
		$this->assertSame(
			PHP_EOL . PHP_EOL . 'use Joomla\CMS\Factory;',
			$contentmulti->get('article_metadata|OVERRIDE_LAYOUT_HEADER')
		);
		// the generated-items branch is skipped entirely
		$this->assertNull($contentmulti->get('article_metadata|LAYOUTITEMS'));
	}

	/**
	 * A claimed override is removed so it cannot be emitted twice.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testClaimedOverrideIsRemovedFromTheRegistry(): void
	{
		$layoutdata = new LayoutData();
		$layoutdata->set('admin.metadata', ['php_view' => '', 'html' => '']);

		$templatelayout = $this->createStub(TemplatelayoutData::class);
		$templatelayout->method('set')
			->willReturnCallback(
				static function (string $a, string $b, bool $c, array $d, array $keys): bool
				{
					return $keys === ['metadata'];
				}
			);

		$structure = $this->createStub(Structure::class);
		$structure->method('build')->willReturn(true);

		$subject = $this->layoutView(
			new ContentMulti(), $structure, $layoutdata, $templatelayout
		);
		$subject->set('article', 'metadata', 'items', 'layout');

		$this->assertNull($layoutdata->get('admin.metadata'));
	}

	/**
	 * Create a view definition with the effect switch.
	 *
	 * @param   int  $addFadein  The configured fade-in switch.
	 *
	 * @return  array{settings: \stdClass}
	 * @since   6.1.7
	 */
	private function view(int $addFadein): array
	{
		$settings = new \stdClass();
		$settings->add_fadein = $addFadein;

		return ['settings' => $settings];
	}

	/**
	 * Create the layout service with real registries.
	 *
	 * @param   ContentMulti              $contentmulti     The contextual content registry.
	 * @param   Structure                 $structure        The structure double.
	 * @param   LayoutData|null           $layoutdata       The layout-override registry.
	 * @param   TemplatelayoutData|null   $templatelayout   The template-layout double.
	 * @param   HeaderInterface|null      $header           The header double.
	 *
	 * @return  LayoutView
	 * @since   6.1.7
	 */
	private function layoutView(
		ContentMulti $contentmulti,
		Structure $structure,
		?LayoutData $layoutdata = null,
		?TemplatelayoutData $templatelayout = null,
		?HeaderInterface $header = null
	): LayoutView
	{
		if ($templatelayout === null)
		{
			$templatelayout = $this->createStub(TemplatelayoutData::class);
			$templatelayout->method('set')->willReturn(false);
		}

		return new LayoutView(
			$this->config(),
			$this->placeholder(),
			$contentmulti,
			$layoutdata ?? new LayoutData(),
			$templatelayout,
			$header ?? $this->createStub(HeaderInterface::class),
			$structure
		);
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
