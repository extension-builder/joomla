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

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use Joomla\Input\Input;
use Joomla\Registry\Registry as JoomlaRegistry;
use ReflectionClass;
use ReflectionProperty;
use VDM\Joomla\Abstraction\Registry as VdmRegistry;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\AdminViews\ToolbarComposer;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomButtons;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\DynamicButtons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentMulti;
use VDM\Joomla\Componentbuilder\Compiler\Builder\ContentOne;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomForm;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DynamicButtons as DynamicButtonsBuilder;
use VDM\Joomla\Componentbuilder\Compiler\Builder\OnlyFunctionButtons;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionAction;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionComponent;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionCore;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionDashboard;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionGlobalAction;
use VDM\Joomla\Componentbuilder\Compiler\Builder\PermissionViews;
use VDM\Joomla\Componentbuilder\Compiler\Component;
use VDM\Joomla\Componentbuilder\Compiler\Component\Data;
use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Creator\Permission;
use VDM\Joomla\Componentbuilder\Compiler\Customcode\Dispenser;
use VDM\Joomla\Componentbuilder\Compiler\Interfaces\EventInterface;
use VDM\Joomla\Componentbuilder\Compiler\Language;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Registry;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Counter;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\File as CompilerFile;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Structure;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Valuation;
use VDM\Joomla\Utilities\StringHelper;
use VDM\Tests\Support\FilesystemTestCase;


/**
 * Deterministic fixture factory for compiler architecture renderers.
 *
 * @since  6.1.6
 */
abstract class ArchitectureTestCase extends FilesystemTestCase
{
	/**
	 * Compiler configuration shared by collaborators in one test.
	 *
	 * @var    Config
	 * @since  6.1.6
	 */
	private Config $architectureConfig;

	/**
	 * Placeholder service shared by collaborators in one test.
	 *
	 * @var    Placeholder
	 * @since  6.1.6
	 */
	private Placeholder $architecturePlaceholder;

	/**
	 * Language service shared by collaborators in one test.
	 *
	 * @var    Language
	 * @since  6.1.6
	 */
	private Language $architectureLanguage;

	/**
	 * Permission service shared by collaborators in one test.
	 *
	 * @var    Permission
	 * @since  6.1.6
	 */
	private Permission $architecturePermission;

	/**
	 * Previous static utility state restored after each test.
	 *
	 * @var    array<string, array{initialized:bool,value:mixed}>
	 * @since  6.1.6
	 */
	private array $staticUtilityState = [];

	/**
	 * Create deterministic renderer collaborators and generated indentation.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->replaceStaticProperty(Indent::class, 'indent', "\t");
		$this->replaceStaticProperty(Indent::class, 'bucket', []);
		$this->replaceStaticProperty(Line::class, 'add', false);
		$this->replaceStaticProperty(StringHelper::class, 'langTag', 'en-GB');

		$this->architectureConfig = new Config(
			new Input([]),
			new JoomlaRegistry(),
			new JoomlaRegistry()
		);
		$this->architectureConfig->set('component_code_name', 'demo');
		$this->architectureConfig->set('lang_prefix', 'COM_DEMO');
		$this->architectureConfig->set('lang_target', 'admin');
		$this->architectureConfig->set('build_target', 'admin');
		$this->architectureConfig->set('joomla_version', 6);
		$this->architectureConfig->set('namespace_prefix', 'Acme');

		$this->architecturePlaceholder = new Placeholder($this->architectureConfig);
		$this->architecturePlaceholder->set('NamespacePrefix', 'Acme', false);
		$this->architectureLanguage = new Language($this->architectureConfig);
		$this->architecturePermission = $this->createPermission();
	}

	/**
	 * Instantiate a renderer with explicit overrides and lightweight real collaborators.
	 *
	 * Overrides may be keyed by constructor parameter name or dependency class name.
	 *
	 * @param   class-string  $class      Renderer class.
	 * @param   array         $overrides  Constructor dependency overrides.
	 *
	 * @return  object
	 * @since   6.1.6
	 */
	protected function renderer(string $class, array $overrides = []): object
	{
		$reflection = new ReflectionClass($class);
		$constructor = $reflection->getConstructor();

		if ($constructor === null)
		{
			return $reflection->newInstance();
		}

		$arguments = [];

		foreach ($constructor->getParameters() as $parameter)
		{
			$type = $parameter->getType()?->getName();

			if (array_key_exists($parameter->getName(), $overrides))
			{
				$arguments[] = $overrides[$parameter->getName()];
				continue;
			}

			if ($type !== null && array_key_exists($type, $overrides))
			{
				$arguments[] = $overrides[$type];
				continue;
			}

			$arguments[] = $this->collaborator($type);
		}

		return $reflection->newInstanceArgs($arguments);
	}

	/**
	 * Resolve the class a Joomla target uses for one generated concern.
	 *
	 * A family only carries a class per target where the generated code
	 * really differs. Every other target shares the one implementation in
	 * the root of the concern folder, so a target outside $diverging
	 * resolves to that shared class rather than to a namespace of its own.
	 *
	 * @param   string         $version    Target namespace segment, such as `JoomlaThree`.
	 * @param   string         $concern    Concern path below Architecture, such as `Menu\CustomView`.
	 * @param   array<string>  $diverging  Target segments that carry their own class.
	 *
	 * @return  class-string
	 * @since   6.1.7
	 */
	protected function targetClass(string $version, string $concern, array $diverging): string
	{
		$root = 'VDM\\Joomla\\Componentbuilder\\Compiler\\Architecture\\';

		if (in_array($version, $diverging, true))
		{
			return $root . $version . '\\' . $concern;
		}

		return $root . $concern;
	}

	/**
	 * Get the shared configuration fixture.
	 *
	 * @return  Config
	 * @since   6.1.6
	 */
	protected function config(): Config
	{
		return $this->architectureConfig;
	}

	/**
	 * Get the shared placeholder fixture.
	 *
	 * @return  Placeholder
	 * @since   6.1.6
	 */
	protected function placeholder(): Placeholder
	{
		return $this->architecturePlaceholder;
	}

	/**
	 * Get the shared language fixture.
	 *
	 * @return  Language
	 * @since   6.1.6
	 */
	protected function language(): Language
	{
		return $this->architectureLanguage;
	}

	/**
	 * Get the shared permission fixture.
	 *
	 * @return  Permission
	 * @since   6.1.6
	 */
	protected function permission(): Permission
	{
		return $this->architecturePermission;
	}

	/**
	 * Restore process-static renderer utilities before normal test cleanup.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	protected function tearDown(): void
	{
		try
		{
			foreach ($this->staticUtilityState as $key => $state)
			{
				[$class, $property] = explode('::', $key, 2);
				$reflection = new ReflectionProperty($class, $property);

				if ($state['initialized'])
				{
					$reflection->setValue(null, $state['value']);
				}
				else
				{
					// Typed static properties cannot be returned to an uninitialized
					// state. The injected values are their canonical defaults.
				}
			}
		}
		finally
		{
			$this->staticUtilityState = [];
			parent::tearDown();
		}
	}

	/**
	 * Resolve a production collaborator without booting the compiler factory.
	 *
	 * @param   class-string|null  $type  Declared dependency type.
	 *
	 * @return  object
	 * @since   6.1.6
	 */
	private function collaborator(?string $type): object
	{
		return match ($type)
		{
			Config::class => $this->architectureConfig,
			Placeholder::class => $this->architecturePlaceholder,
			Language::class => $this->architectureLanguage,
			Permission::class => $this->architecturePermission,
			Component::class => $this->createComponent(),
			CustomButtons::class => $this->createCustomButtons(),
			DynamicButtons::class => new DynamicButtons(
				$this->architectureConfig,
				new DynamicButtonsBuilder(),
				$this->architectureLanguage
			),
			ToolbarComposer::class => new ToolbarComposer(),
			Dispenser::class => $this->createDispenser(),
			Structure::class => $this->createStub(Structure::class),
			CompilerFile::class => $this->createStub(CompilerFile::class),
			default => $this->createGenericCollaborator($type),
		};
	}

	/**
	 * Create a no-custom-code dispenser double.
	 *
	 * @return  Dispenser
	 * @since   6.1.6
	 */
	private function createDispenser(): Dispenser
	{
		$dispenser = $this->createStub(Dispenser::class);
		$dispenser->method('get')->willReturn('');

		return $dispenser;
	}

	/**
	 * Create a compiler Component registry without loading component data.
	 *
	 * @return  Component
	 * @since   6.1.6
	 */
	private function createComponent(): Component
	{
		$data = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
		$event = $this->createStub(EventInterface::class);

		return new Component($data, $event);
	}

	/**
	 * Create the real Permission collaborator with empty focused registries.
	 *
	 * @return  Permission
	 * @since   6.1.6
	 */
	private function createPermission(): Permission
	{
		$valuation = (new ReflectionClass(Valuation::class))->newInstanceWithoutConstructor();

		return new Permission(
			$this->architectureConfig,
			new PermissionCore(),
			new PermissionViews(),
			new PermissionAction(),
			new PermissionComponent(),
			new PermissionGlobalAction(),
			new PermissionDashboard(),
			new Counter($valuation),
			$this->architectureLanguage
		);
	}

	/**
	 * Create the real CustomButtons coordinator on its no-button path.
	 *
	 * @return  CustomButtons
	 * @since   6.1.6
	 */
	private function createCustomButtons(): CustomButtons
	{
		return new CustomButtons(
			$this->architectureConfig,
			new ContentOne(),
			new ContentMulti(),
			new CustomForm(),
			new OnlyFunctionButtons(),
			$this->createStub(Structure::class),
			$this->architectureLanguage,
			$this->architecturePlaceholder,
			new Registry()
		);
	}

	/**
	 * Create an interface double, real registry, or dormant final dependency.
	 *
	 * @param   class-string|null  $type  Declared dependency type.
	 *
	 * @return  object
	 * @since   6.1.6
	 */
	private function createGenericCollaborator(?string $type): object
	{
		if ($type === null)
		{
			return new \stdClass();
		}

		$reflection = new ReflectionClass($type);

		if ($reflection->isInterface())
		{
			$stub = $this->createStub($type);

			if (method_exists($stub, 'get'))
			{
				$stub->method('get')->willReturn('');
			}

			return $stub;
		}

		if ($reflection->isSubclassOf(VdmRegistry::class)
			|| $reflection->isSubclassOf(Registry::class))
		{
			$constructor = $reflection->getConstructor();

			if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0)
			{
				return $reflection->newInstance();
			}
		}

		$constructor = $reflection->getConstructor();

		if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0)
		{
			return $reflection->newInstance();
		}

		if (!$reflection->isFinal())
		{
			return $this->createStub($type);
		}

		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Replace and remember one private static utility property.
	 *
	 * @param   class-string  $class     Utility class.
	 * @param   string        $property  Property name.
	 * @param   mixed         $value     Deterministic test value.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	private function replaceStaticProperty(string $class, string $property, mixed $value): void
	{
		$reflection = new ReflectionProperty($class, $property);
		$key = $class . '::' . $property;

		$this->staticUtilityState[$key] = [
			'initialized' => $reflection->isInitialized(),
			'value' => $reflection->isInitialized() ? $reflection->getValue() : null,
		];

		$reflection->setValue(null, $value);
	}
}
