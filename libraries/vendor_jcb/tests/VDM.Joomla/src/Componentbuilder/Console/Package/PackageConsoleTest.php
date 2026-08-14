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

namespace VDM\Joomla\Tests\Componentbuilder\Console\Package;


use InvalidArgumentException;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use VDM\Joomla\Componentbuilder\Abstraction\Console\Package as PackageCommand;
use VDM\Joomla\Componentbuilder\Abstraction\Console\Package\Get as GetCommandBase;
use VDM\Joomla\Componentbuilder\Abstraction\Console\Package\Set as SetCommandBase;
use VDM\Joomla\Componentbuilder\Console\Package\Get as GetCommand;
use VDM\Joomla\Componentbuilder\Console\Package\Init;
use VDM\Joomla\Componentbuilder\Console\Package\Pull;
use VDM\Joomla\Componentbuilder\Console\Package\Push;
use VDM\Joomla\Componentbuilder\Console\Package\Reset;
use VDM\Joomla\Componentbuilder\Package\Builder\Get as GetBuilder;
use VDM\Joomla\Componentbuilder\Package\Builder\Set as SetBuilder;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\Factory as PackageFactory;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\FilesystemTestCase;
use VDM\Tests\Support\PackageConsoleFixture;


/**
 * Package command parsing, lifecycle, validation, output, and action tests.
 *
 * @since  1.0.0
 */
#[CoversClass(PackageCommand::class)]
#[CoversClass(GetCommandBase::class)]
#[CoversClass(SetCommandBase::class)]
#[CoversClass(GetCommand::class)]
#[CoversClass(Init::class)]
#[CoversClass(Pull::class)]
#[CoversClass(Push::class)]
#[CoversClass(Reset::class)]
#[UsesClass(GetBuilder::class)]
#[UsesClass(SetBuilder::class)]
#[UsesClass(Tracker::class)]
#[UsesClass(MessageBus::class)]
final class PackageConsoleTest extends FilesystemTestCase
{
	/**
	 * Language tag active before the current test.
	 *
	 * @var    mixed
	 * @since  1.0.0
	 */
	private mixed $languageTag;

	/**
	 * Install a deterministic Joomla language boundary for command construction.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->languageTag = StringHelper::$langTag;
		StringHelper::$langTag = 'en-GB';
		$language = $this->createStub(Language::class);
		$language->method('transliterate')
			->willReturnCallback(static fn(string $value): string => $value);
		$languageFactory = $this->createStub(LanguageFactoryInterface::class);
		$languageFactory->method('createLanguage')->willReturn($language);
		$container = new Container();
		$container->set(LanguageFactoryInterface::class, $languageFactory, true);
		$this->setJoomlaContainer($container);
		$application = $this->createStub(CMSApplicationInterface::class);
		$application->method('getLanguage')
			->willReturn($language);
		$this->setJoomlaApplication($application);
	}

	/**
	 * Restore the process-static naming language after each command test.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		StringHelper::$langTag = $this->languageTag;

		parent::tearDown();
	}

	/**
	 * Reject empty command and entity identities before opening global services.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testConstructorRejectsEmptyCommandAndEntityNames(): void
	{
		$item = $this->createStub(ItemInterface::class);

		try
		{
			new GetCommand('', 'admin_view', $item);
			$this->fail('An empty command name was accepted.');
		}
		catch (InvalidArgumentException $error)
		{
			$this->assertSame('Command name may not be empty.', $error->getMessage());
		}

		try
		{
			new GetCommand('package:get', '', $item);
			$this->fail('An empty entity name was accepted.');
		}
		catch (InvalidArgumentException $error)
		{
			$this->assertSame('Entity may not be empty.', $error->getMessage());
		}
	}

	/**
	 * Publish an option surface matched to each concrete operation's contract.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testConcreteCommandsPublishTheirReviewedOptionSurfaces(): void
	{
		$get = $this->command(GetCommand::class);
		$init = $this->command(Init::class);
		$pull = $this->command(Pull::class);
		$push = $this->command(Push::class);
		$reset = $this->command(Reset::class);

		$this->assertSame(['items', 'items-file'], $this->optionNames($get));
		$this->assertSame(
			['items', 'items-file', 'repo', 'repo-file', 'force', 'resolve'],
			$this->optionNames($init)
		);
		$this->assertSame(
			['items', 'items-file', 'repo', 'repo-file', 'resolve'],
			$this->optionNames($pull)
		);
		$this->assertSame(['items', 'items-file'], $this->optionNames($push));
		$this->assertSame(['items', 'items-file', 'resolve'], $this->optionNames($reset));
		$this->assertStringContainsString('Get and synchronize admin view', $get->getDescription());
		$this->assertStringContainsString('Force is always enabled', $pull->getHelp());
		$this->assertStringContainsString('Reset requires explicit item GUIDs', $reset->getHelp());
	}

	/**
	 * Parse JSON, environment, and @file sources into a stable unique item list.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testItemsResolveFromEachSupportedSourceAndNormalizeValues(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->fixture();
		$inline = $this->boundInput(
			$subject,
			['--items' => '{"items":[" first ","second","first",""]}']
		);

		$this->assertSame(
			['first', 'second'],
			$this->invoke($subject, 'resolveItems', [$inline])
		);

		$this->setEnvironmentVariable('JCB_GET_ITEMS', " env-one, env-two\nenv-three ");
		$environment = $this->boundInput($subject, []);
		$this->assertSame(
			['env-one', "env-two\nenv-three"],
			$this->invoke($subject, 'resolveItems', [$environment])
		);

		$this->setEnvironmentVariable('JCB_GET_ITEMS', null);
		$file = $this->writeTemporaryFile('items.json', '[" file-one ","file-two","file-one"]');
		$fromFile = $this->boundInput($subject, ['--items' => '@' . $file]);
		$this->assertSame(
			['file-one', 'file-two'],
			$this->invoke($subject, 'resolveItems', [$fromFile])
		);
	}

	/**
	 * Preserve CSV's explicit comma precedence over embedded newlines.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testItemStringParsersHandleListsObjectsAndLineEndings(): void
	{
		$subject = $this->fixture();

		$this->assertSame(
			['one', 'two'],
			$this->invoke($subject, 'parseItemsString', ['["one","two"]'])
		);
		$this->assertSame(
			['one', 'two'],
			$this->invoke($subject, 'parseItemsString', ['{"items":["one","two"]}'])
		);
		$this->assertSame(
			['one', 'two', 'three'],
			$this->invoke($subject, 'parseItemsString', ["one\r\ntwo\rthree"])
		);
		$this->assertSame(
			['one', "two\nthree"],
			$this->invoke($subject, 'parseItemsString', ["one,two\nthree"])
		);
		$this->assertSame(
			['1', 'two'],
			$this->invoke($subject, 'normalizeStringList', [[1, ' two ', '', 1]])
		);
	}

	/**
	 * Report the exact option and path when a requested file is unavailable.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testMissingItemsFileIsRejectedWithActionableContext(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->fixture();
		$missing = $this->temporaryPath('missing-items.txt');
		$input = $this->boundInput($subject, ['--items-file' => $missing]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unable to read file for --items-file: ' . $missing);

		$this->invoke($subject, 'resolveItems', [$input]);
	}

	/**
	 * Honor the documented single-source priority instead of merging lower input.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testExplicitInlineItemsSuppressLowerPriorityFileInput(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->fixture();
		$file = $this->writeTemporaryFile('lower-priority.txt', 'file-only');
		$input = $this->boundInput(
			$subject,
			['--items' => 'inline-only', '--items-file' => $file]
		);

		$this->assertSame(
			['inline-only'],
			$this->invoke($subject, 'resolveItems', [$input]),
			'--items is documented as higher priority than --items-file.'
		);
	}

	/**
	 * Map action status and both documented exception categories to stable exits.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testExecutionWrapperMapsStatusesAndExceptions(): void
	{
		$cases = [
			['exception' => null, 'actionStatus' => 7, 'status' => 7, 'message' => ''],
			[
				'exception' => new InvalidArgumentException('invalid request'),
				'actionStatus' => 0,
				'status' => 1,
				'message' => 'invalid request',
			],
			[
				'exception' => new RuntimeException('remote exploded'),
				'actionStatus' => 0,
				'status' => 2,
				'message' => 'An unexpected error occurred.',
			],
		];

		foreach ($cases as $case)
		{
			$subject = $this->fixture();
			$subject->status = $case['actionStatus'];
			$subject->exception = $case['exception'];
			$subject->services['Package.Message'] = new MessageBus();
			$output = new BufferedOutput();

			$this->assertSame($case['status'], $subject->execute(new ArrayInput([]), $output));
			$this->assertStringContainsString($case['message'], $output->fetch());
		}
	}

	/**
	 * Resolve one message bus per command and render all categories in order.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testMessageBusIsResolvedOnceAndEveryCategoryIsRendered(): void
	{
		$messages = new MessageBus();
		$messages->add('success', 'stored remotely');
		$messages->add('warning', 'one dependency skipped');
		$messages->add('error', 'one dependency failed');
		$subject = $this->fixture();
		$subject->services['Package.Message'] = $messages;
		$output = new BufferedOutput();

		$this->assertSame(0, $subject->execute(new ArrayInput([]), $output));
		$this->assertSame(0, $subject->execute(new ArrayInput([]), $output));

		$text = $output->fetch();
		$this->assertSame(
			[['alias' => 'Package.Message', 'entity' => null]],
			$subject->serviceCalls
		);
		$this->assertSame(2, substr_count($text, 'stored remotely'));
		$this->assertSame(2, substr_count($text, 'one dependency skipped'));
		$this->assertSame(2, substr_count($text, 'one dependency failed'));
		$this->assertStringNotContainsString('no additional messages', $text);
	}

	/**
	 * Emit the explicit completion fallback only for a successful silent action.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testSuccessfulSilentActionReceivesCompletionFallback(): void
	{
		$subject = $this->fixture();
		$subject->services['Package.Message'] = new MessageBus();
		$output = new BufferedOutput();

		$this->assertSame(0, $subject->execute(new ArrayInput([]), $output));
		$this->assertStringContainsString(
			'Task completed with no additional messages.',
			$output->fetch()
		);
	}

	/**
	 * Decode only repository objects, arrays, or JSON GUID strings.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testRepositoryJsonDecodingAcceptsSupportedShapesAndRejectsScalars(): void
	{
		$subject = $this->command(Init::class);
		$object = $this->invoke($subject, 'decodeRepoJson', ['{"repository":"definitions"}']);
		$array = $this->invoke($subject, 'decodeRepoJson', ['[{"repository":"one"}]']);
		$guid = '11111111-1111-4111-8111-111111111111';

		$this->assertSame('definitions', $object->repository);
		$this->assertIsObject($array);
		$this->assertSame('one', $array->{0}->repository);
		$this->assertSame($guid, $this->invoke($subject, 'decodeRepoJson', ['"' . $guid . '"']));

		foreach (['', '{broken', '42', 'null'] as $invalid)
		{
			try
			{
				$this->invoke($subject, 'decodeRepoJson', [$invalid]);
				$this->fail('Invalid repository JSON was accepted: ' . $invalid);
			}
			catch (InvalidArgumentException $error)
			{
				$this->assertStringContainsString(
					'repository json',
					strtolower($error->getMessage())
				);
			}
		}
	}

	/**
	 * Prefer inline repository JSON and delegate domain validation to the builder.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testRepositoryResolutionHonorsInlinePrecedenceAndBuilderValidation(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->command(Init::class);
		$builder = $this->createMock(GetBuilder::class);
		$builder->expects($this->once())
			->method('validRepo')
			->with(
				'admin_view',
				$this->callback(
					static fn(object $repository): bool =>
						$repository->repository === 'inline'
				)
			)
			->willReturn(true);
		$this->setProperty($subject, 'get', $builder);
		$file = $this->writeTemporaryFile('repository.json', '{"repository":"file"}');
		$input = $this->boundInput(
			$subject,
			['--repo' => '{"repository":"inline"}', '--repo-file' => $file]
		);

		$repository = $this->invoke($subject, 'resolveRepo', [$input]);

		$this->assertSame('inline', $repository->repository);
	}

	/**
	 * Interpret flags from explicit switches and common environment spellings.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testForceAndResolveFlagsHonorExplicitAndEnvironmentValues(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->command(Init::class);
		$this->setEnvironmentVariable('JCB_GET_FORCE', 'yes');
		$this->setEnvironmentVariable('JCB_GET_RESOLVE', 'ON');
		$environment = $this->boundInput($subject, []);

		$this->assertTrue($this->invoke($subject, 'resolveForce', [$environment]));
		$this->assertTrue($this->invoke($subject, 'resolveValidate', [$environment]));

		$this->setEnvironmentVariable('JCB_GET_FORCE', 'no');
		$this->setEnvironmentVariable('JCB_GET_RESOLVE', '0');
		$explicit = $this->boundInput($subject, ['--force' => true, '--resolve' => true]);
		$this->assertTrue($this->invoke($subject, 'resolveForce', [$explicit]));
		$this->assertTrue($this->invoke($subject, 'resolveValidate', [$explicit]));

		foreach (['1', 'true', ' yes ', 'ON'] as $truthy)
		{
			$this->assertTrue($this->invoke($subject, 'toBool', [$truthy]));
		}

		foreach (['', '0', 'false', 'off', 'anything'] as $falsey)
		{
			$this->assertFalse($this->invoke($subject, 'toBool', [$falsey]));
		}
	}

	/**
	 * Lazily obtain the message bus before warning about an unresolved repo GUID.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testUnresolvedRepositoryGuidUsesLazyMessageBusAndFallsBackToDefault(): void
	{
		$guid = '11111111-1111-4111-8111-111111111111';
		$messages = new MessageBus();
		$this->isolateFactory(PackageFactory::class);
		$container = new Container();
		$container->set('Package.Message', $messages, true);
		(new ReflectionProperty(PackageFactory::class, 'container'))
			->setValue(null, $container);
		$subject = $this->command(Init::class);

		$this->assertNull($this->invoke($subject, 'resolveRepoValue', [$guid]));
		$this->assertSame(
			['Repository with GUID "' . $guid . '" could not be resolved and was ignored.'],
			$messages->get('warning'),
			'Repository diagnostics must resolve the lazy bus instead of dereferencing null.'
		);
	}

	/**
	 * Delegate alias-friendly get requests and render categorized results in order.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testGetCommandDelegatesAliasesAndRendersCategorizedResults(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->command(GetCommand::class);
		$builder = $this->createMock(GetBuilder::class);
		$builder->expects($this->once())
			->method('get')
			->with('admin_view', ['alias-one', 'alias-two'])
			->willReturn([
				'local' => ['local-guid' => 'Local Label'],
				'not_found' => ['missing-guid' => 'Missing Label'],
				'added' => ['added-guid' => 'Added Label'],
			]);
		$this->setProperty($subject, 'get', $builder);
		$this->setProperty($subject, 'message', new MessageBus());
		$output = new BufferedOutput();

		$status = $subject->execute(
			new ArrayInput(['--items' => '["alias-one","alias-two"]']),
			$output
		);
		$text = $output->fetch();

		$this->assertSame(0, $status);
		$this->assertStringContainsString('Get Request', $text);
		$this->assertMatchesRegularExpression('/Added\s+1/', $text);
		$this->assertStringContainsString(' - added-guid: Added Label', $text);
		$this->assertLessThan(strpos($text, 'Local Label'), strpos($text, 'Added Label'));
		$this->assertLessThan(strpos($text, 'Missing Label'), strpos($text, 'Local Label'));
		$this->assertStringContainsString('Get completed at ', $text);
	}

	/**
	 * Resolve init identifiers, repository, and force before one builder call.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testInitCommandFiltersIdentifiersAndForwardsRepositoryAndForce(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->command(Init::class);
		$builder = $this->createMock(GetBuilder::class);
		$builder->expects($this->once())
			->method('validRepo')
			->willReturn(true);
		$builder->expects($this->once())
			->method('getValidGuids')
			->with('admin_view', ['alias', 'valid-guid'])
			->willReturn(['valid-guid']);
		$builder->expects($this->once())
			->method('init')
			->with(
				'admin_view',
				['valid-guid'],
				$this->callback(
					static fn(object $repository): bool =>
						$repository->repository === 'definitions'
				),
				true
			)
			->willReturn(['local' => [], 'not_found' => [], 'added' => ['valid-guid' => 'added']]);
		$this->setProperty($subject, 'get', $builder);
		$this->setProperty($subject, 'message', new MessageBus());
		$output = new BufferedOutput();

		$status = $subject->execute(
			new ArrayInput([
				'--items' => 'alias,valid-guid',
				'--repo' => '{"repository":"definitions"}',
				'--force' => true,
				'--resolve' => true,
			]),
			$output
		);

		$this->assertSame(0, $status);
		$this->assertStringContainsString('Some invalid GUIDs were removed', $output->fetch());
	}

	/**
	 * Make pull an unconditional forced init without exposing a force option.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testPullCommandAlwaysForcesTheInitBuilder(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->command(Pull::class);
		$builder = $this->createMock(GetBuilder::class);
		$builder->expects($this->once())
			->method('init')
			->with('admin_view', ['item-guid'], null, true)
			->willReturn(['local' => ['item-guid' => 'local'], 'not_found' => [], 'added' => []]);
		$this->setProperty($subject, 'get', $builder);
		$this->setProperty($subject, 'message', new MessageBus());
		$output = new BufferedOutput();

		$this->assertSame(
			0,
			$subject->execute(new ArrayInput(['--items' => 'item-guid']), $output)
		);
		$this->assertStringContainsString('yes (implicit)', $output->fetch());
	}

	/**
	 * Resolve reset identifiers before invoking the state-reset operation.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testResetCommandFiltersThenResetsTheSelectedItems(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->command(Reset::class);
		$builder = $this->createMock(GetBuilder::class);
		$builder->expects($this->once())
			->method('getValidGuids')
			->with('admin_view', ['alias', 'item-guid'])
			->willReturn(['item-guid']);
		$builder->expects($this->once())
			->method('reset')
			->with('admin_view', ['item-guid']);
		$this->setProperty($subject, 'get', $builder);
		$this->setProperty($subject, 'message', new MessageBus());
		$output = new BufferedOutput();

		$this->assertSame(
			0,
			$subject->execute(
				new ArrayInput(['--items' => 'alias,item-guid', '--resolve' => true]),
				$output
			)
		);
		$this->assertStringContainsString('Reset completed at ', $output->fetch());
	}

	/**
	 * Pass normalized push identifiers once to the synchronous set builder.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testPushCommandDelegatesNormalizedItemsToSetBuilder(): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->command(Push::class);
		$builder = $this->createMock(SetBuilder::class);
		$builder->expects($this->once())
			->method('items')
			->with('admin_view', ['first-guid', 'second-guid']);
		$this->setProperty($subject, 'set', $builder);
		$this->setProperty($subject, 'message', new MessageBus());
		$output = new BufferedOutput();

		$this->assertSame(
			0,
			$subject->execute(
				new ArrayInput(['--items' => ' first-guid,second-guid,first-guid ']),
				$output
			)
		);
		$this->assertStringContainsString('Push Request', $output->fetch());
	}

	/**
	 * Require GUIDs on every strict or destructive Package command.
	 *
	 * @param   class-string<PackageCommand>  $commandClass  Concrete command class.
	 * @param   bool                          $usesSet       Whether the command uses SET.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('strictGuidCommands')]
	#[Group('known-defect')]
	public function testStrictCommandsRejectNonGuidItems(
		string $commandClass,
		bool $usesSet
	): void
	{
		$this->clearPackageEnvironment();
		$subject = $this->command($commandClass);

		if ($usesSet)
		{
			$this->setProperty($subject, 'set', new SetBuilder(new Tracker(), new Container()));
		}
		else
		{
			$this->setProperty($subject, 'get', new GetBuilder(new Tracker(), new Container()));
		}

		$this->setProperty($subject, 'message', new MessageBus());
		$output = new BufferedOutput();

		$this->assertSame(
			1,
			$subject->execute(new ArrayInput(['--items' => 'plain-alias']), $output),
			$commandClass . ' promises strict GUID input unless resolution is explicitly enabled.'
		);
		$this->assertStringContainsString('GUID', $output->fetch());
	}

	/**
	 * Supply commands whose published contracts require strict GUIDs.
	 *
	 * @return  array<string, array{class-string<PackageCommand>, bool}>
	 * @since   1.0.0
	 */
	public static function strictGuidCommands(): array
	{
		return [
			'init' => [Init::class, false],
			'pull' => [Pull::class, false],
			'push' => [Push::class, true],
			'reset' => [Reset::class, false],
		];
	}

	/**
	 * Instantiate a concrete command with an isolated item boundary.
	 *
	 * @param   class-string<PackageCommand>  $class   Concrete command class.
	 * @param   string                        $entity  Entity routing key.
	 *
	 * @return  PackageCommand
	 * @since   1.0.0
	 */
	private function command(string $class, string $entity = 'admin_view'): PackageCommand
	{
		return new $class('package:test', $entity, $this->createStub(ItemInterface::class));
	}

	/**
	 * Instantiate the test-owned abstract Package command.
	 *
	 * @return  PackageConsoleFixture
	 * @since   1.0.0
	 */
	private function fixture(): PackageConsoleFixture
	{
		return new PackageConsoleFixture(
			'package:fixture',
			'admin_view',
			$this->createStub(ItemInterface::class)
		);
	}

	/**
	 * Return command-specific option names in their configured order.
	 *
	 * @param   PackageCommand  $command  Configured command.
	 *
	 * @return  array<int, string>
	 * @since   1.0.0
	 */
	private function optionNames(PackageCommand $command): array
	{
		return array_keys($command->getDefinition()->getOptions());
	}

	/**
	 * Bind parameters for direct protected-parser testing.
	 *
	 * @param   PackageCommand       $command     Configured command.
	 * @param   array<string, mixed>  $parameters  Input parameters.
	 *
	 * @return  ArrayInput
	 * @since   1.0.0
	 */
	private function boundInput(PackageCommand $command, array $parameters): ArrayInput
	{
		$input = new ArrayInput($parameters);
		$input->bind($command->getDefinition());

		return $input;
	}

	/**
	 * Invoke one protected command method through the test boundary.
	 *
	 * @param   object             $subject    Command instance.
	 * @param   string             $method     Method name.
	 * @param   array<int, mixed>  $arguments  Invocation arguments.
	 *
	 * @return  mixed
	 * @since   1.0.0
	 */
	private function invoke(object $subject, string $method, array $arguments = []): mixed
	{
		$reflection = new ReflectionMethod($subject, $method);
		$reflection->setAccessible(true);

		return $reflection->invokeArgs($subject, $arguments);
	}

	/**
	 * Inject an inherited protected collaborator into a concrete final command.
	 *
	 * @param   object  $subject  Command instance.
	 * @param   string  $name     Property name.
	 * @param   mixed   $value    Test collaborator.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function setProperty(object $subject, string $name, mixed $value): void
	{
		$property = new ReflectionProperty($subject, $name);
		$property->setValue($subject, $value);
	}

	/**
	 * Remove Package environment inputs so each source assertion is isolated.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	private function clearPackageEnvironment(): void
	{
		foreach ([
			'JCB_GET_ITEMS',
			'JCB_GET_ITEMS_FILE',
			'JCB_GET_REPO',
			'JCB_GET_REPO_FILE',
			'JCB_GET_FORCE',
			'JCB_GET_RESOLVE',
		] as $name)
		{
			$this->setEnvironmentVariable($name, null);
		}
	}
}
