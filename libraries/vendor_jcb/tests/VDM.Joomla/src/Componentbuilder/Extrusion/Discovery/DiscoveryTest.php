<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    17th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion\Discovery;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Locator;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Access;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Collector;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Locator\Form;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Locator\Language;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Locator\Schema;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Locator\Table;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Locator\View;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Manifest;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Mvc;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Scanner;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Screen;
use VDM\Joomla\Componentbuilder\Extrusion\Discovery\Selector;
use VDM\Joomla\Componentbuilder\Extrusion\Interfaces\LocatorInterface;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\Heuristic;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaFive;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaFour;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaSix;
use VDM\Joomla\Componentbuilder\Extrusion\Layout\JoomlaThree;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Inventory;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Message;
use VDM\Joomla\Componentbuilder\Extrusion\Reader\Php\Methods;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Tests\Support\ExtrusionComponentFixture;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Bounded, contained discovery of an untrusted component source tree.
 *
 * @since  6.1.6
 */
#[CoversClass(Scanner::class)]
#[CoversClass(Manifest::class)]
#[CoversClass(Selector::class)]
#[CoversClass(Collector::class)]
#[CoversClass(Locator::class)]
#[CoversClass(Schema::class)]
#[CoversClass(Form::class)]
#[CoversClass(Language::class)]
#[CoversClass(Table::class)]
#[CoversClass(View::class)]
final class DiscoveryTest extends FilesystemTestCase
{
	/**
	 * The run configuration.
	 *
	 * @var    Config
	 * @since  6.1.6
	 */
	private Config $config;

	/**
	 * The source identity registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	private Source $source;

	/**
	 * The run report registry.
	 *
	 * @var    Report
	 * @since  6.1.6
	 */
	private Report $report;

	/**
	 * The message bus the collector explains itself through.
	 *
	 * @var    Message
	 * @since  6.1.6
	 */
	private Message $message;

	/**
	 * The located artifact registry.
	 *
	 * @var    Inventory
	 * @since  6.1.6
	 */
	private Inventory $inventory;

	/**
	 * Start every test from an untouched set of run registries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->restate();
	}

	/**
	 * Replace every run registry, so one test can make two independent runs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function restate(): void
	{
		$this->config = new Config();
		$this->source = new Source();
		$this->report = new Report();
		$this->message = new Message();
		$this->inventory = new Inventory();
	}

	/**
	 * Materialise one relative file map below the temporary root.
	 *
	 * @param   string                $prefix  The relative tree prefix.
	 * @param   array<string,string>  $files   Relative path keyed to its contents.
	 *
	 * @return  string  The absolute tree path.
	 * @since   6.1.6
	 */
	private function tree(string $prefix, array $files): string
	{
		foreach ($files as $relative => $contents)
		{
			$this->writeTemporaryFile($prefix . '/' . $relative, $contents);
		}

		return $this->temporaryPath($prefix);
	}

	/**
	 * Materialise one tree and resolve the component root inside it.
	 *
	 * @param   string                $prefix     The relative tree prefix.
	 * @param   array<string,string>  $files      Relative path keyed to its contents.
	 * @param   string                $component  The component directory name.
	 *
	 * @return  string  The resolved component root.
	 * @since   6.1.6
	 */
	private function componentRoot(string $prefix, array $files, string $component): string
	{
		$root = $this->scanner()->root($this->tree($prefix, $files) . '/' . $component);

		$this->assertIsString($root);

		return $root;
	}

	/**
	 * A scanner bound to the current run registries.
	 *
	 * @return  Scanner  The bounded scanner.
	 * @since   6.1.6
	 */
	private function scanner(): Scanner
	{
		return new Scanner($this->config, $this->report);
	}

	/**
	 * A selector carrying all four target-version layouts.
	 *
	 * @return  Selector  The layout selector.
	 * @since   6.1.6
	 */
	private function selector(): Selector
	{
		return new Selector(
			$this->config,
			$this->source,
			new JoomlaThree(),
			new JoomlaFour(),
			new JoomlaFive(),
			new JoomlaSix()
		);
	}

	/**
	 * A manifest resolver bound to the current run registries.
	 *
	 * @return  Manifest  The identity resolver.
	 * @since   6.1.6
	 */
	private function manifest(): Manifest
	{
		return new Manifest($this->config, $this->scanner(), $this->source, $this->report);
	}

	/**
	 * One locator wired to the discovery boundary.
	 *
	 * @param   string  $class  The concrete locator class.
	 *
	 * @return  LocatorInterface  The wired locator.
	 * @since   6.1.6
	 */
	private function locator(string $class): LocatorInterface
	{
		return new $class($this->scanner(), $this->selector(), new Heuristic(), $this->source, $this->report);
	}

	/**
	 * A collector wired to every locator.
	 *
	 * @return  Collector  The discovery collector.
	 * @since   6.1.6
	 */
	private function collector(): Collector
	{
		return new Collector(
			$this->config,
			$this->scanner(),
			$this->manifest(),
			$this->inventory,
			$this->source,
			$this->report,
			$this->message,
			$this->locator(Schema::class),
			$this->locator(Form::class),
			$this->locator(Language::class),
			$this->locator(Table::class),
			$this->locator(View::class),
			new Mvc(
				$this->scanner(),
				$this->selector(),
				$this->source,
				$this->report,
				new Methods()
			),
			new Screen($this->scanner(), $this->selector(), $this->source, $this->report),
			new Access($this->scanner(), $this->selector(), $this->source, $this->report)
		);
	}

	/**
	 * The located paths keyed to the role each entry claims.
	 *
	 * @param   array<int, array<string, mixed>>  $found  Located entries.
	 *
	 * @return  array<string, string>  Absolute path keyed to its role.
	 * @since   6.1.6
	 */
	private function roles(array $found): array
	{
		$roles = [];

		foreach ($found as $entry)
		{
			$roles[(string) $entry['path']] = (string) ($entry['role'] ?? '');
		}

		return $roles;
	}

	/**
	 * A source root must resolve to a real directory or be refused.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRootResolvesARealDirectoryAndRefusesAnythingElse(): void
	{
		$tree = $this->tree('modern', ExtrusionComponentFixture::modern());
		$scanner = $this->scanner();

		$this->assertSame(realpath($tree . '/com_example'), $scanner->root($tree . '/com_example'));
		$this->assertNull($scanner->root($tree . '/com_missing'));
		$this->assertNull($scanner->root($tree . '/com_example/com_example.xml'));
		$this->assertNull($scanner->root(''));
	}

	/**
	 * A relative candidate may not traverse, be absolute, or use foreign separators.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolveRefusesTraversalAbsolutePathsAndForeignSeparators(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$scanner = $this->scanner();

		$this->assertSame($root . '/admin/sql', $scanner->resolve($root, 'admin/sql'));
		$this->assertSame($root . '/admin/sql', $scanner->resolve($root, '/admin/sql/'));
		$this->assertSame($root . '/admin/sql', $scanner->resolve($root, 'admin\\sql'));

		$this->assertNull($scanner->resolve($root, '../com_example/admin/sql'));
		$this->assertNull($scanner->resolve($root, 'admin/../admin/sql'));
		$this->assertNull($scanner->resolve($root, 'admin\\..\\admin\\sql'));
		$this->assertNull($scanner->resolve($root, $root . '/admin/sql'));
		$this->assertNull($scanner->resolve($root, ''));
		$this->assertNull($scanner->resolve($root, '/'));
	}

	/**
	 * A symbolic link out of the tree is refused rather than followed.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testContainRefusesASymlinkPointingOutsideTheRoot(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$outside = $this->createTemporaryDirectory('outside');
		$this->writeTemporaryFile('outside/escaped.xml', ExtrusionComponentFixture::DECOY);
		$link = $root . '/admin/escape';

		if (!@symlink($outside, $link))
		{
			$this->markTestSkipped('This filesystem does not permit creating symbolic links.');
		}

		$inside = $root . '/admin/shortcut';

		$this->assertTrue(@symlink($root . '/admin/sql', $inside));

		$scanner = $this->scanner();

		$this->assertNull($scanner->contain($root, $link));
		$this->assertSame($link, $this->report->get('skipped.symlink.' . md5($link)));
		$this->assertNull($scanner->contain($root, $inside));
		$this->assertSame($inside, $this->report->get('skipped.symlink.' . md5($inside)));
		$this->assertNull($scanner->resolve($root, 'admin/shortcut'));
		$this->assertNotContains($link . '/escaped.xml', $scanner->files($root, ['xml']));
		$this->assertNotContains($link, $scanner->files($root, ['xml']));
	}

	/**
	 * A candidate that resolves outside the root is refused and recorded.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testContainRefusesAPathResolvingOutsideTheRoot(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$outside = $this->writeTemporaryFile('outside/escaped.xml', ExtrusionComponentFixture::DECOY);
		$resolved = realpath($outside);
		$scanner = $this->scanner();

		$this->assertIsString($resolved);
		$this->assertNull($scanner->contain($root, $outside));
		$this->assertSame($resolved, $this->report->get('skipped.uncontained.' . md5($resolved)));
		$this->assertNull($scanner->contain($root, $root . '/admin/missing.xml'));
		$this->assertSame($root, $scanner->contain($root, $root));
	}

	/**
	 * The walk stops descending at the configured depth and records what it left.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFilesHonoursTheDepthCap(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$this->config->set('depth', 1);

		$this->assertSame([$root . '/com_example.xml'], $this->scanner()->files($root, ['xml']));
		$this->assertSame(
			$root . '/admin/sql',
			$this->report->get('skipped.depth.' . md5($root . '/admin/sql'))
		);
		$this->assertSame(
			$root . '/compiler/joomla_3',
			$this->report->get('skipped.depth.' . md5($root . '/compiler/joomla_3'))
		);
	}

	/**
	 * The walk stops at the configured file budget and records the budget.
	 *
	 * The capped result must still come back sorted. The walk is breadth-first, so
	 * the order files are met in is not the order they sort in, and a cap that
	 * returned early would hand back enumeration order instead. Every downstream
	 * inventory index is positional, so that would make the run's own numbering
	 * depend on how the filesystem happened to enumerate the tree.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFilesHonoursTheMaxFilesCap(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$this->config->set('maxFiles', 2);
		$found = $this->scanner()->files($root, ['xml']);

		$this->assertSame(
			[$root . '/admin/forms/item.xml', $root . '/com_example.xml'],
			$found
		);
		$this->assertSame(2, $this->report->get('skipped.maxfiles'));
		$this->assertNotContains($root . '/compiler/joomla_3/component.xml', $found);

		$sorted = $found;
		sort($sorted, SORT_STRING);

		$this->assertSame($sorted, $found);
	}

	/**
	 * Repository and dependency directories are pruned, but vendor is not.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFilesPrunesRepositoryAndDependencyDirectories(): void
	{
		$files = ExtrusionComponentFixture::modern();
		$files['com_example/.git/config.xml'] = ExtrusionComponentFixture::DECOY;
		$files['com_example/node_modules/pack/thing.xml'] = ExtrusionComponentFixture::DECOY;
		$files['com_example/admin/src/vendor/example/power/src/Table.php']
			= ExtrusionComponentFixture::tableClass();
		$root = $this->componentRoot('modern', $files, 'com_example');
		$found = $this->scanner()->files($root, ['xml', 'php']);

		$this->assertContains($root . '/com_example.xml', $found);
		$this->assertContains($root . '/admin/src/vendor/example/power/src/Table.php', $found);
		$this->assertNotContains($root . '/.git/config.xml', $found);
		$this->assertNotContains($root . '/node_modules/pack/thing.xml', $found);

		foreach ($found as $path)
		{
			$this->assertStringNotContainsString('/.git/', $path);
			$this->assertStringNotContainsString('/node_modules/', $path);
		}

		$this->assertSame([], $this->scanner()->files($root, ['zzz']));
		$this->assertSame($found, array_values(array_unique($found)));
	}

	/**
	 * Identity comes from the component's own manifest, never from a decoy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testManifestEstablishesIdentityAndRefusesTheDecoy(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$this->writeTemporaryFile(
			'modern/com_example/modules/mod_thing/mod_thing.xml',
			'<?xml version="1.0" encoding="utf-8"?><extension type="module"><name>mod_thing</name></extension>'
		);
		$manifest = $this->manifest();
		$decoy = $root . '/compiler/joomla_3/component.xml';

		$this->assertTrue($manifest->establish($root));
		$this->assertSame($root, $this->source->get('path'));
		$this->assertSame($root . '/com_example.xml', $this->source->get('manifest'));
		$this->assertSame('com_example', $this->source->get('code_name'));
		$this->assertSame('com_example', $this->source->get('name'));
		$this->assertSame('2.4.1', $this->source->get('version'));

		$facts = $manifest->find($root);

		$this->assertIsArray($facts);
		$this->assertSame($root . '/com_example.xml', $facts['path']);
		$this->assertSame('com_example', $facts['option']);

		$parsedDecoy = $manifest->parse($decoy);

		$this->assertIsArray($parsedDecoy);
		$this->assertSame('com_decoy', $parsedDecoy['option']);
		$this->assertNull($manifest->parse($root . '/modules/mod_thing/mod_thing.xml'));
		$this->assertNull($manifest->parse($root . '/admin/forms/item.xml'));
		$this->assertNull($manifest->parse($root . '/admin/missing.xml'));

		$this->assertSame('com_example', $manifest->option('Example', $root . '/com_example.xml'));
		$this->assertSame('com_widgets', $manifest->option('COM_Widgets', '/tree/manifest.xml'));
		$this->assertSame('com_myextension', $manifest->option('My Extension!', '/tree/manifest.xml'));
		$this->assertSame('', $manifest->option('', '/tree/manifest.xml'));
	}

	/**
	 * Without a manifest the code name is inferred, or its absence is reported.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testManifestInfersTheCodeNameOrReportsThatItCannot(): void
	{
		$installed = [
			'installed/administrator/components/com_guess/sql/install.mysql.utf8.sql'
				=> ExtrusionComponentFixture::SCHEMA,
			'installed/administrator/components/com_guess/models/forms/item.xml'
				=> ExtrusionComponentFixture::FORM
		];
		$root = $this->componentRoot('tree', $installed, 'installed');

		$this->assertTrue($this->manifest()->establish($root));
		$this->assertSame('com_guess', $this->source->get('code_name'));
		$this->assertSame('J3', $this->source->get('layout'));
		$this->assertStringContainsString(
			'code name inferred from the tree',
			(string) $this->report->get('source.manifest')
		);

		$this->restate();

		$blank = $this->componentRoot('blank', ['plain/readme.txt' => 'nothing here'], 'plain');
		$manifest = $this->manifest();

		$this->assertFalse($manifest->establish($blank));
		$this->assertSame('', $this->source->get('code_name', ''));
		$this->assertSame('', $this->source->get('layout'));
		$this->assertNull($manifest->find($blank));
		$this->assertNull($manifest->guess($blank));
		$this->assertStringContainsString(
			'no code name could be inferred',
			(string) $this->report->get('source.manifest')
		);
	}

	/**
	 * At equal depth, a generically named manifest loses to a specific one.
	 *
	 * Depth alone settles the fixture's buried decoy, so it never exercises the
	 * name penalty. Here both manifests sit at the root and neither is com_
	 * prefixed, which leaves the generic-name penalty as the only thing that can
	 * separate them.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGenericallyNamedManifestsLoseToSpecificOnesAtEqualDepth(): void
	{
		$files = [
			'bundle/thing.xml' => str_replace(
				'<name>com_example</name>', '<name>Thing</name>', ExtrusionComponentFixture::MANIFEST
			),
			'bundle/manifest.xml' => str_replace(
				'<name>com_example</name>', '<name>Wrong</name>', ExtrusionComponentFixture::MANIFEST
			)
		];
		$root = $this->componentRoot('ranked', $files, 'bundle');
		$manifest = $this->manifest();

		$this->assertSame('com_wrong', $manifest->parse($root . '/manifest.xml')['option']);
		$this->assertSame('com_thing', $manifest->parse($root . '/thing.xml')['option']);

		$this->assertTrue($manifest->establish($root));
		$this->assertSame('com_thing', $this->source->get('code_name'));
		$this->assertSame($root . '/thing.xml', $this->source->get('manifest'));
	}

	/**
	 * A manifest buried below the depth bound cannot claim the identity.
	 *
	 * The buried file is a perfectly valid, com_ prefixed component manifest, so
	 * only the depth bound keeps it from winning. Without that bound it would
	 * rename the component to whatever an unrelated bundled extension is called.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAManifestBelowTheDepthBoundIsNotEligible(): void
	{
		$files = [
			'com_deep/a/b/c/com_buried.xml' => str_replace(
				'com_example', 'com_buried', ExtrusionComponentFixture::MANIFEST
			)
		];
		$root = $this->componentRoot('deep', $files, 'com_deep');
		$manifest = $this->manifest();

		$this->assertSame(
			'com_buried',
			$manifest->parse($root . '/a/b/c/com_buried.xml')['option']
		);
		$this->assertNull($manifest->find($root));

		$this->assertTrue($manifest->establish($root));
		$this->assertSame('com_deep', $this->source->get('code_name'));
		$this->assertSame('', (string) $this->source->get('manifest', ''));
		$this->assertStringContainsString(
			'code name inferred from the tree',
			(string) $this->report->get('source.manifest')
		);
	}

	/**
	 * The structural markers choose the modern or the legacy family.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFamilyDetectsTheModernAndLegacyTrees(): void
	{
		$modern = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');

		$this->assertTrue($this->manifest()->establish($modern));
		$this->assertSame('J4', $this->source->get('layout'));
		$this->assertSame(3, $this->report->get('source.markers.modern'));
		$this->assertSame(0, $this->report->get('source.markers.legacy'));

		$this->restate();

		$legacy = $this->componentRoot('legacy', ExtrusionComponentFixture::legacy(), 'com_legacy');

		$this->assertTrue($this->manifest()->establish($legacy));
		$this->assertSame('com_legacy', $this->source->get('code_name'));
		$this->assertSame('J3', $this->source->get('layout'));
		$this->assertSame(0, $this->report->get('source.markers.modern'));
		$this->assertSame(3, $this->report->get('source.markers.legacy'));
	}

	/**
	 * An explicit layout option outranks the detected family.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSelectorHonoursTheOptionThenTheDetectedFamily(): void
	{
		$selector = $this->selector();
		$all = $selector->all();

		$this->assertSame(['J3', 'J4', 'J5', 'J6'], array_keys($all));

		foreach ($all as $key => $layout)
		{
			$this->assertSame($key, $layout->version());
		}

		$this->assertSame('J4', $selector->layout()->version());

		$this->source->set('layout', 'J3');

		$this->assertSame('J3', $selector->layout()->version());

		$this->config->set('layout', 'j6');

		$this->assertSame('J6', $selector->layout()->version());

		$this->config->set('layout', 'J5');

		$this->assertSame('J5', $selector->layout()->version());

		$this->config->set('layout', 'j9');

		$this->assertSame('J4', $selector->layout()->version());

		$this->config->set('layout', 'auto');
		$this->source->set('layout', '');

		$this->assertSame('J4', $selector->layout()->version());
	}

	/**
	 * The schema is found by the map, and by signature when the map misses.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSchemaLocatorUsesTheMapThenTheContentSignature(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$locator = $this->locator(Schema::class);
		$this->manifest()->establish($root);

		$this->assertSame('schema', $locator->kind());
		$this->assertSame(
			[['path' => $root . '/admin/sql/install.mysql.utf8.sql', 'tier' => 'map', 'name' => null]],
			$locator->locate($root)
		);
		$this->assertSame('map', $this->report->get('located.schema.0.tier'));

		$this->restate();

		$odd = [
			'com_odd/com_odd.xml' => str_replace(
				'com_example', 'com_odd', ExtrusionComponentFixture::MANIFEST
			),
			'com_odd/database/tables.sql' => ExtrusionComponentFixture::SCHEMA,
			'com_odd/database/notes.txt' => 'CREATE TABLE not in a sql file'
		];
		$oddRoot = $this->componentRoot('odd', $odd, 'com_odd');
		$this->manifest()->establish($oddRoot);
		$found = $this->locator(Schema::class)->locate($oddRoot);

		$this->assertCount(1, $found);
		$this->assertSame($oddRoot . '/database/tables.sql', $found[0]['path']);
		$this->assertSame('signature', $found[0]['tier']);
		$this->assertSame('signature', $this->report->get('located.schema.0.tier'));

		$this->restate();

		$bare = $this->componentRoot(
			'none',
			['com_none/com_none.xml' => str_replace(
				'com_example', 'com_none', ExtrusionComponentFixture::MANIFEST
			)],
			'com_none'
		);
		$this->manifest()->establish($bare);

		$this->assertSame([], $this->locator(Schema::class)->locate($bare));
		$this->assertTrue($this->report->get('located.schema.missing'));
	}

	/**
	 * A form is named after its view, and an XML without fields is not a form.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFormLocatorNamesTheViewAndRefusesANonForm(): void
	{
		$files = ExtrusionComponentFixture::modern();
		$files['com_example/admin/forms/notaform.xml'] = "<form>\n\t<fieldset name=\"empty\" />\n</form>\n";
		$root = $this->componentRoot('modern', $files, 'com_example');
		$locator = $this->locator(Form::class);
		$this->manifest()->establish($root);
		$found = $locator->locate($root);

		$this->assertSame('form', $locator->kind());
		$this->assertSame(
			[['path' => $root . '/admin/forms/item.xml', 'tier' => 'map', 'name' => 'item']],
			$found
		);
		$this->assertSame('map', $this->report->get('located.form.0.tier'));

		$this->restate();

		$legacy = $this->componentRoot('legacy', ExtrusionComponentFixture::legacy(), 'com_legacy');
		$this->manifest()->establish($legacy);

		$this->assertSame(
			[['path' => $legacy . '/admin/models/forms/item.xml', 'tier' => 'map', 'name' => 'item']],
			$this->locator(Form::class)->locate($legacy)
		);
	}

	/**
	 * A legacy-named catalogue in a modern tree still resolves, by signature.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLanguageLocatorFallsThroughToTheSignatureTier(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$locator = $this->locator(Language::class);
		$this->manifest()->establish($root);

		$this->assertSame('language', $locator->kind());
		$this->assertSame(
			[[
				'path' => $root . '/admin/language/en-GB/com_example.ini',
				'tier' => 'map',
				'name' => 'en-GB'
			]],
			$locator->locate($root)
		);

		$this->restate();

		$files = ExtrusionComponentFixture::modern();
		unset($files['com_example/admin/language/en-GB/com_example.ini']);
		$files['com_example/admin/language/en-GB/en-GB.com_example.ini']
			= ExtrusionComponentFixture::LANGUAGE;
		$files['com_example/admin/language/en-GB/en-GB.com_example.sys.ini']
			= "COM_EXAMPLE=\"Example\"\n";
		$files['com_example/admin/language/en-GB/en-GB.mod_other.ini'] = "MOD_OTHER=\"Other\"\n";
		$prefixed = $this->componentRoot('prefixed', $files, 'com_example');
		$this->manifest()->establish($prefixed);
		$found = $this->locator(Language::class)->locate($prefixed);

		$this->assertSame('J4', $this->source->get('layout'));
		$this->assertCount(2, $found);
		$this->assertSame($prefixed . '/admin/language/en-GB/en-GB.com_example.ini', $found[0]['path']);
		$this->assertSame('signature', $found[0]['tier']);
		$this->assertSame($prefixed . '/admin/language/en-GB/en-GB.com_example.sys.ini', $found[1]['path']);
		$this->assertSame('signature', $found[1]['tier']);
		$this->assertSame('signature', $this->report->get('located.language.1.tier'));
	}

	/**
	 * An installed component's catalogue is found in the site's own folders.
	 *
	 * Joomla moves an installed component's language files to the central
	 * administrator/language and language folders under the site root, so a
	 * harvest aimed at an installed component folder holds not one ini file
	 * of its own. The locator probes those exact central names for exactly
	 * this component -- never a scan of the site -- or every label the whole
	 * run resolves would stay a constant.
	 *
	 * @return  void
	 * @since   6.1.8
	 */
	public function testLanguageLocatorFindsTheInstalledSitesCentralCatalogue(): void
	{
		$this->writeTemporaryFile(
			'site/administrator/components/com_example/example.php',
			"<?php\n"
		);
		$this->writeTemporaryFile(
			'site/administrator/language/en-GB/com_example.ini',
			ExtrusionComponentFixture::LANGUAGE
		);
		$this->writeTemporaryFile(
			'site/administrator/language/en-GB/com_example.sys.ini',
			"COM_EXAMPLE=\"Example\"\n"
		);
		$this->writeTemporaryFile(
			'site/language/en-GB/com_example.ini',
			"COM_EXAMPLE_FRONT=\"Front\"\n"
		);
		$this->writeTemporaryFile(
			'site/administrator/language/en-GB/com_other.ini',
			"COM_OTHER=\"Other\"\n"
		);
		$root = $this->temporaryPath('site/administrator/components/com_example');
		$this->source->set('code_name', 'com_example');

		$found = $this->locator(Language::class)->locate($root);

		$this->assertSame(
			[
				$this->temporaryPath('site/administrator/language/en-GB/com_example.ini'),
				$this->temporaryPath('site/administrator/language/en-GB/com_example.sys.ini'),
				$this->temporaryPath('site/language/en-GB/com_example.ini')
			],
			array_column($found, 'path'),
			'Exactly this component\'s central files, and no other extension\'s.'
		);
		$this->assertSame(
			['central', 'central', 'central'],
			array_column($found, 'tier')
		);
	}

	/**
	 * A table definition class is found by signature inside a vendored namespace.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableClassLocatorFindsAVendoredClassBySignature(): void
	{
		$files = ExtrusionComponentFixture::modern();
		$files['com_example/admin/src/vendor/example/power/src/Table.php']
			= ExtrusionComponentFixture::tableClass();
		$files['com_example/admin/src/Table/UnsafeTable.php']
			= ExtrusionComponentFixture::unsafeTableClass();
		$root = $this->componentRoot('modern', $files, 'com_example');
		$locator = $this->locator(Table::class);
		$this->manifest()->establish($root);
		$found = $locator->locate($root);

		$this->assertSame('table_class', $locator->kind());
		$this->assertCount(2, $found);
		$this->assertSame($root . '/admin/src/vendor/example/power/src/Table.php', $found[0]['path']);
		$this->assertSame('signature', $found[0]['tier']);
		$this->assertSame($root . '/admin/src/Table/UnsafeTable.php', $found[1]['path']);
		$this->assertSame('signature', $found[1]['tier']);
		$this->assertSame('signature', $this->report->get('located.table_class.0.tier'));
		$this->assertStringContainsString('/vendor/', (string) $found[0]['path']);
	}

	/**
	 * Layouts and templates are classified from the map in both generations.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testViewLocatorClassifiesLayoutsAndTemplates(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$locator = $this->locator(View::class);
		$this->manifest()->establish($root);
		$found = $locator->locate($root);

		$this->assertSame('view', $locator->kind());
		$this->assertSame(
			[
				$root . '/admin/layouts/summary.php' => 'layout',
				$root . '/admin/tmpl/item/default.php' => 'main',
				$root . '/admin/tmpl/item/default_extra.php' => 'template'
			],
			$this->roles($found)
		);
		$this->assertSame(['summary', 'default', 'default_extra'], array_column($found, 'name'));
		$this->assertSame(['map', 'map', 'map'], array_column($found, 'tier'));

		$this->restate();

		$legacy = $this->componentRoot('legacy', ExtrusionComponentFixture::legacy(), 'com_legacy');
		$this->manifest()->establish($legacy);

		$this->assertSame(
			[
				$legacy . '/admin/layouts/summary.php' => 'layout',
				$legacy . '/admin/views/item/tmpl/default.php' => 'main',
				$legacy . '/admin/views/item/tmpl/default_extra.php' => 'template'
			],
			$this->roles($this->locator(View::class)->locate($legacy))
		);
	}

	/**
	 * Collection refuses an unusable root and otherwise fills the inventory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCollectorRefusesAnUnusableRootAndFillsTheInventory(): void
	{
		$root = $this->componentRoot('modern', ExtrusionComponentFixture::modern(), 'com_example');
		$collector = $this->collector();

		$missing = $this->temporaryPath('modern') . '/com_missing';

		$this->assertFalse($collector->collect($missing));
		$this->assertStringContainsString(
			'not a readable directory',
			(string) $this->report->get('failed.root.' . md5($missing)),
			'Each unusable root is reported under its own path, because a run may be '
			. 'given several and only some of them may be wrong.'
		);
		$this->assertFalse($this->inventory->exists('schema.0.path'));

		$this->restate();

		$collector = $this->collector();

		$this->assertCount(5, $collector->locators());
		$this->assertTrue($collector->collect($root));
		$this->assertSame('en-GB', $this->source->get('tag'));
		$this->assertSame('com_example', $this->source->get('code_name'));
		$this->assertSame(1, $this->inventory->get('schema_count'));
		$this->assertSame(
			$root . '/admin/sql/install.mysql.utf8.sql',
			$this->inventory->get('schema.0.path')
		);
		$this->assertSame('map', $this->inventory->get('schema.0.tier'));
		$this->assertSame(1, $this->inventory->get('form_count'));
		$this->assertSame($root . '/admin/forms/item.xml', $this->inventory->get('form.0.path'));
		$this->assertSame('item', $this->inventory->get('form.0.name'));
		$this->assertSame('map', $this->inventory->get('form.0.tier'));
		$this->assertSame(1, $this->inventory->get('language_count'));
		$this->assertSame(
			$root . '/admin/language/en-GB/com_example.ini',
			$this->inventory->get('language.0.path')
		);
		$this->assertSame(0, $this->inventory->get('table_class_count'));
		$this->assertSame(3, $this->inventory->get('view_count'));
		$this->assertSame($root . '/admin/layouts/summary.php', $this->inventory->get('view.0.path'));
		$this->assertSame('layout', $this->inventory->get('view.0.role'));
		$this->assertSame('map', $this->inventory->get('view.0.tier'));
		$this->assertSame('template', $this->inventory->get('view.2.role'));

		$this->config->set('tableClass', 'off');
		$this->config->set('admin', false);
		$this->config->set('code', false);

		$this->assertCount(3, $this->collector()->locators());
	}

	/**
	 * A tree with neither a schema nor a table class is not usable.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCollectorFailsWhenNoStructuralSourceExists(): void
	{
		$files = [
			'com_bare/com_bare.xml' => str_replace(
				'com_example', 'com_bare', ExtrusionComponentFixture::MANIFEST
			),
			'com_bare/admin/language/en-GB/com_bare.ini' => "COM_BARE=\"Bare\"\n"
		];
		$root = $this->componentRoot('bare', $files, 'com_bare');

		$this->assertFalse($this->collector()->collect($root));
		$this->assertSame('com_bare', $this->source->get('code_name'));
		$this->assertSame(0, $this->inventory->get('schema_count'));
		$this->assertSame(0, $this->inventory->get('table_class_count'));
		$this->assertSame(1, $this->inventory->get('language_count'));
		$this->assertTrue($this->report->get('located.schema.missing'));
	}
	/**
	 * Aiming at the administrator folder still finds the manifest beside it.
	 *
	 * In a repository a component is admin/, site/ and its manifest as siblings, so
	 * a run pointed at the back end alone would otherwise learn nothing about the
	 * component itself -- and the back end alone is exactly what someone extruding
	 * only the administrator area would point at.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheManifestIsFoundBesideTheAdministratorFolder(): void
	{
		$root = $this->tree('beside', [
			'com_thing.xml' => str_replace(
				'<name>com_example</name>',
				'<name>Thing</name>',
				ExtrusionComponentFixture::MANIFEST
			),
			'admin/sql/install.mysql.utf8.sql' => ExtrusionComponentFixture::SCHEMA
		]);

		$this->assertTrue($this->manifest()->establish($root . '/admin'));
		$this->assertSame('com_thing', $this->source->get('code_name'));
		$this->assertSame('Thing', $this->source->get('name'));
		$this->assertSame('2.4.1', $this->source->get('version'));
		$this->assertSame($root, $this->report->get('source.manifest_beside'));
		$this->assertSame(
			$root . '/com_thing.xml',
			$this->source->get('manifest'),
			'The manifest read is the one beside the folder, named exactly.'
		);
	}

	/**
	 * Only a manifest that declares itself a component is taken from beside.
	 *
	 * Reading a directory the run was not pointed at is a deliberate exception, so
	 * it is held to the strictest test there is: the extension type must say
	 * component outright. A module or plugin manifest sitting beside the folder
	 * must never be mistaken for the component's own.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAnUntypedOrForeignManifestBesideTheFolderIsRefused(): void
	{
		$root = $this->tree('foreign', [
			'mod_thing.xml' => str_replace(
				'type="component"',
				'type="module"',
				ExtrusionComponentFixture::MANIFEST
			),
			'admin/sql/install.mysql.utf8.sql' => ExtrusionComponentFixture::SCHEMA
		]);

		$this->manifest()->establish($root . '/admin');

		$this->assertNull(
			$this->report->get('source.manifest_beside'),
			'A module manifest beside the folder is not the component.'
		);
		$this->assertNull($this->source->get('manifest'));
	}
}
