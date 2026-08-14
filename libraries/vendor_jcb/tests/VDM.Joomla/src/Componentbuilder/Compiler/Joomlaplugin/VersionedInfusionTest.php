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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Joomlaplugin;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use VDM\Joomla\Abstraction\ActiveRegistry;
use VDM\Joomla\Abstraction\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\Architecture\Plugin\ProviderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\HeaderInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PluginDataInterface;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaFive\Infusion as JoomlaFiveInfusion;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaFour\Infusion as JoomlaFourInfusion;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaSix\Infusion as JoomlaSixInfusion;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaThree\Infusion as JoomlaThreeInfusion;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Joomla 3-6 plugin infusion guards, placeholder state, and content contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeInfusion::class)]
#[CoversClass(JoomlaFourInfusion::class)]
#[CoversClass(JoomlaFiveInfusion::class)]
#[CoversClass(JoomlaSixInfusion::class)]
#[UsesClass(Config::class)]
#[UsesClass(Placeholder::class)]
#[UsesClass(ContentMulti::class)]
#[UsesClass(ContentOne::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ActiveRegistry::class)]
#[UsesClass(Placefix::class)]
final class VersionedInfusionTest extends CompilerDomainTestCase
{
	/**
	 * Stop before reading plugin data when the store is empty.
	 *
	 * @param   class-string  $class     Infusion class.
	 * @param   bool          $modern    Unused provider field.
	 * @param   string        $expected  Unused provider field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testSetShortCircuitsWhenDataStoreIsEmpty(
		string $class,
		bool $modern,
		string $expected
	): void
	{
		$data = $this->createMock(PluginDataInterface::class);
		$data->expects($this->once())->method('exists')->willReturn(false);
		$data->expects($this->never())->method('get');
		$subject = $this->subject($class);
		$this->setProperty($subject, 'data', $data);

		$subject->set();
		$this->addToAssertionCount(1);
	}

	/**
	 * Ignore malformed plugin entries without calling compiler collaborators.
	 *
	 * @param   class-string  $class     Infusion class.
	 * @param   bool          $modern    Unused provider field.
	 * @param   string        $expected  Unused provider field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testSetSkipsNonObjectPluginEntries(
		string $class,
		bool $modern,
		string $expected
	): void
	{
		$data = $this->createMock(PluginDataInterface::class);
		$data->expects($this->once())->method('exists')->willReturn(true);
		$data->expects($this->once())->method('get')->willReturn([null, 'invalid', []]);
		$subject = $this->subject($class);
		$this->setProperty($subject, 'data', $data);

		$subject->set();
		$this->addToAssertionCount(1);
	}

	/**
	 * Set plugin namespace placeholders and isolate compiler target state.
	 *
	 * @param   class-string  $class     Infusion class.
	 * @param   bool          $modern    Unused provider field.
	 * @param   string        $expected  Unused provider field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testSetPlaceholdersSelectsPluginTarget(
		string $class,
		bool $modern,
		string $expected
	): void
	{
		$config = $this->compilerConfig();
		$placeholder = new Placeholder($config);
		$subject = $this->subject($class);
		$this->setProperty($subject, 'config', $config);
		$this->setProperty($subject, 'placeholder', $placeholder);
		$plugin = (object) [
			'group_namespace' => 'System',
			'namespace' => 'Example',
			'key' => '9_pLuG!n',
			'lang_prefix' => 'PLG_SYSTEM_EXAMPLE'
		];

		$this->invoke($subject, 'setPlaceholders', $plugin);

		$this->assertSame('System', $placeholder->get('PluginGroupNamespace'));
		$this->assertSame('Example', $placeholder->get('PluginNamespace'));
		$this->assertSame('9_pLuG!n', $config->build_target);
		$this->assertSame('9_pLuG!n', $config->lang_target);
		$this->assertSame('PLG_SYSTEM_EXAMPLE', $config->lang_prefix);
	}

	/**
	 * Apply global one-content placeholders to modern extension headers only.
	 *
	 * @param   class-string  $class     Infusion class.
	 * @param   bool          $modern    Whether modern replacement is expected.
	 * @param   string        $expected  Expected extension header.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testExtensionHeaderHonorsVersionedPlaceholderPolicy(
		string $class,
		bool $modern,
		string $expected
	): void
	{
		$plugin = (object) [
			'key' => '9_pLuG!n',
			'class_name' => 'Example',
			'header' => 'custom-header'
		];
		$header = $this->createMock(HeaderInterface::class);
		$header->expects($this->once())->method('get')
			->with('plugin.extension.header', 'Example')->willReturn('use ###TOKEN###;');
		$content = new ContentMulti();
		$config = $this->compilerConfig();
		$subject = $this->subject($class);
		$this->setProperty($subject, 'header', $header);
		$this->setProperty($subject, 'contentmulti', $content);

		if ($modern)
		{
			$one = new ContentOne();
			$one->set('TOKEN', 'Resolved');
			$this->setProperty($subject, 'placeholder', new Placeholder($config));
			$this->setProperty($subject, 'contentone', $one);
		}

		$this->invoke($subject, 'setExtensionClassHeader', $plugin);

		$this->assertSame(
			$expected,
			$content->get('9_pLuG!n|EXTENSION_CLASS_HEADER')
		);
	}

	/**
	 * Write provider header and class content for Joomla 4-6 plugins.
	 *
	 * @param   class-string  $class  Modern infusion class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('modernVersions')]
	public function testModernProviderWritesHeaderAndClassPlaceholders(string $class): void
	{
		$plugin = (object) ['key' => '9_pLuG!n', 'class_name' => 'Example'];
		$header = $this->createMock(HeaderInterface::class);
		$header->expects($this->once())->method('get')
			->with('plugin.provider.header', 'Example')->willReturn('provider-header');
		$provider = $this->createMock(ProviderInterface::class);
		$provider->expects($this->once())->method('get')->with($plugin)->willReturn('provider-class');
		$content = new ContentMulti();
		$subject = $this->subject($class);
		$this->setProperty($subject, 'header', $header);
		$this->setProperty($subject, 'provider', $provider);
		$this->setProperty($subject, 'contentmulti', $content);

		$this->invoke($subject, 'setProviderClassHeader', $plugin);
		$this->invoke($subject, 'setProviderClass', $plugin);

		$this->assertSame('provider-header', $content->get('9_pLuG!n|PROVIDER_CLASS_HEADER'));
		$this->assertSame('provider-class', $content->get('9_pLuG!n|PROVIDER_CLASS'));
	}

	/**
	 * Provide every supported Joomla plugin infusion implementation.
	 *
	 * @return  array<string, array{class-string, bool, string}>
	 * @since   6.1.6
	 */
	public static function versions(): array
	{
		$legacy = 'use ###TOKEN###;' . PHP_EOL . 'custom-header';
		$modern = 'use Resolved;' . PHP_EOL . 'custom-header';

		return [
			'Joomla 3' => [JoomlaThreeInfusion::class, false, $legacy],
			'Joomla 4' => [JoomlaFourInfusion::class, true, $modern],
			'Joomla 5' => [JoomlaFiveInfusion::class, true, $modern],
			'Joomla 6' => [JoomlaSixInfusion::class, true, $modern]
		];
	}

	/**
	 * Provide namespaced Joomla plugin infusion implementations.
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
	 * @param   object  $plugin   Plugin value.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function invoke(object $subject, string $method, object &$plugin): mixed
	{
		return (new ReflectionMethod($subject, $method))->invokeArgs($subject, [&$plugin]);
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
