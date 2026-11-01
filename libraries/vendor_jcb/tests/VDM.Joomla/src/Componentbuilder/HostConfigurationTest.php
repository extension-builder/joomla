<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    1st November, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder;


use Joomla\CMS\Application\CMSApplication;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;
use VDM\Joomla\Componentbuilder\Compiler\Config as CompilerConfig;
use VDM\Joomla\Componentbuilder\Fieldtype\Config as FieldtypeConfig;
use VDM\Joomla\Componentbuilder\JoomlaPower\Config as JoomlaPowerConfig;
use VDM\Joomla\Componentbuilder\Package\Config as PackageConfig;
use VDM\Joomla\Componentbuilder\Power\Config as PowerConfig;
use VDM\Joomla\Componentbuilder\Repository\Config as RepositoryConfig;
use VDM\Joomla\Componentbuilder\Snippet\Config as SnippetConfig;
use VDM\Tests\Support\JoomlaTestCase;


/**
 * Host application configuration and explicit configuration injection contracts.
 *
 * @since  6.2.1
 */
#[CoversClass(CompilerConfig::class)]
#[CoversClass(FieldtypeConfig::class)]
#[CoversClass(JoomlaPowerConfig::class)]
#[CoversClass(PackageConfig::class)]
#[CoversClass(PowerConfig::class)]
#[CoversClass(RepositoryConfig::class)]
#[CoversClass(SnippetConfig::class)]
final class HostConfigurationTest extends JoomlaTestCase
{
	/**
	 * Default construction reads the active application, never stale Factory configuration.
	 *
	 * @param   class-string<ComponentConfig>  $class  Domain configuration class.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('configurations')]
	public function testDefaultsUseTheActiveApplicationConfiguration(string $class): void
	{
		$registry = new Registry(['tmp_path' => '/application/tmp']);
		$application = $this->createMock(CMSApplication::class);
		$application->expects($this->once())->method('getInput')
			->willReturn(new Input(['request_value' => 'active-request']));
		$application->expects($this->once())->method('getConfig')->willReturn($registry);
		$this->setJoomlaApplication($application);
		$this->setJoomlaFactoryProperty('config', new Registry(['tmp_path' => '/stale/factory/tmp']));

		$config = new $class(null, new Registry(['gitea_username' => 'configured-user']));

		$this->assertSame($registry, (new ReflectionProperty($class, 'config'))->getValue($config));
		$this->assertSame('active-request', $config->get('request_value'));
		$this->assertSame('configured-user', $config->gitea_username);

		if ($config instanceof CompilerConfig || $config instanceof PowerConfig)
		{
			$this->assertSame('/application/tmp', $config->tmp_path);
		}
	}

	/**
	 * An explicitly supplied registry retains identity and bypasses application config lookup.
	 *
	 * @param   class-string<ComponentConfig>  $class  Domain configuration class.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('configurations')]
	public function testInjectedConfigurationTakesPrecedence(string $class): void
	{
		$registry = new Registry(['tmp_path' => '/injected/tmp']);
		$application = $this->createMock(CMSApplication::class);
		$application->expects($this->once())->method('getInput')->willReturn(new Input());
		$application->expects($this->never())->method('getConfig');
		$this->setJoomlaApplication($application);

		$config = new $class(null, new Registry(), $registry);

		$this->assertSame($registry, (new ReflectionProperty($class, 'config'))->getValue($config));

		if ($config instanceof CompilerConfig || $config instanceof PowerConfig)
		{
			$this->assertSame('/injected/tmp', $config->tmp_path);
		}
	}

	/**
	 * Fully injected compiler and Power configuration works without a Joomla application.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	public function testFullyInjectedConfigurationNeedsNoApplication(): void
	{
		foreach ([CompilerConfig::class, PowerConfig::class] as $class)
		{
			$config = new $class(new Input(), new Registry(), new Registry(['tmp_path' => '/isolated/tmp']));

			$this->assertSame('/isolated/tmp', $config->tmp_path);
		}
	}

	/**
	 * Every domain constructor that selects a host configuration registry.
	 *
	 * @return  array<string, array{class-string<ComponentConfig>}>
	 * @since   6.2.1
	 */
	public static function configurations(): array
	{
		return [
			'compiler' => [CompilerConfig::class],
			'fieldtype' => [FieldtypeConfig::class],
			'joomla-power' => [JoomlaPowerConfig::class],
			'package' => [PackageConfig::class],
			'power' => [PowerConfig::class],
			'repository' => [RepositoryConfig::class],
			'snippet' => [SnippetConfig::class],
		];
	}
}
