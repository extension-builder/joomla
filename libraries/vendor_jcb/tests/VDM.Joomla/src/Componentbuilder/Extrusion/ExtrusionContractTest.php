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

namespace VDM\Joomla\Tests\Componentbuilder\Extrusion;


use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use VDM\Joomla\Componentbuilder\Extrusion\Helper\Builder;
use VDM\Joomla\Componentbuilder\Extrusion\Helper\Extrusion;
use VDM\Joomla\Componentbuilder\Extrusion\Helper\Mapping;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * SQL extrusion parsing, build orchestration, and component-link contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Mapping::class)]
#[CoversClass(Builder::class)]
#[CoversClass(Extrusion::class)]
final class ExtrusionContractTest extends JoomlaTestCase
{
	/**
	 * Component helper option active before each test.
	 *
	 * @var    string|null
	 * @since  6.1.6
	 */
	private ?string $componentOption = null;

	/**
	 * Isolate the deprecated helper's component table prefix.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->componentOption = Helper::$option;
		Helper::$option = 'com_componentbuilder';
	}

	/**
	 * Restore the component helper option.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		Helper::$option = $this->componentOption;
		$this->componentOption = null;

		parent::tearDown();
	}

	/**
	 * Extract normalized table names from supported CREATE and INSERT forms.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMappingExtractsAndNormalizesSqlTableNames(): void
	{
		$subject = new class extends Mapping
		{
			/**
			 * Initialize the component code used for table normalization.
			 *
			 * @since  6.1.6
			 */
			public function __construct()
			{
				$this->name_code = 'demo';
			}

			/**
			 * Expose table-name extraction for the contract test.
			 *
			 * @param   string  $sql  SQL statement to inspect.
			 *
			 * @return  string|null  Normalized table name.
			 * @since   6.1.6
			 */
			public function table(string $sql): ?string
			{
				return $this->getTableName($sql);
			}
		};

		$this->assertSame('article', $subject->table('CREATE TABLE IF NOT EXISTS `#__demo_article` (`id` INT)'));
		$this->assertSame('category', $subject->table('INSERT INTO `#__demo_category` (`name`) VALUES (\'A\')'));
		$this->assertSame('logs', $subject->table('CREATE TABLE `demo_logs` (`id` INT)'));
		$this->assertSame('article', $subject->table('ALTER TABLE `#__demo_article` ADD `title` TEXT'));
	}

	/**
	 * Normalize SQL column metadata, including custom comment configuration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMappingPreparesConfiguredFieldDetailsAndOtherValues(): void
	{
		$subject = new class extends Mapping
		{
			/**
			 * Construct the mapping fixture without legacy setup.
			 *
			 * @since  6.1.6
			 */
			public function __construct()
			{
			}

			/**
			 * Expose field-detail normalization.
			 *
			 * @param   string  $name  Field name.
			 * @param   object  $data  SQL field metadata.
			 *
			 * @return  array<string, mixed>  Normalized details.
			 * @since   6.1.6
			 */
			public function prepare(string $name, object $data): array
			{
				return $this->prepareFieldDetails($name, $data);
			}

			/**
			 * Expose column-definition extraction.
			 *
			 * @param   string  $sql  SQL statement to inspect.
			 *
			 * @return  string|null  Extracted definition block.
			 * @since   6.1.6
			 */
			public function definitions(string $sql): ?string
			{
				return $this->extractColumnDefinitions($sql);
			}
		};
		$data = (object) [
			'Type' => 'VARCHAR(512)',
			'Comment' => '{"label":"Contact Email","type":"Email"}',
			'Default' => 'unknown@example.test',
			'Null' => 'NO',
			'Key' => 'UNI',
		];
		$field = $subject->prepare('contact_email', $data);

		$this->assertSame('contact_email', $field['name']);
		$this->assertSame('Contact Email', $field['label']);
		$this->assertSame('VARCHAR', $field['dataType']);
		$this->assertSame('Email', $field['fieldType']);
		$this->assertSame('Other', $field['size']);
		$this->assertSame(512, $field['sizeOther']);
		$this->assertSame('Other', $field['default']);
		$this->assertSame('unknown@example.test', $field['defaultOther']);
		$this->assertSame('NOT NULL', $field['null']);
		$this->assertSame(1, $field['key']);
		$this->assertSame(
			"\n `id` INT,\n  `title` TEXT\n",
			$subject->definitions("-- header\nCREATE TABLE `demo` (\n `id` INT,\n /* inline */ `title` TEXT\n) ENGINE=InnoDB")
		);
		$this->assertNull($subject->definitions('CREATE TABLE demo'));
	}

	/**
	 * Build all fields before their owning view and preserve map order.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBuilderOrchestratesFieldsBeforeEachView(): void
	{
		$subject = new class extends Builder
		{
			/**
			 * Recorded build operations.
			 *
			 * @var    array<int, string>
			 * @since  6.1.6
			 */
			public array $calls = [];

			/**
			 * Seed the field/view map used by the orchestration contract.
			 *
			 * @since  6.1.6
			 */
			public function __construct()
			{
				$this->map = [
					'article' => [['name' => 'title'], ['name' => 'body']],
					'category' => [['name' => 'name']],
				];
			}

			/**
			 * Run the protected build orchestration.
			 *
			 * @return  bool  True when every view and field is built.
			 * @since   6.1.6
			 */
			public function build(): bool
			{
				return $this->setBuild();
			}

			/**
			 * Record a field build.
			 *
			 * @param   string                $view   Owning view name.
			 * @param   array<string, mixed>  $field  Field definition.
			 *
			 * @return  bool  True after recording the field.
			 * @since   6.1.6
			 */
			protected function setField(string $view, array $field): bool
			{
				$this->calls[] = "field:{$view}.{$field['name']}";
				return true;
			}

			/**
			 * Record a view build.
			 *
			 * @param   string  $name  View name.
			 *
			 * @return  bool  True after recording the view.
			 * @since   6.1.6
			 */
			protected function setView(string $name): bool
			{
				$this->calls[] = 'view:' . $name;
				return true;
			}
		};

		$this->assertTrue($subject->build());
		$this->assertSame(
			['field:article.title', 'field:article.body', 'view:article', 'field:category.name', 'view:category'],
			$subject->calls
		);
	}

	/**
	 * Merge generated admin views into the component relation payload.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testExtrusionLinksGeneratedViewsWithReviewedDefaults(): void
	{
		$query = $this->createMock(DatabaseQuery::class);
		$query->expects($this->once())->method('select')->willReturnSelf();
		$query->expects($this->once())->method('from')->willReturnSelf();
		$query->expects($this->once())->method('where')->willReturnSelf();
		$db = $this->createMock(DatabaseDriver::class);
		$db->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$db->method('quoteName')->willReturnCallback(static fn(string $value): string => $value);
		$db->expects($this->once())->method('setQuery')->with($query)->willReturnSelf();
		$db->expects($this->once())->method('execute')->willReturn(true);
		$db->expects($this->once())->method('getNumRows')->willReturn(0);
		$db->expects($this->once())->method('insertObject')->with(
			'#__componentbuilder_component_admin_views',
			$this->callback(static function (object $record): bool
			{
				$views = json_decode($record->addadmin_views, true);
				return $record->joomla_component === 77
					&& $record->created === '2026-08-14 12:00:00'
					&& $record->created_by === 42
					&& $record->published === 1
					&& $views['addadmin_views4']['adminview'] === 11
					&& $views['addadmin_views4']['order'] === 5
					&& $views['addadmin_views5']['adminview'] === 12
					&& $views['addadmin_views5']['icomoon'] === 'joomla'
					&& $views['addadmin_views5']['history'] === 1;
			})
		)->willReturn(true);
		$this->setJoomlaFactoryProperty('database', $db);
		$subject = new class($db) extends Extrusion
		{
			/**
			 * Seed database and administrator-view state.
			 *
			 * @param   DatabaseDriver  $db  Database boundary.
			 *
			 * @since   6.1.6
			 */
			public function __construct(DatabaseDriver $db)
			{
				$this->db = $db;
				$this->views = [11, 12];
				$this->addadmin_views = ['existing' => ['adminview' => 9]];
				$this->today = '2026-08-14 12:00:00';
				$this->user = (object) ['id' => 42];
			}

			/**
			 * Link the configured administrator views to a component.
			 *
			 * @param   int  $component  Component identifier, mutated by reference.
			 *
			 * @return  bool  True when every view is linked.
			 * @since   6.1.6
			 */
			public function link(int &$component): bool
			{
				return $this->setAdminViews($component);
			}
		};
		$component = 77;

		$this->assertTrue($subject->link($component));
	}

	/**
	 * The first list field (array index zero) must remain marked as a list field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testBuilderMarksTheFirstConfiguredListField(): void
	{
		$db = $this->createMock(DatabaseDriver::class);
		$db->expects($this->once())->method('insertObject')->with(
			'#__componentbuilder_admin_fields',
			$this->callback(static function (object $record): bool
			{
				$fields = json_decode($record->addfields, true);

				return $fields['addfields0']['list'] === 1
					&& $fields['addfields0']['search'] === 1
					&& $fields['addfields0']['filter'] === 1;
			})
		)->willReturn(true);
		$subject = new class($db) extends Builder
		{
			/**
			 * Seed database and field state for the list-index regression.
			 *
			 * @param   DatabaseDriver  $db  Database boundary.
			 *
			 * @since   6.1.6
			 */
			public function __construct(DatabaseDriver $db)
			{
				$this->db = $db;
				$this->fields = ['article' => [10, 20]];
				$this->list = ['article' => [10, 20]];
				$this->today = '2026-08-14 12:00:00';
				$this->user = (object) ['id' => 42];
			}

			/**
			 * Add the configured field set to the administrator view.
			 *
			 * @return  bool  True when every field is added.
			 * @since   6.1.6
			 */
			public function fields(): bool
			{
				return $this->addFields('article', 99);
			}
		};

		$this->assertTrue($subject->fields());
	}
}
