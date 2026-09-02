<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Dynamic;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Dynamic\Expectations;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The documented expectations of a dynamic get API resource.
 *
 * @since 6.1.7
 */
#[CoversClass(Expectations::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class ExpectationsTest extends ArchitectureTestCase
{
	/**
	 * The docblock lines of an item get with every kind of clause.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_ITEM = <<<'GEN'

	 *
	 * The dynamic get expects, as far as it shows:
	 *  - a.id = the id of the request (the :id route segment)
	 *  - a.created_by = the id of the calling user
	 *  - a.access IN the access levels of the calling user
	 *  - a.groups IN the user groups of the calling user
	 *  - a.catid: the category filter, which the compiler does not build yet
	 *  - a.alias = $this->input->getString('alias'), a value the model reads at runtime, so the request must carry it
	 *  - a.owner = $this->userId, a value the model reads at runtime
	 *  - a.tags: matched inside its decoded array value
	 *  - b.type = 'truck'
	 *  - where a.published = 1
	 *  - ordered by a.ordering ASC
	 *  - ordered at random
	 *  - grouped by a.catid
	 * Custom PHP runs before the item, after the item and may add conditions or change the result the compiler cannot describe.
GEN;

	/**
	 * The docblock lines of a bare list get that paginates.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_BARE_LIST = <<<'GEN'

	 *
	 * The dynamic get sets no filter of its own.
	 * Paginated with page[offset] and page[limit].
GEN;

	/**
	 * Every clause of an item get is described in words.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryClauseOfAnItemGetIsDescribed(): void
	{
		$get = (object) [
			'gettype' => 1,
			'add_php_before_getitem' => 1,
			'add_php_after_getitem' => 1,
			'filter' => [
				['table_key' => 'a.id', 'operator' => '=', 'filter_type' => 1, 'state_key' => ''],
				['table_key' => 'a.created_by', 'operator' => '=', 'filter_type' => 2],
				['table_key' => 'a.access', 'operator' => 'IN', 'filter_type' => 3],
				['table_key' => 'a.groups', 'operator' => 'IN', 'filter_type' => 4],
				['table_key' => 'a.catid', 'operator' => '=', 'filter_type' => 5],
				['table_key' => 'a.alias', 'operator' => '=', 'filter_type' => 8, 'state_key' => "\$this->input->getString('alias')"],
				['table_key' => 'a.owner', 'operator' => '=', 'filter_type' => 8, 'state_key' => '$this->userId'],
				['table_key' => 'a.tags', 'operator' => '=', 'filter_type' => 9],
				['table_key' => 'b.type', 'operator' => '=', 'filter_type' => 11, 'state_key' => "'truck'"],
				['table_key' => '', 'operator' => '=', 'filter_type' => 1],
				'not a filter',
			],
			'where' => [['table_key' => 'a.published', 'operator' => '=', 'value_key' => '1'], ['table_key' => 'a.x', 'value_key' => '']],
			'order' => [['table_key' => 'a.ordering', 'direction' => 'ASC'], ['table_key' => 'a.id', 'direction' => 'RAND']],
			'group' => [['table_key' => 'a.catid']],
		];

		$this->assertSame(self::EXPECTED_ITEM, $this->renderer(Expectations::class)->get([
			'list' => false, 'settings' => (object) ['main_get' => $get],
		]));
	}

	/**
	 * A bare list get says it paginates, or that it returns every record.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testABareListGetSaysWhetherItPaginates(): void
	{
		$subject = $this->renderer(Expectations::class);

		$this->assertSame(self::EXPECTED_BARE_LIST, $subject->get([
			'list' => true, 'settings' => (object) ['main_get' => (object) ['gettype' => 2, 'pagination' => 1]],
		]));

		$all = $subject->get([
			'list' => true, 'settings' => (object) ['main_get' => (object) ['gettype' => 2, 'pagination' => 0, 'add_php_getlistquery' => 1]],
		]);

		$this->assertStringContainsString(' * Every record is returned, the get does not paginate.', $all);
		$this->assertStringContainsString(' * Custom PHP runs in the list query and may add conditions', $all);
	}

	/**
	 * The list id filter does not mention the route segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheListIdFilterDoesNotMentionTheRouteSegment(): void
	{
		$code = $this->renderer(Expectations::class)->get([
			'list' => true, 'settings' => (object) ['main_get' => (object) [
				'pagination' => 1, 'filter' => [['table_key' => 'a.id', 'operator' => '=', 'filter_type' => 1]],
			]],
		]);

		$this->assertStringContainsString(' *  - a.id = the id of the request' . PHP_EOL, $code);
		$this->assertStringNotContainsString(':id route segment', $code);
	}

	/**
	 * A resource without a main get is described as filterless.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAResourceWithoutAMainGetIsFilterless(): void
	{
		$this->assertSame(
			PHP_EOL . "\t *" . PHP_EOL . "\t * The dynamic get sets no filter of its own.",
			$this->renderer(Expectations::class)->get(['list' => false, 'settings' => (object) []])
		);
	}
}
