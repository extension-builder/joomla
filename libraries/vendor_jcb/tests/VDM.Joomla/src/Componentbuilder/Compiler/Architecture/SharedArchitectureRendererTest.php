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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListItem;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListItem\ItemCode;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListItem\Link;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListItem\LinkAuthority;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListItem\LinkLogic;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ListItemBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ToolbarComposer;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Component\ImageType;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomButtons;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\DynamicButtons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\Category;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CategoryCode;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomForm;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DoNotEscape;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DynamicButtons as DynamicButtonsBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\FieldRelations;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ListJoin;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OnlyFunctionButtons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\SelectionTranslation;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Paths;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Componentbuilder\File\Image;


/**
 * Generated-output and collaborator contracts for shared architecture services.
 *
 * @since  6.1.6
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class SharedArchitectureRendererTest extends ArchitectureTestCase
{
	/**
	 * Protect exact toolbar placeholder replacement without incidental wrapping.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testToolbarComposerReplacesExplicitSectionsByteForByte(): void
	{
		$subject = new ToolbarComposer();
		$override = 'before'
			. Placefix::_('DYNAMIC_BUTTONS')
			. 'middle'
			. Placefix::_('CUSTOM_BUTTONS')
			. 'after'
			. Placefix::_('FUNCTION_BUTTONS');

		$this->assertSame(
			'beforeDYNAMICmiddleCUSTOMafterFUNCTION',
			$subject->build($override, 'DYNAMIC', 'CUSTOM', 'FUNCTION')
		);
	}

	/**
	 * Protect auto-appended section order, indentation, and terminal newlines.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testToolbarComposerWrapsMissingItemButtonsBeforeFunctions(): void
	{
		$subject = new ToolbarComposer();
		$dynamic = "\t\t\tDYNAMIC;";
		$custom = PHP_EOL . "\t\t\tCUSTOM;";
		$function = "\t\tFUNCTION;";
		$expected = 'OVERRIDE'
			. PHP_EOL . "\t\t// Only load dynamic+custom if there are items"
			. PHP_EOL . "\t\tif (!\$this->isEmptyState)"
			. PHP_EOL . "\t\t{"
			. PHP_EOL . $dynamic . $custom
			. PHP_EOL . "\t\t}" . PHP_EOL
			. PHP_EOL . $function;

		$this->assertSame(
			$expected,
			$subject->build('OVERRIDE', $dynamic, $custom, $function)
		);
	}

	/**
	 * Protect button validation, exact generated lines, and translation side effect.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDynamicButtonsRenderOnlyCompleteDefinitions(): void
	{
		$builder = new DynamicButtonsBuilder();
		$builder->set('articles', [
			[
				'NAME' => 'EXPORT_REPORT',
				'name' => 'Export Report',
				'link' => 'export_report',
				'icon' => 'download',
			],
			['NAME' => 'INCOMPLETE'],
		]);
		$subject = new DynamicButtons($this->config(), $builder, $this->language());
		$expected = "\t\tif (\$this->canDo->get('export_report.access'))"
			. PHP_EOL . "\t\t{"
			. PHP_EOL . "\t\t\t//  add export report button."
			. PHP_EOL . "\t\t\tJoomla___0c1a176a_304f_433a_8233_37d01ff87815___Power::custom('articles.redirectToExport_report', 'download', '', 'COM_DEMO_EXPORT_REPORT', true);"
			. PHP_EOL . "\t\t}";

		$this->assertSame($expected, $subject->get('articles'));
		$this->assertSame('', $subject->get('missing'));
		$this->assertSame(
			'Export report',
			$this->language()->get('admin', 'COM_DEMO_EXPORT_REPORT')
		);
	}

	/**
	 * Protect image detection, canonical copy naming, and fallback persistence.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testImageTypeCopiesDetectedImageAndFallsBackToJpeg(): void
	{
		$componentPath = $this->createTemporaryDirectory('component');
		$this->createTemporaryDirectory('component/admin/assets/images');
		$paths = new class($componentPath) extends Paths
		{
			/**
			 * Component output root exposed by this test boundary.
			 *
			 * @var    string
			 * @since  6.1.6
			 */
			private string $componentPath;

			/**
			 * Create a path boundary with one deterministic compiler path.
			 *
			 * @param   string  $componentPath  Component output root.
			 *
			 * @since   6.1.6
			 */
			public function __construct(string $componentPath)
			{
				$this->componentPath = $componentPath;
			}

			/**
			 * Resolve the path consumed by ImageType.
			 *
			 * @param   string  $key  Compiler path key.
			 *
			 * @return  mixed
			 * @since   6.1.6
			 */
			public function __get($key)
			{
				if ($key === 'component_path')
				{
					return $this->componentPath;
				}

				return parent::__get($key);
			}
		};
		$subject = new ImageType($paths, new Image());

		$this->assertSame('png', $subject->set('images/banners/white.png'));
		$this->assertFileEquals(
			JPATH_SITE . '/images/banners/white.png',
			$componentPath . '/admin/assets/images/vdm-component.png'
		);
		$this->assertSame('jpg', $subject->set('images/does-not-exist.png'));
		$this->assertSame('jpg', $subject->get());
	}

	/**
	 * Protect field-type precedence, escape policy, and Joomla user APIs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testItemCodeSelectsTheCorrectRenderingExpression(): void
	{
		$selection = new SelectionTranslation();
		$selection->set('articles.state', true);
		$doNotEscape = new DoNotEscape();
		$doNotEscape->set('articles.body', true);
		$subject = new ItemCode($this->config(), $selection, $doNotEscape);

		$category = $this->item('catid', 'category', false);
		$this->assertSame(
			'$displayData->sanitize($item->category_title)',
			$subject->get($category, 'articles', false, '$displayData->')
		);

		$selectionItem = $this->item('state');
		$this->assertSame(
			'Joomla___ba6326ef_cb79_4348_80f4_ab086082e3c5___Power::_($item->state)',
			$subject->get($selectionItem, 'articles', false)
		);

		// a field flagged not to be escaped keeps its HTML, encoded rather
		// than stripped, and is never emitted raw
		$keepsHtml = $this->item('body');
		$this->assertSame(
			'$this->escape($item->body)',
			$subject->get($keepsHtml, 'articles', true)
		);
		$this->assertSame(
			'$displayData->escape($item->body)',
			$subject->get($keepsHtml, 'articles', true, '$displayData->')
		);

		// every other field is reduced to plain text
		$sanitised = $this->item('title');
		$this->assertSame('$this->sanitize($item->title)', $subject->get($sanitised, 'articles', true));

		$user = $this->item('created_by', 'user', false);
		$this->assertStringContainsString('loadUserById((int) ($item->created_by ?? 0))->name', $subject->get($user, 'articles', false));
		$this->config()->set('joomla_version', 3);
		$this->assertSame(
			'Joomla___39403062_84fb_46e0_bac4_0023f766e827___Power::getUser((int)$item->created_by)->name',
			$subject->get($user, 'articles', false)
		);
	}

	/**
	 * Protect link routing and the by-reference checkout trigger contract.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLinkRoutesSpecialTypesWithoutEnablingCheckout(): void
	{
		$category = new Category();
		$category->set('articles.extension', 'com_demo');
		$subject = new Link($category);
		$checkout = false;

		$this->assertSame(
			'index.php?option=com_categories&task=category.edit&id=<?php echo (int)$item->catid; ?>&extension=com_demo',
			$subject->get($this->item('catid', 'category', false), $checkout, 'article', 'articles')
		);
		$this->assertFalse($checkout);

		$this->assertSame(
			'index.php?option=com_users&task=user.edit&id=<?php echo (int) $item->created_by ?>',
			$subject->get($this->item('created_by', 'user', false), $checkout, 'article', 'articles')
		);
		$this->assertFalse($checkout);
	}

	/**
	 * Protect default link referral handling and checkout activation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLinkActivatesCheckoutOnlyForTheDefaultItemRoute(): void
	{
		$subject = new Link(new Category());
		$checkout = false;

		$this->assertSame(
			'<?php echo $edit; ?>&id=<?php echo $item->id; ?>&ref=dashboard',
			$subject->get($this->item('title'), $checkout, 'article', 'articles', '&ref=dashboard')
		);
		$this->assertTrue($checkout);
	}

	/**
	 * Protect authority syntax and the Joomla 4+ modal guard.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLinkAuthorityPreservesTargetSpecificModalGuard(): void
	{
		$categoryCode = new CategoryCode();
		$categoryCode->set('article.view', 'articles');
		$subject = new LinkAuthority($this->config(), $categoryCode, $this->permission());
		$item = $this->item('catid', 'category', false);

		$this->assertSame(
			"!\$displayData->isModal && \$actor->authorise('core.edit', 'com_demo.articles.category.' . (int) (\$item->catid ?? 0))",
			$subject->get($item, 'article', 'articles', '$displayData->', '$actor')
		);

		$this->config()->set('joomla_version', 3);
		$this->assertSame(
			"\$actor->authorise('core.edit', 'com_demo.articles.category.' . (int) (\$item->catid ?? 0))",
			$subject->get($item, 'article', 'articles', '$displayData->', '$actor')
		);
	}

	/**
	 * Protect checkout markup and the modern modal-selection fallback.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testLinkLogicAddsCheckoutAndModernModalSelectionContracts(): void
	{
		$subject = new LinkLogic($this->config());
		$code = $subject->get(
			$this->item('title'),
			'$this->sanitize($item->title)',
			'<?php echo $edit; ?>&id=<?php echo $item->id; ?>',
			"\$canDo->get('article.edit')",
			'article',
			'articles',
			'$this->',
			true
		);

		$this->assertStringStartsWith(PHP_EOL . "\t\t\t<div class=\"name\">", $code);
		$this->assertStringContainsString("Power::_('jgrid.checkedout'", $code);
		$this->assertStringContainsString('<?php if (!$this->isModal): ?>', $code);
		$this->assertStringContainsString('data-content-type="com_demo.article"', $code);
		$this->assertStringContainsString('$this->getModalTitleKey()', $code);
		$this->assertStringEndsWith(PHP_EOL . "\t\t\t</div>", $code);

		$this->config()->set('joomla_version', 3);
		$legacy = $subject->get(
			$this->item('title'),
			'$this->sanitize($item->title)',
			'/edit',
			'$allowed',
			'article',
			'articles',
			'$this->',
			true,
			false
		);
		$this->assertStringNotContainsString('data-content-select', $legacy);
		$this->assertStringContainsString("<?php echo \$this->sanitize(\$item->title); ?>", $legacy);
	}

	/**
	 * Protect the integrated plain and linked list-item contracts.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testListItemCoordinatesRenderingAndRowClassByLinkPolicy(): void
	{
		$subject = $this->listItem();
		$itemClass = 'initial';
		$plain = $this->item('title');

		$this->assertSame(
			PHP_EOL . "\t\t\t<?php echo \$this->sanitize(\$item->title); ?>",
			$subject->get($plain, 'article', 'articles', $itemClass, false)
		);
		$this->assertSame('initial', $itemClass);

		$linked = $plain;
		$linked['link'] = true;
		$output = $subject->get($linked, 'article', 'articles', $itemClass, false);
		$this->assertSame('nowrap', $itemClass);
		$this->assertStringContainsString('<?php echo $edit; ?>&id=<?php echo $item->id; ?>', $output);
		$this->assertStringContainsString("\$canDo->get('core.edit')", $output);
		$this->assertStringContainsString("Power::_('jgrid.checkedout'", $output);
	}

	/**
	 * Protect relation placeholder substitution and the standard wrapper.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testListItemBuilderResolvesIdGuidCodeAndCompilerPlaceholders(): void
	{
		$this->placeholder()->remove('NamespacePrefix');
		$this->placeholder()->set('NamespacePrefix', 'Acme');
		$fieldRelations = new FieldRelations();
		$fieldRelations->set('articles.field-guid.2', [
			'join_type' => 2,
			'set' => '<strong>[field=7]</strong>|$item->{field-guid}|[[[NamespacePrefix]]]',
		]);
		$subject = new ListItemBuilder(
			$this->placeholder(),
			$this->listItem(),
			$fieldRelations,
			new ListJoin()
		);
		$item = $this->item('title');
		$item['id'] = 7;
		$item['guid'] = 'field-guid';
		$itemClass = '';
		$output = $subject->get($item, 'article', 'articles', $itemClass, false);

		$this->assertSame(
			PHP_EOL . "\t\t\t<div><strong>"
			. PHP_EOL . "\t\t\t<?php echo \$this->sanitize(\$item->title); ?>"
			. '</strong>|$item->title|Acme'
			. PHP_EOL . "\t\t\t</div>",
			$output
		);
	}

	/**
	 * Protect custom-button output, focused builder side effects, and JS APIs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testCustomButtonsRenderActionsAndPersistRequiredScaffolding(): void
	{
		[$subject, $contentMulti, $customForm] = $this->customButtons();
		$view = [
			'settings' => (object) [
				'name_single_code' => 'article',
				'name_list_code' => 'articles',
				'add_custom_button' => 1,
				'custom_buttons' => [[
					'name' => 'Publish Article',
					'target' => 1,
					'method' => 'publishArticle',
					'icomoon' => 'checkmark',
				]],
				'php_controller' => '',
				'php_model' => '',
				'default' => '<section>No form yet</section>',
			],
		];
		$output = $subject->get($view, 2);

		$this->assertStringContainsString("\$this->canDo->get('article.publish_article')", $output);
		$this->assertStringContainsString("custom('article.publishArticle', 'checkmark custom-button-publisharticle'", $output);
		$this->assertSame('Publish Article', $this->language()->get('admin', 'COM_DEMO_PUBLISH_ARTICLE'));
		$this->assertTrue($contentMulti->exists('article|ADMIN_CUSTOM_BUTTONS_CONTROLLER'));
		$this->assertStringContainsString('getWebAssetManager()->addInlineScript', $contentMulti->get('article|ADMIN_JAVASCRIPT_FOR_BUTTONS'));
		$this->assertTrue($customForm->get('admin.article'));

		$this->config()->set('joomla_version', 3);
		$this->assertStringContainsString('addScriptDeclaration', $subject->javascript());
		$this->assertStringNotContainsString('getWebAssetManager', $subject->javascript());
	}

	/**
	 * Create one complete item fixture.
	 *
	 * @param   string  $code   Field code.
	 * @param   string  $type   Field type.
	 * @param   bool    $title  Whether this is the target title.
	 *
	 * @return  array<string, mixed>
	 * @since   6.1.6
	 */
	private function item(string $code, string $type = 'text', bool $title = true): array
	{
		return [
			'code' => $code,
			'type' => $type,
			'title' => $title,
			'multiple' => false,
			'link' => false,
			'guid' => 'fixture-guid',
		];
	}

	/**
	 * Create the integrated list-item coordinator.
	 *
	 * @return  ListItem
	 * @since   6.1.6
	 */
	private function listItem(): ListItem
	{
		return new ListItem(
			new ItemCode($this->config(), new SelectionTranslation(), new DoNotEscape()),
			new Link(new Category()),
			new LinkAuthority($this->config(), new CategoryCode(), $this->permission()),
			new LinkLogic($this->config())
		);
	}

	/**
	 * Create custom buttons with observable focused registries.
	 *
	 * @return  array{CustomButtons,ContentMulti,CustomForm}
	 * @since   6.1.6
	 */
	private function customButtons(): array
	{
		$contentOne = new ContentOne();
		$contentOne->set('COMPONENT', 'DEMO');
		$contentMulti = new ContentMulti();
		$customForm = new CustomForm();
		$subject = new CustomButtons(
			$this->config(),
			$contentOne,
			$contentMulti,
			$customForm,
			new OnlyFunctionButtons(),
			$this->createStub(Structure::class),
			$this->language(),
			$this->placeholder(),
			new Registry()
		);

		return [$subject, $contentMulti, $customForm];
	}
}
