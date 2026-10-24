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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use InvalidArgumentException;
use Joomla\CMS\Event\Privacy\CanRemoveDataEvent;
use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\CMS\User\User;
use Joomla\Component\Privacy\Administrator\Export\Domain;
use Joomla\Component\Privacy\Administrator\Removal\Status;
use Joomla\Component\Privacy\Administrator\Table\RequestTable;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Plugin\Extension;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Plugin\Privacy;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitecturePlugin;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Power\Parser;


/**
 * Execute the bundled privacy definition through the native event and Power paths.
 *
 * @since 6.2.0
 */
#[CoversClass(Privacy::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Power')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class JoomlaSixPluginPrivacyTest extends ArchitectureTestCase
{
	/**
	 * Unique generated runtime names.
	 *
	 * @var int
	 * @since 6.2.0
	 */
	private static int $fixture = 0;

	/**
	 * Load only the core data contracts; no installed application or database runs.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		foreach (['Plugin/PrivacyPlugin', 'Table/RequestTable', 'Removal/Status', 'Export/Domain'] as $class)
		{
			require_once JCB_JOOMLA_ROOT . '/administrator/components/com_privacy/src/' . $class . '.php';
		}
	}

	/**
	 * Null users retain default results; callbacks receive and replace values by reference.
	 *
	 * @param string $indentation The configured indentation unit.
	 * @param bool   $debug       Whether generated comments include their compiler origin.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('formattingProvider')]
	public function testNativeDispatchPreservesDefaultResultsAndHelperReferences(string $indentation, bool $debug): void
	{
		(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, $indentation);
		(new ReflectionProperty(Indent::class, 'bucket'))->setValue(null, []);
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, $debug);
		[$instance, $helper] = $this->compile();
		$dispatcher = new Dispatcher();
		$dispatcher->addSubscriber($instance);
		$request = $this->createStub(RequestTable::class);
		$emptyRemoval = new CanRemoveDataEvent('onPrivacyCanRemoveData', ['subject' => $request, 'user' => null]);
		$emptyExport = new ExportRequestEvent('onPrivacyExportRequest', ['subject' => $request, 'user' => null]);
		$emptyRemove = new RemoveDataEvent('onPrivacyRemoveData', ['subject' => $request, 'user' => null]);

		foreach ([$emptyRemoval, $emptyExport, $emptyRemove] as $event)
		{
			$dispatcher->dispatch($event->getName(), $event);
		}

		$this->assertCount(1, $emptyRemoval->getArgument('result', []));
		$this->assertInstanceOf(Status::class, $emptyRemoval->getArgument('result', [])[0]);
		$this->assertTrue($emptyRemoval->getArgument('result', [])[0]->canRemove);
		$this->assertSame([[]], $emptyExport->getArgument('result', []));
		$this->assertSame([], $helper::$calls);
		$this->assertFalse($emptyRemove->hasArgument('result'));
		$user = $this->createStub(User::class);
		$removal = new CanRemoveDataEvent('onPrivacyCanRemoveData', ['subject' => $request, 'user' => $user]);
		$export = new ExportRequestEvent('onPrivacyExportRequest', ['subject' => $request, 'user' => $user]);
		$remove = new RemoveDataEvent('onPrivacyRemoveData', ['subject' => $request, 'user' => $user]);

		foreach ([$removal, $export, $remove] as $event)
		{
			$dispatcher->dispatch($event->getName(), $event);
		}

		$this->assertCount(1, $removal->getArgument('result', []));
		$this->assertFalse($removal->getArgument('result', [])[0]->canRemove);
		$this->assertSame('retained', $removal->getArgument('result', [])[0]->reason);
		$this->assertCount(1, $export->getArgument('result', []));
		$this->assertCount(1, $export->getArgument('result', [])[0]);
		$this->assertInstanceOf(Domain::class, $export->getArgument('result', [])[0][0]);
		$this->assertFalse($remove->hasArgument('result'));
		$this->assertSame([
			['onPrivacyCanRemoveData', $instance, $request, $user],
			['onPrivacyExportRequest', $instance, $request, $user],
			['onPrivacyRemoveData', $instance, $request, $user],
		], $helper::$calls);
	}

	/**
	 * Helper exceptions must propagate without replacing them or adding success results.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testHelperExceptionsPropagateUnchanged(): void
	{
		[$instance, $helper] = $this->compile();
		$dispatcher = new Dispatcher();
		$dispatcher->addSubscriber($instance);
		$failure = new RuntimeException('privacy helper failed');
		$helper::$failure = $failure;
		$args = ['subject' => $this->createStub(RequestTable::class), 'user' => $this->createStub(User::class)];

		foreach ([new CanRemoveDataEvent('onPrivacyCanRemoveData', $args), new ExportRequestEvent('onPrivacyExportRequest', $args), new RemoveDataEvent('onPrivacyRemoveData', $args)] as $event)
		{
			try
			{
				$dispatcher->dispatch($event->getName(), $event);
				$this->fail('Expected the helper exception.');
			}
			catch (RuntimeException $exception)
			{
				$this->assertSame($failure, $exception);
			}

			$this->assertSame([], $event->getArgument('result', []));
		}
	}

	/**
	 * A component with no privacy helper methods still contributes the same defaults.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testMissingHelperMethodsKeepDefaultResults(): void
	{
		[$instance] = $this->compile(false);
		$args = ['subject' => $this->createStub(RequestTable::class), 'user' => $this->createStub(User::class)];
		$removal = new CanRemoveDataEvent('onPrivacyCanRemoveData', $args);
		$export = new ExportRequestEvent('onPrivacyExportRequest', $args);
		$remove = new RemoveDataEvent('onPrivacyRemoveData', $args);
		$instance->onPrivacyCanRemoveData($removal);
		$instance->onPrivacyExportRequest($export);
		$instance->onPrivacyRemoveData($remove);
		$this->assertCount(1, $removal->getArgument('result', []));
		$this->assertTrue($removal->getArgument('result', [])[0]->canRemove);
		$this->assertSame([[]], $export->getArgument('result', []));
		$this->assertFalse($remove->hasArgument('result'));
	}

	/**
	 * Reviewed identities never grant permission to rewrite edited user logic.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function alteredDefinitions(): iterable
	{
		yield 'changed body' => ['body'];
		yield 'changed signature' => ['signature'];
		yield 'unknown definition identity' => ['identity'];
	}

	/**
	 * @param string $change The incompatible definition change.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('alteredDefinitions')]
	public function testUnknownContractsKeepTheMigrationDiagnostic(string $change): void
	{
		$plugin = $this->definition();

		if ($change === 'body')
		{
			$plugin->main_class_code = str_replace('return $status;', '$status->canRemove = false; return $status;', $plugin->main_class_code);
		}
		elseif ($change === 'signature')
		{
			$plugin->main_class_code = str_replace('$user = null)', '$user)', $plugin->main_class_code);
		}
		else
		{
			$plugin->method_selection = '{}';
		}

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('::onPrivacyCanRemoveData()');
		$this->renderer(Extension::class)->get($plugin);
	}

	/**
	 * Selection is build-local; original source and surrounding GUI markers survive.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testProviderIsolationAndSurroundingSourcePreservation(): void
	{
		$plugin = $this->definition();
		$original = $plugin->main_class_code;
		$prefix = "// [JCBGUI.main_class_code.66.START]\n";
		$suffix = "\n// [JCBGUI.main_class_code.66.END]\n\tprivate function untouched(): string { return 'public function onPrivacyRemoveData() {}'; }";
		$plugin->main_class_code = $prefix . $original . $suffix;

		foreach ([6, 5, 4, 3, 6] as $major)
		{
			$container = new Container();
			$container->set('Config', (object) ['joomla_version' => $major]);
			$container->set('Placeholder', $this->placeholder());
			$container->set('Compiler.Builder.Content.One', new ContentOne());
			$container->set('Power.Parser', new Parser());
			$container->set('Joomla.Power', $this->createStub(PowerInterface::class));
			$container->registerServiceProvider(new ArchitecturePlugin());
			$code = $container->get('Architecture.Plugin.Extension')->get($plugin);
			$this->assertStringContainsString($prefix, $code);
			$this->assertStringContainsString($suffix, $code);

			if ($major === 6)
			{
				$this->assertStringContainsString('CanRemoveDataEvent $event): void', $code);
				$this->assertStringContainsString('Joomla___aebfeb9f_f8a3_42be_a21d_5db56ae30c1c___Power::onPrivacyCanRemoveData', $code);
				$this->assertStringNotContainsString('return $status;', $code);
				$this->assertSame($code, $container->get('Architecture.Plugin.Extension')->get($plugin));
			}
			else
			{
				$this->assertStringContainsString($original, $code);
				$this->assertStringNotContainsString('CanRemoveDataEvent $event', $code);
			}
		}

		$this->assertSame($prefix . $original . $suffix, $plugin->main_class_code);
		$renderer = new Privacy();
		$this->assertSame('', $renderer->get((object) [], ''));
		$native = $renderer->get($plugin, $plugin->main_class_code);
		$this->assertSame($native, $renderer->get($plugin, $native));
	}

	/**
	 * Compiler-owned method formatting follows the configured utilities without touching neighbors.
	 *
	 * @param string $indentation The configured indentation unit.
	 * @param bool   $debug       Whether generated comments include their compiler origin.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('formattingProvider')]
	public function testNativeMethodsUseConfiguredIndentationAndCommentOrigins(string $indentation, bool $debug): void
	{
		(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, $indentation);
		(new ReflectionProperty(Indent::class, 'bucket'))->setValue(null, []);
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, $debug);
		$plugin = $this->definition();
		$prefix = "// [JCBGUI.main_class_code.66.START]\n";
		$suffix = "\n// [JCBGUI.main_class_code.66.END]\n\tprivate function untouched(): string { return '\\t user text'; }";
		$source = $prefix . $plugin->main_class_code . $suffix;
		$code = (new Privacy())->get($plugin, $source);
		$this->assertStringStartsWith($prefix, $code);
		$this->assertStringEndsWith($suffix, $code);
		$comment = $debug ? ' \\[' . preg_quote(Privacy::class, '~') . ' [0-9]+\\]' : '';
		$opening = '~^' . preg_quote($indentation, '~') . '/\\*\\*\\R'
			. preg_quote($indentation, '~') . ' \\*' . $comment
			. ' Process the privacy request through the component helper\\.$~m';
		$this->assertSame(3, preg_match_all($opening, $code));

		foreach (['onPrivacyCanRemoveData', 'onPrivacyExportRequest', 'onPrivacyRemoveData'] as $name)
		{
			$this->assertStringContainsString(PHP_EOL . $indentation . 'public function ' . $name . '(', $code);
			$this->assertStringContainsString(PHP_EOL . str_repeat($indentation, 3)
				. 'Joomla___aebfeb9f_f8a3_42be_a21d_5db56ae30c1c___Power::' . $name . '(', $code);
		}

		$this->assertSame(3, substr_count($code, PHP_EOL . str_repeat($indentation, 2) . '$request = $event->getRequest();'));
		$this->assertSame(2, substr_count($code, PHP_EOL . str_repeat($indentation, 2) . '$event->addResult('));
		$this->assertSame($source, $prefix . $plugin->main_class_code . $suffix);
	}

	/**
	 * Configured output formatting must also preserve executable native event behavior.
	 *
	 * @return iterable
	 * @since 6.2.0
	 */
	public static function formattingProvider(): iterable
	{
		yield 'tabs' => ["\t", false];
		yield 'spaces' => ['    ', false];
		yield 'tabs with origins' => ["\t", true];
		yield 'spaces with origins' => ['    ', true];
	}

	/**
	 * Decode the actual distributed default, so fixture drift cannot hide this regression.
	 *
	 * @return object
	 * @since 6.2.0
	 */
	private function definition(): object
	{
		foreach (file(JCB_PROJECT_ROOT . '/admin/sql/install.mysql.utf8.sql') as $line)
		{
			if (str_contains($line, "'8aa96d76-94e3-47d1-8dd8-f430b72ed0f7'"))
			{
				$row = str_getcsv(substr(trim($line), 1, -2), ',', "'", '\\');

				return (object) [
					'comment' => '/** Bundled privacy plugin. */',
					'class_name' => 'NativePrivacyFixture' . ++self::$fixture,
					'extends' => 'PrivacyPlugin',
					'header' => base64_decode(trim($row[18])),
					'method_selection' => stripslashes(trim($row[20])),
					'main_class_code' => base64_decode(trim($row[21])),
				];
			}
		}

		throw new RuntimeException('The distributed privacy definition was not found.');
	}

	/**
	 * Compile Power imports and execute handlers without starting a Joomla application.
	 *
	 * @param bool $callbacks Whether the component helper implements the privacy hooks.
	 *
	 * @return array
	 * @since 6.2.0
	 */
	private function compile(bool $callbacks = true): array
	{
		$plugin = $this->definition();
		$helperName = 'PrivacyHelper' . self::$fixture;
		$helper = __NAMESPACE__ . '\\' . $helperName;
		$body = $callbacks ? <<<'PHP'
public static $calls = [];
public static $failure = null;
private static function record($name, $plugin, $request, $user): void
{
	self::$calls[] = [$name, $plugin, $request, $user];
	if (self::$failure) { throw self::$failure; }
}
public static function onPrivacyCanRemoveData($plugin, &$status, $request, $user): void
{
	self::record(__FUNCTION__, $plugin, $request, $user);
	$status = new \Joomla\Component\Privacy\Administrator\Removal\Status();
	$status->canRemove = false;
	$status->reason = 'retained';
}
public static function onPrivacyExportRequest($plugin, &$domains, $request, $user): void
{
	self::record(__FUNCTION__, $plugin, $request, $user);
	$domains = [new \Joomla\Component\Privacy\Administrator\Export\Domain()];
}
public static function onPrivacyRemoveData($plugin, $request, $user): void
{
	self::record(__FUNCTION__, $plugin, $request, $user);
}
PHP : '';
		eval('namespace ' . __NAMESPACE__ . '; class ' . $helperName . ' {' . $body . '}');
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['c06c5116-6b9d-487c-9b09-5094ec4506a3', 0, (object) ['_namespace' => 'Joomla\\Event', 'class_name' => 'SubscriberInterface', 'type' => 'interface']],
			['5c599ecf-8f58-44b4-bbaf-a47eb5d302e5', 0, (object) ['_namespace' => 'Joomla\\Component\\Privacy\\Administrator\\Removal', 'class_name' => 'Status', 'type' => 'class']],
			['aebfeb9f-f8a3-42be-a21d-5db56ae30c1c', 0, (object) ['_namespace' => __NAMESPACE__, 'class_name' => $helperName, 'type' => 'class']],
		]);
		$source = $this->renderer(Extension::class, ['power' => $power])->get($plugin);
		$injector = new Injector($power, new Extractor($this->createStub(DatabaseInterface::class), 6), new Parser(), $this->placeholder());
		$code = $injector->power('<?php' . PHP_EOL . 'namespace ' . __NAMESPACE__ . ';' . PHP_EOL . $plugin->header . PHP_EOL . $source);
		$this->assertStringNotContainsString('Joomla___', $code);
		$this->assertStringNotContainsString('registerListeners', $code);
		eval(substr($code, strlen('<?php')));
		$class = new ReflectionClass(__NAMESPACE__ . '\\' . $plugin->class_name);

		// Parent construction loads application language/database state, outside this event unit.
		return [$class->newInstanceWithoutConstructor(), $helper];
	}
}
