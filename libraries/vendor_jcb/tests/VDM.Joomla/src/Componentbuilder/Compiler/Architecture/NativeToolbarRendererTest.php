<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    23rd October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Toolbar\Button\StandardButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
use Joomla\CMS\User\User;
use Joomla\DI\Container;
use Joomla\Registry\Registry as JoomlaRegistry;
use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomButtons;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\DynamicButtons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomForm;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OnlyFunctionButtons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DynamicButtons as ButtonDefinitions;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;


/**
 * Native toolbar behavior and earlier-target output isolation.
 *
 * @since  6.2.0
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class NativeToolbarRendererTest extends ArchitectureTestCase
{
	/**
	 * Every public target identity resolves and preserves its output contract.
	 *
	 * @param   int     $version  Compile target.
	 * @param   string  $word     Target namespace suffix.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testTargetSelectionAndRuntimeButtons(int $version, string $word): void
	{
		$this->config()->set('joomla_version', $version);
		$content = new ContentOne();
		$content->set('COMPONENT', 'DEMO');
		$definitions = new ButtonDefinitions();
		$definitions->set('articles', [[
			'NAME' => 'EXPORT_REPORT', 'name' => 'Export Report',
			'link' => 'export_report', 'icon' => 'download.png',
		]]);
		$container = new Container();
		foreach ([
			'Config' => $this->config(),
			'Compiler.Builder.Content.One' => $content,
			'Compiler.Builder.Content.Multi' => new ContentMulti(),
			'Compiler.Builder.Custom.Form' => new CustomForm(),
			'Compiler.Builder.Only.Function.Buttons' => new OnlyFunctionButtons(),
			'Compiler.Builder.Dynamic.Buttons' => $definitions,
			'Utilities.Structure' => $this->createStub(Structure::class),
			'Language' => $this->language(), 'Placeholder' => $this->placeholder(),
			'Registry' => new Registry(),
		] as $key => $value)
		{
			$container->share($key, $value, true);
		}
		(new ArchitectureView())->register($container);
		$custom = $container->get('Architecture.CustomButtons');
		$dynamic = $container->get('Architecture.DynamicButtons');
		$namespace = 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\Joomla' . $word . '\\';
		$this->assertInstanceOf($namespace . 'CustomButtons', $custom);
		$this->assertInstanceOf($namespace . 'DynamicButtons', $dynamic);
		$this->assertSame($custom, $container->get($namespace . 'CustomButtons'));
		$view = ['settings' => (object) [
			'name_single_code' => 'article', 'name_list_code' => 'articles',
			'add_custom_button' => 1, 'custom_buttons' => [[
				'name' => 'Publish Article', 'target' => 3,
				'method' => 'publishArticle', 'icomoon' => 'checkmark',
			]], 'php_controller' => '', 'php_model' => '', 'default' => '<form></form>',
		]];
		$single = $custom->get($view, 2);
		$list = $custom->get($view, 3);
		$linked = $dynamic->get('articles');

		if ($version < 6)
		{
			$legacyCustom = $this->renderer(CustomButtons::class);
			$legacyDynamic = new DynamicButtons($this->config(), $definitions, $this->language());
			$this->assertSame($legacyCustom->get($view, 2), $single);
			$this->assertSame($legacyCustom->get($view, 3), $list);
			$this->assertSame($legacyDynamic->get('articles'), $linked);
			$this->assertSame($legacyCustom->javascript(), $custom->javascript());

			return;
		}

		$this->assertStringNotContainsString('::custom(', $single . $list . $linked);
		$this->assertStringContainsString("['core']", $custom->javascript());
		$buttons = $this->execute($single . $list . $linked, true);
		$this->assertCount(3, $buttons, $single . $list . $linked);
		$this->assertSame('article.publishArticle', $buttons[0]->getTask());
		$this->assertSame('articles.publishArticle', $buttons[1]->getTask());
		$this->assertSame('articles.redirectToExport_report', $buttons[2]->getTask());
		$this->assertFalse($buttons[0]->getListCheck());
		$this->assertTrue($buttons[1]->getListCheck());
		$this->assertTrue($buttons[2]->getListCheck());
		$this->assertSame('checkmark custom-button-publisharticle', $buttons[0]->getName());
		$this->assertSame('download', $buttons[2]->getName());
		$this->assertSame('COM_DEMO_PUBLISH_ARTICLE', $buttons[0]->getText());
		$this->assertSame([], $this->execute($single . $list . $linked, false));
	}

	/**
	 * Earlier-target handlers retain their task branches after PHP serialization.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testLegacySubmitHandlersUseConfiguredIndentation(): void
	{
		$subject = $this->renderer(CustomButtons::class);

		foreach (["\t", '  ', '    '] as $indent)
		{
			(new \ReflectionProperty(\VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent::class, 'indent'))->setValue(null, $indent);
			(new \ReflectionProperty(\VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent::class, 'bucket'))->setValue(null, []);

			foreach ([3, 4, 5] as $version)
			{
				$this->config()->set('joomla_version', $version);
				$source = $subject->javascript();
				$start = strpos($source, '$jcbToolbarScript = ');
				$end = strpos($source, '$this->getDocument()', $start);
				$script = eval(substr($source, $start, $end - $start) . 'return $jcbToolbarScript;');
				$this->assertSame(
					"Joomla.submitbutton=function(task){if(task==''){returnfalse;}else{Joomla.submitform(task);returntrue;}};",
					preg_replace('/\s+/', '', $script)
				);
				$this->assertStringContainsString(PHP_EOL . str_repeat($indent, 2) . 'Joomla.submitform(task);', $script);
				$this->assertStringContainsString(
					$version === 3 ? 'addScriptDeclaration($jcbToolbarScript);' : 'getWebAssetManager()->addInlineScript($jcbToolbarScript);',
					$source
				);

				if ($indent !== "\t")
				{
					$this->assertStringNotContainsString("\t", $source . $script);
				}
			}
		}
	}

	/**
	 * Execute generated statements on Joomla's real toolbar objects.
	 *
	 * @param   string  $code     Generated statements.
	 * @param   bool    $allowed  Permission result.
	 *
	 * @return  array<StandardButton>
	 * @since   6.2.0
	 */
	private function execute(string $code, bool $allowed): array
	{
		$factory = $this->createStub(ToolbarFactoryInterface::class);
		$factory->method('createButton')->willReturnCallback(
			static fn (Toolbar $toolbar, string $type) => new StandardButton()
		);
		$toolbar = new Toolbar('toolbar', $factory);
		$document = $this->createStub(HtmlDocument::class);
		$document->method('getToolbar')->willReturn($toolbar);
		$user = $this->createStub(User::class);
		$user->method('authorise')->willReturn($allowed);
		$view = new class
		{
			/**
			 * Document owning the toolbar.
			 *
			 * @var    HtmlDocument
			 * @since  6.2.0
			 */
			public HtmlDocument $document;

			/**
			 * Permission results used by generated checks.
			 *
			 * @var    JoomlaRegistry
			 * @since  6.2.0
			 */
			public JoomlaRegistry $canDo;

			/**
			 * Current user for list checks.
			 *
			 * @var    User
			 * @since  6.2.0
			 */
			public User $user;


			/**
			 * Return the view document.
			 *
			 * @return  HtmlDocument
			 * @since   6.2.0
			 */
			public function getDocument(): HtmlDocument
			{
				return $this->document;
			}
		};
		$view->document = $document;
		$view->user = $user;
		$view->canDo = new JoomlaRegistry([
			'article' => ['publish_article' => $allowed], 'export_report' => ['access' => $allowed],
		]);
		(function () use ($code): void
		{
			eval($code);
		})->call($view);

		return $toolbar->getItems();
	}

	/**
	 * Supported compile targets, independent of the installed Joomla host.
	 *
	 * @return  iterable<string, array{int, string}>
	 * @since   6.2.0
	 */
	public static function targets(): iterable
	{
		foreach ([3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six'] as $version => $word)
		{
			yield 'Joomla ' . $version => [$version, $word];
		}
	}
}
