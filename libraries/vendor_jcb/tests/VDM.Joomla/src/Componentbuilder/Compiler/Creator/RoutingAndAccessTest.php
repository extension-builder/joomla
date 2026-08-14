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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Creator;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Abstraction\Registry\Traits\IsArray;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryCode;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Request as RequestBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Router as RouterBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Creator\AccessSectionsCategory;
use VDM\Joomla\Componentbuilder\Compiler\Creator\AccessSectionsJoomlaFields;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Request;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Router;
use VDM\Joomla\Componentbuilder\Compiler\Creator\RouterConstructorDefault;
use VDM\Joomla\Componentbuilder\Compiler\Creator\RouterConstructorManual;
use VDM\Joomla\Componentbuilder\Compiler\Creator\RouterMethodsDefault;
use VDM\Joomla\Componentbuilder\Compiler\Creator\RouterMethodsManual;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Utilities\GetHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Generated access XML, request normalization, and router contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(AccessSectionsCategory::class)]
#[CoversClass(AccessSectionsJoomlaFields::class)]
#[CoversClass(Request::class)]
#[CoversClass(Router::class)]
#[CoversClass(RouterConstructorDefault::class)]
#[CoversClass(RouterConstructorManual::class)]
#[CoversClass(RouterMethodsDefault::class)]
#[CoversClass(RouterMethodsManual::class)]
#[UsesClass(CategoryCode::class)]
#[UsesClass(RequestBuilder::class)]
#[UsesClass(RouterBuilder::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(Dispenser::class)]
#[UsesClass(Indent::class)]
#[UsesClass(Line::class)]
#[UsesClass(GetHelper::class)]
#[UsesClass(StringHelper::class)]
#[UsesTrait(IsArray::class)]
final class RoutingAndAccessTest extends CreatorTestCase
{
	/**
	 * Emit category permissions only for the category mapped to this view.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAccessSectionsCategoryBuildsExactMappedSection(): void
	{
		$categoryCode = new CategoryCode();
		$categoryCode->set('article.views', 'articles');
		$subject = new AccessSectionsCategory($categoryCode);

		$this->assertSame('', $subject->get('article', 'other'));
		$this->assertSame(
			PHP_EOL . "\t" . '<section name="category.articles">'
				. PHP_EOL . "\t\t" . '<action name="core.create" title="JACTION_CREATE" description="JACTION_CREATE_COMPONENT_DESC" />'
				. PHP_EOL . "\t\t" . '<action name="core.delete" title="JACTION_DELETE" description="COM_CATEGORIES_ACCESS_DELETE_DESC" />'
				. PHP_EOL . "\t\t" . '<action name="core.edit" title="JACTION_EDIT" description="COM_CATEGORIES_ACCESS_EDIT_DESC" />'
				. PHP_EOL . "\t\t" . '<action name="core.edit.state" title="JACTION_EDITSTATE" description="COM_CATEGORIES_ACCESS_EDITSTATE_DESC" />'
				. PHP_EOL . "\t\t" . '<action name="core.edit.own" title="JACTION_EDITOWN" description="COM_CATEGORIES_ACCESS_EDITOWN_DESC" />'
				. PHP_EOL . "\t" . '</section>',
			$subject->get('article', 'articles')
		);
	}

	/**
	 * Emit both Joomla custom-field sections with their complete action sets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAccessSectionsJoomlaFieldsBuildsCompleteSections(): void
	{
		$xml = (new AccessSectionsJoomlaFields())->get();

		$this->assertStringStartsWith(PHP_EOL . "\t" . '<section name="fieldgroup">', $xml);
		$this->assertStringEndsWith(PHP_EOL . "\t" . '</section>', $xml);
		$this->assertSame(2, substr_count($xml, '<section name='));
		$this->assertSame(10, substr_count($xml, '<action name='));
		$this->assertStringContainsString('<action name="core.edit.value" title="JACTION_EDITVALUE" description="COM_FIELDS_GROUP_PERMISSION_EDITVALUE_DESC" />', $xml);
		$this->assertStringContainsString('<action name="core.edit.value" title="JACTION_EDITVALUE" description="COM_FIELDS_FIELD_PERMISSION_EDITVALUE_DESC" />', $xml);
	}

	/**
	 * Extract a custom request key and rewrite the field name before registration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRequestExtractsCustomKeyAndUsesTargetFallback(): void
	{
		$builder = new RequestBuilder();
		$subject = new Request($builder);

		$subject->set(
			'article',
			'<field name="article_request_slug" type="text" />',
			'name="article_request_',
			'id'
		);
		$subject->set(
			'category',
			'<field name="category_request_id" type="number" />',
			'name="category_request_id',
			'id'
		);

		$this->assertSame(
			'<field name="slug" type="text" />',
			$builder->get('id.article.slug')
		);
		$this->assertSame(
			'<field name="id" type="number" />',
			$builder->get('id.category.id')
		);
	}

	/**
	 * Build identical reviewed constructor snippets for both routing strategies.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRouterConstructorStrategiesBuildExactViewRegistration(): void
	{
		$builder = new RouterBuilder();
		$builder->set('views', [
			(object) ['view' => 'article', 'key' => 'id', 'alias' => 'alias'],
			(object) ['view' => 'articles', 'key' => null, 'alias' => null]
		]);
		$expected = PHP_EOL . PHP_EOL
			. "\t\t// Add the (article:view) router configuration" . PHP_EOL
			. "\t\t\$article = new RouterViewConfiguration('article');" . PHP_EOL
			. "\t\t\$article->setKey('id');" . PHP_EOL
			. "\t\t\$this->registerView(\$article);" . PHP_EOL . PHP_EOL
			. "\t\t// Add the (articles:view) router configuration" . PHP_EOL
			. "\t\t\$articles = new RouterViewConfiguration('articles');" . PHP_EOL
			. "\t\t\$this->registerView(\$articles);";

		$this->assertSame($expected, (new RouterConstructorDefault($builder))->get());
		$this->assertSame($expected, (new RouterConstructorManual($builder))->get());
	}

	/**
	 * Generate routing methods only for views with both key and alias metadata.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRouterMethodStrategiesBuildKeyedMethodsAndSkipLists(): void
	{
		$builder = new RouterBuilder();
		$builder->set('views', [
			(object) [
				'view' => 'article',
				'View' => 'Article',
				'key' => 'id',
				'alias' => 'alias',
				'table' => '#__demo_article'
			],
			(object) [
				'view' => 'articles',
				'View' => 'Articles',
				'key' => null,
				'alias' => null,
				'table' => '#__demo_article'
			]
		]);
		$default = (new RouterMethodsDefault($builder))->get();
		$manual = (new RouterMethodsManual($builder))->get();

		$this->assertSame($default, $manual);
		$this->assertStringContainsString('public function getArticleId($segment, $query)', $default);
		$this->assertStringContainsString("->from(\$this->db->quoteName('#__demo_article'))", $default);
		$this->assertStringContainsString("\$this->db->quoteName('alias') . ' = :alias'", $default);
		$this->assertStringContainsString('public function getArticleSegment($id, $query)', $default);
		$this->assertStringNotContainsString('getArticlesId', $default);
		$this->assertStringNotContainsString('getArticlesSegment', $default);
	}

	/**
	 * Select custom constructor and method code through configured router modes.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRouterSelectsAndConsumesCustomCodeModes(): void
	{
		$calls = [];
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->method('get')->willReturnCallback(
			static function (string $first, string $second, string $prefix,
				?string $note, bool $unset) use (&$calls): string
			{
				$calls[] = [$first, $second, $prefix, $note, $unset];

				return $prefix . 'custom:' . $second;
			}
		);
		$request = new RequestBuilder();
		$builder = new RouterBuilder([
			'mode_before' => 3,
			'mode_method' => 3
		]);
		$subject = $this->router($dispenser, $request, $builder);

		$this->assertSame(PHP_EOL . PHP_EOL . 'custom:constructor_before_parent', $subject->getConstructor());
		$this->assertSame(PHP_EOL . PHP_EOL . 'custom:methods', $subject->getMethods());
		$this->assertSame(PHP_EOL . PHP_EOL . 'custom:constructor_after_parent', $subject->getConstructorAfterParent());
		$this->assertSame([
			['_site_router_', 'constructor_before_parent', PHP_EOL . PHP_EOL, null, true],
			['_site_router_', 'methods', PHP_EOL . PHP_EOL, null, true],
			['_site_router_', 'constructor_after_parent', PHP_EOL . PHP_EOL, null, true]
		], $calls);
	}

	/**
	 * Update an ID router key from recorded request metadata exactly once.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRouterUpdatesIdKeyFromRequestMetadata(): void
	{
		$dispenser = $this->createStub(Dispenser::class);
		$request = new RequestBuilder();
		$request->set('slug.article.slug', '<field name="slug" />');
		$builder = new RouterBuilder([
			'mode_before' => 1,
			'mode_method' => 0,
			'views' => [
				(object) [
					'view' => 'article',
					'key' => 'id',
					'alias' => 'alias'
				]
			]
		]);
		$subject = $this->router($dispenser, $request, $builder);

		$constructor = $subject->getConstructor();

		$this->assertStringContainsString("\$article->setKey('slug');", $constructor);
		$this->assertSame('slug', $request->get('views')[0]->key);
		$this->assertSame('', $subject->getMethods());
	}

	/**
	 * Assemble a router creator with real lightweight strategy collaborators.
	 *
	 * @param   Dispenser      $dispenser  Custom-code source.
	 * @param   RequestBuilder $request    Request registry.
	 * @param   RouterBuilder  $builder    Router registry.
	 *
	 * @return  Router
	 * @since   6.1.6
	 */
	private function router(
		Dispenser $dispenser,
		RequestBuilder $request,
		RouterBuilder $builder
	): Router
	{
		return new Router(
			$dispenser,
			$request,
			$builder,
			new RouterConstructorDefault($builder),
			new RouterConstructorManual($builder),
			new RouterMethodsDefault($builder),
			new RouterMethodsManual($builder)
		);
	}
}
