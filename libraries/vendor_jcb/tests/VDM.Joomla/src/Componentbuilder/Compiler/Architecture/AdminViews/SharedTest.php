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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\AdminViews;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\Shared;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The pieces both views of one admin view share.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class SharedTest extends ArchitectureTestCase
{
	/**
	 * What was written for each view.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $multi = null;

	/**
	 * What was written for the component as a whole.
	 *
	 * @var    ContentOne|null
	 * @since  6.1.7
	 */
	private ?ContentOne $one = null;

	/**
	 * Every admin view is given the pieces both of its views need.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryAdminViewIsGivenThePiecesBothViewsNeed(): void
	{
		$this->build($this->view());

		$written = $this->multi->get('demo');

		$this->assertIsArray($written);
		foreach ([
			'###UNIQUEFIELDS###',
			'###TITLEALIASFIX###',
			'###GENERATENEWTITLE###',
			'###GENERATENEWALIAS###',
			'###MODEL_BATCH_COPY###',
			'###MODEL_BATCH_MOVE###',
			'###JCONTROLLERFORM_ALLOWADD###',
			'###JCONTROLLERFORM_ALLOWEDIT###',
			'###JMODELADMIN_GETFORM###',
			'###JMODELADMIN_ALLOWEDIT###',
			'###JMODELADMIN_CANDELETE###',
			'###JMODELADMIN_CANEDITSTATE###'
		] as $key)
		{
			$this->assertArrayHasKey($key, $written);
		}
	}

	/**
	 * The permissions of every admin view are added to what the component asks.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testThePermissionsOfEveryViewAreAddedToTheComponents(): void
	{
		$this->build($this->view());

		$this->assertNotNull($this->one->get('ACCESS_SECTIONS'));
	}

	/**
	 * A view without the Joomla fields is given none of their permissions.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutTheJoomlaFieldsIsGivenNoneOfTheirPermissions(): void
	{
		$this->build($this->view());
		$without = $this->one->get('ACCESS_SECTIONS');

		$this->build($this->view() + ['joomla_fields' => 1]);
		$with = $this->one->get('ACCESS_SECTIONS');

		$this->assertNotSame($with, $without);
	}

	/**
	 * A view the site can reach is given the routes that reach it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewTheSiteCanReachIsGivenItsRoutes(): void
	{
		$this->build($this->view() + ['edit_create_site_view' => 1]);

		$this->assertNotNull($this->one->get('ROUTER_PARSE_SWITCH'));
		$this->assertNotNull($this->one->get('ROUTER_BUILD_VIEWS'));
	}

	/**
	 * A view the site cannot reach is given no routes.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewTheSiteCannotReachIsGivenNoRoutes(): void
	{
		$this->build($this->view());

		$this->assertNull($this->one->get('ROUTER_PARSE_SWITCH'));
		$this->assertNull($this->one->get('ROUTER_BUILD_VIEWS'));
	}

	/**
	 * The view is told when the compiler is done with it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheViewIsToldWhenTheCompilerIsDoneWithIt(): void
	{
		$fired = [];
		$event = $this->createStub(
			'VDM\\Joomla\\Componentbuilder\\Compiler\\Interfaces\\EventInterface'
		);
		$event->method('trigger')->willReturnCallback(
			static function (string $name, $data = null) use (&$fired): void
			{
				$fired[] = $name;
			}
		);

		$this->build($this->view(), ['event' => $event]);

		$this->assertContains('jcb_ce_onAfterBuildAdminViewContent', $fired);
	}

	/**
	 * Build the pieces both views of one admin view share.
	 *
	 * @param   array  $view       The view being built.
	 * @param   array  $overrides  What the compiler collected.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function build(array $view, array $overrides = []): void
	{
		$this->multi = new ContentMulti();
		$this->one = new ContentOne();

		$subject = $this->renderer(Shared::class, $overrides + [
			'contentmulti' => $this->multi,
			'contentone' => $this->one
		]);

		$single = 'demo';
		$list = 'demos';
		$subject->build($view, $single, $list);
	}

	/**
	 * A view the compiler collected.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(): array
	{
		$settings = new stdClass();
		$settings->name_single_code = 'demo';
		$settings->name_list_code = 'demos';
		$settings->name_single = 'demo';
		$settings->name_list = 'demos';

		return ['settings' => $settings];
	}
}
