<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    23rd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Component\JoomlaSix;


use Closure;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Access\Rules;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Language\Language;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Mysqli\MysqliQuery;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Session\SessionInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Psr\Log\NullLogger;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor as JoomlaPowerExtractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector as JoomlaPowerInjector;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Power\Extractor as PowerExtractor;
use VDM\Joomla\Componentbuilder\Compiler\Power\Injector as PowerInjector;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Execute generated Joomla 6 MVC code with the real Joomla Power injectors.
 *
 * @since  6.2.0
 */
#[CoversNothing]
final class MvcTemplatesTest extends JoomlaTestCase
{
	/**
	 * Distinct generated namespaces within the current PHPUnit process.
	 *
	 * @var   int
	 * @since 6.2.0
	 */
	private static int $sequence = 0;

	/**
	 * Native table schema cache restored after each generated fixture.
	 *
	 * @var   array|null
	 * @since 6.2.0
	 */
	private ?array $tableFields = null;

	/**
	 * Isolate native table discovery state as well as Factory services.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$property = new ReflectionProperty(Table::class, 'tableFields');
		$this->tableFields = $property->getValue();
		$property->setValue(null, []);
	}

	/**
	 * Restore native schema state for randomized test order.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	protected function tearDown(): void
	{
		(new ReflectionProperty(Table::class, 'tableFields'))->setValue(null, $this->tableFields);
		parent::tearDown();
	}


	/**
	 * Model templates whose identity used to be resolved in the constructor.
	 *
	 * @return  array<string, array{string, string, string}>
	 * @since   6.2.0
	 */
	public static function identityModels(): array
	{
		return [
			'site item' => ['SITE_VIEW_MODEL', 'Site', 'Record'],
			'site list' => ['SITE_VIEWS_MODEL', 'Site', 'Records'],
			'custom admin item' => ['CUSTOM_ADMIN_VIEW_MODEL', 'Administrator', 'Record'],
			'custom admin list' => ['CUSTOM_ADMIN_VIEWS_MODEL', 'Administrator', 'Records'],
			'dashboard' => ['DASH_MODEL', 'Administrator', 'Demo'],
		];
	}

	/**
	 * Native controller injection refreshes every identity snapshot and user cache.
	 *
	 * @param   string  $template  Compiler template.
	 * @param   string  $client    MVC client namespace.
	 * @param   string  $name      Model name.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('identityModels')]
	public function testIdentityIsResolvedAfterConstruction(string $template, string $client, string $name): void
	{
		$user = $this->identity(41, [2, 8]);
		$app = $this->createMock(CMSApplication::class);
		$input = new Input();
		$app->method('getInput')->willReturn($input);
		$app->method('getLogger')->willReturn(new NullLogger());
		// BaseController checks then injects the identity. The constructor must not read it.
		$app->expects($this->exactly(2))->method('getIdentity')->willReturn($user);
		$this->setJoomlaApplication($app);
		[$class, $factory] = $this->modelClass($template, $client, $name);
		$controller = new BaseController(['name' => 'demo', 'base_path' => JPATH_ROOT], $factory, $app, $input);
		$model = $controller->getModel($name, $client, ['dbo' => null, 'ignore_request' => true]);
		$this->assertInstanceOf($class, $model);
		$this->assertSame($user, $this->property($model, 'user'));

		if ($template !== 'DASH_MODEL')
		{
			$this->assertSame(41, $this->property($model, 'userId'));
			$this->assertSame([2, 8], $this->property($model, 'groups'));
			$this->assertSame([2, 8], $model->authorisedGroups);
			$this->assertSame([1, 3], $this->property($model, 'levels'));
			$cache = str_ends_with($template, 'VIEWS_MODEL') ? 'cache' : '_item';
			(new ReflectionProperty($model, $cache))->setValue($model, [41 => 'private item']);
		}

		$other = $this->identity(99, [7]);
		$model->setCurrentUser($other);
		$this->assertSame($other, $this->property($model, 'user'));

		if ($template !== 'DASH_MODEL')
		{
			$this->assertSame(99, $this->property($model, 'userId'));
			$this->assertSame([7], $this->property($model, 'groups'));
			$this->assertEmpty($this->property($model, $cache));
		}
	}

	/**
	 * Item query failures retain the original exception, including redirects/404.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testItemFailuresAreNotStoredAndSilentlyReturnedAsFalse(): void
	{
		$app = $this->createStub(CMSApplication::class);
		$app->method('getInput')->willReturn(new Input());
		$this->setJoomlaApplication($app);

		foreach (['SITE_VIEW_MODEL', 'CUSTOM_ADMIN_VIEW_MODEL'] as $template)
		{
			$client = $template === 'SITE_VIEW_MODEL' ? 'Site' : 'Administrator';
			$placeholder = $template === 'SITE_VIEW_MODEL' ? 'SITE_GET_ITEM' : 'CUSTOM_ADMIN_GET_ITEM';
			[$class, $factory] = $this->modelClass($template, $client, 'Record', [
				'###' . $placeholder . '###' => PHP_EOL . "\t\t\t" . 'throw new \\RuntimeException("query failed", $pk);',
			]);
			$model = new $class(['dbo' => null, 'ignore_request' => true], $factory);
			$model->setCurrentUser($this->identity(41, [2]));

			foreach ([404, 503] as $code)
			{
				try
				{
					$model->getItem($code);
					$this->fail('The model swallowed the query failure.');
				}
				catch (RuntimeException $error)
				{
					$this->assertSame($code, $error->getCode());
					$this->assertSame('query failed', $error->getMessage());
				}
			}
		}
	}

	/**
	 * The inherited database getter respects both initial and later injection.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTablesKeepTheirOwnInjectedDatabase(): void
	{
		$class = $this->render('ADMIN_VIEW_TABLE', 'Administrator\\Table\\RecordTable');
		$first = $this->database();
		$second = $this->database();
		$a = new $class($first, new Dispatcher());
		$b = new $class($second, new Dispatcher());
		$getDatabase = static fn (Table $table): DatabaseInterface => Closure::bind(
			function (): DatabaseInterface
			{
				return $this->getDatabase();
			}, $table, $table
		)();
		$this->assertSame($first, $getDatabase($a));
		$this->assertSame($second, $getDatabase($b));
		$a->setDatabase($second);
		$this->assertSame($second, $getDatabase($a));
		$a->setRules(new Rules());
		$a->published = 0;
		$this->assertTrue($a->check());
	}

	/**
	 * Unique aliases throw the precise existing message without persisting data.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testTableAliasFailuresUseExceptionsAndTheInjectedDatabase(): void
	{
		$language = $this->createStub(Language::class);
		$language->method('_')->willReturnArgument(0);
		$this->setJoomlaFactoryProperty('language', $language);
		$class = $this->render('ADMIN_VIEW_TABLE', 'Administrator\\Table\\RecordTable');

		foreach ([1 => 'COM_DEMO_RECORD_ERROR_UNIQUE_ALIAS', -2 => 'COM_DEMO_RECORD_ERROR_UNIQUE_ALIAS_TRASHED'] as $published => $message)
		{
			$db = $this->database(['id', 'alias', 'published', 'created', 'created_by', 'modified', 'modified_by']);
			$db->method('loadAssoc')->willReturn(['id' => 42, 'alias' => 'taken', 'published' => $published]);
			$db->method('updateObject')->willReturnCallback(function (): bool
			{
				$this->fail('A conflicting alias must not be stored.');
			});
			$table = new $class($db, new Dispatcher());
			$table->id = 7;
			$table->alias = 'taken';
			$table->setCurrentUser($this->identity(41, [2]));

			try
			{
				$table->store();
				$this->fail('A conflicting alias must throw.');
			}
			catch (RuntimeException $error)
			{
				$this->assertSame($message, $error->getMessage());
			}
		}
	}

	/**
	 * Helper asset failures remain warnings and cannot update an unrelated row.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testHelperAssetDatabaseFailuresRemainWarnings(): void
	{
		$db = $this->database(['id', 'name', 'parent_id', 'lft', 'rgt', 'level', 'title', 'rules']);
		$db->method('loadAssoc')->willThrowException(new RuntimeException('asset database unavailable'));
		$db->method('updateObject')->willReturnCallback(function (): bool
		{
			$this->fail('Failed asset persistence must not update the component row.');
		});
		$container = new Container();
		$container->set(DatabaseInterface::class, $db);
		$this->setJoomlaContainer($container);
		$app = $this->createMock(CMSApplication::class);
		$app->method('getDispatcher')->willReturn(new Dispatcher());
		$app->expects($this->exactly(2))->method('enqueueMessage')->with('asset database unavailable', 'warning');
		$this->setJoomlaApplication($app);

		foreach (['ADMIN_HELPER_CLASS', 'SITE_HELPER_CLASS'] as $template)
		{
			$code = '<?php' . PHP_EOL . 'use Joomla\\Database\\DatabaseInterface;' . PHP_EOL
				. 'return new class {' . $this->method($template, 'setAsset') . '};';
			$code = str_replace('###component###', 'demo', $code);
			$helper = eval(substr($this->inject($code), 5));
			$this->assertFalse($helper::setAsset(7, 'record'));
		}
	}

	/**
	 * Core list failures reach the view before pagination, toolbar or rendering.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNativeViewExceptionModeDoesNotSwallowInheritedListFailures(): void
	{
		$database = $this->database();
		$database->method('loadObjectList')->willThrowException(new RuntimeException('read failed'));
		$factory = $this->createStub(MVCFactoryInterface::class);
		$model = new ListModel(['name' => 'records', 'dbo' => null, 'ignore_request' => true], $factory);
		$model->setDatabase($database);
		$model->setState('list.start', 0);
		$model->setState('list.limit', 0);
		$template = $this->template('ADMIN_VIEWS_HTML');
		$this->assertSame(1, preg_match('/\$model = \$this->getModel\(\);.*?\$this->items = \$model->getItems\(\);/s', $template, $match));
		$view = new class($model)
		{
			/**
			 * Items populated by the generated display prefix.
			 * @var   mixed
			 * @since 6.2.0
			 */
			public mixed $items = null;

			/**
			 * Native model under the generated display prefix.
			 * @var   ListModel
			 * @since 6.2.0
			 */
			private ListModel $model;

			/**
			 * Supply the native model.
			 * @param   ListModel  $model  Model to retrieve.
			 * @since   6.2.0
			 */
			public function __construct(ListModel $model)
			{
				$this->model = $model;
			}

			/**
			 * Resolve the same native model used by a real HTML view.
			 * @return  ListModel
			 * @since   6.2.0
			 */
			public function getModel(): ListModel
			{
				return $this->model;
			}
		};
		$display = Closure::bind(eval('return function (): void {' . $match[0] . '};'), $view, $view);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('read failed');
		$display();
	}

	/**
	 * Helpers inject an identity when they bypass the normal controller factory.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testHelperCreatedModelsReceiveTheCurrentIdentity(): void
	{
		$app = $this->createStub(CMSApplication::class);
		$user = $this->identity(27, [2]);
		$app->method('getIdentity')->willReturn($user);
		$app->method('getInput')->willReturn(new Input());
		$this->setJoomlaApplication($app);
		[$modelClass, $factory] = $this->modelClass('SITE_VIEW_MODEL', 'Site', 'Record');
		$component = $this->createStub(MVCComponent::class);
		$component->method('getMVCFactory')->willReturn($factory);
		$app->method('bootComponent')->willReturn($component);

		$languageTag = new ReflectionProperty(\VDM\Joomla\Utilities\StringHelper::class, 'langTag');
		$previousTag = $languageTag->getValue();
		$languageTag->setValue(null, 'en-GB');

		try
		{
			foreach (['ADMIN_HELPER_CLASS', 'SITE_HELPER_CLASS'] as $template)
			{
				$code = '<?php' . PHP_EOL . 'use Joomla\\CMS\\User\\CurrentUserInterface;' . PHP_EOL
					. 'return new class {' . $this->method($template, 'getModel')
					. $this->method($template, 'getPrefixFromModelPath') . '};';
				$helper = eval(substr($this->inject($code), 5));
				$model = $helper::getModel('record', 'Site', 'demo', ['dbo' => null, 'ignore_request' => true]);
				$this->assertInstanceOf($modelClass, $model);
				$this->assertSame($user, $this->property($model, 'user'));
				$this->assertSame(27, $this->property($model, 'userId'));
			}
		}
		finally
		{
			$languageTag->setValue(null, $previousTag);
		}

	}

	/**
	 * API deletion preserves response statuses and never drains deprecated errors.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testApiDeletePreservesSuccessAndFailureBoundaries(): void
	{
		$language = $this->createStub(Language::class);
		$language->method('_')->willReturnArgument(0);
		$this->setJoomlaFactoryProperty('language', $language);

		foreach (['success' => 204, 'missing' => 404, 'missing-after-load' => 404, 'conflict' => 409, 'denied' => 403, 'failure' => 500] as $outcome => $status)
		{
			$table = $this->createStub(Table::class);
			$table->method('load')->willReturn($outcome !== 'missing');
			$model = $this->getMockBuilder(AdminModel::class)->disableOriginalConstructor()
				->onlyMethods(['getForm', 'getTable', 'delete', 'getError'])->getMock();
			$model->method('getTable')->willReturn($table);
			$model->expects($this->never())->method('getError');
			$model->method('delete')->willReturnCallback(function () use ($model, $outcome): bool
			{
				$this->assertTrue($model->shouldUseExceptions());

				if ($outcome === 'failure')
				{
					$model->setError('delete failed');
				}

				return $outcome === 'success';
			});
			$session = $this->createStub(SessionInterface::class);
			$session->method('get')->willReturnCallback(static fn (string $key): bool => ($outcome === 'conflict' && $key === 'http_status_code_409')
				|| ($outcome === 'missing-after-load' && $key === 'http_status_code_404'));
			$app = $this->createMock(CMSApplication::class);
			$app->method('getSession')->willReturn($session);
			$app->expects($outcome === 'success' ? $this->once() : $this->never())->method('setHeader')->with('status', 204);
			$code = '<?php use Joomla\\CMS\\Language\\Text; use Joomla\\CMS\\MVC\\Controller\\Exception\\ResourceNotFound;'
				. 'use Joomla\\CMS\\Access\\Exception\\NotAllowed; return new class($model, $app) {'
				. 'private $model; private $app; public function __construct($model, $app) {$this->model = $model; $this->app = $app;}'
				. 'public function getModel() {return $this->model;} public function allowDelete() {return true;}'
				. $this->method('API_VIEW_CONTROLLER', 'delete') . '};';
			$controller = eval(substr($code, 5));

			try
			{
				$controller->delete(7);
				$this->assertSame('success', $outcome);
			}
			catch (\Exception $error)
			{
				$this->assertSame($status, $error->getCode(), $outcome . ': ' . $error->getMessage());
			}
		}
	}

	/**
	 * Create a generated model with a real native MVC factory.
	 *
	 * @param   string  $template      Template name.
	 * @param   string  $client        Client namespace.
	 * @param   string  $name          Model name.
	 * @param   array   $replacements  Intentional custom code fixture.
	 *
	 * @return  array{string, MVCFactory}
	 * @since   6.2.0
	 */
	private function modelClass(string $template, string $client, string $name, array $replacements = []): array
	{
		$class = $this->render($template, $client . '\\Model\\' . $name . 'Model', $replacements);
		$factory = new MVCFactory(substr($class, 0, strpos($class, '\\' . $client . '\\')));
		$factory->setDatabase($this->database());
		$factory->setDispatcher(new Dispatcher());

		return [$class, $factory];
	}

	/**
	 * Render the authoritative template and execute its normal namespace imports.
	 *
	 * @param   string  $template      Template name.
	 * @param   string  $suffix        Expected generated class suffix.
	 * @param   array   $replacements  Custom code placeholders.
	 *
	 * @return  string  Generated class name.
	 * @since   6.2.0
	 */
	private function render(string $template, string $suffix, array $replacements = []): string
	{
		$prefix = 'NativeMvcFixture' . ++self::$sequence;
		$code = $this->template($template);
		$code = substr($code, strpos($code, '###BOM###') + strlen('###BOM###'));
		$header = 'use Joomla\\CMS\\MVC\\Factory\\MVCFactoryInterface; use Joomla\\CMS\\MVC\\Model\\ItemModel;'
			. 'use Joomla\\CMS\\MVC\\Model\\ListModel; use Joomla\\CMS\\User\\User;'
			. 'use Joomla\\CMS\\Application\\CMSApplicationInterface; use Joomla\\Input\\Input;';
		$code = strtr($code, $replacements + [
			'###NAMESPACEPREFIX###' => $prefix,
			'###ComponentNamespace###' => 'Demo',
			'###Component###' => 'Demo', '###component###' => 'demo', '###COMPONENT###' => 'DEMO',
			'###SView###' => 'Record', '###SViews###' => 'Records', '###View###' => 'Record', '###Views###' => 'Records',
			'###view###' => 'record', '###VIEW###' => 'RECORD', '###sview###' => 'record', '###sviews###' => 'records',
			'###' . $template . '_HEADER###' => $header,
		]);
		$code = preg_replace('/###[A-Za-z0-9_]+###/', '', $code);
		$this->assertIsString($code);
		eval(substr($this->inject('<?php' . $code), 5));

		return $prefix . '\\Component\\Demo\\' . $suffix;
	}

	/**
	 * Resolve both existing Power conventions, including namespace collision logic.
	 *
	 * @param   string  $code  Generated PHP source.
	 *
	 * @return  string  Resolved PHP source.
	 * @since   6.2.0
	 */
	private function inject(string $code): string
	{
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['39403062-84fb-46e0-bac4-0023f766e827', 0, (object) ['_namespace' => 'Joomla\\CMS', 'class_name' => 'Factory', 'type' => 'class']],
			['ba6326ef-cb79-4348-80f4-ab086082e3c5', 0, (object) ['_namespace' => 'Joomla\\CMS\\Language', 'class_name' => 'Text', 'type' => 'class']],
			['aeb8e463-291f-4445-9ac4-34b637c12dbd', 0, (object) ['_namespace' => 'Joomla\\CMS\\Component', 'class_name' => 'ComponentHelper', 'type' => 'class']],
			['0a59c65c-9daf-4bc9-baf4-e063ff9e6a8a', 0, (object) ['_namespace' => 'VDM\\Joomla\\Utilities', 'class_name' => 'ArrayHelper', 'type' => 'class']],
			['1f28cb53-60d9-4db1-b517-3c7dc6b429ef', 0, (object) ['_namespace' => 'VDM\\Joomla\\Utilities', 'class_name' => 'StringHelper', 'type' => 'class']],
		]);
		$db = $this->createStub(DatabaseInterface::class);
		$placeholder = new Placeholder(new Config(new Input(), new Registry(), new Registry()));
		$code = (new PowerInjector($power, new PowerExtractor($db), new Parser(), $placeholder))->power($code);
		$code = (new JoomlaPowerInjector($power, new JoomlaPowerExtractor($db, 6), new Parser(), $placeholder))->power($code);
		$this->assertStringNotContainsString('Joomla___', $code);
		$this->assertStringNotContainsString('Super___', $code);

		return $code;
	}

	/**
	 * Load one source template from the repository root.
	 *
	 * @param   string  $name  Template basename.
	 *
	 * @return  string  Template contents.
	 * @since   6.2.0
	 */
	private function template(string $name): string
	{
		$code = file_get_contents(dirname(JPATH_COMPONENT_ADMINISTRATOR) . '/admin/compiler/joomla_6/' . $name . '.php');
		$this->assertIsString($code);

		return $code;
	}

	/**
	 * Extract a complete source method without substituting its implementation.
	 *
	 * @param   string  $template  Template basename.
	 * @param   string  $name      Method name.
	 *
	 * @return  string  Source method.
	 * @since   6.2.0
	 */
	private function method(string $template, string $name): string
	{
		$this->assertSame(1, preg_match('/\t(?:public|protected)(?: static)? function ' . $name . '\([^{}]+\n\t\{.*?\n\t\}/s', $this->template($template), $match));

		return $match[0];
	}

	/**
	 * Supply database boundaries while retaining real native query objects.
	 *
	 * @param   array  $fields  Native table schema column names.
	 *
	 * @return  DatabaseDriver
	 * @since   6.2.0
	 */
	private function database(array $fields = ['id']): DatabaseDriver
	{
		$db = $this->createStub(DatabaseDriver::class);
		$columns = [];

		foreach ($fields as $field)
		{
			$columns[$field] = (object) ['Field' => $field, 'Type' => 'int', 'Default' => 0];
		}

		$db->method('getTableColumns')->willReturn($columns);
		$db->method('getDateFormat')->willReturn('Y-m-d H:i:s');
		$db->method('createQuery')->willReturnCallback(static fn (): MysqliQuery => new MysqliQuery($db));
		$db->method('setQuery')->willReturnSelf();
		$db->method('getServerType')->willReturn('mysql');
		$db->method('getName')->willReturn('fixture');

		return $db;
	}

	/**
	 * Provide explicit ACL values without database-backed user lookup.
	 *
	 * @param   int    $id      User identifier.
	 * @param   array  $groups  Authorised groups.
	 *
	 * @return  User
	 * @since   6.2.0
	 */
	private function identity(int $id, array $groups): User
	{
		$user = $this->createMock(User::class);
		$user->id = $id;
		$user->guest = 0;
		$user->groups = $groups;
		$user->method('getAuthorisedGroups')->willReturn($groups);
		$user->method('getAuthorisedViewLevels')->willReturn([1, 3]);
		$user->method('authorise')->willReturn(true);
		$user->expects($this->never())->method('get');

		return $user;
	}

	/**
	 * Observe protected identity/cache state without changing its visibility.
	 *
	 * @param   object  $object  Generated model.
	 * @param   string  $name    Property name.
	 *
	 * @return  mixed  Current value.
	 * @since   6.2.0
	 */
	private function property(object $object, string $name): mixed
	{
		return (new ReflectionProperty($object, $name))->getValue($object);
	}
}
