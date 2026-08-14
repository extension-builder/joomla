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


use Joomla\Input\Input;
use Joomla\Registry\Registry as JoomlaRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\FunctionRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Abstraction\Registry\Traits\InArray;
use VDM\Joomla\Abstraction\Registry\Traits\PathCount;
use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Layout as BuilderLayout;
use VDM\Joomla\Componentbuilder\Compiler\Builder\MovedPublishingFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\NewPublishingFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OrderZero;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionAction;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionComponent;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionCore;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionDashboard;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionGlobalAction;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionViews;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFieldData as SiteFieldDataBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SiteFields;
use VDM\Joomla\Componentbuilder\Compiler\Builder\TabCounter;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Creator\CustomButtonPermissions;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Helper;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Layout;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Permission;
use VDM\Joomla\Componentbuilder\Compiler\Creator\SiteFieldData;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * Builder state transitions and generated-helper contracts for creators.
 *
 * @since  6.1.6
 */
#[CoversClass(CustomButtonPermissions::class)]
#[CoversClass(Helper::class)]
#[CoversClass(Layout::class)]
#[CoversClass(Permission::class)]
#[CoversClass(SiteFieldData::class)]
#[UsesClass(Config::class)]
#[UsesClass(Language::class)]
#[UsesClass(ComponentConfig::class)]
#[UsesClass(FunctionRegistry::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(ContentMulti::class)]
#[UsesClass(ContentOne::class)]
#[UsesClass(BuilderLayout::class)]
#[UsesClass(MovedPublishingFields::class)]
#[UsesClass(NewPublishingFields::class)]
#[UsesClass(OrderZero::class)]
#[UsesClass(PermissionAction::class)]
#[UsesClass(PermissionComponent::class)]
#[UsesClass(PermissionCore::class)]
#[UsesClass(PermissionDashboard::class)]
#[UsesClass(PermissionGlobalAction::class)]
#[UsesClass(PermissionViews::class)]
#[UsesClass(SiteFieldDataBuilder::class)]
#[UsesClass(SiteFields::class)]
#[UsesClass(TabCounter::class)]
#[UsesClass(Counter::class)]
#[UsesClass(Indent::class)]
#[UsesClass(InArray::class)]
#[UsesClass(PathCount::class)]
final class CreatorStateTest extends CreatorTestCase
{
	/**
	 * Register decode, UIkit, and textarea metadata without duplicate decoders.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSiteFieldDataBuildsDecodeAndTextareaRegistries(): void
	{
		$config = $this->config(['uikit' => 2]);
		$siteFields = new SiteFields();
		$siteFields->set('article.body', [
			'catalog___body' => [
				'site' => 'site',
				'as' => 'item',
				'key' => 'body'
			]
		]);
		$builder = new SiteFieldDataBuilder();
		$subject = new SiteFieldData($config, $siteFields, $builder);

		$subject->set('article', 'body', 'json', 'editor');
		$subject->set('article', 'body', 'base64', 'editor');
		$subject->set('article', 'body', 'json', 'editor');

		$this->assertSame([
			'json',
			'base64'
		], $builder->get('decode.site.catalog.item.body.decode'));
		$this->assertSame('editor', $builder->get('decode.site.catalog.item.body.type'));
		$this->assertSame('article', $builder->get('decode.site.catalog.item.body.admin_view'));
		$this->assertCount(3, $builder->get('uikit.site.catalog.item.body'));
		$this->assertCount(3, $builder->get('textareas.site.catalog.item.body'));
	}

	/**
	 * Route regular, publishing, default, and colliding fields to their builders.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLayoutRoutesFieldsAndResolvesPositionCollisions(): void
	{
		$orderZero = new OrderZero();
		$tabCounter = new TabCounter();
		$layout = new BuilderLayout();
		$moved = new MovedPublishingFields();
		$newPublishing = new NewPublishingFields();
		$subject = new Layout(
			$this->config(['default_fields' => ['created']]),
			$orderZero,
			$tabCounter,
			$layout,
			$moved,
			$newPublishing
		);
		$first = ['order_edit' => 1, 'tab' => 2, 'alignment' => 1];
		$collision = ['order_edit' => 1, 'tab' => 2, 'alignment' => 1];
		$publishing = ['order_edit' => 2, 'tab' => 15, 'alignment' => 2];
		$details = ['order_edit' => 3, 'tab' => 1, 'alignment' => 1];

		$subject->set('article', 'Metadata', 'created', $first);
		$subject->set('article', 'Metadata', 'alias', $collision);
		$subject->set('article', 'Publishing', 'featured', $publishing);
		$subject->set('article', '', 'title', $details);

		$this->assertSame('created', $layout->get('article.Metadata.1.1'));
		$this->assertSame('alias', $layout->get('article.Metadata.1.2'));
		$this->assertSame('title', $layout->get('article.Details.1.3'));
		$this->assertSame('Metadata', $tabCounter->get('article.2'));
		$this->assertSame('Details', $tabCounter->get('article.1'));
		$this->assertTrue($moved->get('article.created'));
		$this->assertSame('featured', $newPublishing->get('article.2.2'));
	}

	/**
	 * Assign distinct negative orders to consecutive zero-order fields.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testLayoutAssignsDistinctOrdersToConsecutiveZeroFields(): void
	{
		$subject = new Layout(
			$this->config(['default_fields' => []]),
			new OrderZero(),
			new TabCounter(),
			new BuilderLayout(),
			new MovedPublishingFields(),
			new NewPublishingFields()
		);
		$first = ['order_edit' => 0, 'tab' => 1, 'alignment' => 1];
		$second = ['order_edit' => 0, 'tab' => 1, 'alignment' => 1];

		$subject->set('article', 'Details', 'first', $first);
		$subject->set('article', 'Details', 'second', $second);

		$this->assertSame(-999, $first['order_edit']);
		$this->assertSame(-998, $second['order_edit']);
	}

	/**
	 * Generate the stable no-op helper method required by every component.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHelperNoneBuildsExactFallbackMethod(): void
	{
		$subject = new Helper(
			$this->config(),
			$this->createStub(Structure::class),
			new ContentOne(),
			new ContentMulti()
		);

		$this->assertSame(
			PHP_EOL . PHP_EOL . "\t/**" . PHP_EOL
				. "\t *\tCan be used to build help urls." . PHP_EOL
				. "\t **/" . PHP_EOL
				. "\tpublic static function getHelpUrl(string \$view)" . PHP_EOL
				. "\t{" . PHP_EOL
				. "\t\treturn false;" . PHP_EOL
				. "\t}",
			$subject->none()
		);
	}

	/**
	 * Build both help structures and inject version-aware generated methods.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testHelperBuildsStructuresAndInjectsGeneratedMethods(): void
	{
		$buildCalls = [];
		$structure = $this->createStub(Structure::class);
		$structure->method('build')->willReturnCallback(
			static function (array $target, string $type) use (&$buildCalls): bool
			{
				$buildCalls[] = [$target, $type];

				return true;
			}
		);
		$contentOne = new ContentOne();
		$contentMulti = new ContentMulti();
		$subject = new Helper(
			$this->config([
				'joomla_version' => 5,
				'component_code_name' => 'demo'
			]),
			$structure,
			$contentOne,
			$contentMulti
		);

		$this->assertFalse($subject->set('article'));
		$this->assertTrue($subject->set('help_document'));
		$this->assertSame([
			[['admin' => 'help'], 'help'],
			[['site' => 'help'], 'help']
		], $buildCalls);
		$this->assertStringContainsString('getApplication()->getIdentity()', $contentOne->get('HELP'));
		$this->assertStringContainsString("a.admin_view = ", $contentOne->get('HELP'));
		$this->assertStringContainsString("a.site_view = ", $contentOne->get('HELP_SITE'));
		$this->assertSame('blabla', $contentMulti->get('help|BLABLA'));
	}

	/**
	 * Add language and component permission records for every custom button.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomButtonPermissionsBuildsLanguageAndAccessRecords(): void
	{
		$config = $this->config(['lang_prefix' => 'COM_DEMO']);
		$language = new Language($config);
		$permissions = new PermissionComponent();
		$counter = $this->createStub(Counter::class);
		$settings = (object) [
			'custom_buttons' => [
				['name' => 'Send Invoice'],
				['name' => 'Archive Now']
			]
		];

		(new CustomButtonPermissions(
			$config,
			$language,
			$permissions,
			$counter
		))->add($settings, 'Article', 'article');

		$this->assertCount(4, $language->getTarget('bothadmin'));
		$this->assertSame(
			'Article Send Invoice Button Access',
			$language->get('bothadmin', 'COM_DEMO_ARTICLE_SEND_INVOICE_BUTTON_ACCESS')
		);
		$this->assertSame(
			'Allows the users in this group to access the send invoice button.',
			$language->get('bothadmin', 'COM_DEMO_ARTICLE_SEND_INVOICE_BUTTON_ACCESS_DESC')
		);
		$this->assertSame([
			'name' => 'article.send_invoice',
			'title' => 'COM_DEMO_ARTICLE_SEND_INVOICE_BUTTON_ACCESS',
			'description' => 'COM_DEMO_ARTICLE_SEND_INVOICE_BUTTON_ACCESS_DESC'
		], $permissions->get('article_send_invoice_button_access'));
		$this->assertSame(2, $counter->accessSize);
	}

	/**
	 * Resolve view-local and global actions through the core action map.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPermissionResolvesMappedActionsAndExistence(): void
	{
		$core = new PermissionCore();
		$actions = new PermissionAction();
		$globals = new PermissionGlobalAction();
		$core->set('article|core.edit', 'article.edit');
		$actions->set('article.edit|article', 'article');
		$globals->set('article.edit|article', 'article');
		$subject = new Permission(
			$this->config(),
			$core,
			new PermissionViews(),
			$actions,
			new PermissionComponent(),
			$globals,
			new PermissionDashboard(),
			$this->createStub(Counter::class),
			new Language($this->config())
		);

		$this->assertSame('article.edit', $subject->getAction('article', 'core.edit'));
		$this->assertSame('article.edit', $subject->getGlobal('article', 'core.edit'));
		$this->assertTrue($subject->actionExist('article', 'core.edit'));
		$this->assertTrue($subject->globalExist('article', 'core.edit'));
		$this->assertSame('core.delete', $subject->getAction('article', 'core.delete'));
		$this->assertSame('core.delete', $subject->getGlobal('article', 'core.delete'));
		$this->assertFalse($subject->actionExist('article', 'core.delete'));
		$this->assertFalse($subject->globalExist('article', 'core.delete'));
	}

	/**
	 * Create an isolated compiler configuration with selected values.
	 *
	 * @param   array<string, mixed>  $values  Initial registry values.
	 *
	 * @return  Config
	 * @since   6.1.6
	 */
	private function config(array $values = []): Config
	{
		$config = new Config(new Input(), new JoomlaRegistry(), new JoomlaRegistry());

		foreach ($values as $key => $value)
		{
			$config->set($key, $value);
		}

		return $config;
	}
}
