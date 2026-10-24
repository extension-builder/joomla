<?php
/**
 * @package    Joomla.Component.Builder.Tests
 * @created    23rd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use InvalidArgumentException;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Plugin\Routes;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\Plugin\Extension;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitecturePlugin;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Power\Parser;


/**
 * Execute native plugin output and reject ambiguous legacy definitions before packaging.
 *
 * @since 6.2.0
 */
#[CoversClass(Extension::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Power')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class JoomlaSixPluginExtensionTest extends ArchitectureTestCase
{
	/**
	 * Unique generated class suffix.
	 *
	 * @var int
	 * @since 6.2.0
	 */
	private static int $fixture = 0;

	/**
	 * Generated subscription scaffolding follows the compiler's formatting settings.
	 *
	 * @param string $indentation The configured indentation unit.
	 * @param bool   $debug       Whether generated comments include their compiler origin.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('formattingProvider')]
	public function testSubscriptionsUseConfiguredIndentationAndCommentOrigins(string $indentation, bool $debug): void
	{
		(new ReflectionProperty(Indent::class, 'indent'))->setValue(null, $indentation);
		(new ReflectionProperty(Indent::class, 'bucket'))->setValue(null, []);
		(new ReflectionProperty(Line::class, 'add'))->setValue(null, $debug);
		$body = "\tpublic function onReady(\\Joomla\\Event\\EventInterface \$event): void { \$event->setArgument('ready', true); }";
		$plugin = $this->definition($body);
		$code = $this->renderer(Extension::class)->get($plugin);
		$this->assertStringContainsString($body, $code);
		$this->assertStringContainsString(PHP_EOL . $indentation . 'public static function getSubscribedEvents(): array', $code);
		$this->assertStringContainsString(PHP_EOL . str_repeat($indentation, 2) . "\$events['onReady'] = 'onReady';", $code);
		$this->assertStringContainsString(PHP_EOL . str_repeat($indentation, 3) . '? parent::getSubscribedEvents() : [];', $code);
		$comment = $debug ? ' \\[' . preg_quote(Extension::class, '~') . ' [0-9]+\\]' : '';
		$this->assertMatchesRegularExpression('~^' . preg_quote($indentation, '~') . ' \\*' . $comment . ' Native event subscriptions\\.$~m', $code);
		$instance = $this->compile($plugin);
		$this->assertSame(['onReady' => 'onReady'], $instance::getSubscribedEvents());
		$dispatcher = new Dispatcher();
		$dispatcher->addSubscriber($instance);
		$event = new Event('onReady');
		$dispatcher->dispatch('onReady', $event);
		$this->assertTrue($event->getArgument('ready'));
	}

	/**
	 * Output formatting modes supported by the compiler utilities.
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
	 * Native event results, mutation and failure reach the dispatcher unchanged.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testImplicitNativeHandlersExecuteWithPowerImportsAndOptionalArguments(): void
	{
		$body = <<<'PHP'
	public function onAjaxFixture(Joomla___11111111_2222_3333_4444_555555555555___Power $event, string $suffix = 'ok'): void
	{
		$event->addResult(false);
		$event->addResult($suffix);
	}

	public function onMutate(\joomla\event\eventinterface $event, ...$unused): void
	{
		$event->setArgument('mutated', true);
	}

	public function onFailure(\Joomla\Event\EventInterface $event): void
	{
		throw new \RuntimeException('native failure');
	}

	public function helper(): bool
	{
		return false;
	}
PHP;
		$plugin = $this->definition($body);
		$plugin->header = 'use RuntimeException as SubscriberInterface;';
		$instance = $this->compile($plugin);
		$this->assertInstanceOf(SubscriberInterface::class, $instance);
		$this->assertSame(['onAjaxFixture', 'onMutate', 'onFailure'], array_keys($instance::getSubscribedEvents()));
		$dispatcher = new Dispatcher();
		$dispatcher->addSubscriber($instance);
		$event = new AjaxEvent('onAjaxFixture', ['subject' => $this->createStub(CMSApplication::class)]);
		$dispatcher->dispatch('onAjaxFixture', $event);
		$this->assertSame([false, 'ok'], $event->getEventResult());
		$event = new Event('onMutate');
		$dispatcher->dispatch('onMutate', $event);
		$this->assertTrue($event->getArgument('mutated'));
		$this->expectExceptionMessage('native failure');
		$dispatcher->dispatch('onFailure', new Event('onFailure'));
	}

	/**
	 * Explicit maps retain aliases, priorities and deliberate untyped native handlers.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testExplicitMapRemainsAuthoritative(): void
	{
		$body = <<<'PHP'
	public static function getSubscribedEvents($optional = null): /* native map */ array
	{
		return ['custom.event' => ['gather', 20]];
	}

	public function gather($event, $optional = null): void
	{
		$event->setArgument('order', [...$event->getArgument('order', []), 'plugin']);
	}

	public function onUnsubscribed($context, $value): bool
	{
		return false;
	}
PHP;
		$instance = $this->compile($this->definition($body));
		$this->assertSame(['custom.event' => ['gather', 20]], $instance::getSubscribedEvents());
		$dispatcher = new Dispatcher();
		$dispatcher->addSubscriber($instance);
		$dispatcher->addListener('custom.event', static function ($event): void
		{
			$event->setArgument('order', [...$event->getArgument('order', []), 'later']);
		}, 0);
		$event = new Event('custom.event');
		$dispatcher->dispatch('custom.event', $event);
		$this->assertSame(['plugin', 'later'], $event->getArgument('order'));
	}

	/**
	 * Reusing the renderer neither mutates definitions nor leaks subscriptions.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testGeneratedApiPlaceholderIsResolvedBeforeSubscriberInspection(): void
	{
		$placeholder = $this->renderer(Placeholder::class);
		$this->renderer(Routes::class, ['placeholder' => $placeholder])->set([]);
		$plugin = $this->definition('[[[API_ROUTES_METHOD]]]');
		$renderer = $this->renderer(Extension::class, ['placeholder' => $placeholder]);
		$first = $renderer->get($plugin);
		$this->assertStringContainsString("\$events['onBeforeApiRoute'] = 'onBeforeApiRoute';", $first);
		$this->assertStringContainsString('BeforeApiRouteEvent $event', $first);
		$this->assertStringNotContainsString('[[[API_ROUTES_METHOD]]]', $first);
		$this->assertSame('[[[API_ROUTES_METHOD]]]', $plugin->main_class_code);
		$this->assertSame($first, $renderer->get($plugin));
		$empty = $this->definition('');
		$instance = $this->compile($empty);
		$this->assertSame([], $instance::getSubscribedEvents());
		$this->assertStringNotContainsString('onBeforeApiRoute', $renderer->get($empty));
	}

	/**
	 * An inherited native subscription map survives synthesis in a child plugin.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testInheritedNativeMapAndGroupedEventImportsArePreserved(): void
	{
		$parent = 'SubscriberParent' . ++self::$fixture;
		eval('namespace ' . __NAMESPACE__ . '; class ' . $parent . ' extends \\Joomla\\CMS\\Plugin\\CMSPlugin implements \\Joomla\\Event\\SubscriberInterface {'
			. ' public static function getSubscribedEvents(): array { return ["inherited" => "fromParent"]; }'
			. ' public function fromParent($event): void { $event->setArgument("parent", true); } }');
		$plugin = $this->definition(<<<'PHP'
	public function onChild(NativeEvent $event)
	{
		$closure = function () { return false; };
		$nested = new class {
			public function onNested($context, $legacy) { return false; }
		};
		$class = \Joomla\Event\Event::class;
		$event->setArgument('child', $closure());
		return null;
	}
PHP);
		$plugin->extends = '\\' . __NAMESPACE__ . '\\' . $parent;
		$plugin->header = 'use Joomla\\Event\\{Event as NativeEvent, EventInterface};';
		$instance = $this->compile($plugin);
		$this->assertSame(['inherited' => 'fromParent', 'onChild' => 'onChild'], $instance::getSubscribedEvents());
		$dispatcher = new Dispatcher();
		$dispatcher->addSubscriber($instance);
		$event = new Event('inherited');
		$dispatcher->dispatch('inherited', $event);
		$this->assertTrue($event->getArgument('parent'));
		$event = new Event('onChild');
		$dispatcher->dispatch('onChild', $event);
		$this->assertFalse($event->getArgument('child'));
	}

	/**
	 * New build containers select only the requested target, including after Joomla 6.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	public function testNativeProviderDependencyIsIsolatedToJoomlaSix(): void
	{
		foreach ([6, 5, 4, 3, 6] as $major)
		{
			$container = new Container();
			$container->set('Config', (object) ['joomla_version' => $major]);
			$container->set('Placeholder', $this->renderer(Placeholder::class));
			$container->set('Compiler.Builder.Content.One', new ContentOne());
			$container->set('Power.Parser', new Parser());

			if ($major === 6)
			{
				$container->set('Joomla.Power', $this->createStub(PowerInterface::class));
			}

			(new ArchitecturePlugin())->register($container);
			$plugin = $this->definition("\tpublic function onReady(\\Joomla\\Event\\EventInterface \$event): void {}\n\tpublic function helper(): void {}");
			$source = $plugin->main_class_code;
			$code = $container->get('Architecture.Plugin.Extension')->get($plugin);

			if ($major === 6)
			{
				$this->assertStringContainsString("\$events['onReady'] = 'onReady';", $code);
				$this->assertStringNotContainsString("'helper' => 'helper'", $code);
				$this->assertSame($source, $plugin->main_class_code);
			}
			elseif ($major > 3)
			{
				$this->assertStringContainsString("'helper' => 'helper'", $code);
			}
			else
			{
				$this->assertStringNotContainsString('getSubscribedEvents', $code);
			}
		}
	}

	/**
	 * Definitions that need intentional migration must fail with exact context.
	 *
	 * @return array<string,array{string,string}>
	 * @since 6.2.0
	 */
	public static function legacyDefinitions(): array
	{
		return [
			'inline positional' => ['public function helper() {} public function onSave($context, $item) {}', 'onSave'],
			'positional' => ['public function onSave($context, $item) {}', 'onSave'],
			'scalar' => ['public function onSave(string $context) {}', 'onSave'],
			'ambiguous untyped' => ['public function onSave($item) {}', 'onSave'],
			'value return' => ['public function onSave(\\Joomla\\Event\\EventInterface $event) { return false; }', 'onSave'],
			'class constant before return' => ['public function onSave(\\Joomla\\Event\\EventInterface $event) { $class = \\Joomla\\Event\\Event::class; return false; }', 'onSave'],
			'dispatcher constructor' => ['public function __construct(\\Joomla\\Event\\DispatcherInterface $dispatcher, array $config = []) {}', '__construct'],
			'old parent constructor' => ['public function __construct(array $config = []) { parent::__construct(null, $config); }', '__construct'],
			'untyped subject constructor' => ['public function __construct($subject, array $config = []) {}', '__construct'],
			'legacy listener override' => ['public function registerListeners() {}', 'registerListeners'],
			'compound null return' => ['public function onSave(\\Joomla\\Event\\EventInterface $event) { return null ?? false; }', 'onSave'],
			'optional before required' => ['public function onSave(\\Joomla\\Event\\EventInterface $event = null, $required) {}', 'onSave'],
			'untyped native map' => ['public static function getSubscribedEvents() { return []; }', 'getSubscribedEvents'],
			'invalid native map' => ['protected static function getSubscribedEvents(): array { return []; }', 'getSubscribedEvents'],
		];
	}

	/**
	 * @param string $body   Stored source.
	 * @param string $method Expected diagnostic context.
	 *
	 * @return void
	 * @since 6.2.0
	 */
	#[DataProvider('legacyDefinitions')]
	public function testLegacyDefinitionsHaveActionableDiagnostics(string $body, string $method): void
	{
		$plugin = $this->definition("\t" . $body);
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Joomla 6 plugin ' . $plugin->class_name . '::' . $method . '()');
		$this->renderer(Extension::class)->get($plugin);
	}

	/**
	 * @param string $body Stored source.
	 *
	 * @return object
	 * @since 6.2.0
	 */
	private function definition(string $body): object
	{
		return (object) [
			'comment' => '/** Native subscriber fixture. */',
			'class_name' => 'NativeSubscriberFixture' . ++self::$fixture,
			'extends' => '\\Joomla\\CMS\\Plugin\\CMSPlugin',
			'header' => '',
			'main_class_code' => $body,
		];
	}

	/**
	 * Compile through the real Joomla Power import and collision pipeline.
	 *
	 * @param object $plugin Definition to execute.
	 *
	 * @return object
	 * @since 6.2.0
	 */
	private function compile(object $plugin): object
	{
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['c06c5116-6b9d-487c-9b09-5094ec4506a3', 0, (object) ['_namespace' => 'Joomla\\Event', 'class_name' => 'SubscriberInterface', 'type' => 'interface']],
			['11111111-2222-3333-4444-555555555555', 0, (object) ['_namespace' => 'Joomla\\CMS\\Event\\Plugin', 'class_name' => 'AjaxEvent', 'type' => 'class']],
		]);
		$code = $this->renderer(Extension::class, ['power' => $power])->get($plugin);
		$this->assertStringContainsString($plugin->main_class_code, $code);
		$injector = new Injector($power, new Extractor($this->createStub(DatabaseInterface::class), 6), new Parser(), $this->renderer(Placeholder::class));
		$code = $injector->power('<?php' . PHP_EOL . 'namespace ' . __NAMESPACE__ . ';' . PHP_EOL . 'use Joomla\\CMS\\Plugin\\CMSPlugin;' . PHP_EOL . $plugin->header . PHP_EOL . $code);
		$this->assertStringNotContainsString('Joomla___', $code);
		eval(substr($code, strlen('<?php')));
		$class = __NAMESPACE__ . '\\' . $plugin->class_name;

		return new $class([]);
	}
}
