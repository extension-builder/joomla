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
use VDM\Joomla\Abstraction\Remote\Config;
use VDM\Joomla\Componentbuilder\Power\Interfaces\TableInterface;
use VDM\Tests\Support\RemoteConfigFixture;
use VDM\Tests\Support\TestCase;


/**
 * Shared remote configuration state and table-metadata tests.
 *
 * @since  6.1.6
 */
#[CoversClass(Config::class)]
final class ConfigTest extends TestCase
{
	/**
	 * Expose the reviewed default repository layout and optional metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDefaultsExposeRepositoryLayoutContract(): void
	{
		$subject = new RemoteConfigFixture($this->createStub(TableInterface::class));

		$this->assertSame('item.json', $subject->getSettingsName());
		$this->assertSame('index.json', $subject->getIndexPath());
		$this->assertSame('src', $subject->getSrcPath());
		$this->assertSame('README.md', $subject->getMainReadmePath());
		$this->assertTrue($subject->hasMainReadme());
		$this->assertSame('README.md', $subject->getItemReadmeName());
		$this->assertTrue($subject->hasItemReadme());
		$this->assertSame('guid', $subject->getGuidField());
		$this->assertSame('system_name', $subject->getGuidHelperField());
		$this->assertSame('prefix-', $subject->getPrefixKey());
		$this->assertSame('-suffix', $subject->getSuffixKey());
		$this->assertSame(['[[TYPE]]' => 'power'], $subject->getPlaceholders());
		$this->assertSame(['source_file'], $subject->getFiles());
		$this->assertSame(['source_folder'], $subject->getFolders());
		$this->assertSame(['class_property'], $subject->getChildren());
		$this->assertSame(
			[
				'name' => 'index_map_IndexName',
				'path' => 'index_map_IndexPath',
				'settings' => 'index_map_IndexSettingsPath',
				'guid' => 'index_map_IndexGUID'
			],
			$subject->getIndexMap()
		);
		$this->assertSame(['name', 'path', 'settings', 'guid', 'local'], $subject->getIndexHeader());
	}

	/**
	 * Mutate table, area, settings, and index paths through the fluent API.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFluentStateMutatorsNormalizeAreaAndPreserveIdentity(): void
	{
		$subject = new RemoteConfigFixture($this->createStub(TableInterface::class));

		$this->assertSame($subject, $subject->table('power'));
		$this->assertSame($subject, $subject->area('class_property'));
		$this->assertSame($subject, $subject->setSettingsName('settings.json'));
		$subject->setIndexPath('catalog/index.json');

		$this->assertSame('power', $subject->getTable());
		$this->assertSame('Class property', $subject->getArea());
		$this->assertSame('settings.json', $subject->getSettingsName());
		$this->assertSame('catalog/index.json', $subject->getIndexPath());
	}

	/**
	 * Delegate table names and fields while caching the filtered field map.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTableMetadataIsDelegatedAndFieldMapIsCached(): void
	{
		$table = $this->createMock(TableInterface::class);
		$table->expects($this->once())
			->method('fields')
			->with('power')
			->willReturn(['id', 'guid', 'access', 'system_name']);
		$table->expects($this->once())
			->method('listViewCodeName')
			->with('power')
			->willReturn('powers');
		$table->expects($this->once())
			->method('titleName')
			->with('power')
			->willReturn('system_name');
		$subject = new RemoteConfigFixture($table);
		$subject->table('power');

		$this->assertSame('powers', $subject->getListViewCodeName());
		$this->assertSame('system_name', $subject->getTitleName());
		$this->assertSame(
			['id' => 'id', 'guid' => 'guid', 'system_name' => 'system_name'],
			$subject->getMap()
		);
		$this->assertSame(
			['id' => 'id', 'guid' => 'guid', 'system_name' => 'system_name'],
			$subject->getMap()
		);
	}

	/**
	 * Preserve an empty map when the table exposes no fields.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testEmptyTableFieldsProduceEmptyMap(): void
	{
		$table = $this->createStub(TableInterface::class);
		$table->method('fields')->willReturn([]);
		$subject = new RemoteConfigFixture($table);
		$subject->table('missing');

		$this->assertSame([], $subject->getMap());
	}
}
