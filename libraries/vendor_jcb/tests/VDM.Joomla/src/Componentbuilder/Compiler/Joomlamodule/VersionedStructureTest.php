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
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\ModuleDataInterface;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaFive\Structure as JoomlaFiveStructure;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaFour\Structure as JoomlaFourStructure;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaSix\Structure as JoomlaSixStructure;
use VDM\Joomla\Componentbuilder\Compiler\Joomlamodule\JoomlaThree\Structure as JoomlaThreeStructure;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;
use VDM\Tests\Support\CompilerDomainTestCase;


/**
 * Joomla 3-6 module manifest, path, and empty-build orchestration contracts.
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
	 * Generate version-specific manifest roots and modern namespace declarations.
	 *
	 * @param   class-string  $class            Structure class.
	 * @param   int           $version          Joomla major version.
	 * @param   bool          $hasNamespace     Whether modern namespace XML is required.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testManifestCarriesTargetVersionClientAndNamespace(
		string $class,
		int $version,
		bool $hasNamespace
	): void
	{
		$subject = $this->subject($class, $version);
		$module = (object) [
			'target_client' => 'administrator',
			'lang_prefix' => 'MOD_EXAMPLE',
			'module_version' => '2.3.4',
			'namespace' => 'Example'
		];
		$xml = $this->invoke($subject, 'getXML', $module);

		$this->assertStringContainsString(
			'<extension type="module" version="' . $version . '.0" client="administrator" method="upgrade">',
			$xml
		);
		$this->assertStringContainsString('<name>MOD_EXAMPLE</name>', $xml);
		$this->assertStringContainsString('<version>2.3.4</version>', $xml);
		$this->assertStringContainsString('<description>MOD_EXAMPLE_XML_DESCRIPTION</description>', $xml);
		$this->assertStringContainsString('###MAINXML###', $xml);
		$this->assertSame(
			$hasNamespace,
			str_contains($xml, '<namespace path="src">Vendor\\Module\\Example</namespace>')
		);
	}

	/**
	 * Place module output below the compiler path for every target implementation.
	 *
	 * @param   class-string  $class         Structure class.
	 * @param   int           $version       Joomla major version.
	 * @param   bool          $hasNamespace  Unused provider field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testModulePathUsesConfiguredCompilerRoot(
		string $class,
		int $version,
		bool $hasNamespace
	): void
	{
		$subject = $this->subject($class, $version);
		$module = (object) ['folder_name' => 'mod_example'];
		$this->invoke($subject, 'modulePath', $module);

		$this->assertSame('/build/compiler/mod_example', $module->folder_path);
	}

	/**
	 * Stop immediately when no module data exists.
	 *
	 * @param   class-string  $class         Structure class.
	 * @param   int           $version       Joomla major version.
	 * @param   bool          $hasNamespace  Unused provider field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testBuildShortCircuitsWhenModuleStoreIsEmpty(
		string $class,
		int $version,
		bool $hasNamespace
	): void
	{
		$module = $this->createMock(ModuleDataInterface::class);
		$module->expects($this->once())->method('exists')->willReturn(false);
		$module->expects($this->never())->method('get');
		$subject = $this->subject($class, $version);
		$this->setProperty($subject, 'module', $module);

		$this->assertNull($subject->build());
	}

	/**
	 * Dispatch the before-build event even when the selected module collection is empty.
	 *
	 * @param   class-string  $class         Structure class.
	 * @param   int           $version       Joomla major version.
	 * @param   bool          $hasNamespace  Unused provider field.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('versions')]
	public function testBuildPublishesEmptyCollectionThroughBeforeEvent(
		string $class,
		int $version,
		bool $hasNamespace
	): void
	{
		$module = $this->createMock(ModuleDataInterface::class);
		$module->expects($this->once())->method('exists')->willReturn(true);
		$module->expects($this->once())->method('get')->willReturn([]);
		$event = $this->createMock(EventInterface::class);
		$event->expects($this->once())->method('trigger')
			->with('jcb_ce_onBeforeBuildModules', $this->isArray());
		$subject = $this->subject($class, $version);
		$this->setProperty($subject, 'module', $module);
		$this->setProperty($subject, 'event', $event);

		$this->assertNull($subject->build());
	}

	/**
	 * Provide every supported Joomla module structure implementation.
	 *
	 * @return  array<string, array{class-string, int, bool}>
	 * @since   6.1.6
	 */
	public static function versions(): array
	{
		return [
			'Joomla 3' => [JoomlaThreeStructure::class, 3, false],
			'Joomla 4' => [JoomlaFourStructure::class, 4, true],
			'Joomla 5' => [JoomlaFiveStructure::class, 5, true],
			'Joomla 6' => [JoomlaSixStructure::class, 6, true]
		];
	}

	/**
	 * Create a structure with target-version configuration and stable namespace state.
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
	 * @param   object  $value    Module value.
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
