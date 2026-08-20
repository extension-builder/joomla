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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Component;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\Finalise;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ComponentFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Everything the component still needs once every file has its content.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\Component')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class FinaliseTest extends ArchitectureTestCase
{
	/**
	 * What was written once for the whole component.
	 *
	 * @var    ContentOne|null
	 * @since  6.1.7
	 */
	private ?ContentOne $one = null;

	/**
	 * A site given no views to route routes nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteGivenNoViewsToRouteRoutesNothing(): void
	{
		$this->set();

		$this->assertSame(
			0, $this->one->allActive()['###ROUTER_BUILD_VIEWS###']
		);
	}

	/**
	 * A site given views to route wraps the test the router reads.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteGivenViewsToRouteWrapsTheTestTheRouterReads(): void
	{
		$this->set([], '$view === \'looker\'');

		$this->assertSame(
			'($view === \'looker\')',
			$this->one->allActive()['###ROUTER_BUILD_VIEWS###']
		);
	}

	/**
	 * A component given a readme carries it into the build.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentGivenAReadmeCarriesItIntoTheBuild(): void
	{
		$this->set(['addreadme' => 1, 'readme' => '# Demo']);

		$this->assertSame('# Demo', $this->one->allActive()['###README###']);
	}

	/**
	 * A component given no readme is left without one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentGivenNoReadmeIsLeftWithoutOne(): void
	{
		$this->set();

		$this->assertArrayNotHasKey('###README###', $this->one->allActive());
	}

	/**
	 * A component given a changelog carries it into the build.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentGivenAChangelogCarriesItIntoTheBuild(): void
	{
		$this->set(['changelog' => '## 1.0.0']);

		$this->assertSame('## 1.0.0', $this->one->allActive()['###CHANGELOG###']);
	}

	/**
	 * Joomla 3 builds its site router without the constructor pieces.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeBuildsItsSiteRouterWithoutTheConstructorPieces(): void
	{
		$this->config()->set('joomla_version', 3);

		$this->set();

		$written = $this->one->allActive();

		$this->assertArrayNotHasKey('###SITE_ROUTER_METHODS###', $written);
		$this->assertArrayNotHasKey(
			'###SITE_ROUTER_CONSTRUCTOR_BEFORE_PARENT###', $written
		);
	}

	/**
	 * Every later Joomla builds its site router in full.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryLaterJoomlaBuildsItsSiteRouterInFull(): void
	{
		$this->set();

		$written = $this->one->allActive();

		$this->assertArrayHasKey('###SITE_ROUTER_METHODS###', $written);
		$this->assertArrayHasKey(
			'###SITE_ROUTER_CONSTRUCTOR_BEFORE_PARENT###', $written
		);
		$this->assertArrayHasKey(
			'###SITE_ROUTER_CONSTRUCTOR_AFTER_PARENT###', $written
		);
	}

	/**
	 * The build target the extensions borrowed is handed back.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheBuildTargetTheExtensionsBorrowedIsHandedBack(): void
	{
		$this->config()->build_target = 'site';
		$this->config()->lang_target = 'both';

		$this->set();

		$this->assertSame('site', $this->config()->build_target);
		$this->assertSame('both', $this->config()->lang_target);
		$this->assertSame('COM_DEMO', $this->config()->lang_prefix);
	}

	/**
	 * Finish the build of one component.
	 *
	 * @param   array        $given       What the component was given.
	 * @param   string|null  $routeViews  The view test the compiler collected.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function set(array $given = [], ?string $routeViews = null): void
	{
		$this->one = new ContentOne();

		if ($routeViews !== null)
		{
			$this->one->set('ROUTER_BUILD_VIEWS', $routeViews);
		}

		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));

		foreach ($given as $key => $value)
		{
			$component->set($key, $value);
		}

		$fields = new ComponentFields();
		$fields->set('looker.title', 'text');

		$subject = $this->renderer(Finalise::class, [
			'contentone' => $this->one,
			'config' => $this->config(),
			'component' => $component,
			'componentfields' => $fields
		]);

		$subject->set();
	}
}
