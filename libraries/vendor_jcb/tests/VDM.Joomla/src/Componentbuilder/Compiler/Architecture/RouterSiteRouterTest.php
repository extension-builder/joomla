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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Router\SiteRouter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * Generated site router member contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class RouterSiteRouterTest extends ArchitectureTestCase
{
	/**
	 * What the router has already been given, so a member knows to join it.
	 *
	 * @var    ContentOne
	 * @since  6.1.7
	 */
	private ContentOne $one;

	/**
	 * The per-view content the category helper is described in.
	 *
	 * @var    ContentMulti
	 * @since  6.1.7
	 */
	private ContentMulti $multi;

	/**
	 * The category extensions the component's views own.
	 *
	 * @var    Category
	 * @since  6.1.7
	 */
	private Category $category;

	/**
	 * The file structures the run asked to be built.
	 *
	 * @var    array<int, array>
	 * @since  6.1.7
	 */
	private array $built = [];

	/**
	 * Build the site router over registries this test can read back.
	 *
	 * @return  SiteRouter
	 * @since   6.1.7
	 */
	private function subject(): SiteRouter
	{
		$this->one = new ContentOne();
		$this->multi = new ContentMulti();
		$this->category = new Category();

		$structure = $this->createStub(Structure::class);
		$structure->method('build')->willReturnCallback(
			function (array $target, string $type, ?string $fileName = null,
				?array $config = null): bool
			{
				$this->built[] = [$target, $type];

				return true;
			}
		);

		return $this->renderer(SiteRouter::class, [
			'structure' => $structure,
			'category' => $this->category,
			'contentone' => $this->one,
			'contentmulti' => $this->multi,
		]);
	}

	/**
	 * A list view takes a case that reads its id out of the last segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAListViewTakesACaseThatReadsItsId(): void
	{
		$this->assertSame(self::EXPECTED_PARSE_CASE, $this->subject()->parseCase('looks'));
	}

	/**
	 * With no name to build one for, there is no case.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithNoNameThereIsNoCase(): void
	{
		$this->assertSame('', $this->subject()->parseCase(''));
	}

	/**
	 * The first view tested stands alone, and every later one joins it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheFirstViewStandsAloneAndLaterOnesJoinIt(): void
	{
		$subject = $this->subject();
		$view = 'look';

		$this->assertSame("\$view === 'look'", $subject->buildViews($view));

		$this->one->set('ROUTER_BUILD_VIEWS', "\$view === 'look'");

		$this->assertSame(" || \$view === 'look'", $subject->buildViews($view));
	}

	/**
	 * A view that owns no category extension maps nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutACategoryExtensionMapsNothing(): void
	{
		$this->assertSame('', $this->subject()->categoryViews('look', 'looks'));
		$this->assertSame([], $this->built);
	}

	/**
	 * A view that owns a category extension maps it, and gets its helper built.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatOwnsACategoryExtensionMapsIt(): void
	{
		$subject = $this->subject();
		$this->category->set('looks.extension', 'com_demo.looks');

		$this->assertSame(
			PHP_EOL . "\t\t\t\"com_demo.looks\" => \"look\"",
			$subject->categoryViews('look', 'looks')
		);
		$this->assertSame([[['site' => 'categorylook'], 'category']], $this->built);
		$this->assertSame(
			[
				'###otherview###' => 'look',
				'###view###' => 'look',
				'###View###' => 'Look',
				'###views###' => 'looks',
				'###Views###' => 'Looks',
			],
			$this->multi->get('categorylook')
		);
		$this->assertSame(
			self::EXPECTED_CATEGORY_INCLUDE,
			$this->one->get('CATEGORY_CLASS_TREES')
		);
	}

	/**
	 * The second entry in the map is separated from the first.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheSecondMapEntryIsSeparatedFromTheFirst(): void
	{
		$subject = $this->subject();
		$this->category->set('looks.extension', 'com_demo.looks');
		$this->one->set('ROUTER_CATEGORY_VIEWS', 'something');

		$this->assertSame(
			',' . PHP_EOL . "\t\t\t\"com_demo.looks\" => \"look\"",
			$subject->categoryViews('look', 'looks')
		);
	}

	/**
	 * The category helper is only described once, however many views ask.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheCategoryHelperIsOnlyDescribedOnce(): void
	{
		$subject = $this->subject();
		$this->category->set('looks.extension', 'com_demo.looks');

		$subject->categoryViews('look', 'looks');
		$subject->categoryViews('look', 'looks');

		$this->assertCount(1, $this->built);
	}

	/**
	 * A view with no dynamic get of its own still takes its plain case.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutADynamicGetTakesItsPlainCase(): void
	{
		$view = 'look';

		$this->assertSame(
			self::EXPECTED_PARSE_SWITCH,
			$this->subject()->parseSwitch($view)
		);
	}

	/**
	 * The generated router member this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_PARSE_CASE = <<<'GEN'

				case 'looks':
					$id = explode(':', $segments[$count-1]);
					$vars['id'] = (int) $id[0];
					$vars['view'] = 'looks';
				break;
		GEN;

	/**
	 * The generated router member this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_PARSE_SWITCH = <<<'GEN'

					case 'look':
						$vars['view'] = 'look';
						if (is_numeric($segments[$count-1]))
						{
							$vars['id'] = (int) $segments[$count-1];
						}
						elseif ($segments[$count-1])
						{
							$id = $this->getVar('look', $segments[$count-1], 'alias', 'id');
							if($id)
							{
								$vars['id'] = $id;
							}
						}
						break;
		GEN;

	/**
	 * The generated router member this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CATEGORY_INCLUDE = <<<'GEN'

		//Insure this view category file is loaded.
		$classname = 'DemoLookCategories';
		if (!class_exists($classname))
		{
			$path = JPATH_SITE . '/components/com_demo/helpers/categorylook.php';
			if (is_file($path))
			{
				include_once $path;
			}
		}
		GEN;
}
