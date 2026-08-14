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
use VDM\Joomla\Componentbuilder\Compiler\Model\Createdate;
use VDM\Joomla\Componentbuilder\Compiler\Model\Filesfolders;
use VDM\Joomla\Componentbuilder\Compiler\Model\Modifieddate;
use VDM\Joomla\Componentbuilder\Compiler\Model\Permissions;
use VDM\Joomla\Componentbuilder\Compiler\Model\Tabs;
use VDM\Joomla\Componentbuilder\Compiler\Model\Whmcs;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\ObjectHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * Behavioral contracts for compiler model value normalization.
 *
 * @since  6.1.6
 */
#[CoversClass(Createdate::class)]
#[CoversClass(Filesfolders::class)]
#[CoversClass(Modifieddate::class)]
#[CoversClass(Permissions::class)]
#[CoversClass(Tabs::class)]
#[CoversClass(Whmcs::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(ObjectHelper::class)]
#[UsesClass(StringHelper::class)]
final class ModelNormalizationTest extends TestCase
{
	/**
	 * Read creation dates from both supported item shapes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCreatedateReadsArraySettingsAndObjectProperties(): void
	{
		$arrayItem = [
			'settings' => (object) ['created' => '2024-03-01 11:45:00']
		];
		$objectItem = (object) ['created' => '2023-12-22 23:59:00'];
		$subject = new Createdate();

		$this->assertSame('1st March, 2024', $subject->get($arrayItem));
		$this->assertSame('22nd December, 2023', $subject->get($objectItem));
	}

	/**
	 * Select the newest timestamp across an item and all of its fields.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testModifieddateSelectsLatestFieldModification(): void
	{
		$item = [
			'adminview' => 'articles',
			'settings' => (object) [
				'modified' => '2024-01-01 00:00:00',
				'fields' => [
					['settings' => (object) ['modified' => '2024-02-02 00:00:00']],
					['settings' => (object) ['modified' => '0000-00-00 00:00:00']],
					['settings' => (object) ['modified' => '2024-03-03 00:00:00']]
				]
			]
		];

		$this->assertSame('3rd March, 2024', (new Modifieddate())->get($item));
	}

	/**
	 * Use a main dynamic-get modification when no field collection exists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testModifieddateUsesMainGetAndCachesPerViewKey(): void
	{
		$subject = new Modifieddate();
		$item = [
			'siteview' => 'catalog',
			'settings' => (object) [
				'modified' => '2024-04-01 00:00:00',
				'main_get' => (object) ['modified' => '2024-05-10 00:00:00']
			]
		];

		$this->assertSame('10th May, 2024', $subject->get($item));

		$item['settings']->modified = '2025-01-01 00:00:00';
		$item['settings']->main_get->modified = '2025-02-01 00:00:00';

		$this->assertSame('10th May, 2024', $subject->get($item));
		$this->assertSame(
			'1st February, 2025',
			$subject->get(array_merge($item, ['siteview' => 'catalog_copy']))
		);
	}

	/**
	 * Merge all five encoded file sources into their three destination buckets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFilesfoldersMergesAndConsumesEveryEncodedSource(): void
	{
		$item = (object) [
			'files' => [['name' => 'existing.php']],
			'folders' => [['name' => 'existing']],
			'addfiles' => '{"first":{"name":"relative.php"}}',
			'addfolders' => '[{"name":"relative"}]',
			'addurls' => '[{"url":"https://example.test/archive.zip"}]',
			'addfilesfullpath' => '[{"name":"/absolute.php"}]',
			'addfoldersfullpath' => '[{"name":"/absolute"}]'
		];

		(new Filesfolders())->set($item);

		$this->assertSame([
			['name' => 'existing.php'],
			['name' => 'relative.php'],
			['name' => '/absolute.php']
		], $item->files);
		$this->assertSame([
			['name' => 'existing'],
			['name' => 'relative'],
			['name' => '/absolute']
		], $item->folders);
		$this->assertSame([
			['url' => 'https://example.test/archive.zip']
		], $item->urls);
		$this->assertObjectNotHasProperty('addfiles', $item);
		$this->assertObjectNotHasProperty('addfolders', $item);
		$this->assertObjectNotHasProperty('addurls', $item);
		$this->assertObjectNotHasProperty('addfilesfullpath', $item);
		$this->assertObjectNotHasProperty('addfoldersfullpath', $item);
	}

	/**
	 * Discard invalid file JSON without disturbing existing normalized values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFilesfoldersIgnoresInvalidSourcesAndStillConsumesThem(): void
	{
		$item = (object) [
			'files' => [['name' => 'kept.php']],
			'addfiles' => '{invalid'
		];

		(new Filesfolders())->set($item);

		$this->assertSame([['name' => 'kept.php']], $item->files);
		$this->assertObjectNotHasProperty('addfiles', $item);
		$this->assertObjectNotHasProperty('addfolders', $item);
		$this->assertObjectNotHasProperty('addurls', $item);
	}

	/**
	 * Zip column-oriented permission data into action records.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPermissionsZipsActionsAndImplementations(): void
	{
		$item = (object) [
			'addpermissions' => json_encode([
				'action' => ['article.edit', 'article.delete'],
				'implementation' => ['3', '2']
			], JSON_THROW_ON_ERROR)
		];

		(new Permissions())->set($item);

		$this->assertSame([
			['action' => 'article.edit', 'implementation' => '3'],
			['action' => 'article.delete', 'implementation' => '2']
		], $item->permissions);
		$this->assertObjectNotHasProperty('addpermissions', $item);
	}

	/**
	 * Reindex row-oriented permission records without changing their values.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPermissionsNormalizesRowOrientedRecords(): void
	{
		$item = (object) [
			'addpermissions' => '{"7":{"action":"core.manage","implementation":"1"}}'
		];

		(new Permissions())->set($item);

		$this->assertSame([
			['action' => 'core.manage', 'implementation' => '1']
		], $item->permissions);
		$this->assertObjectNotHasProperty('addpermissions', $item);
	}

	/**
	 * Trim custom tab names and canonicalize the reserved publishing tab.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTabsNormalizesNamesAndReservesPublishingSlot(): void
	{
		$item = (object) [
			'addtabs' => json_encode([
				['name' => ' Details '],
				['name' => 'PUBLISHING'],
				['name' => ' Metadata ']
			], JSON_THROW_ON_ERROR)
		];

		(new Tabs())->set($item);

		$this->assertSame('Details', $item->tabs[1]);
		$this->assertSame('publishing', $item->tabs[2]);
		$this->assertSame('Metadata', $item->tabs[3]);
		$this->assertSame('publishing', $item->tabs[15]);
		$this->assertObjectNotHasProperty('addtabs', $item);
	}

	/**
	 * Add mandatory default tabs when no encoded tab collection is supplied.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testTabsSuppliesDetailsAndPublishingDefaults(): void
	{
		$item = (object) ['addtabs' => 'not-json'];

		(new Tabs())->set($item);

		$this->assertSame([
			1 => 'Details',
			15 => 'publishing'
		], $item->tabs);
		$this->assertObjectNotHasProperty('addtabs', $item);
	}

	/**
	 * Derive WHMCS links from the company website when license sales are enabled.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testWhmcsDerivesLinksFromWebsite(): void
	{
		$item = (object) [
			'add_license' => 1,
			'website' => 'https://vendor.example/'
		];

		(new Whmcs())->set($item);

		$this->assertSame('https://vendor.example/', $item->whmcs_buy_link);
		$this->assertSame('https://vendor.example/whmcs', $item->whmcs_url);
	}

	/**
	 * Prefer an explicit WHMCS endpoint and preserve an explicit buy link.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testWhmcsHonorsExplicitLinks(): void
	{
		$endpointItem = (object) [
			'add_license' => 1,
			'whmcs_url' => 'https://billing.example/'
		];
		$explicitItem = (object) [
			'add_license' => 1,
			'whmcs_url' => 'https://billing.example/',
			'whmcs_buy_link' => 'https://shop.example/buy'
		];
		$subject = new Whmcs();

		$subject->set($endpointItem);
		$subject->set($explicitItem);

		$this->assertSame('https://billing.example/', $endpointItem->whmcs_buy_link);
		$this->assertSame('https://shop.example/buy', $explicitItem->whmcs_buy_link);
		$this->assertSame('https://billing.example/', $explicitItem->whmcs_url);
	}

	/**
	 * Clear all WHMCS state when licensing is explicitly disabled.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testWhmcsClearsLinksWhenLicensingIsDisabled(): void
	{
		$item = (object) [
			'add_license' => 0,
			'whmcs_key' => 'secret',
			'whmcs_buy_link' => 'https://shop.example/buy',
			'whmcs_url' => 'https://billing.example/'
		];

		(new Whmcs())->set($item);

		$this->assertSame('', $item->whmcs_key);
		$this->assertSame('', $item->whmcs_buy_link);
		$this->assertSame('', $item->whmcs_url);
	}
}
