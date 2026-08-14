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

namespace VDM\Joomla\Tests\Componentbuilder\Power;


use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Abstraction\ComponentConfig;
use VDM\Joomla\Componentbuilder\JoomlaPower\Config as JoomlaPowerConfig;
use VDM\Joomla\Componentbuilder\JoomlaPower\Remote\Config as JoomlaPowerRemoteConfig;
use VDM\Joomla\Componentbuilder\Power\Config as PowerConfig;
use VDM\Joomla\Componentbuilder\Power\Interfaces\TableInterface;
use VDM\Joomla\Componentbuilder\Power\Remote\Config as PowerRemoteConfig;
use VDM\Joomla\Componentbuilder\Power\Table;
use VDM\Joomla\Componentbuilder\Repository\Config as RepositoryConfig;
use VDM\Joomla\Componentbuilder\Repository\Remote\Config as RepositoryRemoteConfig;
use VDM\Joomla\Componentbuilder\Snippet\Config as SnippetConfig;
use VDM\Joomla\Componentbuilder\Snippet\Remote\Config as SnippetRemoteConfig;
use VDM\Joomla\Componentbuilder\SnippetType\Remote\Config as SnippetTypeRemoteConfig;
use VDM\Joomla\Interfaces\Remote\ConfigInterface;
use VDM\Tests\Support\TestCase;


/**
 * Repository-source configuration, table-map, and remote-schema contracts.
 *
 * @since  6.1.6
 */
#[CoversClass(PowerConfig::class)]
#[CoversClass(JoomlaPowerConfig::class)]
#[CoversClass(RepositoryConfig::class)]
#[CoversClass(SnippetConfig::class)]
#[CoversClass(PowerRemoteConfig::class)]
#[CoversClass(JoomlaPowerRemoteConfig::class)]
#[CoversClass(RepositoryRemoteConfig::class)]
#[CoversClass(SnippetRemoteConfig::class)]
#[CoversClass(SnippetTypeRemoteConfig::class)]
#[CoversClass(Table::class)]
#[UsesClass(ComponentConfig::class)]
final class RepositoryConfigurationTest extends TestCase
{
	/**
	 * Protect Super Power repository defaults, user precedence, and switches.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPowerConfigBuildsOrderedInitialRepositoriesAndLocalPolicy(): void
	{
		$config = $this->config(PowerConfig::class, [
			'gitea_username' => 'alice',
			'gitea_token' => 'secret',
			'powers_repository' => 1,
			'super_powers_repositories' => 1,
			'local_powers_repository_path' => '/srv/powers',
		], ['tmp_path' => '/tmp/jcb']);
		$repos = $config->super_powers_init_repos;

		$this->assertSame(['alice.super-powers', 'joomla.super-powers', 'joomla.gitea', 'joomla.openai'], array_keys($repos));
		$this->assertSame('master', $repos['alice.super-powers']->read_branch);
		$this->assertSame('secret', $config->gitea_token);
		$this->assertTrue($config->add_super_powers);
		$this->assertTrue($config->add_own_powers);
		$this->assertSame('/srv/powers', $config->local_powers_repository_path);
		$this->assertSame("\t", $config->indentation_value);
	}

	/**
	 * Protect domain-specific repository names, organizations, and user ordering.
	 *
	 * @param   class-string<ComponentConfig>  $class             Config class.
	 * @param   string                         $organizationKey   Parameter key.
	 * @param   string                         $organizationPath  Magic config path.
	 * @param   string                         $repositoriesPath  Magic repository path.
	 * @param   string                         $repositoryName    Repository name.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('domainConfigProvider')]
	public function testDomainConfigsBuildUserAndCoreRepositories(
		string $class,
		string $organizationKey,
		string $organizationPath,
		string $repositoriesPath,
		string $repositoryName
	): void
	{
		$config = $this->config($class, [
			'gitea_username' => 'alice',
			$organizationKey => 'core-team',
		]);
		$this->assertSame('alice', $config->gitea_username);
		$repos = $config->{$repositoriesPath};

		$this->assertSame('core-team', $config->{$organizationPath});
		$this->assertSame(
			["alice.{$repositoryName}", "core-team.{$repositoryName}"],
			array_keys($repos)
		);
		$this->assertSame('alice', $repos["alice.{$repositoryName}"]->organisation);
		$this->assertSame('master', $repos["core-team.{$repositoryName}"]->read_branch);
	}

	/**
	 * Supply configuration naming conventions across repository domains.
	 *
	 * @return  array<string, array<int, string>>
	 * @since   6.1.6
	 */
	public static function domainConfigProvider(): array
	{
		return [
			'Joomla Powers' => [
				JoomlaPowerConfig::class,
				'joomla_powers_core_organisation',
				'joomla_powers_core_organisation',
				'joomla_powers_init_repos',
				'joomla-powers',
			],
			'repositories' => [
				RepositoryConfig::class,
				'repository_core_organisation',
				'repository_core_organisation',
				'repository_init_repos',
				'repository',
			],
			'snippets' => [
				SnippetConfig::class,
				'snippet_core_organisation',
				'snippet_core_organisation',
				'snippet_init_repos',
				'snippet',
			],
		];
	}

	/**
	 * Protect remote table, index, key, and README conventions across domains.
	 *
	 * @param   class-string<ConfigInterface>  $class       Remote config class.
	 * @param   string                         $table       Table name.
	 * @param   string                         $area        Human area.
	 * @param   string                         $index       Index path.
	 * @param   string                         $prefix      Prefix key.
	 * @param   string                         $suffix      Suffix key.
	 * @param   bool                           $hasReadme   README policy.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[DataProvider('remoteConfigProvider')]
	public function testRemoteConfigsExposeRepositorySchemaContracts(
		string $class,
		string $table,
		string $area,
		string $index,
		string $prefix,
		string $suffix,
		bool $hasReadme
	): void
	{
		$subject = new $class(new Table());

		$this->assertInstanceOf(ConfigInterface::class, $subject);
		$this->assertSame($table, $subject->getTable());
		$this->assertSame($area, $subject->getArea());
		$this->assertSame($index, $subject->getIndexPath());
		$this->assertSame($prefix, $subject->getPrefixKey());
		$this->assertSame($suffix, $subject->getSuffixKey());
		$this->assertSame($hasReadme, $subject->hasMainReadme());
		$this->assertSame($hasReadme, $subject->hasItemReadme());
		$this->assertSame('guid', $subject->getGuidField());
		$this->assertContains('name', $subject->getIndexHeader());
		$this->assertContains('guid', $subject->getIndexHeader());
		$this->assertContains('local', $subject->getIndexHeader());
	}

	/**
	 * Supply remote repository schema variants.
	 *
	 * @return  array<string, array<int, mixed>>
	 * @since   6.1.6
	 */
	public static function remoteConfigProvider(): array
	{
		return [
			'Super Power' => [PowerRemoteConfig::class, 'power', 'Super Power', 'super-powers.json', 'Super---', '---Power', true],
			'Joomla Power' => [JoomlaPowerRemoteConfig::class, 'joomla_power', 'Joomla Power', 'index.json', 'Joomla---', '---Power', true],
			'Repository' => [RepositoryRemoteConfig::class, 'repository', 'Repository', 'index.json', '', '', true],
			'Snippet' => [SnippetRemoteConfig::class, 'snippet', 'Snippet', 'snippet-index.json', '', '', true],
			'Snippet Type' => [SnippetTypeRemoteConfig::class, 'snippet_type', 'Snippet Type', 'snippet-type.json', '', '', false],
		];
	}

	/**
	 * Protect metadata traversal for parents, children, search areas, and lists.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPowerTableTraversesRelationshipsAndSearchableFields(): void
	{
		$subject = new Table();
		$parents = $subject->parents('power');
		$children = $subject->children('power', ['power']);

		$this->assertInstanceOf(TableInterface::class, $subject);
		$this->assertSame('power', $parents['extends']['link']['entity'] ?? $parents['extends']['entity']);
		$this->assertSame('guid', $parents['use_selection|use']['key']);
		$this->assertSame(
			['load_selection|load', 'extends', 'implements', 'extendsinterfaces', 'use_selection|use'],
			array_column($children['guid'], 'key')
		);
		$this->assertSame(
			['description', 'licensing_template', 'extendsinterfaces_custom', 'head', 'main_class_code'],
			array_keys($subject->search('power', 'code'))
		);
		$this->assertSame(['main_class_code' => 'main_class_code'], $subject->search('power', 'customcode'));
		$this->assertSame('powers', $subject->listViewCodeName('power'));
		$this->assertNull($subject->listViewCodeName('does_not_exist'));
	}

	/**
	 * Protect field mapping, ignored secrets, and map caching on remote configs.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testRemoteConfigMapsFieldsAndRemovesDomainIgnoredValues(): void
	{
		$power = new PowerRemoteConfig(new Table());
		$repository = new RepositoryRemoteConfig(new Table());

		$this->assertArrayHasKey('name', $power->getMap());
		$this->assertArrayNotHasKey('approved_paths', $power->getMap());
		$this->assertArrayNotHasKey('approved', $power->getMap());
		$this->assertArrayNotHasKey('access', $power->getMap());
		$this->assertArrayNotHasKey('token', $repository->getMap());
		$this->assertArrayNotHasKey('access', $repository->getMap());
		$this->assertSame('repositories', $repository->getListViewCodeName());
	}

	/**
	 * Record the missing Joomla Input imports on three constructor contracts.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testDomainConfigConstructorsAcceptJoomlaInput(): void
	{
		foreach ([JoomlaPowerConfig::class, RepositoryConfig::class, SnippetConfig::class] as $class)
		{
			$parameter = (new ReflectionClass($class))->getConstructor()->getParameters()[0];
			$this->assertSame(Input::class, $parameter->getType()->getName(), $class);
		}
	}

	/**
	 * Record repository initialization's dependency on prior username access.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testDomainRepositoriesIncludeConfiguredUserWithoutCacheWarmup(): void
	{
		$config = $this->config(RepositoryConfig::class, ['gitea_username' => 'alice']);

		$this->assertArrayHasKey('alice.repository', $config->repository_init_repos);
	}

	/**
	 * Hydrate config dependencies while retaining production magic-get behavior.
	 *
	 * This avoids Joomla global state and also allows the domain methods to be
	 * tested while their currently mis-namespaced Input hints remain documented.
	 *
	 * @param   class-string<ComponentConfig>  $class   Config class.
	 * @param   array<string, mixed>            $params  Component parameters.
	 * @param   array<string, mixed>            $global  Joomla configuration.
	 *
	 * @return  ComponentConfig
	 * @since   6.1.6
	 */
	private function config(string $class, array $params = [], array $global = []): ComponentConfig
	{
		$reflection = new ReflectionClass($class);
		$config = $reflection->newInstanceWithoutConstructor();
		(new ReflectionProperty(ComponentConfig::class, 'input'))->setValue($config, new Input());
		(new ReflectionProperty(ComponentConfig::class, 'params'))->setValue($config, new Registry($params));

		if ($reflection->hasProperty('config'))
		{
			$reflection->getProperty('config')->setValue($config, new Registry($global));
		}

		return $config;
	}
}
