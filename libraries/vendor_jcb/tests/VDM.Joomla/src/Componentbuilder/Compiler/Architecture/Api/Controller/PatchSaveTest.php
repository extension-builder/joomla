<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    7th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Controller;


use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Input\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use RuntimeException;
use Tobscure\JsonApi\Exception\InvalidParameterException;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\RecordId;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueGuid;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Execute the generated PATCH hook inside Joomla's native save lifecycle.
 *
 * Storage and model transformations are bounded fixtures; request merging,
 * generated preparation, form filtering, validation and API error flow are real.
 *
 * @since  6.2.0
 */
#[CoversClass(RecordId::class)]
final class PatchSaveTest extends ArchitectureTestCase
{
	/**
	 * Form discovery state restored after exercising native save paths.
	 *
	 * @var    array<string, mixed>
	 * @since  6.2.0
	 */
	private array $formHelperState = [];

	/**
	 * Isolate native form discovery and supply the language boundary.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		foreach (['paths', 'prefixes', 'entities'] as $name)
		{
			$this->formHelperState[$name] = (new ReflectionProperty(FormHelper::class, $name))->getValue();
		}

		$this->formHelperState['formInstances'] = (new ReflectionProperty(Form::class, 'forms'))->getValue();
		(new ReflectionProperty(Form::class, 'forms'))->setValue(null, []);
		$factory = $this->createStub(FormFactoryInterface::class);
		$database = $this->createStub(DatabaseInterface::class);
		$user = $this->createStub(User::class);
		$factory->method('createForm')->willReturnCallback(
			static function (string $name, array $options) use ($database, $user): Form
			{
				$form = new Form($name, $options);
				$form->setDatabase($database);
				$form->setCurrentUser($user);

				return $form;
			}
		);
		$container = new Container();
		$container->set(FormFactoryInterface::class, $factory);
		$this->setJoomlaContainer($container);
		$language = $this->createStub(Language::class);
		$language->method('_')->willReturnArgument(0);
		$this->setJoomlaFactoryProperty('language', $language);
	}

	/**
	 * Restore process-static form state for randomized suite execution.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function tearDown(): void
	{
		(new ReflectionProperty(Form::class, 'forms'))->setValue(null, $this->formHelperState['formInstances']);
		unset($this->formHelperState['formInstances']);

		foreach ($this->formHelperState as $name => $value)
		{
			(new ReflectionProperty(FormHelper::class, $name))->setValue(null, $value);
		}

		parent::tearDown();
	}

	/**
	 * Repeated partial updates preserve stored bytes and related tag identifiers.
	 *
	 * @param   bool  $guidRoute  Resolve the route through the unique GUID.
	 * @param   bool  $tagHelper  Return tags in Joomla's helper representation.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('routeAndTagShapes')]
	public function testRepeatedNameOnlyPatchPreservesCodeSubformsJsonAndTags(bool $guidRoute, bool $tagHelper): void
	{
		$state = $this->storedState();
		$expected = $state->row;
		$model = $this->model($state, $tagHelper);

		foreach (['First rename', 'Second rename'] as $name)
		{
			$controller = $this->controller($model, ['name' => $name], 'PATCH', $guidRoute);
			$this->assertSame($controller, $controller->edit());
			$expected['name'] = $name;
			$this->assertSame($expected, $state->row);
			$this->assertSame(['3', '8'], $state->tags);
			$this->assertSame('return "original";', base64_decode($state->row['code'], true));
			$this->assertSame([42], $controller->displayed);
		}

		$this->assertSame([42, 42], $state->hydrated);
		$this->assertCount(2, $state->saved);
		$this->assertSame('return "original";', $state->validated[0]['code']);
		$this->assertSame(['subform0' => ['value' => 'kept']], $state->validated[0]['settings']);
		$this->assertSame(['enabled' => true], $state->validated[0]['options']);
		$this->assertArrayNotHasKey('display_summary', $state->validated[0]);
	}

	/**
	 * Explicit values remain input even when they look like stored Base64.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testExplicitReplacementAndClearingAreNotReplacedByStoredValues(): void
	{
		$state = $this->storedState();
		$model = $this->model($state);
		$literalBase64 = 'cmV0dXJuICJzdWJtaXR0ZWQiOw==';
		$this->controller($model, [
			'code' => $literalBase64,
			'settings' => ['subform0' => ['value' => 'replacement']],
			'options' => ['enabled' => false],
			'tags' => ['5'],
		])->edit();

		$this->assertSame(base64_encode($literalBase64), $state->row['code']);
		$this->assertSame('{"subform0":{"value":"replacement"}}', $state->row['settings']);
		$this->assertSame('{"enabled":false}', $state->row['options']);
		$this->assertSame(['5'], $state->tags);

		$this->controller($model, ['code' => '', 'settings' => [], 'options' => [], 'tags' => []])->edit();

		$this->assertSame('', $state->row['code']);
		$this->assertSame('[]', $state->row['settings']);
		$this->assertSame('[]', $state->row['options']);
		$this->assertSame([], $state->tags);
	}

	/**
	 * Explicit null reaches the form and remains subject to required validation.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNullRequiredInputIsNotSilentlyHydratedFromStorage(): void
	{
		$state = $this->storedState();
		$expected = $state->row;
		$controller = $this->controller($this->model($state), ['name' => null]);

		try
		{
			$controller->edit();
			$this->fail('A required null name must fail native form validation.');
		}
		catch (InvalidParameterException $exception)
		{
			$this->assertStringContainsString('JLIB_FORM_VALIDATE_FIELD_REQUIRED', $exception->getMessage());
		}

		$this->assertArrayHasKey('name', $state->validated[0]);
		$this->assertNull($state->validated[0]['name']);
		$this->assertSame([], $state->saved);
		$this->assertSame($expected, $state->row);
	}

	/**
	 * Omitted nullable and false-like storage values retain their normalized type.
	 *
	 * @param   string|null  $stored      The raw JSON or SQL-null column.
	 * @param   mixed        $normalized  Its edit-model representation.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('nullableStoredValues')]
	public function testInheritedNullableAndZeroValuesReachValidation(?string $stored, mixed $normalized): void
	{
		$state = $this->storedState();
		$state->row['options'] = $stored;
		$this->controller($this->model($state), ['name' => 'Still retained'])->edit();

		$this->assertArrayHasKey('options', $state->validated[0]);
		$this->assertSame($normalized, $state->validated[0]['options']);
	}

	/**
	 * Distinguish decoded JSON null, SQL null and false-like scalar values.
	 *
	 * @return  array<string, array{string|null, mixed}>
	 * @since   6.2.0
	 */
	public static function nullableStoredValues(): array
	{
		return [
			'JSON null' => ['null', null],
			'SQL null' => [null, null],
			'JSON zero' => ['0', 0],
			'JSON false' => ['false', false],
		];
	}

	/**
	 * POST creation never hydrates a previously stored record.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testCreateRetainsNativeInputWithoutReadingAnExistingItem(): void
	{
		$state = $this->storedState();
		$controller = $this->controller($this->model($state), [
			'name' => 'Created record',
			'code' => 'new code',
			'settings' => [],
			'options' => [],
		], 'POST');
		$controller->add();

		$this->assertSame([], $state->hydrated);
		$this->assertSame([], $state->loads);
		$this->assertSame('Created record', $state->saved[0]['name']);
		$this->assertSame('new code', $state->saved[0]['code']);
		$this->assertArrayNotHasKey('licensing_template', $state->saved[0]);
		$this->assertSame([], $state->saved[0]['tags']);
		$this->assertSame([42], $controller->displayed);
	}

	/**
	 * Failed normalized reads abort before form validation or persistence.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testFailedItemReadCannotPersistTheRawStorageMerge(): void
	{
		$state = $this->storedState();
		$state->readable = false;
		$controller = $this->controller($this->model($state), ['name' => 'Cannot save']);

		try
		{
			$controller->edit();
			$this->fail('An unreadable normalized record must abort PATCH.');
		}
		catch (RuntimeException $exception)
		{
			$this->assertSame('JLIB_APPLICATION_ERROR_RECORD_LOAD', $exception->getMessage());
			$this->assertSame(500, $exception->getCode());
		}

		$this->assertSame([], $state->validated);
		$this->assertSame([], $state->saved);
	}

	/**
	 * Cover both supported route identifiers and normalized tag representations.
	 *
	 * @return  array<string, array{bool, bool}>
	 * @since   6.2.0
	 */
	public static function routeAndTagShapes(): array
	{
		return [
			'numeric ID and tags helper' => [false, true],
			'GUID and tags helper' => [true, true],
			'numeric ID and tag array' => [false, false],
			'GUID and tag array' => [true, false],
		];
	}

	/**
	 * Supply explicit database bytes and observable boundary call histories.
	 *
	 * @return  object
	 * @since   6.2.0
	 */
	private function storedState(): object
	{
		return (object) [
			'row' => [
				'id' => 42,
				'guid' => '3745af8f-f96b-4e17-831e-eb4062cd4389',
				'name' => 'Original name',
				'code' => base64_encode('return "original";'),
				'licensing_template' => base64_encode('GPL-2.0-or-later'),
				'settings' => '{"subform0":{"value":"kept"}}',
				'options' => '{"enabled":true}',
			],
			'tags' => ['3', '8'],
			'readable' => true,
			'loads' => [],
			'hydrated' => [],
			'validated' => [],
			'saved' => [],
			'errors' => [],
		];
	}

	/**
	 * Mock storage/model boundaries while using Joomla's real form processing.
	 *
	 * @param   object  $state      Mutable fixture storage and call histories.
	 * @param   bool    $tagHelper  Expose tags through the native TagsHelper.
	 *
	 * @return  AdminModel
	 * @since   6.2.0
	 */
	private function model(object $state, bool $tagHelper = true): AdminModel
	{
		$table = $this->createStub(Table::class);
		$table->method('getKeyName')->willReturn('id');
		$table->method('getColumnAlias')->willReturnArgument(0);
		$table->method('hasField')->willReturn(false);
		$table->method('getFields')->willReturn(array_map(
			static fn(string $name): object => (object) ['Field' => $name],
			array_keys($state->row)
		));
		$table->method('load')->willReturnCallback(
			static function ($key) use ($state, $table): bool
			{
				$state->loads[] = $key;

				foreach ($state->row as $name => $value)
				{
					$table->{$name} = $value;
				}

				return $key === 42 || $key === ['guid' => $state->row['guid']];
			}
		);
		$form = new Form('patch-record');
		$form->setDatabase($this->createStub(DatabaseInterface::class));
		$form->setCurrentUser($this->createStub(User::class));
		$this->assertTrue($form->load(
			'<form><field name="id" type="text" filter="integer" label="ID" translateLabel="false" />'
			. '<field name="guid" type="text" filter="string" label="GUID" translateLabel="false" />'
			. '<field name="name" type="text" filter="string" required="true" label="Name" translateLabel="false" />'
			. '<field name="code" type="textarea" filter="raw" label="Code" translateLabel="false" />'
			. '<field name="licensing_template" type="textarea" filter="raw" label="License" translateLabel="false" />'
			. '<field name="settings" type="subform" multiple="true" filter="raw" validate="Subform" label="Settings" translateLabel="false">'
			. '<form><field name="value" type="text" filter="string" label="Value" translateLabel="false" /></form></field>'
			. '<field name="options" type="text" filter="raw" label="Options" translateLabel="false" />'
			. '<field name="tags" type="text" filter="raw" label="Tags" translateLabel="false" /></form>'
		));
		$model = $this->createStub(AdminModel::class);
		$model->method('getTable')->willReturn($table);
		$model->method('getName')->willReturn('record');
		$model->method('getState')->willReturn(42);
		$model->method('getForm')->willReturn($form);
		$model->method('getErrors')->willReturnCallback(static fn(): array => $state->errors);
		$model->method('getItem')->willReturnCallback(
			static function ($id) use ($state, $tagHelper): object|false
			{
				$state->hydrated[] = $id;

				if (!$state->readable)
				{
					return false;
				}

				$item = (object) $state->row;
				$item->code = base64_decode($item->code, true);
				$item->licensing_template = base64_decode($item->licensing_template, true);
				$item->settings = json_decode($item->settings, true);
				$item->options = is_string($item->options) ? json_decode($item->options, true) : $item->options;
				$item->display_summary = 'A computed property that is not a table column';
				$item->tags = $state->tags;

				if ($tagHelper)
				{
					$item->tags = new TagsHelper();
					$item->tags->tags = implode(',', $state->tags);
				}

				return $item;
			}
		);
		$model->method('validate')->willReturnCallback(
			static function (Form $form, array $data) use ($state): array|false
			{
				$state->validated[] = $data;
				$result = $form->process($data);
				$state->errors = $form->getErrors();

				return $result;
			}
		);
		$model->method('save')->willReturnCallback(
			static function (array $data) use ($state): bool
			{
				$state->saved[] = $data;

				foreach (array_keys($state->row) as $name)
				{
					if (!array_key_exists($name, $data) || $name === 'id')
					{
						continue;
					}

					$state->row[$name] = match ($name)
					{
						'code', 'licensing_template' => base64_encode($data[$name]),
						'settings', 'options' => json_encode($data[$name]),
						default => $data[$name],
					};
				}

				$state->tags = $data['tags'];

				return true;
			}
		);

		return $model;
	}

	/**
	 * Render the complete maintained template, replacing only external boundaries.
	 *
	 * The generated edit, record lookup and PATCH preparation methods execute
	 * unchanged over the real Joomla ApiController lifecycle.
	 *
	 * @param   AdminModel  $model      The bounded model and storage contract.
	 * @param   array       $payload    The unmodified client body.
	 * @param   string      $method     HTTP method.
	 * @param   bool        $guidRoute  Resolve by GUID instead of numeric ID.
	 *
	 * @return  ApiController
	 * @since   6.2.0
	 */
	private function controller(AdminModel $model, array $payload, string $method = 'PATCH', bool $guidRoute = false): ApiController
	{
		$input = new Input($guidRoute
			? ['guid' => '3745af8f-f96b-4e17-831e-eb4062cd4389']
			: ['id' => 42]);
		$input->server->set('REQUEST_METHOD', $method);
		$input->set('data', $payload);
		$json = $this->createStub(Json::class);
		$json->method('getRaw')->willReturn(json_encode($payload));
		$inputs = new ReflectionProperty(Input::class, 'inputs');
		$values = $inputs->getValue($input);
		$values['json'] = $json;
		$inputs->setValue($input, $values);
		$guid = new DatabaseUniqueGuid();
		$guid->set('record', true);
		$recordId = $this->renderer(RecordId::class, ['databaseuniqueguid' => $guid])->get('record');
		$template = file_get_contents(dirname(__DIR__, 10) . '/admin/compiler/joomla_4/API_VIEW_CONTROLLER.php');
		$this->assertIsString($template);
		$template = substr($template, strpos($template, '###BOM###'));
		$token = 'Patch' . substr(hash('sha256', $template . $recordId), 0, 16);
		$class = 'VDM\\PatchContract\\Component\\Example\\Api\\Controller\\' . $token . 'Controller';

		if (!class_exists($class, false))
		{
			$code = strtr($template, [
				'###BOM###' => '',
				'###NAMESPACEPREFIX###' => 'VDM\\PatchContract',
				'###ComponentNamespace###' => 'Example',
				'###View###' => $token,
				'###view###' => 'record',
				'###views###' => 'records',
				'###API_VIEW_CONTROLLER_HEADER###' => 'use Joomla\\CMS\\MVC\\Controller\\ApiController; use Joomla\\CMS\\Language\\Text;',
				'###API_VIEW_CONTROLLER_GETMODEL###' => 'return $this->fixtureModel;',
				'###API_VIEW_CONTROLLER_RECORDID###' => $recordId,
				'###API_VIEW_CONTROLLER_ALLOWVIEW###' => 'return true;',
				'###JCONTROLLERFORM_ALLOWADD###' => 'return true;',
				'###JCONTROLLERFORM_ALLOWEDIT###' => 'return true;',
				'###API_VIEW_CONTROLLER_ALLOWDELETE###' => 'return true;',
			]);
			$this->assertStringNotContainsString('###', $code);
			eval($code);
		}

		$harness = <<<'PHP'
return new class($model, $input) extends GENERATED_CONTROLLER
{
	/**
	 * The storage/model boundary returned by generated model selection.
	 * @var    \Joomla\CMS\MVC\Model\AdminModel
	 * @since  6.2.0
	 */
	protected \Joomla\CMS\MVC\Model\AdminModel $fixtureModel;

	/**
	 * Record identifiers passed to the external JSON:API presentation layer.
	 * @var    array<int>
	 * @since  6.2.0
	 */
	public array $displayed = [];

	/**
	 * Supply only the dependencies needed by the native save lifecycle.
	 * @param   \Joomla\CMS\MVC\Model\AdminModel  $model  Model boundary.
	 * @param   \Joomla\Input\Input              $input  Native request.
	 * @since   6.2.0
	 */
	public function __construct(\Joomla\CMS\MVC\Model\AdminModel $model, \Joomla\Input\Input $input)
	{
		$this->fixtureModel = $model;
		$this->input = $input;
		$this->option = 'com_patch_contract';
	}

	/**
	 * Bound the response view after native validation and persistence finish.
	 * @param   int|null  $id  Saved record identifier.
	 * @return  static
	 * @since   6.2.0
	 */
	public function displayItem($id = null)
	{
		$this->displayed[] = $id;

		return $this;
	}
};
PHP;

		return eval(str_replace('GENERATED_CONTROLLER', '\\' . $class, $harness));
	}
}
