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
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\EventDispatcher;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelExpertField;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteDecrypt;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\DecodeColumn;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\FieldonContentPrepare;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\FilterColumn;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\Globals;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\JoinStructure;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\UikitLoader;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Dynamic-get row transformation and support-code renderer contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Globals::class)]
#[CoversClass(JoinStructure::class)]
#[CoversClass(FilterColumn::class)]
#[CoversClass(DecodeColumn::class)]
#[CoversClass(FieldonContentPrepare::class)]
#[CoversClass(UikitLoader::class)]
#[UsesClass(ContentOne::class)]
#[UsesClass(EventDispatcher::class)]
#[UsesClass(ModelExpertField::class)]
#[UsesClass(SiteDecrypt::class)]
#[UsesClass(Placeholder::class)]
final class FieldRendererTest extends CompilerDomainTestCase
{
	/**
	 * Global mappings emit state/property writes and ignore malformed/unknown entries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGlobalsRendersOnlySelectedSupportedMappings(): void
	{
		$output = (new Globals())->get(
			[
				['as' => 'a', 'type' => 1, 'name' => 'category', 'key' => 'catid'],
				['as' => 'b', 'type' => 2, 'name' => 'owner', 'key' => 'user_id'],
				['as' => 'a', 'type' => 99, 'name' => 'ignored', 'key' => 'x'],
				['as' => 'c', 'type' => 1, 'name' => 'not_selected', 'key' => 'x']
			],
			'$item',
			['a', 'b', 'a']
		);

		$this->assertStringContainsString("\$this->setState('a.category', \$item->catid);", $output);
		$this->assertStringContainsString('$this->b_owner = $item->user_id;', $output);
		$this->assertStringNotContainsString('ignored', $output);
		$this->assertStringNotContainsString('not_selected', $output);
		$this->assertSame('', (new Globals())->get([], '$item', ['a']));
	}

	/**
	 * Join metadata strips aliases and builds deterministic normalized method keys.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testJoinStructureNormalizesJoinMethodIdentity(): void
	{
		$subject = new JoinStructure();
		$result = $subject->get([
			'key' => 'join-key-17',
			'as' => 'b',
			'on_field' => 'a.created_by',
			'join_field' => 'b.id',
			'selection' => ['name' => 'Users']
		], 'Article Detail');

		$this->assertSame('created_by', $result['on_field']);
		$this->assertSame('id', $result['join_field']);
		$this->assertSame('Id', $result['Join_field']);
		$this->assertSame('Users', $result['name']);
		$this->assertSame('article_detail', $result['code']);
		$this->assertSame('B', $result['AS']);
		$this->assertSame('created_byIdUsersB', $result['valueName']);
		$this->assertMatchesRegularExpression('/^Created_byIdUsers[A-Za-z]{4}_B$/', $result['methodName']);
		$this->assertSame('name', $subject->getFieldName(' a.name '));
		$this->assertNull($subject->get(['as' => 'a'], 'code'));
	}

	/**
	 * Decode renderers reverse layered codecs and suppress duplicate row work.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDecodeColumnRendersLayeredCodeOnce(): void
	{
		$config = $this->compilerConfig(['cryption_types' => []]);
		$subject = new DecodeColumn(
			$config,
			new Placeholder($config),
			new ModelExpertField(),
			new SiteDecrypt()
		);
		$get = ['key' => 'main', 'selection' => ['select' => '$query->select("a.payload")']];
		$checker = ['payload' => ['decode' => ['base64', 'json']]];

		$output = $subject->get($get, $checker, '$item', 'article');

		$this->assertStringContainsString('$item->payload = json_decode($item->payload, true);', $output);
		$this->assertStringContainsString('$item->payload = base64_decode($item->payload);', $output);
		$this->assertLessThan(
			strpos($output, 'base64_decode($item->payload)'),
			strpos($output, 'json_decode($item->payload')
		);
		$this->assertSame('', $subject->get($get, $checker, '$item', 'article'));
	}

	/**
	 * Column filters render the chosen removal strategy and are idempotent per row key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testFilterColumnRendersGroupGuardAndRemovalStrategyOnce(): void
	{
		$subject = new FilterColumn();
		$get = [
			'key' => 'main',
			'as' => 'a',
			'selection' => ['select' => '$query->select("a.groups")']
		];
		$filters = ['groups' => ['table_key' => 'a.groups', 'filter_type' => 4]];

		$output = $subject->get($get, $filters, '$item', '$items[$key]', 'article', '');

		$this->assertStringContainsString('array_intersect((array) $this->groups, (array) $item->groups)', $output);
		$this->assertStringContainsString('unset($items[$key]);', $output);
		$this->assertStringContainsString('continue;', $output);
		$this->assertSame('', $subject->get($get, $filters, '$item', '$items[$key]', 'article', ''));
	}

	/**
	 * Content-prepare code follows the compile-target event axis and records dispatcher setup.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testContentPrepareUsesTargetSpecificEventDispatchContract(): void
	{
		$get = [
			'key' => 'main',
			'context' => 'article',
			'selection' => ['select' => '$query->select("a.body")']
		];
		$checker = ['body' => []];

		$legacyDispatcher = new EventDispatcher();
		$legacy = new FieldonContentPrepare(
			$this->compilerConfig(['joomla_version' => 4, 'component_code_name' => 'demo']),
			$this->componentContent(),
			$legacyDispatcher
		);
		$legacyOutput = $legacy->get($get, $checker, '$item', 'article');

		$this->assertStringContainsString('$this->_dispatcher->triggerEvent("onContentPrepare"', $legacyOutput);
		$this->assertStringContainsString("'com_demo.article.body'", $legacyOutput);
		$this->assertStringContainsString('$this->_dispatcher = ', $legacyDispatcher->get('article'));

		$modernDispatcher = new EventDispatcher();
		$modern = new FieldonContentPrepare(
			$this->compilerConfig(['joomla_version' => 6, 'component_code_name' => 'demo']),
			$this->componentContent(),
			$modernDispatcher
		);
		$modernOutput = $modern->get($get, $checker, '$item', 'article');

		$this->assertStringContainsString("\$this->getDispatcher()->dispatch('onContentPrepare'", $modernOutput);
		$this->assertStringContainsString("'context' => 'com_demo.article.body'", $modernOutput);
		$this->assertStringNotContainsString('$this->_dispatcher = ', $modernDispatcher->get('article'));
	}

	/**
	 * UIkit versions one and two emit one field loader plus the public component accessor.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUikitLoaderEmitsSupportedLoaderOnceAndAccessor(): void
	{
		$subject = new UikitLoader($this->compilerConfig(['uikit' => 2]), $this->componentContent());
		$get = ['key' => 'main', 'selection' => ['select' => '$query->select("a.body")']];

		$output = $subject->get($get, ['body' => []], '$item', 'article');

		$this->assertStringContainsString('DemoHelper::getUikitComp($item->body,$this->uikitComp)', $output);
		$this->assertSame('', $subject->get($get, ['body' => []], '$item', 'article'));
		$this->assertStringContainsString('public function getUikitComp()', $subject->getUikitComp());
		$this->assertStringContainsString('return $this->uikitComp;', $subject->getUikitComp());

		$disabled = new UikitLoader($this->compilerConfig(['uikit' => 0]), $this->componentContent());
		$this->assertSame('', $disabled->get($get, ['body' => []], '$item', 'article'));
		$this->assertSame('', $disabled->getUikitComp());
	}

	/**
	 * Create global content containing the generated component class prefix.
	 *
	 * @return  ContentOne
	 * @since   6.1.6
	 */
	private function componentContent(): ContentOne
	{
		$content = new ContentOne();
		$content->set('Component', 'Demo');

		return $content;
	}
}
