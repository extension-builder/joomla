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


use Joomla\Input\Input;
use Joomla\Registry\Registry as JoomlaRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use ReflectionClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\FunctionRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Abstraction\Registry\Traits\IsArray;
use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ConfigFieldsets as ConfigFieldsetsBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ConfigFieldsetsCustomfield as Customfield;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Contributors;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ExtensionsParams;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldGroupControl;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FrontendParams;
use VDM\Joomla\Componentbuilder\Compiler\Builder\HasMenuGlobal;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Request as RequestBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsCustomfield;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsEmailHelper;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsEncryption;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsGlobal;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsGooglechart;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsGroupControl;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsSiteControl;
use VDM\Joomla\Componentbuilder\Compiler\Creator\ConfigFieldsetsUikit;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Request;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\GetHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Generated configuration fieldset contracts for focused leaf creators.
 *
 * @since  6.1.6
 */
#[CoversClass(ConfigFieldsetsCustomfield::class)]
#[CoversClass(ConfigFieldsetsEmailHelper::class)]
#[CoversClass(ConfigFieldsetsEncryption::class)]
#[CoversClass(ConfigFieldsetsGlobal::class)]
#[CoversClass(ConfigFieldsetsGooglechart::class)]
#[CoversClass(ConfigFieldsetsGroupControl::class)]
#[CoversClass(ConfigFieldsetsSiteControl::class)]
#[CoversClass(ConfigFieldsetsUikit::class)]
#[UsesClass(Config::class)]
#[UsesClass(Component::class)]
#[UsesClass(Language::class)]
#[UsesClass(ComponentConfig::class)]
#[UsesClass(FunctionRegistry::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(ConfigFieldsetsBuilder::class)]
#[UsesClass(Customfield::class)]
#[UsesClass(Contributors::class)]
#[UsesClass(ExtensionsParams::class)]
#[UsesClass(FieldGroupControl::class)]
#[UsesClass(FrontendParams::class)]
#[UsesClass(HasMenuGlobal::class)]
#[UsesClass(RequestBuilder::class)]
#[UsesClass(Request::class)]
#[UsesClass(Indent::class)]
#[UsesClass(Line::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(GetHelper::class)]
#[UsesClass(StringHelper::class)]
#[UsesTrait(IsArray::class)]
final class ConfigFieldsetLeafTest extends CreatorTestCase
{
	/**
	 * Filter display-targeted custom fields and consume the source tab.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomfieldCreatorBuildsOnlyConfigVisibleFields(): void
	{
		$config = $this->config();
		$language = new Language($config);
		$custom = new Customfield();
		$custom->set('Advanced Options', [
			'<field name="alpha" display="config" />',
			'<field name="beta" />',
			'<field name="gamma" display="site" />'
		]);
		$fieldsets = new ConfigFieldsetsBuilder();

		(new ConfigFieldsetsCustomfield($config, $language, $custom, $fieldsets))
			->set('COM_DEMO_CONFIG');

		$content = $this->content($fieldsets);
		$this->assertStringContainsString('name="advanced_options_custom_config"', $content);
		$this->assertStringContainsString('<field name="alpha"  />', $content);
		$this->assertStringContainsString('<field name="beta" />', $content);
		$this->assertStringNotContainsString('name="gamma"', $content);
		$this->assertSame(
			'Advanced Options',
			$language->get('admin', 'COM_DEMO_CONFIG_ADVANCED_OPTIONS')
		);
		$this->assertFalse($custom->exists('Advanced Options'));
	}

	/**
	 * Build group selectors and matching installation defaults.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGroupControlBuildsSelectorsAndDefaults(): void
	{
		$config = $this->config();
		$groups = new FieldGroupControl();
		$groups->set('reviewers', 'COM_DEMO_REVIEWERS');
		$fieldsets = new ConfigFieldsetsBuilder();
		$params = new ExtensionsParams();

		(new ConfigFieldsetsGroupControl(
			$config,
			new Language($config),
			$groups,
			$fieldsets,
			$params,
			new Customfield()
		))->set('COM_DEMO_CONFIG');

		$content = $this->content($fieldsets);
		$this->assertStringContainsString('name="group_config"', $content);
		$this->assertStringContainsString('<field name="reviewers"', $content);
		$this->assertStringContainsString('label="COM_DEMO_REVIEWERS"', $content);
		$this->assertSame(['"reviewers":["2"]'], $params->get('component'));
	}

	/**
	 * Generate the combined UIkit mode selector and reviewed defaults.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUikitBuildsCombinedVersionFieldsAndDefaults(): void
	{
		$config = $this->config(['uikit' => 2]);
		$language = new Language($config);
		$fieldsets = new ConfigFieldsetsBuilder();
		$params = new ExtensionsParams();

		(new ConfigFieldsetsUikit(
			$config,
			$language,
			$fieldsets,
			$params,
			new Customfield()
		))->set('COM_DEMO_CONFIG');

		$content = $this->content($fieldsets);
		$this->assertStringContainsString('name="uikit_config"', $content);
		$this->assertStringContainsString('<field name="add_jquery_framework"', $content);
		$this->assertStringContainsString('<field name="uikit_version"', $content);
		$this->assertStringContainsString('<field name="uikit_min"', $content);
		$this->assertSame(
			'Uikit2 and Uikit3 Settings',
			$language->get('admin', 'COM_DEMO_CONFIG_UIKIT_LABEL')
		);
		$this->assertContains('"add_jquery_framework":"1"', $params->get('component'));
		$this->assertContains('"uikit_version":"2"', $params->get('component'));
	}

	/**
	 * Keep generated UIkit option elements free of stray text quotes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testUikitBuildsWellFormedOptionElements(): void
	{
		$config = $this->config(['uikit' => 2]);
		$fieldsets = new ConfigFieldsetsBuilder();

		(new ConfigFieldsetsUikit(
			$config,
			new Language($config),
			$fieldsets,
			new ExtensionsParams(),
			new Customfield()
		))->set('COM_DEMO_CONFIG');

		$this->assertStringNotContainsString('</option>"', $this->content($fieldsets));
	}

	/**
	 * Build chart configuration fields, custom extension, and defaults.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGooglechartBuildsAdminAndSiteSettings(): void
	{
		$config = $this->config(['google_chart' => true]);
		$fieldsets = new ConfigFieldsetsBuilder();
		$params = new ExtensionsParams();
		$custom = new Customfield();
		$custom->set('Chart Settings', ['<field name="chart_custom" />']);

		(new ConfigFieldsetsGooglechart(
			$config,
			new Language($config),
			$fieldsets,
			$custom,
			$params
		))->set('COM_DEMO_CONFIG');

		$content = $this->content($fieldsets);
		$this->assertStringContainsString('name="googlechart_config"', $content);
		$this->assertStringContainsString('name="admin_chartbackground"', $content);
		$this->assertStringContainsString('name="site_chartbackground"', $content);
		$this->assertStringContainsString('name="chart_custom"', $content);
		$this->assertStringContainsString('"admin_chartbackground":"#F7F7FA"', implode('', $params->get('component')));
		$this->assertFalse($custom->exists('Chart Settings'));
	}

	/**
	 * Build basic-encryption settings and language when encryption is active.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEncryptionBuildsBasicKeyFieldset(): void
	{
		$config = $this->config([
			'basic_encryption' => true,
			'whmcs_encryption' => false,
			'medium_encryption' => false
		]);
		$component = $this->component();
		$component->set('add_license', false);
		$fieldsets = new ConfigFieldsetsBuilder();
		$language = new Language($config);

		(new ConfigFieldsetsEncryption(
			$config,
			$language,
			$component,
			$fieldsets,
			new Customfield()
		))->set('COM_DEMO_CONFIG');

		$content = $this->content($fieldsets);
		$this->assertStringContainsString('name="encryption_config"', $content);
		$this->assertStringContainsString('<field name="basic_key"', $content);
		$this->assertStringNotContainsString('<field name="medium_key"', $content);
		$this->assertSame(
			'Encryption Settings',
			$language->get('admin', 'COM_DEMO_CONFIG_ENCRYPTION_LABEL')
		);
	}

	/**
	 * Build mail configuration defaults only when the component enables the helper.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmailHelperBuildsMailConfiguration(): void
	{
		$config = $this->config();
		$component = $this->component();
		$component->set('add_email_helper', true);
		$fieldsets = new ConfigFieldsetsBuilder();

		(new ConfigFieldsetsEmailHelper(
			$config,
			new Language($config),
			$component,
			$fieldsets,
			new Customfield()
		))->set('COM_DEMO_CONFIG');

		$content = $this->content($fieldsets);
		$this->assertStringContainsString('name="mail_configuration_custom_config"', $content);
		$this->assertStringContainsString('name="mailonline"', $content);
		$this->assertStringContainsString('name="mailer"', $content);
		$this->assertStringContainsString('name="dkim_domain"', $content);
	}

	/**
	 * Build Joomla 5 namespace prefixes and immutable author metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGlobalBuildsNamespaceAndAuthorFields(): void
	{
		$config = $this->config([
			'joomla_version' => 5,
			'namespace_prefix' => 'Acme',
			'component_code_name' => 'demo',
			'add_checkin' => false,
			'set_tag_history' => false,
			'add_contributors' => false
		]);
		$fieldsets = new ConfigFieldsetsBuilder();

		(new ConfigFieldsetsGlobal(
			$config,
			new Language($config),
			$this->component(),
			new Contributors(),
			$fieldsets,
			new ExtensionsParams(),
			new Customfield()
		))->set('COM_DEMO_CONFIG', 'Jane &amp; John', 'team@example.test');

		$content = $this->content($fieldsets);
		$this->assertStringContainsString('addruleprefix="Acme\Component\Demo\Administrator\Rule"', $content);
		$this->assertStringContainsString('name="global_config"', $content);
		$this->assertStringContainsString('name="autorName"', $content);
		$this->assertStringContainsString('default="Jane &amp; John"', $content);
		$this->assertStringContainsString('default="team@example.test"', $content);
	}

	/**
	 * Extract routing controls and retain only page settings for a site view.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSiteControlSeparatesRequestsMenusAndFrontendParams(): void
	{
		$component = $this->component();
		$component->set('site_views', [[
			'settings' => (object) ['name' => 'Catalog']
		]]);
		$custom = new Customfield();
		$custom->set('Catalog', [
			'<field name="catalog_request_id" type="number" />',
			'<field name="catalog_menu" type="menu" />',
			'<field name="limit" type="number" />'
		]);
		$menus = new HasMenuGlobal();
		$frontend = new FrontendParams();
		$requestBuilder = new RequestBuilder();

		(new ConfigFieldsetsSiteControl(
			$component,
			new ConfigFieldsetsBuilder(),
			$custom,
			$menus,
			$frontend,
			new Request($requestBuilder)
		))->set('COM_DEMO_CONFIG');

		$this->assertSame(
			'<field name="id" type="number" />',
			$requestBuilder->get('id.catalog.id')
		);
		$this->assertSame('catalog_menu', $menus->get('catalog'));
		$this->assertSame([
			2 => '<field name="limit" type="number" />'
		], $frontend->get('Catalog'));
		$this->assertFalse($custom->exists('Catalog.0'));
	}

	/**
	 * Join generated fieldset fragments in their insertion order.
	 *
	 * @param   ConfigFieldsetsBuilder  $fieldsets  Fieldset builder.
	 *
	 * @return  string
	 * @since   6.1.6
	 */
	private function content(ConfigFieldsetsBuilder $fieldsets): string
	{
		return implode(PHP_EOL, $fieldsets->get('component', []));
	}

	/**
	 * Create an isolated compiler config with common component defaults.
	 *
	 * @param   array<string, mixed>  $values  Overrides.
	 *
	 * @return  Config
	 * @since   6.1.6
	 */
	private function config(array $values = []): Config
	{
		$config = new Config(new Input(), new JoomlaRegistry(), new JoomlaRegistry());
		$values = array_merge([
			'lang_target' => 'admin',
			'lang_prefix' => 'COM_DEMO'
		], $values);

		foreach ($values as $key => $value)
		{
			$config->set($key, $value);
		}

		return $config;
	}

	/**
	 * Create an empty component registry without loading database-backed data.
	 *
	 * @return  Component
	 * @since   6.1.6
	 */
	private function component(): Component
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$event = $this->createStub(EventInterface::class);

		return new Component($data, $event);
	}
}
