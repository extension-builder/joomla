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

namespace VDM\Joomla\Tests\Componentbuilder\Package\Builder;


use ArrayObject;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use VDM\Joomla\Componentbuilder\Package\Builder\Get;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Tests\Support\RecordingPackageGetHandler;
use VDM\Tests\Support\RecordingPackageGrepHandler;
use VDM\Tests\Support\TestCase;


/**
 * Package Get Builder Test.
 *
 * @since  1.0.0
 */
#[CoversClass(Get::class)]
#[UsesClass(Tracker::class)]
final class GetTest extends TestCase
{
	/**
	 * Empty and unmapped requests are no-ops with a stable result shape.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testEmptyAndUnknownRequestsAreNoOps(): void
	{
		$builder = new Get(new Tracker(), new Container());
		$empty = [
			'local' => [],
			'not_found' => [],
			'added' => [],
		];

		$this->assertSame($empty, $builder->init('admin_view', []));
		$this->assertSame($empty, $builder->init('not_an_entity', ['value']));
		$this->assertSame($empty, $builder->get('not_an_entity', ['value']));
		$this->assertSame([], $builder->getValidGuids('not_an_entity', ['value']));
		$this->assertFalse($builder->validRepo('not_an_entity', (object) []));
	}

	/**
	 * Grep capabilities own identifier and repository validation.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testGrepCapabilityOwnsGuidAndRepositoryValidation(): void
	{
		$repository = (object) ['repository' => 'definitions'];
		$grep = new RecordingPackageGrepHandler(
			static fn(array $items): array => ['resolved-' . $items[0]],
			static fn(object $repo): bool => $repo->repository === 'definitions'
		);
		$container = new Container();
		$container->set('AdminView.Grep', $grep, true);
		$builder = new Get(new Tracker(), $container);

		$this->assertSame(['resolved-alias'], $builder->getValidGuids('admin_view', ['alias']));
		$this->assertTrue($builder->validRepo('admin_view', $repository));
		$this->assertSame([['alias']], $grep->guidCalls());
		$this->assertSame([$repository], $grep->repoCalls());
		$this->assertSame([], $builder->getValidGuids('component_admin_views', ['missing-capability']));
		$this->assertFalse($builder->validRepo('component_admin_views', $repository));
	}

	/**
	 * Entity, dependency, file, and folder queues drain in deterministic order.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testGetDrainsRecursiveDependenciesAndMergesCategorizedResults(): void
	{
		$tracker = new Tracker();
		$events = new ArrayObject();
		$container = new Container();
		$container->set(
			'AdminView.Grep',
			new RecordingPackageGrepHandler(static fn(array $items): array => ['admin-guid']),
			true
		);
		$container->set(
			'CustomCode.Grep',
			new RecordingPackageGrepHandler(static fn(array $items): array => $items),
			true
		);
		$container->set(
			'AdminView.Remote.Get',
			new RecordingPackageGetHandler(
				static function (array $items) use ($tracker, $events): array
				{
					$events[] = 'admin';
					$tracker->set('get.custom_code.first', ['value' => 'custom-guid']);

					return [
						'local' => ['same' => 'admin-first', 'admin-guid' => 'admin_view'],
						'not_found' => [],
						'added' => [],
					];
				}
			),
			true
		);
		$container->set(
			'CustomCode.Remote.Get',
			new RecordingPackageGetHandler(
				static function (array $items) use ($tracker, $events): array
				{
					$events[] = 'custom-code';
					$tracker->set('file.get.asset', ['key' => 'file-key']);
					$tracker->set('folder.get.asset', ['key' => 'folder-key']);

					return [
						'local' => ['same' => 'dependency-second'],
						'not_found' => [],
						'added' => ['custom-guid' => 'custom_code'],
					];
				}
			),
			true
		);
		$container->set(
			'File.Remote.Get',
			new RecordingPackageGetHandler(
				static function (array $items) use ($events): array
				{
					$events[] = 'file';

					return [
						'local' => [],
						'not_found' => [],
						'added' => ['file-key' => 'file-path'],
					];
				}
			),
			true
		);
		$container->set(
			'Folder.Remote.Get',
			new RecordingPackageGetHandler(
				static function (array $items) use ($events): array
				{
					$events[] = 'folder';

					return [
						'local' => [],
						'not_found' => ['folder-key' => 'folder-path'],
						'added' => [],
					];
				}
			),
			true
		);

		$result = (new Get($tracker, $container))->get('admin_view', ['alias']);

		$this->assertSame(['admin', 'custom-code', 'file', 'folder'], $events->getArrayCopy());
		$this->assertSame('admin-first', $result['local']['same']);
		$this->assertSame('admin_view', $result['local']['admin-guid']);
		$this->assertSame('custom_code', $result['added']['custom-guid']);
		$this->assertSame('file-path', $result['added']['file-key']);
		$this->assertSame('folder-path', $result['not_found']['folder-key']);
		$this->assertNull($tracker->get('get'));
		$this->assertNull($tracker->get('file.get'));
		$this->assertNull($tracker->get('folder.get'));
	}

	/**
	 * Explicit repository and force settings reach every recursive capability.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testInitPropagatesRepositoryAndForceAcrossQueues(): void
	{
		$tracker = new Tracker();
		$repository = (object) ['guid' => 'repo-guid'];
		$container = new Container();
		$admin = new RecordingPackageGetHandler(
			static function (array $items, ?object $repo, bool $force) use ($tracker): array
			{
				$tracker->set('get.custom_code.dependency', (object) ['value' => 'custom-guid']);
				$tracker->set('file.get.file', ['key' => 'file-key']);
				$tracker->set('folder.get.folder', ['key' => 'folder-key']);

				return ['local' => [], 'not_found' => [], 'added' => []];
			}
		);
		$custom = new RecordingPackageGetHandler();
		$file = new RecordingPackageGetHandler();
		$folder = new RecordingPackageGetHandler();
		$container->set('AdminView.Remote.Get', $admin, true);
		$container->set('CustomCode.Remote.Get', $custom, true);
		$container->set('File.Remote.Get', $file, true);
		$container->set('Folder.Remote.Get', $folder, true);

		(new Get($tracker, $container))->init('admin_view', ['admin-guid'], $repository, true);

		$this->assertSame([['admin-guid'], $repository, true], $admin->calls()[0]['arguments']);
		$this->assertSame([['custom-guid'], $repository, true], $custom->calls()[0]['arguments']);
		$this->assertSame([['file' => ['key' => 'file-key']], $repository, true], $file->calls()[0]['arguments']);
		$this->assertSame([['folder' => ['key' => 'folder-key']], $repository, true], $folder->calls()[0]['arguments']);
	}

	/**
	 * A missing entity handler remains a no-op while supported queued work runs.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testMissingEntityCapabilityDoesNotBlockSupportedDependencies(): void
	{
		$tracker = new Tracker();
		$tracker->set('get.custom_code.dependency', ['value' => 'custom-guid']);
		$container = new Container();
		$container->set('AdminView.Grep', new RecordingPackageGrepHandler(), true);
		$container->set('CustomCode.Grep', new RecordingPackageGrepHandler(), true);
		$custom = new RecordingPackageGetHandler();
		$container->set('CustomCode.Remote.Get', $custom, true);

		$result = (new Get($tracker, $container))->get('admin_view', ['admin-guid']);

		$this->assertSame([['custom-guid'], null, false], $custom->calls()[0]['arguments']);
		$this->assertSame(['local' => [], 'not_found' => [], 'added' => []], $result);
		$this->assertNull($tracker->get('get'));
	}

	/**
	 * Reset follows only inbound dependency edges and then resets both asset queues.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testResetFollowsInboundEdgesAndDrainsAssets(): void
	{
		$tracker = new Tracker();
		$events = new ArrayObject();
		$container = new Container();
		$container->set(
			'AdminView.Remote.Get',
			new RecordingPackageGetHandler(
				null,
				static function (array $items) use ($tracker, $events): bool
				{
					$events[] = 'admin';
					$tracker->set('get.custom_code.in', ['direction' => 'in', 'value' => 'child-guid']);
					$tracker->set('get.custom_code.out', (object) ['direction' => 'out', 'value' => 'parent-guid']);

					return true;
				}
			),
			true
		);
		$custom = new RecordingPackageGetHandler(
			null,
			static function (array $items) use ($tracker, $events): bool
			{
				$events[] = 'custom-code';
				$tracker->set('file.get.file', ['key' => 'file-key']);
				$tracker->set('folder.get.folder', ['key' => 'folder-key']);

				return true;
			}
		);
		$file = new RecordingPackageGetHandler(
			null,
			static function (array $items) use ($events): bool
			{
				$events[] = 'file';

				return true;
			}
		);
		$folder = new RecordingPackageGetHandler(
			null,
			static function (array $items) use ($events): bool
			{
				$events[] = 'folder';

				return true;
			}
		);
		$container->set('CustomCode.Remote.Get', $custom, true);
		$container->set('File.Remote.Get', $file, true);
		$container->set('Folder.Remote.Get', $folder, true);

		(new Get($tracker, $container))->reset('admin_view', ['admin-guid']);

		$this->assertSame(['admin', 'custom-code', 'file', 'folder'], $events->getArrayCopy());
		$this->assertSame(['child-guid'], $custom->calls()[0]['arguments'][0]);
		$this->assertSame(['file' => ['key' => 'file-key']], $file->calls()[0]['arguments'][0]);
		$this->assertSame(['folder' => ['key' => 'folder-key']], $folder->calls()[0]['arguments'][0]);
		$this->assertNull($tracker->get('get'));
		$this->assertNull($tracker->get('file.get'));
		$this->assertNull($tracker->get('folder.get'));
	}

	/**
	 * Exceptions from a registered capability remain visible to the caller.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testRegisteredCapabilityExceptionsPropagate(): void
	{
		$container = new Container();
		$container->set(
			'AdminView.Grep',
			new RecordingPackageGrepHandler(
				static function (array $items): array
				{
					throw new RuntimeException('repository index is invalid');
				}
			),
			true
		);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('repository index is invalid');

		(new Get(new Tracker(), $container))->get('admin_view', ['admin-guid']);
	}

	/**
	 * Reduced containers must not retain asset-reset work for a later operation.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testResetDrainsAssetQueuesWhenCapabilitiesAreUnavailable(): void
	{
		$tracker = new Tracker();
		$tracker->set('file.get.file', ['key' => 'file-key']);
		$tracker->set('folder.get.folder', ['key' => 'folder-key']);

		(new Get($tracker, new Container()))->reset('admin_view', ['admin-guid']);

		$this->assertSame(
			[null, null],
			[$tracker->get('file.get'), $tracker->get('folder.get')],
			'Unsupported reset queues must be discarded inside the current operation scope.'
		);
	}

	/**
	 * Categorized results belong to one public operation, not the builder lifetime.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testIndependentGetOperationsDoNotReusePriorResults(): void
	{
		$container = new Container();
		$container->set('AdminView.Grep', new RecordingPackageGrepHandler(), true);
		$container->set(
			'AdminView.Remote.Get',
			new RecordingPackageGetHandler(
				static fn(array $items): array => [
					'local' => [$items[0] => 'admin_view'],
					'not_found' => [],
					'added' => [],
				]
			),
			true
		);
		$builder = new Get(new Tracker(), $container);
		$builder->get('admin_view', ['first-guid']);

		$this->assertSame(
			[
				'local' => ['second-guid' => 'admin_view'],
				'not_found' => [],
				'added' => [],
			],
			$builder->get('admin_view', ['second-guid']),
			'Each API/MCP-style Package operation needs an isolated result set.'
		);
	}
}
