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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use Closure;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Language\Language;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\Dispatcher;
use Joomla\Input\Input;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use RuntimeException;
use Tobscure\JsonApi\Exception\InvalidParameterException;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\RecordId;
use VDM\Joomla\Componentbuilder\Compiler\Builder\BaseSixFour;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CheckBox;
use VDM\Joomla\Componentbuilder\Compiler\Builder\JsonString;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionFields;


/**
 * Compose generated models with Joomla's native API, validation and save lifecycle.
 *
 * Only application identity, XML loading, SQL storage and cache are bounded.
 * Fresh model/table instances prevent fixture state from masking production bugs.
 *
 * @since  6.2.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
final class GeneratedPatchLifecycleTest extends ArchitectureTestCase
{
	/**
	 * Static Joomla discovery state restored after each native lifecycle.
	 *
	 * @var    array<array{ReflectionProperty, mixed}>
	 * @since  6.2.0
	 */
	private array $joomlaState = [];

	/**
	 * Isolate plugin/form discovery and language resolution.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		foreach ([[PluginHelper::class, 'plugins'], [Form::class, 'forms'], [FormHelper::class, 'paths'], [FormHelper::class, 'prefixes'], [FormHelper::class, 'entities']] as [$class, $name])
		{
			$property = new ReflectionProperty($class, $name);
			$this->joomlaState[] = [$property, $property->getValue()];

			if ($class !== FormHelper::class)
			{
				$property->setValue(null, []);
			}
		}

		$language = $this->createStub(Language::class);
		$language->method('_')->willReturnCallback(static fn ($key): string => (string) $key === 'JLIB_APPLICATION_ERROR_SAVE_FAILED' ? 'Save failed: %s' : (string) $key);
		$this->setJoomlaFactoryProperty('language', $language);
		$database = $this->createStub(DatabaseDriver::class);
		$database->method('getDateFormat')->willReturn('Y-m-d H:i:s');
		$this->setJoomlaFactoryProperty('database', $database);
	}

	/**
	 * Restore native process-static state for randomized test execution.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function tearDown(): void
	{
		foreach ($this->joomlaState as [$property, $value])
		{
			$property->setValue(null, $value);
		}

		parent::tearDown();
	}

	/**
	 * Denied fields and post-getForm plugin changes cannot clear relationships.
	 *
	 * @param   string  $guard      Field permission or validation-plugin guard.
	 * @param   bool    $submitted  Explicit forbidden tag payload.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('deniedTags')]
	public function testDeniedTagsSurviveNativeValidationAndSave(string $guard, bool $submitted): void
	{
		$storage = $this->storage();
		$expected = $storage->row;
		$payload = ['name' => 'Renamed'] + ($submitted ? ['tags' => []] : []);
		$controller = $this->controller($storage, $payload, $guard);
		$controller->edit();

		$this->assertSame(['3', '8'], $storage->tags);
		$this->assertArrayNotHasKey('tags', $storage->beforeSaveData);
		$this->assertSame(array_replace($expected, ['name' => 'Renamed', 'modified' => '2026-10-07 12:00:00', 'modified_by' => 7, 'version' => 2]), $storage->row);
		$this->assertSame(['validate', 'bind', 'before', 'store', 'cache', 'after'], $storage->events);
		$this->assertGreaterThan(1, count($storage->models));
		$this->assertSame(count($storage->models), count(array_unique(array_map('spl_object_id', $storage->models))));
	}

	/**
	 * Exercise XML removal, edit disabling and native validation plugin changes.
	 *
	 * @return  iterable<string, array{string, bool}>
	 * @since   6.2.0
	 */
	public static function deniedTags(): iterable
	{
		foreach (['edit', 'access', 'view', 'plugin-remove', 'plugin-unset'] as $guard)
		{
			foreach ([false, true] as $submitted)
			{
				yield $guard . ($submitted ? ' explicit' : ' omitted') => [$guard, $submitted];
			}
		}
	}

	/**
	 * Omission is stable across requests; permitted empty input deliberately clears.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testRepeatedPatchPreservesBytesAndPermittedTagsCanBeCleared(): void
	{
		$storage = $this->storage();
		$expected = $storage->row;

		foreach (['First', 'Second'] as $index => $name)
		{
			$this->controller($storage, ['name' => $name])->edit();
			$this->assertSame(array_replace($expected, ['name' => $name, 'modified' => '2026-10-07 12:00:00', 'modified_by' => 7, 'version' => $index + 2]), $storage->row);
			$this->assertSame(['3', '8'], $storage->tags);
		}

		$this->controller($storage, ['code' => 'YQ==', 'options' => [], 'tags' => []])->edit();
		$this->assertSame(base64_encode('YQ=='), $storage->row['code']);
		$this->assertSame('[]', $storage->row['options']);
		$this->assertSame([], $storage->tags);
		$this->assertSame([], $storage->beforeSaveData['tags']);
	}

	/**
	 * Generated table preparation still owns audit fields and name canonicalization.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testExistingPrepareTableBehaviorRunsAfterPreservation(): void
	{
		$storage = $this->storage();
		$storage->row['name'] = 'Fish &amp; Chips';
		$this->controller($storage, ['code' => 'replacement'])->edit();

		$this->assertSame('Fish & Chips', $storage->row['name']);
		$this->assertSame('2026-10-07 12:00:00', $storage->row['modified']);
		$this->assertSame(7, $storage->row['modified_by']);
		$this->assertSame(2, $storage->row['version']);
		$this->assertSame('2020-01-02 10:30:00', $storage->row['created']);
		$this->assertSame(9, $storage->row['created_by']);
	}

	/**
	 * Explicit invalid input fails native validation before table or save plugins.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testInvalidExplicitValueDoesNotReachPersistence(): void
	{
		$storage = $this->storage();
		$expected = $storage->row;

		try
		{
			$this->controller($storage, ['name' => null, 'tags' => []])->edit();
			$this->fail('An explicit null required field must fail validation.');
		}
		catch (InvalidParameterException $exception)
		{
			$this->assertStringContainsString('JLIB_FORM_VALIDATE_FIELD_REQUIRED', $exception->getMessage());
		}

		$this->assertSame($expected, $storage->row);
		$this->assertSame(['3', '8'], $storage->tags);
		$this->assertSame(['validate'], $storage->events);
	}

	/**
	 * Native save failures preserve storage and do not dispatch successful events.
	 *
	 * @param   string  $failure  Persistence boundary to fail.
	 * @param   array   $events   Expected native event order.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('saveFailures')]
	public function testNativeFailuresDoNotPersist(string $failure, array $events): void
	{
		$storage = $this->storage();
		$expected = $storage->row;
		$storage->failure = $failure;

		try
		{
			$this->controller($storage, ['name' => 'Must not persist', 'tags' => []])->edit();
			$this->fail('The native save error must reach the API caller.');
		}
		catch (RuntimeException $exception)
		{
			$this->assertStringContainsString('fixture ' . $failure, $exception->getMessage());
		}

		$this->assertSame($expected, $storage->row);
		$this->assertSame(['3', '8'], $storage->tags);
		$this->assertSame($events, $storage->events);
	}

	/**
	 * Native table and plugin failures occur at distinct lifecycle boundaries.
	 *
	 * @return  array<string, array{string, array<string>}>
	 * @since   6.2.0
	 */
	public static function saveFailures(): array
	{
		return [
			'bind exception' => ['bind', ['validate', 'bind']],
			'table check' => ['check', ['validate', 'bind']],
			'plugin veto' => ['veto', ['validate', 'bind', 'before']],
			'table store' => ['store', ['validate', 'bind', 'before', 'store']],
		];
	}

	/**
	 * Creation and administrator saves keep native tag defaults and input.
	 *
	 * @param   string  $method  Request method.
	 * @param   bool    $api     Application client.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('nonPatchClients')]
	public function testNonPatchSemanticsRemainNative(string $method, bool $api): void
	{
		$storage = $this->storage();
		$controller = $this->controller($storage, ['name' => 'Created or edited', 'tags' => []], '', $method, $api);

		if ($method === 'POST')
		{
			$controller->add();
		}
		else
		{
			$controller->edit();
		}

		$this->assertSame([], $storage->tags);
		$this->assertSame([], $storage->beforeSaveData['tags']);
	}

	/**
	 * Cases outside API PATCH preservation.
	 *
	 * @return  array<string, array{string, bool}>
	 * @since   6.2.0
	 */
	public static function nonPatchClients(): array
	{
		return ['API POST' => ['POST', true], 'administrator PATCH' => ['PATCH', false]];
	}

	/**
	 * Stored bytes intentionally include formatting normalization would destroy.
	 *
	 * @return  object
	 * @since   6.2.0
	 */
	private function storage(): object
	{
		return (object) [
			'row' => ['id' => 42, 'name' => 'Original', 'code' => base64_encode('original code'), 'options' => '{ "enabled" : true }', 'checkbox' => '1', 'created' => '2020-01-02 10:30:00', 'created_by' => 9, 'ordering' => 1, 'modified' => null, 'modified_by' => 0, 'version' => 1],
			'tags' => ['3', '8'],
			'events' => [],
			'models' => [],
			'failure' => '',
			'beforeSaveData' => [],
		];
	}

	/**
	 * Bound SQL reads/writes while preserving Joomla's actual Table::bind.
	 *
	 * @param   object      $storage     Persisted row and observations.
	 * @param   Dispatcher  $dispatcher  Native table event dispatcher.
	 *
	 * @return  Table
	 * @since   6.2.0
	 */
	private function table(object $storage, Dispatcher $dispatcher): Table
	{
		$table = $this->getStubBuilder(Table::class)->disableOriginalConstructor()->onlyMethods([
			'load', 'store', 'check', 'getFields', 'hasField', 'getKeyName', 'getColumnAlias', 'getDispatcher',
		])->getStub();
		$table->method('getKeyName')->willReturn('id');
		$table->method('getColumnAlias')->willReturnArgument(0);
		$table->method('hasField')->willReturn(false);
		$table->method('getDispatcher')->willReturn($dispatcher);
		$table->method('getFields')->willReturn(array_map(static fn (string $field): object => (object) ['Field' => $field], array_keys($storage->row)));

		foreach ($storage->row as $name => $value)
		{
			$table->{$name} = $name === 'ordering' ? 1 : null;
		}

		$table->method('load')->willReturnCallback(static function ($id) use ($table, $storage): bool
		{
			foreach ($storage->row as $name => $value)
			{
				$table->{$name} = $value;
			}

			return (int) $id === 42;
		});
		$table->method('check')->willReturnCallback(static function () use ($table, $storage): bool
		{
			if ($storage->failure === 'check')
			{
				$table->setError('fixture check');

				return false;
			}

			return true;
		});
		$table->method('store')->willReturnCallback(static function () use ($table, $storage): bool
		{
			$storage->events[] = 'store';

			if ($storage->failure === 'store')
			{
				$table->setError('fixture store');

				return false;
			}

			$table->id = 42;

			foreach (array_keys($storage->row) as $name)
			{
				$storage->row[$name] = $table->{$name};
			}

			if (isset($table->newTags))
			{
				$storage->tags = $table->newTags;
			}

			return true;
		});

		return $table;
	}

	/**
	 * Build real component forms and native plugin events for each model instance.
	 *
	 * @param   object  $storage  Boundary state.
	 * @param   string  $guard    Tag permission/plugin restriction.
	 * @param   User    $user     Request identity.
	 *
	 * @return  GeneratedLifecycleModelFixture
	 * @since   6.2.0
	 */
	private function model(object $storage, string $guard, User $user): GeneratedLifecycleModelFixture
	{
		$form = new Form('lifecycle');
		$form->setDatabase($this->createStub(DatabaseInterface::class));
		$form->setCurrentUser($user);
		$this->assertTrue($form->load('<form>'
			. '<field name="id" type="text" filter="integer" />'
			. '<field name="name" type="text" filter="string" required="true" />'
			. '<field name="code" type="textarea" filter="raw" />'
			. '<field name="options" type="text" filter="raw" />'
			. '<field name="checkbox" type="text" filter="raw" />'
			. '<field name="created" type="text" filter="raw" />'
			. '<field name="created_by" type="text" filter="integer" />'
			. '<field name="tags" type="text" filter="raw" /></form>'));
		$dispatcher = new Dispatcher();
		$dispatcher->addListener('onContentBeforeValidateData', static function ($event) use ($storage, $guard): void
		{
			$storage->events[] = 'validate';

			if ($guard === 'plugin-remove')
			{
				$event->getArgument('subject')->removeField('tags');
			}
			elseif ($guard === 'plugin-unset')
			{
				$event->getArgument('subject')->setFieldAttribute('tags', 'filter', 'unset');
			}
		});
		$dispatcher->addListener('onTableBeforeBind', static function () use ($storage): void
		{
			$storage->events[] = 'bind';

			if ($storage->failure === 'bind')
			{
				throw new RuntimeException('fixture bind');
			}
		});
		$dispatcher->addListener('onContentBeforeSave', static function ($event) use ($storage): void
		{
			$storage->events[] = 'before';
			$storage->beforeSaveData = $event->getArgument('data');

			if ($storage->failure === 'veto')
			{
				$event->getArgument('subject')->setError('fixture veto');
				$event->addResult(false);
			}
		});
		$dispatcher->addListener('onContentAfterSave', static function () use ($storage): void
		{
			$storage->events[] = 'after';
		});
		$tableFactory = fn (): Table => $this->table($storage, $dispatcher);
		$base = new BaseSixFour();
		$base->set('article', ['code']);
		$json = new JsonString();
		$json->set('article', ['options']);
		$checkbox = new CheckBox();
		$checkbox->set('article', ['checkbox']);
		$permissions = new PermissionFields();

		if (in_array($guard, ['edit', 'access', 'view'], true))
		{
			$permissions->set('article', ['tags' => [$guard => 'text']]);
		}

		$view = 'article';
		$overrides = ['basesixfour' => $base, 'jsonstring' => $json];
		$getItem = $this->renderer($this->targetClass('JoomlaSix', 'Model\\GetItemMethod', []), $overrides)->get($view);
		$getForm = $this->renderer($this->targetClass('JoomlaSix', 'Model\\GetForm', ['JoomlaThree']), ['permissionfields' => $permissions])->get('article', 'articles');
		$save = $this->renderer($this->targetClass('JoomlaSix', 'Model\\ItemSave', ['JoomlaThree']), $overrides)->get($view);
		$checkboxSave = $this->renderer($this->targetClass('JoomlaSix', 'Model\\CheckboxSave', []), ['checkbox' => $checkbox])->get($view);
		$template = file_get_contents(dirname(__DIR__, 8) . '/admin/compiler/joomla_4/ADMIN_VIEW_MODEL.php');
		$methods = '';

		foreach (['getItem', 'getForm', 'save', 'prepareTable'] as $method)
		{
			$this->assertSame(1, preg_match('/\t(?:public|protected) function ' . $method . '\([^\n]+\n\t\{.*?\n\t\}/s', $template, $match));
			$methods .= $match[0] . "\n";
		}

		$methods = strtr($methods, [
			'###METHOD_GET_ITEM###' => $getItem,
			'###JMODELADMIN_GETFORM###' => $getForm,
			'###METHOD_ITEM_SAVE###' => $save,
			'###CHECKBOX_SAVE###' => $checkboxSave,
			'###LICENSE_LOCKED_CHECK###' => '',
			'###LICENSE_TABLE_LOCKED_CHECK###' => '',
			'###ADMIN_VIEW_MODEL_ITEM_ACCESS###' => '',
			'###LINKEDVIEWGLOBAL###' => '',
			'###TITLEALIASFIX###' => '',
			'###component###' => 'demo',
			'###view###' => 'article',
		]);
		$methods = strtr($methods, [
			'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getDate()' => "new \\Joomla\\CMS\\Date\\Date('2026-10-07 12:00:00')",
			'Joomla___193deb3e_0c3e_4610_8e55_450e463095b4___Power' => '\\Joomla\\CMS\\Filter\\InputFilter',
			'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power' => '\\Joomla\\CMS\\Factory',
			'Joomla___a87c432d_b5b4_428e_b7ff_14b51664c624___Power' => '\\Joomla\\Registry\\Registry',
			'Text::_(' => '\\Joomla\\CMS\\Language\\Text::_(',
		]);
		preg_match_all('/###[A-Za-z_]+###/', $methods, $unresolved);
		$this->assertSame([], $unresolved[0]);
		$model = eval('return new class($tableFactory, $form, $dispatcher, $storage) extends \\' . GeneratedLifecycleModelFixture::class . ' {' . $methods . '};');
		$model->setCurrentUser($user);
		$storage->models[] = $model;

		return $model;
	}

	/**
	 * Execute the maintained API controller over fresh generated model instances.
	 *
	 * @param   object  $storage  Boundary state.
	 * @param   array   $payload  Original client body.
	 * @param   string  $guard    Field permission or validation-plugin guard.
	 * @param   string  $method   HTTP request method.
	 * @param   bool    $api      API client flag.
	 *
	 * @return  ApiController
	 * @since   6.2.0
	 */
	private function controller(object $storage, array $payload, string $guard = '', string $method = 'PATCH', bool $api = true): ApiController
	{
		$input = new Input(['id' => 42, 'data' => $payload]);
		$input->server->set('REQUEST_METHOD', $method);
		$user = $this->createStub(User::class);
		$user->id = 7;
		$user->method('authorise')->willReturnCallback(static fn (string $action): bool => $action !== 'article.' . $guard . '.tags');
		$app = $this->createStub(CMSApplication::class);
		$app->method('getInput')->willReturn($input);
		$app->method('getIdentity')->willReturn($user);
		$app->method('isClient')->willReturnCallback(static fn (string $client): bool => $api && $client === 'api');
		$this->setJoomlaFactoryProperty('application', $app);
		$modelFactory = fn (): GeneratedLifecycleModelFixture => $this->model($storage, $guard, $user);
		$template = file_get_contents(dirname(__DIR__, 8) . '/admin/compiler/joomla_4/API_VIEW_CONTROLLER.php');
		$template = substr($template, strpos($template, '###BOM###'));
		$class = 'VDM\\LifecycleContract\\Component\\Demo\\Api\\Controller\\ArticlesController';

		if (!class_exists($class, false))
		{
			$code = strtr($template, [
				'###BOM###' => '',
				'###NAMESPACEPREFIX###' => 'VDM\\LifecycleContract',
				'###ComponentNamespace###' => 'Demo',
				'###View###' => 'Articles',
				'###view###' => 'article',
				'###views###' => 'articles',
				'###API_VIEW_CONTROLLER_HEADER###' => 'use Joomla\\CMS\\MVC\\Controller\\ApiController; use Joomla\\CMS\\Language\\Text;',
				'###API_VIEW_CONTROLLER_GETMODEL###' => 'return ($this->modelFactory)();',
				'###API_VIEW_CONTROLLER_RECORDID###' => $this->renderer(RecordId::class)->get('article'),
				'###API_VIEW_CONTROLLER_ALLOWVIEW###' => 'return true;',
				'###JCONTROLLERFORM_ALLOWADD###' => 'return true;',
				'###JCONTROLLERFORM_ALLOWEDIT###' => 'return true;',
				'###API_VIEW_CONTROLLER_ALLOWDELETE###' => 'return true;',
			]);
			$this->assertStringNotContainsString('###', $code);
			eval($code);
		}

		$harness = <<<'PHP'
return new class($modelFactory, $input) extends GENERATED_CONTROLLER
{
	/**
	 * Fresh generated model factory, matching Joomla MVC model selection.
	 * @var    \Closure
	 * @since  6.2.0
	 */
	protected \Closure $modelFactory;

	/**
	 * Supply request and component lookup boundaries.
	 * @param   \Closure              $factory  Model factory.
	 * @param   \Joomla\Input\Input  $input    Original request.
	 * @since   6.2.0
	 */
	public function __construct(\Closure $factory, \Joomla\Input\Input $input)
	{
		$this->modelFactory = $factory;
		$this->input = $input;
		$this->option = 'com_demo';
	}

	/**
	 * Bound response serialization after the native save finishes.
	 * @param   int|null  $id  Record identifier.
	 * @return  static
	 * @since   6.2.0
	 */
	public function displayItem($id = null)
	{
		return $this;
	}
};
PHP;

		return eval(str_replace('GENERATED_CONTROLLER', '\\' . $class, $harness));
	}
}
