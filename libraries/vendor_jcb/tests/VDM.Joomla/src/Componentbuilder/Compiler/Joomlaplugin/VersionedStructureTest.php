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
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\PluginDataInterface;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaFive\Structure as JoomlaFiveStructure;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaFour\Structure as JoomlaFourStructure;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaSix\Structure as JoomlaSixStructure;
use VDM\Joomla\Componentbuilder\Compiler\Joomlaplugin\JoomlaThree\Structure as JoomlaThreeStructure;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Joomla 3-6 plugin manifest, path, and empty-build orchestration contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(JoomlaThreeStructure::class)]
#[CoversClass(JoomlaFourStructure::class)]
#[CoversClass(JoomlaFiveStructure::class)]
#[CoversClass(JoomlaSixStructure::class)]
#[UsesClass(Config::class)]
#[UsesClass(Indent::class)]
#[UsesClass(Placefix::class)]
final class VersionedStructureTest extends CompilerDomainTestCase
{
	/**
	 * Render target-version manifests, including the Joomla 3 group normalization boundary.
	 *
	 * @param   class-string  $class         Structure class.
	 * @param   int           $version       Joomla major version.
	 * @param   bool          $hasNamespace  Whether modern namespace XML is required.
	 * @param   string        $group         Expected manifest group.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testManifestCarriesTargetVersionGroupAndNamespace(
		string $class,
		int $version,
		bool $hasNamespace,
		string $group
	): void
	{
		$subject = $this->subject($class, $version);
		$plugin = (object) [
			'group' => 'MiXeD',
			'group_namespace' => 'Mixed',
			'namespace' => 'Example',
			'lang_prefix' => 'PLG_MIXED_EXAMPLE',
			'plugin_version' => '4.5.6'
		];
		$xml = $this->invoke($subject, 'getXML', $plugin);

		$this->assertStringContainsString(
			'<extension type="plugin" version="' . $version . '.0" group="' . $group . '" method="upgrade">',
			$xml
		);
		$this->assertStringContainsString('<name>PLG_MIXED_EXAMPLE</name>', $xml);
		$this->assertStringContainsString('<version>4.5.6</version>', $xml);
		$this->assertStringContainsString('<description>PLG_MIXED_EXAMPLE_XML_DESCRIPTION</description>', $xml);
		$this->assertStringContainsString('###MAINXML###', $xml);
		$this->assertSame(
			$hasNamespace,
			str_contains(
				$xml,
				'<namespace path="src">Vendor\\Plugin\\Mixed\\Example</namespace>'
			)
		);
	}

	/**
	 * Place plugin output below the configured compiler path.
	 *
	 * @param   class-string  $class         Structure class.
	 * @param   int           $version       Joomla major version.
	 * @param   bool          $hasNamespace  Unused provider field.
	 * @param   string        $group         Unused provider field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testPluginPathUsesConfiguredCompilerRoot(
		string $class,
		int $version,
		bool $hasNamespace,
		string $group
	): void
	{
		$subject = $this->subject($class, $version);
		$plugin = (object) ['folder_name' => 'plg_mixed_example'];
		$this->invoke($subject, 'pluginPath', $plugin);

		$this->assertSame('/build/compiler/plg_mixed_example', $plugin->folder_path);
	}

	/**
	 * Stop immediately when no plugin data exists.
	 *
	 * @param   class-string  $class         Structure class.
	 * @param   int           $version       Joomla major version.
	 * @param   bool          $hasNamespace  Unused provider field.
	 * @param   string        $group         Unused provider field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testBuildShortCircuitsWhenPluginStoreIsEmpty(
		string $class,
		int $version,
		bool $hasNamespace,
		string $group
	): void
	{
		$plugin = $this->createMock(PluginDataInterface::class);
		$plugin->expects($this->once())->method('exists')->willReturn(false);
		$plugin->expects($this->never())->method('get');
		$subject = $this->subject($class, $version);
		$this->setProperty($subject, 'plugin', $plugin);

		$this->assertNull($subject->build());
	}

	/**
	 * Dispatch the before-build event even for an empty selected plugin collection.
	 *
	 * @param   class-string  $class         Structure class.
	 * @param   int           $version       Joomla major version.
	 * @param   bool          $hasNamespace  Unused provider field.
	 * @param   string        $group         Unused provider field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testBuildPublishesEmptyCollectionThroughBeforeEvent(
		string $class,
		int $version,
		bool $hasNamespace,
		string $group
	): void
	{
		$plugin = $this->createMock(PluginDataInterface::class);
		$plugin->expects($this->once())->method('exists')->willReturn(true);
		$plugin->expects($this->once())->method('get')->willReturn([]);
		$event = $this->createMock(EventInterface::class);
		$event->expects($this->once())->method('trigger')
			->with('jcb_ce_onBeforeBuildPlugins', $this->isArray());
		$subject = $this->subject($class, $version);
		$this->setProperty($subject, 'plugin', $plugin);
		$this->setProperty($subject, 'event', $event);

		$this->assertNull($subject->build());
	}

	/**
	 * Provide every supported Joomla plugin structure implementation.
	 *
	 * @return  array<string, array{class-string, int, bool, string}>
	 * @since   6.1.6
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => [JoomlaThreeStructure::class, 3, false, 'mixed'],
			'Joomla 4' => [JoomlaFourStructure::class, 4, true, 'MiXeD'],
			'Joomla 5' => [JoomlaFiveStructure::class, 5, true, 'MiXeD'],
			'Joomla 6' => [JoomlaSixStructure::class, 6, true, 'MiXeD']
		];
	}

	/**
	 * Create a structure with target-version configuration and namespace state.
	 *
	 * @param   class-string  $class    Structure class.
	 * @param   int           $version  Joomla major version.
	 *
	 * @return  object
	 * @since   6.1.6
	 */
	private function subject(string $class, int $version): object
	{
		$subject = (new ReflectionClass($class))->newInstanceWithoutConstructor();
		$this->setProperty($subject, 'config', $this->compilerConfig([
			'compiler_path' => '/build/compiler',
			'component_context' => 'com_example',
			'joomla_version' => $version,
			'joomla_versions' => [$version => ['xml_version' => $version . '.0']]
		]));

		if ((new ReflectionClass($class))->hasProperty('NamespacePrefix'))
		{
			$this->setProperty($subject, 'NamespacePrefix', 'Vendor');
			$this->setProperty($subject, 'ComponentNamespace', 'Example');
		}

		return $subject;
	}

	/**
	 * Invoke one protected rendering operation with reference-safe arguments.
	 *
	 * @param   object  $subject  Structure instance.
	 * @param   string  $method   Method name.
	 * @param   object  $value    Plugin value.
	 *
	 * @return  mixed
	 * @since   6.1.6
	 */
	private function invoke(object $subject, string $method, object &$value): mixed
	{
		return (new ReflectionMethod($subject, $method))->invokeArgs($subject, [&$value]);
	}

	/**
	 * Replace one non-public collaborator or rendering state value.
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
