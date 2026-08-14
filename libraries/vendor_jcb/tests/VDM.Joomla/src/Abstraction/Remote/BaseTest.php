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
use VDM\Joomla\Abstraction\Remote\Base;
use VDM\Joomla\Abstraction\Remote\Config;
use VDM\Joomla\Componentbuilder\Power\Interfaces\TableInterface;
use VDM\Tests\Support\RemoteBaseFixture;
use VDM\Tests\Support\RemoteConfigFixture;
use VDM\Tests\Support\TestCase;


/**
 * Shared remote item mapping and index-generation tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Base::class)]
#[UsesClass(Config::class)]
final class BaseTest extends TestCase
{
	/**
	 * Forward mutable configuration state while preserving fluent base identity.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigurationStateIsForwardedThroughBase(): void
	{
		$table = $this->createStub(TableInterface::class);
		$table->method('listViewCodeName')->willReturn('powers');
		$subject = new RemoteBaseFixture(new RemoteConfigFixture($table));

		$this->assertSame($subject, $subject->table('power'));
		$this->assertSame($subject, $subject->area('class_property'));
		$this->assertSame($subject, $subject->setSettingsName('data.json'));
		$subject->setIndexPath('catalog.json');

		$this->assertSame('power', $subject->getTable());
		$this->assertSame('powers', $subject->getListViewCodeName());
		$this->assertSame('Class property', $subject->getArea());
		$this->assertSame('data.json', $subject->getSettingsName());
		$this->assertSame('catalog.json', $subject->getIndexPath());
		$this->assertSame(['[[TYPE]]' => 'power'], $subject->getPlaceholders());
		$this->assertSame(['source_file'], $subject->getFiles());
		$this->assertSame(['source_folder'], $subject->getFolders());
		$this->assertSame(['class_property'], $subject->getChildren());
		$this->assertTrue($subject->hasMainReadme());
		$this->assertTrue($subject->hasItemReadme());
	}

	/**
	 * Map configured table fields while removing ignored access metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMapItemUsesConfiguredFieldMapAndMissingValues(): void
	{
		$table = $this->createStub(TableInterface::class);
		$table->method('fields')->willReturn(['guid', 'system_name', 'access', 'description']);
		$subject = new RemoteBaseFixture(new RemoteConfigFixture($table));
		$subject->table('power');

		$this->assertEquals(
			(object) [
				'guid' => 'a1b2-c3d4',
				'system_name' => 'Demo',
				'description' => null
			],
			$subject->mapItem(
				(object) [
					'guid' => 'a1b2-c3d4',
					'system_name' => 'Demo',
					'access' => 1
				]
			)
		);
	}

	/**
	 * Build the canonical repository index paths and GUID fields.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetIndexItemBuildsCanonicalPaths(): void
	{
		$table = $this->createStub(TableInterface::class);
		$table->method('titleName')->willReturn('system_name');
		$subject = new RemoteBaseFixture(new RemoteConfigFixture($table));
		$subject->table('power');

		$this->assertSame(
			[
				'name' => 'Demo power',
				'path' => 'src/a1b2-c3d4',
				'settings' => 'src/a1b2-c3d4/item.json',
				'guid' => 'a1b2-c3d4'
			],
			$subject->getIndexItem(
				(object) ['guid' => 'a1b2-c3d4', 'system_name' => 'Demo power']
			)
		);
		$this->assertSame(
			'missing-guid',
			$subject->getIndexItem((object) ['system_name' => 'Missing'])['guid']
		);
	}
}
