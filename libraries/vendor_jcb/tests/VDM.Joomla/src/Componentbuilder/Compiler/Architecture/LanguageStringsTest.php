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
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Language\AdminSys;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Language\Site;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Language\SiteSys;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Languages;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Language;


/**
 * Registered language string contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class LanguageStringsTest extends ArchitectureTestCase
{
	/**
	 * The language the strings are gathered in before they are moved.
	 *
	 * @var    Language
	 * @since  6.1.7
	 */
	private Language $strings;

	/**
	 * The languages of the component being built, where they end up.
	 *
	 * @var    Languages
	 * @since  6.1.7
	 */
	private Languages $languages;

	/**
	 * The events the compiler was given the chance to answer.
	 *
	 * @var    array
	 * @since  6.1.7
	 */
	private array $events = [];

	/**
	 * Build one of the language string writers.
	 *
	 * @param   string  $class  Which writer.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function subject(string $class): object
	{
		$this->strings = new Language($this->config());
		$this->languages = new Languages();
		$this->events = [];
		$test = $this;

		$event = new class($test) implements EventInterface
		{
			/**
			 * The test to record against.
			 *
			 * @var    object
			 * @since  6.1.7
			 */
			private object $test;

			/**
			 * Constructor.
			 *
			 * @param   object  $test  The test to record against.
			 *
			 * @since   6.1.7
			 */
			public function __construct(object $test)
			{
				$this->test = $test;
			}

			/**
			 * Record the event the compiler was given.
			 *
			 * @param   string  $event    The event name.
			 * @param   mixed   $data     What it carries.
			 * @param   mixed   $default  What to answer with.
			 *
			 * @return  mixed
			 * @since   6.1.7
			 */
			public function trigger(string $event, $data = null, $default = null)
			{
				$this->test->recordEvent($event);

				return null;
			}
		};

		return $this->renderer($class, [
			'language' => $this->strings,
			'languages' => $this->languages,
			'event' => $event,
		]);
	}

	/**
	 * Record an event the compiler was given.
	 *
	 * @param   string  $event  The event name.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function recordEvent(string $event): void
	{
		$this->events[] = $event;
	}

	/**
	 * The strings that reached the component being built.
	 *
	 * @param   string  $side  Which side of the component.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function registered(string $side): array
	{
		$langTag = $this->config()->get('lang_tag', 'en-GB');

		return (array) $this->languages->get("components.{$langTag}.{$side}");
	}

	/**
	 * The site side is given the name of the component it belongs to.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheSiteSideIsGivenTheComponentName(): void
	{
		$subject = $this->subject(Site::class);

		$this->assertTrue($subject->get('Demo'));
		$this->assertSame('Demo', $this->registered('site')['COM_DEMO'] ?? null);
	}

	/**
	 * The site side is given the toolbar strings every view uses.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheSiteSideIsGivenTheToolbarStrings(): void
	{
		$subject = $this->subject(Site::class);
		$subject->get('Demo');

		$registered = $this->registered('site');

		$this->assertSame('Save', $registered['JTOOLBAR_APPLY'] ?? null);
		$this->assertSame('Cancel', $registered['JTOOLBAR_CANCEL'] ?? null);
	}

	/**
	 * The compiler is given the chance to add its own before they are read.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheCompilerIsGivenTheChanceToAddItsOwn(): void
	{
		$subject = $this->subject(Site::class);
		$subject->get('Demo');

		$this->assertContains('jcb_ce_onBeforeBuildSiteLang', $this->events);
	}

	/**
	 * The strings the site side needs before it is installed name the component.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheSiteInstallStringsNameTheComponent(): void
	{
		$subject = $this->subject(SiteSys::class);

		$this->assertTrue($subject->get('Demo'));
		$this->assertSame('Demo', $this->registered('sitesys')['COM_DEMO'] ?? null);
	}

	/**
	 * The strings gathered for the administrator install reach the component.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheGatheredAdministratorInstallStringsReachTheComponent(): void
	{
		$subject = $this->subject(AdminSys::class);
		$this->strings->set('adminsys', 'COM_DEMO_MENU', 'Demo');

		$this->assertTrue($subject->get());
		$this->assertSame('Demo', $this->registered('adminsys')['COM_DEMO_MENU'] ?? null);
	}

	/**
	 * With nothing gathered for it there is nothing to move, and it says so.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWithNothingGatheredTheAdministratorInstallSaysSo(): void
	{
		$subject = $this->subject(AdminSys::class);

		$this->assertFalse($subject->get());
	}
}
