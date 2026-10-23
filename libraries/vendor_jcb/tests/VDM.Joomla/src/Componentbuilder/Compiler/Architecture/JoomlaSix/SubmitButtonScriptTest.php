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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\JoomlaSix;


use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\CMS\WebAsset\WebAssetRegistry;
use Joomla\DI\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomView\SubmitButtonScript as SharedSubmitButtonScript;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\JoomlaSix\CustomView\SubmitButtonScript;
use VDM\Joomla\Componentbuilder\Compiler\Service\ArchitectureView;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * Custom-view handler registration preserves submit behavior and authored overrides.
 *
 * @since  6.2.0
 */
#[CoversClass(SubmitButtonScript::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class SubmitButtonScriptTest extends ArchitectureTestCase
{
	/**
	 * The established handler, unchanged across the asset registration migration.
	 *
	 * @var    string
	 * @since  6.2.0
	 */
	private const HANDLER = <<<'JS'
	Joomla.submitbutton = function(task) {
		if (task === 'demo.back') {
			parent.history.back();
			return false;
		} else {
			var form = document.getElementById('adminForm');
			form.task.value = task;
			form.submit();
		}
	}
JS;

	/**
	 * The generated fragment registers the existing global handler after core.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testNativeFragmentRegistersUnchangedHandlerOnceAfterCore(): void
	{
		$definition = $this->definition('Custom view markup');
		$subject = new SubmitButtonScript($this->config());
		$source = $subject->get($definition);
		$wa = new WebAssetManager(new WebAssetRegistry());
		$wa->registerScript('core', 'https://example.test/core.js');
		$view = (new ReflectionClass(HtmlView::class))->newInstanceWithoutConstructor();
		$view->setDocument(new HtmlDocument(['webAssetManager' => $wa]));
		$render = function (string $fragment): void
		{
			eval('?>' . $fragment);
		};

		ob_start();
		try
		{
			$render->call($view, $source);
			$render->call($view, $source);
			$this->assertSame('', trim(ob_get_contents()));
		}
		finally
		{
			ob_end_clean();
		}

		$assets = $wa->getAssets('script', true);
		$this->assertSame(['core', 'com_demo.demo.submit'], array_keys($assets));
		$this->assertSame(['core'], $assets['com_demo.demo.submit']->getDependencies());
		$this->assertSame(self::HANDLER, $assets['com_demo.demo.submit']->getOption('content'));
		$this->assertTrue($assets['com_demo.demo.submit']->getOption('inline'));
		$this->assertStringNotContainsString('<script', $source);
	}

	/**
	 * Registered services select native registration only for target Joomla 6.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testProviderRetainsExactOlderOutputAndSelectsNativeTarget(): void
	{
		$expected = PHP_EOL . '<script type="text/javascript">' . PHP_EOL
			. self::HANDLER . PHP_EOL . '</script>';

		foreach ([3, 4, 5, 6] as $major)
		{
			$this->config()->set('joomla_version', $major);
			$container = new Container();
			$container->share('Config', $this->config(), true);
			$provider = new ArchitectureView();
			$provider->register($container);
			$subject = $container->get('Architecture.CustomView.SubmitButtonScript');
			$this->assertSame($subject, $container->get(SharedSubmitButtonScript::class));
			$definition = $this->definition('Custom view markup');

			if ($major === 6)
			{
				$this->assertInstanceOf(SubmitButtonScript::class, $subject);
				$this->assertSame($subject, $container->get('Architecture.CustomView.J6.SubmitButtonScript'));
				$this->assertSame($subject, $container->get(SubmitButtonScript::class));
			}
			else
			{
				$this->assertSame(SharedSubmitButtonScript::class, $subject::class);
				$this->assertSame($expected, $subject->get($definition));
				$this->assertSame($subject, $container->get('Architecture.CustomView.Shared.SubmitButtonScript'));
			}

			$this->config()->set('joomla_version', $major === 6 ? 3 : 6);
			$this->assertSame($subject, $provider->getCustomViewSubmitButtonScript($container));
		}
	}

	/**
	 * Existing override detection and empty-view behavior retain user source verbatim.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAuthoredOverrideAndEmptyViewRemainUntouched(): void
	{
		foreach ([new SharedSubmitButtonScript(), new SubmitButtonScript($this->config())] as $subject)
		{
			foreach (['', '<script>Joomla.submitbutton = function(task) { customSubmit(task); }</script>'] as $body)
			{
				$definition = $this->definition($body);
				$before = serialize($definition);
				$this->assertSame('', $subject->get($definition));
				$this->assertSame($before, serialize($definition));
			}
		}
	}

	/**
	 * Build the minimal custom-view definition used by this renderer.
	 *
	 * @param   string  $body  The authored view markup.
	 *
	 * @return  array  The custom-view definition.
	 * @since   6.2.0
	 */
	private function definition(string $body): array
	{
		return ['settings' => (object) ['code' => 'demo', 'default' => $body]];
	}
}
