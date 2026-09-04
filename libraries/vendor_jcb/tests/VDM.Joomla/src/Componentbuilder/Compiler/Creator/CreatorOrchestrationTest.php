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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Creator;


use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry as JoomlaRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;
use VDM\Joomla\Componentbuilder\Compiler\Adminview\Permission as AdminviewPermission;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitch;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AccessSwitchList;
use VDM\Joomla\Componentbuilder\Compiler\Builder\AssetsRules;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ConfigFieldsets as ConfigFieldsetsBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ConfigFieldsetsCustomfield as Customfield;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Contributors;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomTabs;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DoNotEscape;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ExtensionsParams;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldGroupControl;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FrontendParams;
use VDM\Joomla\Componentbuilder\Compiler\Builder\HasMenuGlobal;
use VDM\Joomla\Componentbuilder\Compiler\Builder\HasPermissions;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Layout as LayoutBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListFieldClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\MetaData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\MovedPublishingFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\NewPublishingFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OrderZero;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionAction;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionComponent;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionCore;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionDashboard;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionGlobalAction;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionViews;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Request as RequestBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldData as SiteFieldDataBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\TabCounter;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Component\Placeholder as ComponentPlaceholder;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Creator\AccessSections;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Builders;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsets;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsCustomfield;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsEmailHelper;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsEncryption;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsGlobal;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsGooglechart;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsGroupControl;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsSiteControl;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsUikit;
use VDM\Joomla\Componentbuilder\Compiler\Creator\CustomButtonPermissions;
use VDM\Joomla\Componentbuilder\Compiler\Creator\CustomFieldTypeFile;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldDynamic;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldString;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldXML;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldsetString;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldsetXML;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Layout;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Permission;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Request;
use VDM\Joomla\Componentbuilder\Compiler\Creator\SiteFieldData;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Field;
use VDM\Joomla\Componentbuilder\Compiler\Field\Attributes;
use VDM\Joomla\Componentbuilder\Compiler\Field\Groups;
use VDM\Joomla\Componentbuilder\Compiler\Field\ModalSelect;
use VDM\Joomla\Componentbuilder\Compiler\Field\Name;
use VDM\Joomla\Componentbuilder\Compiler\Field\TypeName;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Language\Fieldset as FieldsetLanguage;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Power;
use VDM\Joomla\Componentbuilder\Compiler\Registry as CompilerRegistry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Xml;


/**
 * High-level creator orchestration and cross-registry contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(AccessSections::class)]
#[CoversClass(Builders::class)]
#[CoversClass(ConfigFieldsets::class)]
#[CoversClass(FieldsetString::class)]
#[CoversClass(FieldsetXML::class)]
#[UsesClass(Config::class)]
#[UsesClass(Component::class)]
#[UsesClass(Language::class)]
#[UsesClass(Placeholder::class)]
#[UsesClass(ComponentPlaceholder::class)]
#[UsesClass(Permission::class)]
#[UsesClass(CustomButtonPermissions::class)]
#[UsesClass(Layout::class)]
#[UsesClass(SiteFieldData::class)]
#[UsesClass(FieldDynamic::class)]
#[UsesClass(FieldString::class)]
#[UsesClass(FieldXML::class)]
#[UsesClass(Attributes::class)]
#[UsesClass(Groups::class)]
#[UsesClass(FieldsetLanguage::class)]
#[UsesClass(AdminviewPermission::class)]
#[UsesClass(ComponentConfig::class)]
#[UsesClass(CompilerRegistry::class)]
#[UsesClass(Registry::class)]
#[UsesClass(Counter::class)]
#[UsesClass(Indent::class)]
#[UsesClass(Xml::class)]
final class CreatorOrchestrationTest extends CreatorTestCase
{
	/**
	 * Build component access XML, permissions, events, and size configuration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAccessSectionsBuildsCoreComponentContract(): void
	{
		$events = [];
		$event = $this->recordingEvent($events);
		$component = $this->component([
			'admin_views' => [[
				'settings' => (object) [
					'name_single' => 'Component',
					'name_list' => 'Components',
					'fields' => [],
					'permissions' => []
				]
			]]
		]);
		[$subject, $state] = $this->accessSections($component, $event);

		$xml = $subject->get();

		$this->assertSame([
			'jcb_ce_onBeforeBuildAccessSections',
			'jcb_ce_onAfterBuildAccessSections'
		], $events);
		$this->assertStringStartsWith('<section name="component">', $xml);
		$this->assertStringContainsString(
			'<action name="core.admin" title="JACTION_ADMIN" description="JACTION_ADMIN_COMPONENT_DESC" />',
			$xml
		);
		$this->assertStringContainsString(
			'<action name="core.edit.created" title="COM_DEMO_EDIT_CREATED_DATE"',
			$xml
		);
		$this->assertSame(12, $state['counter']->accessSize);
		$this->assertSame(0, $state['config']->get('add_assets_table_fix'));
		$this->assertSame('Use Batch', $state['language']->get('bothadmin', 'COM_DEMO_USE_BATCH'));
	}

	/**
	 * Specify the desired empty contract when a component has no admin views.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAccessSectionsReturnsEmptyStringWithoutAdminViews(): void
	{
		$events = [];
		[$subject] = $this->accessSections(
			$this->component(),
			$this->recordingEvent($events)
		);

		$this->assertSame('', $subject->get());
	}

	/**
	 * Fan a persisted field into schema, list, layout, and component registries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBuildersSynchronizesPersistedFieldRegistries(): void
	{
		[$subject, $state] = $this->builders();
		$view = [
			'history' => 1,
			'settings' => (object) ['tabs' => [1 => 'Details']]
		];
		$field = $this->builderField('field-title', 1);

		$subject->set(
			'', 'COM_DEMO_ARTICLE', 'article', 'articles', 'title',
			$view, $field, 'text', false
		);

		$this->assertSame('VARCHAR', $state['databasetables']->get('article.title.type'));
		$this->assertSame(['title'], $state['databaseuniquekeys']->get('article'));
		$this->assertTrue($state['history']->get('article'));
		$this->assertSame('title', $state['title']->get('article'));
		$this->assertSame('title', $state['layoutBuilder']->get('article.Details.1.1'));
		$this->assertSame('title', $state['lists']->get('articles.0.code'));
		$this->assertSame([
			'type' => 'VARCHAR(64)',
			'default' => '',
			'GUID' => 'field-title',
			'null_switch' => 0,
			'unique_key' => true,
			'key' => false
		], $state['componentfields']->get('article.title.db'));
		$this->assertSame('', $state['componentfields']->get('article.title.label'));
		$this->assertSame('Title', $state['language']->get('admin', 'COM_DEMO_ARTICLE_TITLE'));
	}

	/**
	 * A guid column force-loads the GuidHelper power, whichever registry it
	 * lands in, because the generated save method keeps the guid valid and
	 * unique with it even when the component does not add powers itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testBuildersForceLoadsTheGuidPowerForAGuidColumn(): void
	{
		$power = $this->createMock(Power::class);
		$power->expects($this->exactly(2))
			->method('get')
			->with('9c513baf-b279-43fd-ae29-a585c8cbc4f0', 1);

		[$subject, $state] = $this->builders(null, $power);
		$view = ['settings' => (object) ['tabs' => [1 => 'Details']]];

		// a plain key: the column registers as the table's unique guid
		$field = $this->builderField('field-guid', 1);
		$field['title'] = 0;
		$field['settings']->name = 'guid';
		$field['settings']->indexes = 2;
		$subject->set(
			'', 'COM_DEMO_ARTICLE', 'article', 'articles', 'guid',
			$view, $field, 'text', false
		);

		// a unique index: the column registers among the unique keys instead
		$field['settings']->indexes = 1;
		$subject->set(
			'', 'COM_DEMO_ITEM', 'item', 'items', 'guid',
			$view, $field, 'text', false
		);

		$this->assertTrue($state['databaseuniqueguid']->get('article'));
		$this->assertNull($state['databaseuniqueguid']->get('item'));
		$this->assertSame(['guid'], $state['databaseuniquekeys']->get('item'));
	}

	/**
	 * Keep non-persistent fields out of DB registries while retaining UI layout.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBuildersHonorsNoDatabaseFieldSwitch(): void
	{
		[$subject, $state] = $this->builders();
		$view = ['settings' => (object) ['tabs' => [1 => 'Details']]];
		$field = $this->builderField('field-note', 2);

		$subject->set(
			'COM_DEMO_ARTICLE_NOTE', 'COM_DEMO_ARTICLE', 'article', 'articles',
			'note', $view, $field, 'text', false
		);

		$this->assertNull($state['databasetables']->get('article.note'));
		$this->assertNull($state['componentfields']->get('article.note'));
		$this->assertSame('note', $state['layoutBuilder']->get('article.Details.1.1'));
	}

	/**
	 * Execute every timer-two config-fieldset stage between matching events.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigFieldsetsOrchestratesPostViewCreators(): void
	{
		$config = $this->config([
			'lang_target' => 'admin',
			'lang_prefix' => 'COM_DEMO',
			'uikit' => 2,
			'google_chart' => true
		]);
		$component = $this->component();
		$language = new Language($config);
		$fieldsets = new ConfigFieldsetsBuilder();
		$custom = new Customfield();
		$extensions = new ExtensionsParams();
		$groups = new FieldGroupControl();
		$groups->set('reviewers', 'COM_DEMO_REVIEWERS');
		$events = [];
		$event = $this->recordingEvent($events);
		$placeholder = new Placeholder($config);
		$componentPlaceholder = new ComponentPlaceholder(
			$config,
			$this->createStub(DatabaseInterface::class)
		);
		(new ReflectionProperty($componentPlaceholder, 'placeholders'))
			->setValue($componentPlaceholder, []);
		$global = new ConfigFieldsetsGlobal(
			$config, $language, $component, new Contributors(),
			$fieldsets, $extensions, $custom
		);
		$site = new ConfigFieldsetsSiteControl(
			$component, $fieldsets, $custom, new HasMenuGlobal(),
			new FrontendParams(), new Request(new RequestBuilder())
		);
		$subject = new ConfigFieldsets(
			$config,
			$component,
			$event,
			$placeholder,
			$componentPlaceholder,
			$extensions,
			$custom,
			$this->inert(\VDM\Joomla\Componentbuilder\Compiler\Creator\FieldAsString::class),
			$global,
			$site,
			new ConfigFieldsetsGroupControl(
				$config, $language, $groups, $fieldsets, $extensions, $custom
			),
			new ConfigFieldsetsUikit($config, $language, $fieldsets, $extensions, $custom),
			new ConfigFieldsetsGooglechart($config, $language, $fieldsets, $custom, $extensions),
			new ConfigFieldsetsEmailHelper($config, $language, $component, $fieldsets, $custom),
			new ConfigFieldsetsEncryption($config, $language, $component, $fieldsets, $custom),
			new ConfigFieldsetsCustomfield($config, $language, $custom, $fieldsets)
		);

		$subject->set(2);

		$content = implode(PHP_EOL, $fieldsets->get('component', []));
		$this->assertSame([
			'jcb_ce_onBeforeSetConfigFieldsets',
			'jcb_ce_onAfterSetConfigFieldsets'
		], $events);
		$this->assertStringContainsString('name="group_config"', $content);
		$this->assertStringContainsString('name="uikit_config"', $content);
		$this->assertStringContainsString('name="googlechart_config"', $content);
		$this->assertContains('"reviewers":["2"]', $extensions->get('component'));
		$this->assertContains('"uikit_version":"2"', $extensions->get('component'));
	}

	/**
	 * Build the complete legacy string fieldset with dynamic and default fields.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldsetStringBuildsDynamicAndDefaultFieldContract(): void
	{
		[$subject, $state, $view] = $this->fieldset(false);

		$result = $subject->get($view, 'Demo', 'article', 'articles');

		$this->assertStringStartsWith('<fieldset name="details">', $result);
		$this->assertStringContainsString('name="id"', $result);
		$this->assertStringContainsString('name="title"', $result);
		$this->assertStringContainsString('type="text"', $result);
		$this->assertStringEndsWith("\t</fieldset>", $result);
		$this->assertSame('title', $state['fieldNames']->get('article.title'));
		$this->assertSame(9, $state['counter']->field);
		$this->assertSame([
			'jcb_ce_onBeforeBuildFields',
			'jcb_ce_onAfterBuildFields'
		], $state['events']);
	}

	/**
	 * Build the XML backend fieldset with the same semantic field inventory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldsetXmlBuildsDynamicAndDefaultFieldContract(): void
	{
		[$subject, $state, $view] = $this->fieldset(true);

		$result = $subject->get($view, 'Demo', 'article', 'articles');
		$xml = new \SimpleXMLElement($result);

		$this->assertSame('details', (string) $xml['name']);
		$this->assertCount(9, $xml->field);
		$this->assertSame('id', (string) $xml->field[0]['name']);
		$this->assertSame('title', (string) $xml->field[8]['name']);
		$this->assertSame('text', (string) $xml->field[8]['type']);
		$this->assertSame('title', $state['fieldNames']->get('article.title'));
		$this->assertSame(9, $state['counter']->field);
		$this->assertSame([
			'jcb_ce_onBeforeBuildFields',
			'jcb_ce_onAfterBuildFields'
		], $state['events']);
	}

	/**
	 * Create the access-section subject and observable collaborators.
	 *
	 * @param   Component       $component  Component state.
	 * @param   EventInterface  $event      Event dispatcher.
	 *
	 * @return  array{0:AccessSections,1:array<string,mixed>}
	 * @since   6.1.6
	 */
	private function accessSections(
		Component $component,
		EventInterface $event
	): array
	{
		$config = $this->config([
			'lang_prefix' => 'COM_DEMO',
			'default_fields' => []
		]);
		$language = new Language($config);
		$counter = $this->createStub(Counter::class);
		$views = new PermissionViews();
		$componentPermissions = new PermissionComponent();
		$permission = new Permission(
			$config,
			new PermissionCore(),
			$views,
			new PermissionAction(),
			$componentPermissions,
			new PermissionGlobalAction(),
			new PermissionDashboard(),
			$counter,
			$language
		);
		$buttons = new CustomButtonPermissions(
			$config, $language, $componentPermissions, $counter
		);
		$subject = new AccessSections(
			$config,
			$event,
			$language,
			$component,
			$this->createStub(Name::class),
			new TypeName(),
			$counter,
			$permission,
			new AssetsRules(),
			new CustomTabs(),
			$views,
			new PermissionFields(),
			$componentPermissions,
			$buttons
		);

		return [$subject, compact('config', 'language', 'counter')];
	}

	/**
	 * Create the registry-heavy Builders subject without the compiler factory.
	 *
	 * @param   Config|null  $config  Shared configuration when composing fieldsets.
	 * @param   Power|null   $power   The power loader, when a test observes it.
	 *
	 * @return  array{0:Builders,1:array<string,mixed>}
	 * @since   6.1.6
	 */
	private function builders(?Config $config = null, ?Power $power = null): array
	{
		$config ??= $this->config([
			'lang_target' => 'admin',
			'lang_prefix' => 'COM_DEMO',
			'default_fields' => [],
			'uikit' => 0
		]);
		$language = new Language($config);
		$placeholder = new Placeholder($config);
		$layoutBuilder = new LayoutBuilder();
		$layout = new Layout(
			$config,
			new OrderZero(),
			new TabCounter(),
			$layoutBuilder,
			new MovedPublishingFields(),
			new NewPublishingFields()
		);
		$sitefielddata = new SiteFieldData(
			$config,
			new SiteFields(),
			new SiteFieldDataBuilder()
		);
		$reflection = new ReflectionClass(Builders::class);
		$arguments = [];
		$state = compact(
			'config', 'language', 'placeholder', 'layoutBuilder', 'layout', 'sitefielddata'
		);

		foreach ($reflection->getConstructor()->getParameters() as $parameter)
		{
			$type = $parameter->getType();

			if (!$type instanceof ReflectionNamedType)
			{
				throw new \LogicException(
					'Builders constructor dependencies must remain named object types.'
				);
			}

			$class = $type->getName();

			$dependency = match ($class)
			{
				Config::class => $config,
				Power::class => $power ?? $this->createStub(Power::class),
				Language::class => $language,
				Placeholder::class => $placeholder,
				Layout::class => $layout,
				SiteFieldData::class => $sitefielddata,
				CMSApplicationInterface::class => $this->createStub(CMSApplicationInterface::class),
				default => (new ReflectionClass($class))->newInstance()
			};
			$arguments[] = $dependency;
			$state[$parameter->getName()] = $dependency;
		}

		return [$reflection->newInstanceArgs($arguments), $state];
	}

	/**
	 * Create a complete fieldset subject for either backend.
	 *
	 * @param   bool  $xmlBackend  Use object/XML field rendering.
	 *
	 * @return  array{0:FieldsetString|FieldsetXML,1:array<string,mixed>,2:array<string,mixed>}
	 * @since   6.1.6
	 */
	private function fieldset(bool $xmlBackend): array
	{
		$config = $this->config([
			'lang_target' => 'admin',
			'lang_prefix' => 'COM_DEMO',
			'default_fields' => [],
			'uikit' => 0
		]);
		$placeholder = new Placeholder($config);
		$placeholder->set('VIEW', 'ARTICLE', false);
		$placeholder->set('VIEWS', 'ARTICLES', false);
		$language = new Language($config);
		$fieldNames = new FieldNames();
		$accessSwitch = new AccessSwitch();
		$accessSwitchList = new AccessSwitchList();
		$metadata = new MetaData();
		$fieldsetLanguage = new FieldsetLanguage(
			$language, $metadata, $accessSwitch, $accessSwitchList
		);
		$permission = new AdminviewPermission(new HasPermissions());
		$counter = $this->createStub(Counter::class);
		$events = [];
		$event = $this->recordingEvent($events);
		[$builders, $builderState] = $this->builders($config);
		$groups = new Groups($this->createStub(DatabaseInterface::class));
		$name = $this->createStub(Name::class);
		$name->method('get')->willReturnCallback(
			static fn (array &$field): string => (string) $field['settings']->name
		);
		$typeName = $this->createStub(TypeName::class);
		$typeName->method('get')->willReturnCallback(
			static fn (array &$field): string => (string) $field['settings']->type_name
		);
		$attributes = new Attributes(
			$config,
			new CompilerRegistry(),
			new ListFieldClass(),
			new DoNotEscape(),
			$placeholder,
			$this->createStub(Customcode::class),
			$language,
			$groups
		);
		$xml = new Xml($config, $this->createStub(CMSApplicationInterface::class));
		$fieldRenderer = $xmlBackend
			? new FieldXML(
				$config, $language, $this->createStub(Field::class), $groups,
				$name, $typeName, $attributes, $this->inert(ModalSelect::class),
				$xml, $this->inert(CustomFieldTypeFile::class), $counter,
				$builderState['componentfields']
			)
			: new FieldString(
				$config, $language, $this->createStub(Field::class), $groups,
				$name, $typeName, $attributes, $this->inert(ModalSelect::class),
				$this->inert(CustomFieldTypeFile::class), $counter,
				$builderState['componentfields']
			);
		$fieldDynamic = new FieldDynamic(
			$name,
			$typeName,
			$attributes,
			$this->inert(ModalSelect::class),
			$groups,
			$fieldNames,
			$fieldRenderer,
			$builders,
			$builderState['layout']
		);
		$arguments = [
			$config,
			$placeholder,
			$fieldsetLanguage,
			$event,
			$permission,
			$fieldDynamic,
			$fieldNames,
			$accessSwitch,
			$metadata,
			$builderState['layout'],
			$counter
		];

		if ($xmlBackend)
		{
			$arguments[] = $xml;
		}

		$class = $xmlBackend ? FieldsetXML::class : FieldsetString::class;
		$subject = (new ReflectionClass($class))->newInstanceArgs($arguments);
		$field = $this->builderField('field-title', 2);
		$field['settings']->name = 'title';
		$field['settings']->type_name = 'text';
		$field['settings']->properties = [
			['name' => 'name', 'example' => 'title', 'translatable' => 0, 'mandatory' => 1],
			['name' => 'type', 'example' => 'text', 'translatable' => 0, 'mandatory' => 1]
		];
		$view = [
			'history' => 0,
			'access' => 0,
			'metadata' => 0,
			'settings' => (object) [
				'name_single' => 'Article',
				'name_list' => 'Articles',
				'type' => 1,
				'tabs' => [1 => 'Details'],
				'permissions' => [],
				'fields' => [$field]
			]
		];

		$state = compact('fieldNames', 'counter', 'language');
		$state['events'] =& $events;

		return [$subject, $state, $view];
	}

	/**
	 * Create a valid database-field payload used by Builders and fieldsets.
	 *
	 * @param   string  $guid  Field GUID.
	 * @param   int     $list  List/DB switch.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.6
	 */
	private function builderField(string $guid, int $list): array
	{
		return [
			'field' => $guid,
			'list' => $list,
			'title' => 1,
			'alias' => 0,
			'link' => 0,
			'sort' => 0,
			'search' => 0,
			'filter' => 0,
			'tab' => 1,
			'alignment' => 1,
			'order_edit' => 1,
			'settings' => (object) [
				'id' => 17,
				'guid' => $guid,
				'name' => 'title',
				'type_name' => 'text',
				'xml' => '<field name="title" type="text" label="Title" />',
				'properties' => [],
				'datatype' => 'VARCHAR',
				'datalenght' => '64',
				'datalenght_other' => '',
				'datadefault' => '',
				'datadefault_other' => '',
				'null_switch' => 0,
				'indexes' => 1,
				'store' => 0
			]
		];
	}

	/**
	 * Create a callback event dispatcher that records event order.
	 *
	 * @param   array<int, string>  $events  Event bucket.
	 *
	 * @return  EventInterface
	 * @since   6.1.6
	 */
	private function recordingEvent(array &$events): EventInterface
	{
		$event = $this->createStub(EventInterface::class);
		$event->method('trigger')->willReturnCallback(
			static function (string $name) use (&$events): void
			{
				$events[] = $name;
			}
		);

		return $event;
	}

	/**
	 * Create an isolated compiler configuration.
	 *
	 * @param   array<string, mixed>  $values  Initial values.
	 *
	 * @return  Config
	 * @since   6.1.6
	 */
	private function config(array $values = []): Config
	{
		$config = new Config(new Input(), new JoomlaRegistry(), new JoomlaRegistry());

		foreach ($values as $key => $value)
		{
			$config->set($key, $value);
		}

		return $config;
	}

	/**
	 * Create a real component registry without loading component data.
	 *
	 * @param   array<string, mixed>  $values  Component state.
	 *
	 * @return  Component
	 * @since   6.1.6
	 */
	private function component(array $values = []): Component
	{
		$component = new Component(
			$this->inert(Data::class),
			$this->createStub(EventInterface::class)
		);

		foreach ($values as $key => $value)
		{
			$component->set($key, $value);
		}

		return $component;
	}

	/**
	 * Create an unused final collaborator without invoking its dependency graph.
	 *
	 * @template T of object
	 * @param   class-string<T>  $class  Collaborator class.
	 *
	 * @return  T
	 * @since   6.1.6
	 */
	private function inert(string $class): object
	{
		return (new ReflectionClass($class))->newInstanceWithoutConstructor();
	}
}
