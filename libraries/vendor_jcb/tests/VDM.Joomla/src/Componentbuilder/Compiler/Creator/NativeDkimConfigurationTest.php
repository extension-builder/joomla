<?php
/**
 * @package    Joomla.Component.Builder.Tests
 * @created    23rd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Creator;


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Language\Language as CmsLanguage;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\CMS\WebAsset\WebAssetRegistry;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ConfigFieldsets;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ConfigFieldsetsCustomfield;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Component\Placeholder as ComponentPlaceholder;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Creator\JoomlaThree\ConfigFieldsetsEmailHelper as J3EmailHelper;
use VDM\Joomla\Componentbuilder\Compiler\Creator\JoomlaFour\ConfigFieldsetsEmailHelper as J4EmailHelper;
use VDM\Joomla\Componentbuilder\Compiler\Creator\JoomlaFive\ConfigFieldsetsEmailHelper as J5EmailHelper;
use VDM\Joomla\Componentbuilder\Compiler\Creator\JoomlaSix\ConfigFieldsetsEmailHelper as J6EmailHelper;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Service\Creator;
use VDM\Joomla\Componentbuilder\Power\Parser;


/**
 * DKIM configuration keeps its form contract while Joomla 6 owns its media asset.
 *
 * @since  6.2.0
 */
#[CoversClass(J3EmailHelper::class)]
#[CoversClass(J4EmailHelper::class)]
#[CoversClass(J5EmailHelper::class)]
#[CoversClass(J6EmailHelper::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class NativeDkimConfigurationTest extends CreatorTestCase
{
	/**
	 * Earlier targets keep the exact baseline field XML and language values.
	 *
	 * @param   int     $major  Target Joomla major.
	 * @param   string  $class  Expected concrete creator.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testProviderSelectsOnlyNativeJoomlaSixOutput(int $major, string $class): void
	{
		[$creator, $fields, $language, $config] = $this->fixture($major);
		$this->assertSame($class, $creator::class);
		$creator->set('COM_DEMO_CONFIG');
		$xml = implode(PHP_EOL, $fields->get('component', []));
		$description = $language->get('admin', 'COM_DEMO_CONFIG_NOTE_DKIM_USE_DESCRIPTION');
		$this->assertSame(3, substr_count($xml, 'onchange="vdm_dkim();"'));
		$this->assertStringContainsString('onchange="vdm_dkim();"', $xml);

		if ($major < 6)
		{
			$content = (new ReflectionProperty(Language::class, 'content'))->getValue($language);
			$this->assertSame(
				'5cbc334347c36e0d97729eb829470c31870148d3664c726e93b08d632cdb1b71',
				hash('sha256', $xml . "\n" . json_encode($content)),
				'Complete XML/language snapshot recorded from merged base faa95cdeecdbdb4504f27d66870310aec1a3ec0f.'
			);
			$this->assertStringContainsString('<script>', $description);
			$this->assertStringContainsString('<field type="note" name="note_dkim_use"', $xml);
		}
		else
		{
			$this->assertStringNotContainsString('<script', $description);
			$this->assertStringContainsString("id='a_dkim_domain'", $description);
			$this->assertStringContainsString('type="Dkim" addfieldprefix="DkimFixture\Component\Demo\Administrator\Field"', $xml);
		}
	}

	/**
	 * Match the namespace used by the authoritative component placeholder producer.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFieldPrefixMatchesNonstandardComponentNamespace(): void
	{
		foreach (['123-demo', 'custom_name', 'class'] as $name)
		{
			[$creator, $fields, , $config] = $this->fixture(6);
			$config->set('component_code_name', $name);
			$placeholders = new ComponentPlaceholder($config, $this->createStub(DatabaseInterface::class));
			(new ReflectionProperty(ComponentPlaceholder::class, 'placeholders'))->setValue($placeholders, []);
			(new ReflectionMethod(ComponentPlaceholder::class, 'addCorePlaceholders'))->invoke($placeholders);
			$namespace = $placeholders->get()['###ComponentNamespace###'];
			$creator->set('COM_DEMO_CONFIG');
			$xml = implode(PHP_EOL, $fields->get('component', []));
			$this->assertStringContainsString('addfieldprefix="DkimFixture\\Component\\' . $namespace . '\Administrator\Field"', $xml);
		}
	}

	/**
	 * Disabled helpers and caller-supplied DKIM fieldsets do not activate the asset.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testOptionalAndCustomFieldsetBranchesRemainAuthoritative(): void
	{
		[$creator, $fields, $language, , $component, $custom] = $this->fixture(6);
		$component->set('add_email_helper', false);
		$creator->set('COM_DEMO_CONFIG');
		$this->assertSame([], $fields->get('component', []));
		$component->set('add_email_helper', true);
		$custom->set('DKIM', ['<field name="own_dkim" type="text" />']);
		$creator->set('COM_DEMO_CONFIG');
		$xml = implode(PHP_EOL, $fields->get('component', []));
		$this->assertStringContainsString('name="own_dkim"', $xml);
		$this->assertStringNotContainsString('name="note_dkim_use"', $xml);
		$this->assertStringNotContainsString('type="Dkim"', $xml);
		$this->assertFalse($custom->exists('DKIM'));
	}

	/**
	 * The generated field resolves through Joomla Form and activates one real WAM item.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testGeneratedNativeNoteLoadsFromFormAndKeepsItsMarkup(): void
	{
		$state = [];
		foreach (['paths', 'prefixes', 'entities'] as $name)
		{
			$state[$name] = (new ReflectionProperty(FormHelper::class, $name))->getValue();
		}
		$root = new ReflectionProperty(Uri::class, 'root');
		$oldRoot = $root->getValue();
		$root->setValue(null, ['prefix' => 'https://example.test', 'path' => '/subsite']);

		try
		{
			[$creator, $fields, $language] = $this->fixture(6);
			$creator->set('COM_DEMO_CONFIG');
			$xml = new \SimpleXMLElement('<form>' . implode(PHP_EOL, $fields->get('component')) . '</form>');
			$note = $xml->xpath('//field[@name="note_dkim_use"]')[0];
			$note['label'] = 'Server Configuration';
			$note['description'] = $language->get('admin', 'COM_DEMO_CONFIG_NOTE_DKIM_USE_DESCRIPTION');
			$assets = new WebAssetManager(new WebAssetRegistry());
			$application = $this->createStub(CMSApplication::class);
			$application->method('getDocument')->willReturn(new HtmlDocument(['webAssetManager' => $assets]));
			$application->method('getLanguage')->willReturn(new CmsLanguage('en-GB'));
			$application->method('getIdentity')->willReturn(new User());
			$this->setJoomlaApplication($application);
			$this->setJoomlaFactoryProperty('language', new CmsLanguage('en-GB'));
			$container = new Container();
			$container->set(DatabaseInterface::class, $this->createStub(DatabaseInterface::class));
			$this->setJoomlaContainer($container);
			$this->loadFieldTemplate();
			$form = new Form('native-dkim', ['control' => 'jform']);
			$this->assertTrue($form->load($xml));
			$field = $form->getField('note_dkim_use');
			$this->assertInstanceOf('DkimFixture\Component\Demo\Administrator\Field\DkimField', $field);
			$native = new NoteField($form);
			$native->setup($note, null);
			$label = new ReflectionMethod(NoteField::class, 'getLabel');
			$generated = new ReflectionMethod($field, 'getLabel');
			$this->assertSame($label->invoke($native), $generated->invoke($field));
			$this->assertStringContainsString("id='a_dkim_domain'", $generated->invoke($field));
			$this->assertCount(1, $assets->getAssets('script', true));
			$script = $assets->getAsset('script', 'com_demo.dkim');
			$this->assertSame('https://example.test/subsite/media/com_demo/js/admin/dkim.js', $script->getUri());
			$this->assertSame('auto', $script->getVersion());
			$this->assertTrue($script->getAttribute('defer'));
			$this->assertSame([], $script->getDependencies());
		}
		finally
		{
			$root->setValue(null, $oldRoot);
			foreach ($state as $name => $value)
			{
				(new ReflectionProperty(FormHelper::class, $name))->setValue(null, $value);
			}
		}
	}

	/**
	 * Compile the source template through the real Joomla Power import pipeline.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	private function loadFieldTemplate(): void
	{
		$source = file_get_contents(JCB_PROJECT_ROOT . '/admin/compiler/joomla_6/ADMIN_DKIM_FIELD.php');
		$source = '<?php' . substr($source, strpos($source, '###BOM###') + strlen('###BOM###'));
		$source = strtr($source, ['###NAMESPACEPREFIX###' => 'DkimFixture', '###ComponentNamespace###' => 'Demo', '###component###' => 'demo']);
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['39403062-84fb-46e0-bac4-0023f766e827', 0, (object) ['_namespace' => 'Joomla\CMS', 'class_name' => 'Factory', 'type' => 'class']],
			['eecc143e-b5cf-4c33-ba4d-97da1df61422', 0, (object) ['_namespace' => 'Joomla\CMS\Uri', 'class_name' => 'Uri', 'type' => 'class']],
		]);
		$source = (new Injector($power, new Extractor($this->createStub(DatabaseInterface::class), 6), new Parser(), new Placeholder($this->config(6))))->power($source);
		$this->assertStringNotContainsString('Joomla___', $source);
		eval(substr($source, 5));
	}

	/**
	 * Make the real selected creator and isolated build state.
	 *
	 * @param   int  $major  Target Joomla major.
	 *
	 * @return  array
	 * @since   6.2.0
	 */
	private function fixture(int $major): array
	{
		$config = $this->config($major);
		$language = new Language($config);
		$component = new Component((new ReflectionClass(Data::class))->newInstanceWithoutConstructor(), $this->createStub(EventInterface::class));
		$component->set('add_email_helper', true);
		$fields = new ConfigFieldsets();
		$custom = new ConfigFieldsetsCustomfield();
		$container = new Container();
		foreach (['Config' => $config, 'Language' => $language, 'Component' => $component, 'Compiler.Builder.Config.Fieldsets' => $fields, 'Compiler.Builder.Config.Fieldsets.Customfield' => $custom] as $name => $value)
		{
			$container->share($name, $value, true);
		}
		(new Creator())->register($container);
		$creator = $container->get('Compiler.Creator.Config.Fieldsets.Email.Helper');
		$this->assertSame($creator, $container->get('Compiler.Creator.Config.Fieldsets.J' . $major . '.Email.Helper'));

		return [$creator, $fields, $language, $config, $component, $custom];
	}

	/**
	 * Create deterministic compiler configuration.
	 *
	 * @param   int  $major  Target Joomla major.
	 *
	 * @return  Config
	 * @since   6.2.0
	 */
	private function config(int $major): Config
	{
		$config = new Config(new Input(), new Registry(), new Registry());
		foreach (['lang_target' => 'admin', 'lang_prefix' => 'COM_DEMO', 'component_code_name' => 'demo', 'namespace_prefix' => 'DkimFixture', 'component_autoloader_path' => '', 'joomla_version' => $major] as $key => $value)
		{
			$config->set($key, $value);
		}

		return $config;
	}

	/**
	 * Supported target versions and their distinct identities.
	 *
	 * @return  array
	 * @since   6.2.0
	 */
	public static function targets(): array
	{
		return [[3, J3EmailHelper::class], [4, J4EmailHelper::class], [5, J5EmailHelper::class], [6, J6EmailHelper::class]];
	}
}
