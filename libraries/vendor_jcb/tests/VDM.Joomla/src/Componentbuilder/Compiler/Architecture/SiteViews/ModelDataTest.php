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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\SiteViews\ModelData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The site view model data, keyed on the main get type the view was given.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\SiteViews')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ModelDataTest extends ArchitectureTestCase
{
	/**
	 * What was written for the view.
	 *
	 * @var    ContentMulti|null
	 * @since  6.1.7
	 */
	private ?ContentMulti $multi = null;

	/**
	 * A single item view is given the item side of the model.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASingleItemViewIsGivenTheItemSideOfTheModel(): void
	{
		$this->set(1);

		$this->assertSame(
			[
				'###USER_PERMISSION_CHECK_ACCESS###',
				'###SITE_BEFORE_GET_ITEM###',
				'###SITE_GET_ITEM###',
				'###SITE_AFTER_GET_ITEM###'
			],
			array_keys($this->multi->get('looker'))
		);
	}

	/**
	 * A list view is given the items side of the model.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListViewIsGivenTheItemsSideOfTheModel(): void
	{
		$this->set(2);

		$this->assertSame(
			[
				'###USER_PERMISSION_CHECK_ACCESS###',
				'###SITE_GET_LIST_QUERY###',
				'###SITE_BEFORE_GET_ITEMS###',
				'###SITE_GET_ITEMS###',
				'###SITE_AFTER_GET_ITEMS###'
			],
			array_keys($this->multi->get('looker'))
		);
	}

	/**
	 * A view of neither get type is given no model data at all.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewOfNeitherGetTypeIsGivenNoModelDataAtAll(): void
	{
		$this->set(3);

		$this->assertSame([], $this->multi->allActive());
	}

	/**
	 * The data is filed under the code name of the view it was built for.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheDataIsFiledUnderTheCodeNameOfTheView(): void
	{
		$this->set(1, 'seeker');

		$this->assertSame(['seeker'], array_keys($this->multi->allActive()));
	}

	/**
	 * Set the model data of one site view.
	 *
	 * @param   int     $gettype  The main get type the view was given.
	 * @param   string  $code     Its code name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function set(int $gettype, string $code = 'looker'): void
	{
		$this->multi = new ContentMulti();

		$subject = $this->renderer(ModelData::class, [
			'contentmulti' => $this->multi,
			'config' => $this->config()
		]);

		$subject->set($this->view($gettype, $code));
	}

	/**
	 * A site view the compiler collected.
	 *
	 * @param   int     $gettype  The main get type it was given.
	 * @param   string  $code     Its code name.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(int $gettype, string $code): array
	{
		$settings = new stdClass();
		$settings->code = $code;
		$settings->Code = ucfirst($code);
		$settings->CODE = strtoupper($code);
		$settings->main_get = new stdClass();
		$settings->main_get->gettype = $gettype;
		$settings->main_get->main_get = [];

		return ['settings' => $settings];
	}
}
