<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    10th November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Dispatcher;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use VDM\Component\Componentbuilder\Administrator\Model\FieldModel;
use VDM\Component\Componentbuilder\Administrator\Rule\ConditionalrequiredRule;
use VDM\Tests\Support\JoomlaTestCase;


require_once dirname(__DIR__, 4) . '/admin/src/Model/FieldModel.php';
require_once dirname(__DIR__, 4) . '/admin/src/Rule/ConditionalrequiredRule.php';


/**
 * Exercise the real field editor's native validation with isolated persistence.
 *
 * Administrator code is outside the vendor source coverage inventory. The form
 * fields and conditional rule come from the component, not a copied evaluator.
 *
 * @since  6.2.1
 */
#[CoversNothing]
final class FieldValidationTest extends JoomlaTestCase
{
	/**
	 * Note identifier declared by the installed catalogue and Field conditions.
	 *
	 * @var    string
	 * @since  6.2.1
	 */
	private const NOTE = 'f9ecacd0-8481-4157-8c71-d7aaefc2b7c3';

	/**
	 * Identifier for a second display-only field type.
	 *
	 * @var    string
	 * @since  6.2.1
	 */
	private const SPACER = '626d0cba-a908-4a4f-a447-96d781aeaa0b';

	/**
	 * Identifier for a field type that needs a database column.
	 *
	 * @var    string
	 * @since  6.2.1
	 */
	private const TEXT = '201327fe-3067-4316-a155-3fe2a52e05c0';

	/**
	 * Process-static form and plugin state restored after each test.
	 *
	 * @var    array<array{ReflectionProperty, mixed}>
	 * @since  6.2.1
	 */
	private array $globals = [];


	/**
	 * Register the actual component rule without booting installed plugins.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->setJoomlaFactoryProperty('language', new Language('en-GB'));
		foreach ([
			[PluginHelper::class, 'plugins'],
			[FormHelper::class, 'prefixes'],
			[FormHelper::class, 'entities'],
			[FormHelper::class, 'paths'],
			[ConditionalrequiredRule::class, 'validations'],
		] as [$class, $name])
		{
			$property = new ReflectionProperty($class, $name);
			$this->globals[] = [$property, $property->getValue()];
		}
		(new ReflectionProperty(PluginHelper::class, 'plugins'))->setValue(null, []);
		(new ReflectionProperty(ConditionalrequiredRule::class, 'validations'))->setValue(null, null);
		FormHelper::addRulePrefix('VDM\\Component\\Componentbuilder\\Administrator\\Rule');
	}

	/**
	 * Restore static registries for the suite's randomized execution order.
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
	 * New and existing notes/spacers save without browser exemption hints.
	 *
	 * @param   string  $type      Selected field type identifier.
	 * @param   bool    $existing  Whether a stored field is being edited.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('displayTypes')]
	public function testDisplayTypesNeedNoDatabaseColumn(string $type, bool $existing): void
	{
		$form = $this->form();
		$before = $form->getXml()->asXML();
		$model = $this->model($existing ? ['id' => 42, 'fieldtype' => $type] : []);
		$result = $model->validate($form, ['id' => $existing ? 42 : 0, 'name' => 'Instructions', 'fieldtype' => $type]);

		$this->assertIsArray($result);
		$this->assertSame('Instructions', $result['name']);
		$this->assertSame((string) $type, (string) $result['fieldtype']);
		$this->assertArrayNotHasKey('datatype', $result);
		$this->assertArrayNotHasKey('__conditional_required', $result);
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Cover each authored display-only identity on new records and record edits.
	 *
	 * @return  array<string, array{string, bool}>
	 * @since   6.2.1
	 */
	public static function displayTypes(): array
	{
		return [
			'new note' => [self::NOTE, false],
			'existing note' => [self::NOTE, true],
			'new spacer' => [self::SPACER, false],
			'existing spacer' => [self::SPACER, true],
		];
	}

	/**
	 * Browser-cleared empty controls are accepted just like omitted column fields.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testNoteAcceptsExplicitlyEmptyDatabaseControls(): void
	{
		$form = $this->form();
		$before = $form->getXml()->asXML();
		$model = $this->model();
		$result = $model->validate($form, [
			'name' => 'Instructions', 'fieldtype' => self::NOTE, 'datatype' => '',
			'null_switch' => '', 'store' => '', 'indexes' => '', 'datalenght' => '',
			'datalenght_other' => '', 'datadefault' => '', 'datadefault_other' => '',
			'on_get_model_field' => '', 'on_save_model_field' => '',
		]);

		$this->assertIsArray($result);
		$this->assertSame('', $result['datatype']);
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Reusing a form cannot carry a note's temporary exemptions into a normal field.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testRepeatedValidationRestoresRequirementsAndDetachesTheRule(): void
	{
		$form = $this->form();
		$before = $form->getXml()->asXML();
		$note = $this->model();
		$this->assertIsArray($note->validate($form, ['name' => 'Instructions', 'fieldtype' => self::NOTE]));
		$this->assertSame($before, $form->getXml()->asXML());

		$text = $this->model(['id' => 42, 'fieldtype' => self::NOTE]);
		$this->assertFalse($text->validate($form, ['id' => 42, 'name' => 'Title', 'fieldtype' => self::TEXT]));
		$this->assertSame($before, $form->getXml()->asXML());

		$complete = $this->model(['id' => 42, 'fieldtype' => self::NOTE]);
		$this->assertIsArray($complete->validate($form, $this->columnData()));
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Switching to a note also suppresses dependent stale database requirements.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testSwitchingToNoteKeepsValuesWithoutRequiringTheirHiddenDependents(): void
	{
		$form = $this->form();
		unset($form->getFieldXml('indexes')['required']);
		$form->setFieldAttribute('null_switch', 'required', 'required');
		$before = $form->getXml()->asXML();
		$model = $this->model(['id' => 42, 'fieldtype' => self::TEXT]);
		$data = [
			'id' => 42, 'name' => 'Instructions', 'fieldtype' => self::NOTE, 'datatype' => 'VARCHAR',
			'datalenght' => 'Other', 'datadefault' => 'Other', 'store' => '6',
		];
		$result = $model->validate($form, $data);

		$this->assertIsArray($result);
		$this->assertSame('VARCHAR', $result['datatype']);
		$this->assertSame('Other', $result['datalenght']);
		$this->assertSame('Other', $result['datadefault']);
		$this->assertSame(6, $result['store']);
		$this->assertArrayNotHasKey('on_get_model_field', $result);
		$this->assertArrayNotHasKey('on_save_model_field', $result);
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Switching away from Note cannot bypass ordinary column requirements.
	 *
	 * @param   string  $missing  Required field omitted from the submitted values.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('columnRequirements')]
	public function testOrdinaryFieldsIgnoreForgedBrowserExemptions(string $missing): void
	{
		$form = $this->form();
		$before = $form->getXml()->asXML();
		$model = $this->model(['id' => 42, 'fieldtype' => self::NOTE]);
		$data = $this->columnData();
		$data['not_required'] = 'datatype,null_switch,store,datalenght,indexes,name,fieldtype';
		unset($data[$missing]);

		$this->assertFalse($model->validate($form, $data));
		$this->assertNotEmpty($model->getErrors());
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Database fields whose requirements must remain effective for ordinary types.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.2.1
	 */
	public static function columnRequirements(): array
	{
		return [
			'data type' => ['datatype'],
			'null switch' => ['null_switch'],
			'storage' => ['store'],
			'length' => ['datalenght'],
			'indexes' => ['indexes'],
		];
	}

	/**
	 * Complete ordinary data remains valid after changing away from a note.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testOrdinaryFieldsWithColumnSettingsStillValidate(): void
	{
		$model = $this->model(['id' => 42, 'fieldtype' => self::NOTE]);
		$result = $model->validate($this->form(), $this->columnData());

		$this->assertIsArray($result);
		$this->assertSame('VARCHAR', $result['datatype']);
		$this->assertSame(0, $result['store']);
		$this->assertSame(0, $result['indexes']);
	}

	/**
	 * Display-only conditions do not make the ordinary name requirement optional.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testNotesStillRequireTheirName(): void
	{
		$form = $this->form();
		$before = $form->getXml()->asXML();
		$model = $this->model();

		$this->assertFalse($model->validate($form, ['fieldtype' => self::NOTE, 'not_required' => 'name']));
		$this->assertNotEmpty($model->getErrors());
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Protected selectors use stored metadata instead of discarded client input.
	 *
	 * @param   string  $policy      Form preprocessing applied to the selector.
	 * @param   bool    $storedNote  Whether the protected stored type is a note.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('protectedSelectors')]
	public function testProtectedSelectorsUseTheStoredType(string $policy, bool $storedNote): void
	{
		$form = $this->form();
		$data = ['id' => 42, 'name' => 'Instructions', 'fieldtype' => $storedNote ? self::TEXT : self::NOTE];
		if ($policy === 'removed')
		{
			$form->removeField('fieldtype');
		}
		else
		{
			// A preprocessed form must permit omission of its protected selector.
			$form->setFieldAttribute('fieldtype', 'required', 'false');
			$form->setFieldAttribute('fieldtype', $policy, $policy === 'disabled' ? 'true' : 'unset');
			if ($policy === 'disabled')
			{
				unset($data['fieldtype']);
			}
		}
		$type = $storedNote ? self::NOTE : self::TEXT;
		$model = $this->model(['id' => 42, 'fieldtype' => $type]);
		$before = $form->getXml()->asXML();
		$result = $model->validate($form, $data);

		$this->assertSame($storedNote, is_array($result));
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Exercise both accepted notes and denied attempts to turn normal fields into notes.
	 *
	 * @return  iterable<string, array{string, bool}>
	 * @since   6.2.1
	 */
	public static function protectedSelectors(): iterable
	{
		foreach (['removed', 'disabled', 'filter'] as $policy)
		{
			yield $policy . ' stored note' => [$policy, true];
			yield $policy . ' stored text' => [$policy, false];
		}
	}

	/**
	 * Only API PATCH omission inherits a selector; an explicit empty value does not.
	 *
	 * @param   bool  $patch     Whether native application context is API PATCH.
	 * @param   bool  $explicit  Whether an empty field type was explicitly submitted.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('omittedSelectors')]
	public function testOmittedAndExplicitSelectorsHaveDistinctPatchSemantics(bool $patch, bool $explicit): void
	{
		$form = $this->form();
		// The API normally hydrates stored columns. Also protect the model's sparse-form contract.
		$form->setFieldAttribute('fieldtype', 'required', 'false');
		$inherits = $patch && !$explicit;
		$model = $this->model(['id' => 42, 'fieldtype' => self::NOTE], $patch);
		$data = ['id' => 42, 'name' => 'Instructions'];
		if ($explicit)
		{
			$data['fieldtype'] = '';
		}

		$this->assertSame($inherits, is_array($model->validate($form, $data)));
	}

	/**
	 * Omission is different from deliberately clearing an editable field type.
	 *
	 * @return  array<string, array{bool, bool}>
	 * @since   6.2.1
	 */
	public static function omittedSelectors(): array
	{
		return [
			'patch omission' => [true, false],
			'post omission' => [false, false],
			'patch explicit empty' => [true, true],
		];
	}

	/**
	 * Untrusted labels and malformed identifiers cannot relax requirements.
	 *
	 * @param   mixed  $type  Invalid field type identifier.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('malformedTypes')]
	public function testMalformedIdentifiersCannotClaimADisplayOnlyCondition(mixed $type): void
	{
		$model = $this->model();

		$this->assertFalse($model->validate($this->form(), [
			'name' => 'Instructions', 'fieldtype' => $type, 'not_required' => 'datatype,null_switch,store',
		]));
	}

	/**
	 * Include invalid scalar and structured values after normal form filtering.
	 *
	 * @return  array<string, array{mixed}>
	 * @since   6.2.1
	 */
	public static function malformedTypes(): array
	{
		return [
			'name instead of identifier' => ['note'],
			'empty identifier' => [''],
			'zero identifier' => ['0'],
			'negative identifier' => ['-1'],
			'fractional identifier' => ['1.5'],
			'injection-shaped identifier' => ['19 OR 1=1'],
			'array identifier' => [[self::NOTE]],
			'object-shaped identifier' => [['guid' => self::NOTE]],
		];
	}

	/**
	 * Even the installed Note identity needs an authored conditional definition.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testNoteRequiresAnAuthoredDisplayOnlyCondition(): void
	{
		$form = $this->form();
		$before = $form->getXml()->asXML();
		$model = $this->model();
		$property = new ReflectionProperty($model, 'conditionalRequirements');
		$conditions = $property->getValue($model);
		$display = array_pop($conditions);
		$this->assertSame('fieldtype', $display['matches'][0]['name']);
		$this->assertSame([self::NOTE, self::SPACER], $display['matches'][0]['options']);
		$property->setValue($model, $conditions);

		$this->assertFalse($model->validate($form, [
			'name' => 'Instructions', 'fieldtype' => self::NOTE,
			'not_required' => 'datatype,null_switch,store',
		]));
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Custom and legacy identities work only when the trusted condition declares them.
	 *
	 * @param   string|int  $type  Additional field type identity to declare.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('additionalDisplayIdentities')]
	public function testAdditionalIdentitiesRequireAnAuthoredCondition(string|int $type): void
	{
		$form = $this->form();
		$before = $form->getXml()->asXML();
		$model = $this->model();
		$data = ['name' => 'Instructions', 'fieldtype' => $type];
		$this->assertFalse($model->validate($form, $data));

		$property = new ReflectionProperty($model, 'conditionalRequirements');
		$conditions = $property->getValue($model);
		$last = array_key_last($conditions);
		$this->assertSame('fieldtype', $conditions[$last]['matches'][0]['name']);
		$conditions[$last]['matches'][0]['options'][] = (string) $type;
		$property->setValue($model, $conditions);
		$result = $model->validate($form, $data);

		$this->assertIsArray($result);
		$this->assertSame((string) $type, (string) $result['fieldtype']);
		$this->assertArrayNotHasKey('datatype', $result);
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Include custom GUIDs and explicitly authored legacy catalogue identifiers.
	 *
	 * @return  array<string, array{string|int}>
	 * @since   6.2.1
	 */
	public static function additionalDisplayIdentities(): array
	{
		return [
			'custom GUID' => ['b85696a0-92bb-4db1-8939-6eed795ca378'],
			'legacy integer identifier' => [19],
			'legacy string identifier' => ['20'],
		];
	}

	/**
	 * A failed persisted-record load cannot relax any required field.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testFailedStoredRecordLoadDoesNotRelaxRequirements(): void
	{
		$form = $this->form();
		$before = $form->getXml()->asXML();
		$model = $this->model(false);

		$this->assertFalse($model->validate($form, ['id' => 42, 'name' => 'Instructions', 'fieldtype' => self::NOTE]));
		$this->assertSame($before, $form->getXml()->asXML());
	}

	/**
	 * Build a real form from the component's XML, retaining the fields under test.
	 *
	 * @return  Form
	 * @since   6.2.1
	 */
	private function form(): Form
	{
		$form = new Form('componentbuilder.field.validation');
		$form->setDatabase($this->createStub(DatabaseInterface::class));
		$form->setCurrentUser(new User());
		$form->loadFile(dirname(__DIR__, 4) . '/admin/forms/field.xml');
		$keep = [
			'id', 'name', 'fieldtype', 'datatype', 'datalenght', 'datalenght_other',
			'datadefault', 'datadefault_other', 'indexes', 'null_switch', 'store',
			'on_get_model_field', 'on_save_model_field', 'not_required',
		];
		foreach ($form->getXml()->xpath('/form/fieldset/field') as $field)
		{
			if (!in_array((string) $field['name'], $keep, true))
			{
				$form->removeField((string) $field['name']);
			}
		}

		return $form;
	}

	/**
	 * Supply valid ordinary field settings, including meaningful zero selections.
	 *
	 * @return  array<string, string|int>
	 * @since   6.2.1
	 */
	private function columnData(): array
	{
		return [
			'id' => 42, 'name' => 'Title', 'fieldtype' => self::TEXT, 'datatype' => 'VARCHAR',
			'datalenght' => '255', 'indexes' => '0', 'null_switch' => 'NULL', 'store' => '0',
		];
	}

	/**
	 * Isolate stored records while running the actual model and native parent.
	 *
	 * @param   array|false  $stored  Existing record, new record, or failed load.
	 * @param   bool         $patch   Whether the application handles API PATCH.
	 *
	 * @return  FieldModel
	 * @since   6.2.1
	 */
	private function model(array|false $stored = [], bool $patch = false): FieldModel
	{
		$app = $this->createStub(CMSApplication::class);
		$app->method('isClient')->willReturnCallback(static fn (string $client): bool => $patch && $client === 'api');
		$input = new Input([]);
		$input->server->set('REQUEST_METHOD', $patch ? 'PATCH' : 'POST');
		$app->method('getInput')->willReturn($input);
		$this->setJoomlaApplication($app);
		$model = $this->getMockBuilder(FieldModel::class)
			->disableOriginalConstructor()
			->onlyMethods(['getItem'])
			->getMock();
		$id = $stored === false ? 42 : ($stored['id'] ?? 0);
		$model->expects($id > 0 ? $this->once() : $this->never())->method('getItem')
			->with($id)->willReturn($stored === false ? false : (object) $stored);
		(new ReflectionProperty($model, 'name'))->setValue($model, 'field');
		(new ReflectionProperty($model, 'state'))->setValue($model, new Registry(['field.id' => $id]));
		(new ReflectionProperty($model, '__state_set'))->setValue($model, true);
		(new ReflectionProperty($model, 'events_map'))->setValue($model, ['validate' => 'content']);
		$model->setDispatcher(new Dispatcher());
		$db = $this->createMock(DatabaseInterface::class);
		// Conditions use declared identities, never special field type name lookups.
		$db->expects($this->never())->method('createQuery');
		$db->expects($this->never())->method('setQuery');
		$db->expects($this->never())->method('loadResult');
		$model->setDatabase($db);

		return $model;
	}
}
