<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    15th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Extension;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UpdateMysql;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Extension\VersionUpdate;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HistoryInterface;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Interfaces\Data\ItemInterface;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Extension Version Update Test.
 *
 * @since  6.1.7
 */
#[CoversClass(VersionUpdate::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionUpdateTest extends ArchitectureTestCase
{
	/**
	 * A historic version entry gains the dynamic SQL and its SQL update file.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testUpdateXmlSqlMergesDynamicSqlIntoThePreviousVersion(): void
	{
		$contentmulti = new ContentMulti();
		$updatemysql = new UpdateMysql();
		$updatemysql->set(
			'ALTERTABLE`#__demo_a`ADD`x`INT;',
			'ALTER TABLE `#__demo_a` ADD `x` INT;'
		);

		$structure = $this->structure();
		$structure->expects($this->once())
			->method('build')
			->with(['admin' => 'onezerozero'], 'sql_update', '1_0_0')
			->willReturn(true);

		$subject = $this->versionUpdate(
			$this->component([
				'component_version' => '2.0.0',
				'old_component_version' => '1.0.0',
			]),
			new ContentOne(),
			$contentmulti,
			$updatemysql,
			$structure
		);

		$update = ['version' => 'v1.0.0', 'mysql' => "ALTER TABLE `#__demo_b` ADD `y` INT;"];
		$updateXML = [];
		$addDynamicSQL = true;

		$subject->setUpdateXmlSql($update, $updateXML, $addDynamicSQL);

		$this->assertSame('1.0.0', $update['version']);
		$this->assertFalse($addDynamicSQL);
		$this->assertSame(
			"ALTER TABLE `#__demo_b` ADD `y` INT;" . PHP_EOL . PHP_EOL
			. "ALTER TABLE `#__demo_a` ADD `x` INT;",
			$update['mysql']
		);
		$this->assertSame(
			$update['mysql'],
			$contentmulti->get('onezerozero_1_0_0|UPDATE_VERSION_MYSQL')
		);
		$this->assertSame([], $updateXML);
		$this->assertNull($subject->getLastUpdateUrl());
	}

	/**
	 * The active version records its URL and emits the update-server block.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testUpdateXmlSqlRecordsActiveVersionUrlAndServerBlock(): void
	{
		$contentone = new ContentOne();
		$contentone->set('Component_name', 'Demo Component');
		$contentone->set('SHORT_DESCRIPTION', 'Short demo description');
		$contentone->set('AUTHOR', 'Demo Author');
		$contentone->set('AUTHORWEBSITE', 'https://demo.example');

		$structure = $this->structure();
		$structure->expects($this->never())->method('build');

		$subject = $this->versionUpdate(
			$this->component([
				'component_version' => '2.0.0',
				'old_component_version' => '1.0.0',
				'add_update_server' => 1,
			]),
			$contentone,
			new ContentMulti(),
			new UpdateMysql(),
			$structure
		);

		$update = [
			'version' => '2.0.0',
			'mysql' => '',
			'url' => 'https://demo.example/demo-2.0.0.zip',
		];
		$updateXML = [];
		$addDynamicSQL = false;

		$subject->setUpdateXmlSql($update, $updateXML, $addDynamicSQL);

		$this->assertSame('https://demo.example/demo-2.0.0.zip', $subject->getLastUpdateUrl());
		$this->assertSame("\t<update>", $updateXML[0]);
		$this->assertContains("\t\t<name>Demo Component</name>", $updateXML);
		$this->assertContains("\t\t<description>Short demo description</description>", $updateXML);
		$this->assertContains("\t\t<element>com_demo</element>", $updateXML);
		$this->assertContains("\t\t<type>component</type>", $updateXML);
		$this->assertContains("\t\t<version>2.0.0</version>", $updateXML);
		$this->assertContains(
			"\t\t\t<downloadurl type=\"full\" format=\"zip\">https://demo.example/demo-2.0.0.zip</downloadurl>",
			$updateXML
		);
		$this->assertContains("\t\t\t<tag>stable</tag>", $updateXML);
		$this->assertContains("\t\t<maintainer>Demo Author</maintainer>", $updateXML);
		$this->assertContains(
			"\t\t<targetplatform name=\"joomla\" version=\"5.*\"/>",
			$updateXML
		);
		$this->assertSame("\t</update>", $updateXML[count($updateXML) - 1]);
	}

	/**
	 * The dynamic previous-version entry projects the recorded URL backwards.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testDynamicUpdateXmlSqlProjectsThePreviousVersionUrl(): void
	{
		$updatemysql = new UpdateMysql();
		$updatemysql->set(
			'ALTERTABLE`#__demo_a`ADD`x`INT;',
			'ALTER TABLE `#__demo_a` ADD `x` INT;'
		);

		$structure = $this->structure();
		$structure->expects($this->once())
			->method('build')
			->with(['admin' => 'onezerozero'], 'sql_update', '1_0_0')
			->willReturn(true);

		$component = $this->component([
			'component_version' => '2.0.0',
			'old_component_version' => '1.0.0',
		]);

		$subject = $this->versionUpdate(
			$component,
			new ContentOne(),
			new ContentMulti(),
			$updatemysql,
			$structure
		);
		$subject->setLastUpdateUrl('https://demo.example/demo-2.0.0.zip');

		$updateXML = [];
		$subject->setDynamicUpdateXmlSql($updateXML);

		$stored = $component->get('version_update');

		$this->assertCount(1, $stored);
		$this->assertSame('1.0.0', $stored[0]['version']);
		$this->assertSame('https://demo.example/demo-1.0.0.zip', $stored[0]['url']);
		$this->assertSame('ALTER TABLE `#__demo_a` ADD `x` INT;', $stored[0]['mysql']);
	}

	/**
	 * The complete pass writes server placeholders without touching the database.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSetWritesServerPlaceholdersWithoutDynamicSql(): void
	{
		$contentone = new ContentOne();
		$contentone->set('Component_name', 'Demo Component');
		$contentmulti = new ContentMulti();

		$builds = [];
		$structure = $this->structure();
		$structure->expects($this->exactly(2))
			->method('build')
			->willReturnCallback(
				static function (array $target, string $type) use (&$builds): bool
				{
					$builds[] = [$target, $type];

					return true;
				}
			);

		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->never())->method('table');

		$subject = $this->versionUpdate(
			$this->component([
				'component_version' => '2.0.0',
				'version_update' => [
					['version' => '2.0.0', 'mysql' => '', 'url' => 'https://demo.example/demo.zip'],
				],
				'update_server_target' => 1,
				'update_server_file_name' => 'demo_update_server',
				'add_update_server' => 1,
				'update_server_url' => 'https://demo.example/update.xml',
				'add_changelog_server' => 1,
				'changelog_server_url' => 'https://demo.example/changelog.xml',
				'changelog_server_file_name' => 'demo_changelog',
			]),
			$contentone,
			$contentmulti,
			new UpdateMysql(),
			$structure,
			$item
		);
		$subject->set();

		$this->assertSame(
			[
				[['admin' => 'demo_update_server'], 'update_server'],
				[['admin' => 'demo_changelog'], 'changelog_server'],
			],
			$builds
		);

		$serverXml = $contentmulti->get('demo_update_server|UPDATE_SERVER_XML');

		$this->assertStringStartsWith('<?xml version="1.0" encoding="utf-8"?>', $serverXml);
		$this->assertStringContainsString('<updates>', $serverXml);
		$this->assertStringContainsString('<version>2.0.0</version>', $serverXml);
		$this->assertStringEndsWith('</updates>', $serverXml);

		$this->assertSame(
			PHP_EOL . "\t<updateservers>" . PHP_EOL
			. "\t\t<server type=\"extension\" enabled=\"1\" element=\"com_demo\" "
			. "name=\"Demo Component\">https://demo.example/update.xml</server>" . PHP_EOL
			. "\t</updateservers>",
			$contentone->get('UPDATESERVER')
		);
		$this->assertSame(
			PHP_EOL . "\t<changelogurl>https://demo.example/changelog.xml</changelogurl>",
			$contentone->get('CHANGELOGSERVER')
		);
		$this->assertSame(
			'<changelogs></changelogs>',
			$contentmulti->get('demo_changelog|CHANGELOG_SERVER_XML')
		);
		$this->assertSame('https://demo.example/demo.zip', $subject->getLastUpdateUrl());
	}

	/**
	 * Active dynamic SQL persists the component version and update rows.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testSetPersistsDynamicVersionData(): void
	{
		$this->config()->set('component_id', 42);
		$this->config()->set('component_guid', 'demo-guid-1234');

		$updatemysql = new UpdateMysql();
		$updatemysql->set(
			'ALTERTABLE`#__demo_a`ADD`x`INT;',
			'ALTER TABLE `#__demo_a` ADD `x` INT;'
		);

		$component = $this->component([
			'component_version' => '2.0.0',
			'old_component_version' => '1.0.0',
			'version_update' => [
				['version' => '1.0.0', 'mysql' => ''],
			],
		]);

		$structure = $this->structure();
		$structure->expects($this->once())
			->method('build')
			->with(['admin' => 'onezerozero'], 'sql_update', '1_0_0')
			->willReturn(true);

		$writes = [];
		$item = $this->createMock(ItemInterface::class);
		$item->expects($this->exactly(2))
			->method('table')
			->willReturnCallback(
				static function (string $table) use (&$writes, $item): ItemInterface
				{
					$writes[] = ['table' => $table];

					return $item;
				}
			);
		$item->expects($this->exactly(2))
			->method('set')
			->willReturnCallback(
				static function (object $row, string $key) use (&$writes): bool
				{
					$writes[count($writes) - 1] += ['row' => $row, 'key' => $key];

					return true;
				}
			);

		$history = $this->createMock(HistoryInterface::class);
		$history->expects($this->once())
			->method('get')
			->with('joomla_component', 42)
			->willReturn(null);

		$subject = $this->versionUpdate(
			$component,
			new ContentOne(),
			new ContentMulti(),
			$updatemysql,
			$structure,
			$item,
			$history
		);
		$subject->set();

		$this->assertCount(2, $writes);
		$this->assertSame('joomla_component', $writes[0]['table']);
		$this->assertSame('id', $writes[0]['key']);
		$this->assertSame(42, $writes[0]['row']->id);
		$this->assertSame('2.0.0', $writes[0]['row']->component_version);

		$this->assertSame('component_updates', $writes[1]['table']);
		$this->assertSame('guid', $writes[1]['key']);
		$this->assertSame('demo-guid-1234', $writes[1]['row']->joomla_component);
		$this->assertArrayHasKey('version_update0', $writes[1]['row']->version_update);
		$this->assertArrayHasKey('version_update1', $writes[1]['row']->version_update);
		$this->assertSame(
			'2.0.0',
			$writes[1]['row']->version_update['version_update1']['version']
		);

		// the appended active entry recorded its demo URL
		$this->assertSame('http://domain.com/demo.zip', $subject->getLastUpdateUrl());
	}

	/**
	 * Create a compiler Component registry seeded with values.
	 *
	 * @param   array<string, mixed>  $values  Component values to set.
	 *
	 * @return  Component
	 * @since   6.1.7
	 */
	private function component(array $values): Component
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$component = new Component($data, $this->createStub(EventInterface::class));

		foreach ($values as $key => $value)
		{
			$component->set($key, $value);
		}

		return $component;
	}

	/**
	 * Create the version-update service with real registries.
	 *
	 * @param   Component            $component     The component registry.
	 * @param   ContentOne           $contentone    The global content registry.
	 * @param   ContentMulti         $contentmulti  The contextual content registry.
	 * @param   UpdateMysql          $updatemysql   The dynamic SQL registry.
	 * @param   Structure            $structure     The structure double.
	 * @param   ItemInterface|null   $item          The data item double.
	 * @param   HistoryInterface|null $history      The history double.
	 *
	 * @return  VersionUpdate
	 * @since   6.1.7
	 */
	private function versionUpdate(
		Component $component,
		ContentOne $contentone,
		ContentMulti $contentmulti,
		UpdateMysql $updatemysql,
		Structure $structure,
		?ItemInterface $item = null,
		?HistoryInterface $history = null
	): VersionUpdate
	{
		return new VersionUpdate(
			$this->config(),
			$component,
			$this->placeholder(),
			$contentone,
			$contentmulti,
			$updatemysql,
			$structure,
			$item ?? $this->createStub(ItemInterface::class),
			$history ?? $this->createStub(HistoryInterface::class)
		);
	}

	/**
	 * Create a structure double with only its build boundary open.
	 *
	 * @return  Structure&\PHPUnit\Framework\MockObject\MockObject
	 * @since   6.1.7
	 */
	private function structure(): Structure
	{
		return $this->getMockBuilder(Structure::class)
			->disableOriginalConstructor()
			->onlyMethods(['build'])
			->getMock();
	}
}
