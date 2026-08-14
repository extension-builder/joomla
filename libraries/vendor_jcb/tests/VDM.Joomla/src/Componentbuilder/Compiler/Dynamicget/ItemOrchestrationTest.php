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
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\EventDispatcher;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ModelExpertFieldInitiator;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherFilter;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherGroup;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherJoin;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherOrder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherQuery;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OtherWhere;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteDecrypt;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldDecodeFilter;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\CustomGetMethods;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\CustomJoin;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\DecodeColumn;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\FieldonContentPrepare;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\FilterColumn;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\GetItem;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\GetItems;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\Globals;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\JoinStructure;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\Queries;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryFilter;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryGroup;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryOrder;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\QueryWhere;
use VDM\Joomla\Componentbuilder\Compiler\Dynamicget\UikitLoader;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Dynamic-get single, list, and joined-method orchestration contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(GetItem::class)]
#[CoversClass(GetItems::class)]
#[CoversClass(CustomGetMethods::class)]
#[UsesClass(Globals::class)]
#[UsesClass(JoinStructure::class)]
#[UsesClass(ContentOne::class)]
#[UsesClass(SiteDecrypt::class)]
#[UsesClass(SiteFieldData::class)]
#[UsesClass(SiteFieldDecodeFilter::class)]
#[UsesClass(ModelExpertFieldInitiator::class)]
#[UsesClass(EventDispatcher::class)]
#[UsesClass(Placeholder::class)]
final class ItemOrchestrationTest extends CompilerDomainTestCase
{
	/**
	 * A custom single-item method uses the selected database API, fails safely, and returns data.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetItemBuildsModernDatabaseFailSafeAndCustomReturn(): void
	{
		$config = $this->compilerConfig([
			'joomla_version' => 6,
			'cryption_types' => [],
			'build_target' => 'site'
		]);
		$subject = new GetItem(
			$config,
			new SiteDecrypt(),
			new Placeholder($config),
			$this->inertCompilerCollaborator(Language::class),
			$this->componentContent(),
			new SiteFieldData(),
			new SiteFieldDecodeFilter(),
			new ModelExpertFieldInitiator(),
			new EventDispatcher(),
			$this->inertCompilerCollaborator(DecodeColumn::class),
			$this->inertCompilerCollaborator(FilterColumn::class),
			$this->inertCompilerCollaborator(FieldonContentPrepare::class),
			$this->inertCompilerCollaborator(UikitLoader::class),
			new Globals(),
			$this->inertCompilerCollaborator(CustomJoin::class),
			$this->inertCompilerCollaborator(Queries::class),
			$this->inertCompilerCollaborator(QueryFilter::class),
			$this->inertCompilerCollaborator(QueryWhere::class),
			$this->inertCompilerCollaborator(QueryOrder::class),
			$this->inertCompilerCollaborator(QueryGroup::class)
		);

		$output = $subject->get((object) ['configured' => true], 'article', '', 'custom');

		$this->assertStringContainsString('$db = $this->getDatabase();', $output);
		$this->assertStringContainsString('$query = $db->getQuery(true);', $output);
		$this->assertStringContainsString('$data = $db->loadObject();', $output);
		$this->assertStringContainsString('if (empty($data))', $output);
		$this->assertStringContainsString('return false;', $output);
		$this->assertStringEndsWith("\t\treturn \$data;", $output);
		$this->assertStringContainsString('add your custom code here.', $subject->get(null, 'article'));
	}

	/**
	 * List post-processing always creates slugs and retains custom calculation placement.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGetItemsBuildsSlugLoopAndCalculation(): void
	{
		$config = $this->compilerConfig(['cryption_types' => [], 'build_target' => 'site']);
		$subject = new GetItems(
			$config,
			new SiteDecrypt(),
			new Placeholder($config),
			$this->componentContent(),
			new SiteFieldData(),
			new SiteFieldDecodeFilter(),
			new ModelExpertFieldInitiator(),
			new EventDispatcher(),
			$this->inertCompilerCollaborator(DecodeColumn::class),
			$this->inertCompilerCollaborator(FilterColumn::class),
			$this->inertCompilerCollaborator(FieldonContentPrepare::class),
			$this->inertCompilerCollaborator(UikitLoader::class),
			new Globals(),
			$this->inertCompilerCollaborator(CustomJoin::class)
		);
		$get = (object) [
			'addcalculation' => 1,
			'php_calculation' => '$item->total = 7;'
		];

		$output = $subject->get($get, 'articles');

		$this->assertStringContainsString('foreach ($items as $nr => &$item)', $output);
		$this->assertStringContainsString("\$item->slug = (\$item->id ?? '0')", $output);
		$this->assertStringContainsString('$item->total = 7;', $output);
		$this->assertSame(['$item->total = 7;'], $get->php_calculation);
		$this->assertSame(PHP_EOL, $subject->get(null, 'articles'));
	}

	/**
	 * Joined custom gets emit a dedicated query method with a guarded result contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomGetMethodsBuildsJoinedQueryMethod(): void
	{
		$config = $this->compilerConfig([
			'joomla_version' => 6,
			'cryption_types' => [],
			'build_target' => 'site'
		]);
		$subject = new CustomGetMethods(
			$config,
			new Placeholder($config),
			$this->inertCompilerCollaborator(FieldonContentPrepare::class),
			new JoinStructure(),
			$this->inertCompilerCollaborator(DecodeColumn::class),
			$this->inertCompilerCollaborator(FilterColumn::class),
			$this->inertCompilerCollaborator(UikitLoader::class),
			$this->componentContent(),
			new SiteDecrypt(),
			new ModelExpertFieldInitiator(),
			new SiteFieldData(),
			new SiteFieldDecodeFilter(),
			new OtherJoin(),
			new OtherQuery(),
			new OtherFilter(),
			new OtherWhere(),
			new OtherOrder(),
			new OtherGroup(),
			new EventDispatcher()
		);
		$get = [
			'key' => 'comments',
			'as' => 'b',
			'on_field' => 'a.id',
			'join_field' => 'b.article_id',
			'operator' => '=',
			'selection' => [
				'table' => '#__demo_comments',
				'name' => 'Comments',
				'select' => "\$query->select('b.*');",
				'from' => "\$db->quoteName('#__demo_comments', 'b')"
			]
		];

		$output = $subject->get((object) ['custom_get' => [$get]], 'article');

		$this->assertMatchesRegularExpression('/public function getIdArticle_idComments[A-Za-z]{4}_B\(\$id\)/', $output);
		$this->assertStringContainsString('$db = $this->getDatabase();', $output);
		$this->assertStringContainsString("\$query->where('b.article_id = ' . \$db->quote(\$id));", $output);
		$this->assertStringContainsString('$db->execute();', $output);
		$this->assertStringContainsString('if ($db->getNumRows())', $output);
		$this->assertStringContainsString('return $db->loadObjectList();', $output);
		$this->assertStringEndsWith(PHP_EOL, $output);
		$this->assertSame('', $subject->get((object) [], 'article'));
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
