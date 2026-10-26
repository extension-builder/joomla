<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    26th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Plugin;


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Plugin\Routes;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaFive\Plugin\Extension as JoomlaFiveExtension;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Plugin\Extension as JoomlaSixExtension;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueGuid;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueKeys;
use VDM\Joomla\Componentbuilder\Compiler\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Gui;
use VDM\Joomla\Componentbuilder\Compiler\Field;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaFive\Data as JoomlaFiveData;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaSix\Data as JoomlaSixData;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Model\Joomlaplugins;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder\Reverse;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureApi;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitecturePlugin;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Compile a linked stored API placeholder and dispatch its generated native plugin.
 *
 * Database rows are the persistence boundary; association loading, plugin data
 * normalization, placeholders, route emission, subscriptions and Power injection
 * execute their real implementations. This does not boot an installed JCB site.
 *
 * @since 6.2.0
 */
#[CoversClass(Routes::class)]
#[CoversClass(Joomlaplugins::class)]
#[CoversClass(JoomlaFiveData::class)]
#[CoversClass(JoomlaSixData::class)]
#[CoversClass(JoomlaFiveExtension::class)]
#[CoversClass(JoomlaSixExtension::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class LinkedPluginTest extends ArchitectureTestCase
{
	/**
	 * Keep generated classes distinct when PHPUnit repeats formatting fixtures.
	 *
	 * @var int
	 * @since 6.2.0
	 */
	private static int $fixture = 0;

	/**
	 * A placeholder-only record produces a native subscriber with working routes.
	 *
	 * @param bool $debug Whether emitted comments include compiler source locations.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('debugModes')]
	public function testJoomlaSixCompilesTheLinkedPlaceholderAndDispatchesItsRoutes(bool $debug): void
	{
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, $debug);
		[$code, $plugin] = $this->compileLinkedPlugin(6, "\t[[[API_ROUTES_METHOD]]]");
		$this->assertSame(1, substr_count($code, 'function getSubscribedEvents('));
		$this->assertSame(1, substr_count($code, 'function onBeforeApiRoute('));
		$instance = $this->loadPlugin($code, $plugin->class_name);
		$this->assertSame(['onBeforeApiRoute' => 'onBeforeApiRoute'], $instance::getSubscribedEvents());
		$this->assertDispatchedRoutes($instance);
	}

	/**
	 * An authored subscriber map and its custom event survive generated API methods.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testJoomlaSixKeepsTheAuthoredMapWithoutDuplicatingSubscriptions(): void
	{
		$body = <<<'PHP'
	public static function getSubscribedEvents(): array
	{
		return ['onBeforeApiRoute' => ['onBeforeApiRoute', 20], 'custom.audit' => 'audit'];
	}

	public function audit(\Joomla\Event\Event $event): void
	{
		$event->setArgument('checked', true);
	}

	[[[API_ROUTES_METHOD]]]
PHP;
		[$code, $plugin] = $this->compileLinkedPlugin(6, $body);
		$this->assertSame(1, substr_count($code, 'function getSubscribedEvents('));
		$this->assertSame(1, substr_count($code, 'function onBeforeApiRoute('));
		$instance = $this->loadPlugin($code, $plugin->class_name);
		$this->assertSame([
			'onBeforeApiRoute' => ['onBeforeApiRoute', 20],
			'custom.audit' => 'audit',
		], $instance::getSubscribedEvents());
		$this->assertDispatchedRoutes($instance);
		$dispatcher = new Dispatcher();
		$dispatcher->addSubscriber($instance);
		$event = new Event('custom.audit');
		$dispatcher->dispatch('custom.audit', $event);
		$this->assertTrue($event->getArgument('checked'));
	}

	/**
	 * The same stored placeholder retains every byte of the Joomla 5 output.
	 *
	 * @param bool $debug Whether emitted comments include compiler source locations.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('debugModes')]
	public function testJoomlaFiveLinkedPluginOutputRemainsUnchanged(bool $debug): void
	{
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, $debug);
		[$code] = $this->compileLinkedPlugin(5, "\t[[[API_ROUTES_METHOD]]]");
		$fixture = __DIR__ . '/fixtures/joomla5-linked-api' . ($debug ? '-debug' : '') . '.txt';
		$this->assertSame(file_get_contents($fixture), $code);
		$this->assertStringNotContainsString('getSubscribedEvents', $code);
		$this->assertStringNotContainsString('implements SubscriberInterface', $code);
	}

	/**
	 * Guard generated code with compiler debug locations both enabled and disabled.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function debugModes(): iterable
	{
		yield 'without source locations' => [false];
		yield 'with source locations' => [true];
	}

	/**
	 * Follow the component link through the real plugin loader and selected emitter.
	 *
	 * @param int    $target Requested Joomla output target.
	 * @param string $body   Authored main_class_code before base64 storage.
	 *
	 * @return array{string,object}
	 * @since 6.2.0
	 */
	private function compileLinkedPlugin(int $target, string $body): array
	{
		$this->config()->set('joomla_version', $target);
		$this->config()->set('project_author', 'JCB test');
		$this->config()->set('project_website', 'https://example.invalid');
		$this->config()->set('add_placeholders', false);
		$this->setJoomlaApplication($this->createStub(CMSApplication::class));
		$record = (object) [
			'id' => 17,
			'class_id' => 4,
			'guid' => '3a7b223e-0346-48e6-a115-74c16a5de3ba',
			'name' => 'LinkedApi',
			'group' => 'webservices',
			'created' => '2026-10-26 00:00:00',
			'extends' => '\Joomla\CMS\Plugin\CMSPlugin',
			'main_class_code' => base64_encode($body),
			'comment' => base64_encode('/** Linked API plugin fixture. */'),
			'header' => '',
			'add_head' => 0,
			'addreadme' => 0,
			'add_sql' => 0,
			'add_sql_uninstall' => 0,
			'add_update_server' => 0,
			'add_sales_server' => 0,
		];
		$query = $this->createStub(QueryInterface::class);
		foreach (['select', 'from', 'join', 'where'] as $method)
		{
			$query->method($method)->willReturnSelf();
		}
		$database = $this->createMock(DatabaseInterface::class);
		$database->method('quoteName')->willReturnArgument(0);
		$database->method('quote')->willReturnCallback(static fn($value) => "'" . $value . "'");
		$database->expects($this->once())->method('getQuery')->with(true)->willReturn($query);
		$database->expects($this->once())->method('setQuery')->with($query);
		$database->expects($this->once())->method('execute');
		$database->method('getNumRows')->willReturn(1);
		$database->expects($this->once())->method('loadObject')->willReturnCallback(static fn() => clone $record);
		$customcode = $this->createStub(Customcode::class);
		$customcode->method('update')->willReturnArgument(0);
		$gui = new Gui($this->config(), $this->createStub(Reverse::class), new Parser(), $database);
		$data = $this->renderer($target === 6 ? JoomlaSixData::class : JoomlaFiveData::class, [
			'customcode' => $customcode,
			'gui' => $gui,
			'field' => $this->createStub(Field::class),
			'db' => $database,
		]);
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['c06c5116-6b9d-487c-9b09-5094ec4506a3', 0, (object) [
				'_namespace' => 'Joomla\Event', 'class_name' => 'SubscriberInterface', 'type' => 'interface',
			]],
		]);
		$container = new Container();
		$container->share('Config', $this->config());
		$container->share('Placeholder', $this->placeholder());
		$container->share('Compiler.Builder.Content.One', new ContentOne());
		$container->share('Compiler.Builder.Database.Unique.Keys', new DatabaseUniqueKeys());
		$container->share('Compiler.Builder.Database.Unique.Guid', new DatabaseUniqueGuid());
		$container->share('Power.Parser', new Parser());
		$container->share('Joomla.Power', $power);
		$container->registerServiceProvider(new ArchitectureApi());
		$container->registerServiceProvider(new ArchitecturePlugin());
		$component = $this->component($record->guid);
		(new Joomlaplugins($data, $container->get('Architecture.Api.Plugin.Routes')))->set($component);
		$plugin = $data->get($record->guid);
		$this->assertNotNull($plugin);
		$this->assertStringNotContainsString('[[[API_ROUTES_METHOD]]]', $plugin->main_class_code);
		$this->assertSame(base64_encode($body), $record->main_class_code);
		$this->assertSame('admin', $this->config()->build_target);
		$this->assertSame('COM_DEMO', $this->config()->lang_prefix);
		$code = $container->get('Architecture.Plugin.Extension')->get($plugin);
		$injector = new Injector($power, new Extractor($database, $target), new Parser(), $this->placeholder());
		$code = $injector->power('<?php' . PHP_EOL . 'namespace ' . __NAMESPACE__ . ';'
			. PHP_EOL . 'use Joomla\\CMS\\Plugin\\CMSPlugin;'
			. PHP_EOL . $plugin->header . PHP_EOL . $code);
		$this->assertStringNotContainsString('Joomla___', $code);
		$this->assertStringNotContainsString('[[[API_ROUTES_METHOD]]]', $code);

		return [$code, $plugin];
	}

	/**
	 * Supply nonempty admin CRUD routes, a custom-admin read and a public site read.
	 *
	 * @param string $guid The linked API plugin identity.
	 *
	 * @return object
	 * @since 6.2.0
	 */
	private function component(string $guid): object
	{
		return (object) [
			'addjoomla_plugins' => json_encode([['plugin' => $guid]], JSON_THROW_ON_ERROR),
			'admin_views' => [[
				'add_api' => 2,
				'settings' => (object) [
					'name_single' => 'Article', 'name_list' => 'Articles',
					'name_single_code' => 'article', 'name_list_code' => 'articles',
					'fields' => [['field' => 'guid', 'list' => 1, 'settings' => (object) [
						'name' => 'Guid', 'type_name' => 'text', 'datatype' => 'VARCHAR', 'indexes' => 2,
						'xml' => '<field type="text" name="guid" label="Guid" />',
					]]],
				],
			]],
			'custom_admin_views' => [['settings' => (object) [
				'code' => 'report', 'Code' => 'Report',
				'main_get' => (object) ['gettype' => 2, 'main_source' => 1],
			]]],
			'site_views' => [['public_access' => 1, 'settings' => (object) [
				'code' => 'page', 'Code' => 'Page',
				'main_get' => (object) ['gettype' => 1, 'main_source' => 1],
			]]],
		];
	}

	/**
	 * Load emitted PHP with a unique namespace while leaving its class body intact.
	 *
	 * @param string $code  Generated plugin PHP.
	 * @param string $class The emitted class name.
	 *
	 * @return CMSPlugin
	 * @since 6.2.0
	 */
	private function loadPlugin(string $code, string $class): CMSPlugin
	{
		$namespace = __NAMESPACE__ . '\Compiled' . ++self::$fixture;
		$code = str_replace('namespace ' . __NAMESPACE__ . ';', 'namespace ' . $namespace . ';', $code);
		require $this->writeTemporaryFile('linked-plugin-' . self::$fixture . '.php', $code);
		$class = $namespace . '\\' . $class;
		$instance = new $class([]);
		$this->assertInstanceOf(SubscriberInterface::class, $instance);

		return $instance;
	}

	/**
	 * Native dispatch must register actual authenticated CRUD and public read routes.
	 *
	 * @param CMSPlugin $plugin The compiled Joomla 6 subscriber.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	private function assertDispatchedRoutes(CMSPlugin $plugin): void
	{
		$application = $this->createStub(CMSApplication::class);
		$plugin->setApplication($application);
		$dispatcher = new Dispatcher();
		$dispatcher->addSubscriber($plugin);
		$router = new ApiRouter($application);
		$dispatcher->dispatch('onBeforeApiRoute', new BeforeApiRouteEvent('onBeforeApiRoute', [
			'subject' => $application, 'router' => $router,
		]));
		$routes = $router->getRoutes();
		$this->assertCount(11, $routes);
		$guidRoutes = [];
		$reads = [];
		foreach ($routes as $route)
		{
			$this->assertSame('com_demo', $route->getDefaults()['component']);
			if (in_array('GET', $route->getMethods(), true))
			{
				$reads[$route->getController()] = $route->getDefaults()['public'];
			}
			if (preg_match($route->getRegex(), 'v1/demo/articles/guid/3a7b223e-0346-48e6-a115-74c16a5de3ba'))
			{
				$guidRoutes[$route->getMethods()[0]] = $route->getController();
			}
		}
		$this->assertSame([
			'GET' => 'article.displayItem', 'PATCH' => 'article.edit', 'DELETE' => 'article.delete',
		], $guidRoutes);
		$this->assertSame([
			'articles.displayList' => false, 'article.displayItem' => false,
			'report.displayList' => false, 'page.displayItem' => true,
		], $reads);
	}
}
