<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Package\Dependency;


use Closure;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Package\Dependency\Resolver;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Power\Interfaces\TableInterface;
use VDM\Joomla\Componentbuilder\Utilities\Normalize;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Interfaces\Database\LoadInterface;
use VDM\Joomla\Interfaces\Remote\ConfigInterface;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Package Dependency Resolver Test.
 *
 * @since  1.0.0
 */
#[CoversClass(Resolver::class)]
#[UsesClass(Tracker::class)]
final class ResolverTest extends JoomlaTestCase
{
	/**
	 * Stable GUID fixtures make relationship identity explicit.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private const ITEM_GUID = '11111111-1111-4111-8111-111111111111';
	private const PARENT_GUID = '22222222-2222-4222-8222-222222222222';
	private const SUBFORM_GUID = '33333333-3333-4333-8333-333333333333';
	private const TEMPLATE_GUID = '44444444-4444-4444-8444-444444444444';
	private const LAYOUT_GUID = '55555555-5555-4555-8555-555555555555';
	private const FIELD_GUID = '66666666-6666-4666-8666-666666666666';
	private const SECOND_FIELD_GUID = '77777777-7777-4777-8777-777777777777';

	/**
	 * Language tag active before a resolver test.
	 *
	 * @var    mixed
	 * @since  1.0.0
	 */
	private mixed $languageTag;

	/**
	 * Isolate StringHelper's language and Joomla's process-static container.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->languageTag = StringHelper::$langTag;
		StringHelper::$langTag = 'en-GB';
		$language = $this->createStub(Language::class);
		$language->method('transliterate')->willReturnArgument(0);
		$factory = $this->createStub(LanguageFactoryInterface::class);
		$factory->method('createLanguage')->willReturn($language);
		$container = new Container();
		$container->share(LanguageFactoryInterface::class, $factory, true);
		$this->setJoomlaContainer($container);
	}

	/**
	 * Restore the process-static language tag.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		StringHelper::$langTag = $this->languageTag;

		parent::tearDown();
	}

	/**
	 * Every supported dependency source is normalized into item and tracker records.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testExtractRecordsDirectedDynamicAndFilesystemDependencies(): void
	{
		[$resolver, $tracker] = $this->resolver(
			parents: [
				'parent_guid' => $this->link('power', '#__componentbuilder_power', 'guid'),
				'rows|dependency' => $this->link('field', '#__componentbuilder_field', 'guid'),
			],
			children: [
				'guid' => [$this->link('child_entity', '#__componentbuilder_child_entity', 'parent_guid')],
			],
			search: [
				'code' => ['code'],
				'placeholders' => ['notes'],
			],
			files: ['file_path' => 'full'],
			folders: ['folder_rows|path' => 'custom'],
			directChildren: ['child_entity'],
			loadValue: static function (array $select, array $tables, ?array $where): mixed
			{
				if ($tables === ['#__componentbuilder_child_entity'])
				{
					return $where['parent_guid'] ?? null;
				}

				if ($tables === ['placeholder'] && ($where['target'] ?? null) === '[[[TOKEN]]]')
				{
					return 41;
				}

				return null;
			},
			normalizePath: static function (string $path, string $target): ?array
			{
				return match ([$path, $target])
				{
					['docs/readme.md', 'full'] => [
						'key' => 'file.key.md',
						'path' => 'docs/readme.md',
						'full' => '/isolated/docs/readme.md',
					],
					['assets', 'custom'] => [
						'key' => 'folder.key.zip',
						'path' => 'assets',
						'full' => '/isolated/assets',
					],
					default => null,
				};
			}
		);
		$item = (object) [
			'guid' => self::ITEM_GUID,
			'parent_guid' => self::PARENT_GUID,
			'rows' => [
				['dependency' => self::SUBFORM_GUID],
				(object) ['dependency' => self::SUBFORM_GUID],
				['dependency' => 'not-a-guid'],
			],
			'code' => <<<'PHP'
[CUSTOMCODE=buildExample]
$this->loadTemplate('alpha_alias');
LayoutHelper::render('beta_alias');
PHP,
			'notes' => 'Use [[[TOKEN]]] and ignore [[[MISSING]]].',
			'file_path' => 'docs/readme.md',
			'folder_rows' => [
				['path' => 'assets'],
				['path' => 'assets'],
			],
		];

		$result = $resolver->extract($item);

		$this->assertNotNull($result);
		$records = $result['@dependencies'];
		$this->assertCount(9, $records);
		$this->assertSame(
			[
				'power',
				'field',
				'child_entity',
				'custom_code',
				'template',
				'layout',
				'placeholder',
				'file',
				'folder',
			],
			array_column($records, 'entity')
		);
		$this->assertSame('out', $this->record($records, 'power')['direction']);
		$this->assertSame('in', $this->record($records, 'child_entity')['direction']);
		$this->assertSame('buildExample', $this->record($records, 'custom_code')['value']);
		$this->assertSame(self::TEMPLATE_GUID, $this->record($records, 'template')['value']);
		$this->assertSame(self::LAYOUT_GUID, $this->record($records, 'layout')['value']);
		$this->assertSame('[[[TOKEN]]]', $this->record($records, 'placeholder')['value']);
		$this->assertArrayNotHasKey('full', $this->record($records, 'file'));
		$this->assertArrayNotHasKey('full', $this->record($records, 'folder'));
		$this->assertSame(
			'/isolated/docs/readme.md',
			$tracker->get('file.set.file--key--md.full')
		);
		$this->assertSame(
			'/isolated/assets',
			$tracker->get('folder.set.folder--key--zip.full')
		);
		$this->assertSame(
			self::PARENT_GUID,
			$tracker->get('set.power.guid|' . self::PARENT_GUID . '.value')
		);
		$this->assertSame(
			self::ITEM_GUID,
			$tracker->get('set.child_entity.parent_guid|' . self::ITEM_GUID . '.value')
		);
	}

	/**
	 * Field XML references support GUIDs, numeric IDs, and validation rules.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testFieldXmlExtractsSubformAndValidationRuleDependencies(): void
	{
		[$resolver, $tracker] = $this->resolver(
			tableName: 'field',
			loadValue: static function (array $select, array $tables, ?array $where): mixed
			{
				if ($select === ['guid'] && $tables === ['field'] && ($where['id'] ?? null) === '17')
				{
					return self::SECOND_FIELD_GUID;
				}

				return null;
			}
		);
		$item = (object) [
			'fieldtype' => '7139f2c8-a70a-46a6-bbe3-4eefe54ca515',
			'xml' => sprintf(
				'<field fields="%s, 17, invalid" validate="exampleRule" />',
				self::FIELD_GUID
			),
		];

		$result = $resolver->extract($item);

		$this->assertNotNull($result);
		$this->assertSame(
			['field', 'field', 'validation_rule'],
			array_column($result['@dependencies'], 'entity')
		);
		$this->assertSame(
			[self::FIELD_GUID, self::SECOND_FIELD_GUID, 'exampleRule'],
			array_column($result['@dependencies'], 'value')
		);
		$this->assertSame(
			self::SECOND_FIELD_GUID,
			$tracker->get('set.field.guid|' . self::SECOND_FIELD_GUID . '.value')
		);
	}

	/**
	 * A resolver instance does not leak item-local dependencies between extracts.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testEachExtractStartsWithFreshItemState(): void
	{
		[$resolver] = $this->resolver(
			parents: [
				'parent_guid' => $this->link('power', '#__componentbuilder_power', 'guid'),
			]
		);

		$first = $resolver->extract((object) ['parent_guid' => self::PARENT_GUID]);
		$second = $resolver->extract((object) []);

		$this->assertSame(self::PARENT_GUID, $first['@dependencies'][0]['value']);
		$this->assertNull($second);
	}

	/**
	 * Already-saved dependencies remain item metadata but are not queued again.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testSavedDependencyIsNotQueuedAgain(): void
	{
		[$resolver, $tracker] = $this->resolver(
			parents: [
				'parent_guid' => $this->link('power', '#__componentbuilder_power', 'guid'),
			]
		);
		$tracker->set('save.power.guid|' . self::PARENT_GUID, true);

		$result = $resolver->extract((object) ['parent_guid' => self::PARENT_GUID]);

		$this->assertSame(self::PARENT_GUID, $result['@dependencies'][0]['value']);
		$this->assertNull($tracker->get('set.power'));
	}

	/**
	 * Placeholder entities do not recursively interpret placeholder syntax.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testPlaceholderTableSkipsPlaceholderSearchArea(): void
	{
		$config = $this->createStub(ConfigInterface::class);
		$config->method('getTable')->willReturn('placeholder');
		$config->method('getChildren')->willReturn([]);
		$config->method('getFiles')->willReturn([]);
		$config->method('getFolders')->willReturn([]);
		$table = $this->createMock(TableInterface::class);
		$table->method('parents')->willReturn([]);
		$table->method('children')->willReturn([]);
		$table->expects($this->once())
			->method('search')
			->with('placeholder', 'code')
			->willReturn([]);
		$load = $this->createStub(LoadInterface::class);
		$load->method('items')->willReturn([]);

		$resolver = new Resolver(
			$config,
			$this->createStub(Normalize::class),
			new Tracker(),
			$table,
			$load,
			$this->createStub(ItemsInterface::class)
		);

		$this->assertNull($resolver->extract((object) ['target' => '[[[SELF]]]']));
	}

	/**
	 * Scalar normalization promises integer support for relationship fields.
	 *
	 * The current filter discards all non-string scalars even though the method
	 * contract explicitly accepts and preserves integers, floats, and booleans.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testNumericParentIdentifiersArePreserved(): void
	{
		[$resolver] = $this->resolver(
			parents: [
				'parent_id' => $this->link('legacy_entity', '#__componentbuilder_legacy_entity', 'id'),
			]
		);

		$this->assertSame(
			[
				'@dependencies' => [[
					'key' => 'id',
					'value' => '42',
					'entity' => 'legacy_entity',
					'table' => '#__componentbuilder_legacy_entity',
					'direction' => 'out',
				]],
			],
			$resolver->extract((object) ['parent_id' => 42])
		);
	}

	/**
	 * Build a resolver around deterministic capability fixtures.
	 *
	 * @param   array<string, array<string, string>>              $parents         Outbound relationships.
	 * @param   array<string, array<int, array<string, string>>>  $children        Inbound relationships.
	 * @param   array<string, array<int|string, string>>          $search          Search fields by area.
	 * @param   array<string, string>                             $files           File fields by target.
	 * @param   array<string, string>                             $folders         Folder fields by target.
	 * @param   string                                            $tableName       Active entity table.
	 * @param   array<int, string>                                $directChildren  Allowed direct children.
	 * @param   Closure|null                                      $loadValue       Database value fixture.
	 * @param   Closure|null                                      $normalizePath   Path normalization fixture.
	 *
	 * @return  array{Resolver, Tracker}
	 * @since   1.0.0
	 */
	private function resolver(
		array $parents = [],
		array $children = [],
		array $search = [],
		array $files = [],
		array $folders = [],
		string $tableName = 'admin_view',
		array $directChildren = [],
		?Closure $loadValue = null,
		?Closure $normalizePath = null
	): array
	{
		$config = $this->createStub(ConfigInterface::class);
		$config->method('getTable')->willReturn($tableName);
		$config->method('getChildren')->willReturn($directChildren);
		$config->method('getFiles')->willReturn($files);
		$config->method('getFolders')->willReturn($folders);
		$table = $this->createStub(TableInterface::class);
		$table->method('parents')->willReturn($parents);
		$table->method('children')->willReturn($children);
		$table->method('search')->willReturnCallback(
			static fn(string $tableName, string $area): array => $search[$area] ?? []
		);
		$load = $this->createStub(LoadInterface::class);
		$load->method('items')->willReturnCallback(
			static fn(array $select, array $tables): array => match ($tables)
			{
				['template'] => [(object) [
					'id' => 1,
					'guid' => self::TEMPLATE_GUID,
					'alias' => 'alpha_alias',
				]],
				['layout'] => [(object) [
					'id' => 2,
					'guid' => self::LAYOUT_GUID,
					'alias' => 'beta_alias',
				]],
				default => [],
			}
		);
		$load->method('value')->willReturnCallback(
			static fn(array $select, array $tables, ?array $where = null): mixed => $loadValue === null
				? null
				: $loadValue($select, $tables, $where)
		);
		$normalize = $this->createStub(Normalize::class);
		$normalize->method('path')->willReturnCallback(
			static fn(string $path, string $target): ?array => $normalizePath === null
				? null
				: $normalizePath($path, $target)
		);
		$tracker = new Tracker();

		return [
			new Resolver(
				$config,
				$normalize,
				$tracker,
				$table,
				$load,
				$this->createStub(ItemsInterface::class)
			),
			$tracker,
		];
	}

	/**
	 * Build one relationship-map fixture.
	 *
	 * @param   string  $entity  Related entity name.
	 * @param   string  $table   Related database table.
	 * @param   string  $key     Related database key.
	 *
	 * @return  array<string, string>
	 * @since   1.0.0
	 */
	private function link(string $entity, string $table, string $key): array
	{
		return [
			'entity' => $entity,
			'table' => $table,
			'key' => $key,
		];
	}

	/**
	 * Return the single dependency record for an entity.
	 *
	 * @param   array<int, array<string, mixed>>  $records  Dependency records.
	 * @param   string                            $entity   Entity name.
	 *
	 * @return  array<string, mixed>
	 * @since   1.0.0
	 */
	private function record(array $records, string $entity): array
	{
		$matches = array_values(
			array_filter(
				$records,
				static fn(array $record): bool => $record['entity'] === $entity
			)
		);

		$this->assertCount(1, $matches, 'Expected exactly one dependency for ' . $entity . '.');

		return $matches[0];
	}
}
