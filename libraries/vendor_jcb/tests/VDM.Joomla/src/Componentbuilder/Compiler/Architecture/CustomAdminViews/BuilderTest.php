<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    20th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\CustomAdminViews;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomAdminViews\Builder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Every custom admin view the component was given.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomAdminViews')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class BuilderTest extends ArchitectureTestCase
{
	/**
	 * What was written for each view.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $multi = null;

	/**
	 * A component given no custom admin views is left alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentGivenNoCustomAdminViewsIsLeftAlone(): void
	{
		$this->build([]);

		$this->assertSame([], $this->multi->allActive());
		$this->assertSame('admin', $this->config()->build_target);
	}

	/**
	 * A custom admin view is named to everything built for it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACustomAdminViewIsNamedToEverythingBuiltForIt(): void
	{
		$this->build([$this->view('looker')]);

		$written = $this->multi->get('looker');

		$this->assertIsArray($written);
		$this->assertSame('Looker', $written['###SView###']);
		$this->assertSame('looker', $written['###sview###']);
		$this->assertSame('LOOKER', $written['###SVIEW###']);
	}

	/**
	 * The custom admin target is the one the views are built under.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheCustomAdminTargetIsTheOneTheyAreBuiltUnder(): void
	{
		$this->build([$this->view('looker')]);

		$this->assertSame('custom_admin', $this->config()->build_target);
		$this->assertSame('admin', $this->config()->lang_target);
	}

	/**
	 * Every custom admin view the component was given is built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryCustomAdminViewGivenIsBuilt(): void
	{
		$this->build([$this->view('looker'), $this->view('seeker')]);

		$this->assertSame(
			['looker', 'seeker'], array_keys($this->multi->allActive())
		);
	}

	/**
	 * Build every custom admin view of one component.
	 *
	 * @param   array  $views  The views the component was given.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function build(array $views): void
	{
		$this->multi = new ContentMulti();

		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));

		if ($views !== [])
		{
			$component->set('custom_admin_views', $views);
		}

		$subject = $this->renderer(Builder::class, [
			'contentmulti' => $this->multi,
			'component' => $component
		]);

		$subject->build();
	}

	/**
	 * A custom admin view the compiler collected.
	 *
	 * @param   string  $code  Its code name.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(string $code): array
	{
		$settings = new stdClass();
		$settings->code = $code;
		$settings->Code = ucfirst($code);
		$settings->CODE = strtoupper($code);
		$settings->name = ucfirst($code);
		$settings->description = '';
		$settings->add_php_view = 0;
		$settings->add_php_jview = 0;
		$settings->add_php_jview_display = 0;
		$settings->add_php_document = 0;
		$settings->add_css = 0;
		$settings->add_javascript_file = 0;
		$settings->main_get = new stdClass();
		$settings->main_get->gettype = 1;
		$settings->default = '';

		// every custom admin view the compiler collects carries its icon
		return ['settings' => $settings, 'icomoon' => 'demo-icon'];
	}
}
