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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\SiteStatics;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Everything the site side of the component needs whether or not it has views.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\Component')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class SiteStaticsTest extends ArchitectureTestCase
{
	/**
	 * What was written once for the whole site.
	 *
	 * @var    ContentOne|null
	 * @since  6.1.7
	 */
	private ?ContentOne $one = null;

	/**
	 * A component keeping neither site folder is left alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentKeepingNeitherSiteFolderIsLeftAlone(): void
	{
		$this->set(true, true, 0);

		$this->assertSame([], $this->one->allActive());
		$this->assertSame('admin', $this->config()->build_target);
	}

	/**
	 * A component keeping only the site edit folder still gets the statics.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentKeepingOnlyTheSiteEditFolderStillGetsTheStatics(): void
	{
		$this->set(true, false, 0);

		$this->assertSame('site', $this->config()->build_target);
		$this->assertArrayHasKey(
			'###SITE_CUSTOM_HELPER_SCRIPT###', $this->one->allActive()
		);
	}

	/**
	 * A site given no default view redirects to the root.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteGivenNoDefaultViewRedirectsToTheRoot(): void
	{
		$this->set(false, false, 0);

		$this->assertSame('', $this->one->allActive()['###SITE_DEFAULT_VIEW###']);
	}

	/**
	 * A site given a default view keeps the one it was given.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testASiteGivenADefaultViewKeepsTheOneItWasGiven(): void
	{
		$this->set(false, false, 0, 'looker');

		$this->assertSame(
			'looker', $this->one->allActive()['###SITE_DEFAULT_VIEW###']
		);
	}

	/**
	 * A component given no site event leaves both event placeholders empty.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentGivenNoSiteEventLeavesBothPlaceholdersEmpty(): void
	{
		$this->set(false, false, 0);

		$written = $this->one->allActive();

		$this->assertSame('', $written['###SITE_GLOBAL_EVENT###']);
		$this->assertSame('', $written['###SITE_GLOBAL_EVENT_HELPER###']);
	}

	/**
	 * A component given a site event is given the method that triggers it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentGivenASiteEventIsGivenTheMethodThatTriggersIt(): void
	{
		$this->set(false, false, 1);

		$written = $this->one->allActive();

		$this->assertStringContainsString(
			'Helper::globalEvent(Factory::getDocument());',
			$written['###SITE_GLOBAL_EVENT###']
		);
		$this->assertStringContainsString(
			'public static function globalEvent($document)',
			$written['###SITE_GLOBAL_EVENT_HELPER###']
		);
	}

	/**
	 * Set the site statics of one component.
	 *
	 * @param   bool         $removeSite      Whether the site folder is dropped.
	 * @param   bool         $removeSiteEdit  Whether the site edit folder is dropped.
	 * @param   int          $siteEvent       Whether a global site event was given.
	 * @param   string|null  $defaultView     The default view already set, if any.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function set(
		bool $removeSite,
		bool $removeSiteEdit,
		int $siteEvent,
		?string $defaultView = null
	): void
	{
		$this->one = new ContentOne();

		if ($defaultView !== null)
		{
			$this->one->set('SITE_DEFAULT_VIEW', $defaultView);
		}

		$this->config()->remove_site_folder = $removeSite;
		$this->config()->remove_site_edit_folder = $removeSiteEdit;

		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));
		$component->set('add_site_event', $siteEvent);

		$dispenser = (new ReflectionClass(Dispenser::class))->newInstanceWithoutConstructor();
		$dispenser->hub = [
			'component_php_helper_site' => 'echo "helper";',
			'component_php_site_event' => 'echo "event";'
		];

		$subject = $this->renderer(SiteStatics::class, [
			'contentone' => $this->one,
			'config' => $this->config(),
			'placeholder' => $this->placeholder(),
			'component' => $component,
			'dispenser' => $dispenser
		]);

		$subject->set();
	}
}
