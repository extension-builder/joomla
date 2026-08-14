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

namespace VDM\Tests\Support;


use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\LanguageFactory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry as JoomlaRegistry;
use ReflectionClass;
use ReflectionProperty;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Utilities\Component\Helper;
use VDM\Joomla\Utilities\String\FieldHelper;
use VDM\Joomla\Utilities\String\TypeHelper;
use VDM\Joomla\Utilities\StringHelper;


/**
 * Shared construction helpers for compiler domain-service contracts.
 *
 * @since  1.0.0
 */
abstract class CompilerDomainTestCase extends CompilerUtilityTestCase
{
	/**
	 * Process-static state changed by compiler naming utilities.
	 *
	 * @var    array<string, mixed>
	 * @since  1.0.0
	 */
	private array $namingState = [];

	/**
	 * Install deterministic naming parameters and Joomla language services.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$container = new ReflectionProperty(JoomlaFactory::class, 'container');
		$params = new ReflectionProperty(Helper::class, 'params');
		$fieldBuilder = new ReflectionProperty(FieldHelper::class, 'builder');
		$typeBuilder = new ReflectionProperty(TypeHelper::class, 'builder');
		$typeCache = new ReflectionProperty(TypeHelper::class, 'cache');
		$this->namingState = [
			'container' => $container->getValue(),
			'option' => Helper::$option,
			'params' => $params->getValue(),
			'languageTag' => StringHelper::$langTag,
			'fieldBuilder' => $fieldBuilder->getValue(),
			'typeBuilder' => $typeBuilder->getValue(),
			'typeCache' => $typeCache->getValue()
		];

		$languageContainer = new Container();
		$languageContainer->share(LanguageFactoryInterface::class, new LanguageFactory(), true);
		$container->setValue(null, $languageContainer);
		Helper::$option = 'com_componentbuilder';
		$params->setValue(null, [
			'com_componentbuilder' => new JoomlaRegistry([
				'language' => 'en-GB',
				'field_name_builder' => 2,
				'type_name_builder' => 2
			])
		]);
		StringHelper::$langTag = 'en-GB';
		$fieldBuilder->setValue(null, 2);
		$typeBuilder->setValue(null, 2);
		$typeCache->setValue(null, []);
	}

	/**
	 * Create an isolated compiler configuration.
	 *
	 * @param   array<string, mixed>  $values  Initial configuration values.
	 *
	 * @return  Config
	 * @since   1.0.0
	 */
	protected function compilerConfig(array $values = []): Config
	{
		$config = new Config(new Input(), new JoomlaRegistry(), new JoomlaRegistry());

		foreach ($values as $key => $value)
		{
			$config->set($key, $value);
		}

		return $config;
	}

	/**
	 * Create a final collaborator without running its dependency graph.
	 *
	 * Only use this for a dependency which the exercised path cannot call.
	 *
	 * @template T of object
	 * @param   class-string<T>  $class  Collaborator class.
	 *
	 * @return  T
	 * @since   1.0.0
	 */
	protected function inertCompilerCollaborator(string $class): object
	{
		return (new ReflectionClass($class))->newInstanceWithoutConstructor();
	}

	/**
	 * Replace or seed one non-public collaborator/state property.
	 *
	 * @param   object  $subject   Object whose state is being prepared.
	 * @param   string  $property  Property name.
	 * @param   mixed   $value     Property value.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function setCompilerProperty(object $subject, string $property, mixed $value): void
	{
		(new ReflectionProperty($subject, $property))->setValue($subject, $value);
	}

	/**
	 * Restore naming and Joomla factory state after each domain contract.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function tearDown(): void
	{
		(new ReflectionProperty(JoomlaFactory::class, 'container'))
			->setValue(null, $this->namingState['container']);
		Helper::$option = $this->namingState['option'];
		(new ReflectionProperty(Helper::class, 'params'))
			->setValue(null, $this->namingState['params']);
		StringHelper::$langTag = $this->namingState['languageTag'];
		(new ReflectionProperty(FieldHelper::class, 'builder'))
			->setValue(null, $this->namingState['fieldBuilder']);
		(new ReflectionProperty(TypeHelper::class, 'builder'))
			->setValue(null, $this->namingState['typeBuilder']);
		(new ReflectionProperty(TypeHelper::class, 'cache'))
			->setValue(null, $this->namingState['typeCache']);
		$this->namingState = [];

		parent::tearDown();
	}
}
