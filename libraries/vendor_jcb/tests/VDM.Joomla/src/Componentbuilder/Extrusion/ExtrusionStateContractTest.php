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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Decision;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Form;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Harvest;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Inventory;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Language;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Message;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Schema;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Scope;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Table;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View;
use VDM\Joomla\Interfaces\Registryinterface;
use VDM\Tests\Support\TestCase;


/**
 * The extrusion run-state contract: configuration options and state registries.
 *
 * @since  6.1.6
 */
#[CoversClass(Config::class)]
#[CoversNamespace('VDM\Joomla\Componentbuilder\Extrusion\Registry')]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
final class ExtrusionStateContractTest extends TestCase
{
	/**
	 * The state registry leaves, in constructor order.
	 *
	 * @var    array<string>
	 * @since  6.1.6
	 */
	private const LEAVES = [
		'Source', 'Inventory', 'Table', 'Schema', 'Form',
		'Language', 'View', 'Resolved', 'Harvest', 'Decision', 'Report'
	];

	/**
	 * The state registry namespace.
	 *
	 * @var    string
	 * @since  6.1.6
	 */
	private const REGISTRY_NAMESPACE = 'VDM\Joomla\Componentbuilder\Extrusion\Registry';

	/**
	 * Every state registry leaf, keyed by its short class name.
	 *
	 * @return  array<string, array{0: class-string, 1: string}>
	 * @since   6.1.6
	 */
	public static function registryLeaves(): array
	{
		$cases = [];

		foreach (self::LEAVES as $name)
		{
			$cases[$name] = [self::REGISTRY_NAMESPACE . '\\' . $name, $name];
		}

		return $cases;
	}

	/**
	 * Every leaf must be a final, body-less registry, not a place for behaviour.
	 *
	 * @param   class-string  $class  The leaf class name.
	 * @param   string        $name   The expected short class name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('registryLeaves')]
	public function testEveryStateRegistryLeafIsAFinalEmptyRegistry(string $class, string $name): void
	{
		$this->assertTrue(class_exists($class), $class . ' must exist.');

		$reflection = new ReflectionClass($class);

		$this->assertTrue($reflection->isFinal(), $class . ' must be final.');
		$this->assertFalse($reflection->isAbstract());
		$this->assertSame($name, $reflection->getShortName());
		$this->assertSame(self::REGISTRY_NAMESPACE, $reflection->getNamespaceName());
		$this->assertTrue($reflection->isSubclassOf(Registry::class));
		$this->assertTrue($reflection->implementsInterface(Registryinterface::class));
		$this->assertSame(
			['methods' => [], 'properties' => [], 'constants' => []],
			$this->declaredMembers($reflection),
			$class . ' must have an empty body.'
		);
		$this->assertSame(
			Registry::class,
			$reflection->getConstructor()?->getDeclaringClass()->getName()
		);

		$subject = $this->leaf($class);

		$this->assertSame($class, $subject::class);
		$this->assertSame('.', $subject->getSeparator());
		$this->assertNull($subject->getName());
		$this->assertCount(0, $subject);
	}

	/**
	 * Every leaf must store and read dotted paths and stay fluent.
	 *
	 * @param   class-string  $class  The leaf class name.
	 * @param   string        $name   The expected short class name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('registryLeaves')]
	public function testEveryStateRegistryStoresAndReadsDottedPaths(string $class, string $name): void
	{
		$subject = $this->leaf($class);

		$this->assertSame($subject, $subject->set('view.article.field.title', 'Title'));
		$this->assertSame('Title', $subject->get('view.article.field.title'));
		$this->assertSame(['title' => 'Title'], $subject->get('view.article.field'));
		$this->assertSame(
			['view' => ['article' => ['field' => ['title' => 'Title']]]],
			$subject->toArray()
		);
		$this->assertTrue($subject->exists('view.article'));
		$this->assertFalse($subject->exists('view.category'));
		$this->assertNull($subject->get('view.category.field.name'));
		$this->assertSame('fallback', $subject->get('view.category.field.name', 'fallback'));
		$this->assertSame($subject, $subject->remove('view.article.field.title'));
		$this->assertFalse($subject->exists('view.article.field.title'));
		$this->assertSame($name, $subject->setName($name)->getName());
		$this->assertSame($subject, $subject->clear());
		$this->assertCount(0, $subject);
		$this->assertSame([], $subject->toArray());
	}

	/**
	 * The configuration must seed every reviewed default on construction.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigurationSeedsEveryReviewedDefault(): void
	{
		$config = new Config();

		$this->assertSame($this->expectedDefaults(), $config->toArray());
		$this->assertCount(30, $config);
		$this->assertSame('create', $config->get('mode'));
		$this->assertSame(0, $config->get('component'));
		$this->assertSame('update', $config->get('onExisting'));
		$this->assertTrue($config->get('admin'));
		$this->assertFalse($config->get('site'));
		$this->assertTrue($config->get('tabs'));
		$this->assertTrue($config->get('conditions'));
		$this->assertTrue($config->get('language'));
		$this->assertFalse($config->get('translations'));
		$this->assertTrue($config->get('relations'));
		$this->assertFalse($config->get('code'));
		$this->assertSame([], $config->get('include'));
		$this->assertSame([], $config->get('exclude'));
		$this->assertSame(['table', 'notes', 'xml', 'derived'], $config->get('precedence'));
		$this->assertSame(Config::TIERS, $config->get('precedence'));
		$this->assertSame('auto', $config->get('tableClass'));
		$this->assertSame('auto', $config->get('layout'));
		$this->assertSame('en-GB', $config->get('languageTag'));
		$this->assertFalse($config->get('dryRun'));
		$this->assertFalse($config->get('strict'));
		$this->assertSame(12, $config->get('depth'));
		$this->assertSame(20000, $config->get('maxFiles'));
		$this->assertSame(Config::BOILERPLATE, $config->get('skipColumns'));
		$this->assertSame($config, $config->defaults());
		$this->assertSame($this->expectedDefaults(), $config->toArray());
		$this->assertSame('.', $config->getSeparator());
		$this->assertInstanceOf(Registry::class, $config);
		$this->assertInstanceOf(Registryinterface::class, $config);
		$this->assertTrue((new ReflectionClass(Config::class))->isFinal());
	}

	/**
	 * Clearing the configuration must restore the defaults, not empty it.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testClearingConfigurationRestoresDefaultsInsteadOfEmptyingIt(): void
	{
		$config = new Config();
		$config->set('mode', 'update')
			->set('dryRun', true)
			->set('precedence', ['xml'])
			->set('skipColumns', ['title'])
			->set('include', ['article'])
			->set('custom', 'value');

		$this->assertSame('update', $config->get('mode'));
		$this->assertSame('value', $config->get('custom'));
		$this->assertCount(31, $config);

		$cleared = $config->clear();

		$this->assertSame($config, $cleared);
		$this->assertNotSame([], $config->toArray());
		$this->assertSame($this->expectedDefaults(), $config->toArray());
		$this->assertCount(30, $config);
		$this->assertSame('create', $config->get('mode'));
		$this->assertFalse($config->get('dryRun'));
		$this->assertSame(Config::TIERS, $config->get('precedence'));
		$this->assertSame(Config::BOILERPLATE, $config->get('skipColumns'));
		$this->assertSame([], $config->get('include'));
		$this->assertFalse($config->exists('custom'));
		$this->assertNull($config->get('custom'));
	}

	/**
	 * Options handed in at construction must survive the default seeding.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigurationKeepsTheOptionsHandedInAtConstruction(): void
	{
		$config = new Config([
			'mode' => 'update',
			'dryRun' => true,
			'precedence' => ['xml'],
			'include' => ['article'],
			'depth' => 3,
			'custom' => 'value'
		]);

		$this->assertSame('update', $config->get('mode'), 'a configured mode must not be reset to its default.');
		$this->assertTrue($config->get('dryRun'));
		$this->assertSame(['xml'], $config->get('precedence'), 'a list option must not be merged with its default.');
		$this->assertSame(['article'], $config->get('include'));
		$this->assertSame(3, $config->get('depth'));
		$this->assertSame('value', $config->get('custom'));
		$this->assertSame('en-GB', $config->get('languageTag'), 'an absent option must still be seeded.');
		$this->assertSame('auto', $config->get('layout'));
		$this->assertSame('update', $config->get('onExisting'));
		$this->assertSame(20000, $config->get('maxFiles'));
		$this->assertSame(Config::BOILERPLATE, $config->get('skipColumns'));
		$this->assertCount(31, $config);

		$this->assertSame(0, $config->rank('xml'), 'the configured precedence must drive the ranks.');
		$this->assertSame(5, $config->rank('table'));
		$this->assertTrue($config->selected('article'));
		$this->assertFalse($config->selected('category'));
		$this->assertFalse($config->extrudable('id'));

		$this->assertSame($this->expectedDefaults(), $config->clear()->toArray());
	}

	/**
	 * A configuration loaded from a string or an object must behave as an array does.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigurationLoadsOptionsFromStringsObjectsAndNulls(): void
	{
		$fromString = new Config('{"mode":"update","layout":"j5","exclude":["note"]}');

		$this->assertSame('update', $fromString->get('mode'));
		$this->assertSame('j5', $fromString->get('layout'));
		$this->assertSame(['note'], $fromString->get('exclude'));
		$this->assertFalse($fromString->selected('note'));
		$this->assertTrue($fromString->selected('article'));
		$this->assertSame('en-GB', $fromString->get('languageTag'));
		$this->assertCount(30, $fromString);

		$fromObject = new Config((object) ['strict' => true, 'tableClass' => 'off']);

		$this->assertTrue($fromObject->get('strict'));
		$this->assertSame('off', $fromObject->get('tableClass'));
		$this->assertTrue($fromObject->get('admin'));
		$this->assertSame(Config::TIERS, $fromObject->get('precedence'));
		$this->assertCount(30, $fromObject);

		$fromNulls = new Config(['layout' => null, 'depth' => null, 'skipColumns' => null]);

		$this->assertSame('auto', $fromNulls->get('layout'), 'a null option must fall back to its default.');
		$this->assertSame(12, $fromNulls->get('depth'));
		$this->assertSame(Config::BOILERPLATE, $fromNulls->get('skipColumns'));
		$this->assertFalse($fromNulls->extrudable('id'));
		$this->assertSame(
			$this->keyed($this->expectedDefaults()),
			$this->keyed($fromNulls->toArray()),
			'seeding over null options must leave the full reviewed catalogue.'
		);
	}

	/**
	 * Restoring the defaults must reset the catalogue and leave foreign keys alone.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRestoringDefaultsResetsTheCatalogueButKeepsForeignKeys(): void
	{
		$config = new Config();
		$config->set('mode', 'update')
			->set('precedence', ['xml'])
			->set('skipColumns', ['title'])
			->set('custom', 'value');

		$restored = $config->defaults();

		$this->assertSame($config, $restored);
		$this->assertSame('create', $config->get('mode'));
		$this->assertSame(Config::TIERS, $config->get('precedence'));
		$this->assertSame(Config::BOILERPLATE, $config->get('skipColumns'));
		$this->assertSame(
			'value',
			$config->get('custom'),
			'defaults() must leave keys outside the catalogue in place.'
		);
		$this->assertTrue($config->exists('custom'));
		$this->assertCount(31, $config);

		$config->clear();

		$this->assertFalse(
			$config->exists('custom'),
			'clear() must drop keys outside the catalogue.'
		);
		$this->assertCount(30, $config);
	}

	/**
	 * Enumerated options must accept only their catalogued values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEnumeratedOptionsAcceptOnlyTheirAllowedValues(): void
	{
		$config = new Config();
		$expected = [
			'mode' => ['create', 'update'],
			'onExisting' => ['skip', 'update', 'replace'],
			'tableClass' => ['auto', 'off'],
			'layout' => ['auto', 'j3', 'j4', 'j5', 'j6']
		];

		foreach ($expected as $key => $values)
		{
			$this->assertSame($values, $config->allowed($key), $key . ' allowed values.');
			$this->assertTrue($config->known($key));

			foreach ($values as $value)
			{
				$this->assertTrue($config->permitted($key, $value), $key . ' => ' . $value);
			}

			$this->assertFalse($config->permitted($key, 'nonsense'));
			$this->assertFalse($config->permitted($key, ''));
			$this->assertFalse($config->permitted($key, strtoupper($values[0])));
		}

		$this->assertFalse($config->permitted('mode', 'replace'));
		$this->assertFalse($config->permitted('onExisting', 'create'));
		$this->assertFalse($config->permitted('tableClass', 'on'));
		$this->assertFalse($config->permitted('layout', 'j7'));
		$this->assertFalse($config->permitted('layout', 'joomla4'));
		$this->assertTrue($config->permitted('languageTag', 'af-ZA'));
		$this->assertTrue($config->permitted('depth', '32'));
		$this->assertTrue($config->permitted('unconstrained', 'anything'));
		$this->assertSame([], $config->allowed('languageTag'));
		$this->assertSame([], $config->allowed('missing'));
		$this->assertTrue($config->known('skipColumns'));
		$this->assertTrue($config->known('maxFiles'));
		$this->assertTrue($config->known('include'));
		$this->assertFalse($config->known('missing'));
		$this->assertFalse($config->known('Mode'));
		$this->assertFalse($config->known('BOILERPLATE'));
		$this->assertFalse($config->known('TIERS'));
		$this->assertFalse($config->known(''));
	}

	/**
	 * Selection must honour include and exclude, an empty include filtering nothing.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSelectionHonoursIncludeAndExcludeFilters(): void
	{
		$config = new Config();

		$this->assertTrue($config->selected('article'));
		$this->assertTrue($config->selected('anything_at_all'));

		$config->set('include', ['article', 'category']);

		$this->assertTrue($config->selected('article'));
		$this->assertTrue($config->selected('category'));
		$this->assertFalse($config->selected('note'));

		$config->set('exclude', ['category', 'note']);

		$this->assertTrue($config->selected('article'));
		$this->assertFalse($config->selected('category'));
		$this->assertFalse($config->selected('note'));

		$config->set('include', []);

		$this->assertTrue($config->selected('article'));
		$this->assertTrue($config->selected('anything_at_all'));
		$this->assertFalse($config->selected('category'));

		$config->set('exclude', []);
		$config->set('include', ['Article']);

		$this->assertTrue($config->selected('Article'));
		$this->assertFalse($config->selected('article'));

		$config->set('include', 'article');

		$this->assertTrue($config->selected('article'));
		$this->assertFalse($config->selected('category'));
	}

	/**
	 * Boilerplate columns must never be extruded, real columns always may be.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBoilerplateColumnsAreNeverExtruded(): void
	{
		$config = new Config();

		$this->assertCount(17, Config::BOILERPLATE);
		$this->assertNotContains(
			'guid',
			Config::BOILERPLATE,
			'The guid column is a real JCB field -- the Globally Unique ID field '
			. 'every view links -- so it is extruded and paired like any other.'
		);

		foreach (Config::BOILERPLATE as $column)
		{
			$this->assertFalse($config->extrudable($column), $column . ' is boilerplate.');
			$this->assertFalse($config->extrudable(strtoupper($column)));
			$this->assertFalse($config->extrudable('  ' . $column . ' '));
		}

		$this->assertTrue($config->extrudable('title'));
		$this->assertTrue($config->extrudable('description'));
		$this->assertTrue($config->extrudable('created_date'));
		$this->assertTrue($config->extrudable('parameters'));

		$config->set('skipColumns', ['Title']);

		$this->assertFalse($config->extrudable('title'));
		$this->assertTrue($config->extrudable('id'));

		$config->set('skipColumns', [' DESCRIPTION ', 'Note']);

		$this->assertFalse(
			$config->extrudable('description'),
			'a padded skip entry must be normalised the same way the column is.'
		);
		$this->assertFalse($config->extrudable('  Note'));
		$this->assertTrue($config->extrudable('title'));

		$config->clear();

		$this->assertFalse($config->extrudable('id'));
		$this->assertTrue($config->extrudable('title'));
	}

	/**
	 * Precedence ranks must order the tiers and push unknown tiers past the end.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPrecedenceRanksOrderTiersAndPushUnknownTiersLast(): void
	{
		$config = new Config();

		$this->assertSame(0, $config->rank('table'));
		$this->assertSame(1, $config->rank('notes'));
		$this->assertSame(2, $config->rank('xml'));
		$this->assertSame(3, $config->rank('derived'));
		$this->assertSame(5, $config->rank('guess'));
		$this->assertLessThan($config->rank('notes'), $config->rank('table'));
		$this->assertGreaterThan($config->rank('derived'), $config->rank('guess'));

		$config->set('precedence', ['xml', 'derived', 'notes', 'table']);

		$this->assertSame(0, $config->rank('xml'));
		$this->assertSame(1, $config->rank('derived'));
		$this->assertSame(2, $config->rank('notes'));
		$this->assertSame(3, $config->rank('table'));
		$this->assertGreaterThan($config->rank('xml'), $config->rank('table'));

		$config->set('precedence', ['notes']);

		$this->assertSame(0, $config->rank('notes'));
		$this->assertSame(5, $config->rank('table'));
		$this->assertSame(5, $config->rank('xml'));

		$config->clear();

		$this->assertSame(0, $config->rank('table'));
		$this->assertSame(3, $config->rank('derived'));
	}

	/**
	 * The scope must expose exactly the twelve state registries, keyed by name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testScopeExposesExactlyTheTwelveStateRegistriesByName(): void
	{
		$config = new Config();
		$registries = $this->stateRegistries();
		$scope = new Scope($config, ...array_values($registries));
		$exposed = $scope->registries();

		$this->assertCount(12, $exposed);
		$this->assertSame(
			[
				'source', 'inventory', 'table', 'schema', 'form',
				'language', 'view', 'resolved', 'harvest', 'decision', 'report', 'message'
			],
			array_keys($exposed)
		);
		$this->assertSame(array_keys($registries), array_keys($exposed));
		$this->assertArrayNotHasKey('config', $exposed);

		foreach ($registries as $name => $registry)
		{
			$this->assertSame($registry, $exposed[$name], $name . ' must be the injected instance.');
			$this->assertNotInstanceOf(Config::class, $exposed[$name]);
			$this->assertInstanceOf(Registry::class, $exposed[$name]);
			$this->assertInstanceOf(Registryinterface::class, $exposed[$name]);
			$this->assertSame(
				self::REGISTRY_NAMESPACE . '\\' . ucfirst($name),
				$exposed[$name]::class
			);
		}

		$this->assertSame($exposed, $scope->registries());
		$this->assertTrue((new ReflectionClass(Scope::class))->isFinal());
	}

	/**
	 * The scope must clear every registry and restore the configuration defaults.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testScopeResetClearsEveryRegistryAndRestoresConfigurationDefaults(): void
	{
		$config = new Config();
		$registries = $this->stateRegistries();
		$scope = new Scope($config, ...array_values($registries));

		$config->set('mode', 'update')
			->set('dryRun', true)
			->set('precedence', ['derived'])
			->set('custom', 'value');

		foreach ($registries as $name => $registry)
		{
			$registry->set('run.' . $name, $name);

			$this->assertSame($name, $registry->get('run.' . $name));
			$this->assertCount(1, $registry);
		}

		$scope->reset();

		foreach ($registries as $name => $registry)
		{
			$this->assertCount(0, $registry, $name . ' must be cleared.');
			$this->assertSame([], $registry->toArray());
			$this->assertFalse($registry->exists('run.' . $name));
			$this->assertNull($registry->get('run.' . $name));
		}

		$this->assertNotSame([], $config->toArray());
		$this->assertSame($this->expectedDefaults(), $config->toArray());
		$this->assertSame('create', $config->get('mode'));
		$this->assertFalse($config->get('dryRun'));
		$this->assertSame(Config::TIERS, $config->get('precedence'));
		$this->assertFalse($config->exists('custom'));
	}

	/**
	 * The message bus gathers plain data at levels and never formats anything.
	 *
	 * The bus is what a caller reads to answer "what did this run achieve", so its
	 * shape is a contract: levels in reading order, no duplicates, and no markup.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheMessageBusGathersLevelledPlainData(): void
	{
		$bus = new Message();

		$this->assertSame([], $bus->all());
		$this->assertSame(0, $bus->total());
		$this->assertFalse($bus->failed());

		$this->assertSame($bus, $bus->success('Extruded 3 views.'));
		$bus->notice('No table definition class was found.')
			->warning('No language file was found.', 'en-GB')
			->error('Nothing described a table.', '/tmp/empty');

		$this->assertTrue($bus->failed());
		$this->assertSame(4, $bus->total());
		$this->assertSame(1, $bus->total(Message::WARNING));
		$this->assertSame(
			[Message::ERROR, Message::WARNING, Message::NOTICE, Message::SUCCESS],
			array_keys($bus->all()),
			'Levels must come back in reading order, worst first.'
		);
		$this->assertSame(
			[['message' => 'No language file was found.', 'subject' => 'en-GB']],
			$bus->level(Message::WARNING)
		);
		$this->assertSame(
			[['message' => 'Extruded 3 views.']],
			$bus->level(Message::SUCCESS),
			'A message with no subject must not invent one.'
		);

		foreach ($bus->all() as $messages)
		{
			foreach ($messages as $entry)
			{
				$this->assertArrayHasKey('message', $entry);
				$this->assertDoesNotMatchRegularExpression(
					'/<[a-z]/i',
					$entry['message'],
					'The bus gathers messages; formatting belongs to the caller.'
				);
			}
		}
	}

	/**
	 * The bus refuses empties, de-duplicates, and falls back to notice.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTheMessageBusRefusesEmptiesAndDuplicates(): void
	{
		$bus = new Message();

		$bus->record(Message::WARNING, '   ');
		$bus->success('');

		$this->assertSame(0, $bus->total(), 'An empty message is not a message.');

		$bus->warning('Same thing.');
		$bus->warning('Same thing.');
		$bus->warning('Same thing.', 'a subject');

		$this->assertSame(
			2,
			$bus->total(Message::WARNING),
			'A repeat is dropped, but the same text about a different subject is not.'
		);

		$bus->record('not-a-level', 'Where does this go?');

		$this->assertSame(
			[['message' => 'Where does this go?']],
			$bus->level(Message::NOTICE),
			'An unknown level must fall back rather than be lost.'
		);

		$this->assertSame([], $bus->level('nonsense'));
		$this->assertSame($bus, $bus->clear());
		$this->assertSame(0, $bus->total());
	}

	/**
	 * A fresh set of the twelve state registries, in constructor order.
	 *
	 * @return  array<string, Registry>  The registries keyed by scope name.
	 * @since   6.1.6
	 */
	private function stateRegistries(): array
	{
		return [
			'source' => new Source(),
			'inventory' => new Inventory(),
			'table' => new Table(),
			'schema' => new Schema(),
			'form' => new Form(),
			'language' => new Language(),
			'view' => new View(),
			'resolved' => new Resolved(),
			'harvest' => new Harvest(),
			'decision' => new Decision(),
			'report' => new Report(),
			'message' => new Message()
		];
	}

	/**
	 * Instantiate one registry leaf by class name.
	 *
	 * @param   class-string  $class  The leaf class name.
	 *
	 * @return  Registry  The registry instance.
	 * @since   6.1.6
	 */
	private function leaf(string $class): Registry
	{
		return new $class();
	}

	/**
	 * The members a class declares itself, rather than inheriting.
	 *
	 * @param   ReflectionClass<object>  $reflection  The class to inspect.
	 *
	 * @return  array{methods: array<string>, properties: array<string>, constants: array<string>}
	 * @since   6.1.6
	 */
	private function declaredMembers(ReflectionClass $reflection): array
	{
		$owner = $reflection->getName();
		$declared = ['methods' => [], 'properties' => [], 'constants' => []];
		$members = [
			'methods' => $reflection->getMethods(),
			'properties' => $reflection->getProperties(),
			'constants' => $reflection->getReflectionConstants()
		];

		foreach ($members as $kind => $group)
		{
			foreach ($group as $member)
			{
				if ($member->getDeclaringClass()->getName() === $owner)
				{
					$declared[$kind][] = $member->getName();
				}
			}
		}

		return $declared;
	}

	/**
	 * One option set sorted by name, so a comparison ignores insertion order only.
	 *
	 * @param   array<string, mixed>  $options  The option set to sort.
	 *
	 * @return  array<string, mixed>  The same options, ordered by key.
	 * @since   6.1.6
	 */
	private function keyed(array $options): array
	{
		ksort($options);

		return $options;
	}

	/**
	 * The reviewed option defaults, held independently of the production class.
	 *
	 * @return  array<string, mixed>  The expected default option set.
	 * @since   6.1.6
	 */
	private function expectedDefaults(): array
	{
		return [
			'mode' => 'create',
			'component' => 0,
			'codeName' => '',
			'dump' => '',
			'onExisting' => 'update',
			'admin' => true,
			'site' => false,
			'tabs' => true,
			'conditions' => true,
			'language' => true,
			'translations' => false,
			'relations' => true,
			'component_details' => true,
			'siteViews' => true,
			'adminPath' => '',
			'sitePath' => '',
			'libraries' => [],
			'code' => false,
			'include' => [],
			'exclude' => [],
			'precedence' => ['table', 'notes', 'xml', 'derived'],
			'tableClass' => 'auto',
			'layout' => 'auto',
			'languageTag' => 'en-GB',
			'dryRun' => false,
			'strict' => false,
			'depth' => 12,
			'maxFiles' => 20000,
			'skipColumns' => [
				'id', 'asset_id', 'published', 'created_by', 'modified_by',
				'created', 'modified', 'checked_out', 'checked_out_time', 'version',
				'hits', 'access', 'ordering', 'metakey', 'metadesc', 'metadata', 'params'
			],
			'skipViews' => [
				'default', 'default_batch_body', 'default_batch_footer', 'default_body',
				'default_custom_admin', 'default_custom_admin_template', 'default_foot',
				'default_head', 'default_import', 'default_import_custom',
				'default_list_custom_admin', 'default_list_site', 'default_main',
				'default_site', 'default_site_template', 'default_toolbar', 'default_vdm'
			]
		];
	}
}
