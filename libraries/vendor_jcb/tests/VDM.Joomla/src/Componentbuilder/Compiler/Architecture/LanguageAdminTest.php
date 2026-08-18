<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    18th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Language\Admin;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Languages;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;


/**
 * Registered administrator language contracts.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class LanguageAdminTest extends ArchitectureTestCase
{
	/**
	 * The registry the strings were handed to.
	 *
	 * @var    Languages
	 * @since  6.1.7
	 */
	private Languages $languages;

	/**
	 * Every component gets the strings its administrator side always shows.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryComponentGetsTheStringsItAlwaysShows(): void
	{
		$strings = $this->build();

		$this->assertSame('Demo', $strings['COM_DEMO']);
		$this->assertSame('Demo Dashboard', $strings['COM_DEMO_DASHBOARD']);
		$this->assertSame('No Access Granted!', $strings['COM_DEMO_NO_ACCESS_GRANTED']);
		$this->assertSame(
			'Not found or access denied!', $strings['COM_DEMO_NOT_FOUND_OR_ACCESS_DENIED']
		);
	}

	/**
	 * The strings are handed over sorted, under the tag being built for.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheStringsAreHandedOverSortedUnderTheirTag(): void
	{
		$this->config()->set('lang_tag', 'af-ZA');
		$strings = $this->build();

		$this->assertNotSame([], $strings);

		$keys = array_keys($strings);
		$sorted = $keys;
		sort($sorted, SORT_STRING);

		$this->assertSame($sorted, $keys);
		$this->assertNull($this->languages->get('components.en-GB.admin'));
	}

	/**
	 * The working target is emptied once its strings have been handed over.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheWorkingTargetIsEmptiedOnceHandedOver(): void
	{
		$this->build();

		$this->assertFalse($this->language()->exist('admin'));
	}

	/**
	 * The component's own name and its configuration label are system strings.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheComponentNameAndConfigurationLabelAreSystemStrings(): void
	{
		$this->build();
		$system = $this->language()->getTarget('adminsys');

		$this->assertSame('Demo', $system['COM_DEMO']);
		$this->assertSame('Demo Configuration', $system['COM_DEMO_CONFIGURATION']);
	}

	/**
	 * A component sold under a license explains where to get one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentSoldUnderALicenseSaysWhereToGetOne(): void
	{
		$strings = $this->build([
			'add_license' => 1,
			'license_type' => 3,
			'whmcs_buy_link' => 'https://vdm.dev/buy',
			'companyname' => 'VDM',
		]);

		$this->assertArrayHasKey('NIE_REG_NIE', $strings);
		$this->assertStringContainsString('License not set for Demo.', $strings['NIE_REG_NIE']);
		$this->assertStringContainsString("href='https://vdm.dev/buy'", $strings['NIE_REG_NIE']);
		$this->assertStringContainsString('>VDM</a>', $strings['NIE_REG_NIE']);
	}

	/**
	 * A license of another type says nothing about where to buy one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testALicenseOfAnotherTypeSaysNothingAboutBuying(): void
	{
		$this->assertArrayNotHasKey('NIE_REG_NIE', $this->build([
			'add_license' => 1,
			'license_type' => 2,
		]));
	}

	/**
	 * A component without the importer gets none of its strings.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutTheImporterGetsNoneOfItsStrings(): void
	{
		$this->assertArrayNotHasKey('COM_DEMO_IMPORT_TITLE', $this->build());
	}

	/**
	 * The importer brings its own strings along.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheImporterBringsItsOwnStrings(): void
	{
		$this->config()->set('add_eximport', true);
		$strings = $this->build();

		$this->assertSame('Data Importer', $strings['COM_DEMO_IMPORT_TITLE']);
		$this->assertSame('Export Failed', $strings['COM_DEMO_EXPORT_FAILED']);
		$this->assertSame('-- Ignore This Column --', $strings['COM_DEMO_IMPORT_IGNORE_COLUMN']);
	}

	/**
	 * Strings gathered for both sides are folded into the admin side too.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testStringsGatheredForBothSidesAreFoldedIn(): void
	{
		$this->language()->set('both', 'COM_DEMO_SHARED', 'Shared');
		$this->language()->set('bothadmin', 'COM_DEMO_ADMIN_SHARED', 'Admin Shared');

		$strings = $this->build();

		$this->assertSame('Shared', $strings['COM_DEMO_SHARED']);
		$this->assertSame('Admin Shared', $strings['COM_DEMO_ADMIN_SHARED']);
	}

	/**
	 * Register the admin language over one component declaration.
	 *
	 * @param   array  $component  The component data to seed.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function build(array $component = []): array
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$seeded = new Component($data, $this->createStub(EventInterface::class));

		foreach ($component as $key => $value)
		{
			$seeded->set($key, $value);
		}

		$this->languages = new Languages();
		$subject = $this->renderer(Admin::class, [
			'component' => $seeded,
			'languages' => $this->languages,
		]);

		$this->assertTrue($subject->get('Demo'));

		$tag = $this->config()->get('lang_tag', 'en-GB');

		return (array) $this->languages->get('components.' . $tag . '.admin', []);
	}
}
