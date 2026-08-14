<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Console;


use InvalidArgumentException;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\DI\Container;
use Joomla\Input\Input;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VDM\Joomla\Componentbuilder\Compiler\Factory as CompilerFactory;
use VDM\Joomla\Componentbuilder\Compiler\FilePaths;
use VDM\Joomla\Componentbuilder\Console\Compiler;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Utilities\GuidHelper;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Compiler CLI input, normalization, dispatch, factory, and status tests.
 *
 * @since  1.0.0
 */
#[CoversClass(Compiler::class)]
#[UsesClass(CompilerFactory::class)]
#[UsesClass(FilePaths::class)]
#[UsesClass(GuidHelper::class)]
final class CompilerTest extends FilesystemTestCase
{
	/**
	 * Layout base path active before a compiler action test.
	 *
	 * @var    mixed
	 * @since  1.0.0
	 */
	private mixed $layoutBasePath;

	/**
	 * Preserve the process-static layout setting changed by compiler execution.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->layoutBasePath = LayoutHelper::$defaultBasePath;
	}

	/**
	 * Restore the layout setting after Joomla and factory state is exercised.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		LayoutHelper::$defaultBasePath = $this->layoutBasePath;

		parent::tearDown();
	}

	/**
	 * Reject an empty command name before touching Joomla application state.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testConstructorRejectsEmptyCommandName(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Command name may not be empty.');

		new Compiler('', $this->createStub(ItemInterface::class));
	}

	/**
	 * Publish the complete reviewed compiler option surface and help contract.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testConfigurationPublishesComponentAndCompilerOptions(): void
	{
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));

		$this->assertSame('component:compile', $subject->getName());
		$this->assertSame(
			[
				'component',
				'components',
				'components-file',
				'backup',
				'repository',
				'add-placeholders',
				'debug-line-nr',
				'minify',
				'powers',
				'joomla-version',
				'powers-repository',
				'indentation-value',
				'add-build-date',
				'build-date',
				'options',
				'install',
			],
			array_keys($subject->getDefinition()->getOptions())
		);
		$this->assertStringContainsString('Compile a component', $subject->getDescription());
		$this->assertStringContainsString('JCB_COMPILER_OPTIONS', $subject->getHelp());
		$this->assertStringContainsString('underscore keys', $subject->getHelp());
	}

	/**
	 * Merge every explicit component source, discard invalid values, and dedupe.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testComponentsResolveFromSingleListAndFileSources(): void
	{
		$this->clearCompilerEnvironment();
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));
		$one = '11111111-1111-4111-8111-111111111111';
		$two = '22222222-2222-4222-8222-222222222222';
		$three = '33333333-3333-4333-8333-333333333333';
		$file = $this->writeTemporaryFile(
			'components.json',
			json_encode(['components' => [$three, $one, 'not-a-guid']], JSON_THROW_ON_ERROR)
		);
		$input = $this->boundInput(
			$subject,
			[
				'--component' => ' ' . $one . ' ',
				'--components' => json_encode([$two, $one], JSON_THROW_ON_ERROR),
				'--components-file' => $file,
			]
		);
		$this->setProperty($subject, 'cliInput', $input);

		$this->assertSame(
			[$one, $two, $three],
			$this->invoke($subject, 'resolveComponents')
		);
	}

	/**
	 * Use environment inputs only when their matching CLI option is absent.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testComponentsUseEnvironmentFallbackAndAtFileShorthand(): void
	{
		$this->clearCompilerEnvironment();
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));
		$one = '11111111-1111-4111-8111-111111111111';
		$two = '22222222-2222-4222-8222-222222222222';
		$file = $this->writeTemporaryFile('components.txt', $two . "\n" . $one);
		$this->setEnvironmentVariable('JCB_COMPILE_COMPONENT', $one);
		$this->setEnvironmentVariable('JCB_COMPILE_COMPONENTS', 'invalid');
		$input = $this->boundInput($subject, ['--components' => '@' . $file]);
		$this->setProperty($subject, 'cliInput', $input);

		$this->assertSame(
			[$one, $two],
			$this->invoke($subject, 'resolveComponents')
		);
	}

	/**
	 * Normalize JSON catalog shapes, CSV, and platform line endings.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testComponentListParserSupportsDocumentedShapes(): void
	{
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));

		$this->assertSame(
			['one', 'two'],
			$this->invoke($subject, 'parseStringList', ['{"components":["one","two"]}'])
		);
		$this->assertSame(
			['one', 'two'],
			$this->invoke($subject, 'parseStringList', ['{"items":["one","two"]}'])
		);
		$this->assertSame(
			['one', 'two'],
			$this->invoke($subject, 'parseStringList', ['{"first":"one","second":"two"}'])
		);
		$this->assertSame(
			['one', 'two', 'three'],
			$this->invoke($subject, 'parseStringList', ["one\r\ntwo\rthree"])
		);
		$this->assertSame(
			['one', "two\nthree"],
			$this->invoke($subject, 'parseStringList', ["one,two\nthree"])
		);
	}

	/**
	 * Reject an unreadable component file with its option and full path.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testMissingComponentFileIsRejected(): void
	{
		$this->clearCompilerEnvironment();
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));
		$missing = $this->temporaryPath('missing-components.json');
		$input = $this->boundInput($subject, ['--components-file' => $missing]);
		$this->setProperty($subject, 'cliInput', $input);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unable to read file for --components-file: ' . $missing);

		$this->invoke($subject, 'resolveComponents');
	}

	/**
	 * Apply environment, bundle, then explicit CLI precedence into Joomla input.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testCompilerOptionsNormalizeWithCliTakingHighestPrecedence(): void
	{
		$this->clearCompilerEnvironment();
		$applicationInput = new Input([]);
		$subject = $this->subject($this->createStub(ItemInterface::class), $applicationInput);
		$this->setEnvironmentVariable('JCB_BACKUP', '1');
		$this->setEnvironmentVariable('JCB_MINIFY', '2');
		$bundle = json_encode(
			[
				'minify' => '2',
				'powers-repository' => '1',
				'indentation-value' => '4',
				'build-date' => '2026-08-14',
			],
			JSON_THROW_ON_ERROR
		);
		$input = $this->boundInput(
			$subject,
			[
				'--options' => $bundle,
				'--minify' => '1',
				'--joomla-version' => '6',
				'--install' => true,
			]
		);
		$this->setProperty($subject, 'cliInput', $input);

		$this->invoke($subject, 'normalizeCompilerOptions');
		$post = $applicationInput->post;

		$this->assertSame('1', $post->get('backup'));
		$this->assertSame('1', $post->get('minify'));
		$this->assertSame('6', $post->get('joomla_version'));
		$this->assertSame('1', $post->get('powers_repository'));
		$this->assertSame('4', $post->get('indentation_value'));
		$this->assertSame('2026-08-14', $post->get('build_date'));
		$this->assertSame('1', $post->get('show_advanced_options'));
		$this->assertSame('2', $post->get('add_build_date'));
		$this->assertTrue($this->property($subject, 'autoInstall'));
		$this->assertNull($post->get('install'));
	}

	/**
	 * Accept hyphenated and padded bundle keys through one canonical mapping.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testOptionKeysNormalizeToLowercaseUnderscores(): void
	{
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));

		$this->assertSame(
			'powers_repository',
			$this->invoke($subject, 'normalizeOptionKey', [' Powers-Repository '])
		);
	}

	/**
	 * Reject malformed bundles and values outside each reviewed option catalog.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testCompilerOptionsRejectMalformedBundlesAndUnsupportedValues(): void
	{
		$this->clearCompilerEnvironment();
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));
		$malformed = $this->boundInput($subject, ['--options' => '{broken']);
		$this->setProperty($subject, 'cliInput', $malformed);

		try
		{
			$this->invoke($subject, 'normalizeCompilerOptions');
			$this->fail('Malformed compiler option JSON was accepted.');
		}
		catch (InvalidArgumentException $error)
		{
			$this->assertSame('Invalid compiler options JSON (bundle).', $error->getMessage());
		}

		$invalidValue = $this->boundInput($subject, ['--joomla-version' => '7']);
		$this->setProperty($subject, 'cliInput', $invalidValue);

		try
		{
			$this->invoke($subject, 'normalizeCompilerOptions');
			$this->fail('An unsupported Joomla target was accepted.');
		}
		catch (InvalidArgumentException $error)
		{
			$this->assertSame(
				'Invalid value "7" for option "joomla_version".',
				$error->getMessage()
			);
		}
	}

	/**
	 * Preserve the documented explicit "no" value instead of treating it absent.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testExplicitZeroCompilerOptionIsInjected(): void
	{
		$this->clearCompilerEnvironment();
		$applicationInput = new Input([]);
		$subject = $this->subject($this->createStub(ItemInterface::class), $applicationInput);
		$input = $this->boundInput($subject, ['--minify' => '0']);
		$this->setProperty($subject, 'cliInput', $input);

		$this->invoke($subject, 'normalizeCompilerOptions');

		$this->assertSame(
			'0',
			$applicationInput->post->get('minify'),
			'Allowed zero values are explicit input, not GLOBAL/absent state.'
		);
	}

	/**
	 * Map a missing component selection to the documented validation exit code.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testExecuteReturnsValidationStatusWhenNoComponentsWereProvided(): void
	{
		$this->clearCompilerEnvironment();
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->never())->method('table');
		$subject = $this->subject($item, new Input([]));
		$stderr = new BufferedOutput();

		$status = $subject->execute(new ArrayInput([]), $this->consoleOutput($stderr));

		$this->assertSame(1, $status);
		$this->assertStringContainsString('No component GUID(s) provided.', $stderr->fetch());
	}

	/**
	 * Resolve a component GUID to its ID, invoke one compiler, and reset its factory.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testCompileFailureSetsComponentIdAndResetsCompilerFactory(): void
	{
		$this->clearCompilerEnvironment();
		$guid = '11111111-1111-4111-8111-111111111111';
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->once())
			->method('table')
			->with('joomla_component')
			->willReturnSelf();
		$item->expects($this->once())
			->method('value')
			->with($guid)
			->willReturn(42);
		$applicationInput = new Input([]);
		$subject = $this->subject($item, $applicationInput);
		$compiler = new class
		{
			/**
			 * Number of compiler calls.
			 *
			 * @var    int
			 * @since  1.0.0
			 */
			public int $calls = 0;

			/**
			 * Return a deterministic compiler failure.
			 *
			 * @return  bool  Always false.
			 * @since   1.0.0
			 */
			public function run(): bool
			{
				$this->calls++;

				return false;
			}
		};
		$this->isolateFactory(CompilerFactory::class);
		$container = new Container();
		$container->set('Compiler', $compiler, true);
		(new ReflectionProperty(CompilerFactory::class, 'container'))
			->setValue(null, $container);
		$stderr = new BufferedOutput();

		$status = $subject->execute(
			new ArrayInput(['--component' => $guid]),
			$this->consoleOutput($stderr)
		);

		$this->assertSame(1, $status);
		$this->assertSame(1, $compiler->calls);
		$this->assertSame(42, $applicationInput->post->getInt('component_id'));
		$this->assertStringContainsString('Compiler failed', $stderr->fetch());
		$this->assertNull(
			(new ReflectionProperty(CompilerFactory::class, 'container'))->getValue()
		);
	}

	/**
	 * Translate an unexpected item-gateway failure without opening the compiler.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testExecuteMapsUnexpectedLookupFailureToStatusTwo(): void
	{
		$this->clearCompilerEnvironment();
		$guid = '11111111-1111-4111-8111-111111111111';
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->once())->method('table')->willReturnSelf();
		$item->expects($this->once())
			->method('value')
			->willThrowException(new RuntimeException('database unavailable'));
		$subject = $this->subject($item, new Input([]));
		$stderr = new BufferedOutput();

		$status = $subject->execute(
			new ArrayInput(['--component' => $guid]),
			$this->consoleOutput($stderr)
		);
		$text = $stderr->fetch();

		$this->assertSame(2, $status);
		$this->assertStringContainsString('An unexpected error occurred.', $text);
		$this->assertStringContainsString('database unavailable', $text);
	}

	/**
	 * Collect component, module, and plugin artifacts in deterministic order.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testCompilerPathsAreCollectedAcrossEveryExtensionType(): void
	{
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));
		$paths = new FilePaths();
		$paths->set('component', '/tmp/component.zip');
		$paths->set('modules', ['/tmp/module-one.zip', '/tmp/module-two.zip']);
		$paths->set('plugins', ['/tmp/plugin.zip']);
		$this->isolateFactory(CompilerFactory::class);
		$container = new Container();
		$container->set('FilePaths', $paths, true);
		(new ReflectionProperty(CompilerFactory::class, 'container'))
			->setValue(null, $container);

		$this->invoke($subject, 'collectCompilerPaths');

		$this->assertSame(
			[
				'/tmp/component.zip',
				'/tmp/module-one.zip',
				'/tmp/module-two.zip',
				'/tmp/plugin.zip',
			],
			$this->property($subject, 'outputPaths')
		);
	}

	/**
	 * Skip installation by default and fail safely when an artifact is missing.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testInstallationIsOptInAndMissingArtifactsReturnStatusTwo(): void
	{
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));
		$missing = $this->temporaryPath('missing-extension.zip');
		$stderr = new BufferedOutput();
		$this->setProperty($subject, 'ioStyle', new SymfonyStyle(new ArrayInput([]), $stderr));
		$this->setProperty($subject, 'outputPaths', [$missing]);
		$this->setProperty($subject, 'autoInstall', false);

		$this->assertSame(0, $this->invoke($subject, 'installExtensions'));
		$this->assertSame('', $stderr->fetch());

		$this->setProperty($subject, 'autoInstall', true);
		$this->assertSame(2, $this->invoke($subject, 'installExtensions'));
		$this->assertStringContainsString('does not exist', $stderr->fetch());
	}

	/**
	 * Render each collected local compiler message through human stderr output.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testLocalCompilerMessagesRenderAsSuccessOutput(): void
	{
		$subject = $this->subject($this->createStub(ItemInterface::class), new Input([]));
		$output = new BufferedOutput();
		$this->setProperty($subject, 'ioStyle', new SymfonyStyle(new ArrayInput([]), $output));
		$this->setProperty($subject, 'messages', ['first build', 'second build']);

		$this->assertTrue($this->invoke($subject, 'renderMessageBus'));
		$text = $output->fetch();
		$this->assertStringContainsString('first build', $text);
		$this->assertStringContainsString('second build', $text);
	}

	/**
	 * Construct a compiler command with isolated CMS and console applications.
	 *
	 * @param   ItemInterface  $item   Component item gateway.
	 * @param   Input          $input  Joomla application input.
	 *
	 * @return  Compiler
	 * @since   1.0.0
	 */
	private function subject(ItemInterface $item, Input $input): Compiler
	{
		$application = $this->consoleApplication($input);
		$this->setJoomlaApplication($application);
		$subject = new Compiler('component:compile', $item);
		$subject->setApplication($application);

		return $subject;
	}

	/**
	 * Build a constructor-free CMS console boundary around a real Joomla input.
	 *
	 * @param   Input  $input  Joomla application input.
	 *
	 * @return  ConsoleApplication
	 * @since   1.0.0
	 */
	private function consoleApplication(Input $input): ConsoleApplication
	{
		$application = $this->createStub(ConsoleApplication::class);
		$application->method('getLanguage')
			->willReturn($this->createStub(Language::class));
		$application->method('getInput')->willReturn($input);
		$application->method('getDefinition')->willReturn(new InputDefinition());
		$application->method('getHelperSet')->willReturn(new HelperSet());
		$application->method('get')
			->willReturnCallback(
				static fn(string $key, mixed $default = null): mixed =>
					$key === 'tmp_path' ? sys_get_temp_dir() : $default
			);

		return $application;
	}

	/**
	 * Create a split output boundary whose human stream can be asserted.
	 *
	 * @param   BufferedOutput  $stderr  Human-output buffer.
	 *
	 * @return  ConsoleOutputInterface
	 * @since   1.0.0
	 */
	private function consoleOutput(BufferedOutput $stderr): ConsoleOutputInterface
	{
		$output = $this->createStub(ConsoleOutputInterface::class);
		$output->method('getErrorOutput')->willReturn($stderr);

		return $output;
	}

	/**
	 * Bind direct parser input to the command definition.
	 *
	 * @param   Compiler              $subject     Compiler command.
	 * @param   array<string, mixed>  $parameters  CLI input values.
	 *
	 * @return  ArrayInput
	 * @since   1.0.0
	 */
	private function boundInput(Compiler $subject, array $parameters): ArrayInput
	{
		$input = new ArrayInput($parameters);
		$input->bind($subject->getDefinition());

		return $input;
	}

	/**
	 * Invoke one protected compiler command method.
	 *
	 * @param   Compiler           $subject    Compiler command.
	 * @param   string             $method     Method name.
	 * @param   array<int, mixed>  $arguments  Invocation arguments.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	private function invoke(Compiler $subject, string $method, array $arguments = []): mixed
	{
		$reflection = new ReflectionMethod($subject, $method);
		$reflection->setAccessible(true);

		return $reflection->invokeArgs($subject, $arguments);
	}

	/**
	 * Set one private compiler command property.
	 *
	 * @param   Compiler  $subject  Compiler command.
	 * @param   string    $name     Property name.
	 * @param   mixed     $value    Test value.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function setProperty(Compiler $subject, string $name, mixed $value): void
	{
		(new ReflectionProperty(Compiler::class, $name))->setValue($subject, $value);
	}

	/**
	 * Read one private compiler command property.
	 *
	 * @param   Compiler  $subject  Compiler command.
	 * @param   string    $name     Property name.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	private function property(Compiler $subject, string $name): mixed
	{
		return (new ReflectionProperty(Compiler::class, $name))->getValue($subject);
	}

	/**
	 * Remove compiler environment inputs so option precedence is deterministic.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function clearCompilerEnvironment(): void
	{
		foreach ([
			'JCB_COMPILE_COMPONENT',
			'JCB_COMPILE_COMPONENTS',
			'JCB_COMPILE_COMPONENTS_FILE',
			'JCB_COMPILER_OPTIONS',
			'JCB_COMPILE_INSTALL',
			'JCB_BACKUP',
			'JCB_REPOSITORY',
			'JCB_ADD_PLACEHOLDERS',
			'JCB_DEBUG_LINE_NR',
			'JCB_MINIFY',
			'JCB_POWERS',
			'JCB_JOOMLA_VERSION',
			'JCB_POWERS_REPOSITORY',
			'JCB_INDENTATION_VALUE',
			'JCB_ADD_BUILD_DATE',
			'JCB_BUILD_DATE',
		] as $name)
		{
			$this->setEnvironmentVariable($name, null);
		}
	}
}
