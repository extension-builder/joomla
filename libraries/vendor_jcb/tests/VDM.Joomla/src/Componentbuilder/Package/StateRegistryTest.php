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

namespace VDM\Joomla\Tests\Componentbuilder\Package;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Tests\Support\TestCase;


/**
 * Package State Registry Test.
 *
 * @since  1.0.0
 */
#[CoversClass(Tracker::class)]
#[CoversClass(MessageBus::class)]
final class StateRegistryTest extends TestCase
{
	/**
	 * Tracker paths preserve independent entity, file, and folder queue shapes.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testTrackerKeepsSemanticQueuesIndependent(): void
	{
		$tracker = new Tracker();
		$entity = ['value' => 'entity-guid', 'direction' => 'out'];
		$file = ['key' => 'file-key', 'value' => 'images/icon.svg'];
		$folder = ['key' => 'folder-key', 'value' => 'assets'];
		$tracker->set('get.admin_view.guid|entity-guid', $entity);
		$tracker->set('file.get.file-key', $file);
		$tracker->set('folder.set.folder-key', $folder);

		$this->assertSame(
			['admin_view' => ['guid|entity-guid' => $entity]],
			$tracker->get('get')
		);
		$this->assertSame(['file-key' => $file], $tracker->get('file.get'));
		$this->assertSame(['folder-key' => $folder], $tracker->get('folder.set'));

		$tracker->remove('get');

		$this->assertNull($tracker->get('get'));
		$this->assertSame(['file-key' => $file], $tracker->get('file.get'));
		$this->assertSame(['folder-key' => $folder], $tracker->get('folder.set'));
	}

	/**
	 * Message categories append ordered diagnostics rather than concatenating them.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testMessageBusAppendsOrderedMessagesByCategory(): void
	{
		$messages = new MessageBus();
		$messages->add('warning', 'first warning');
		$messages->add('warning', 'second warning');
		$messages->add('success', 'completed');

		$this->assertSame(['first warning', 'second warning'], $messages->get('warning'));
		$this->assertSame(['completed'], $messages->get('success'));
		$this->assertSame(
			[
				'warning' => ['first warning', 'second warning'],
				'success' => ['completed'],
			],
			$messages->jsonSerialize()
		);
	}
}
