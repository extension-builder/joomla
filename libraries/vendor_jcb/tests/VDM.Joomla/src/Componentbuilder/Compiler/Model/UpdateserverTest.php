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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Model;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Model\Updateserver;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * Update-server normalization and generated changelog contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Updateserver::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(StringHelper::class)]
final class UpdateserverTest extends TestCase
{
	/**
	 * Sort Markdown newest first while generating oldest-first grouped XML.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetBuildsStableMarkdownAndXmlChangelogs(): void
	{
		$item = (object) [
			'name_code' => 'demo&more',
			'version_update' => json_encode([
				[
					'version' => '2.0.0',
					'change_log' => '- Fixed newest'
				],
				[
					'version' => '1.0.0',
					'change_log' => implode("\n", [
						'- Security hardening',
						'* Fixed crash',
						'+ Language strings',
						'1. Added feature',
						'2) Changed workflow',
						'• Removed API',
						'A general note',
						'   '
					])
				]
			], JSON_THROW_ON_ERROR)
		];

		(new Updateserver())->set($item);

		$this->assertSame(
			'# v2.0.0' . PHP_EOL . PHP_EOL . '- Fixed newest'
				. PHP_EOL . PHP_EOL . '# v1.0.0' . PHP_EOL . PHP_EOL
				. implode("\n", [
					'- Security hardening',
					'* Fixed crash',
					'+ Language strings',
					'1. Added feature',
					'2) Changed workflow',
					'• Removed API',
					'A general note',
					'   '
				]),
			$item->changelog
		);
		$this->assertSame($this->expectedChangelogXml(), $item->changelogxml);
		$this->assertSame('2.0.0', $item->version_update[0]['version']);
		$this->assertSame('1.0.0', $item->version_update[1]['version']);
	}

	/**
	 * Reindex sparse update data without emitting changelogs for incomplete rows.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetReindexesUpdatesAndSkipsIncompleteChangelogRows(): void
	{
		$item = (object) [
			'version_update' => '{"4":{"version":"1.0.0"},"9":{"change_log":"Fixed"}}'
		];

		(new Updateserver())->set($item);

		$this->assertSame([
			['version' => '1.0.0'],
			['change_log' => 'Fixed']
		], $item->version_update);
		$this->assertObjectNotHasProperty('changelog', $item);
		$this->assertObjectNotHasProperty('changelogxml', $item);
	}

	/**
	 * Normalize an invalid update payload to null without generated artifacts.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetRejectsInvalidUpdateJson(): void
	{
		$item = (object) ['version_update' => '{invalid'];

		(new Updateserver())->set($item);

		$this->assertNull($item->version_update);
		$this->assertObjectNotHasProperty('changelog', $item);
		$this->assertObjectNotHasProperty('changelogxml', $item);
	}

	/**
	 * Return the reviewed XML artifact produced for all classification branches.
	 *
	 * @return  string
	 * @since   6.1.6
	 */
	private function expectedChangelogXml(): string
	{
		return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<changelogs>
  <changelog>
    <element>com_demo&amp;more</element>
    <type>component</type>
    <version>1.0.0</version>
    <security>
      <item>Security hardening</item>
    </security>
    <fix>
      <item>Fixed crash</item>
    </fix>
    <language>
      <item>Language strings</item>
    </language>
    <addition>
      <item>Added feature</item>
    </addition>
    <change>
      <item>Changed workflow</item>
    </change>
    <remove>
      <item>Removed API</item>
    </remove>
    <note>
      <item>A general note</item>
    </note>
  </changelog>
  <changelog>
    <element>com_demo&amp;more</element>
    <type>component</type>
    <version>2.0.0</version>
    <fix>
      <item>Fixed newest</item>
    </fix>
  </changelog>
</changelogs>
XML
			. PHP_EOL;
	}
}
