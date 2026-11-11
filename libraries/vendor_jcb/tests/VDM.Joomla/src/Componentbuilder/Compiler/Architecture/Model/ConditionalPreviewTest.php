<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    11th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Model;


use DOMDocument;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Input\Input;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\ConditionalRule;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Model\ValidationFix;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;
use VDM\Tests\Support\ConditionalPreviewCompiler;


/**
 * Protect coherent installed previews and their real Joomla validation contract.
 *
 * These tests compile normalized fixtures, not a complete JCB component. Record
 * persistence and database-backed field options remain explicit test boundaries.
 *
 * @since  6.2.1
 */
#[CoversClass(ValidationFix::class)]
#[CoversClass(ConditionalRule::class)]
final class ConditionalPreviewTest extends ArchitectureTestCase
{
	/**
	 * Form-rule prefixes and plugin catalog restored after each native fixture.
	 *
	 * @var    array
	 * @since  6.2.1
	 */
	private array $globals = [];

	/**
	 * Set up native forms without starting an installed application or database.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	protected function setUp(): void
	{
		parent::setUp();
		Factory::$language = new Language('en-GB');
		foreach ([[PluginHelper::class, 'plugins'], [FormHelper::class, 'prefixes']] as [$class, $name])
		{
			$property = new ReflectionProperty($class, $name);
			$this->globals[] = [$property, $property->getValue()];
		}
		(new ReflectionProperty(PluginHelper::class, 'plugins'))->setValue(null, []);
		FormHelper::addRulePrefix('VDM\\Component\\Componentbuilder\\Administrator\\Rule');
		$application = $this->createStub(CMSApplication::class);
		$application->method('isClient')->willReturn(false);
		$input = new Input([]);
		$input->server->set('REQUEST_METHOD', 'POST');
		$application->method('getInput')->willReturn($input);
		Factory::$application = $application;
	}

	/**
	 * Restore process-wide form and plugin state.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	protected function tearDown(): void
	{
		foreach ($this->globals as [$property, $value])
		{
			$property->setValue(null, $value);
		}
		parent::tearDown();
	}

	/**
	 * All models sharing the runtime rule reproduce the reviewed source exactly.
	 *
	 * @param   string  $view     The normalized model view name.
	 * @param   array   $valid    A valid conditional input scenario.
	 * @param   array   $invalid  A scenario omitting an active required target.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('models')]
	public function testEveryModelMatchesItsFixtureAndUsesTheNativeLifecycle(string $view, array $valid, array $invalid): void
	{
		$fixture = $this->fixture($view);
		$compiler = new ConditionalPreviewCompiler();
		$fragments = $compiler->fragments($fixture['groups'], $view, 'Componentbuilder');
		$class = ucfirst($view) . 'Model';
		$source = file_get_contents(JCB_PROJECT_ROOT . '/admin/src/Model/' . $class . '.php');
		$this->assertSame($source, $compiler->model($source, $class, $fragments));
		$rule = file_get_contents(JCB_PROJECT_ROOT . '/admin/src/Rule/ConditionalrequiredRule.php');
		$this->assertSame($rule, $compiler->rule($rule, 'ConditionalrequiredRule', $fragments['rule']));
		$this->assertStringNotContainsString('conditionCallback', $source);
		$this->assertStringNotContainsString('::attach(', $source);
		$model = $this->model($fragments);
		$form = $this->conditionForm($fixture['groups']);

		$this->assertIsArray($model->validate($form, $valid + ['ordinary' => 'Required value']));
		$this->assertFalse($model->validate($form, $invalid + ['ordinary' => 'Required value']));
		$this->assertFalse($model->validate($form, $valid + ['ordinary' => '']));
		$this->assertFalse($form->getFieldXml('__conditional_required'));
		foreach ($fixture['groups'] as $group)
		{
			foreach ($group['targets'] as $target)
			{
				$this->assertSame('true', $form->getFieldAttribute($target, 'required'));
			}
		}
	}

	/**
	 * The added declarative Field rule fixes Note and Spacer without weakening text fields.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testFieldDefinitionsCoverNoteSpacerAndOrdinaryStorageRequirements(): void
	{
		$fixture = $this->fixture('field');
		$base = [
			'name' => 'Validation preview',
			'fieldtype' => $fixture['fieldtypeCatalog']['note'],
			'datatype' => '',
			'datalenght' => '',
			'datalenght_other' => '',
			'datadefault' => '',
			'datadefault_other' => '',
			'indexes' => '',
			'null_switch' => '',
			'store' => '',
			'on_get_model_field' => '',
			'on_save_model_field' => '',
		];
		$text = '201327fe-3067-4316-a155-3fe2a52e05c0';
		$cases = [
			'note without database settings' => [$base, false, true],
			'spacer without database settings' => [array_replace($base, ['fieldtype' => $fixture['fieldtypeCatalog']['spacer']]), false, true],
			'note with stale Other and expert selectors' => [array_replace($base, ['datatype' => 'VARCHAR', 'datalenght' => 'Other', 'datadefault' => 'Other', 'store' => '6']), false, true],
			'text without datatype remains invalid' => [array_replace($base, ['fieldtype' => $text, 'null_switch' => 'NULL', 'store' => '0']), false, false],
			'ordinary valid text' => [array_replace($base, ['fieldtype' => $text, 'datatype' => 'VARCHAR', 'datalenght' => '50', 'null_switch' => 'NULL', 'store' => '0', 'indexes' => '0']), true, true],
			'text still requires Other value' => [array_replace($base, ['fieldtype' => $text, 'datatype' => 'VARCHAR', 'datalenght' => 'Other', 'null_switch' => 'NULL', 'store' => '0', 'indexes' => '0']), false, false],
			'note still requires ordinary name' => [array_replace($base, ['name' => '']), false, false],
		];
		$compiler = new ConditionalPreviewCompiler();
		foreach ([array_slice($fixture['groups'], 0, 5), $fixture['groups']] as $variant => $groups)
		{
			$model = $this->model($compiler->fragments($groups, 'field', 'Componentbuilder'));
			foreach ($cases as $label => [$data, $five, $six])
			{
				$form = $this->fieldForm(array_keys($base), $fixture['fieldtypeCatalog'] + ['text' => $text]);
				$result = $model->validate($form, $data);
				$this->assertSame($variant === 0 ? $five : $six, is_array($result), $label);
				foreach ($fixture['groups'][5]['targets'] as $target)
				{
					$this->assertSame('true', $form->getFieldAttribute($target, 'required'), $target . ' restored');
				}
				$this->assertFalse($form->getFieldXml('__conditional_required'));
				if (is_array($result))
				{
					$this->assertArrayNotHasKey('__conditional_required', $result);
					$this->assertSame($data['datatype'], $result['datatype']);
					$this->assertSame($data['datalenght'], $result['datalenght']);
				}
			}
		}
	}

	/**
	 * Token-based regeneration ignores braces and nested methods in unrelated code.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testRegenerationPreservesUnrelatedMembersAndIsIdempotent(): void
	{
		$source = <<<'PHP_SOURCE'
<?php
class PreviewModel
{
	/** The existing alias. */
	public $typeAlias = 'com_example.preview';

	/** Existing validation. */
	public function validate($form, $data, $group = null)
	{
		return false;
	}

	/** Preserve this custom method, including nested syntax. */
	public function untouched()
	{
		$literal = 'do not parse }; { as code';
		return new class
		{
			public function validate()
			{
				return '}';
			}
		};
	}
}
PHP_SOURCE;
		$compiler = new ConditionalPreviewCompiler();
		$fixture = $this->fixture('admin_view');
		$fragments = $compiler->fragments($fixture['groups'], 'preview', 'Example');
		$generated = $compiler->model($source, 'PreviewModel', $fragments);
		$untouched = substr($source, strpos($source, "\t/** Preserve this custom method"));

		$this->assertStringEndsWith($untouched, $generated);
		$this->assertSame($generated, $compiler->model($generated, 'PreviewModel', $fragments));
	}

	/**
	 * Explicit native scenarios for every installed model sharing the rule.
	 *
	 * @return  array<string, array{string,array,array}>
	 * @since   6.2.1
	 */
	public static function models(): array
	{
		return [
			'admin view' => ['admin_view', ['add_sql' => '0'], ['add_sql' => '1']],
			'custom code' => ['custom_code', ['target' => '2', 'function_name' => 'example'], ['target' => '2']],
			'dynamic get' => ['dynamic_get', ['gettype' => '1', 'main_source' => '1', 'view_selection' => 'selection', 'add_php_after_getitem' => '0', 'add_php_before_getitem' => '0', 'add_php_router_parse' => '0'], ['gettype' => '1', 'main_source' => '1', 'add_php_after_getitem' => '0', 'add_php_before_getitem' => '0', 'add_php_router_parse' => '0']],
			'field' => ['field', ['fieldtype' => 'f9ecacd0-8481-4157-8c71-d7aaefc2b7c3'], ['fieldtype' => '201327fe-3067-4316-a155-3fe2a52e05c0', 'null_switch' => 'NULL', 'store' => '0']],
			'fieldtype' => ['fieldtype', ['has_defaults' => '0', 'datatype' => 'VARCHAR', 'datalenght' => 'Other', 'datadefault' => 'Other'], ['has_defaults' => '1', 'null_switch' => 'NULL']],
			'help document' => ['help_document', ['location' => '1', 'admin_view' => 'view', 'type' => '2', 'target' => '0'], ['location' => '1', 'admin_view' => 'view', 'type' => '3', 'target' => '0']],
			'component' => ['joomla_component', ['add_update_server' => '0', 'add_changelog_server' => '0', 'buildcomp' => '0'], ['add_update_server' => '1', 'add_changelog_server' => '0', 'buildcomp' => '0']],
			'library' => ['library', ['target' => '2', 'how' => '3'], ['target' => '1', 'how' => '3', 'type' => '1']],
			'server' => ['server', ['protocol' => '1', 'signature' => 'signature'], ['protocol' => '2', 'authentication' => '1', 'port' => '22', 'path' => '/example', 'username' => 'user', 'password' => 'password']],
		];
	}

	/**
	 * Load the normalized definition input, including its provenance.
	 *
	 * @param   string  $view  The view whose preview is tested.
	 *
	 * @return  array
	 * @since   6.2.1
	 */
	private function fixture(string $view): array
	{
		return json_decode(
			file_get_contents(JCB_TESTS_ROOT . '/Fixtures/Compiler/ConditionalValidation/' . $view . '-input.json'),
			true,
			512,
			JSON_THROW_ON_ERROR
		);
	}

	/**
	 * Compose exact model fragments with Joomla's native FormModel fixture.
	 *
	 * @param   array  $fragments  Production-generated property and methods.
	 *
	 * @return  GeneratedConditionalModelFixture
	 * @since   6.2.1
	 */
	private function model(array $fragments): GeneratedConditionalModelFixture
	{
		return eval('use Joomla\\CMS\\Form\\FormHelper; use Joomla\\CMS\\Factory; use Joomla\\Registry\\Registry; '
			. 'return new class([], new \\Joomla\\Event\\Dispatcher()) extends \\' . GeneratedConditionalModelFixture::class . ' {'
			. $fragments['properties'] . $fragments['methods'] . '};');
	}

	/**
	 * Build native fields for condition-only tests without unrelated view dependencies.
	 *
	 * @param   array  $groups  The model's normalized condition definitions.
	 *
	 * @return  Form
	 * @since   6.2.1
	 */
	private function conditionForm(array $groups): Form
	{
		$fields = ['ordinary' => true];
		foreach ($groups as $group)
		{
			foreach ($group['matches'] as $match)
			{
				$fields[$match['name']] ??= false;
			}
			foreach ($group['targets'] as $target)
			{
				$fields[$target] = true;
			}
		}
		$xml = new \SimpleXMLElement('<form />');
		foreach ($fields as $name => $required)
		{
			$field = $xml->addChild('field');
			$field->addAttribute('name', $name);
			$field->addAttribute('type', 'text');
			$field->addAttribute('filter', 'raw');
			$field->addAttribute('required', $required ? 'true' : 'false');
		}
		$form = $this->nativeForm();
		$this->assertTrue($form->load($xml));
		return $form;
	}

	/**
	 * Use actual Field XML while replacing only its database-backed option catalog.
	 *
	 * @param   array  $names  Actual form fields involved in the regression.
	 * @param   array  $types  Catalog GUIDs and labels at the database boundary.
	 *
	 * @return  Form
	 * @since   6.2.1
	 */
	private function fieldForm(array $names, array $types): Form
	{
		$source = simplexml_load_file(JCB_PROJECT_ROOT . '/admin/forms/field.xml');
		$document = new DOMDocument();
		$document->loadXML('<form><fieldset name="details" /></form>');
		$fieldset = $document->documentElement->firstChild;
		foreach ($names as $name)
		{
			$matches = $source->xpath('//field[@name="' . $name . '"]');
			$this->assertCount(1, $matches);
			$field = $document->importNode(dom_import_simplexml($matches[0]), true);
			if ($name === 'fieldtype')
			{
				$field->setAttribute('type', 'list');
				foreach ($types as $label => $guid)
				{
					$option = $document->createElement('option', $label);
					$option->setAttribute('value', $guid);
					$field->appendChild($option);
				}
			}
			$fieldset->appendChild($field);
		}
		$form = $this->nativeForm();
		$this->assertTrue($form->load($document->saveXML()));
		return $form;
	}

	/**
	 * Inject Joomla's external database and current-user boundaries.
	 *
	 * @return  Form
	 * @since   6.2.1
	 */
	private function nativeForm(): Form
	{
		$form = new Form('conditional.preview');
		$form->setDatabase($this->createStub(DatabaseInterface::class));
		$form->setCurrentUser(new User());
		return $form;
	}
}
