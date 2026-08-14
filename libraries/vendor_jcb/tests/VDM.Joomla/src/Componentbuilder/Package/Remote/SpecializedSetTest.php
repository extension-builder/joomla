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

namespace VDM\Joomla\Tests\Componentbuilder\Package\Remote;


use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Joomla\Componentbuilder\Package\Remote\Alias\Set as AliasSet;
use VDM\Joomla\Componentbuilder\Package\Remote\CustomCode\Set as CustomCodeSet;
use VDM\Joomla\Componentbuilder\Package\Remote\DynamicGet\Set as DynamicGetSet;
use VDM\Joomla\Interfaces\Data\ItemsInterface;
use VDM\Joomla\Interfaces\Data\LoadInterface;
use VDM\Joomla\Interfaces\Git\Repository\ContentsInterface;
use VDM\Joomla\Interfaces\GrepInterface;
use VDM\Joomla\Interfaces\Readme\ItemInterface as ItemReadmeInterface;
use VDM\Joomla\Interfaces\Readme\MainInterface as MainReadmeInterface;
use VDM\Joomla\Interfaces\Remote\ConfigInterface;
use VDM\Joomla\Interfaces\Remote\Dependency\ResolverInterface;
use VDM\Tests\Support\TestCase;


/**
 * Specialized Package Set mapping tests.
 *
 * @since  1.0.0
 */
#[CoversClass(AliasSet::class)]
#[CoversClass(CustomCodeSet::class)]
#[CoversClass(DynamicGetSet::class)]
#[UsesNamespace('VDM\Joomla\Abstraction\Remote')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Remote')]
final class SpecializedSetTest extends TestCase
{
	/**
	 * Layout and template aliases are explicit repository-index columns.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testAliasSetAddsAliasToIndexItemsWithStableFallback(): void
	{
		$config = $this->config(
			indexMap: ['alias' => 'index_map_Alias']
		);
		$set = new AliasSet(...$this->arguments($config));

		$this->assertSame(['alias' => 'article'], $set->getIndexItem((object) ['alias' => 'article']));
		$this->assertSame(['alias' => 'error'], $set->getIndexItem((object) []));
	}

	/**
	 * Automated and manual custom code use different index identity semantics.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testCustomCodeSetBuildsAutomationAndManualIndexMetadata(): void
	{
		$config = $this->config(
			area: 'Custom Code',
			table: 'custom_code',
			indexMap: [
				'name' => 'index_map_IndexName',
				'desc' => 'index_map_ShortDescription',
				'guid' => 'index_map_IndexGUID',
			],
			guidField: 'function_name'
		);
		$lookups = new ArrayObject();
		$load = $this->createStub(LoadInterface::class);
		$load->method('table')->willReturnSelf();
		$load->method('value')->willReturnCallback(
			static function (array $keys, string $field) use ($lookups): string
			{
				$lookups[] = [$keys, $field];

				return 'Example Component';
			}
		);
		$set = new CustomCodeSet($load, ...$this->arguments($config));
		$automated = (object) [
			'target' => 1,
			'component' => 'component-guid',
			'comment_type' => 1,
			'type' => 1,
			'joomla_version' => 6,
			'path' => 'administrator/src/Example.php',
			'function_name' => 'ignoredForAutomation',
		];
		$manual = (object) [
			'target' => 0,
			'system_name' => 'Manual Snippet',
			'function_name' => 'manualSnippet',
		];

		$this->assertSame(
			[
				'name' => 'Component: Example Component',
				'desc' => 'Hash (automation) | PHP/JS [Replacement] | J6',
				'guid' => 'administrator#src#Example.php',
			],
			$set->getIndexItem($automated)
		);
		$this->assertSame(
			[
				'name' => 'Component: Example Component',
				'desc' => 'Hash (automation) | PHP/JS [Replacement] | J6',
				'guid' => 'administrator#src#Example.php',
			],
			$set->getIndexItem(clone $automated)
		);
		$this->assertSame(
			[
				'name' => 'Manual Snippet',
				'desc' => 'JCB (manual)',
				'guid' => 'manualSnippet',
			],
			$set->getIndexItem($manual)
		);
		$this->assertSame(
			[[['guid' => 'component-guid'], 'system_name']],
			$lookups->getArrayCopy(),
			'Component names must be cached across index items.'
		);
	}

	/**
	 * Dynamic source modes clear mutually exclusive fields during item mapping.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testDynamicGetSetClearsMutuallyExclusiveMainSourceFields(): void
	{
		$config = $this->config(
			area: 'Dynamic Get',
			table: 'dynamic_get',
			map: [
				'main_source' => 'main_source',
				'db_table_main' => 'db_table_main',
				'php_custom_get' => 'php_custom_get',
				'view_table_main' => 'view_table_main',
				'filter' => 'filter',
			]
		);
		$set = new DynamicGetSet(...$this->arguments($config));
		$viewSource = (object) [
			'main_source' => 1,
			'db_table_main' => '#__content',
			'php_custom_get' => 'return custom;',
			'view_table_main' => 'view-guid',
			'filter' => [['field' => 'state']],
		];
		$databaseSource = (object) [
			'main_source' => 2,
			'db_table_main' => '#__content',
			'php_custom_get' => 'return custom;',
			'view_table_main' => 'view-guid',
			'filter' => [['field' => 'state']],
		];

		$this->assertEquals(
			(object) [
				'main_source' => 1,
				'db_table_main' => null,
				'php_custom_get' => null,
				'view_table_main' => 'view-guid',
				'filter' => [['field' => 'state']],
			],
			$set->mapItem($viewSource)
		);
		$this->assertEquals(
			(object) [
				'main_source' => 2,
				'db_table_main' => '#__content',
				'php_custom_get' => null,
				'view_table_main' => null,
				'filter' => [['field' => 'state']],
			],
			$set->mapItem($databaseSource)
		);
	}

	/**
	 * Custom-source mode must keep all query-builder fields cleared.
	 *
	 * Null reset values currently fail the parent mapper's isset() guard, so
	 * fields appearing later in the map are repopulated from the raw item.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testDynamicGetCustomSourceDoesNotRepopulateResetFields(): void
	{
		$config = $this->config(
			area: 'Dynamic Get',
			table: 'dynamic_get',
			map: [
				'db_table_main' => 'db_table_main',
				'view_table_main' => 'view_table_main',
				'filter' => 'filter',
				'where' => 'where',
			]
		);
		$set = new DynamicGetSet(...$this->arguments($config));
		$item = (object) [
			'main_source' => 3,
			'db_table_main' => '#__content',
			'view_table_main' => 'view-guid',
			'filter' => [['field' => 'state']],
			'where' => [['field' => 'access']],
		];

		$this->assertEquals(
			(object) [
				'db_table_main' => null,
				'view_table_main' => null,
				'filter' => null,
				'where' => null,
			],
			$set->mapItem($item)
		);
	}

	/**
	 * Build generic Componentbuilder Set constructor arguments.
	 *
	 * @param   ConfigInterface  $config  Remote configuration fixture.
	 *
	 * @return  array<int, mixed>
	 * @since   1.0.0
	 */
	private function arguments(ConfigInterface $config): array
	{
		return [
			new Tracker(),
			new MessageBus(),
			$this->createStub(GrepInterface::class),
			$this->createStub(ResolverInterface::class),
			$config,
			$this->createStub(ItemReadmeInterface::class),
			$this->createStub(MainReadmeInterface::class),
			$this->createStub(ContentsInterface::class),
			$this->createStub(ItemsInterface::class),
			[],
		];
	}

	/**
	 * Build a remote configuration fixture for mapping behavior.
	 *
	 * @param   string                 $area       Human-readable area.
	 * @param   string                 $table      Entity table.
	 * @param   array<string, string>  $map        Entity field map.
	 * @param   array<string, string>  $indexMap   Repository index map.
	 * @param   string                 $guidField  Unique field name.
	 *
	 * @return  ConfigInterface
	 * @since   1.0.0
	 */
	private function config(
		string $area = 'Alias',
		string $table = 'template',
		array $map = [],
		array $indexMap = [],
		string $guidField = 'guid'
	): ConfigInterface
	{
		$config = $this->createStub(ConfigInterface::class);
		$config->method('getArea')->willReturn($area);
		$config->method('getTable')->willReturn($table);
		$config->method('getMap')->willReturn($map);
		$config->method('getIndexMap')->willReturn($indexMap);
		$config->method('getGuidField')->willReturn($guidField);

		return $config;
	}
}
