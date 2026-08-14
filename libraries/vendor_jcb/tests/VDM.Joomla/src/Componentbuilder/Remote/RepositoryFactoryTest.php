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

namespace VDM\Joomla\Tests\Componentbuilder\Remote;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Abstraction\Factory as ExtendingFactory;
use VDM\Joomla\Componentbuilder\JoomlaPower\Factory as JoomlaPowerFactory;
use VDM\Joomla\Componentbuilder\JoomlaPower\Readme\Main as JoomlaPowerMain;
use VDM\Joomla\Componentbuilder\Power\Factory as PowerFactory;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Componentbuilder\Repository\Factory as RepositoryFactory;
use VDM\Joomla\Componentbuilder\Repository\Readme\Main as RepositoryMain;
use VDM\Joomla\Componentbuilder\Search\Factory as SearchFactory;
use VDM\Joomla\Componentbuilder\Snippet\Factory as SnippetFactory;
use VDM\Joomla\Componentbuilder\Snippet\Readme\Main as SnippetMain;
use VDM\Joomla\Componentbuilder\Table;
use VDM\Tests\Support\FactoryTestCase;


/**
 * Repository-domain factory container and shared-service contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(SearchFactory::class)]
#[CoversClass(PowerFactory::class)]
#[CoversClass(JoomlaPowerFactory::class)]
#[CoversClass(RepositoryFactory::class)]
#[CoversClass(SnippetFactory::class)]
#[UsesClass(ExtendingFactory::class)]
final class RepositoryFactoryTest extends FactoryTestCase
{
	/**
	 * Protect each factory's service catalog, sharing, resolution, and singleton.
	 *
	 * @param   class-string          $factory        Factory class.
	 * @param   array<int, string>    $keys           Representative service keys.
	 * @param   string                $resolvedKey    Safe key to resolve.
	 * @param   class-string          $resolvedClass  Expected service class.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('factoryProvider')]
	public function testFactoriesBuildSharedDomainServiceCatalogs(
		string $factory,
		array $keys,
		string $resolvedKey,
		string $resolvedClass
	): void
	{
		$this->isolateFactory($factory);
		$container = $factory::getContainer();

		$this->assertSame($container, $factory::getContainer());

		foreach ($keys as $key)
		{
			$this->assertTrue($container->has($key), $factory . ' did not register ' . $key);
			$this->assertTrue($container->isShared($key), $factory . ' did not share ' . $key);
		}

		$resolved = $factory::_($resolvedKey);
		$this->assertInstanceOf($resolvedClass, $resolved);
		$this->assertSame($resolved, $factory::_($resolvedKey));
	}

	/**
	 * Supply representative contracts for every repository-domain factory.
	 *
	 * @return  array<string, array<int, mixed>>
	 * @since   6.1.6
	 */
	public static function factoryProvider(): array
	{
		return [
			'search' => [
				SearchFactory::class,
				['Config', 'Table', 'Search.Basic', 'Search.Regex', 'Load.Database', 'Insert.Model', 'Agent.Update'],
				'Table',
				Table::class,
			],
			'power' => [
				PowerFactory::class,
				['Power.Config', 'Power.Grep', 'Power.Remote.Config', 'Power.Parser', 'Power.Plantuml', 'Power.Generator', 'Power.Generator.Bucket'],
				'Power.Parser',
				Parser::class,
			],
			'Joomla Power' => [
				JoomlaPowerFactory::class,
				['Joomla.Power.Config', 'Joomla.Power.Grep', 'Joomla.Power.Remote.Config', 'Joomla.Power.Remote.Get', 'Joomla.Power.Readme.Main'],
				'Joomla.Power.Readme.Main',
				JoomlaPowerMain::class,
			],
			'repository' => [
				RepositoryFactory::class,
				['Repository.Config', 'Repository.Grep', 'Repository.Remote.Config', 'Repository.Resolver', 'Repository.Remote.Set', 'Repository.Readme.Main'],
				'Repository.Readme.Main',
				RepositoryMain::class,
			],
			'snippet and snippet type' => [
				SnippetFactory::class,
				['Snippet.Config', 'Snippet.Grep', 'Snippet.Remote.Config', 'Snippet.Remote.Get', 'Snippet.Readme.Main', 'SnippetType.Grep', 'SnippetType.Remote.Config'],
				'Snippet.Readme.Main',
				SnippetMain::class,
			],
		];
	}

	/**
	 * Protect factory container isolation between repository domains.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testFactoriesDoNotShareStaticContainersAcrossDomains(): void
	{
		foreach ([SearchFactory::class, PowerFactory::class, RepositoryFactory::class] as $factory)
		{
			$this->isolateFactory($factory);
		}

		$this->assertNotSame(SearchFactory::getContainer(), PowerFactory::getContainer());
		$this->assertNotSame(PowerFactory::getContainer(), RepositoryFactory::getContainer());
	}
}
