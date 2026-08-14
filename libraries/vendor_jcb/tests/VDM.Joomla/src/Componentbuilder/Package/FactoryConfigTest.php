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

namespace VDM\Joomla\Tests\Componentbuilder\Package;


use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesNamespace;
use ReflectionClass;
use ReflectionMethod;
use VDM\Joomla\Componentbuilder\Package\Builder\Get;
use VDM\Joomla\Componentbuilder\Package\Builder\Set;
use VDM\Joomla\Componentbuilder\Package\Config;
use VDM\Joomla\Componentbuilder\Package\Dependency\Tracker;
use VDM\Joomla\Componentbuilder\Package\Factory;
use VDM\Joomla\Componentbuilder\Package\MessageBus;
use VDM\Tests\Support\FactoryTestCase;


/**
 * Package factory composition and global settings tests.
 *
 * @since  1.0.0
 */
#[CoversClass(Factory::class)]
#[CoversClass(Config::class)]
#[UsesNamespace('VDM\Joomla')]
final class FactoryConfigTest extends FactoryTestCase
{
	/**
	 * Install the current Package Input compatibility alias and isolate Factory.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		if (!class_exists('VDM\Joomla\Componentbuilder\Package\Input', false))
		{
			class_alias(Input::class, 'VDM\Joomla\Componentbuilder\Package\Input');
		}

		$this->isolateFactory(Factory::class);
	}

	/**
	 * The factory composes the exact reviewed capability union once per process.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testFactoryBuildsExactSharedCapabilityCatalog(): void
	{
		$container = Factory::getContainer();
		$reflection = new ReflectionClass(Container::class);
		$resources = $reflection->getProperty('resources')->getValue($container);
		$aliases = $reflection->getProperty('aliases')->getValue($container);
		$resourceKeys = array_keys($resources);
		$aliasKeys = array_keys($aliases);
		sort($resourceKeys);
		sort($aliasKeys);

		$this->assertCount(283, $resourceKeys);
		$this->assertSame(
			'c5dfd4b5c834be6fdecf68d51846d97d00a88ac7453d8f86df444313368de3ab',
			hash('sha256', implode("\n", $resourceKeys)),
			'Review the complete Package factory capability catalog before changing this fingerprint.'
		);
		$this->assertCount(46, $aliasKeys);
		$this->assertSame(
			'c759942e513de864789400422da3ddc42a9af8371e6c4174b3fb5ac09dafdb72',
			hash('sha256', implode("\n", $aliasKeys)),
			'Review the complete Package factory alias catalog before changing this fingerprint.'
		);
		$this->assertSame($container, Factory::getContainer());
		$this->assertSame($container, Factory::getContainer());
	}

	/**
	 * Core state and orchestration services share identity across key aliases.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testFactoryResolvesSharedStateAndBuilderAliases(): void
	{
		$tracker = Factory::_('Package.Tracker');
		$message = Factory::_('Package.Message');
		$get = Factory::_('Package.Builder.Get');
		$set = Factory::_('Package.Builder.Set');

		$this->assertInstanceOf(Tracker::class, $tracker);
		$this->assertSame($tracker, Factory::_(Tracker::class));
		$this->assertInstanceOf(MessageBus::class, $message);
		$this->assertSame($message, Factory::_(MessageBus::class));
		$this->assertInstanceOf(Get::class, $get);
		$this->assertSame($get, Factory::_(Get::class));
		$this->assertInstanceOf(Set::class, $set);
		$this->assertSame($set, Factory::_(Set::class));
		$this->assertSame(
			['local' => [], 'not_found' => [], 'added' => []],
			$get->get('unsupported', ['value'])
		);
		$set->items('unsupported', ['value']);
		$this->assertNull($tracker->get('set'));
	}

	/**
	 * Package credentials, organisation, and initial repository settings are stable.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testConfigResolvesAndCachesRemotePackageSettings(): void
	{
		$input = new Input([
			'input_only' => 'request-value',
			'precedence' => 'request-value',
		]);
		$params = new Registry([
			'gitea_username' => 'package-user',
			'gitea_token' => 'secret-token',
			'package_core_organisation' => 'example-org',
			'precedence' => 'parameter-value',
		]);
		$global = new Registry(['tmp_path' => '/isolated/tmp']);
		$config = new Config($input, $params, $global);

		$this->assertSame('package-user', $config->gitea_username);
		$this->assertSame('secret-token', $config->gitea_token);
		$this->assertSame('example-org', $config->package_core_organisation);
		$this->assertSame('request-value', $config->get('input_only'));
		$this->assertSame('parameter-value', $config->get('precedence'));
		$this->assertEquals(
			[
				'example-org.packages' => (object) [
					'organisation' => 'example-org',
					'repository' => 'packages',
					'read_branch' => 'master',
				],
			],
			$config->package_init_repos
		);

		$params->set('gitea_username', 'mutated-after-read');
		$this->assertSame('package-user', $config->gitea_username);
		$config->package_core_organisation = 'runtime-override';
		$this->assertSame('runtime-override', $config->package_core_organisation);
		$this->assertSame(
			$global,
			(new ReflectionClass(Config::class))->getProperty('config')->getValue($config)
		);
	}

	/**
	 * The documented constructor dependency must name Joomla's Input class.
	 *
	 * Config currently lacks the Input import and therefore declares an unrelated
	 * Package\\Input type even though the parent component configuration expects
	 * Joomla\\Input\\Input.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[Group('known-defect')]
	public function testConfigConstructorDeclaresDocumentedJoomlaInputType(): void
	{
		$constructor = new ReflectionMethod(Config::class, '__construct');
		$type = $constructor->getParameters()[0]->getType();

		$this->assertSame(Input::class, $type?->getName());
	}
}
