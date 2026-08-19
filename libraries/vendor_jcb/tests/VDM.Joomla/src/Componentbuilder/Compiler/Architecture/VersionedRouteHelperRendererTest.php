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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryCode;
use VDM\Joomla\Componentbuilder\Compiler\Builder\HasMenuGlobal;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Tags;


/**
 * Generated route helper contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedRouteHelperRendererTest extends ArchitectureTestCase
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
	 * The targets that ask the router for a menu item directly.
	 *
	 * @return  array<string, array{string,int}>
	 * @since   6.1.7
	 */
	public static function modernVersions(): array
	{
		$versions = self::versions();
		unset($versions['Joomla 3']);

		return $versions;
	}

	/**
	 * Build the route helper of one target over the state this case declares.
	 *
	 * @param   string  $version   Target namespace segment.
	 * @param   int     $major     Joomla target major.
	 * @param   bool    $tagged    Whether the view carries tags.
	 * @param   bool    $category  Whether the view carries its own category.
	 * @param   bool    $menu      Whether the view has a menu item of its own.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function subject(string $version, int $major, bool $tagged = true,
		bool $category = false, bool $menu = false): object
	{
		$this->config()->set('joomla_version', $major);

		$tags = new Tags();
		$categoryCode = new CategoryCode();
		$hasMenu = new HasMenuGlobal();

		if ($tagged)
		{
			$tags->set('look', true);
		}

		if ($category)
		{
			$categoryCode->set('look', 'catid');
		}

		if ($menu)
		{
			$hasMenu->set('look', true);
		}

		return $this->renderer(
			$this->targetClass($version, 'Router\\RouteHelper', ['JoomlaThree']),
			['tags' => $tags, 'categorycode' => $categoryCode, 'hasmenuglobal' => $hasMenu]
		);
	}

	/**
	 * A view with neither tags nor a front item gets no route method.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewThatNeedsNoRouteMethodGetsNone(string $version, int $major): void
	{
		$this->assertSame('', $this->subject($version, $major, false)->get('look', 'looks'));
	}

	/**
	 * A front item view gets one even without tags.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAFrontItemViewGetsOneWithoutTags(string $version, int $major): void
	{
		$this->assertNotSame(
			'',
			$this->subject($version, $major, false)->get('look', 'looks', true)
		);
	}

	/**
	 * A view only ever gets one route method, however often it is asked for.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewOnlyEverGetsOneRouteMethod(string $version, int $major): void
	{
		$subject = $this->subject($version, $major);

		$this->assertNotSame('', $subject->get('look', 'looks'));
		$this->assertSame('', $subject->get('look', 'looks'));
	}

	/**
	 * Later targets build the link and ask the router for the item.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernTargetsBuildTheLinkWithoutNeedles(string $version, int $major): void
	{
		$this->assertSame(
			self::EXPECTED_MODERN_PLAIN,
			$this->subject($version, $major)->get('look', 'looks')
		);
	}

	/**
	 * A category on a later target only adds its id to the link.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testACategoryOnAModernTargetOnlyAddsItsId(string $version, int $major): void
	{
		$this->assertSame(
			self::EXPECTED_MODERN_CATEGORY,
			$this->subject($version, $major, true, true)->get('look', 'looks')
		);
	}

	/**
	 * A view with its own menu item asks the router for it by name.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testAModernTargetAsksTheRouterForItsMenuItem(string $version, int $major): void
	{
		$this->assertSame(
			self::EXPECTED_MODERN_MENU,
			$this->subject($version, $major, true, false, true)->get('look', 'looks')
		);
	}

	/**
	 * Joomla 3 gathers needles and looks the menu item up with them.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeGathersNeedlesAndLooksTheItemUp(): void
	{
		$this->assertSame(
			self::EXPECTED_J3_PLAIN,
			$this->subject('JoomlaThree', 3)->get('look', 'looks')
		);
	}

	/**
	 * A category on Joomla 3 adds the whole category path to the needles.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testACategoryOnJoomlaThreeAddsItsWholePath(): void
	{
		$this->assertSame(
			self::EXPECTED_J3_CATEGORY,
			$this->subject('JoomlaThree', 3, true, true)->get('look', 'looks')
		);
	}

	/**
	 * Joomla 3 passes the view name to the lookup when it has its own item.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeNamesTheViewWhenItHasItsOwnItem(): void
	{
		$this->assertSame(
			self::EXPECTED_J3_MENU,
			$this->subject('JoomlaThree', 3, true, false, true)->get('look', 'looks')
		);
	}

	/**
	 * A view called category is not treated as carrying one.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewCalledCategoryDoesNotCarryOne(string $version, int $major): void
	{
		$this->config()->set('joomla_version', $major);

		$tags = new Tags();
		$tags->set('category', true);
		$categoryCode = new CategoryCode();
		$categoryCode->set('category', 'catid');

		$method = $this->renderer(
			$this->targetClass($version, 'Router\\RouteHelper', ['JoomlaThree']),
			['tags' => $tags, 'categorycode' => $categoryCode]
		)->get('category', 'categories');

		$this->assertStringContainsString('getCategoryRoute($id = 0): string', $method);
		$this->assertStringNotContainsString('$catid', $method);
	}

	/**
	 * The generated route method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN_PLAIN = <<<'GEN'


			/**
			 * Get the URL route for look
			 *
			 * @param   integer  $id     The id of the look
			 *
			 * @return  string  The link to the look
			 *
			 * @since   1.5
			 */
			public static function getLookRoute($id = 0): string
			{
				if ($id > 0)
				{
					// Create the link
					$link = 'index.php?option=com_demo&view=look&id='. $id;
				}
				else
				{
					// Create the link but don't add the id.
					$link = 'index.php?option=com_demo&view=look';
				}

				return $link;
			}
		GEN;

	/**
	 * The generated route method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN_CATEGORY = <<<'GEN'


			/**
			 * Get the URL route for look
			 *
			 * @param   integer  $id     The id of the look
			 * @param   integer  $catid  The id of the look's category
			 *
			 * @return  string  The link to the look
			 *
			 * @since   1.5
			 */
			public static function getLookRoute($id = 0, $catid = 0): string
			{
				if ($id > 0)
				{
					// Create the link
					$link = 'index.php?option=com_demo&view=look&id='. $id;
				}
				else
				{
					// Create the link but don't add the id.
					$link = 'index.php?option=com_demo&view=look';
				}
				if ($catid > 1)
				{
					$link .= '&catid='.$catid;
				}

				return $link;
			}
		GEN;

	/**
	 * The generated route method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_MODERN_MENU = <<<'GEN'


			/**
			 * Get the URL route for look
			 *
			 * @param   integer  $id     The id of the look
			 *
			 * @return  string  The link to the look
			 *
			 * @since   1.5
			 */
			public static function getLookRoute($id = 0): string
			{
				if ($id > 0)
				{
					// Create the link
					$link = 'index.php?option=com_demo&view=look&id='. $id;
				}
				else
				{
					// Create the link but don't add the id.
					$link = 'index.php?option=com_demo&view=look';
				}

				if (($item = self::_findItem('look')) !== null)
				{
					$link .= '&Itemid='.$item;
				}

				return $link;
			}
		GEN;

	/**
	 * The generated route method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_PLAIN = <<<'GEN'


			/**
			 * Get the URL route for look
			 *
			 * @param   integer  $id     The id of the look
			 *
			 * @return  string  The link to the look
			 *
			 * @since   1.5
			 */
			public static function getLookRoute($id = 0): string
			{
				if ($id > 0)
				{
					// Initialize the needel array.
					$needles = array(
						'look'  => array((int) $id)
					);
					// Create the link
					$link = 'index.php?option=com_demo&view=look&id='. $id;
				}
				else
				{
					// Initialize the needel array.
					$needles = array(
						'look'  => array()
					);
					// Create the link but don't add the id.
					$link = 'index.php?option=com_demo&view=look';
				}

				if ($item = self::_findItem($needles))
				{
					$link .= '&Itemid='.$item;
				}

				return $link;
			}
		GEN;

	/**
	 * The generated route method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_CATEGORY = <<<'GEN'


			/**
			 * Get the URL route for look
			 *
			 * @param   integer  $id     The id of the look
			 * @param   integer  $catid  The id of the look's category
			 *
			 * @return  string  The link to the look
			 *
			 * @since   1.5
			 */
			public static function getLookRoute($id = 0, $catid = 0): string
			{
				if ($id > 0)
				{
					// Initialize the needel array.
					$needles = array(
						'look'  => array((int) $id)
					);
					// Create the link
					$link = 'index.php?option=com_demo&view=look&id='. $id;
				}
				else
				{
					// Initialize the needel array.
					$needles = array(
						'look'  => array()
					);
					// Create the link but don't add the id.
					$link = 'index.php?option=com_demo&view=look';
				}
				if ($catid > 1)
				{
					$categories = Categories::getInstance('demo.looks');
					$category = $categories->get($catid);
					if ($category)
					{
						$needles['category'] = array_reverse($category->getPath());
						$needles['categories'] = $needles['category'];
						$link .= '&catid='.$catid;
					}
				}

				if ($item = self::_findItem($needles))
				{
					$link .= '&Itemid='.$item;
				}

				return $link;
			}
		GEN;

	/**
	 * The generated route method this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_MENU = <<<'GEN'


			/**
			 * Get the URL route for look
			 *
			 * @param   integer  $id     The id of the look
			 *
			 * @return  string  The link to the look
			 *
			 * @since   1.5
			 */
			public static function getLookRoute($id = 0): string
			{
				if ($id > 0)
				{
					// Initialize the needel array.
					$needles = array(
						'look'  => array((int) $id)
					);
					// Create the link
					$link = 'index.php?option=com_demo&view=look&id='. $id;
				}
				else
				{
					// Initialize the needel array.
					$needles = array(
						'look'  => array()
					);
					// Create the link but don't add the id.
					$link = 'index.php?option=com_demo&view=look';
				}

				if ($item = self::_findItem($needles, 'look'))
				{
					$link .= '&Itemid='.$item;
				}

				return $link;
			}
		GEN;
}
