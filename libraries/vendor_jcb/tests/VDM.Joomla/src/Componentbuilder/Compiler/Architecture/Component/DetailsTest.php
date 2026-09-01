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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Component;


use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\Details;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\ImageType;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Component\Placeholder as ComponentPlaceholder;
use VDM\Joomla\Componentbuilder\Compiler\Creator\AccessSections;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsets;
use VDM\Joomla\Componentbuilder\Compiler\Creator\EmailHelper;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Helper;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Valuation;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * What a component says about itself, before any of its views are built.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture\Component')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class DetailsTest extends ArchitectureTestCase
{
	/**
	 * What the component was filled in with.
	 *
	 * @var    ContentOne|null
	 * @since  6.1.7
	 */
	private ?ContentOne $content = null;

	/**
	 * What the compile counted.
	 *
	 * @var    Counter|null
	 * @since  6.1.7
	 */
	private ?Counter $counter = null;

	/**
	 * What a component says about itself, and what the compiler writes for it.
	 *
	 * @return  array<string, array{string, string, string}>
	 * @since   6.1.7
	 */
	public static function saidOfItself(): array
	{
		return [
			'the company that wrote it' => ['companyname', 'VDM', 'COMPANYNAME'],
			'who wrote it' => ['author', 'Llewellyn', 'AUTHOR'],
			'where to reach them' => ['email', 'llewellyn@vdm.io', 'AUTHOREMAIL'],
			'their website' => ['website', 'https://dev.vdm.io', 'AUTHORWEBSITE'],
			'the copyright' => ['copyright', 'Copyright (C) 2026', 'COPYRIGHT'],
			'the licence' => ['license', 'GNU GPL v2', 'LICENSE'],
			'what it is for' => ['description', 'A demo component.', 'DESCRIPTION']
		];
	}

	/**
	 * The version tweak each option asks for.
	 *
	 * @return  array<string, array{int, string}>
	 * @since   6.1.7
	 */
	public static function versionOptions(): array
	{
		return [
			'the whole version' => [0, '1.2.3'],
			'the first two, then x' => [2, '1.2.x'],
			'the first, then x.x' => [3, '1.x.x']
		];
	}

	/**
	 * Everything the component says about itself is written down.
	 *
	 * @param   string  $said         What the component was asked.
	 * @param   string  $answer       What it said.
	 * @param   string  $placeholder  What the compiler writes it as.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('saidOfItself')]
	public function testWhatTheComponentSaysOfItselfIsWrittenDown(
		string $said, string $answer, string $placeholder
	): void
	{
		$this->fill([$said => '  ' . $answer . '  ']);

		$this->assertSame($answer, $this->content->get($placeholder));
	}

	/**
	 * A component that was asked nothing answers with nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentAskedNothingAnswersWithNothing(): void
	{
		$this->fill();

		foreach (['AUTHOREMAIL', 'AUTHORWEBSITE', 'COPYRIGHT', 'LICENSE', 'VERSION'] as $key)
		{
			$this->assertSame('', $this->content->get($key));
		}
	}

	/**
	 * The version is written the way the component asked for it.
	 *
	 * @param   int     $option    Which part of the version to keep.
	 * @param   string  $expected  What the compiler writes.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versionOptions')]
	public function testTheVersionIsWrittenTheWayItWasAskedFor(int $option, string $expected): void
	{
		$this->fill(['component_version' => '1.2.3', 'mvc_versiondate' => $option]);

		$this->assertSame($expected, $this->content->get('VERSION'));
		$this->assertSame($expected, $this->content->get('GLOBALVERSION'));
	}

	/**
	 * The version the component really carries is kept beside the written one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheRealVersionIsKeptBesideTheWrittenOne(): void
	{
		$this->fill(['component_version' => '1.2.3', 'mvc_versiondate' => 3]);

		$this->assertSame('1.2.3', $this->content->get('ACTUALVERSION'));
		$this->assertSame('1.x.x', $this->content->get('VERSION'));
	}

	/**
	 * A version with no dots in it is left as it was given.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAVersionWithNoDotsIsLeftAsGiven(): void
	{
		$this->fill(['component_version' => '3', 'mvc_versiondate' => 2]);

		$this->assertSame('3', $this->content->get('VERSION'));
	}

	/**
	 * When the component was created and when it was built are both written.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWhenItWasCreatedAndBuiltAreBothWritten(): void
	{
		$this->config()->set('build_date', '2026-08-19 00:00:00');
		$this->fill(['created' => '2015-04-01 00:00:00']);

		$this->assertSame('1st April, 2015', $this->content->get('CREATIONDATE'));
		$this->assertSame('1st April, 2015', $this->content->get('GLOBALCREATIONDATE'));
		$this->assertSame('19th August, 2026', $this->content->get('BUILDDATE'));
		$this->assertSame('19th August, 2026', $this->content->get('GLOBALBUILDDATE'));
	}

	/**
	 * The target's own manifest version is written for it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheTargetsManifestVersionIsWrittenForIt(): void
	{
		$this->fill();

		$this->assertSame(
			$this->config()->joomla_versions[$this->config()->joomla_version]['xml_version'],
			$this->content->get('XMLVERSION')
		);
	}

	/**
	 * A component that asked for no global event is given an empty one.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutAGlobalEventIsGivenAnEmptyOne(): void
	{
		$this->fill();

		$this->assertSame('', $this->content->get('ADMIN_GLOBAL_EVENT'));
		$this->assertSame('', $this->content->get('ADMIN_GLOBAL_EVENT_HELPER'));
	}

	/**
	 * A component that asked for a global event is given the method that runs it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithAGlobalEventIsGivenTheMethodThatRunsIt(): void
	{
		$this->fill(['add_admin_event' => 1], ['component_php_admin_event' => '// my event']);

		$this->assertStringContainsString(
			'Helper::globalEvent(Factory::getDocument());',
			$this->content->get('ADMIN_GLOBAL_EVENT')
		);
		$this->assertStringContainsString(
			'public static function globalEvent($document)',
			$this->content->get('ADMIN_GLOBAL_EVENT_HELPER')
		);
		$this->assertStringContainsString(
			'// my event', $this->content->get('ADMIN_GLOBAL_EVENT_HELPER')
		);
	}

	/**
	 * A component that ships a readme has it listed among its files.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentThatShipsAReadmeHasItListed(): void
	{
		$this->fill(['addreadme' => 1]);

		$this->assertStringContainsString(
			'<filename>README.txt</filename>', $this->content->get('EXSTRA_ADMIN_FILES')
		);
	}

	/**
	 * A component without a readme lists no readme.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutAReadmeListsNone(): void
	{
		$this->fill();

		$this->assertNull($this->content->get('EXSTRA_ADMIN_FILES'));
	}

	/**
	 * The scripts and styles the component carries are written for both sides.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheScriptsAndStylesItCarriesAreWritten(): void
	{
		$this->fill([], [
			'component_js' => '// js',
			'component_css_admin' => '/* admin */',
			'component_css_site' => '/* site */',
			'component_php_helper_admin' => '// admin helper',
			'component_php_helper_both' => '// both helper'
		]);

		$this->assertSame('// js', $this->content->get('ADMINJS'));
		$this->assertSame('// js', $this->content->get('SITEJS'));
		$this->assertSame('/* admin */', $this->content->get('ADMINCSS'));
		$this->assertSame('/* site */', $this->content->get('SITECSS'));
		$this->assertSame('// admin helper', $this->content->get('CUSTOM_HELPER_SCRIPT'));
		$this->assertSame('// both helper', $this->content->get('BOTH_CUSTOM_HELPER_SCRIPT'));
	}

	/**
	 * The router placeholders start empty, for the views to add to.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheRouterPlaceholdersStartEmpty(): void
	{
		$this->fill();

		$this->assertSame('', $this->content->get('ROUTER_PARSE_SWITCH'));
		$this->assertSame('', $this->content->get('ROUTER_BUILD_VIEWS'));
		$this->assertSame('', $this->content->get('CATEGORY_CLASS_TREES'));
	}

	/**
	 * The language the config fieldsets were built under is put back.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheLanguageTheFieldsetsWereBuiltUnderIsPutBack(): void
	{
		$this->config()->set('lang_target', 'site');
		$this->fill();

		$this->assertSame('site', $this->config()->lang_target);
	}

	/**
	 * When the project started is counted from when the component was created.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testWhenTheProjectStartedIsCountedFromItsCreation(): void
	{
		$this->fill(['created' => '2015-04-01 00:00:00']);

		$this->assertSame(
			strtotime('2015-04-01 00:00:00'), $this->counter->projectStart
		);
	}

	/**
	 * A component without an api installs no api folder.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutAnApiInstallsNoApiFolder(): void
	{
		$this->fill();

		$this->assertSame('', $this->content->get('API_FILES'));
	}

	/**
	 * A component with an api installs the api folder.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithAnApiInstallsTheApiFolder(): void
	{
		$this->config()->set('add_api', 1);
		$this->fill();

		$this->assertSame(
			"\n\n\t<api>\n\t\t<files folder=\"api\">\n\t\t\t<filename>index.html</filename>\n\t\t\t<folder>src</folder>\n\t\t</files>\n\t</api>",
			$this->content->get('API_FILES')
		);
	}

	/**
	 * Fill in what one component says about itself.
	 *
	 * @param   array  $said       What the component was asked, and answered.
	 * @param   array  $customCode What custom code it carries.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	private function fill(array $said = [], array $customCode = []): void
	{
		// every component JCB builds was created at some point
		$component = $this->seeded($said + ['created' => '2015-04-01 00:00:00']);
		$this->content = new ContentOne();

		// the compiler resolves these from the site it runs on, which this
		// test is not; naming them keeps the lookup from being attempted
		$this->config()->set('power_library_folder', 'libraries/jcb_powers');
		$this->config()->set('jcb_powers_path', 'libraries/jcb_powers');
		$this->config()->set('component_guid', 'demo-guid');

		$dispenser = (new ReflectionClass(Dispenser::class))->newInstanceWithoutConstructor();
		$dispenser->hub = [
			'component_js' => '',
			'component_css_admin' => '',
			'component_css_site' => '',
			'component_php_helper_admin' => '',
			'component_php_helper_both' => '',
			'component_php_admin_event' => ''
		] + [];
		foreach ($customCode as $key => $value)
		{
			$dispenser->hub[$key] = $value;
		}

		$this->counter = new Counter(
			(new ReflectionClass(Valuation::class))->newInstanceWithoutConstructor()
		);

		$subject = $this->renderer(Details::class, [
			'contentone' => $this->content,
			'counter' => $this->counter,
			'component' => $component,
			'dispenser' => $dispenser,
			'componentplaceholder' => new ComponentPlaceholder(
				$this->config(), $this->database()
			),
			'imagetype' => $this->renderer(ImageType::class),
			'accesssections' => $this->renderer(AccessSections::class, ['component' => $component]),
			'configfieldsets' => $this->renderer(ConfigFieldsets::class, ['component' => $component]),
			'helper' => $this->renderer(Helper::class, ['contentone' => $this->content]),
			'emailhelper' => $this->renderer(EmailHelper::class, [
				'component' => $component, 'contentone' => $this->content
			])
		]);

		$subject->set();
	}

	/**
	 * A database that finds no placeholders of the component's own.
	 *
	 * @return  DatabaseInterface&\PHPUnit\Framework\MockObject\Stub
	 * @since   6.1.7
	 */
	private function database(): DatabaseInterface
	{
		$db = $this->createStub(DatabaseInterface::class);
		$db->method('getQuery')->willReturnCallback(fn (): QueryInterface => $this->query());
		$db->method('getNumRows')->willReturn(0);
		$db->method('loadAssocList')->willReturn([]);
		$db->method('loadObjectList')->willReturn([]);

		return $db;
	}

	/**
	 * A query that answers itself to every clause it is handed.
	 *
	 * @return  QueryInterface&\PHPUnit\Framework\MockObject\Stub
	 * @since   6.1.7
	 */
	private function query(): QueryInterface
	{
		$query = $this->createStub(QueryInterface::class);
		foreach (['select', 'from', 'where', 'order'] as $clause)
		{
			$query->method($clause)->willReturnSelf();
		}

		return $query;
	}

	/**
	 * A component that answers as told.
	 *
	 * @param   array  $said  What it was asked, and answered.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function seeded(array $said): Component
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));

		foreach ($said as $key => $value)
		{
			$component->set($key, $value);
		}

		return $component;
	}
}
