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

namespace VDM\Joomla\Tests\Abstraction\Remote;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Remote\Get;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Joomla\Componentbuilder\Power\Interfaces\TableInterface;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Interfaces\GrepInterface;
use VDM\Tests\Support\RemoteConfigFixture;
use VDM\Tests\Support\RemoteGetFixture;
use VDM\Tests\Support\TestCase;

/**
 * Shared remote-get classification, caching, listing, and reset tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Get::class)]
#[UsesClass(Tracker::class)]
#[UsesClass(MessageBus::class)]
final class GetTest extends TestCase
{
	/**
	 * Classify local, remotely added, missing, and already-processed items exactly once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInitClassifiesItemsAndHonorsTrackerDeduplication(): void
	{
		$remote = (object) ['guid' => 'remote'];
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->once())->method('setBranchField')->with('read_branch');
		$grep->expects($this->exactly(2))
			->method('get')
			->willReturnCallback(
				static fn (string $guid, ?array $order, ?object $repo): ?object =>
					$guid === 'remote' ? $remote : null
			);
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->exactly(4))->method('table')->with('power')->willReturnSelf();
		$item->expects($this->exactly(3))
			->method('value')
			->willReturnCallback(static fn (string $guid): ?int => $guid === 'local' ? 17 : null);
		$item->expects($this->once())->method('set')->with($remote, 'guid')->willReturn(true);
		$tracker = new Tracker();
		$tracker->set('save.power.guid|processed', true);
		$subject = $this->subject($grep, $item, $tracker);

		$this->assertSame(
			[
				'local' => ['local' => 'power'],
				'not_found' => ['missing' => 'power'],
				'added' => ['remote' => 'power'],
			],
			$subject->init(['local', 'remote', 'missing', 'processed'])
		);
		$this->assertTrue($tracker->exists('save.power.guid|local'));
		$this->assertTrue($tracker->exists('save.power.guid|remote'));
		$this->assertTrue($tracker->exists('save.power.guid|missing'));
	}

	/**
	 * Bypass local lookup under force while preserving remote classification.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testInitForceBypassesLocalValueLookup(): void
	{
		$remote = (object) ['guid' => 'existing'];
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->once())->method('setBranchField')->with('read_branch');
		$grep->expects($this->once())
			->method('get')
			->with('existing', ['remote'], null)
			->willReturn($remote);
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$item->expects($this->never())->method('value');
		$item->expects($this->once())->method('set')->with($remote, 'guid')->willReturn(true);

		$this->assertSame(
			['local' => [], 'not_found' => [], 'added' => ['existing' => 'power']],
			$this->subject($grep, $item)->init(['existing'], force: true)
		);
	}

	/**
	 * Cache item persistence outcome and avoid repeating remote or data work.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemCachesPersistenceOutcomeByTableAndGuidField(): void
	{
		$remote = (object) ['guid' => 'remote'];
		$repo = (object) ['guid' => 'repo'];
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->once())
			->method('get')
			->with('remote', ['local', 'remote'], $repo)
			->willReturn($remote);
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$item->expects($this->once())->method('set')->with($remote, 'guid')->willReturn(true);
		$tracker = new Tracker();
		$subject = $this->subject($grep, $item, $tracker);

		$this->assertTrue($subject->item('remote', ['local', 'remote'], $repo));
		$this->assertTrue($subject->item('remote', ['remote'], null));
		$this->assertTrue($tracker->get('save.power.guid|remote'));
	}

	/**
	 * Delegate path access after selecting the remote read branch.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPathAccessDelegatesReadBranchAndPreservesResults(): void
	{
		$path = (object) ['guid' => 'repo'];
		$paths = [$path];
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->exactly(2))->method('setBranchField')->with('read_branch');
		$grep->expects($this->once())->method('getPath')->with('repo')->willReturn($path);
		$grep->expects($this->once())->method('getPaths')->willReturn($paths);
		$subject = $this->subject($grep, $this->createStub(ItemInterface::class));

		$this->assertSame($path, $subject->path('repo'));
		$this->assertSame($paths, $subject->paths());
	}

	/**
	 * Normalize repository indexes, mark local records, and remove entries without GUIDs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testListNormalizesRepositoryIndexesAndLocalState(): void
	{
		$repository = (object) [
			'guid' => 'repo',
			'index' => [
				'power' => (object) [
					'first' => (object) ['name' => 'First', 'guid' => 'one', 'extra' => 'kept'],
					'invalid' => (object) ['name' => 'Missing GUID'],
				],
			],
		];
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->once())->method('setBranchField')->with('read_branch');
		$grep->expects($this->once())->method('getPathsIndexes')->willReturn([$repository]);
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$item->expects($this->once())->method('value')->with('one', 'guid')->willReturn(12);
		$result = $this->subject($grep, $item)->list();

		$this->assertCount(1, $result);
		$this->assertSame('repo', $result[0]->guid);
		$this->assertFalse(isset($result[0]->index->invalid));
		$this->assertSame(
			['name', 'path', 'settings', 'guid', 'local', 'extra'],
			array_keys(get_object_vars($result[0]->index->first))
		);
		$this->assertTrue($result[0]->index->first->local);
		$this->assertSame('kept', $result[0]->index->first->extra);
		$this->assertSame('one', $repository->index['power']->first->guid);
	}

	/**
	 * Report each failed reset and emit success only when every item persists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResetReportsPartialFailureAndAllSuccess(): void
	{
		$grep = $this->createMock(GrepInterface::class);
		$grep->expects($this->exactly(3))->method('setBranchField')->with('read_branch');
		$grep->expects($this->exactly(3))
			->method('get')
			->with($this->isString(), ['remote'], null)
			->willReturnCallback(
			static fn (string $guid): ?object => $guid === 'missing' ? null : (object) ['guid' => $guid]
		);
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->exactly(2))->method('table')->with('power')->willReturnSelf();
		$item->expects($this->exactly(2))
			->method('set')
			->with($this->isInstanceOf(\stdClass::class), 'guid')
			->willReturn(true);
		$messages = new MessageBus();
		$subject = $this->subject($grep, $item, new Tracker(), $messages);
		$subject->area('Power');

		$this->assertFalse($subject->reset(['first', 'missing']));
		$this->assertSame(['The power item:missing did not reset.'], $messages->get('warning'));

		$messages->clear();
		$this->assertTrue($subject->reset(['second']));
		$this->assertSame(['The power item(s) was reset.'], $messages->get('success'));
		$this->assertFalse($subject->reset([]));
	}

	/**
	 * Construct a remote-get fixture with deterministic configuration.
	 *
	 * @param   GrepInterface          $grep      Repository lookup.
	 * @param   ItemInterface          $item      Local persistence.
	 * @param   Tracker|null           $tracker   Operation tracker.
	 * @param   MessageBus|null        $messages  Message bus.
	 *
	 * @return  RemoteGetFixture
	 * @since   6.1.6
	 */
	private function subject(
		GrepInterface $grep,
		ItemInterface $item,
		?Tracker $tracker = null,
		?MessageBus $messages = null
	): RemoteGetFixture
	{
		$table = $this->createStub(TableInterface::class);
		$table->method('titleName')->willReturn('name');
		$config = new RemoteConfigFixture($table);

		return new RemoteGetFixture(
			$config,
			$grep,
			$item,
			$tracker ?? new Tracker(),
			$messages ?? new MessageBus(),
			'power'
		);
	}
}
