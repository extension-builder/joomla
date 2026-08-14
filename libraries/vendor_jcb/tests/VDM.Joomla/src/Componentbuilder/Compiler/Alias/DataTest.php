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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Alias;


use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Alias\Data;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Template and layout alias indexing contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(Data::class)]
final class DataTest extends CompilerDomainTestCase
{
	/**
	 * Alias indexing preserves original, safe, and alpha-only lookup variants.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testAliasIndexResolvesOriginalAndNormalizedVariants(): void
	{
		$subject = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$this->setCompilerProperty($subject, 'index', ['template' => []]);
		$index = new ReflectionMethod(Data::class, 'indexAlias');
		$resolve = new ReflectionMethod(Data::class, 'resolveAliasFromIndex');
		$allowed = new ReflectionMethod(Data::class, 'isTableAllowed');

		$index->invoke($subject, 'template', 'Hero-Card 2', 41);

		$this->assertSame(41, $resolve->invoke($subject, 'Hero-Card 2', 'template'));
		$this->assertSame(41, $resolve->invoke($subject, 'herocardtwo', 'template'));
		$this->assertNull($resolve->invoke($subject, 'missing', 'template'));
		$this->assertTrue($allowed->invoke($subject, 'template'));
		$this->assertTrue($allowed->invoke($subject, 'layout'));
		$this->assertFalse($allowed->invoke($subject, 'module'));

		$values = (new ReflectionProperty(Data::class, 'index'))->getValue($subject);
		$this->assertSame(41, $values['template']['Hero-Card 2']);
		$this->assertContains(41, $values['template']);
	}

	/**
	 * Target resolution expands synchronized language output to both applications.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testResolveTargetsHonorsBothAndSingleTargetConfiguration(): void
	{
		$subject = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$method = new ReflectionMethod(Data::class, 'resolveTargets');
		$config = $this->compilerConfig(['lang_target' => 'both', 'build_target' => 'admin']);
		$this->setCompilerProperty($subject, 'config', $config);

		$this->assertSame(['site', 'admin'], $method->invoke($subject));

		$config->lang_target = 'site';
		$config->build_target = 'site';
		$this->assertSame(['site'], $method->invoke($subject));
	}

	/**
	 * Disallowed source tables terminate without remote or database collaboration.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testDisallowedTableReturnsNullAfterBoundedRetry(): void
	{
		$subject = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$this->setCompilerProperty($subject, 'retry', ['module.hero' => true]);

		$this->assertNull($subject->get('hero', 'module', 'article'));
	}
}
