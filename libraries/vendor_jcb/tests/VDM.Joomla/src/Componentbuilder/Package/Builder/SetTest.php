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
use VDM\Joomla\Componentbuilder\Package\Builder\Set;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Tests\Support\RecordingPackageSetHandler;
use VDM\Tests\Support\TestCase;


/**
 * Package Set Builder Test.
 *
 * @since  1.0.0
 */
#[CoversClass(Set::class)]
#[UsesClass(Tracker::class)]
final class SetTest extends TestCase
{
	/**
	 * Empty and unmapped requests do not resolve arbitrary container aliases.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testEmptyAndUnknownRequestsAreNoOps(): void
	{
		$handler = new RecordingPackageSetHandler();
		$container = new Container();
		$container->set('NotAnEntity.Remote.Set', $handler, true);
		$builder = new Set(new Tracker(), $container);

		$builder->items('admin_view', []);
		$builder->items('not_an_entity', ['guid']);

		$this->assertSame([], $handler->calls());
	}

	/**
	 * Entity, dependency, file, and folder queues drain in discovery order.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testItemsDrainsRecursiveAndNewlyDiscoveredQueues(): void
	{
		$tracker = new Tracker();
		$events = new ArrayObject();
		$container = new Container();
		$admin = new RecordingPackageSetHandler(
			static function (array $items) use ($tracker, $events): bool
			{
				$events[] = 'admin';
				$tracker->set('set.custom_code.array', ['value' => 'custom-array']);
				$tracker->set('set.custom_code.object', (object) ['value' => 'custom-object']);
				$tracker->set('set.custom_code.empty', ['value' => '']);
				$tracker->set('set.custom_code.unsupported', 'raw-string');

				return true;
			}
		);
		$custom = new RecordingPackageSetHandler(
			static function (array $items) use ($tracker, $events): bool
			{
				$events[] = 'custom-code';
				$tracker->set('file.set.first', ['key' => 'file-one']);
				$tracker->set('folder.set.first', ['key' => 'folder-one']);

				return false;
			}
		);
		$fileCalls = 0;
		$file = new RecordingPackageSetHandler(
			static function (array $items) use ($tracker, $events, &$fileCalls): bool
			{
				$fileCalls++;
				$events[] = 'file-' . $fileCalls;

				if ($fileCalls === 1)
				{
					$tracker->set('file.set.second', ['key' => 'file-two']);
				}

				return true;
			}
		);
		$folder = new RecordingPackageSetHandler(
			static function (array $items) use ($events): bool
			{
				$events[] = 'folder';

				return true;
			}
		);
		$container->set('AdminView.Remote.Set', $admin, true);
		$container->set('CustomCode.Remote.Set', $custom, true);
		$container->set('File.Remote.Set', $file, true);
		$container->set('Folder.Remote.Set', $folder, true);

		(new Set($tracker, $container))->items('admin_view', ['admin-guid']);

		$this->assertSame(
			['admin', 'custom-code', 'file-1', 'file-2', 'folder'],
			$events->getArrayCopy()
		);
		$this->assertSame([['admin-guid']], $admin->calls());
		$this->assertSame([['custom-array', 'custom-object']], $custom->calls());
		$this->assertSame(
			[
				['first' => ['key' => 'file-one']],
				['second' => ['key' => 'file-two']],
			],
			$file->calls()
		);
		$this->assertSame([['first' => ['key' => 'folder-one']]], $folder->calls());
		$this->assertNull($tracker->get('set'));
		$this->assertNull($tracker->get('file.set'));
		$this->assertNull($tracker->get('folder.set'));
	}

	/**
	 * Unsupported capabilities are skipped and their queues cannot leak forward.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testMissingCapabilitiesStillDrainEveryQueue(): void
	{
		$tracker = new Tracker();
		$tracker->set('set.custom_code.one', ['value' => 'custom-guid']);
		$tracker->set('file.set.one', ['key' => 'file-key']);
		$tracker->set('folder.set.one', ['key' => 'folder-key']);

		(new Set($tracker, new Container()))->items('admin_view', ['admin-guid']);

		$this->assertNull($tracker->get('set'));
		$this->assertNull($tracker->get('file.set'));
		$this->assertNull($tracker->get('folder.set'));
	}

	/**
	 * A registered capability exception remains visible and stops later phases.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testRegisteredCapabilityExceptionsPropagate(): void
	{
		$tracker = new Tracker();
		$tracker->set('file.set.pending', ['key' => 'file-key']);
		$container = new Container();
		$container->set(
			'AdminView.Remote.Set',
			new RecordingPackageSetHandler(
				static function (array $items): bool
				{
					throw new RuntimeException('remote write rejected');
				}
			),
			true
		);

		try
		{
			(new Set($tracker, $container))->items('admin_view', ['admin-guid']);
			$this->fail('The remote capability exception was suppressed.');
		}
		catch (RuntimeException $error)
		{
			$this->assertSame('remote write rejected', $error->getMessage());
			$this->assertNotNull($tracker->get('file.set'));
		}
	}

	/**
	 * Automation callers receive the failure status returned by an entity handler.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testEntityFailureStatusIsReturnedToTheCaller(): void
	{
		$container = new Container();
		$container->set(
			'AdminView.Remote.Set',
			new RecordingPackageSetHandler(static fn(array $items): bool => false),
			true
		);

		$result = (new Set(new Tracker(), $container))->items('admin_view', ['admin-guid']);

		$this->assertFalse(
			$result,
			'Package push must not report an implicit success when the remote handler failed.'
		);
	}
}
