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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Templatelayout;


use PHPUnit\Framework\Attributes\CoversClass;
use VDM\Joomla\Componentbuilder\Compiler\Alias\Data as AliasData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\LayoutData;
use VDM\Joomla\Componentbuilder\Compiler\Builder\TemplateData;
use VDM\Joomla\Componentbuilder\Compiler\Templatelayout\Data;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Template and layout discovery orchestration contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Data::class)]
final class DataTest extends CompilerDomainTestCase
{
	/**
	 * Content discovery loads each alias once and mirrors layouts across both targets.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSetDiscoversCachesMirrorsAndCountsTemplateLayoutData(): void
	{
		$config = $this->compilerConfig([
			'build_target' => 'admin',
			'lang_target' => 'both',
		]);
		$layouts = new LayoutData();
		$templates = new TemplateData();
		$counter = $this->inertCompilerCollaborator(Counter::class);
		$alias = $this->createMock(AliasData::class);
		$alias->expects($this->exactly(2))
			->method('get')
			->willReturnCallback(
				function (string $name, string $table, string $view): array
				{
					$this->assertSame('article', $view);

					if ($table === 'template')
					{
						$this->assertSame('card', $name);

						return ['id' => 11, 'html' => '', 'php_view' => ''];
					}

					$this->assertSame('layout', $table);
					$this->assertSame('shared', $name);

					return ['id' => 12, 'html' => '', 'php_view' => ''];
				}
			);
		$subject = new Data($config, $layouts, $templates, $alias, $counter);
		$content = '$this->loadTemplate(\'card\'); LayoutHelper::render(\'shared\', []);';

		$this->assertTrue($subject->set($content, 'article'));
		$this->assertTrue($subject->set($content, 'article'));
		$this->assertSame(
			['id' => 11, 'html' => '', 'php_view' => ''],
			$templates->get('admin.article.card')
		);
		$this->assertSame(
			['id' => 12, 'html' => '', 'php_view' => ''],
			$layouts->get('admin.shared')
		);
		$this->assertSame($layouts->get('admin.shared'), $layouts->get('site.shared'));
		$this->assertSame(1, $counter->template);
		$this->assertSame(1, $counter->layout);
	}
}
