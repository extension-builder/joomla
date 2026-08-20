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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\SiteViews;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\SiteViews\Headers;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The site view file headers, keyed on the main get type the view was given.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\SiteViews')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class HeadersTest extends ArchitectureTestCase
{
	/**
	 * What was written for the view.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $multi = null;

	/**
	 * A single item view is given the single view headers.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASingleItemViewIsGivenTheSingleViewHeaders(): void
	{
		$this->set(1, '//');

		$this->assertSame(
			[
				'###SITE_VIEW_MODEL_HEADER###',
				'###SITE_VIEW_HTML_HEADER###',
				'###SITE_VIEW_HEADER###'
			],
			array_keys($this->multi->get('looker'))
		);
	}

	/**
	 * A list view is given the list view headers.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListViewIsGivenTheListViewHeaders(): void
	{
		$this->set(2, '//');

		$this->assertSame(
			[
				'###SITE_VIEWS_MODEL_HEADER###',
				'###SITE_VIEWS_HTML_HEADER###',
				'###SITE_VIEWS_HEADER###'
			],
			array_keys($this->multi->get('looker'))
		);
	}

	/**
	 * A view carrying its own controller code is given a controller header.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewCarryingItsOwnControllerCodeIsGivenAControllerHeader(): void
	{
		$this->set(1, '$this->doSomething();');

		$this->assertArrayHasKey(
			'###SITE_VIEW_CONTROLLER_HEADER###', $this->multi->get('looker')
		);
	}

	/**
	 * A view whose controller code was left empty is given no controller header.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWhoseControllerCodeIsEmptyIsGivenNoControllerHeader(): void
	{
		$this->set(1, '');

		$this->assertArrayNotHasKey(
			'###SITE_VIEW_CONTROLLER_HEADER###', $this->multi->get('looker')
		);
	}

	/**
	 * A view of neither get type is given no headers at all.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewOfNeitherGetTypeIsGivenNoHeadersAtAll(): void
	{
		$this->set(3, '$this->doSomething();');

		$this->assertSame([], $this->multi->allActive());
	}

	/**
	 * Set the file headers of one site view.
	 *
	 * @param   int     $gettype     The main get type the view was given.
	 * @param   string  $controller  The controller code it carries.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function set(int $gettype, string $controller): void
	{
		$this->multi = new ContentMulti();

		$settings = new stdClass();
		$settings->code = 'looker';
		$settings->php_controller = $controller;
		$settings->main_get = new stdClass();
		$settings->main_get->gettype = $gettype;

		$subject = $this->renderer(Headers::class, [
			'contentmulti' => $this->multi
		]);

		$subject->set(['settings' => $settings]);
	}
}
