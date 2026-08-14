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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Builder;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Abstraction\Registry\Traits\IsArray;
use VDM\Joomla\Abstraction\Registry\Traits\IsString;
use VDM\Joomla\Abstraction\Registry\Traits\VarExport;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionAction;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionComponent;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionCore;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionDashboard;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionGlobalAction;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionViews;
use VDM\Joomla\Componentbuilder\Compiler\Builder\UpdateMysql;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Tests\Support\CompilerUtilityTestCase;


/**
 * Focused tests for the nine Builder leaves with behavioral overrides.
 *
 * @since  6.1.6
 */
#[CoversClass(ContentMulti::class)]
#[CoversClass(ContentOne::class)]
#[CoversClass(PermissionAction::class)]
#[CoversClass(PermissionComponent::class)]
#[CoversClass(PermissionCore::class)]
#[CoversClass(PermissionDashboard::class)]
#[CoversClass(PermissionGlobalAction::class)]
#[CoversClass(PermissionViews::class)]
#[CoversClass(UpdateMysql::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(Indent::class)]
#[UsesClass(Line::class)]
#[UsesClass(Placefix::class)]
#[UsesTrait(IsArray::class)]
#[UsesTrait(IsString::class)]
#[UsesTrait(VarExport::class)]
final class BuilderBehaviorTest extends CompilerUtilityTestCase
{
	/**
	 * Normalize ContentMulti's second path segment as a hash placeholder.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testContentMultiModelsTwoDimensionalPlaceholderPaths(): void
	{
		$subject = new ContentMulti();

		$subject->set('view|body', 'first');

		$this->assertSame([
			'view' => ['###body###' => 'first']
		], $subject->toArray());
		$this->assertSame('first', $subject->get('view|body'));
		$this->assertTrue($subject->isArray('view'));

		$subject->set('view|body|ignored', 'replaced');
		$this->assertSame('replaced', $subject->get('view|body'));
	}

	/**
	 * Normalize ContentOne's complete literal path as one hash placeholder.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testContentOneModelsTheCompletePathAsOnePlaceholder(): void
	{
		$subject = new ContentOne();

		$subject->set('view.body', 'value');

		$this->assertSame(['###view.body###' => 'value'], $subject->toArray());
		$this->assertSame('value', $subject->get('view.body'));
		$this->assertTrue($subject->isString('view.body'));
		$this->assertFalse($subject->exists('view|body'));
	}

	/**
	 * Flatten ContentOne with its constructor-selected null separator.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testContentOneFlattensWithItsDefaultSeparator(): void
	{
		$subject = new ContentOne();
		$subject->set('view.body', 'value');

		$this->assertSame(
			['###view.body###' => 'value'],
			$subject->flatten()
		);
	}

	/**
	 * Keep pipe-delimited action registries isolated by component and action.
	 *
	 * @param   class-string<Registry>  $class  Permission registry class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('plainPermissionRegistries')]
	public function testPlainPermissionRegistriesUsePipePaths(string $class): void
	{
		$subject = new $class();

		$subject->set('component|edit', 'core.edit');
		$subject->set('component|delete', 'core.delete');

		$this->assertSame('|', $subject->getSeparator());
		$this->assertSame([
			'component' => [
				'edit' => 'core.edit',
				'delete' => 'core.delete'
			]
		], $subject->toArray());
	}

	/**
	 * Provide the permission registries whose only override is their separator.
	 *
	 * @return  array<string, array{class-string<Registry>}>
	 * @since   6.1.6
	 */
	public static function plainPermissionRegistries(): array
	{
		return [
			'PermissionAction' => [PermissionAction::class],
			'PermissionCore' => [PermissionCore::class],
			'PermissionGlobalAction' => [PermissionGlobalAction::class]
		];
	}

	/**
	 * Build component permission XML with headers first and actions sorted.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPermissionComponentBuildsSortedXmlAndClearsState(): void
	{
		$subject = new PermissionComponent();
		$subject->set('->HEAD<-', [[
			'name' => 'core.manage',
			'title' => 'Manage',
			'description' => 'Manage component'
		]]);
		$subject->set('z-action', [
			'name' => 'core.z',
			'title' => 'Zed',
			'description' => 'Zed action'
		]);
		$subject->set('a-action', [
			'name' => 'core.a',
			'title' => 'Alpha',
			'description' => 'Alpha action'
		]);

		$this->assertSame(
			'<section name="component">' . PHP_EOL
				. "\t\t" . '<action name="core.manage" title="Manage" description="Manage component" />' . PHP_EOL
				. "\t\t" . '<action name="core.a" title="Alpha" description="Alpha action" />' . PHP_EOL
				. "\t\t" . '<action name="core.z" title="Zed" description="Zed action" />' . PHP_EOL
				. "\t" . '</section>',
			$subject->build()
		);
		$this->assertFalse($subject->isActive());
		$this->assertSame('', $subject->build());
	}

	/**
	 * Build view permission sections in insertion order and clear state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPermissionViewsBuildsXmlSectionsAndClearsState(): void
	{
		$subject = new PermissionViews();
		$subject->set('articles', [[
			'name' => 'article.edit',
			'title' => 'Edit',
			'description' => 'Edit articles'
		]]);
		$subject->set('categories', [[
			'name' => 'category.edit',
			'title' => 'Edit',
			'description' => 'Edit categories'
		]]);

		$this->assertSame(
			PHP_EOL
				. "\t" . '<section name="articles">' . PHP_EOL
				. "\t\t" . '<action name="article.edit" title="Edit" description="Edit articles" />' . PHP_EOL
				. "\t" . '</section>' . PHP_EOL
				. "\t" . '<section name="categories">' . PHP_EOL
				. "\t\t" . '<action name="category.edit" title="Edit" description="Edit categories" />' . PHP_EOL
				. "\t" . '</section>',
			$subject->build()
		);
		$this->assertFalse($subject->isActive());
		$this->assertSame('', $subject->build());
	}

	/**
	 * Build the dashboard property for empty and populated registries.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPermissionDashboardBuildsAStablePropertyAndRetainsState(): void
	{
		$empty = new PermissionDashboard();
		$emptyBuild = $empty->build();

		$this->assertStringContainsString(
			"\tprotected array \$viewAccess = [];" . PHP_EOL,
			$emptyBuild
		);
		$this->assertStringNotContainsString('[VDM\\Joomla', $emptyBuild);

		$subject = new PermissionDashboard();
		$subject->set('articles', 'core.manage');
		$build = $subject->build();

		$this->assertStringContainsString(
			"\tprotected array \$viewAccess = [" . PHP_EOL,
			$build
		);
		$this->assertStringContainsString(
			"\t\t'articles' => 'core.manage'," . PHP_EOL,
			$build
		);
		$this->assertStringContainsString("\t];" . PHP_EOL, $build);
		$this->assertSame('core.manage', $subject->get('articles'));
	}

	/**
	 * Treat SQL with different whitespace as the same flat update key.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testUpdateMysqlNormalizesWhitespaceIntoOneFlatKey(): void
	{
		$subject = new UpdateMysql();

		$subject->set(' ALTER  TABLE `#__demo` ADD `state` INT ', 'first');

		$this->assertSame(
			['ALTERTABLE`#__demo`ADD`state`INT' => 'first'],
			$subject->toArray()
		);
		$this->assertSame(
			'first',
			$subject->get("ALTER\nTABLE `#__demo`\tADD `state` INT")
		);

		$subject->set('ALTER TABLE `#__demo` ADD `state` INT', 'replaced');
		$this->assertSame(1, count($subject));
		$this->assertSame('replaced', $subject->get('ALTERTABLE`#__demo`ADD`state`INT'));
	}
}
