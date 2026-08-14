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
use VDM\Joomla\Componentbuilder\Abstraction\BaseRegistry;
use VDM\Joomla\Componentbuilder\Compiler\Model\Linkedviews;
use VDM\Joomla\Componentbuilder\Compiler\Model\Sqltweaking;
use VDM\Joomla\Componentbuilder\Compiler\Model\Updatesql;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Utilities\ArrayHelper;
use VDM\Joomla\Utilities\JsonHelper;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\TestCase;


/**
 * Registry state-transition contracts for compiler models.
 *
 * @since  6.1.6
 */
#[CoversClass(Linkedviews::class)]
#[CoversClass(Sqltweaking::class)]
#[CoversClass(Updatesql::class)]
#[UsesClass(Registry::class)]
#[UsesClass(BaseRegistry::class)]
#[UsesClass(ArrayHelper::class)]
#[UsesClass(JsonHelper::class)]
#[UsesClass(StringHelper::class)]
final class RegistryModelTest extends TestCase
{
	/**
	 * Reindex linked-view records under the current admin-view code.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLinkedviewsRegistersNormalizedRecordsAndConsumesSource(): void
	{
		$registry = new Registry();
		$item = (object) [
			'name_single_code' => 'article',
			'addlinked_views' => '{"3":{"adminview":"categories"},"8":{"adminview":"tags"}}'
		];

		(new Linkedviews($registry))->set($item);

		$this->assertSame([
			['adminview' => 'categories'],
			['adminview' => 'tags']
		], $registry->get('builder.linked_admin_views.article'));
		$this->assertObjectNotHasProperty('addlinked_views', $item);
	}

	/**
	 * Leave registry state untouched for invalid linked-view JSON.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLinkedviewsRejectsInvalidJsonWithoutWritingRegistry(): void
	{
		$registry = new Registry(['existing' => 'kept']);
		$item = (object) [
			'name_single_code' => 'article',
			'addlinked_views' => '{invalid'
		];

		(new Linkedviews($registry))->set($item);

		$this->assertSame(['existing' => 'kept'], $registry->toArray());
		$this->assertObjectNotHasProperty('addlinked_views', $item);
	}

	/**
	 * Normalize IDs, ranges, duplicates, and disabled SQL flags into registry paths.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSqltweakingNormalizesWhereRangesAndDisabledViews(): void
	{
		$registry = new Registry();
		$item = (object) [
			'sql_tweak' => json_encode([
				[
					'adminview' => 'articles',
					'add_sql' => 1,
					'add_sql_options' => 2,
					'ids' => '5 => 3, 2, 2, nope, 1 => x, 8'
				],
				[
					'adminview' => 'categories',
					'add_sql' => 0,
					'add_sql_options' => 2,
					'ids' => '10, 11'
				],
				[
					'add_sql' => 0
				],
				[
					'adminview' => 'ignored',
					'add_sql' => 1,
					'add_sql_options' => 1,
					'ids' => '99'
				]
			], JSON_THROW_ON_ERROR)
		];

		(new Sqltweaking($registry))->set($item);

		$this->assertSame('2,3,4,5,8', $registry->get('builder.sql_tweak.articles.where'));
		$this->assertFalse($registry->get('builder.sql_tweak.categories.add'));
		$this->assertNull($registry->get('builder.sql_tweak.ignored.where'));
		$this->assertObjectNotHasProperty('sql_tweak', $item);
	}

	/**
	 * Consume invalid tweak data without altering the registry.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSqltweakingRejectsInvalidJson(): void
	{
		$registry = new Registry(['existing' => 'kept']);
		$item = (object) ['sql_tweak' => 'invalid'];

		(new Sqltweaking($registry))->set($item);

		$this->assertSame(['existing' => 'kept'], $registry->toArray());
		$this->assertObjectNotHasProperty('sql_tweak', $item);
	}

	/**
	 * Record scalar changes only when both values are meaningful and different.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdatesqlRecordsChangedStringAndNumericScalars(): void
	{
		$registry = new Registry();
		$subject = new Updatesql($registry);

		$subject->set('varchar(64)', 'varchar(128)', 'datatype', 'title');
		$subject->set(10, 12, 'length', 'title');
		$subject->set('same', 'same', 'unchanged', 'title');
		$subject->set('', 'new', 'empty_old', 'title');

		$this->assertSame([
			'old' => 'varchar(64)',
			'new' => 'varchar(128)'
		], $registry->get('builder.update_sql.datatype.title'));
		$this->assertSame([
			'old' => 10,
			'new' => 12
		], $registry->get('builder.update_sql.length.title'));
		$this->assertNull($registry->get('builder.update_sql.unchanged.title'));
		$this->assertNull($registry->get('builder.update_sql.empty_old.title'));
	}

	/**
	 * Add only new non-ignored values from a repeatable field collection.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdatesqlDiffsRepeatableCollectionsWithIgnoreList(): void
	{
		$registry = new Registry();

		(new Updatesql($registry))->set(
			['field' => [1, 2]],
			['field' => [1, 2, 3, 4]],
			'field',
			'articles',
			[4]
		);

		$this->assertSame(3, $registry->get('builder.add_sql.field.articles.3'));
		$this->assertNull($registry->get('builder.add_sql.field.articles.1'));
		$this->assertNull($registry->get('builder.add_sql.field.articles.2'));
		$this->assertNull($registry->get('builder.add_sql.field.articles.4'));
	}

	/**
	 * Diff row-oriented collections and key the new value under its parent.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdatesqlDiffsRowOrientedCollections(): void
	{
		$registry = new Registry();

		(new Updatesql($registry))->set(
			[
				['field' => 1],
				['field' => 2]
			],
			[
				['field' => 1],
				['field' => 2],
				['field' => 3]
			],
			'field',
			'articles'
		);

		$this->assertSame(3, $registry->get('builder.add_sql.field.articles.3'));
		$this->assertCount(1, (array) $registry->get('builder.add_sql.field.articles'));
	}
}
