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
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Builder\GetAsLookup;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherFilter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherGroup;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherJoin;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherOrder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherQuery;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherWhere;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteDynamicGet;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldDecodeFilter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteMainGet;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\CustomGetMethods;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\CustomJoin;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\GetItem;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\GetItems;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\JoinStructure;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\ListQuery;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\Methods;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\Queries;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryFilter;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryGroup;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryOrder;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryWhere;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\UikitLoader;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Dynamic-get query composition and generated method contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(CustomJoin::class)]
#[CoversClass(Queries::class)]
#[CoversClass(ListQuery::class)]
#[CoversClass(Methods::class)]
#[UsesClass(JoinStructure::class)]
#[UsesClass(GetAsLookup::class)]
#[UsesClass(SiteDynamicGet::class)]
#[UsesClass(OtherJoin::class)]
#[UsesClass(OtherQuery::class)]
final class QueryCompositionTest extends CompilerDomainTestCase
{
	/**
	 * Custom joins emit inline calls for available aliases and queue unavailable parents.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomJoinRoutesInlineAndDeferredAssignments(): void
	{
		$config = $this->compilerConfig(['build_target' => 'site']);
		$dynamic = new SiteDynamicGet();
		$other = new OtherJoin();
		$lookup = new GetAsLookup();
		$lookup->set('join.a.created_by', 'created_by');
		$get = $this->joinDefinition();
		$subject = new CustomJoin($config, $dynamic, $other, $lookup, new JoinStructure());

		$inline = $subject->get([$get], '$item', 'article', ['a']);

		$this->assertStringContainsString('$item->created_byIdUsersB = $this->get', $inline);
		$this->assertStringContainsString('($item->created_by);', $inline);

		$default = (new JoinStructure())->get($get, 'article');
		$dynamic->set('site.article.b.id', 'a.created_by');
		$this->assertTrue($subject->check($default, $get, []));
		$this->assertSame('', $subject->get([$get], '$item', 'article', []));
		$this->assertStringContainsString(
			Placefix::_h('STRING') . '->created_byIdUsersB',
			$other->get('site.article.a.created_by.created_byIdUsersB')
		);
	}

	/**
	 * Query composition emits the first source once and suppresses duplicate definitions.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testQueriesBuildsSelectFromAndDeduplicatesPerTargetAndCode(): void
	{
		$config = $this->compilerConfig(['build_target' => 'site']);
		$subject = new Queries(
			$config,
			new JoinStructure(),
			new SiteDynamicGet(),
			new OtherQuery(),
			new Placeholder($config)
		);
		$get = $this->mainDefinition();

		$output = $subject->get([$get], 'article');

		$this->assertStringContainsString('Get from #__demo_articles as a', $output);
		$this->assertStringContainsString("\$query->select('a.*');", $output);
		$this->assertStringContainsString("\$query->from(\$db->quoteName('#__demo_articles', 'a'));", $output);
		$this->assertSame('', $subject->get([$get], 'article'));
		$this->assertNotSame('', $subject->get([$get], 'category'));
	}

	/**
	 * List-query generation preserves target database access, pagination, extensions, and return policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testListQueryBuildsTargetDatabaseAndOptionalClausePipeline(): void
	{
		$config = $this->compilerConfig(['joomla_version' => 6, 'build_target' => 'site']);
		$main = new SiteMainGet();
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->method('get')->willReturn(PHP_EOL . '\t\tCUSTOM_FILTER();');
		$subject = new ListQuery(
			$config,
			$dispenser,
			new Queries($config, new JoinStructure(), new SiteDynamicGet(), new OtherQuery(), new Placeholder($config)),
			new QueryFilter($config, new SiteFieldData(), new SiteFieldDecodeFilter(), $main, new OtherFilter()),
			new QueryWhere($config, new OtherWhere(), $main),
			new QueryOrder($config, new OtherOrder(), $main),
			new QueryGroup($config, new OtherGroup(), $main)
		);
		$get = (object) [
			'pagination' => 0,
			'main_get' => [$this->mainDefinition()],
			'where' => [['table_key' => 'a.published', 'operator' => '=', 'value_key' => 1]],
			'order' => [['table_key' => 'a.title', 'direction' => 'ASC']],
			'group' => [['table_key' => 'a.id']]
		];

		$output = $subject->get($get, 'article');

		$this->assertStringContainsString("\$this->setState('list.limit', 0);", $output);
		$this->assertStringContainsString('$db = $this->getDatabase();', $output);
		$this->assertStringContainsString('CUSTOM_FILTER();', $output);
		$this->assertStringContainsString("\$query->where('a.published = 1');", $output);
		$this->assertStringContainsString("\$query->order('a.title ASC');", $output);
		$this->assertStringContainsString("\$query->group('a.id');", $output);
		$this->assertStringEndsWith("\t\treturn \$query;", $output);
		$this->assertStringNotContainsString('return $query;', $subject->get($get, 'article-two', false));
	}

	/**
	 * Method wrapper generation preserves the public name, body, and documented return contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testMethodsWrapsNonEmptyBodyAndRejectsEmptyBody(): void
	{
		$config = $this->compilerConfig();
		$subject = new Methods(
			$config,
			new Placeholder($config),
			$this->inertCompilerCollaborator(GetItem::class),
			$this->inertCompilerCollaborator(GetItems::class),
			$this->inertCompilerCollaborator(ListQuery::class),
			$this->inertCompilerCollaborator(CustomGetMethods::class),
			$this->inertCompilerCollaborator(UikitLoader::class)
		);

		$output = $subject->getMethod(PHP_EOL . "\t\treturn 42;", 'answer', 'int  The answer.');

		$this->assertStringContainsString('public function answer()', $output);
		$this->assertStringContainsString('@return int  The answer.', $output);
		$this->assertStringContainsString('return 42;', $output);
		$this->assertSame('', $subject->getMethod('', 'unused', 'void'));
	}

	/**
	 * Main table query definition.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.6
	 */
	private function mainDefinition(): array
	{
		return [
			'key' => 'main',
			'as' => 'a',
			'selection' => [
				'type' => 'db',
				'table' => '#__demo_articles',
				'name' => 'Articles',
				'select' => "\$query->select('a.*');",
				'from' => "\$db->quoteName('#__demo_articles', 'a')"
			]
		];
	}

	/**
	 * Joined user query definition.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.6
	 */
	private function joinDefinition(): array
	{
		return [
			'key' => 'join',
			'as' => 'b',
			'on_field' => 'a.created_by',
			'join_field' => 'b.id',
			'operator' => '=',
			'type' => 'LEFT',
			'selection' => [
				'type' => 'db',
				'table' => '#__users',
				'name' => 'Users',
				'select' => "\$query->select('b.id, b.name');",
				'from' => "\$db->quoteName('#__users', 'b')"
			]
		];
	}
}
