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

namespace VDM\Joomla\Tests\Componentbuilder\Data\Migrator;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Data\Migrator\Guid;
use VDM\Joomla\Data\Migrator\Guid as CoreGuidMigrator;
use VDM\Tests\Support\MessageApplicationFixture;
use VDM\Tests\Support\TestCase;


/**
 * JCB ID-to-GUID migration catalog and process-boundary contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Guid::class)]
final class GuidTest extends TestCase
{
	/**
	 * Guard every reviewed mapping family and the complete migration inventory.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigurationPublishesCompleteMigrationCatalog(): void
	{
		$config = $this->configuration();
		$types = array_count_values(array_column($config, 'valueType'));
		ksort($types);
		$tables = array_values(array_unique(array_column($config, 'table')));
		sort($tables);

		$this->assertCount(78, $config);
		$this->assertSame([1 => 52, 2 => 22, 3 => 2, 4 => 1, 5 => 1], $types);
		$this->assertCount(41, $tables);
		$this->assertContains('joomla_component', $tables);
		$this->assertContains('admin_view', $tables);
		$this->assertContains('field', $tables);
		$this->assertContains('power', $tables);
		$this->assertSame(
			'bcf027ddf07d129079e528508ad2c8afcf0a4347fb922cf009ac06d196d5fabd',
			hash('sha256', (string) json_encode($config)),
			'Review every ID-to-GUID migration mapping before accepting this fingerprint change.'
		);
	}

	/**
	 * Forward core migration progress messages to Joomla's application boundary.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testProcessPublishesCoreMigratorMessages(): void
	{
		$core = (new ReflectionClass(CoreGuidMigrator::class))->newInstanceWithoutConstructor();
		$app = new MessageApplicationFixture();
		$subject = new Guid($core, $app);
		(new ReflectionClass(Guid::class))->getProperty('config')->setValue(
			$subject,
			[['valueType' => 99]]
		);

		$subject->process();

		$this->assertSame(
			[
				[
					'message' => 'Success: scan to migrate linked IDs to linked GUIDs has started on 1 field areas.',
					'type' => 'message',
				],
				[
					'message' => 'Success: migration completed and all linked IDs are now migrated to linked GUIDs (on previous run).',
					'type' => 'message',
				],
			],
			$app->messages
		);
	}

	/**
	 * Explain an empty mapping configuration without calling the core migrator.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testProcessWarnsWhenNoMigrationConfigurationExists(): void
	{
		$core = (new ReflectionClass(CoreGuidMigrator::class))->newInstanceWithoutConstructor();
		$app = new MessageApplicationFixture();
		$subject = new Guid($core, $app);
		(new ReflectionClass(Guid::class))->getProperty('config')->setValue($subject, []);

		$subject->process();

		$this->assertSame(
			[['message' => 'No GUID migration configurations found!', 'type' => 'warning']],
			$app->messages
		);
	}

	/**
	 * Relationship mappings must point to their actual owning tables and columns.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testPluginGroupAndPowerMethodMappingsUseCorrectRelationships(): void
	{
		$config = $this->configuration();
		$classMethod = $this->find($config, 'class_method', 'joomla_plugin_group');
		$classProperty = $this->find($config, 'class_property', 'joomla_plugin_group');
		$powerMethod = array_values(array_filter(
			$config,
			static fn(array $map): bool => $map['table'] === 'power' && ($map['field'] ?? null) === 'method'
		))[0];

		$this->assertSame('joomla_plugin_group', $classMethod['linkedTable']);
		$this->assertSame('joomla_plugin_group', $classProperty['linkedTable']);
		$this->assertSame('method_selection', $powerMethod['column']);
	}

	/**
	 * Read the immutable protected migration configuration.
	 *
	 * @return  array<int, array<string, mixed>>
	 * @since   6.1.6
	 */
	private function configuration(): array
	{
		$reflection = new ReflectionClass(Guid::class);
		$subject = $reflection->newInstanceWithoutConstructor();

		return $reflection->getProperty('config')->getValue($subject);
	}

	/**
	 * Find a mapping by owning table and column.
	 *
	 * @param   array<int, array<string, mixed>>  $config  Migration configuration.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.6
	 */
	private function find(array $config, string $table, string $column): array
	{
		foreach ($config as $mapping)
		{
			if ($mapping['table'] === $table && $mapping['column'] === $column)
			{
				return $mapping;
			}
		}

		$this->fail("Missing migration mapping for {$table}.{$column}");
	}
}
