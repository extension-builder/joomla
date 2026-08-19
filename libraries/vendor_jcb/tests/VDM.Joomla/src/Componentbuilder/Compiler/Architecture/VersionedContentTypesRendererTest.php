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
use ReflectionClass;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Alias;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DynamicFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\History;
use VDM\Joomla\Componentbuilder\Compiler\Builder\MainTextField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Tags;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Title;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UninstallScriptContent;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UninstallScriptContext;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;


/**
 * Generated content type declaration contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedContentTypesRendererTest extends ArchitectureTestCase
{
	/**
	 * The context of everything the run registered for removal.
	 *
	 * @var    UninstallScriptContext
	 * @since  6.1.7
	 */
	private UninstallScriptContext $context;

	/**
	 * The content types the run registered.
	 *
	 * @var    UninstallScriptContent
	 * @since  6.1.7
	 */
	private UninstallScriptContent $content;

	/**
	 * Whether the demo view keeps history and carries a title.
	 *
	 * @var    bool
	 * @since  6.1.7
	 */
	private bool $declares = true;

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
	 * The targets that hand each content type to the script.php helper.
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
	 * Build the content types renderer of one Joomla target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function subject(string $version, int $major): object
	{
		$this->config()->set('joomla_version', $major);
		$this->config()->set('namespace_prefix', 'Acme');
		$this->context = new UninstallScriptContext();
		$this->content = new UninstallScriptContent();

		$contentOne = new ContentOne();
		$contentOne->set('ComponentNamespace', 'Demo');

		$history = new History();
		$title = new Title();
		$alias = new Alias();
		$mainText = new MainTextField();
		$dynamic = new DynamicFields();

		if ($this->declares)
		{
			$history->set('look', true);
			$title->set('look', 'name');
			$alias->set('look', 'alias');
			$mainText->set('look', 'description');
			$dynamic->set('look', ['"note": "note"']);
		}

		return $this->renderer(
			$this->targetClass($version, 'Component\\ContentTypes', ['JoomlaThree']),
			[
				'component' => $this->component(),
				'contentone' => $contentOne,
				'alias' => $alias,
				'dynamicfields' => $dynamic,
				'history' => $history,
				'maintextfield' => $mainText,
				'tags' => new Tags(),
				'title' => $title,
				'uninstallcontext' => $this->context,
				'uninstallcontent' => $this->content,
			]
		);
	}

	/**
	 * Build a component carrying one admin view.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function component(): Component
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));

		$settings = new stdClass();
		$settings->name_single = 'look';
		$settings->name_list = 'looks';
		$component->set('admin_views', [['settings' => $settings]]);

		return $component;
	}

	/**
	 * A view that neither keeps history nor carries tags declares no type.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAViewThatNeedsNoContentTypeSaysSo(string $version, int $major): void
	{
		$this->declares = false;
		$subject = $this->subject($version, $major);

		$this->assertFalse($subject->contentType('look', 'demo'));
		$this->assertSame('', $subject->get('install'));
		$this->assertSame([], $this->context->allActive());
		$this->assertSame([], $this->content->allActive());
	}

	/**
	 * A component that declares no admin view produces nothing.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testAComponentWithoutAdminViewsProducesNothing(string $version, int $major): void
	{
		$this->config()->set('joomla_version', $major);
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();

		$subject = $this->renderer(
			$this->targetClass($version, 'Component\\ContentTypes', ['JoomlaThree']),
			['component' => new Component($data, $this->createStub(EventInterface::class))]
		);

		$this->assertSame('', $subject->get('install'));
	}

	/**
	 * Every target declares the same thing about the view itself.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testEveryTargetDeclaresTheSameThingAboutTheView(string $version, int $major): void
	{
		$type = $this->subject($version, $major)->contentType('look', 'demo');

		$this->assertSame('Demo Look', $type['type_title']);
		$this->assertSame('com_demo.look', $type['type_alias']);
		$this->assertSame(self::EXPECTED_FIELD_MAPPINGS, $type['field_mappings']);
	}

	/**
	 * Joomla 3 names the table pair, the helper route and the models form.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeNamesItsTablePairRouteAndModelsForm(): void
	{
		$type = $this->subject('JoomlaThree', 3)->contentType('look', 'demo');

		$this->assertSame(self::EXPECTED_J3_TABLE, $type['table']);
		$this->assertSame('DemoHelperRoute::getLookRoute', $type['router']);
		$this->assertSame(self::EXPECTED_J3_HISTORY, $type['content_history_options']);
		// Joomla 3 says nothing about rules
		$this->assertArrayNotHasKey('rules', $type);
	}

	/**
	 * Later targets name the namespaced table, no route, and empty rules.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernTargetsNameTheNamespacedTableAndNoRoute(string $version, int $major): void
	{
		$type = $this->subject($version, $major)->contentType('look', 'demo');

		$this->assertSame(self::EXPECTED_J4_TABLE, $type['table']);
		$this->assertSame('', $type['rules']);
		$this->assertSame('', $type['router']);
		$this->assertSame(self::EXPECTED_J4_HISTORY, $type['content_history_options']);
	}

	/**
	 * The keys are declared in the order Joomla 3 writes its columns in.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheKeysKeepTheOrderJoomlaThreeWritesThemIn(): void
	{
		$this->assertSame(
			['type_title', 'type_alias', 'table', 'field_mappings', 'router',
				'content_history_options'],
			array_keys($this->subject('JoomlaThree', 3)->contentType('look', 'demo'))
		);
		$this->assertSame(
			['type_title', 'type_alias', 'table', 'rules', 'field_mappings', 'router',
				'content_history_options'],
			array_keys($this->subject('JoomlaSix', 6)->contentType('look', 'demo'))
		);
	}

	/**
	 * Declaring a type registers what has to be removed again.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testDeclaringATypeRegistersWhatMustBeRemoved(string $version, int $major): void
	{
		$this->subject($version, $major)->get('install');

		$this->assertSame(['Look' => 'com_demo.look'], $this->context->allActive());
		$this->assertSame(['look' => 'look'], $this->content->allActive());
	}

	/**
	 * A category type names the core category table and its own removal entry.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('versions')]
	public function testACategoryTypeNamesTheCoreCategoryTable(string $version, int $major): void
	{
		$type = $this->subject($version, $major)->categoryContentType('look', 'looks', 'demo');

		// nothing seeded a category, so the code and the other view read as error
		$this->assertSame('Demo Look Error', $type['type_title']);
		$this->assertSame('com_demo.error.category', $type['type_alias']);
		$this->assertSame(self::EXPECTED_CATEGORY_TABLE, $type['table']);
		$this->assertSame(
			['Look error' => 'com_demo.error.category'],
			$this->context->allActive()
		);
	}

	/**
	 * Joomla 3 routes a category through the helper and reads the models form.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeRoutesACategoryThroughItsHelper(): void
	{
		$type = $this->subject('JoomlaThree', 3)->categoryContentType('look', 'looks', 'demo');

		$this->assertSame('DemoHelperRoute::getCategoryRoute', $type['router']);
		$this->assertSame(self::EXPECTED_J3_CATEGORY_HISTORY, $type['content_history_options']);
		$this->assertArrayNotHasKey('rules', $type);
	}

	/**
	 * Later targets leave a category to their own router and empty its rules.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernTargetsLeaveACategoryToTheirRouter(string $version, int $major): void
	{
		$type = $this->subject($version, $major)->categoryContentType('look', 'looks', 'demo');

		$this->assertSame('', $type['router']);
		$this->assertSame('', $type['rules']);
		$this->assertSame(self::EXPECTED_J4_CATEGORY_HISTORY, $type['content_history_options']);
	}

	/**
	 * Later targets hand each declaration to the script.php helper.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   int     $major    Joomla target major.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('modernVersions')]
	public function testModernTargetsHandEachTypeToTheHelper(string $version, int $major): void
	{
		$this->assertSame(
			self::EXPECTED_J4_INSTALL,
			$this->subject($version, $major)->get('install')
		);
	}

	/**
	 * The action a modern target is compiling for only names the comment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheActionOnlyNamesTheCommentOnModernTargets(): void
	{
		$install = $this->subject('JoomlaSix', 6)->get('install');
		$update = $this->subject('JoomlaSix', 6)->get('update');

		$this->assertStringContainsString('// Install Look Content Types.', $install);
		$this->assertStringContainsString('// Update Look Content Types.', $update);
		$this->assertSame(
			str_replace('// Install Look', '// Update Look', $install),
			$update
		);
	}

	/**
	 * Joomla 3 assembles the row itself and inserts it when installing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeAssemblesAndInsertsTheRow(): void
	{
		$this->assertSame(
			self::EXPECTED_J3_INSTALL,
			$this->subject('JoomlaThree', 3)->get('install')
		);
	}

	/**
	 * Joomla 3 looks the row up first when updating, and updates what it finds.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaThreeLooksTheRowUpWhenUpdating(): void
	{
		$this->assertSame(
			self::EXPECTED_J3_UPDATE,
			$this->subject('JoomlaThree', 3)->get('update')
		);
	}

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_FIELD_MAPPINGS = <<<'GEN'
		{"common": {"core_content_item_id": "id","core_title": "name","core_state": "published","core_alias": "alias","core_created_time": "created","core_modified_time": "modified","core_body": "description","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "null","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"note": "note"}}
		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_TABLE = <<<'GEN'
		{"special": {"dbtable": "#__demo_look","key": "id","type": "Look","prefix": "demoTable","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}
		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J4_TABLE = <<<'GEN'
		{"special": {"dbtable": "#__demo_look","key": "id","type": "LookTable","prefix": "Acme\Component\Demo\Administrator\Table"}}
		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_HISTORY = <<<'GEN'
		{"formFile": "administrator/components/com_demo/models/forms/look.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering","version","hits"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}]}
		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J4_HISTORY = <<<'GEN'
		{"formFile": "administrator/components/com_demo/forms/look.xml","hideFields": ["asset_id","checked_out","checked_out_time"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering","version","hits"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}]}
		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_CATEGORY_TABLE = <<<'GEN'
		{"special":{"dbtable":"#__categories","key":"id","type":"Category","prefix":"JTable","config":"array()"},"common":{"dbtable":"#__ucm_content","key":"ucm_id","type":"Corecontent","prefix":"JTable","config":"array()"}}
		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_CATEGORY_HISTORY = <<<'GEN'
		{"formFile":"administrator\/components\/com_categories\/models\/forms\/category.xml", "hideFields":["asset_id","checked_out","checked_out_time","version","lft","rgt","level","path","extension"], "ignoreChanges":["modified_user_id", "modified_time", "checked_out", "checked_out_time", "version", "hits", "path"],"convertToInt":["publish_up", "publish_down"], "displayLookup":[{"sourceColumn":"created_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"modified_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"parent_id","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}]}
		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J4_CATEGORY_HISTORY = <<<'GEN'
		{"formFile":"administrator\/components\/com_categories\/forms\/category.xml", "hideFields":["asset_id","checked_out","checked_out_time","version","lft","rgt","level","path","extension"], "ignoreChanges":["modified_user_id", "modified_time", "checked_out", "checked_out_time", "version", "hits", "path"],"convertToInt":["publish_up", "publish_down"], "displayLookup":[{"sourceColumn":"created_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"modified_user_id","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"parent_id","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"}]}
		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J4_INSTALL = <<<'GEN'


					// Install Look Content Types.
					$this->setContentType(
						// typeTitle
						'Demo Look',
						// typeAlias
						'com_demo.look',
						// table
						'{"special": {"dbtable": "#__demo_look","key": "id","type": "LookTable","prefix": "Acme\Component\Demo\Administrator\Table"}}',
						// rules
						'',
						// fieldMappings
						'{"common": {"core_content_item_id": "id","core_title": "name","core_state": "published","core_alias": "alias","core_created_time": "created","core_modified_time": "modified","core_body": "description","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "null","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"note": "note"}}',
						// router
						'',
						// contentHistoryOptions
						'{"formFile": "administrator/components/com_demo/forms/look.xml","hideFields": ["asset_id","checked_out","checked_out_time"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering","version","hits"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}]}'
					);


		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_INSTALL = <<<'GEN'


					// Get The Database object
					$db = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getDbo();

					// Create the look content type object.
					$look = new \stdClass();
					$look->type_title = 'Demo Look';
					$look->type_alias = 'com_demo.look';
					$look->table = '{"special": {"dbtable": "#__demo_look","key": "id","type": "Look","prefix": "demoTable","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}';
					$look->field_mappings = '{"common": {"core_content_item_id": "id","core_title": "name","core_state": "published","core_alias": "alias","core_created_time": "created","core_modified_time": "modified","core_body": "description","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "null","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"note": "note"}}';
					$look->router = 'DemoHelperRoute::getLookRoute';
					$look->content_history_options = '{"formFile": "administrator/components/com_demo/models/forms/look.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering","version","hits"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}]}';

					// Set the object into the content types table.
					$look_Inserted = $db->insertObject('#__content_types', $look);


		GEN;

	/**
	 * The generated declaration this contract protects, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_J3_UPDATE = <<<'GEN'


					// Get The Database object
					$db = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getDbo();

					// Create the look content type object.
					$look = new \stdClass();
					$look->type_title = 'Demo Look';
					$look->type_alias = 'com_demo.look';
					$look->table = '{"special": {"dbtable": "#__demo_look","key": "id","type": "Look","prefix": "demoTable","config": "array()"},"common": {"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}';
					$look->field_mappings = '{"common": {"core_content_item_id": "id","core_title": "name","core_state": "published","core_alias": "alias","core_created_time": "created","core_modified_time": "modified","core_body": "description","core_hits": "hits","core_publish_up": "null","core_publish_down": "null","core_access": "null","core_params": "params","core_featured": "null","core_metadata": "null","core_language": "null","core_images": "null","core_urls": "null","core_version": "version","core_ordering": "ordering","core_metakey": "null","core_metadesc": "null","core_catid": "null","core_xreference": "null","asset_id": "asset_id"},"special": {"note": "note"}}';
					$look->router = 'DemoHelperRoute::getLookRoute';
					$look->content_history_options = '{"formFile": "administrator/components/com_demo/models/forms/look.xml","hideFields": ["asset_id","checked_out","checked_out_time","version"],"ignoreChanges": ["modified_by","modified","checked_out","checked_out_time","version","hits"],"convertToInt": ["published","ordering","version","hits"],"displayLookup": [{"sourceColumn": "created_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"},{"sourceColumn": "modified_by","targetTable": "#__users","targetColumn": "id","displayColumn": "name"}]}';

					// Check if look type is already in content_type DB.
					$look_id = null;
					$query = $db->getQuery(true);
					$query->select($db->quoteName(array('type_id')));
					$query->from($db->quoteName('#__content_types'));
					$query->where($db->quoteName('type_alias') . ' LIKE '. $db->quote($look->type_alias));
					$db->setQuery($query);
					$db->execute();

					// Set the object into the content types table.
					if ($db->getNumRows())
					{
						$look->type_id = $db->loadResult();
						$look_Updated = $db->updateObject('#__content_types', $look, 'type_id');
					}
					else
					{
						$look_Inserted = $db->insertObject('#__content_types', $look);
					}


		GEN;
}
