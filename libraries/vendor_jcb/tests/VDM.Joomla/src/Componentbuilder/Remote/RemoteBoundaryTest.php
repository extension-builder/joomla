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

namespace VDM\Joomla\Tests\Componentbuilder\Remote;


use Joomla\CMS\Application\CMSApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionMethod;
use VDM\Joomla\Componentbuilder\JoomlaPower\Grep as JoomlaPowerGrep;
use VDM\Joomla\Componentbuilder\Network\Resolve;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Joomla\Componentbuilder\Power\Grep as PowerGrep;
use VDM\Joomla\Componentbuilder\Power\Table;
use VDM\Joomla\Componentbuilder\Remote\Get;
use VDM\Joomla\Componentbuilder\Remote\Grep as RemoteGrep;
use VDM\Joomla\Componentbuilder\Remote\Set;
use VDM\Joomla\Componentbuilder\Remote\SetDependenciesTrait;
use VDM\Joomla\Componentbuilder\Remote\Version;
use VDM\Joomla\Componentbuilder\Repository\Grep as RepositoryGrep;
use VDM\Joomla\Componentbuilder\Snippet\Grep as SnippetGrep;
use VDM\Joomla\Componentbuilder\Snippet\Remote\Config;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\Git\Repository\ContentsInterface;
use VDM\Joomla\Interfaces\GrepInterface;
use VDM\Joomla\Interfaces\Remote\ConfigInterface;
use VDM\Joomla\Interfaces\Remote\GetInterface;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Remote queue, repository boundary, local fallback, and version contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Get::class)]
#[CoversClass(RemoteGrep::class)]
#[CoversClass(Set::class)]
#[CoversTrait(SetDependenciesTrait::class)]
#[CoversClass(Version::class)]
#[CoversClass(PowerGrep::class)]
#[CoversClass(JoomlaPowerGrep::class)]
#[CoversClass(RepositoryGrep::class)]
#[CoversClass(SnippetGrep::class)]
#[UsesClass(Tracker::class)]
#[UsesClass(Config::class)]
#[UsesClass(Table::class)]
final class RemoteBoundaryTest extends FilesystemTestCase
{
	/**
	 * Protect init classification, tracker de-duplication, and remote persistence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetInitClassifiesLocalMissingAndAddedItemsExactlyOnce(): void
	{
		$tracker = new Tracker();
		$tracker->set('save.snippet.guid|skip', true);
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->once())->method('setBranchField')->with('read_branch');
		$grep->expects($this->exactly(2))
			->method('get')
			->willReturnMap([
				['missing', ['remote'], null, null],
				['remote', ['remote'], null, (object) ['guid' => 'remote', 'name' => 'Fetched']],
			]);
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->exactly(4))->method('table')->with('snippet')->willReturnSelf();
		$item->expects($this->exactly(3))
			->method('value')
			->willReturnMap([
				['local', 'guid', 'present'],
				['missing', 'guid', null],
				['remote', 'guid', null],
			]);
		$item->expects($this->once())
			->method('set')
			->with((object) ['guid' => 'remote', 'name' => 'Fetched'], 'guid')
			->willReturn(true);
		$subject = new Get(
			new Config(new Table()),
			$grep,
			$item,
			$tracker,
			new MessageBus()
		);

		$this->assertInstanceOf(GetInterface::class, $subject);
		$this->assertSame(
			[
				'local' => ['local' => 'snippet'],
				'not_found' => ['missing' => 'snippet'],
				'added' => ['remote' => 'snippet'],
			],
			$subject->init(['skip', 'local', 'missing', 'remote'])
		);
		$this->assertTrue($tracker->exists('save.snippet.guid|remote'));
	}

	/**
	 * Protect dependency queue names, file pointers, validation, and save wins.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDependencyTraitQueuesOnlyUnresolvedValidDependencies(): void
	{
		$tracker = new Tracker();
		$tracker->set('save.power.guid|already', true);
		$tracker->set('asset.save.path--to--icon', true);
		$subject = new RemoteDependencyFixture($tracker);
		$subject->queue((object) [
			'@dependencies' => [
				['key' => 'guid', 'value' => 'new', 'entity' => 'power', 'table' => 'power'],
				(object) ['key' => 'guid', 'value' => 'already', 'entity' => 'power', 'table' => 'power'],
				['key' => 'path.to.file', 'value' => 'file', 'entity' => 'asset', 'table' => 'file_system'],
				['key' => 'path.to.icon', 'value' => 'icon', 'entity' => 'asset', 'table' => 'file_system'],
				['key' => '', 'value' => 'invalid', 'entity' => 'power'],
			],
		]);

		$this->assertTrue($tracker->exists('get.power.guid|new'));
		$this->assertFalse($tracker->exists('get.power.guid|already'));
		$this->assertTrue($tracker->exists('asset.get.path--to--file'));
		$this->assertFalse($tracker->exists('asset.get.path--to--icon'));
		$this->assertCount(3, $tracker);
	}

	/**
	 * Protect dependency equality as order-insensitive but value-sensitive.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetComparesDependencySetsWithoutOrderSensitivity(): void
	{
		$subject = (new ReflectionClass(Set::class))->newInstanceWithoutConstructor();
		$method = new ReflectionMethod(Set::class, 'dependenciesEqual');
		$left = (object) ['@dependencies' => [
			(object) ['key' => 'guid', 'value' => 'one', 'entity' => 'power'],
			(object) ['key' => 'guid', 'value' => 'two', 'entity' => 'power'],
		]];
		$reordered = (object) ['@dependencies' => array_reverse($left->{'@dependencies'})];
		$changed = (object) ['@dependencies' => [
			(object) ['key' => 'guid', 'value' => 'one', 'entity' => 'power'],
			(object) ['key' => 'guid', 'value' => 'three', 'entity' => 'power'],
		]];

		$this->assertTrue($method->invoke($subject, $left, $reordered));
		$this->assertFalse($method->invoke($subject, $left, $changed));
		$this->assertTrue($method->invoke($subject, null, (object) []));
	}

	/**
	 * Protect concrete repository target identities and normalization state.
	 *
	 * @param   class-string  $class   Concrete grep class.
	 * @param   string|null   $target  Network target.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('grepProvider')]
	public function testConcreteGrepsExposeTargetAndNormalizeRepositories(string $class, ?string $target): void
	{
		$config = $this->createStub(ConfigInterface::class);
		$config->method('getTable')->willReturn('entity');
		$resolve = (new ReflectionClass(Resolve::class))->newInstanceWithoutConstructor();
		$subject = new $class(
			$config,
			$this->createStub(ContentsInterface::class),
			$resolve,
			new Tracker(),
			[],
			null,
			$this->createStub(CMSApplication::class)
		);
		$repo = (object) [
			'organisation' => ' acme ',
			'repository' => ' demo ',
			'read_branch' => 'default',
		];

		$this->assertSame($target, $subject->getNetworkTarget());
		$this->assertTrue($subject->validRepo($repo));
		$this->assertSame('acme/demo', $repo->path);
		$this->assertNull($repo->read_branch);
		$this->assertTrue($repo->grep_validated);
		$invalid = (object) ['organisation' => '', 'repository' => 'demo'];
		$this->assertFalse($subject->validRepo($invalid));
	}

	/**
	 * Supply concrete repository discovery implementations.
	 *
	 * @return  array<string, array{0: class-string, 1: string|null}>
	 * @since   6.1.6
	 */
	public static function grepProvider(): array
	{
		return [
			'Super Power local and remote' => [PowerGrep::class, null],
			'Joomla Power remote' => [JoomlaPowerGrep::class, 'joomla-powers'],
			'Repository remote' => [RepositoryGrep::class, 'repository'],
			'Snippet remote' => [SnippetGrep::class, 'snippet'],
		];
	}

	/**
	 * Protect local Super Power settings and source-file composition.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPowerGrepLoadsLocalSettingsAndSourceCode(): void
	{
		$this->writeTemporaryFile('repo/settings.json', '{"guid":"power-guid","name":"Demo"}');
		$this->writeTemporaryFile('repo/Demo.php', "final class Demo\n{\n}\n");
		$subject = $this->grep(PowerGrep::class);
		$path = (object) [
			'full_path' => $this->temporaryPath('repo'),
			'local' => (object) [
				'power-guid' => (object) ['settings' => 'settings.json', 'power' => 'Demo.php'],
			],
		];
		$method = new ReflectionMethod(PowerGrep::class, 'getLocal');

		$power = $method->invoke($subject, $path, 'power-guid');
		$this->assertSame('power-guid', $power->guid);
		$this->assertSame('Demo', $power->name);
		$this->assertSame("final class Demo\n{\n}\n", $power->main_class_code);
	}

	/**
	 * Protect the remote cascade's null result when no repository contains a GUID.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRemoteGrepReturnsNullWhenNoRemotePathContainsGuid(): void
	{
		$subject = $this->grep(SnippetGrep::class);
		$method = new ReflectionMethod(RemoteGrep::class, 'searchRemote');

		$this->assertNull($method->invoke($subject, 'missing-guid'));
	}

	/**
	 * Protect version normalization, classification, major filtering, and sorting.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testVersionGroupsAndSortsStableAndPrereleaseTags(): void
	{
		$subject = new Version('acme', 'demo', 'acme', 'demo');
		$tags = [
			(object) ['name' => 'v6.1.0-beta2'],
			(object) ['name' => 'v5.9.9'],
			(object) ['name' => '6.0.0'],
			(object) ['name' => '6.2.0'],
			(object) ['name' => '6.1.0-rc1'],
			(object) ['invalid' => true],
		];
		$grouped = $this->invoke($subject, 'groupTagsByType', [$tags, '6']);

		$this->assertSame(['6.2.0', '6.0.0'], array_column($grouped['stable'], 'name'));
		$this->assertSame(['6.1.0-rc1', 'v6.1.0-beta2'], array_column($grouped['pre'], 'name'));
		$this->assertSame('6.1.0', $this->invoke($subject, 'normalizeVersion', [' V6.1.0']));
		$this->assertSame('beta', $this->invoke($subject, 'getVersionType', ['6.1.0-beta2']));
		$this->assertSame('stable', $this->invoke($subject, 'getVersionType', ['6.1.0']));
		$this->assertTrue($this->invoke($subject, 'isLatestPreRelease', ['6.1.0-rc1', $grouped['pre']]));
		$this->assertFalse($this->invoke($subject, 'isLatestPreRelease', ['6.1.0-beta2', $grouped['pre']]));
	}

	/**
	 * Protect escaped, source-labelled detail aggregation for remote failures.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testVersionMergesAndEscapesRepositoryErrors(): void
	{
		$subject = new Version('acme', 'demo', 'acme', 'demo');
		$result = $this->invoke($subject, 'mergeErrors', [[
			'error' => 'Unable to retrieve tags.',
			'github-error' => '<script>alert(1)</script>',
			'gitea-error' => 'timeout',
		]]);

		$this->assertStringStartsWith('Unable to retrieve tags.', $result['error']);
		$this->assertStringContainsString('<strong>Github:</strong> &lt;script&gt;alert(1)&lt;/script&gt;', $result['error']);
		$this->assertStringContainsString('<strong>Gitea:</strong> timeout', $result['error']);
		$this->assertStringNotContainsString('<script>alert(1)</script>', $result['error']);
	}

	/**
	 * Construct a concrete grep with isolated, non-network collaborators.
	 *
	 * @param   class-string  $class  Concrete grep class.
	 *
	 * @return  object
	 * @since   6.1.6
	 */
	private function grep(string $class): object
	{
		$config = $this->createStub(ConfigInterface::class);
		$config->method('getTable')->willReturn('entity');

		return new $class(
			$config,
			$this->createStub(ContentsInterface::class),
			(new ReflectionClass(Resolve::class))->newInstanceWithoutConstructor(),
			new Tracker(),
			[],
			null,
			$this->createStub(CMSApplication::class)
		);
	}

	/**
	 * Invoke one protected method while preserving its production implementation.
	 *
	 * @param   object              $subject    Subject object.
	 * @param   string              $method     Method name.
	 * @param   array<int, mixed>   $arguments  Arguments.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function invoke(object $subject, string $method, array $arguments): mixed
	{
		return (new ReflectionMethod($subject, $method))->invokeArgs($subject, $arguments);
	}
}


/**
 * Exposes dependency queueing through its real Tracker boundary.
 *
 * @since  6.1.6
 */
final class RemoteDependencyFixture
{
	use SetDependenciesTrait;

	/**
	 * Dependency tracker consumed by the production trait.
	 *
	 * @var    Tracker
	 * @since  6.1.6
	 */
	protected Tracker $tracker;

	/**
	 * Create a dependency queue fixture.
	 *
	 * @param   Tracker  $tracker  Dependency tracker.
	 *
	 * @since   6.1.6
	 */
	public function __construct(Tracker $tracker)
	{
		$this->tracker = $tracker;
	}

	/**
	 * Queue dependencies using the production trait.
	 *
	 * @param   object  $power  Remote item with dependencies.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function queue(object $power): void
	{
		$this->setDependencies($power);
	}
}
