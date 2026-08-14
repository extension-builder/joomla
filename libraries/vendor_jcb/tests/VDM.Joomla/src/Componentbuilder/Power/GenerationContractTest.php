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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
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
 * Power parsing, discovery, and generated dependency-injection contracts.
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
#[CoversClass(Parser::class)]
#[CoversClass(Plantuml::class)]
#[UsesClass(Indent::class)]
final class GenerationContractTest extends CompilerUtilityTestCase
{
	/**
	 * Protect structured parsing of properties, methods, types, versions, and bodies.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	public function testParserExtractsClassMetadataAndSupportingSections(): void
	{
		$subject = new Parser();
		$code = <<<'PHP'
<?php
/** License text */
namespace Demo;

use Alpha\One;
use Beta\Two as Two;

final class Widget
{
	use FirstTrait, SecondTrait;

	/** Property docs */
	protected ?string $name = 'demo';

	/**
	 * Run.
	 * @param int $count Count.
	 * @return string
	 * @since 2.3.4
	 */
	final public function run(int $count = 2): string
	{
		return 'done';
	}
}
PHP;
		$parsed = $subject->code($code);

		$this->assertSame('$name', $parsed['properties'][0]['name']);
		$this->assertSame('protected', $parsed['properties'][0]['access']);
		$this->assertSame('?string', $parsed['properties'][0]['type']);
		$this->assertSame("'demo'", $parsed['properties'][0]['default']);
		$this->assertSame('Property docs', $parsed['properties'][0]['comment']);
		$this->assertSame('run', $parsed['methods'][0]['name']);
		$this->assertTrue($parsed['methods'][0]['final']);
		$this->assertSame('string', $parsed['methods'][0]['return_type']);
		$this->assertSame('2.3.4', $parsed['methods'][0]['since']);
		$this->assertSame(['name' => '$count', 'type' => 'int', 'default' => '2'], $parsed['methods'][0]['arguments']['$count']);
		$this->assertSame("\n\t\treturn 'done';\n\t", $parsed['methods'][0]['body']);
		$this->assertSame('* License text', $subject->getClassLicense($code));
		$this->assertSame(['use Alpha\\One;', 'use Beta\\Two as Two;'], $subject->getUseStatements($code));
		$this->assertSame(['FirstTrait', 'SecondTrait'], $subject->getTraits($subject->getClassCode($code)));
	}

	/**
	 * Record standard static-property modifier ordering as an intended parser input.
	 *
	 * @return  void
	 * @since   6.1.6
	 */
	#[Group('known-defect')]
	public function testParserRecognizesStandardStaticTypedPropertyOrdering(): void
	{
		$parsed = (new Parser())->code('class Demo { protected static ?string $name = null; }');

		$this->assertSame('$name', $parsed['properties'][0]['name']);
		$this->assertTrue($parsed['properties'][0]['static']);
	}

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
}
