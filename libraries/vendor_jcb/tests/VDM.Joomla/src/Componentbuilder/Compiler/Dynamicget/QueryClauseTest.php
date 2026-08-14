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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Dynamicget;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherFilter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherGroup;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherOrder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherWhere;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldDecodeFilter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteMainGet;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryFilter;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryGroup;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryOrder;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryWhere;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Dynamic-get WHERE, filter, ordering, and grouping placement contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(QueryOrder::class)]
#[CoversClass(QueryGroup::class)]
#[CoversClass(QueryWhere::class)]
#[CoversClass(QueryFilter::class)]
#[UsesClass(OtherOrder::class)]
#[UsesClass(OtherGroup::class)]
#[UsesClass(OtherWhere::class)]
#[UsesClass(OtherFilter::class)]
#[UsesClass(SiteMainGet::class)]
#[UsesClass(SiteFieldData::class)]
#[UsesClass(SiteFieldDecodeFilter::class)]
final class QueryClauseTest extends CompilerDomainTestCase
{
	/**
	 * Main and already-joined aliases render inline while unresolved aliases are deferred.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testOrderAndGroupClausesRespectJoinPlacement(): void
	{
		$config = $this->compilerConfig(['build_target' => 'site']);
		$main = new SiteMainGet();
		$main->set('site.article.b', true);
		$otherOrder = new OtherOrder();
		$order = new QueryOrder($config, $otherOrder, $main);

		$orderOutput = $order->get([
			['table_key' => 'a.title', 'direction' => 'ASC'],
			['table_key' => 'b.created', 'direction' => 'DESC'],
			['table_key' => 'c.score', 'direction' => 'RAND']
		], 'article');

		$this->assertStringContainsString("\$query->order('a.title ASC');", $orderOutput);
		$this->assertStringContainsString("\$query->order('b.created DESC');", $orderOutput);
		$this->assertStringNotContainsString('RAND()', $orderOutput);
		$this->assertStringContainsString("\$query->order('RAND()');", $otherOrder->get('site.article.c.score'));

		$otherGroup = new OtherGroup();
		$groupOutput = (new QueryGroup($config, $otherGroup, $main))->get([
			['table_key' => 'a.id'],
			['table_key' => 'c.category']
		], 'article');

		$this->assertStringContainsString("\$query->group('a.id');", $groupOutput);
		$this->assertStringContainsString("\$query->group('c.category');", $otherGroup->get('site.article.c.category'));
	}

	/**
	 * WHERE clauses preserve quoting modes and defer unresolved joins under their field key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testWhereBuildsNumericArrayAndDeferredClauses(): void
	{
		$config = $this->compilerConfig(['build_target' => 'site']);
		$other = new OtherWhere();
		$subject = new QueryWhere($config, $other, new SiteMainGet());

		$output = $subject->get([
			['table_key' => 'a.published', 'operator' => '=', 'value_key' => 1],
			['table_key' => 'a.id', 'operator' => 'IN', 'value_key' => '$ids'],
			['table_key' => 'b.state', 'operator' => '!=', 'value_key' => '$state']
		], 'article');

		$this->assertStringContainsString("\$query->where('a.published = 1');", $output);
		$this->assertStringContainsString("isset(\$ids)", $output);
		$this->assertStringContainsString("implode(',', \$ids)", $output);
		$this->assertStringContainsString('return false;', $output);
		$this->assertStringContainsString("\$db->quote(\$state)", $other->get('site.article.b.state'));
	}

	/**
	 * Query filters render inline values, defer joins, and register post-load decoders.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFilterRoutesInlineDeferredAndDecodeOnlyTerms(): void
	{
		$config = $this->compilerConfig(['build_target' => 'site']);
		$decode = new SiteFieldDecodeFilter();
		$other = new OtherFilter();
		$subject = new QueryFilter(
			$config,
			new SiteFieldData(),
			$decode,
			new SiteMainGet(),
			$other
		);
		$arrayTerm = [
			'table_key' => 'b.payload',
			'key' => 'array-filter',
			'filter_type' => 9,
			'operator' => '='
		];
		$output = $subject->get([
			['table_key' => 'a.id', 'key' => 'id-filter', 'filter_type' => 1, 'operator' => '='],
			['table_key' => 'a.state', 'key' => 'function-filter', 'filter_type' => 8, 'operator' => 'IN', 'state_key' => '$states'],
			$arrayTerm,
			['table_key' => 'c.flag', 'key' => 'other-filter', 'filter_type' => 11, 'operator' => '=', 'state_key' => '$flag']
		], 'article');

		$this->assertStringContainsString("\$query->where('a.id = ' . (int) \$pk);", $output);
		$this->assertStringContainsString('$array = $states;', $output);
		$this->assertStringContainsString("implode(',', \$array)", $output);
		$this->assertSame($arrayTerm, $decode->get('site.article.array-filter.b.payload'));
		$this->assertStringContainsString("\$query->where('c.flag = \$flag');", $other->get('site.article.c.flag'));
	}

	/**
	 * User-group array filters must resolve the shared ArrayHelper and register decoding.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testUserGroupArrayFilterRegistersDecodeWithoutNamespaceFailure(): void
	{
		$config = $this->compilerConfig(['build_target' => 'site']);
		$decode = new SiteFieldDecodeFilter();
		$subject = new QueryFilter(
			$config,
			new SiteFieldData(),
			$decode,
			new SiteMainGet(),
			new OtherFilter()
		);
		$term = [
			'table_key' => 'a.groups',
			'key' => 'groups-filter',
			'filter_type' => 4,
			'state_key' => 'array'
		];

		$subject->get([$term], 'article');

		$this->assertSame($term, $decode->get('site.article.groups-filter.a.groups'));
	}
}
