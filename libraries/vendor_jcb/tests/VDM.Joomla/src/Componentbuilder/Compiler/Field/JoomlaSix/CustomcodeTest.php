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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Field\JoomlaSix;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Field\Customcode as SharedCustomcode;
use VDM\Joomla\Componentbuilder\Compiler\Field\JoomlaSix\Customcode;
use VDM\Joomla\Componentbuilder\Compiler\Service\Field;
use VDM\Tests\Support\JoomlaTestCase;

/**
 * Field-only footer placement diagnostics cover component and module consumers.
 *
 * @since 6.2.0
 */
#[CoversClass(Customcode::class)]
#[CoversClass(SharedCustomcode::class)]
#[CoversClass(Field::class)]
final class CustomcodeTest extends JoomlaTestCase
{
	/**
	 * Field scripts warn independently of view-level scripts and retain global source.
	 *
	 * @param   int     $major   Compile target.
	 * @param   string  $single  Edit view or module collection key.
	 * @param   string  $list    List view or module collection key.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('targets')]
	public function testProviderWarnsOnlyForJoomlaSixFieldFooters(int $major, string $single, string $list): void
	{
		$scripts = [];
		$messages = [];
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($major === 6 ? $this->exactly(2) : $this->never())->method('enqueueMessage')
			->willReturnCallback(static function ($message, $type) use (&$messages): void
			{
				$messages[] = [$message, $type];
			});
		$this->setJoomlaApplication($app);
		$config = new Config(new Input([]), new Registry(), new Registry());
		$config->set('joomla_version', $major);
		$container = new Container();
		$container->set('Config', $config);
		$container->set('Customcode.Dispenser', $this->dispenser($scripts));
		$container->registerServiceProvider(new Field());
		$subject = $container->get('Field.Customcode');
		$this->assertSame($major === 6 ? Customcode::class : SharedCustomcode::class, $subject::class);
		$this->assertSame($subject, $container->get($major === 6 ? 'Field.J6.Customcode' : 'Field.Shared.Customcode'));
		$source = 'var fieldElement = document.querySelector("#field"); function fieldAction() { return fieldElement; }';
		$field = $this->field([
			'add_javascript_view_footer' => 1,
			'javascript_view_footer' => base64_encode($source),
			'add_javascript_views_footer' => 1,
			'javascript_views_footer' => base64_encode($source),
		]);
		$subject->update(42, $field, $single, $list);
		$subject->update(42, $field, $single, $list);
		$this->assertCount(2, $scripts, 'A repeated field/view visit must not duplicate collection or warnings.');
		$this->assertSame(['view_footer', 'views_footer'], array_column($scripts, 'placement'));
		$this->assertSame([$source, $source], array_column($scripts, 'source'));
		$this->assertSame(['javascript_view_footer', 'javascript_views_footer'], array_column(array_column($scripts, 'metadata'), 'field'));
		$this->assertSame($source, $field->javascript_view_footer);
		$this->assertSame($source, $field->javascript_views_footer);
		$this->assertTrue($field->javascript_view_footer_decoded);
		$this->assertTrue($field->javascript_views_footer_decoded);

		foreach ($messages as $index => [$message, $type])
		{
			$this->assertSame('warning', $type);
			$this->assertStringContainsString($index === 0 ? 'javascript_view_footer' : 'javascript_views_footer', $message);
			$this->assertStringContainsString('field 42 in ' . htmlspecialchars($index === 0 ? $single : $list, ENT_QUOTES, 'UTF-8'), $message);
			$this->assertStringContainsString('Web Asset Manager in the document head', $message);
			$this->assertStringContainsString('unchanged and in global scope', $message);
			$this->assertStringContainsString('Immediate DOM access must use DOMContentLoaded.', $message);
		}
	}

	/**
	 * Older target collection and Joomla six component/module paths.
	 *
	 * @return  array
	 * @since   6.2.0
	 */
	public static function targets(): array
	{
		return [
			'Joomla 3' => [3, 'article', 'articles'],
			'Joomla 4' => [4, 'article', 'articles'],
			'Joomla 5' => [5, 'article', 'articles'],
			'Joomla 6 component' => [6, 'article', 'articles'],
			'Joomla 6 module' => [6, 'module.article', 'module.articles'],
			'Joomla 6 escaped target' => [6, '<article>', 'articles&more'],
		];
	}

	/**
	 * Empty, disabled, absent-target and non-JavaScript fields remain silent.
	 *
	 * @param   array        $overrides  Field source properties.
	 * @param   string|null  $single     Single-view collection key.
	 * @param   string|null  $list       List-view collection key.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	#[DataProvider('silentFooters')]
	public function testOnlyEnabledNonemptyCollectedFooterSourcesWarn(array $overrides, ?string $single, ?string $list): void
	{
		$scripts = [];
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->never())->method('enqueueMessage');
		$field = $this->field($overrides);
		(new Customcode($this->dispenser($scripts), $app))->update(7, $field, $single, $list);
	}

	/**
	 * Both footer fields share all warning boundary conditions.
	 *
	 * @return  iterable
	 * @since   6.2.0
	 */
	public static function silentFooters(): iterable
	{
		yield 'absent' => [[], 'article', 'articles'];
		yield 'css only' => [['add_css_view' => 1, 'css_view' => base64_encode('.field { color: red; }')], 'article', 'articles'];

		foreach (['javascript_view_footer', 'javascript_views_footer'] as $source)
		{
			yield $source . ' disabled' => [['add_' . $source => 0, $source => base64_encode('window.example = true;')], 'article', 'articles'];
			yield $source . ' empty' => [['add_' . $source => 1, $source => ''], 'article', 'articles'];
			yield $source . ' whitespace' => [['add_' . $source => 1, $source => base64_encode(" \n\t")], 'article', 'articles'];
			yield $source . ' no target' => [['add_' . $source => 1, $source => base64_encode('window.example = true;')], null, null];
		}
	}

	/**
	 * Previously decoded field sources are collected and warned without a second decode.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function testAlreadyDecodedFieldSourceIsPreservedAcrossConsumers(): void
	{
		$scripts = [];
		$app = $this->createMock(CMSApplicationInterface::class);
		$app->expects($this->exactly(2))->method('enqueueMessage');
		$source = 'function sharedFieldAction() { return document.getElementById("field"); }';
		$field = $this->field([
			'add_javascript_view_footer' => 1,
			'javascript_view_footer' => $source,
			'javascript_view_footer_decoded' => true,
		]);
		$subject = new Customcode($this->dispenser($scripts), $app);
		$subject->update(8, $field, 'article');
		$subject->update(8, $field, 'module.article');
		$this->assertSame([$source, $source], array_column($scripts, 'source'));
		$this->assertSame([false, false], array_column($scripts, 'base64'));
	}

	/**
	 * Build a complete field definition with silent defaults.
	 *
	 * @param   array  $overrides  Requested source flags and code.
	 *
	 * @return  object
	 * @since   6.2.0
	 */
	private function field(array $overrides): object
	{
		return (object) ($overrides + [
			'add_javascript_view_footer' => 0, 'javascript_view_footer' => '',
			'add_javascript_views_footer' => 0, 'javascript_views_footer' => '',
			'add_css_view' => 0, 'css_view' => '', 'add_css_views' => 0, 'css_views' => '',
		]);
	}

	/**
	 * Retain the dispenser's by-reference decoding and metadata contract.
	 *
	 * @param   array  $scripts  Collected source and routing information.
	 *
	 * @return  Dispenser
	 * @since   6.2.0
	 */
	private function dispenser(array &$scripts): Dispenser
	{
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->hub = [];
		$dispenser->method('set')->willReturnCallback(
			static function (&$source, $placement, $target, $third, $metadata, $base64) use (&$scripts): bool
			{
				if ($base64)
				{
					$source = base64_decode((string) $source);
				}

				$scripts[] = compact('source', 'placement', 'target', 'metadata', 'base64');

				return trim((string) $source) !== '';
			}
		);

		return $dispenser;
	}
}
