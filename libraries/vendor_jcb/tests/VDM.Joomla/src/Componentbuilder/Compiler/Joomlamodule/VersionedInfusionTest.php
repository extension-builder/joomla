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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Joomlamodule;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Module\DispatcherInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HeaderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\ModuleDataInterface;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaFive\Infusion as JoomlaFiveInfusion;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaFour\Infusion as JoomlaFourInfusion;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaSix\Infusion as JoomlaSixInfusion;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaThree\Infusion as JoomlaThreeInfusion;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Joomla 3-6 module infusion guards, target state, and dispatcher output contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeInfusion::class)]
#[CoversClass(JoomlaFourInfusion::class)]
#[CoversClass(JoomlaFiveInfusion::class)]
#[CoversClass(JoomlaSixInfusion::class)]
#[UsesClass(Config::class)]
#[UsesClass(ContentMulti::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(Placefix::class)]
final class VersionedInfusionTest extends CompilerDomainTestCase
{
	/**
	 * Stop before reading module data when the store is empty.
	 *
	 * @param   class-string  $class  Infusion class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testSetShortCircuitsWhenDataStoreIsEmpty(string $class): void
	{
		$data = $this->createMock(ModuleDataInterface::class);
		$data->expects($this->once())->method('exists')->willReturn(false);
		$data->expects($this->never())->method('get');
		$subject = $this->subject($class);
		$this->setProperty($subject, 'data', $data);

		$subject->set();
		$this->addToAssertionCount(1);
	}

	/**
	 * Ignore malformed entries without entering the compiler collaborator graph.
	 *
	 * @param   class-string  $class  Infusion class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testSetSkipsNonObjectModuleEntries(string $class): void
	{
		$data = $this->createMock(ModuleDataInterface::class);
		$data->expects($this->once())->method('exists')->willReturn(true);
		$data->expects($this->once())->method('get')->willReturn([null, 'invalid', []]);
		$subject = $this->subject($class);
		$this->setProperty($subject, 'data', $data);

		$subject->set();
		$this->addToAssertionCount(1);
	}

	/**
	 * Move compiler build and language state into each module's isolated target.
	 *
	 * @param   class-string  $class  Infusion class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModuleConfigurationSelectsBuildAndLanguageTarget(string $class): void
	{
		$config = $this->compilerConfig();
		$subject = $this->subject($class);
		$this->setProperty($subject, 'config', $config);
		$module = (object) ['key' => '17_M0dUl3', 'lang_prefix' => 'MOD_EXAMPLE'];

		$this->invoke($subject, 'setModuleConfiguration', $module);

		$this->assertSame('17_M0dUl3', $config->build_target);
		$this->assertSame('17_M0dUl3', $config->lang_target);
		$this->assertSame('MOD_EXAMPLE', $config->lang_prefix);
	}

	/**
	 * Separate dispatcher header and class placeholders for Joomla 4-6 modules.
	 *
	 * @param   class-string  $class  Modern infusion class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('modernVersions')]
	public function testModernDispatcherWritesHeaderAndClassPlaceholders(string $class): void
	{
		$module = (object) ['key' => '17_M0dUl3', 'class_name' => 'Example'];
		$header = $this->createMock(HeaderInterface::class);
		$header->expects($this->once())->method('get')
			->with('module.dispatcher.header', 'Example')->willReturn('use Joomla\\CMS\\Factory;');
		$dispatcher = $this->createMock(DispatcherInterface::class);
		$dispatcher->expects($this->once())->method('get')->with($module)->willReturn('final class Dispatcher {}');
		$content = new ContentMulti();
		$subject = $this->subject($class);
		$this->setProperty($subject, 'header', $header);
		$this->setProperty($subject, 'dispatcher', $dispatcher);
		$this->setProperty($subject, 'contentmulti', $content);

		$this->invoke($subject, 'setDispatcherCode', $module);

		$this->assertSame(
			PHP_EOL . PHP_EOL . 'use Joomla\\CMS\\Factory;',
			$content->get('17_M0dUl3|DISPATCHER_CLASS_HEADER')
		);
		$this->assertSame(
			'final class Dispatcher {}',
			$content->get('17_M0dUl3|DISPATCHER_CLASS')
		);
	}

	/**
	 * Render Joomla 3 dispatcher code without reading an uninitialized local header.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testJoomlaThreeDispatcherDoesNotReadUndefinedHeaderVariable(): void
	{
		$module = (object) ['key' => '17_M0dUl3'];
		$dispatcher = $this->createMock(DispatcherInterface::class);
		$dispatcher->expects($this->once())->method('get')->with($module)->willReturn('dispatcher-code');
		$content = new ContentMulti();
		$subject = $this->subject(JoomlaThreeInfusion::class);
		$this->setProperty($subject, 'dispatcher', $dispatcher);
		$this->setProperty($subject, 'contentmulti', $content);

		$this->invoke($subject, 'setDispatcherCode', $module);

		$this->assertSame('dispatcher-code', $content->get('17_M0dUl3|MODCODE'));
	}

	/**
	 * Provide every supported Joomla module infusion implementation.
	 *
	 * @return  array<string, array{class-string}>
	 * @since   6.1.6
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => [JoomlaThreeInfusion::class],
			'Joomla 4' => [JoomlaFourInfusion::class],
			'Joomla 5' => [JoomlaFiveInfusion::class],
			'Joomla 6' => [JoomlaSixInfusion::class]
		];
	}

	/**
	 * Provide namespaced Joomla module infusion implementations.
	 *
	 * @return  array<string, array{class-string}>
	 * @since   6.1.6
	 */
	public static function modernVersions(): array
	{
		return [
			'Joomla 4' => [JoomlaFourInfusion::class],
			'Joomla 5' => [JoomlaFiveInfusion::class],
			'Joomla 6' => [JoomlaSixInfusion::class]
		];
	}

	/**
	 * Create a versioned infusion without constructing unrelated collaborators.
	 *
	 * @param   class-string  $class  Infusion class.
	 *
	 * @return  object
	 * @since   6.1.6
	 */
	private function subject(string $class): object
	{
		return (new ReflectionClass($class))->newInstanceWithoutConstructor();
	}

	/**
	 * Invoke one reviewed protected infusion operation.
	 *
	 * @param   object  $subject  Infusion instance.
	 * @param   string  $method   Method name.
	 * @param   object  $module   Module value.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function invoke(object $subject, string $method, object &$module): mixed
	{
		return (new ReflectionMethod($subject, $method))->invokeArgs($subject, [&$module]);
	}

	/**
	 * Replace one non-public collaborator.
	 *
	 * @param   object  $subject   Subject instance.
	 * @param   string  $property  Property name.
	 * @param   mixed   $value     Property value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function setProperty(object $subject, string $property, mixed $value): void
	{
		(new ReflectionProperty($subject, $property))->setValue($subject, $value);
	}
}
