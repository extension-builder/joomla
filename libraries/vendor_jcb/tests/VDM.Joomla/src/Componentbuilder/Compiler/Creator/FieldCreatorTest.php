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
use ReflectionProperty;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ComponentFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DoNotEscape;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ExtensionCustomFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldGroupControl;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldNames;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListFieldClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldData;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Component\Placeholder as ComponentPlaceholder;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Builders;
use VDM\Joomla\Componentbuilder\Compiler\Creator\CustomFieldTypeFile;
use VDM\Joomla\Componentbuilder\Compiler\Creator\EmailHelper;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldAsString;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldDynamic;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldString;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldXML;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldsetDynamic;
use VDM\Joomla\Componentbuilder\Compiler\Creator\FieldsetExtension;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Layout;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Field;
use VDM\Joomla\Componentbuilder\Compiler\Field\Attributes;
use VDM\Joomla\Componentbuilder\Compiler\Field\Groups;
use VDM\Joomla\Componentbuilder\Compiler\Field\ModalSelect;
use VDM\Joomla\Componentbuilder\Compiler\Field\Name;
use VDM\Joomla\Componentbuilder\Compiler\Field\TypeName;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Field\CoreFieldInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Field\InputButtonInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HeaderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Creator\Fieldtypeinterface;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Registry as CompilerRegistry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Xml;


/**
 * Field rendering, aggregation, and supporting-file creator contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(CustomFieldTypeFile::class)]
#[CoversClass(EmailHelper::class)]
#[CoversClass(FieldAsString::class)]
#[CoversClass(FieldDynamic::class)]
#[CoversClass(FieldString::class)]
#[CoversClass(FieldXML::class)]
#[CoversClass(FieldsetDynamic::class)]
#[CoversClass(FieldsetExtension::class)]
#[UsesClass(Config::class)]
#[UsesClass(Component::class)]
#[UsesClass(ComponentPlaceholder::class)]
#[UsesClass(Language::class)]
#[UsesClass(Placeholder::class)]
#[UsesClass(Attributes::class)]
#[UsesClass(Groups::class)]
#[UsesClass(CompilerRegistry::class)]
#[UsesClass(ComponentConfig::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ComponentFields::class)]
#[UsesClass(ContentMulti::class)]
#[UsesClass(ContentOne::class)]
#[UsesClass(DoNotEscape::class)]
#[UsesClass(ExtensionCustomFields::class)]
#[UsesClass(FieldGroupControl::class)]
#[UsesClass(FieldNames::class)]
#[UsesClass(ListFieldClass::class)]
#[UsesClass(SiteFieldData::class)]
#[UsesClass(Counter::class)]
#[UsesClass(Indent::class)]
#[UsesClass(Xml::class)]
final class FieldCreatorTest extends CreatorTestCase
{
	/**
	 * Render stable plain and spacer fields and increment the shared counter.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldStringRendersPlainAndSpacerContracts(): void
	{
		$counter = $this->createStub(Counter::class);
		$subject = $this->fieldString($counter);
		$plainAttributes = [
			'name' => 'title',
			'type' => 'text',
			'label' => 'COM_DEMO_ARTICLE_TITLE',
			'option' => 'ignored'
		];
		$spacerAttributes = [
			'name' => 'intro',
			'type' => 'spacer',
			'label' => 'COM_DEMO_INTRO'
		];
		$options = null;

		$plain = $subject->get(
			'plain', $plainAttributes, 'title', 'text', 'COM_DEMO_ARTICLE',
			'article', 'articles', [], $options, null, "\t"
		);
		$spacer = $subject->get(
			'spacer', $spacerAttributes, 'intro', 'spacer', 'COM_DEMO_ARTICLE',
			'article', 'articles', [], $options
		);

		$this->assertSame(
			PHP_EOL . "\t\t\t<!-- Title Field. Type: Text. (joomla) -->"
				. PHP_EOL . "\t\t\t<field"
				. PHP_EOL . "\t\t\t\tname=\"title\""
				. PHP_EOL . "\t\t\t\ttype=\"text\""
				. PHP_EOL . "\t\t\t\tlabel=\"COM_DEMO_ARTICLE_TITLE\""
				. PHP_EOL . "\t\t\t/>",
			$plain
		);
		$this->assertSame(
			PHP_EOL . "\t\t<!-- Intro Field. Type: Spacer. A None Database Field. (joomla) -->"
				. PHP_EOL . "\t\t<field name=\"intro\" type=\"spacer\" label=\"COM_DEMO_INTRO\" />",
			$spacer
		);
		$this->assertSame(2, $counter->field);
	}

	/**
	 * Render equivalent object fields and preserve every XML attribute.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldXmlRendersPlainAndSpacerObjects(): void
	{
		$counter = $this->createStub(Counter::class);
		$subject = $this->fieldXml($counter);
		$plainAttributes = [
			'name' => 'title',
			'type' => 'text',
			'label' => 'COM_DEMO_ARTICLE_TITLE',
			'option' => 'ignored'
		];
		$spacerAttributes = [
			'name' => 'intro',
			'type' => 'spacer',
			'label' => 'COM_DEMO_INTRO'
		];
		$options = null;

		$plain = $subject->get(
			'plain', $plainAttributes, 'title', 'text', 'COM_DEMO_ARTICLE',
			'article', 'articles', [], $options
		);
		$spacer = $subject->get(
			'spacer', $spacerAttributes, 'intro', 'spacer', 'COM_DEMO_ARTICLE',
			'article', 'articles', [], $options
		);

		$this->assertSame(' Title Field. Type: Text. (joomla)', $plain->comment);
		$this->assertSame('title', (string) $plain->fieldXML['name']);
		$this->assertSame('text', (string) $plain->fieldXML['type']);
		$this->assertSame('COM_DEMO_ARTICLE_TITLE', (string) $plain->fieldXML['label']);
		$this->assertSame('', (string) $plain->fieldXML['option']);
		$this->assertSame(
			' Intro Field. Type: Spacer. A None Database Field. (joomla)',
			$spacer->comment
		);
		$this->assertSame('spacer', (string) $spacer->fieldXML['type']);
		$this->assertSame(2, $counter->field);
	}

	/**
	 * Resolve field metadata, register its name, and dispatch the plain renderer.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldDynamicCoordinatesAttributesRegistryAndRenderer(): void
	{
		$fieldNames = new FieldNames();
		$renderCalls = [];
		$renderer = $this->createStub(Fieldtypeinterface::class);
		$renderer->method('get')->willReturnCallback(
			static function (
				string $setType,
				array &$attributes,
				string $name,
				string $typeName
			) use (&$renderCalls): string
			{
				$renderCalls[] = [$setType, $attributes, $name, $typeName];

				return '<field name="' . $attributes['name'] . '" type="' . $attributes['type'] . '" />';
			}
		);
		$subject = $this->fieldDynamic($renderer, $fieldNames);
		$field = $this->fieldDefinition('title', 'text');
		$view = [];
		$viewType = 1;
		$langView = 'COM_DEMO_ARTICLE';
		$nameSingleCode = 'article';
		$nameListCode = 'articles';
		$placeholders = [];
		$dbkey = 'g';

		$result = $subject->get(
			$field, $view, $viewType, $langView, $nameSingleCode,
			$nameListCode, $placeholders, $dbkey, false
		);

		$this->assertSame('<field name="title" type="text" />', $result);
		$this->assertSame('title', $fieldNames->get('article.title'));
		$this->assertSame('plain', $renderCalls[0][0]);
		$this->assertSame('title', $renderCalls[0][1]['name']);
		$this->assertSame('text', $renderCalls[0][1]['type']);
		$this->assertSame('title', $renderCalls[0][2]);
		$this->assertSame('text', $renderCalls[0][3]);
		$this->assertSame('g', $dbkey);
	}

	/**
	 * Normalize both string and object field backends to compiler XML strings.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldAsStringNormalizesBothRendererBackends(): void
	{
		$stringSubject = new FieldAsString(
			$this->fieldDynamic($this->rendererReturning('<field name="title" />')),
			$this->xml()
		);
		$field = $this->fieldDefinition('title', 'text');
		$view = [];
		$placeholders = [];
		$dbkey = 'g';

		$this->assertSame(
			'<field name="title" />',
			$stringSubject->get(
				$field, $view, 1, 'COM_DEMO_ARTICLE', 'article', 'articles',
				$placeholders, $dbkey
			)
		);

		$object = new \stdClass();
		$object->comment = 'Title field contract';
		$object->fieldXML = new \SimpleXMLElement('<field name="title" type="text"/>');
		$objectSubject = new FieldAsString(
			$this->fieldDynamic($this->rendererReturning($object)),
			$this->xml()
		);

		$result = $objectSubject->get(
			$field, $view, 1, 'COM_DEMO_ARTICLE', 'article', 'articles',
			$placeholders, $dbkey
		);

		$this->assertStringContainsString('<!-- Title field contract -->', $result);
		$this->assertStringContainsString('<field name="title"', $result);
		// Xml::pretty() serialises through ext-tidy when it is loaded and
		// through DOMDocument when it is not, and only tidy puts a space
		// before the self-closing slash -- so the assertion must accept both.
		$this->assertMatchesRegularExpression('/type="text"\s*\/>/', $result);
	}

	/**
	 * Aggregate every valid field while honoring the requested return shape.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldsetDynamicAggregatesStringAndArrayResults(): void
	{
		$renderer = $this->createStub(Fieldtypeinterface::class);
		$renderer->method('get')->willReturnCallback(
			static fn (string $setType, array &$attributes): string =>
				'<field name="' . $attributes['name'] . '" />'
		);
		$subject = new FieldsetDynamic(
			new FieldAsString($this->fieldDynamic($renderer), $this->xml())
		);
		$fields = [
			$this->fieldDefinition('title', 'text'),
			$this->fieldDefinition('alias', 'text')
		];
		$langView = 'COM_DEMO_ARTICLE';
		$nameSingleCode = 'article';
		$nameListCode = 'articles';
		$placeholders = [];
		$dbkey = 'g';

		$this->assertSame(
			'<field name="title" /><field name="alias" />',
			$subject->get(
				$fields, $langView, $nameSingleCode, $nameListCode,
				$placeholders, $dbkey
			)
		);
		$this->assertSame(
			['<field name="title" />', '<field name="alias" />'],
			$subject->get(
				$fields, $langView, $nameSingleCode, $nameListCode,
				$placeholders, $dbkey, false, 2
			)
		);
	}

	/**
	 * Feed extension identity and cached global placeholders into its fieldset.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFieldsetExtensionBuildsWithExtensionIdentity(): void
	{
		$placeholder = new ComponentPlaceholder(
			$this->config(),
			$this->createStub(DatabaseInterface::class)
		);
		$property = new ReflectionProperty($placeholder, 'placeholders');
		$property->setValue($placeholder, ['[[[component]]]' => 'demo']);
		$renderer = $this->createStub(Fieldtypeinterface::class);
		$renderer->method('get')->willReturnCallback(
			static function (
				string $setType,
				array &$attributes,
				string $name,
				string $typeName,
				string $langView,
				string $nameSingleCode,
				string $nameListCode,
				array $placeholders
			): string
			{
				return implode('|', [
					$langView,
					$nameSingleCode,
					$nameListCode,
					$placeholders['[[[component]]]'],
					$attributes['name']
				]);
			}
		);
		$dynamic = new FieldsetDynamic(
			new FieldAsString($this->fieldDynamic($renderer), $this->xml())
		);
		$subject = new FieldsetExtension($placeholder, $dynamic);
		$extension = (object) ['lang_prefix' => 'COM_MODULE_DEMO', 'key' => 'module'];

		$this->assertSame(
			'COM_MODULE_DEMO|module|module|demo|title',
			$subject->get($extension, [$this->fieldDefinition('title', 'text')])
		);
	}

	/**
	 * Build a namespaced standard custom field exactly once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomFieldTypeFileBuildsNamespacedStandardFieldOnce(): void
	{
		$config = $this->config([
			'component_code_name' => 'demo',
			'joomla_version' => 5,
			'lang_prefix' => 'COM_DEMO',
			'lang_target' => 'admin'
		]);
		$content = new ContentOne();
		$content->set('Component', 'Demo');
		$contents = new ContentMulti();
		$componentPlaceholder = new ComponentPlaceholder(
			$config,
			$this->createStub(DatabaseInterface::class)
		);
		(new ReflectionProperty($componentPlaceholder, 'placeholders'))
			->setValue($componentPlaceholder, ['[[[LANG_PREFIX]]]' => 'COM_DEMO']);
		$builds = [];
		$structure = $this->createStub(Structure::class);
		$structure->method('build')->willReturnCallback(
			static function (array $target, string $type, string $name) use (&$builds): bool
			{
				$builds[] = [$target, $type, $name];

				return true;
			}
		);
		$inputButton = $this->createMock(InputButtonInterface::class);
		$inputButton->expects($this->once())
			->method('get')
			->willReturn('<button>Choose</button>');
		$subject = new CustomFieldTypeFile(
			$config,
			$content,
			$contents,
			new SiteFieldData(),
			new Placeholder($config),
			new Language($config),
			$componentPlaceholder,
			$structure,
			$inputButton,
			new FieldGroupControl(),
			new ExtensionCustomFields(),
			$this->createStub(HeaderInterface::class),
			$this->createStub(CoreFieldInterface::class),
			$this->createStub(CMSApplicationInterface::class)
		);
		$data = [
			'type' => 'Acme.MyLookup',
			'code' => 'customer',
			'custom' => [
				'extends' => 'list',
				'php' => [1 => 'return [1 => "One"];']
			]
		];

		$subject->set($data, 'articles', 'article');
		$subject->set($data, 'articles', 'article');

		$this->assertSame([
			[['admin' => 'customfield'], 'fieldlist', 'MyLookup'],
			[['site' => 'customfield'], 'fieldlist', 'MyLookup']
		], $builds);
		$this->assertSame('ACME', $contents->get('customfield_MyLookup|JPREFIX'));
		$this->assertSame('list', $contents->get('customfield_MyLookup|JFORM_extends'));
		$this->assertSame('MyLookup', $contents->get('customfield_MyLookup|type'));
		$this->assertSame(
			'return [1 => "One"];',
			$contents->get('customfield_MyLookup|JFORM_GETOPTIONS_PHP')
		);
		$this->assertSame(
			'<button>Choose</button>',
			$contents->get('customfield_MyLookup|ADD_BUTTON')
		);
	}

	/**
	 * Return no helper code or side effects when the feature is disabled.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmailHelperSkipsDisabledFeature(): void
	{
		$component = $this->component();
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->never())->method('build');
		$subject = new EmailHelper(
			$this->config(['component_code_name' => 'demo']),
			$component,
			$structure,
			new ContentOne(),
			new ContentMulti()
		);

		$this->assertSame('', $subject->get());
	}

	/**
	 * Build and register the Joomla 3 helper with its exact autoload path.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmailHelperBuildsJoomlaThreeRegistration(): void
	{
		$config = $this->config([
			'component_code_name' => 'demo',
			'joomla_version' => 3
		]);
		$component = $this->component(['add_email_helper' => true]);
		$structure = $this->createMock(Structure::class);
		$structure->expects($this->once())
			->method('build')
			->with(['admin' => 'emailer'], 'emailer', 'demo')
			->willReturn(true);
		$contentOne = new ContentOne();
		$contentOne->set('Component', 'Demo');
		$contentMulti = new ContentMulti();
		$subject = new EmailHelper(
			$config,
			$component,
			$structure,
			$contentOne,
			$contentMulti
		);

		$this->assertSame(
			PHP_EOL . "\\JLoader::register('DemoEmail', JPATH_ADMINISTRATOR . '/components/com_demo/helpers/demoemail.php'); ",
			$subject->get()
		);
		$this->assertSame('', $contentMulti->get('emailer_demo|BAKING'));
	}

	/**
	 * Keep Joomla 4+ registration empty while still forcing helper regeneration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmailHelperBuildsModernHelperWithoutLegacyRegistration(): void
	{
		$config = $this->config([
			'component_code_name' => 'demo',
			'joomla_version' => 6
		]);
		$component = $this->component(['add_email_helper' => true]);
		$structure = $this->createStub(Structure::class);
		$structure->method('build')->willReturn(true);
		$contentMulti = new ContentMulti();
		$subject = new EmailHelper(
			$config,
			$component,
			$structure,
			new ContentOne(),
			$contentMulti
		);

		$this->assertSame('', $subject->get());
		$this->assertSame('', $contentMulti->get('emailer_demo|BAKING'));
	}

	/**
	 * Create a string field renderer with inert collaborators outside plain paths.
	 *
	 * @param   Counter  $counter  Shared field counter.
	 *
	 * @return  FieldString
	 * @since   6.1.6
	 */
	private function fieldString(Counter $counter): FieldString
	{
		$config = $this->config();

		return new FieldString(
			$config,
			new Language($config),
			$this->createStub(Field::class),
			new Groups($this->createStub(DatabaseInterface::class)),
			$this->createStub(Name::class),
			new TypeName(),
			$this->inert(Attributes::class),
			$this->inert(ModalSelect::class),
			$this->inert(CustomFieldTypeFile::class),
			$counter,
			new ComponentFields()
		);
	}

	/**
	 * Create an XML field renderer with inert collaborators outside plain paths.
	 *
	 * @param   Counter  $counter  Shared field counter.
	 *
	 * @return  FieldXML
	 * @since   6.1.6
	 */
	private function fieldXml(Counter $counter): FieldXML
	{
		$config = $this->config();

		return new FieldXML(
			$config,
			new Language($config),
			$this->createStub(Field::class),
			new Groups($this->createStub(DatabaseInterface::class)),
			$this->createStub(Name::class),
			new TypeName(),
			$this->inert(Attributes::class),
			$this->inert(ModalSelect::class),
			$this->xml(),
			$this->inert(CustomFieldTypeFile::class),
			$counter,
			new ComponentFields()
		);
	}

	/**
	 * Build a real dynamic-field coordinator around a selectable renderer.
	 *
	 * @param   Fieldtypeinterface  $renderer    Field backend.
	 * @param   FieldNames|null     $fieldNames  Optional registry to inspect.
	 *
	 * @return  FieldDynamic
	 * @since   6.1.6
	 */
	private function fieldDynamic(
		Fieldtypeinterface $renderer,
		?FieldNames $fieldNames = null
	): FieldDynamic
	{
		$config = $this->config([
			'lang_target' => 'admin',
			'default_fields' => []
		]);
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
			new Placeholder($config),
			$this->createStub(Customcode::class),
			new Language($config),
			$groups
		);

		return new FieldDynamic(
			$name,
			$typeName,
			$attributes,
			$this->inert(ModalSelect::class),
			$groups,
			$fieldNames ?? new FieldNames(),
			$renderer,
			$this->inert(Builders::class),
			$this->inert(Layout::class)
		);
	}

	/**
	 * Create a renderer stub returning one selected backend value.
	 *
	 * @param   mixed  $value  Renderer result.
	 *
	 * @return  Fieldtypeinterface
	 * @since   6.1.6
	 */
	private function rendererReturning($value): Fieldtypeinterface
	{
		$renderer = $this->createStub(Fieldtypeinterface::class);
		$renderer->method('get')->willReturn($value);

		return $renderer;
	}

	/**
	 * Create the smallest valid JCB field definition for the real attribute model.
	 *
	 * @param   string  $name  Field name.
	 * @param   string  $type  Field type.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.6
	 */
	private function fieldDefinition(string $name, string $type): array
	{
		return [
			'settings' => (object) [
				'name' => $name,
				'type_name' => $type,
				'xml' => '<field name="' . $name . '" type="' . $type . '" />',
				'properties' => [
					[
						'name' => 'name',
						'example' => $name,
						'translatable' => 0,
						'mandatory' => 1
					],
					[
						'name' => 'type',
						'example' => $type,
						'translatable' => 0,
						'mandatory' => 1
					]
				]
			]
		];
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
	 * Create the real XML utility without booting a Joomla application.
	 *
	 * @return  Xml
	 * @since   6.1.6
	 */
	private function xml(): Xml
	{
		return new Xml(
			$this->config(),
			$this->createStub(CMSApplicationInterface::class)
		);
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
