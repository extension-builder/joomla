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

namespace VDM\Joomla\Tests\Data\Power;


use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\FactoryTrait;
use VDM\Joomla\Componentbuilder\Package\Builder\Get;
use VDM\Joomla\Componentbuilder\Package\Builder\Set;
use VDM\Joomla\Data\Power\Item;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Tests\Support\PowerItemFactoryFixture;
use VDM\Tests\Support\TestCase;


/**
 * Power item local/remote synchronization and retry-boundary tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Item::class)]
#[UsesClass(FactoryTrait::class)]
final class ItemTest extends TestCase
{
	/**
	 * Reset the entity-factory fixture after every test.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		PowerItemFactoryFixture::seed(null);

		parent::tearDown();
	}

	/**
	 * Select the entity fluently and immediately synchronize the local data table.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableSelectsEntityAndLocalDataTable(): void
	{
		$data = $this->createMock(ItemInterface::class);
		$data->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$subject = $this->subject($data);

		$this->assertSame($subject, $subject->table('power'));
		$this->assertSame('power', $subject->getTable());
	}

	/**
	 * Fetch remotely once after a local miss and retry the local read.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetPerformsOneRemoteFetchThenReturnsRetriedLocalItem(): void
	{
		$expected = (object) ['guid' => 'power-guid'];
		$data = $this->createMock(ItemInterface::class);
		$data->expects($this->exactly(2))->method('table')->with('power')->willReturnSelf();
		$data->expects($this->exactly(2))->method('get')->with('power-guid', 'guid')
			->willReturnOnConsecutiveCalls(null, $expected);
		$get = $this->createMock(Get::class);
		$get->expects($this->once())->method('get')->with('power', ['power-guid'])->willReturn([]);

		$this->assertSame($expected, $this->subject($data, $get)->get('power-guid'));
	}

	/**
	 * Remember a failed remote attempt and avoid unbounded recursion or repeated traffic.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRemoteMissIsRetriedOnlyOncePerEntityKeyAndValue(): void
	{
		$data = $this->createMock(ItemInterface::class);
		$data->expects($this->exactly(3))->method('table')->willReturnSelf();
		$data->expects($this->exactly(3))->method('get')->willReturn(null);
		$get = $this->createMock(Get::class);
		$get->expects($this->once())->method('get')->with('power', ['missing'])->willReturn([]);
		$subject = $this->subject($data, $get);

		$this->assertNull($subject->get('missing'));
		$this->assertNull($subject->get('missing'));
	}

	/**
	 * Apply the same bounded remote-retry contract to scalar value reads.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testValueFetchesRemoteOnceThenReturnsRetriedScalar(): void
	{
		$data = $this->createMock(ItemInterface::class);
		$data->expects($this->exactly(2))->method('table')->with('power')->willReturnSelf();
		$data->expects($this->exactly(2))->method('value')->with('power-guid', 'guid', 'name')
			->willReturnOnConsecutiveCalls(null, 'Resolved Power');
		$get = $this->createMock(Get::class);
		$get->expects($this->once())->method('get')->with('power', ['power-guid'])->willReturn([]);

		$this->assertSame(
			'Resolved Power',
			$this->subject($data, $get)->value('power-guid', 'guid', 'name')
		);
	}

	/**
	 * Publish successful local writes and keep deletes local-only.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetPublishesSuccessfulWriteWhileDeleteStaysLocal(): void
	{
		$value = (object) ['guid' => 'power-guid', 'name' => 'Power'];
		$data = $this->createMock(ItemInterface::class);
		$data->expects($this->exactly(2))->method('table')->with('power')->willReturnSelf();
		$data->expects($this->once())->method('set')->with($value, 'guid', 'update')->willReturn(true);
		$data->expects($this->once())->method('delete')->with('power-guid', 'guid')->willReturn(true);
		$set = $this->createMock(Set::class);
		$set->expects($this->once())->method('items')->with('power', ['power-guid']);
		$subject = $this->subject($data, set: $set);

		$this->assertTrue($subject->set($value, 'guid', 'update'));
		$this->assertTrue($subject->delete('power-guid'));
	}

	/**
	 * Avoid remote publication when local persistence fails and forward affected IDs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFailedSetDoesNotPublishAndIdDelegates(): void
	{
		$value = (object) ['guid' => 'power-guid'];
		$data = $this->createMock(ItemInterface::class);
		$data->expects($this->once())->method('table')->willReturnSelf();
		$data->expects($this->once())->method('set')->with($value)->willReturn(false);
		$data->expects($this->once())->method('id')->willReturn(47);
		$set = $this->createMock(Set::class);
		$set->expects($this->never())->method('items');
		$subject = $this->subject($data, set: $set);

		$this->assertFalse($subject->set($value));
		$this->assertSame(47, $subject->id());
	}

	/**
	 * Build an item with deterministic entity-scoped services.
	 *
	 * @param   ItemInterface  $data  Local item service.
	 * @param   Get|null       $get   Remote get orchestrator.
	 * @param   Set|null       $set   Remote set orchestrator.
	 *
	 * @return  Item
	 * @since   6.1.6
	 */
	private function subject(ItemInterface $data, ?Get $get = null, ?Set $set = null): Item
	{
		$container = new Container();
		$container->share('Data.Item', $data, true);
		$container->share('Package.Builder.Get', $get ?? $this->createStub(Get::class), true);
		$container->share('Package.Builder.Set', $set ?? $this->createStub(Set::class), true);
		PowerItemFactoryFixture::seed($container);

		$subject = new Item();
		$reflection = new ReflectionClass($subject);
		$reflection->getProperty('entity')->setValue($subject, 'power');
		$reflection->getProperty('entityFactory')->setValue(
			$subject,
			['power' => PowerItemFactoryFixture::class]
		);

		return $subject;
	}
}
