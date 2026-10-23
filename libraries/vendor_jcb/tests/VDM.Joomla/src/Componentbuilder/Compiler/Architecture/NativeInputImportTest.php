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


use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DynamicButtons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\EximportView;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ImportCustomScripts;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PowerInterface;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Extractor;
use VDM\Joomla\Componentbuilder\Compiler\JoomlaPower\Injector;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureController;
use VDM\Joomla\Componentbuilder\Power\Parser;


/**
 * Joomla 6 generated request input and import cache preserve their boundaries.
 *
 * @since  6.2.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Service')]
final class NativeInputImportTest extends ArchitectureTestCase
{
	/**
	 * Input migration follows the compile target through the real provider.
	 *
	 * @param   int  $major  Requested Joomla target.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testProviderKeepsLegacyInputOnlyForOlderTargets(int $major): void
	{
		foreach ($this->methods($major) as $code)
		{
			$expected = $major === 6
				? '$input = $this->input;'
				: '$input = Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getApplication()->input;';
			$this->assertSame(1, substr_count($code, $expected));
			$this->assertStringContainsString("\$pks = \$input->post->get('cid', array(), 'array');", $code);
			$this->assertStringContainsString('$pks = ArrayHelper::toInteger($pks);', $code);

			if ($major === 6)
			{
				$this->assertStringNotContainsString('getApplication()->input', $code);
			}
			else
			{
				$this->assertStringNotContainsString('$this->input', $code);
			}
		}
	}

	/**
	 * All supported targets keep their existing provider selection.
	 *
	 * @return  array<string, array{int}>
	 * @since   6.2.0
	 */
	public static function targets(): array
	{
		return ['Joomla 3' => [3], 'Joomla 4' => [4], 'Joomla 5' => [5], 'Joomla 6' => [6]];
	}

	/**
	 * Native controller input supplies selected IDs without a global application.
	 *
	 * @param   array  $submitted  Posted record IDs.
	 * @param   array  $expected   Sanitized record IDs.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('selection')]
	public function testNativeInputReadsAndSanitizesPostedSelection(array $submitted, array $expected): void
	{
		$input = new Input([]);
		$input->post->set('cid', $submitted);
		$controller = (object) ['input' => $input];

		foreach ($this->methods(6) as $code)
		{
			$this->assertSame(1, preg_match('/\$input = .*?\$pks = ArrayHelper::toInteger\(\$pks\);/s', $code, $match));
			$run = eval('use Joomla\Utilities\ArrayHelper; return function (): array {' . $match[0] . ' return $pks; };');
			$this->assertSame($expected, $run->bindTo($controller, null)());
		}
	}

	/**
	 * Empty and malformed selection values retain existing integer conversion.
	 *
	 * @return  array<string, array{array, array}>
	 * @since   6.2.0
	 */
	public static function selection(): array
	{
		return ['empty' => [[], []], 'mixed' => [['4', '-2', 'invalid'], [4, -2, 0]]];
	}

	/**
	 * Native cache cleanup runs only after a successful import, in the same group.
	 *
	 * @param   string  $template  Import controller template name.
	 * @param   bool    $success   Import model result.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('imports')]
	public function testImportCacheKeepsSuccessBoundaryAndMenuGroup(string $template, bool $success): void
	{
		$code = file_get_contents(dirname(JPATH_COMPONENT_ADMINISTRATOR) . '/admin/compiler/joomla_6/' . $template);
		$this->assertSame(1, preg_match('/\t\tif \(\$model->import\(\)\)\n\t\t\{.*?\n\t\t\}/s', $code, $match));
		$this->assertStringContainsString('Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getContainer()', $match[0]);
		$cache = $this->createMock(CacheController::class);
		$cache->expects($success ? $this->once() : $this->never())->method('__call')->with('clean', []);
		$factory = $this->createMock(CacheControllerFactoryInterface::class);
		$factory->expects($success ? $this->once() : $this->never())
			->method('createCacheController')->with('callback', ['defaultgroup' => 'mod_menu'])->willReturn($cache);
		$container = new Container();
		$container->set(CacheControllerFactoryInterface::class, $factory);
		$this->setJoomlaContainer($container);
		$model = new class($success)
		{
			/**
			 * Controlled persistence result.
			 *
			 * @var    bool
			 * @since  6.2.0
			 */
			private bool $success;

			/**
			 * Capture the outcome at the persistence boundary.
			 *
			 * @param   bool  $success  Import result.
			 * @since   6.2.0
			 */
			public function __construct(bool $success)
			{
				$this->success = $success;
			}

			/**
			 * Report the model result without touching a database.
			 *
			 * @return  bool
			 * @since   6.2.0
			 */
			public function import(): bool
			{
				return $this->success;
			}
		};
		$class = 'ImportCache' . md5($template);
		$qualified = __NAMESPACE__ . '\\' . $class;

		if (!class_exists($qualified, false))
		{
			$source = '<?php' . "\nnamespace " . __NAMESPACE__
				. ";\n\nuse Joomla\\CMS\\MVC\\Controller\\BaseController;\n\nclass " . $class . ' extends BaseController'
				. "\n{\n\tpublic static function run(\$model): void\n\t{\n" . $match[0] . "\n\t}\n}\n";
			$source = $this->resolveFactoryPower($source);
			eval(substr($source, strlen('<?php')));
		}

		$qualified::run($model);
	}

	/**
	 * Both linked controller templates retain successful and failed imports.
	 *
	 * @return  iterable<string, array{string, bool}>
	 * @since   6.2.0
	 */
	public static function imports(): iterable
	{
		foreach (['ADMIN_IMPORT_CONTROLLER.php', 'ADMIN_IMPORT_CONTROLLER_CUSTOM.php'] as $template)
		{
			yield $template . ' success' => [$template, true];
			yield $template . ' failure' => [$template, false];
		}
	}

	/**
	 * Generate both controller paths through their shared logical service aliases.
	 *
	 * @param   int  $major  Requested Joomla target.
	 *
	 * @return  array<string, string>
	 * @since   6.2.0
	 */
	private function methods(int $major): array
	{
		$this->config()->set('joomla_version', $major);
		$content = new ContentOne();
		$content->set('Component', 'Demo');
		$exports = new EximportView();
		$exports->set('articles', true);
		$buttons = new DynamicButtons();
		$buttons->set('articles', [['link' => 'reports', 'NAME' => 'REPORTS']]);
		$container = new Container();

		foreach ([
			'Config' => $this->config(),
			'Language' => $this->language(),
			'Compiler.Builder.Content.One' => $content,
			'Compiler.Builder.Eximport.View' => $exports,
			'Compiler.Builder.Import.Custom.Scripts' => new ImportCustomScripts(),
			'Compiler.Builder.Dynamic.Buttons' => $buttons,
		] as $key => $service)
		{
			$container->set($key, $service);
		}

		$container->registerServiceProvider(new ArchitectureController());
		$export = $container->get('Architecture.Controller.EximportMethod');
		$button = $container->get('Architecture.Controller.CustomAdminDynamicButton');
		$this->assertSame($export, $container->get('Architecture.Controller.EximportMethod'));
		$this->assertSame($button, $container->get('Architecture.Controller.CustomAdminDynamicButton'));

		return ['export' => $export->get('article', 'articles'), 'button' => $button->get('articles')];
	}

	/**
	 * Resolve the retained Joomla Power through the compiler's actual injector.
	 *
	 * @param   string  $code  Generated source before Power resolution.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	private function resolveFactoryPower(string $code): string
	{
		$power = $this->createStub(PowerInterface::class);
		$power->method('get')->willReturnMap([
			['39403062-84fb-46e0-bac4-0023f766e827', 0, (object) ['_namespace' => 'Joomla\\CMS', 'class_name' => 'Factory', 'type' => 'class']],
		]);
		$injector = new Injector(
			$power,
			new Extractor($this->createStub(DatabaseInterface::class), 6),
			new Parser(),
			$this->placeholder()
		);
		$code = $injector->power($code);
		$this->assertStringNotContainsString('Joomla___', $code);
		$this->assertStringContainsString('use Joomla\CMS\Factory;', $code);

		return $code;
	}
}
