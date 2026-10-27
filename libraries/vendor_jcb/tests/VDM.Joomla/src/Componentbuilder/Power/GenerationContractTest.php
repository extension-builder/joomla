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


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Extrusion\Powers\Reader\ClassFile;
use VDM\Joomla\Componentbuilder\Power\Generator;
use VDM\Joomla\Componentbuilder\Power\Generator\Bucket;
use VDM\Joomla\Componentbuilder\Power\Generator\ClassInjector;
use VDM\Joomla\Componentbuilder\Power\Generator\ClassInjectorBuilder;
use VDM\Joomla\Componentbuilder\Power\Generator\Search;
use VDM\Joomla\Componentbuilder\Power\Generator\ServiceProvider;
use VDM\Joomla\Componentbuilder\Power\Generator\ServiceProviderBuilder;
use VDM\Joomla\Componentbuilder\Power\Parser;
use VDM\Joomla\Componentbuilder\Power\Plantuml;
use VDM\Joomla\Data\Action\Load as Database;
use VDM\Tests\Support\CompilerUtilityTestCase;


/**
 * Power discovery and generated dependency-injection contracts.
 *
 * Structural parsing itself is owned by ParserTest; the parser appears here
 * only as the real collaborator the discovery and generation paths run on.
 *
 * @since  6.1.6
 */
#[CoversClass(Generator::class)]
#[CoversClass(Bucket::class)]
#[CoversClass(ClassInjector::class)]
#[CoversClass(ClassInjectorBuilder::class)]
#[CoversClass(Search::class)]
#[CoversClass(ServiceProvider::class)]
#[CoversClass(ServiceProviderBuilder::class)]
#[CoversClass(Plantuml::class)]
#[UsesClass(Indent::class)]
#[UsesClass(Parser::class)]
#[UsesClass(ClassFile::class)]
final class GenerationContractTest extends CompilerUtilityTestCase
{
	/**
	 * Protect exact generated property, constructor, docblock, and reset output.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testClassInjectorGeneratesExactTabIndentedConstructor(): void
	{
		$subject = new ClassInjector();
		$subject->setVersion('2.3.4');
		$subject->setProperty('widget', 'Widget', 'The Widget Class.');
		$subject->setComment('widget', 'Widget', 'The Widget Class.');
		$subject->setArgument('widget', 'Widget');
		$subject->setAssignment('widget');
		$expected = implode(PHP_EOL, [
			"\t/**",
			"\t * The Widget Class.",
			"\t *",
			"\t * @var   Widget",
			"\t * @since 2.3.4",
			"\t */",
			"\tprotected Widget \$widget;",
			'',
			"\t/**",
			"\t * Constructor.",
			"\t *",
			"\t * @param Widget   \$widget   The Widget Class.",
			"\t *",
			"\t * @since 2.3.4",
			"\t */",
			"\tpublic function __construct(Widget \$widget)",
			"\t{",
			"\t\t\$this->widget = \$widget;",
			"\t}",
		]);

		$this->assertSame($expected, $subject->getCode());
		$this->assertNull($subject->getCode());
	}

	/**
	 * Protect service aliases, sharing, dependency order, and one-shot state.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testServiceProviderGeneratesAliasShareAndDependencyFactory(): void
	{
		$subject = new ServiceProvider();
		$subject->setVersion('2.3.4');
		$subject->setRegisterLine('Widget', 'getWidget', 'Demo.Widget');
		$subject->setGetFunction('Widget', 'getWidget', 'Get the Widget Class.', ['Config', 'Table']);

		$code = $subject->getCode();
		$this->assertStringContainsString("\t\t\$container->alias(Widget::class, 'Demo.Widget')", $code);
		$this->assertStringContainsString("\t\t\t->share('Demo.Widget', [\$this, 'getWidget'], true);", $code);
		$this->assertStringContainsString("\tpublic function getWidget(Container \$container): Widget", $code);
		$this->assertStringContainsString(
			"\t\treturn new Widget(" . PHP_EOL
				. "\t\t\t\$container->get('Config')," . PHP_EOL
				. "\t\t\t\$container->get('Table')" . PHP_EOL
				. "\t\t);",
			$code
		);
		$this->assertNull($subject->getCode());
	}

	/**
	 * Protect cached discovery, validity, names, aliases, and dependency ordering.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testSearchDerivesPowerContractsFromBucketAndQueriesOnce(): void
	{
		$databasePower = (object) [
			'guid' => 'loaded',
			'type' => 'trait',
			'name' => 'LoadedTrait',
			'namespace' => 'VDM\\Demo.LoadedTrait',
		];
		$database = $this->createMock(Database::class);
		$database->expects($this->once())->method('table')->with('power')->willReturnSelf();
		$database->expects($this->once())->method('item')->with(['guid' => 'loaded'])->willReturn($databasePower);
		$bucket = $this->bucket();
		$subject = new Search($database, new Parser(), $bucket);

		$this->assertSame($databasePower, $subject->power('loaded'));
		$this->assertSame($databasePower, $subject->power('loaded'));
		$this->assertFalse($subject->validInject('loaded'));
		$this->assertTrue($subject->validInject('dependency'));
		$this->assertSame('Widget', $subject->name('dependency'));
		$this->assertSame('AliasName', $subject->name('dependency', 'AliasName'));
		$this->assertSame('The Widget Class.', $subject->description('dependency'));
		$this->assertSame('Widget.Service', $subject->alias('dependency', 'Widget'));
		$this->assertSame(['Widget.Service'], $subject->dependencies('consumer'));
	}

	/**
	 * Protect builder selection between constructor injection and service providers.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testGeneratorDispatchesBothPowerGenerationArchitectures(): void
	{
		$search = new Search($this->createStub(Database::class), new Parser(), $this->bucket());
		$classBuilder = new ClassInjectorBuilder($search, new ClassInjector());
		$serviceBuilder = new ServiceProviderBuilder($search, new ServiceProvider());
		$subject = new Generator($classBuilder, $serviceBuilder);
		$base = [
			'main_class_code' => '',
			'use_selection' => [['use' => 'dependency', 'as' => 'default']],
			'description' => 'Generated service. @since 4.5.6',
			'power_version' => '1.0.0',
		];

		$classCode = $subject->get($base + ['implements_custom' => '']);
		$this->assertStringContainsString("\tprotected Widget \$widget;", $classCode);
		$this->assertStringContainsString(' * @since 4.5.6', $classCode);
		$this->assertStringContainsString('public function __construct(Widget $widget)', $classCode);

		$serviceCode = $subject->get($base + ['implements_custom' => 'ServiceProviderInterface']);
		$this->assertStringContainsString("->share('Widget.Service', [\$this, 'getWidget'], true);", $serviceCode);
		$this->assertStringContainsString('public function getWidget(Container $container): Widget', $serviceCode);
		$this->assertStringContainsString('return new Widget();', $serviceCode);
		$this->assertNull($subject->get(array_replace($base, [
			'main_class_code' => 'already implemented',
			'implements_custom' => '',
		])));
	}

	/**
	 * Preserve the comment that keeps each inherited wrapper out of auto-generation.
	 *
	 * @param   string  $path  The original compiler subclass below its source root.
	 *
	 * @return  void
	 * @since   6.2.1
	 */
	#[DataProvider('inheritedCompilerWrappers')]
	public function testParsedCompilerWrapperCommentsPreserveInheritedConstructors(string $path): void
	{
		$source = file_get_contents(JCB_PROJECT_ROOT . '/libraries/vendor_jcb/VDM.Joomla/src/Componentbuilder/Compiler/' . $path);
		$parts = (new ClassFile(new Parser()))->read($source);
		$this->assertNotNull($parts);
		$this->assertSame("\t// Preserve the inherited constructor when JCB saves this Power.", $parts['body']);
		$this->assertCount(1, $parts['uses']);
		$this->assertSame([$parts['uses'][0]['alias']], $parts['extends']);
		$parentGuid = '46fbc151-4fe5-4b35-86df-6e48f7ec506c';
		$parentName = substr($parts['uses'][0]['name'], strrpos($parts['uses'][0]['name'], '\\') + 1);
		$bucket = $this->bucket();
		$bucket->set('power.' . $parentGuid, (object) [
			'guid' => $parentGuid,
			'type' => 'class',
			'name' => $parentName,
			'namespace' => $parts['uses'][0]['name'],
		]);
		$subject = $this->generator($bucket);

		// Extrusion retains an aliased parent in both custom inheritance and use_selection.
		$definition = [
			'name' => $parts['class'],
			'type' => $parts['type'],
			'main_class_code' => $parts['body'],
			'description' => $parts['docblock'],
			'power_version' => '1.0.0',
			'extends' => '-1',
			'extends_custom' => $parts['extends'][0],
			'implements_custom' => '',
			'use_selection' => [
				'use_selection0' => ['use' => $parentGuid, 'as' => $parts['uses'][0]['alias']],
			],
		];

		$this->assertNull($subject->get($definition), 'Saving the extracted subclass must preserve its inherited constructor.');

		// An intentionally empty code field still requests JCB's existing DI scaffolding.
		$emptyDefinition = array_replace($definition, ['main_class_code' => '']);
		$this->assertStringContainsString(
			'public function __construct(' . $parts['extends'][0] . ' $' . strtolower($parts['extends'][0]) . ')',
			$subject->get($emptyDefinition)
		);
	}

	/**
	 * The 25 inherited target variants that must preserve their nonempty Power bodies.
	 *
	 * @return  array<string, array{string}>  Source paths named by compiler variant.
	 * @since   6.2.1
	 */
	public static function inheritedCompilerWrappers(): array
	{
		$cases = [];

		foreach (['JoomlaThree', 'JoomlaFour', 'JoomlaFive'] as $version)
		{
			foreach (['CustomButtons', 'DynamicButtons', 'View/GoogleChartLoader', 'View/Jquery', 'AdminView/ViewScript'] as $renderer)
			{
				$path = 'Architecture/' . $version . '/' . $renderer . '.php';
				$cases[$path] = [$path];
			}

			foreach (['Creator' => 'ConfigFieldsetsEmailHelper', 'Library' => 'IncludeHelper'] as $domain => $renderer)
			{
				$path = $domain . '/' . $version . '/' . $renderer . '.php';
				$cases[$path] = [$path];
			}
		}

		foreach (['JoomlaFour', 'JoomlaFive'] as $version)
		{
			foreach (['View/LibrariesLoader', 'AdminView/FootableScripts'] as $renderer)
			{
				$path = 'Architecture/' . $version . '/' . $renderer . '.php';
				$cases[$path] = [$path];
			}
		}

		return $cases;
	}

	/**
	 * Protect stable PlantUML colors, access signs, tags, and namespace depth.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testPlantumlBuildsExactBasicClassAndNamespaceDiagram(): void
	{
		$subject = new Plantuml();
		$code = [
			'properties' => [[
				'access' => 'protected',
				'static' => true,
				'type' => 'string',
				'name' => '$name',
			]],
			'methods' => [[
				'access' => 'public',
				'static' => false,
				'abstract' => false,
				'name' => 'run',
				'return_type' => 'void',
			]],
		];
		$class = "\n  class Widget << (F,LightGreen) >> #RoyalBlue {\n"
			. "    # {static} string \$name\n"
			. "    + run() : void\n"
			. "  }\n";

		$this->assertSame($class, $subject->classBasicDiagram(['name' => 'Widget', 'type' => 'final class'], $code));
		$this->assertSame(
			"namespace VDM\\Demo #Azure {\n\n{$class}}\n",
			$subject->namespaceDiagram('VDM\\Demo', $class)
		);
	}

	/**
	 * Build deterministic preloaded powers and cache values.
	 *
	 * @return  Bucket
	 * @since   6.1.6
	 */
	private function bucket(): Bucket
	{
		$bucket = new Bucket();
		$bucket->set('power.dependency', (object) [
			'guid' => 'dependency',
			'type' => 'class',
			'name' => 'Widget',
			'namespace' => 'VDM\\Demo.WidgetService',
			'use_selection' => null,
		]);
		$bucket->set('power.consumer', (object) [
			'guid' => 'consumer',
			'type' => 'class',
			'name' => 'Consumer',
			'namespace' => 'VDM\\Demo.Consumer',
			'use_selection' => (object) [(object) ['use' => 'dependency', 'as' => 'default']],
		]);
		$bucket->set('service_providers.dependency', []);

		return $bucket;
	}

	/**
	 * Compose the real save-time generator with a deterministic Power catalogue.
	 *
	 * @param   Bucket  $bucket  Preloaded selected Power definitions.
	 *
	 * @return  Generator
	 * @since   6.2.1
	 */
	private function generator(Bucket $bucket): Generator
	{
		$database = $this->createMock(Database::class);
		$database->expects($this->never())->method('table');
		$search = new Search($database, new Parser(), $bucket);

		return new Generator(
			new ClassInjectorBuilder($search, new ClassInjector()),
			new ServiceProviderBuilder($search, new ServiceProvider())
		);
	}
}
