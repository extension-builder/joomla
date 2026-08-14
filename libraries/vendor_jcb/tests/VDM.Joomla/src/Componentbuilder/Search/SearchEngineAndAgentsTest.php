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

namespace VDM\Joomla\Tests\Componentbuilder\Search;


use Joomla\Input\Input;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Search\Abstraction\Engine;
use VDM\Joomla\Componentbuilder\Search\Agent\Find;
use VDM\Joomla\Componentbuilder\Search\Agent\Replace;
use VDM\Joomla\Componentbuilder\Search\Agent\Search;
use VDM\Joomla\Componentbuilder\Search\Agent\Update;
use VDM\Joomla\Componentbuilder\Search\Config;
use VDM\Joomla\Componentbuilder\Search\Engine\Basic;
use VDM\Joomla\Componentbuilder\Search\Engine\Regex;
use VDM\Joomla\Componentbuilder\Search\Interfaces\FindInterface;
use VDM\Joomla\Componentbuilder\Search\Interfaces\ReplaceInterface;
use VDM\Joomla\Componentbuilder\Search\Interfaces\SearchInterface;
use VDM\Joomla\Componentbuilder\Search\Interfaces\SearchTypeInterface;
use VDM\Tests\Support\TestCase;


/**
 * Search-engine, recursive-state, and agent collaborator contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Config::class)]
#[CoversClass(Engine::class)]
#[CoversClass(Basic::class)]
#[CoversClass(Regex::class)]
#[CoversClass(Search::class)]
#[CoversClass(Update::class)]
#[CoversClass(Find::class)]
#[CoversClass(Replace::class)]
#[CoversClass(FindInterface::class)]
#[CoversClass(ReplaceInterface::class)]
#[CoversClass(SearchInterface::class)]
#[CoversClass(SearchTypeInterface::class)]
#[UsesClass(Input::class)]
final class SearchEngineAndAgentsTest extends TestCase
{
	/**
	 * Protect input filtering, default values, markers, and registry caching.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testConfigFiltersInputAndCachesMutableCounters(): void
	{
		$config = $this->config([
			'type_search' => '2foo',
			'search_value' => '<tag>Needle</tag>',
			'replace_value' => '<b>Thread</b>',
			'match_case' => '1',
			'whole_word' => '1',
			'regex_search' => '0',
			'component_id' => '17x',
			'table_name' => 'admin-view!',
			'field_name' => 'php_view!',
			'item_id' => '42x',
		]);

		$this->assertSame(2, $config->type_search);
		$this->assertSame('<tag>Needle</tag>', $config->search_value);
		$this->assertSame('<b>Thread</b>', $config->replace_value);
		$this->assertSame(1, $config->match_case);
		$this->assertSame(1, $config->whole_word);
		$this->assertSame(17, $config->component_id);
		$this->assertSame('adminview', $config->table_name);
		$this->assertSame('php_view', $config->field_name);
		$this->assertSame(42, $config->item_id);
		$this->assertSame('{+|=[', $config->marker_start);
		$this->assertSame(']=|+}', $config->marker_end);
		$this->assertSame(0, $config->field_counter);
		$config->field_counter += 3;
		$this->assertSame(3, $config->field_counter);
	}

	/**
	 * Protect literal whole-word matching, case policy, and exact markers.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testBasicEngineMarksAndReplacesLiteralWholeWords(): void
	{
		$config = $this->config([
			'search_value' => 'Needle',
			'replace_value' => 'Thread',
			'match_case' => 0,
			'whole_word' => 1,
		]);
		$subject = new Basic($config);

		$this->assertInstanceOf(SearchTypeInterface::class, $subject);
		$this->assertSame('a {+|=[NEEDLE]=|+} here', $subject->string('  a NEEDLE here  '));
		$this->assertNull($subject->string('needlework'));
		$this->assertSame('a Thread here', $subject->replace('a needle here'));
		$this->assertSame('needlework', $subject->replace('needlework'));
		$this->assertSame(2, $config->line_counter);
	}

	/**
	 * Protect regular-expression semantics independently from literal matching.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRegexEngineUsesPostedPatternAndCasePolicy(): void
	{
		$config = $this->config([
			'search_value' => 'foo[0-9]+',
			'replace_value' => 'bar',
			'match_case' => 1,
		]);
		$subject = new Regex($config);

		$this->assertSame('{+|=[foo42]=|+}', $subject->string('foo42'));
		$this->assertNull($subject->string('FOO42'));
		$this->assertSame('before bar after', $subject->replace('before foo7 after'));
		$this->assertTrue($subject->match('foo123'));
		$this->assertFalse($subject->match('foo'));
	}

	/**
	 * Protect nested path keys, multiline numbering, counters, and reset state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSearchAgentIndexesNestedMatchesAndResetsPerTable(): void
	{
		$config = $this->config(['search_value' => 'Needle']);
		$engine = new Basic($config);
		$subject = new Search($config, $engine);
		$value = [
			'group' => [
				'row' => [
					'code' => "first\nNeedle second",
				],
			],
		];

		$this->assertInstanceOf(SearchInterface::class, $subject);
		$this->assertTrue($subject->value($value, 9, 'payload', 'demo'));
		$this->assertSame(
			['group.row.code.2' => '{+|=[Needle]=|+} second'],
			$subject->get('demo')[9]['payload']
		);
		$this->assertSame(1, $config->field_counter);
		$this->assertSame(2, $config->line_counter);
		$this->assertFalse($subject->value(['empty' => 'nothing'], 10, 'payload', 'demo'));
		$subject->reset('demo');
		$this->assertNull($subject->get('demo'));
	}

	/**
	 * Protect targeted nested and line-level updates without collateral changes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdateAgentTargetsNestedPathAndSpecificLine(): void
	{
		$config = $this->config([
			'search_value' => 'Needle',
			'replace_value' => 'Thread',
		]);
		$subject = new Update(new Basic($config));
		$value = [
			'left' => "Needle one\nNeedle two",
			'right' => "Needle three\nNeedle four",
		];

		$this->assertSame(
			[
				'left' => "Needle one\nThread two",
				'right' => "Needle three\nNeedle four",
			],
			$subject->value($value, 'left.2')
		);
		$this->assertNull($subject->value($value, 'missing.1'));
		$this->assertSame('Thread one' . PHP_EOL . 'Needle two', $subject->value($value['left'], 1));
	}

	/**
	 * Protect item filtering, original-value capture, replacement, and isolation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFindAndReplaceAgentsCaptureOnlyChangedFields(): void
	{
		$config = $this->config([
			'table_name' => 'demo',
			'search_value' => 'Needle',
			'replace_value' => 'Thread',
		]);
		$search = new Search($config, new Basic($config));
		$find = new Find($config, $search);
		$replace = new Replace($config, new Update(new Basic($config)));
		$items = [
			7 => (object) ['id' => 7, 'title' => 'Needle title', 'note' => 'safe'],
			8 => (object) ['id' => 8, 'title' => 'safe', 'note' => 'also safe'],
		];

		$this->assertInstanceOf(FindInterface::class, $find);
		$this->assertInstanceOf(ReplaceInterface::class, $replace);
		$find->items($items);
		$this->assertEquals(
			[7 => (object) ['id' => 7, 'title' => 'Needle title']],
			$find->get()
		);

		$replace->items($find->get());
		$this->assertEquals(
			[7 => (object) ['id' => 7, 'title' => 'Thread title']],
			$replace->get()
		);
		$find->reset();
		$replace->reset();
		$this->assertNull($find->get());
		$this->assertNull($replace->get());
	}

	/**
	 * Build a search configuration from deterministic request input.
	 *
	 * @param   array<string, mixed>  $values  Request values.
	 *
	 * @return  Config
	 * @since   6.1.6
	 */
	private function config(array $values): Config
	{
		return new Config(new Input($values));
	}
}
