<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    27th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Tests\Contract;


use Joomla\CMS\User\User;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use VDM\Component\Componentbuilder\Administrator\Model\Language_translationsModel;
use VDM\Tests\Support\JoomlaTestCase;


require_once dirname(__DIR__, 4) . '/admin/src/Model/Language_translationsModel.php';


/**
 * Protect the generated administrator model's filter/cache boundary.
 *
 * The real model runs with isolated state, database and user boundaries;
 * administrator code is outside the vendor source coverage inventory.
 *
 * @since  6.2.1
 */
#[CoversNothing]
final class LanguageTranslationsFilterTest extends JoomlaTestCase
{
	/**
	 * Every custom selection independently changes both list and total identity.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testCustomFiltersHaveIndependentCacheIdentities(): void
	{
		$model = $this->model();
		$method = new ReflectionMethod($model, 'getStoreId');
		foreach (['getItems', 'getTotal'] as $operation)
		{
			$original = $method->invoke($model, $operation);
			$identities = [$original];
			foreach (['extension' => 'joomla_component__fixture', 'translated' => 'af-ZA', 'not_translated' => 'en-GB'] as $filter => $value)
			{
				$model->setState('filter.' . $filter, $value);
				$identities[] = $method->invoke($model, $operation);
				$model->setState('filter.' . $filter, null);
				$this->assertSame($original, $method->invoke($model, $operation));
			}
			$this->assertCount(4, array_unique($identities));
		}
	}

	/**
	 * Invalid UI selections result in an empty query without invoking helper SQL.
	 *
	 * @param   string  $filter  Custom filter name.
	 * @param   mixed   $value   Malformed submitted value.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('malformedSelections')]
	public function testMalformedSelectionsProduceAnEmptyQuery(string $filter, mixed $value): void
	{
		$model = $this->model();
		$model->setState('filter.' . $filter, $value);
		$conditions = [];
		$query = $this->createStub(DatabaseQuery::class);
		foreach (['select', 'from', 'join', 'order'] as $method)
		{
			$query->method($method)->willReturnSelf();
		}
		$query->method('where')->willReturnCallback(static function (mixed $condition) use (&$conditions, $query): DatabaseQuery
		{
			$conditions[] = $condition;

			return $query;
		});
		$db = $this->createStub(DatabaseDriver::class);
		$db->method('getQuery')->willReturn($query);
		$db->method('quoteName')->willReturnArgument(0);
		$db->method('escape')->willReturnArgument(0);
		$model->setDatabase($db);
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturn(true);
		$model->setCurrentUser($user);

		$this->assertSame($query, (new ReflectionMethod($model, 'getListQuery'))->invoke($model));
		$this->assertContains('a.id = 0', $conditions);
	}

	/**
	 * Supply malformed array and partial extension inputs.
	 *
	 * @return  array<string, array{string, mixed}>
	 * @since   6.2.1
	 */
	public static function malformedSelections(): array
	{
		return [
			'extension missing delimiter' => ['extension', 'joomla_component'],
			'extension missing identifier' => ['extension', 'joomla_component__'],
			'extension array' => ['extension', ['joomla_component__fixture']],
			'translated array' => ['translated', ['af-ZA']],
			'untranslated array' => ['not_translated', ['af-ZA']],
		];
	}

	/**
	 * Build the actual model without booting its installed-application constructor.
	 *
	 * @return  Language_translationsModel
	 * @since   6.2.1
	 */
	private function model(): Language_translationsModel
	{
		$model = (new ReflectionClass(Language_translationsModel::class))->newInstanceWithoutConstructor();
		(new ReflectionProperty($model, 'state'))->setValue($model, new Registry());
		(new ReflectionProperty($model, '__state_set'))->setValue($model, true);

		return $model;
	}
}
